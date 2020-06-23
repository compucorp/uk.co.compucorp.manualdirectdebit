<?php

use CRM_ManualDirectDebit_Test_Fabricator_Setting as SettingFabricator;
use CRM_ManualDirectDebit_Test_Fabricator_Contact as ContactFabricator;
use CRM_ManualDirectDebit_Test_Fabricator_Contribution as ContributionFabricator;
use CRM_ManualDirectDebit_Test_Fabricator_RecurringContribution as RecurringContributionFabricator;

require_once __DIR__ . '/../../../BaseHeadlessTest.php';

/**
 * Runs tests on MandateStorageManager.
 *
 * @group headless
 */
class CRM_ManualDirectDebit_Common_MandateStorageManagerTest extends BaseHeadlessTest {

  private $mandateValues = [];

  public function setUp() {
    SettingFabricator::fabricate();
  }

  public function tearDown() {
    \Civi::reset();
  }

  /**
   * Test assignRecurringContributionMandate function
   * @throws CiviCRM_API3_Exception
   * @throws Exception
   */
  public function testSaveDirectDebitMandate() {
    $contact = ContactFabricator::fabricate();

    $now = new DateTime();
    $this->mandateValues = [
      'entity_id' => $contact['id'],
      'bank_name' => 'Lloyds Bank',
      'account_holder_name' => 'John Doe',
      'ac_number' => '12345678',
      'sort_code' => '12-34-56',
      'dd_code' => 1,
      'dd_ref' => 'DD Ref',
      'start_date' => $now->format('Y-m-d H:i:s'),
      'authorisation_date' => $now->format('Y-m-d H:i:s'),
      'originator_number' => 1,
    ];

    $storageManager = new CRM_ManualDirectDebit_Common_MandateStorageManager();
    $mandate = $storageManager->saveDirectDebitMandate($contact['id'], $this->mandateValues);
    $this->assertNotNull($mandate->id);
    $this->assertEquals($mandate->bank_name, 'Lloyds Bank');
    $this->assertEquals($mandate->account_holder_name, 'John Doe');
    $this->assertEquals($mandate->ac_number, '12345678');
    $this->assertEquals($mandate->sort_code, '12-34-56');
    $this->assertNotNull($mandate->dd_code);
    //DD Ref should be generated without something else if not the value is 'DD Ref'
    $this->assertNotEquals($mandate->dd_ref, 'DD Ref');
    $this->assertNotNull($mandate->originator_number);


  }

  /**
   * @depends testSaveDirectDebitMandate
   * Test assignRecurringContributionMandate function
   * @throws CiviCRM_API3_Exception
   * @throws Exception
   */
  public function testAssignRecurringContributionMandate() {

    $fabricatedContact = ContactFabricator::fabricate();
    $recurringContribution = RecurringContributionFabricator::fabricate([
      'contact_id' => $fabricatedContact['id'],
      'amount' => 100,
      'frequency_interval' => 1,
    ]);

    $this->mandateValues['entity_id'] = $fabricatedContact['id'];
    $storageManager = new CRM_ManualDirectDebit_Common_MandateStorageManager();
    $mandate = $storageManager->saveDirectDebitMandate($fabricatedContact['id'], $this->mandateValues);
    $storageManager->assignRecurringContributionMandate($recurringContribution['id'], $mandate->id);

    $values['recurring_contribution_id'] = $recurringContribution['id'];

  }

  /**
   * @depends testAssignContributionMandate
   * @return void
   * @throws CiviCRM_API3_Exception
   * @throws Exception
   *
  public function testAssignContributionMandate() {
    $fabricatedContact = ContactFabricator::fabricate();
    $now = new DateTime();
    $fabricatedContribution = ContributionFabricator::fabricate([
      'financial_type_id' => "Member Dues",
      'total_amount' => 100,
      'receive_date' => $now->format('Y-m-d H:i:s'),
      'contact_id' => $fabricatedContact['id'],
    ]);
    $this->mandateValues['entity_id'] = $fabricatedContact['id'];
    $storageManager = new CRM_ManualDirectDebit_Common_MandateStorageManager();
    $mandate = $storageManager->saveDirectDebitMandate($fabricatedContact['id'], $this->mandateValues);
    $storageManager->assignContributionMandate($fabricatedContribution['id'], $mandate->id);

    $contribution =  civicrm_api3('Contribution', 'get', [
      'sequential' => 1,
      'id' => $fabricatedContribution['id'],
    ]);

    $this->assertNotEmpty($contribution['values']);
    $this->assertEquals($contribution['values'][0]['mandate_id'],  $mandate->id);

  }*/

}


