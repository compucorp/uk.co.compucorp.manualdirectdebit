<?php

/**
 * Process 'postOfflineAutoRenewal' hook and create activity
 */
class  CRM_ManualDirectDebit_Hook_PostOfflineAutoRenewal_Activity {

  /**
   * Recurring contribution dao
   *
   * @var int
   */
  private $recurContributionDao;

  /**
   * CRM_ManualDirectDebit_Hook_postOfflineAutoRenewal_Activity constructor.
   *
   * @param $recurContributionId
   *
   * @throws \Exception
   */
  public function __construct($recurContributionId) {
    $this->recurContributionDao = CRM_Contribute_BAO_ContributionRecur::findById($recurContributionId);
  }

  /**
   * Process hook
   *
   * @throws \Exception
   */
  public function process() {
    $isPaymentMethodDirectDebit = isset($this->recurContributionDao->payment_instrument_id)
      && CRM_ManualDirectDebit_Common_DirectDebitDataProvider::isPaymentMethodDirectDebit($this->recurContributionDao->payment_instrument_id);

    if ($isPaymentMethodDirectDebit) {
      $this->createActivity();
    }
  }

  /**
   * Creates activity
   */
  private function createActivity() {
    CRM_ManualDirectDebit_Common_Activity::create(
      "Offline Direct Debit Auto-renewal",
      "offline_direct_debit_auto_renewal",
      $this->recurContributionDao->id,
      CRM_ManualDirectDebit_Common_User::getAdminContactId(),
      $this->recurContributionDao->contact_id
    );
  }

}
