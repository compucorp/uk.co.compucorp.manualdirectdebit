<?php

use CRM_ManualDirectDebit_Common_MessageTemplate as MessageTemplate;
use CRM_ManualDirectDebit_Test_Fabricator_Contact as ContactFabricator;
use CRM_ManualDirectDebit_Test_Fabricator_Mandate as MandateFabricator;
use CRM_ManualDirectDebit_Test_Fabricator_Setting as SettingFabricator;
use CRM_MembershipExtras_Test_Fabricator_MembershipType as MembershipTypeFabricator;

require_once __DIR__ . '/../../../BaseHeadlessTest.php';

/**
 * Runs tests on the "Print Direct Debit Letters" membership task.
 *
 * @group headless
 */
class CRM_ManualDirectDebit_Form_PrintMergeDocumentTest extends BaseHeadlessTest {

  use CRM_ManualDirectDebit_Test_Helper_PaymentPlanTrait;

  /**
   * The task being tested.
   *
   * @var CRM_ManualDirectDebit_Form_PrintMergeDocument
   */
  private $form;

  /**
   * Writes mandates and their links to contributions.
   *
   * @var CRM_ManualDirectDebit_Common_MandateStorageManager
   */
  private $mandateStorage;

  /**
   * Sets up the task and the Direct Debit settings mandates need.
   */
  public function setUp() {
    SettingFabricator::fabricate();
    $this->mandateStorage = new CRM_ManualDirectDebit_Common_MandateStorageManager();
    $this->form = new CRM_ManualDirectDebit_Form_PrintMergeDocument();
  }

  /**
   * Tests every Direct Debit template the extension installs renders.
   *
   * The letters are generated from the templates shipped with the extension,
   * so this is the check that matters: each one must come out with its Direct
   * Debit content resolved, and with no Smarty markup left behind.
   */
  public function testInstalledDirectDebitTemplatesRender() {
    $membershipId = $this->setUpDirectDebitMembership();

    foreach (MessageTemplate::getDefaultDirectDebitTemplates() as $template) {
      $html = $this->generateLetter($membershipId, $this->getInstalledTemplateBody($template['name']));

      $this->assertNotEmpty($html, 'No letter was generated for ' . $template['name']);
      $this->assertContains('Lloyds Bank', $html, 'Mandate details are missing from ' . $template['name']);
      $this->assertContains('debit.png', $html, 'The Direct Debit logo is missing from ' . $template['name']);
      $this->assertContains('The Direct Debit Guarantee', $html, 'The guarantee is missing from ' . $template['name']);
      $this->assertUnresolvedMarkupIsAbsent($html, $template['name']);
    }
  }

  /**
   * Tests the template parameters and the contact tokens both resolve.
   *
   * Direct Debit templates mix Smarty variables collected per membership with
   * ordinary CiviCRM tokens, and both have to survive the same render.
   */
  public function testTemplateParamsAndTokensAreResolved() {
    $membershipId = $this->setUpDirectDebitMembership();
    $contactId = civicrm_api3('Membership', 'getvalue', [
      'id' => $membershipId,
      'return' => 'contact_id',
    ]);
    $displayName = civicrm_api3('Contact', 'getvalue', [
      'id' => $contactId,
      'return' => 'display_name',
    ]);

    $body = '<p>{contact.display_name}</p>'
      . '<p>{$mandateData.bank_name} / {$mandateData.account_holder_name}</p>'
      . '<img src="{$directDebitImageSrc}" />'
      . '<p>{ts}Thank you for choosing Direct Debit.{/ts}</p>';

    $html = $this->generateLetter($membershipId, $body);

    $this->assertContains($displayName, $html, 'The contact token was not resolved');
    $this->assertContains('Lloyds Bank', $html, 'The mandate template parameter was not resolved');
    $this->assertContains('John Doe', $html, 'The account holder template parameter was not resolved');
    $this->assertContains('debit.png', $html, 'The Direct Debit logo was not resolved');
    $this->assertContains('Thank you for choosing Direct Debit.', $html, 'The translated string was not rendered');
    $this->assertUnresolvedMarkupIsAbsent($html, 'the test template');
  }

  /**
   * Tests a membership with nothing to bill is reported rather than fatal.
   *
   * The task collects these and logs them once the rest of the letters have
   * been generated, so the exception has to reach it intact.
   */
  public function testMembershipWithoutAContributionIsRejected() {
    $membershipId = $this->createMembershipWithoutAContribution();

    // Asserted on the message rather than the class: the collector throws
    // CRM_Core_Exception here and CiviCRM_API3_Exception on the 6.8.x line.
    $this->expectException(Exception::class);
    $this->expectExceptionMessage("Can't find contribution id by membership id");

    $this->generateLetter($membershipId, '<p>{$mandateData.bank_name}</p>');
  }

