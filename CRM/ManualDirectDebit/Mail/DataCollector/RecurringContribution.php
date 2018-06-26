<?php

/**
 * Collect data for message template by recurring contribution id
 */
class CRM_ManualDirectDebit_Mail_DataCollector_RecurringContribution extends CRM_ManualDirectDebit_Mail_DataCollector_Base{

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
    $contributionId = $this->getContributionIdByRecurringContributionId();
    if ($contributionId) {
      $this->contributionId = $contributionId;
    }
    else {
      throw new CiviCRM_API3_Exception("Can't find contribution id by recurring contribution id",'dd_3');
    }
  }

  /**
   * Gets 'contribution id' by 'recurring contribution id'
   *
   * @return int
   */
  private function getContributionIdByRecurringContributionId() {
    $query = "
      SELECT contribution.id AS contribution_id
      FROM civicrm_contribution_recur AS contribution_recur
      LEFT JOIN civicrm_contribution AS contribution
        ON contribution_recur.id = contribution.contribution_recur_id
      WHERE contribution_recur.id = %1
      ORDER BY contribution.id ASC
      LIMIT 1
    ";

    $dao = CRM_Core_DAO::executeQuery($query, [
      1 => [$this->enteredRecurringContributionId, 'Integer']
    ]);

    while ($dao->fetch()) {
      return $dao->contribution_id;
    }

    return FALSE;
  }

}
