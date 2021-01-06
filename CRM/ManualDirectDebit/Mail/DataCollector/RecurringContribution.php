<?php

/**
 * Collect data for message template by recurring contribution id
 */
class CRM_ManualDirectDebit_Mail_DataCollector_RecurringContribution extends CRM_ManualDirectDebit_Mail_DataCollector_Base {

  /**
   * Entered recurring contribution id
   *
   * @var int
   */
  protected $enteredRecurringContributionId;

  /**
   * CRM_ManualDirectDebit_Mail_DataCollector_RecurringContribution constructor.
   *
   * @param int $enteredRecurringContributionId
   */
  public function __construct($enteredRecurringContributionId) {
    $this->enteredRecurringContributionId = $enteredRecurringContributionId;
  }

  /**
   * Sets contribution id
   */
  protected function setContributionId() {
    $contributionId = $this->getLastContributionIdByRecurringContributionId();
    if ($contributionId) {
      $this->contributionId = $contributionId;
    }
    else {
      throw new CiviCRM_API3_Exception("Can't find contribution id by recurring contribution id", 'dd_3');
    }
  }

  /**
   * Gets 'contribution id' by 'recurring contribution id'
   *
   * @return int
   */
  private function getLastContributionIdByRecurringContributionId() {
    $result = civicrm_api3('Contribution', 'get', [
      'sequential' => 1,
      'contribution_recur_id' => $this->enteredRecurringContributionId,
      'options' => ['sort' => 'id DESC', 'limit' => 1],
    ]);

    return $result['count'] == 1 ? $result['values'][0]['id'] : FALSE;
  }

}