  /**
   * Tests the contact lookup that decides who a letter is recorded against.
   *
   * Activities are recorded per contact, so a contact holding more than one
   * membership has to resolve to a single contact ID, and a membership that
   * was not asked for must not appear at all.
   */
  public function testContactLookupResolvesEachMembershipToItsContact() {
    $firstMembershipId = $this->setUpDirectDebitMembership();
    $contactId = civicrm_api3('Membership', 'getvalue', [
      'id' => $firstMembershipId,
      'return' => 'contact_id',
    ]);
    $secondMembershipId = $this->addMembershipToContact($contactId);
    $otherMembershipId = $this->createMembershipWithoutAContribution();

    $contactIds = $this->getContactIdsKeyedByMembership([$firstMembershipId, $secondMembershipId]);

    $this->assertEquals(
      [$firstMembershipId => $contactId, $secondMembershipId => $contactId],
      $contactIds,
      'Both memberships should resolve to the one contact that holds them'
    );
    $this->assertCount(1, array_unique($contactIds), 'The contact should collapse to a single activity recipient');
    $this->assertArrayNotHasKey($otherMembershipId, $contactIds, 'Only the requested memberships should be returned');
  }

  /**
   * Gives an existing contact a second membership.
   *
   * @param int $contactId
   *   Contact to add the membership to.
   *
   * @return int
   *   ID of the created membership.
   */
  private function addMembershipToContact($contactId) {
    $membershipType = MembershipTypeFabricator::fabricate([
      'name' => 'Test Second Membership',
      'period_type' => 'rolling',
      'minimum_fee' => 60,
      'duration_interval' => 1,
      'duration_unit' => 'year',
    ]);

    return civicrm_api3('Membership', 'create', [
      'contact_id' => $contactId,
      'membership_type_id' => $membershipType['id'],
      'join_date' => '2026-01-01',
      'start_date' => '2026-01-01',
    ])['id'];
  }

  /**
   * Generates a single Direct Debit letter.
   *
   * @param int $membershipId
   *   Membership to generate the letter for.
   * @param string $body
   *   Body of the message template to render.
   *
   * @return string
   *   The rendered letter.
   */
  private function generateLetter($membershipId, $body) {
    $method = new ReflectionMethod($this->form, 'generateDirectDebitHTML');
    $method->setAccessible(TRUE);

    $contactIds = $this->getContactIdsKeyedByMembership([$membershipId]);

    return $method->invoke($this->form, $membershipId, $contactIds[$membershipId], $body);
  }

  /**
   * Calls the form's membership to contact lookup.
   *
   * @param array $membershipIds
   *   Memberships to look up.
   *
   * @return array
   *   Contact ID, keyed by membership ID.
   */
  private function getContactIdsKeyedByMembership($membershipIds) {
    $method = new ReflectionMethod($this->form, 'getContactIdsKeyedByMembership');
    $method->setAccessible(TRUE);

    return $method->invoke($this->form, $membershipIds);
  }

  /**
   * Creates a Direct Debit payment plan membership with a mandate.
   *
   * @return int
   *   ID of the created membership.
   */
  private function setUpDirectDebitMembership() {
    $recurringContribution = $this->setupPlan('2026-01-01', '2026-01-01');
    $membershipId = $recurringContribution['membership_id'];

    $contributionId = civicrm_api3('MembershipPayment', 'getvalue', [
      'membership_id' => $membershipId,
      'return' => 'contribution_id',
      'options' => ['sort' => 'contribution_id DESC', 'limit' => 1],
    ]);

    $mandate = MandateFabricator::fabricate(['entity_id' => $recurringContribution['contact_id']]);
    $this->mandateStorage->assignRecurringContributionMandate($recurringContribution['id'], $mandate['id']);
    $this->mandateStorage->assignContributionMandate($contributionId, $mandate['id']);

    return $membershipId;
  }

  /**
   * Creates a membership that has no contribution attached to it.
   *
   * @return int
   *   ID of the created membership.
   */
  private function createMembershipWithoutAContribution() {
    $contact = ContactFabricator::fabricate();
    $membershipType = MembershipTypeFabricator::fabricate([
      'name' => 'Test Membership Without Payment',
      'period_type' => 'rolling',
      'minimum_fee' => 120,
      'duration_interval' => 1,
      'duration_unit' => 'year',
    ]);

    return civicrm_api3('Membership', 'create', [
      'contact_id' => $contact['id'],
      'membership_type_id' => $membershipType['id'],
      'join_date' => '2026-01-01',
      'start_date' => '2026-01-01',
    ])['id'];
  }

  /**
   * Reads the body of one of the installed Direct Debit templates.
   *
   * @param string $templateName
   *   Machine name of the Direct Debit template.
   *
   * @return string
   *   Body of the message template.
   */
  private function getInstalledTemplateBody($templateName) {
    $templateId = MessageTemplate::getTemplateIdByName($templateName);
    $this->assertNotEmpty($templateId, 'The ' . $templateName . ' template was not installed');

    return civicrm_api3('MessageTemplate', 'getvalue', [
      'id' => $templateId,
      'return' => 'msg_html',
    ]);
  }

  /**
   * Asserts a rendered letter has no Smarty markup left in it.
   *
   * @param string $html
   *   The rendered letter.
   * @param string $templateName
   *   Name of the template, used in the failure message.
   */
  private function assertUnresolvedMarkupIsAbsent($html, $templateName) {
    foreach (['{$', '{ts}', '{if ', '{foreach '] as $markup) {
      $this->assertNotContains($markup, $html, 'Unresolved "' . $markup . '" left in ' . $templateName);
    }
  }

}
