<?php

/**
 * Class CRM_ManualDirectDebit_Test_Fabricator_Setting
 */
class CRM_ManualDirectDebit_Test_Fabricator_Setting {

  /**
   * @param array $params
   */
  public static function fabricate($params = []) {
    $params = array_merge(static::getDefaultParams(), $params);
    foreach ($params as $key => $value) {
      \Civi::settings()->set($key, $value);
    }
  }

  /**
   * @return array
   */
  private static function getDefaultParams() {
    return [
      'manualdirectdebit_default_reference_prefix' => 'DD',
      'manualdirectdebit_minimum_reference_prefix_length' => 6,
      'manualdirectdebit_new_instruction_run_dates' => [1],
      'manualdirectdebit_payment_collection_run_dates' => [1],
      'manualdirectdebit_minimum_days_to_first_payment' => 2,
    ];
  }

}
