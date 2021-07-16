<?php
use CRM_ManualDirectDebit_Common_SettingsManager as SettingsManager;

/**
 * Class CRM_ManualDirectDebit_Hook_CalculateContributionReceiveDate_ContributionBase.
 *
 * Holds methods and attributes to all classes that calculate receive dates for
 * instalments in a payment plan.
 */
abstract class CRM_ManualDirectDebit_Hook_CalculateContributionReceiveDate_Base {
  /**
   * Start date of the payment plan and the receive date of first instalment.
   *
   * @var string
   */
  protected $receiveDate = '';

  /**
   * List of parameters being used to create the first instalment.
   *
   * @var array
   */
  protected $params = [];

  /**
   * Array with Direct Debit extension settings.
   *
   * @var array
   */
  protected $ddSettings = [];

  /**
   * The DirectDebit payment instrument data.
   *
   * @var array
   */
  protected $directDebitPaymentInstrument = [];

  /**
   * CRM_ManualDirectDebit_Hook_CalculateContributionReceiveDate_FirstContribution constructor.
   *
   * @param string $receiveDate
   * @param array $params
   * @param \CRM_ManualDirectDebit_Common_SettingsManager $settingsManager
   *
   * @throws \CRM_Extension_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function __construct(&$receiveDate, $params, SettingsManager $settingsManager) {
    $this->receiveDate =& $receiveDate;
    $this->params = $params;
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
   * Checks if the contribution is being paid for with direct debit.
   *
   * @return bool
   */
  protected function isDirectDebit() {
    if ($this->params['payment_instrument_id'] === 'direct_debit') {
      return TRUE;
    }

    if ($this->params['payment_instrument_id'] == $this->directDebitPaymentInstrument['value']) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Obtains recurrring contribution used for the payment plan.
   *
   * @return array
   * @throws \CiviCRM_API3_Exception
   */
  protected function getRecurringContribution() {
    $result = civicrm_api3('ContributionRecur', 'get', [
      'sequential' => 1,
      'id' => $this->params['contribution_recur_id'],
    ]);

    if ($result['count'] > 0) {
      return array_shift($result['values']);
    }

    return [];
  }

  /**
   * Changes the receive date for the instalment, if necessary.
   *
   * @return mixed
   */
  abstract public function process();

}
