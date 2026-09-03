<?php

use CRM_ManualDirectDebit_Common_MessageTemplate as MessageTemplate;

require_once __DIR__ . '/../../BaseHeadlessTest.php';

/**
 * Runs tests on the installation of the Direct Debit message templates.
 *
 * @group headless
 */
class CRM_ManualDirectDebit_UpgraderTest extends BaseHeadlessTest {

  /**
   * The extension's upgrader.
   *
   * @var CRM_ManualDirectDebit_Upgrader
   */
  private $upgrader;

  /**
   * setUp test
   */
  public function setUp(): void {
    $this->upgrader = CRM_Extension_System::singleton()
      ->getMapper()
      ->getUpgrader('uk.co.compucorp.manualdirectdebit');

    $this->assertNotNull($this->upgrader, 'The extension upgrader could not be loaded');
  }

  /**
   * Tests installing the templates again does not duplicate them.
   */
  public function testInstallingTheTemplatesAgainDoesNotDuplicateThem() {
    $this->assertTrue($this->upgrader->upgrade_0006(), 'The first run failed');
    $this->assertTrue($this->upgrader->upgrade_0006(), 'The second run failed');

    foreach (MessageTemplate::getDefaultDirectDebitTemplates() as $template) {
      $this->assertCount(
        1,
        $this->getTemplateIdsByTitle($template['title']),
        'There should be one "' . $template['title'] . '" template, however many times the installer runs'
      );

      $this->assertCount(
        1,
        $this->getTemplateIdsByMachineName($template['name']),
        'There should be one template carrying the machine name ' . $template['name']
      );
    }
  }

  /**
   * Tests every installed template is marked as a Direct Debit template.
   */
  public function testEveryInstalledTemplateIsMarkedAsADirectDebitTemplate() {
    foreach (MessageTemplate::getDefaultDirectDebitTemplates() as $template) {
      $templateId = MessageTemplate::getTemplateIdByName($template['name']);

      $this->assertNotEmpty($templateId, 'The ' . $template['name'] . ' template was not installed');
      $this->assertTrue(
        MessageTemplate::isDirectDebitTemplate($templateId),
        'The ' . $template['name'] . ' template is not marked as a Direct Debit template'
      );
    }
  }

  /**
   * Tests templates that lost their machine names are adopted, not duplicated.
   */
  public function testTemplatesThatLostTheirMachineNamesAreAdoptedRatherThanDuplicated() {
    $templateIds = [];
    foreach (MessageTemplate::getDefaultDirectDebitTemplates() as $template) {
      $templateIds[$template['name']] = (int) MessageTemplate::getTemplateIdByName($template['name']);
      $this->assertNotEmpty($templateIds[$template['name']], 'The ' . $template['name'] . ' template was not installed');
    }

    $this->stripAllDirectDebitCustomValues();

    $this->assertTrue($this->upgrader->upgrade_0006(), 'The installer run failed');

    foreach (MessageTemplate::getDefaultDirectDebitTemplates() as $template) {
      $templateId = $templateIds[$template['name']];

      $this->assertEquals(
        [$templateId],
        $this->getTemplateIdsByTitle($template['title']),
        'The ' . $template['name'] . ' template should have been adopted, not duplicated'
      );
      $this->assertTrue(
        MessageTemplate::isDirectDebitTemplate($templateId),
        'The adopted ' . $template['name'] . ' template should have been re-marked'
      );
    }
  }

