<?php

/**
 * Provides skeleton for activity in 'Post' and 'PostOfflineAutoRenewal' hooks
 */
abstract class CRM_ManualDirectDebit_Hook_Common_PostActivityBase {

  /**
   * Recur contribution dao
   *
   * @var object
   */
  protected $contributionRecurDao;

  public function __construct($recurContributionId) {
    $this->contributionRecurDao = CRM_Contribute_BAO_ContributionRecur::findById($recurContributionId);
  }

  /**
   * Process hook
   */
  public function process() {
    if (!CRM_ManualDirectDebit_Common_DirectDebitDataProvider::isPaymentMethodDirectDebit($this->contributionRecurDao->payment_instrument_id)) {
      return;
    }

    $this->createActivity();
  }

  /**
   * Creates activity
   */
  abstract protected function createActivity();

}
