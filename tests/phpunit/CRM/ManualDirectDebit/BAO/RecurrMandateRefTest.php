<?php

use CRM_ManualDirectDebit_Test_Fabricator_Mandate as MandateFabricator;
use CRM_ManualDirectDebit_Test_Fabricator_Setting as SettingFabricator;
use CRM_ManualDirectDebit_Test_Fabricator_Contact as ContactFabricator;
use CRM_ManualDirectDebit_Test_Fabricator_RecurringContribution as RecurringContributionFabricator;

require_once __DIR__ . '/../../../BaseHeadlessTest.php';

/**
 * Runs tests on RecurrMandateRef.
 *
 * @group headless
 */
class CRM_ManualDirectDebit_BAO_RecurrMandateRefTest extends BaseHeadlessTest {

  /**
   * @var array
   */
  protected $recurringContribution;
  /**
   * @var array
   */
  protected $mandate;

  /**
   * Setup test
   * @throws CiviCRM_API3_Exception
   */
  public function setUp() {
    //Fabricate default settings
    SettingFabricator::fabricate();
    $contact = ContactFabricator::fabricate();
    $this->mandate = MandateFabricator::fabricate(['entity_id' => $contact['id']]);
    $this->recurringContribution = RecurringContributionFabricator::fabricate([
      'contact_id' => $contact['id'],
      'amount' => 100,
      'frequency_interval' => 1,
    ]);
  }

  /**
   * Tests create recurrMandateRef entity
   */
  public function testCreate() {
    $recurrMandateRef = CRM_ManualDirectDebit_BAO_RecurrMandateRef::create([
      'recurr_id' => $this->recurringContribution['id'],
      'mandate_id' => $this->mandate['id'],
    ]);

    $this->assertEquals($this->recurringContribution['id'], $recurrMandateRef->recurr_id);
    $this->assertEquals($this->mandate['id'], $recurrMandateRef->mandate_id);

  }

  /**
   * Tests getMandateIdForRecurringContribution function
   */
  public function testGetMandateIdForRecurringContribution() {
    CRM_ManualDirectDebit_BAO_RecurrMandateRef::create([
      'recurr_id' => $this->recurringContribution['id'],
      'mandate_id' => $this->mandate['id'],
    ]);
    $mandateId = CRM_ManualDirectDebit_BAO_RecurrMandateRef::getMandateIdForRecurringContribution($this->recurringContribution['id']);
    $this->assertEquals($mandateId, $this->mandate['id']);
  }

  /**
   * Tests getMandateReferenceId
   */
  public function testGetMandateReferenceId() {
    $recurrMandateRef = CRM_ManualDirectDebit_BAO_RecurrMandateRef::create([
      'recurr_id' => $this->recurringContribution['id'],
      'mandate_id' => $this->mandate['id'],
    ]);
    $mandateRefId = CRM_ManualDirectDebit_BAO_RecurrMandateRef::getMandateReferenceId($this->mandate['id'], $this->recurringContribution['id']);
    $this->assertEquals($recurrMandateRef->id, $mandateRefId);

  }

}