  /**
   * Tests a template the extension did not create is left alone.
   */
  public function testATemplateTheExtensionDidNotCreateIsLeftAlone() {
    $template = $this->defaultTemplate(MessageTemplate::AUTO_RENEW_MSG_NAME);
    $ourTemplateId = (int) MessageTemplate::getTemplateIdByName($template['name']);
    $this->assertNotEmpty($ourTemplateId, 'The ' . $template['name'] . ' template was not installed');

    // The admin deletes ours and writes their own under the same title.
    civicrm_api3('MessageTemplate', 'delete', ['id' => $ourTemplateId]);
    $theirBody = '<p>Our own wording, thanks.</p>';
    $theirTemplateId = $this->createMessageTemplate($template['title'], $theirBody);

    $this->assertTrue($this->upgrader->upgrade_0006(), 'The installer run failed');

    $this->assertFalse(
      MessageTemplate::isDirectDebitTemplate($theirTemplateId),
      'Their template must not be marked as a Direct Debit template'
    );
    $this->assertEquals(
      $theirBody,
      $this->getTemplateBody($theirTemplateId),
      'Their template body must not have been touched'
    );

    $installedTemplateId = (int) MessageTemplate::getTemplateIdByName($template['name']);
    $this->assertNotEmpty($installedTemplateId, 'The shipped template should have been installed alongside theirs');
    $this->assertNotEquals($theirTemplateId, $installedTemplateId, 'The shipped template should be a new one, not theirs');
  }

  /**
   * Returns one of the templates the extension installs.
   *
   * @param string $machineName
   *   Machine name of the template.
   *
   * @return array
   *   Machine name, title and template file of the template.
   */
  private function defaultTemplate($machineName) {
    foreach (MessageTemplate::getDefaultDirectDebitTemplates() as $template) {
      if ($template['name'] === $machineName) {
        return $template;
      }
    }

    $this->fail($machineName . ' is not one of the templates the extension installs');
  }

  /**
   * Creates a message template with no Direct Debit custom values.
   *
   * @param string $title
   *   Title of the template.
   * @param string $body
   *   Body of the template.
   *
   * @return int
   *   ID of the created template.
   */
  private function createMessageTemplate($title, $body) {
    return (int) civicrm_api3('MessageTemplate', 'create', [
      'msg_title' => $title,
      'msg_subject' => $title,
      'msg_text' => 'N/A',
      'msg_html' => $body,
      'is_active' => 1,
      'is_reserved' => 0,
    ])['id'];
  }

  /**
   * Removes every message template's Direct Debit custom values.
   */
  private function stripAllDirectDebitCustomValues() {
    CRM_Core_DAO::executeQuery('DELETE FROM civicrm_value_direct_debit_message_template');
  }

  /**
   * Returns a message template's body.
   *
   * @param int $templateId
   *   ID of the message template.
   *
   * @return string
   */
  private function getTemplateBody($templateId) {
    return civicrm_api3('MessageTemplate', 'getvalue', [
      'id' => $templateId,
      'return' => 'msg_html',
    ]);
  }

  /**
   * Returns the IDs of every message template with the given title.
   *
   * @param string $title
   *   Title to match.
   *
   * @return array
   *   Message template IDs, oldest first.
   */
  private function getTemplateIdsByTitle($title) {
    return $this->getTemplateIds(['msg_title' => $title]);
  }

  /**
   * Returns the IDs of every message template carrying the given machine name.
   *
   * @param string $machineName
   *   Machine name to match.
   *
   * @return array
   *   Message template IDs, oldest first.
   */
  private function getTemplateIdsByMachineName($machineName) {
    $machineNameCustomFieldId = civicrm_api3('CustomField', 'getvalue', [
      'return' => 'id',
      'custom_group_id' => 'direct_debit_message_template',
      'name' => 'template_machine_name',
    ]);

    return $this->getTemplateIds(['custom_' . $machineNameCustomFieldId => $machineName]);
  }

  /**
   * Returns the IDs of every message template matching the given filters.
   *
   * @param array $filters
   *   MessageTemplate.get parameters to match on.
   *
   * @return array
   *   Message template IDs, oldest first.
   */
  private function getTemplateIds($filters) {
    $templates = civicrm_api3('MessageTemplate', 'get', $filters + [
      'sequential' => 1,
      'return' => ['id'],
      'options' => ['limit' => 0, 'sort' => 'id ASC'],
    ]);

    $templateIds = [];
    foreach ($templates['values'] as $template) {
      $templateIds[] = (int) $template['id'];
    }

    return $templateIds;
  }

}
