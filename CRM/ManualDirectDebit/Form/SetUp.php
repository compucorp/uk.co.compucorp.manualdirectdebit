<?php

use CRM_ManualDirectDebit_ExtensionUtil as E;

/**
 * Form Manual Direct Debit Setup up Form
 *
 */
class CRM_ManualDirectDebit_Form_SetUp extends CRM_Core_Form {

  /**
   * @var contributionId
   */
  private $contributionId;

  /**
   * @throws CRM_Core_Exception
   */
  public function preProcess() {
    parent::preProcess();
    $this->contributionId = CRM_Utils_Request::retrieveValue('contribution_id', 'Positive', NULL);
    if ($this->contributionId == NULL) {
      CRM_Utils_System::redirect('/');
    }
    CRM_Utils_System::setTitle(E::ts('Set up your Direct Debit'));
  }

  /**
   * @throws CiviCRM_API3_Exception
   */
  public function buildQuickForm() {
    parent::buildQuickForm();
    $errorMessage = NULL;
    $contribution = $this->getContribution();
    if (empty($contribution)) {
      $errorMessage = E::ts('This invoice is already paid. Please contact the administrator for the correct link to setup direct debit.');
    }
    elseif (empty($contribution['contribution_recur_id'])) {
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
    }
    else {
      $this->add('select', 'payment_dates', E::ts('Payment Date'), $paymentDates);
    }

    $this->add('text', 'bank_name', E::ts('Bank name:'), ['size' => 40], TRUE);
    $this->add('text', 'bank_account_holder', E::ts('Name of Account holder:'), ['size' => 40], TRUE);
    $this->add('text', 'bank_account_number', E::ts('Account number:'), ['size' => 40], TRUE);
    $this->add('text', 'bank_sort_code', E::ts('Sort code:'), ['size' => 40], TRUE);
    $this->add('hidden', 'contribution_id', $this->contributionId);
    $this->add('hidden', 'contact_id', $contribution['contact_id']);

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

    $values = $this->exportValues();
    $recurringContribution = $this->getRecurringContribution($values['contribution_id']);
    $this->updateRecurringContribution($recurringContribution, $values);
    $mandate = $this->createDirectDebitMandate($values);
    $this->attachDirectDebitMandateToContributions(
      $mandate->id,
      $values['contribution_id'],
      $recurringContribution['id']
    );

    $url = CRM_Utils_System::url('civicrm/direct_debit/setup/confirmation');
    CRM_Utils_System::redirect($url);
  }

  /**
   * @param $contributionId
   * @return mixed
   * @throws CiviCRM_API3_Exception
   */
  private function getRecurringContribution($contributionId) {
    return civicrm_api3('Contribution', 'getsingle', [
      'id' => $contributionId,
      'api.ContributionRecur.get' => [],
    ])['api.ContributionRecur.get']['values'][0];
  }

  /**
   * @param $recurringContribution
   * @param $values
   * @throws CiviCRM_API3_Exception
   */
  private function updateRecurringContribution($recurringContribution, $values) {
    $cycleDay = $this->getCycleDay(
      $values['payment_dates'],
      $recurringContribution['frequency_unit']
    );

    civicrm_api3('ContributionRecur', 'create', [
      'id' => $recurringContribution['id'],
      'payment_instrument_id' => 'direct_debit',
      'cycle_day' => $cycleDay,
    ]);

  }

  /**
   * @param $values
   * @return CRM_Core_DAO|object
   * @throws CiviCRM_API3_Exception
   */
  private function createDirectDebitMandate($values) {
    $defaultDDCode = civicrm_api3('OptionValue', 'get', [
      'sequential' => 1,
      'option_group_id' => 'direct_debit_codes',
      'label' => "0N",
    ])['values'][0]['value'];

    //Get the first return value for originator number
    $originatorNumber = civicrm_api3('OptionValue', 'get', [
      'sequential' => 1,
      'option_group_id' => "direct_debit_originator_number",
    ])['values'][0]['value'];

    $now = new DateTime();
    $mandateValues = [
      'entity_id' => $values['contact_id'],
      'bank_name' => $values['bank_name'],
      'account_holder_name' => $values['bank_account_holder'],
      'ac_number' => $values['bank_account_number'],
      'sort_code' => $values['bank_sort_code'],
      'dd_code' => $defaultDDCode,
      'start_date' => $now->format('Y-m-d H:i:s'),
      'dd_ref' => 'DD Ref',
      'authorisation_date' => $now->format('Y-m-d H:i:s'),
      'originator_number' => $originatorNumber,
    ];

    $storageManager = new CRM_ManualDirectDebit_Common_MandateStorageManager();
    return $storageManager->saveDirectDebitMandate($values['contact_id'], $mandateValues);

  }

  /**
   * @param $mandateId
   * @param $contributionId
   * @param $recurringContributionId
   */
  private function attachDirectDebitMandateToContributions($mandateId, $contributionId, $recurringContributionId) {
    $storageManager = new CRM_ManualDirectDebit_Common_MandateStorageManager();
    $storageManager->assignRecurringContributionMandate($recurringContributionId, $mandateId);
    $storageManager->assignContributionMandate($contributionId, $mandateId);
  }

  /**
   * @param $paymentDay
   * @param $frequencyUnit
   * @return integer
   */
  private function getCycleDay($paymentDay, $frequencyUnit) {

    if ($frequencyUnit != 'year') {
      return $paymentDay;
    }

    $now = new DateTime();
    $cycleDate = DateTime::createFromFormat('Y-m-d', $now->format('Y-m' . '-' . $paymentDay));

    //If cycle date is in the past, set cycle date should be next month.
    if ($cycleDate < $now) {
      $cycleDate = $cycleDate->modify('next month');
    }

    return (int) $cycleDate->format('z') + 1;
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
    ])['values'][0]['lcMessages'];
    $ordinalSuffixFormatter = new NumberFormatter($locale, NumberFormatter::ORDINAL);

    $paymentDates = [];
    foreach ($settings['payment_collection_run_dates'] as $day) {
      $paymentDates[$day] = $ordinalSuffixFormatter->format($day);
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
        'total_amount',
      ],
    ]);

    if (empty($contribution['values'])) {
      return [];
    }

    return $contribution['values'][0];
  }

}
