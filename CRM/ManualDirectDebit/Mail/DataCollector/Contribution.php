<?php

/**
 * Collect data for message template by contribution id
 */
class CRM_ManualDirectDebit_Mail_DataCollector_Contribution extends CRM_ManualDirectDebit_Mail_DataCollector_Base {

  /**
   * Entered contribution id
   *
   * @var int
   */
  protected $enteredContributionId;

  /**
   * CRM_ManualDirectDebit_Mail_DataCollector_Contribution constructor.
   *
   * @param $enteredContributionId
   */
  public function __construct($enteredContributionId) {
    $this->enteredContributionId = $enteredContributionId;
  }

  /**
   * Sets contribution id
   */
  protected function setContributionId() {
    $this->contributionId = $this->enteredContributionId;
  }

}
