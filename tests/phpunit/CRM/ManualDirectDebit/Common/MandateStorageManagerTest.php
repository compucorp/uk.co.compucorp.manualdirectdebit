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
   * @var mixed
   */
  private $originatorNumber;
  /**
   * @var CRM_ManualDirectDebit_Common_MandateStorageManager
   */
  private $storageManager;

  /**
   * setUp test
   * @throws CiviCRM_API3_Exception
   */
  public function setUp() {
    $this->contact = ContactFabricator::fabricate();
    $this->originatorNumber = OriginatorNumberFabricator::fabricate()['values'][0]['value'];
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
      'originator_number' => $this->originatorNumber,
    ];

    //Fabricate default settings
    SettingFabricator::fabricate();

    $this->storageManager = new CRM_ManualDirectDebit_Common_MandateStorageManager();
    $this->clearMandateContributionConnectorProperties();
  }

  /**
   * Clear MandateContributionConnector properties
   *
   * Sometimes the object has properties from previous test because its class follow the singlton pattern
   * we need to clear those properties between tests by accessing its private method
   */
  public function clearMandateContributionConnectorProperties() {
    $mandateContributionConnector = CRM_ManualDirectDebit_Hook_MandateContributionConnector::getInstance();
    $class = new \ReflectionClass($mandateContributionConnector);
    $method = $class->getMethod("refreshProperties");
    $method->setAccessible(TRUE);
    $method->invokeArgs($mandateContributionConnector, []);
  }

  /**
   * Test assignRecurringContributionMandate function
   * @throws CiviCRM_API3_Exception
   * @throws Exception
   */
  public function testSaveDirectDebitMandate() {

    $mandate = $this->storageManager->saveDirectDebitMandate($this->contact['id'], $this->mandateValues);
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
    $mandate = $this->storageManager->saveDirectDebitMandate($this->contact['id'], $this->mandateValues);
    $this->storageManager->assignRecurringContributionMandate($recurringContribution['id'], $mandate->id);

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

    $fabricatedContributionWithMandate = $this->fabricateContributionWithMandate();
    $mandate = $fabricatedContributionWithMandate['mandate'];
    $fabricatedContribution = $fabricatedContributionWithMandate['contribution'];

    $contribution = civicrm_api3('Contribution', 'get', [
      'sequential' => 1,
      'id' => $fabricatedContribution['id'],
    ]);

    $this->assertNotEmpty($contribution['values']);

    $mandateIdCustomFieldId =
      CRM_ManualDirectDebit_Common_DirectDebitDataProvider::getCustomFieldIdByName("mandate_id");
    $this->assertEquals($contribution['values'][0]['custom_' . $mandateIdCustomFieldId], $mandate->id);
  }

  /**
   * @depends testAssignContributionMandate
   * Tests ChangeMandateForContribution function
   * @return void
   * @throws CiviCRM_API3_Exception
   * @throws Exception
   */
  public function testChangeMandateForContribution() {

    $fabricatedContributionWithMandate = $this->fabricateContributionWithMandate();
    $mandate = $fabricatedContributionWithMandate['mandate'];
    $fabricatedContribution = $fabricatedContributionWithMandate['contribution'];

    $mandateIdCustomFieldId =
      CRM_ManualDirectDebit_Common_DirectDebitDataProvider::getCustomFieldIdByName("mandate_id");

    $contribution = civicrm_api3('Contribution', 'get', [
      'sequential' => 1,
      'id' => $fabricatedContribution['id'],
    ]);

    $this->assertEquals($contribution['values'][0]['custom_' . $mandateIdCustomFieldId], $mandate->id);
    $now = new DateTime();
    $newMandate = [
      'entity_id' => $this->contact['id'],
      'bank_name' => 'HSBC',
      'account_holder_name' => 'John Doe',
      'ac_number' => '87654321',
      'sort_code' => '56-32-12',
      'dd_code' => 1,
      'dd_ref' => 'DD Ref',
      'start_date' => $now->format('Y-m-d H:i:s'),
      'authorisation_date' => $now->format('Y-m-d H:i:s'),
      'originator_number' => $this->originatorNumber,
    ];

    $newMandate = $this->storageManager->saveDirectDebitMandate($this->contact['id'], $newMandate);
    $this->storageManager->changeMandateForContribution($newMandate->id, $mandate->id);
    $contribution = civicrm_api3('Contribution', 'get', [
      'sequential' => 1,
      'id' => $fabricatedContribution['id'],
    ]);
    $this->assertEquals($contribution['values'][0]['custom_' . $mandateIdCustomFieldId], $newMandate->id);
  }

  /**
   * @depends testAssignContributionMandate
   * Tests DeleteMandate function
   * @return void
   * @throws CiviCRM_API3_Exception
   * @throws Exception
   */
  public function testDeleteMandate() {

    $fabricatedContributionWithMandate = $this->fabricateContributionWithMandate();
    $mandate = $fabricatedContributionWithMandate['mandate'];
    $fabricatedContribution = $fabricatedContributionWithMandate['contribution'];

    $mandateIdCustomFieldId =
      CRM_ManualDirectDebit_Common_DirectDebitDataProvider::getCustomFieldIdByName("mandate_id");

    $contribution = civicrm_api3('Contribution', 'get', [
      'sequential' => 1,
      'id' => $fabricatedContribution['id'],
    ]);

    $this->assertEquals($contribution['values'][0]['custom_' . $mandateIdCustomFieldId], $mandate->id);

    $this->storageManager->deleteMandate($mandate->id);

    $contribution = civicrm_api3('Contribution', 'get', [
      'sequential' => 1,
      'id' => $fabricatedContribution['id'],
    ]);

    $this->assertEmpty($contribution['values'][0]['custom_' . $mandateIdCustomFieldId]);

  }

  /**
   * Generates Contribution with mandate.
   * @throws Exception
   */
  private function fabricateContributionWithMandate() {
    $fabricatedContribution = ContributionFabricator::fabricate([
      'contact_id' => $this->contact['id'],
    ]);
    $this->mandateValues['entity_id'] = $this->contact['id'];
    $storageManager = new CRM_ManualDirectDebit_Common_MandateStorageManager();
    $mandate = $storageManager->saveDirectDebitMandate($this->contact['id'], $this->mandateValues);
    $storageManager->assignContributionMandate($fabricatedContribution['id'], $mandate->id);

    return ['mandate' => $mandate, 'contribution' => $fabricatedContribution];

  }

}
