<?php

/**
 * Class provide validation of `Direct Debit Mandate` custom group as part of the form
 */
class CRM_ManualDirectDebit_Hook_ValidateForm_MandateValidator {

  /**
   * Form object that is being altered.
   *
   * @var object
   */
  private $form;

  public function __construct(&$form) {
    $this->form = $form;
  }

  /**
   * Checks necessary of validation
   */
  public function checkValidation() {
    $currentPaymentInstrumentId = $this->getCurrentPaymentInstrumentId();

    if ( ! CRM_ManualDirectDebit_Common_DirectDebitDataProvider::isPaymentMethodDirectDebit($currentPaymentInstrumentId) || $this->isEditForm()) {
      $this->turnOffDirectDebitValidation();
    } else {
      $this->checkSettings();
    }
  }

  /**
   * Gets current payment instrument Id
   *
   * @return int
   */
  private function getCurrentPaymentInstrumentId() {
    if ($this->form->elementExists('payment_instrument_id')) {
      $paymentInstrumentElement = $this->form->getElement('payment_instrument_id');
      $paymentInstrumentValue = $paymentInstrumentElement->getValue()[0];
    }

    return $paymentInstrumentValue;
  }

  /**
   * Turns off all validation for direct debit
   */
  private function turnOffDirectDebitValidation() {
    $mandateDataProvider = new CRM_ManualDirectDebit_Common_DirectDebitDataProvider();
    $directDebitMandateCustomFieldNames = $mandateDataProvider->getMandateCustomFieldNames();
    $currentError = $this->form->getVar('_errors');

    foreach ($currentError as $error => $value) {
      if (in_array($error, $directDebitMandateCustomFieldNames)) {
        unset($currentError[$error]);
      }
    }
    $this->form->setVar('_errors', $currentError);
  }

  /**
   * Checks if necessary setting is configured for creating mandate
   */
  private function checkSettings() {
    try {
      CRM_ManualDirectDebit_Common_SettingsManager::getMinimumDayForFirstPayment();
    } catch (CiviCRM_API3_Exception $error) {
      $currentError = $this->form->getVar('_errors');
      $currentError[] = ['directDebitMandate' => "Please, configure minimum days to first payment"];
      $this->form->setVar('_errors', $currentError);
      CRM_Core_Session::setStatus($error->getMessage(), $title = 'Error', $type = 'error');
    }
  }

  /**
   * Checks if form is updated
   */
  private function isEditForm() {
    return $this->form->getAction() == CRM_Core_Action::UPDATE;
  }

}
