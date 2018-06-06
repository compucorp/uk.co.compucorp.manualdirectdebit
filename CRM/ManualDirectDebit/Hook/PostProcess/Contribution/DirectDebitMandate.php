<?php

/**
 *  Check condition for creating empty mandate for Contribution
 */
class CRM_ManualDirectDebit_Hook_PostProcess_Contribution_DirectDebitMandate {

  /**
   * Contribution form object from Hook
   *
   * @var object
   */
  private $form;

  /**
   * Object which manage writing and reading Data from DB
   *
   * @var CRM_ManualDirectDebit_Common_MandateStorageManager
   */
  private $mandateStorage;

  /**
   * Id of current contact
   *
   * @var int
   */
  private $currentContactId;

  public function __construct(&$form) {
    $this->mandateStorage = new CRM_ManualDirectDebit_Common_MandateStorageManager();
    $this->form = $form;
    $this->currentContactId = $this->form->getVar('_contactID');
  }

  /**
   * Checks if payment option appropriate for creating mandate
   */
  public function checkPaymentOptionToCreateMandate() {
    $isRecurring = isset($this->form->getVar('_params')['is_recur']) && !empty($this->form->getVar('_params')['is_recur']);

    if ($isRecurring) {
      $selectedPaymentProcessor = $this->form->getVar('_params')['payment_processor_id'];

      if (CRM_ManualDirectDebit_Common_DirectDebitDataProvider::isDirectDebitPaymentProcessor($selectedPaymentProcessor)) {
        $this->mandateStorage->createEmptyMandate($this->currentContactId);
      }
    }
    else {
      $selectedPaymentInstrument = $this->form->getVar('_params')['payment_instrument_id'];

      if (CRM_ManualDirectDebit_Common_DirectDebitDataProvider::isPaymentMethodDirectDebit($selectedPaymentInstrument)) {
        $this->mandateStorage->createEmptyMandate($this->currentContactId);
      }
    }
  }

  /**
   * Changes mandate for recurring contribution
   *
   * @return bool
   */
  public function changeMandateForRecurringContribution() {
    $recurringContributionId = $this->form->getVar('_submitValues')['recurrId'];
    $oldMandateId = CRM_ManualDirectDebit_BAO_RecurrMandateRef::getMandateIdForRecurringContribution($recurringContributionId);

    if (is_null($oldMandateId)){
      CRM_Core_Session::setStatus(t("Contribution doesn't exist"), $title = 'Error', $type = 'alert');

      return FALSE;
    }

    $mandateId = $this->mandateStorage->getLastInsertedMandateId( $this->form->getVar('_entityId'));

    CRM_ManualDirectDebit_BAO_RecurrMandateRef::changeMandateForRecurrContribution($mandateId, $recurringContributionId);
    $this->mandateStorage->changeMandateForContribution($mandateId, $oldMandateId);

    $this->redirectToContributionTab();
  }

  /**
   * Redirects to contribution tab
   */
  private function redirectToContributionTab() {
    $this->form->controller->setDestination(CRM_Utils_System::url('civicrm/contact/view', http_build_query([
        'action' => 'browse',
        'reset' => 1,
        'cid' => $this->form->getVar('_entityId'),
        'selectedChild' => 'contribute',
      ])
    ));
  }

}
