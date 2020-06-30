<?php

use CRM_ManualDirectDebit_Test_Fabricator_Setting as SettingFabricator;
use CRM_ManualDirectDebit_Test_Fabricator_Contact as ContactFabricator;
use CRM_ManualDirectDebit_Test_Fabricator_Contribution as ContributionFabricator;
use CRM_ManualDirectDebit_Test_Fabricator_RecurringContribution as RecurringContributionFabricator;
use CRM_ManualDirectDebit_Test_Fabricator_OriginatorNumber as OriginatorNumberFabricator;

require_once __DIR__ . '/../../../BaseHeadlessTest.php';

/**
 * Runs tests on MandateStorageManager.
 *
 * @group headless
 */
class CRM_ManualDirectDebit_Common_MandateStorageManagerTest extends BaseHeadlessTest {

  /**
   * @var array
   */
  protected $mandateValues = [];
  /**
   * @var array
   */
  protected $contact;

  /**
   * setUp test
   * @throws CiviCRM_API3_Exception
   */
  public function setUp() {
    $this->contact = ContactFabricator::fabricate();
    $originatorNumber = OriginatorNumberFabricator::fabricate()['values'][0]['value'];
    $now = new DateTime();
    $this->mandateValues = [
      'entity_id' => $this->contact['id'],
      'bank_name' => 'Lloyds Bank',
      'account_holder_name' => 'John Doe',
      'ac_number' => '12345678',
      'sort_code' => '12-34-56',
      'dd_code' => 1,
      'dd_ref' => 'DD Ref',
      'start_date' => $now->format('Y-m-d H:i:s'),
      'authorisation_date' => $now->format('Y-m-d H:i:s'),
      'originator_number' => $originatorNumber,
    ];

    //Fabricate default settings
    SettingFabricator::fabricate();

  }

  /**
   * Test assignRecurringContributionMandate function
   * @throws CiviCRM_API3_Exception
   * @throws Exception
   */
  public function testSaveDirectDebitMandate() {

    $storageManager = new CRM_ManualDirectDebit_Common_MandateStorageManager();
    $mandate = $storageManager->saveDirectDebitMandate($this->contact['id'], $this->mandateValues);
    $this->assertNotNull($mandate->id);
    $this->assertEquals($mandate->bank_name, 'Lloyds Bank');
    $this->assertEquals($mandate->account_holder_name, 'John Doe');
    $this->assertEquals($mandate->ac_number, '12345678');
    $this->assertEquals($mandate->sort_code, '12-34-56');
    $this->assertNotNull($mandate->dd_code);
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

    $recurringContribution = RecurringContributionFabricator::fabricate(['contact_id' => $this->contact['id']]);
    $this->mandateValues['entity_id'] = $this->contact['id'];
    $storageManager = new CRM_ManualDirectDebit_Common_MandateStorageManager();
    $mandate = $storageManager->saveDirectDebitMandate($this->contact['id'], $this->mandateValues);
    $storageManager->assignRecurringContributionMandate($recurringContribution['id'], $mandate->id);

    $recurrMandateId =
      CRM_ManualDirectDebit_BAO_RecurrMandateRef::getMandateIdForRecurringContribution($recurringContribution['id']);
    $this->assertEquals($recurrMandateId, $mandate->id);

  }

  /**
   * @depends testSaveDirectDebitMandate
   * Tests assignContributionMandate function
   * @return void
   * @throws CiviCRM_API3_Exception
   * @throws Exception
   */
  public function testAssignContributionMandate() {
    $fabricatedContribution = ContributionFabricator::fabricate(['contact_id' => $this->contact['id']]);
    $this->mandateValues['entity_id'] = $this->contact['id'];
    $storageManager = new CRM_ManualDirectDebit_Common_MandateStorageManager();
    $mandate = $storageManager->saveDirectDebitMandate($this->contact['id'], $this->mandateValues);
    $storageManager->assignContributionMandate($fabricatedContribution['id'], $mandate->id);

    $contribution = civicrm_api3('Contribution', 'get', [
      'sequential' => 1,
      'id' => $fabricatedContribution['id'],
    ]);

    $this->assertNotEmpty($contribution['values']);

    $mandateIdCustomFieldId =
      CRM_ManualDirectDebit_Common_DirectDebitDataProvider::getCustomFieldIdByName("mandate_id");
    $this->assertEquals($contribution['values'][0]['custom_' . $mandateIdCustomFieldId], $mandate->id);

  }

}
