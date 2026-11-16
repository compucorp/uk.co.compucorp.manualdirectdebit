<?php

require_once __DIR__ . '/../../../BaseHeadlessTest.php';

/**
 * Tests the payment plan statuses a Direct Debit payment batch collects.
 *
 * @group headless
 */
class CRM_ManualDirectDebit_Form_BatchTransactionTest extends BaseHeadlessTest {

  /**
   * The "In Progress" contribution status a test removed, to put back.
   *
   * @var array|null
   */
  private $removedInProgressStatus = NULL;

  public function tearDown(): void {
    if (is_array($this->removedInProgressStatus)) {
      \Civi\Api4\OptionValue::create(FALSE)
        ->setValues($this->removedInProgressStatus)
        ->execute();
      $this->removedInProgressStatus = NULL;
    }
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
   * site installed fresh on CiviCRM 5.55 or later.
   */
  private function removeInProgressFromContributionStatuses() {
    $inProgress = \Civi\Api4\OptionValue::get(FALSE)
      ->addSelect('option_group_id', 'label', 'value', 'name', 'weight', 'is_active', 'is_reserved', 'filter')
      ->addWhere('option_group_id:name', '=', 'contribution_status')
      ->addWhere('name', '=', 'In Progress')
      ->execute()
      ->first();
    if (is_array($inProgress)) {
      \Civi\Api4\OptionValue::delete(FALSE)
        ->addWhere('id', '=', $inProgress['id'])
        ->execute();
      unset($inProgress['id']);
      $this->removedInProgressStatus = $inProgress;
    }
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
