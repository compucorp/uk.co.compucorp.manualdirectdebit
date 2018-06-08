<?php

/**
 * Class provide information about Direct Debit Mandate Settings
 */
class CRM_ManualDirectDebit_Common_SettingsManager {

  public static $minimumDaysToFirstPayment;

  /**
   * Gets all extension settings
   *
   * @return array
   */
  public function getManualDirectDebitSettings() {
    $settingValues = $this->getSettingsValues();

    $settings = [];
    $settings['default_reference_prefix'] = $settingValues['values'][0]['manualdirectdebit_default_reference_prefix'];
    $settings['new_instruction_run_dates'] = $this->incrementAllArrayValues(
      $settingValues['values'][0]['manualdirectdebit_new_instruction_run_dates']);
    $settings['payment_collection_run_dates'] = $this->incrementAllArrayValues(
      $settingValues['values'][0]['manualdirectdebit_payment_collection_run_dates']);
    $settings['minimum_days_to_first_payment'] = $settingValues['values'][0]['manualdirectdebit_minimum_days_to_first_payment'];

    return $settings;
  }

  /**
   * Iterates all value in array. Because the first date should starts from 1,
   * but not from 0.
   *
   * @param $possibleRunDates
   *
   * @return mixed
   */
  private function incrementAllArrayValues($possibleRunDates) {
    foreach ($possibleRunDates as $sequentialNumber => $value) {
      $possibleRunDates[$sequentialNumber] = ++$value;
    }

    return $possibleRunDates;
  }

  /**
   * Gets setting information about minimum days to first payment
   *
   * @return int|null
   */
  public static function getMinimumDayForFirstPayment() {
    if (isset(self::$minimumDaysToFirstPayment) && !empty(self::$minimumDaysToFirstPayment)){
      return self::$minimumDaysToFirstPayment;
    }

    $settingTitle = 'manualdirectdebit_minimum_days_to_first_payment';
    $settingValues = civicrm_api3('setting', 'getsingle', [
      'return' => $settingTitle,
      'sequential' => 1,
    ]);

    if(isset($settingValues[$settingTitle]) && !empty($settingValues[$settingTitle])){
      self::$minimumDaysToFirstPayment = $settingValues[$settingTitle];
      return self::$minimumDaysToFirstPayment;
    } else {
      throw new CiviCRM_API3_Exception(t("Please, configure minimum days to first payment"),'required_setting_not_configured');
    }
  }

  /**
   * Gets setting values
   *
   * @return array
   */
  private function getSettingsValues() {
    $settingValues = $this->fetchSettingsValues();

    if (!isset($settingValues) || empty($settingValues)) {
      $result = civicrm_api3('System', 'flush');
      if ($result['is_error'] == 0){
        $settingValues =  $this->fetchSettingsValues();
      }
    }
    return $settingValues;
  }

  /**
   * Fetches setting values
   *
   * @return array
   */
  private function fetchSettingsValues() {
    $settingFields = [
      'manualdirectdebit_default_reference_prefix',
      'manualdirectdebit_new_instruction_run_dates',
      'manualdirectdebit_payment_collection_run_dates',
      'manualdirectdebit_minimum_days_to_first_payment',
    ];

    return civicrm_api3('setting', 'get', [
      'return' => $settingFields,
      'sequential' => 1,
    ]);
  }

}
