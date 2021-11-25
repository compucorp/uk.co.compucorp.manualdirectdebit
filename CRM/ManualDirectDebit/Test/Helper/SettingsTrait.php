<?php

use CRM_ManualDirectDebit_Common_SettingsManager as SettingsManager;

trait CRM_ManualDirectDebit_Test_Helper_SettingsTrait {

  /**
   * Mocks Manual Direct Debit Settings.
   *
   * @param $settingValues
   */
  protected function mockSettings($settingValues) {
    $settings = [
      'manualdirectdebit_default_reference_prefix' => $settingValues['default_reference_prefix'],
      'manualdirectdebit_minimum_reference_prefix_length' => $settingValues['minimum_reference_prefix_length'],
      'manualdirectdebit_minimum_days_to_first_payment' => $settingValues['minimum_days_to_first_payment'],
      'manualdirectdebit_second_instalment_date_behaviour' => $settingValues['second_instalment_date_behaviour'],
      'manualdirectdebit_new_instruction_run_dates' => $settingValues['new_instruction_run_dates'],
      'manualdirectdebit_payment_collection_run_dates' => $settingValues['payment_collection_run_dates'],
    ];

    foreach ($settings as $key => $setting) {
      Civi::settings()->set($key, $setting);
    }
  }

  /**
   * Builds a mock class to manage DD settings.
   *
   * @param $settings
   * @return mixed
   */
  private function buildSettingsManagerMock($settings) {
    $settingsManager = $this->createMock(SettingsManager::class);
    $settingsManager
      ->method('getManualDirectDebitSettings')
      ->willReturn(array_merge($this->defaultDDSettings, $settings));

    return $settingsManager;
  }

}
