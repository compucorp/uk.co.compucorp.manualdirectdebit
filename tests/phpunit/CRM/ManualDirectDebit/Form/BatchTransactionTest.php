<?php

require_once __DIR__ . '/../../../BaseHeadlessTest.php';

/**
 * Tests the payment plan statuses a Direct Debit payment batch collects.
 *
 * @group headless
 */
class CRM_ManualDirectDebit_Form_BatchTransactionTest extends BaseHeadlessTest {

  /**
   * Clears the status caches. Each test runs in a transaction that is rolled
   * back afterwards, which brings back a removed status with its own ID.
   */
  public function tearDown(): void {
    CRM_Core_PseudoConstant::flush();
    Civi::cache('metadata')->clear();

    parent::tearDown();
  }

  public function testBatchCollectsEveryPlanStatusExceptCancelled() {
    $planStatusIds = CRM_ManualDirectDebit_Form_BatchTransaction::getCollectablePlanStatusIds();

    $this->assertContains($this->getPlanStatusId('Pending'), $planStatusIds);
    $this->assertContains($this->getPlanStatusId('In Progress'), $planStatusIds);
    $this->assertContains($this->getPlanStatusId('Completed'), $planStatusIds);
    $this->assertNotContains($this->getPlanStatusId('Cancelled'), $planStatusIds);
  }

  /**
   * On a site installed fresh on CiviCRM 5.55 or later the contribution
   * statuses have no "In Progress"; In Progress plans are still collected.
   */
  public function testBatchCollectsInProgressPlansWhenContributionStatusesHaveNoInProgress() {
    $this->removeInProgressFromContributionStatuses();

    $planStatusIds = CRM_ManualDirectDebit_Form_BatchTransaction::getCollectablePlanStatusIds();

    $this->assertContains($this->getPlanStatusId('In Progress'), $planStatusIds);
    $this->assertNotContains($this->getPlanStatusId('Cancelled'), $planStatusIds);
  }

  /**
   * Removes "In Progress" from the contribution status options, as on a
   * site installed fresh on CiviCRM 5.55 or later. The test's transaction
   * rollback puts it back.
   */
  private function removeInProgressFromContributionStatuses() {
    \Civi\Api4\OptionValue::delete(FALSE)
      ->addWhere('option_group_id:name', '=', 'contribution_status')
      ->addWhere('name', '=', 'In Progress')
      ->execute();
    CRM_Core_PseudoConstant::flush();
    Civi::cache('metadata')->clear();

    $this->assertNotContains('In Progress', CRM_Contribute_PseudoConstant::contributionStatus(NULL, 'name'));
  }

  /**
   * @param string $name
   *
   * @return int
   */
  private function getPlanStatusId($name) {
    return (int) CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_ContributionRecur', 'contribution_status_id', $name);
  }

}
