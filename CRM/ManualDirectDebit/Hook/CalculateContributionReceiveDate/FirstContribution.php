<?php
use CRM_ManualDirectDebit_Common_SettingsManager as SettingsManager;

/**
 * Class CRM_ManualDirectDebit_Hook_CalculateContributionReceiveDate_FirstContribution.
 *
 * Implements hook to calculate the receive date of the first contribution of a
 * payment plan.
 */
class CRM_ManualDirectDebit_Hook_CalculateContributionReceiveDate_FirstContribution {

  /**
   * Start date of the payment plan and the receive date of first instalment.
   *
   * @var string
   */
  private $receiveDate = '';

  /**
   * List of parameters being used to create the first instalment.
   *
   * @var array
   */
  private $params = [];

  /**
   * Array with Direct Debit extension settings.
   *
   * @var array
   */
  private $ddSettings = [];

  /**
   * The DirectDebit payment instrument data.
   *
   * @var array
   */
  private $directDebitPaymentInstrument = [];

  /**
   * CRM_ManualDirectDebit_Hook_CalculateContributionReceiveDate_FirstContribution constructor.
   *
   * @param $receiveDate
   * @param $params
   * @param \CRM_ManualDirectDebit_Common_SettingsManager $settingsManager
   *
   * @throws \CRM_Extension_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function __construct(&$receiveDate, &$params, SettingsManager $settingsManager) {
    $this->receiveDate =& $receiveDate;
    $this->params =& $params;
    $this->ddSettings = $settingsManager->getManualDirectDebitSettings();
    $this->directDebitPaymentInstrument = $this->getDDPaymentMethod();
  }

  /**
   * Obtains the data for the Direct Debit payment instrument.
   *
   * @return mixed
   * @throws \CRM_Extension_Exception
   * @throws \CiviCRM_API3_Exception
   */
  private function getDDPaymentMethod() {
    $result = civicrm_api3('OptionValue', 'get', [
      'sequential' => 1,
      'name' => 'direct_debit',
      'option_group_id' => 'payment_instrument',
      'options' => ['limit' => 0],
    ]);

    if ($result['count'] > 0) {
      return $result['values'][0];
    }

    throw new CRM_Extension_Exception('Could not obtain DD Payment Instrument!');
  }

  /**
   * Calculates receive date for payment plan if payment method is DD.
   *
   * @throws \Exception
   */
  public function process() {
    if (!$this->isDirectDebit()) {
      return;
    }

    $receiveDateTime = new DateTime($this->receiveDate);
    $nextInstructionRunDate = $this->getNextValidDateAfter($receiveDateTime, $this->ddSettings['new_instruction_run_dates']);

    if ($this->ddSettings['minimum_days_to_first_payment']) {
      $nextInstructionRunDate->add(new DateInterval("P{$this->ddSettings['minimum_days_to_first_payment']}D"));
    }

    $nextPaymentCollectionDate = $this->getNextValidDateAfter($nextInstructionRunDate, $this->ddSettings['payment_collection_run_dates']);
    $this->receiveDate = $nextPaymentCollectionDate->format('Y-m-d H:i:s');
  }

  /**
   * Checks if the contribution is being paid for with direct debit.
   *
   * @return bool
   */
  private function isDirectDebit() {
    if ($this->params['payment_instrument_id'] === 'direct_debit') {
      return TRUE;
    }

    if ($this->params['payment_instrument_id'] === $this->directDebitPaymentInstrument['value']) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Returns first date in collection of days that is after given dates.
   *
   * @param \DateTime $referenceDate
   * @param array $validDaysArray
   *
   * @return \Date|\DateTime
   */
  private function getNextValidDateAfter(\DateTime $referenceDate, array $validDaysArray) {
    $referenceYear = intval($referenceDate->format('Y'));

    for ($year = $referenceYear; $year < $referenceYear + 2; $year++) {
      for ($month = 1; $month < 13; $month++) {
        foreach ($validDaysArray as $paymentCollectionDay) {
          $paymentCollectionDay = ($paymentCollectionDay < 10 ? '0' . $paymentCollectionDay : $paymentCollectionDay);
          $paymentCollectionMonth = ($month < 10 ? '0' . $month : $month);
          $nextAvailableDate = new DateTime("{$year}-{$paymentCollectionMonth}-{$paymentCollectionDay}");

          if ($nextAvailableDate > $referenceDate) {
            return $nextAvailableDate;
          }
        }
      }
    }

    return $referenceDate;
  }

}
