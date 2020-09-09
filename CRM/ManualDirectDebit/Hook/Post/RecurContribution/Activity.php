<?php

/**
 * Process 'post' hook and create activity
 */
class CRM_ManualDirectDebit_Hook_Post_RecurContribution_Activity extends CRM_ManualDirectDebit_Hook_Common_PostActivityBase {

  /**
   * Operation being performed with CiviCRM object
   *
   * @var string
   */
  protected $operation;

  public function __construct($recurContributionId, $operation) {
    parent::__construct($recurContributionId);
    $this->operation = $operation;
  }

  /**
   * Creates activity
   */
  protected function createActivity() {
    $loggedContactId = CRM_ManualDirectDebit_Common_User::getLoggedContactId();

    if (empty($loggedContactId)) {
      $loggedContactId = $this->contributionRecurDao->contact_id;
    }

    if ($this->operation == "create") {
      CRM_ManualDirectDebit_Common_Activity::create(
        "New Direct Debit Recurring Payment",
        "new_direct_debit_recurring_payment",
        $this->contributionRecurDao->id,
        $loggedContactId,
        $this->contributionRecurDao->contact_id
      );
    }

    if ($this->operation == "edit") {
      CRM_ManualDirectDebit_Common_Activity::create(
        "Update Direct Debit Recurring Payment",
        "update_direct_debit_recurring_payment",
        $this->contributionRecurDao->id,
        $loggedContactId,
        $this->contributionRecurDao->contact_id
      );
    }
  }

}
