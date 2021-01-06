<?php

/**
 * Collect data for message template by membership id
 */
class CRM_ManualDirectDebit_Mail_DataCollector_Membership extends CRM_ManualDirectDebit_Mail_DataCollector_Base {

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
    $contributionId = $this->getLastContributionIDByMembership();
    if ($contributionId) {
      $this->contributionId = $contributionId;
    }
    else {
      throw new CiviCRM_API3_Exception("Can't find contribution id by membership id", 'dd_2');
    }
  }

  /**
   * Gets 'contribution id' by 'membership id'
   *
   * @return int
   */
  private function getLastContributionIDByMembership() {
    $result = civicrm_api3('MembershipPayment', 'get', [
      'sequential' => 1,
      'membership_id' => $this->enteredMembershipId,
      'options' => ['sort' => 'contribution_id DESC', 'limit' => 1],
    ]);

    return $result['count'] == 1 ? $result['values'][0]['contribution_id'] : FALSE;
  }

}
