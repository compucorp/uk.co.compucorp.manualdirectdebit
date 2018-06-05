<?php

/**
 * Process 'post' hook and create activity
 */
class CRM_ManualDirectDebit_Hook_Post_RecurContribution_Activity {

  /**
   * Recur contribution dao
   *
   * @var object
   */
  private $contributionRecurDao;

  public function __construct($recurContributionId) {
    $this->contributionRecurDao = CRM_Contribute_BAO_ContributionRecur::findById($recurContributionId);
  }

  /**
   * Process hook
   */
  public function process() {
    if (CRM_ManualDirectDebit_Common_DirectDebitDataProvider::isPaymentMethodDirectDebit($this->contributionRecurDao->payment_instrument_id)) {
      $this->createActivityWithNewType();
    }
  }

  /**
   * Creates activity with "new" type
   */
  private function createActivityWithNewType() {
    CRM_ManualDirectDebit_Common_Activity::create(
      "New Direct Debit Recurring Payment",
      "new_direct_debit_recurring_payment",
      $this->contributionRecurDao->id,
      CRM_ManualDirectDebit_Common_User::getLoggedContactId(),
      $this->contributionRecurDao->contact_id
    );
  }

}
