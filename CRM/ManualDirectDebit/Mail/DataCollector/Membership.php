<?php

/**
 * Collect data for message template by membership id
 */
class CRM_ManualDirectDebit_Mail_DataCollector_Membership extends CRM_ManualDirectDebit_Mail_DataCollector_Base{

  /**
   * Entered membership id
   *
   * @var int
   */
  protected $enteredMembershipId;

  /**
   * CRM_ManualDirectDebit_Mail_DataCollector_Membership constructor.
   *
   * @param int $enteredMembershipId
   */
  public function __construct($enteredMembershipId) {
    $this->enteredMembershipId = $enteredMembershipId;
  }

  /**
   * Sets contribution id
   */
  protected function setContributionId() {
    $contributionId = $this->getContributionIdByMembership();
    if ($contributionId) {
      $this->contributionId = $contributionId;
    }
    else {
      throw new CiviCRM_API3_Exception("Can't find contribution id by membership id",'dd_2');
    }
  }

  /**
   * Gets 'contribution id' by 'membership id'
   *
   * @return int
   */
  private function getContributionIdByMembership() {
    $query = "
      SELECT membership_payment.contribution_id AS contribution_id
      FROM civicrm_membership_payment AS membership_payment
      WHERE membership_payment.membership_id = %1
      ORDER BY membership_payment.contribution_id ASC
      LIMIT 1
    ";

    $dao = CRM_Core_DAO::executeQuery($query, [
      1 => [$this->enteredMembershipId, 'Integer']
    ]);

    while ($dao->fetch()) {
      return $dao->contribution_id;
    }

    return FALSE;
  }





}
