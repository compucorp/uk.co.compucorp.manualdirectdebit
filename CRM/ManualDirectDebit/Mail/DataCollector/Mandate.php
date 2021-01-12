<?php

/**
 * Collect data for message template by mandate id
 */
class CRM_ManualDirectDebit_Mail_DataCollector_Mandate extends CRM_ManualDirectDebit_Mail_DataCollector_Base {

  /**
   * Entered Mandate id
   *
   * @var int
   */
  protected $enteredMandateId;

  /**
   * CRM_ManualDirectDebit_Mail_DataCollector_Mandate constructor.
   *
   * @param int $enteredMandateId
   */
  public function __construct($enteredMandateId) {
    $this->enteredMandateId = $enteredMandateId;
  }

  /**
   * Sets contribution id
   *
   * @throws \CiviCRM_API3_Exception
   */
  protected function setContributionId() {
    $contributionId = $this->getContributionByMandate();
    if ($contributionId) {
      $this->contributionId = $contributionId;
    }
    else {
      throw new CiviCRM_API3_Exception("Can't find contribution id by mandate id", 'dd_1');
    }
  }

  /**
   * Gets 'contribution id' by 'mandate id'
   *
   * @return int
   */
  private function getContributionByMandate() {
    $query = "
      SELECT dd_information.entity_id AS contribution_id
      FROM civicrm_value_dd_information AS dd_information
      WHERE dd_information.mandate_id = %1
      ORDER BY dd_information.id ASC
      LIMIT 1
  ";

    $dao = CRM_Core_DAO::executeQuery($query, [
      1 => [$this->enteredMandateId, 'Integer'],
    ]);

    while ($dao->fetch()) {
      return $dao->contribution_id;
    }

    return FALSE;
  }

}
