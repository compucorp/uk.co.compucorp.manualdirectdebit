<?php

use CRM_ManualDirectDebit_ExtensionUtil as E;

/**
 * Form Manual Direct Debit Setup up Form
 *
 */
class CRM_ManualDirectDebit_Form_SetUp extends CRM_Core_Form {

  /**
   * @var $contributionId
   */
  private $contributionId;

  public function preProcess() {
    parent::preProcess();
    $this->contributionId = CRM_Utils_Request::retrieveValue('contribution_id', 'Positive', NULL);
    if ($this->contributionId == NULL) {
      CRM_Utils_System::redirect('/');
    }
    CRM_Utils_System::setTitle(E::ts('Set up a Direct Debit'));
  }

  public function buildQuickForm() {
    parent::buildQuickForm();
    $errorMessage = NULL;
    $contribution = $this->getContribution();
    if (empty($contribution)) {
      $errorMessage = E::ts('This invoice is already paid. Please contact the administrator for the correct link to setup direct debit.');
    } else if (empty($contribution['contribution_recur_id'])) {
      $errorMessage = E::ts('This invoice is not part of a payment plan. Please contact the administrator for the correct link to set up a direct debit.');
    }

    if ($errorMessage != NULL) {
      $this->assign('errorMessage', $errorMessage);
      return;
    }

    $this->assign('invoiceNumber', $contribution['invoice_number']);
    $this->assign('amount', $this->calculateAmount($contribution['total_amount'], $contribution['tax_amount']));

    if (!empty($contribution['tax_amount'])) {
      $this->assign('taxAmount', $contribution['tax_amount']);
    }
    $this->assign('totalAmount', $contribution['total_amount']);

    $paymentDates = $this->getPaymentDates();
    if (count($paymentDates) == 1) {
      $this->assign('payment_date_value', reset($paymentDates));
      $this->add('hidden', 'payment_dates', key($paymentDates));
    }else {
      $this->add('select', 'payment_dates', E::ts('Payment Date'), $paymentDates);
    }

    $this->add('text', 'bank_name', E::ts('Bank name:'), ['size' => 40], TRUE);
    $this->add('text', 'bank_account_holder', E::ts('Name of Account holder:'), ['size' => 40], TRUE);
    $this->add('text', 'bank_account_number', E::ts('Account number:'), ['size' => 40], TRUE);
    $this->add('text', 'bank_sort_code', E::ts('Sort code:'), ['size' => 40], TRUE);

    $this->addButtons([
      [
        'type' => 'submit',
        'name' => E::ts('Submit'),
        'isDefault' => TRUE,
      ],
    ]);

  }

  public function postProcess() {
    parent::postProcess();
  }

  /**
   * @param $totalAmount
   * @param $taxAmount
   * @return mixed
   */
  private function calculateAmount($totalAmount, $taxAmount) {
    return $totalAmount - $taxAmount;
  }

  /**
   * @return array
   * @throws CiviCRM_API3_Exception
   */
  private function getPaymentDates() {
    $settingsManager = new CRM_ManualDirectDebit_Common_SettingsManager();
    $settings = $settingsManager->getManualDirectDebitSettings();

    $locale = civicrm_api3('Setting', 'get', [
      'sequential' => 1,
      'return' => ["lcMessages"],
    ])['values']['lcMessages'];
    $ordinalSuffixFormatter = new NumberFormatter($locale, NumberFormatter::ORDINAL);

    $paymentDates = [];
    foreach ($settings['payment_collection_run_dates'] as $day){
      $paymentDates[$day] =  $ordinalSuffixFormatter->format($day);
    }

    return $paymentDates;
  }

  /**
   * @return array|mixed
   * @throws CiviCRM_API3_Exception
   */
  private function getContribution() {
    $contribution = civicrm_api3('Contribution', 'get', [
      'sequential' => 1,
      'id' => $this->contributionId,
      'contribution_status_id' => 'Pending',
      'return' => [
        'contribution_recur_id',
        'invoice_number',
        'tax_amount',
        'total_amount'
      ],
    ]);

    if (empty($contribution['values'])) {
      return [];
    }

    return $contribution['values'][0];
  }

}
