<?php

/**
 * Process 'postProcess' hook and create activity
 */
class CRM_ManualDirectDebit_Hook_PostProcess_Contribution_Activity {

  /**
   * Contribution form object from Hook
   *
   * @var object
   */
  private $form;

  public function __construct(&$form) {
    $this->form = $form;
  }

  /**
   * Process hook
   */
  public function process() {
    $isRecurring = isset($this->form->getVar('_values')['contribution_recur_id']) && !empty($this->form->getVar('_values')['contribution_recur_id']);
    if (!$isRecurring) {
      return;
    }

    $selectedPaymentInstrument = $this->form->getVar('_params')['payment_instrument_id'];
    if (CRM_ManualDirectDebit_Common_DirectDebitDataProvider::isPaymentMethodDirectDebit($selectedPaymentInstrument)) {
      $this->createActivityWithUpdateType();
      return;
    }

  }

  /**
   * Creates activity with "update" type
   */
  private function createActivityWithUpdateType() {
    $contributionRecurId = $this->form->getVar('_values')['contribution_recur_id'];
    $contributionRecurDao = CRM_Contribute_BAO_ContributionRecur::findById($contributionRecurId);
    CRM_ManualDirectDebit_Common_Activity::create(
      "Update Direct Debit Recurring Payment",
      "update_direct_debit_recurring_payment",
      $contributionRecurDao->id,
      CRM_ManualDirectDebit_Common_User::getAdminContactId(),
      $contributionRecurDao->contact_id
    );
  }

}
