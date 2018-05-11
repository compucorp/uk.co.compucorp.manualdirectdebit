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

  public function __construct($form) {
    $this->form = $form;
  }

  /**
   * Checks necessary of validation
   */
  public function checkValidation() {
    $currentPaymentInstrumentId = $this->getCurrentPaymentInstrumentId();

    if ( ! CRM_ManualDirectDebit_Common_DirectDebitDataProvider::isPaymentMethodDirectDebit($currentPaymentInstrumentId)) {
      $this->turnOffDirectDebitValidation();
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
   * Turns off all validation fir direct debit
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

}
