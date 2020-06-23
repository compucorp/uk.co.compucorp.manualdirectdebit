<?php

use CRM_ManualDirectDebit_Test_Fabricator_Setting as SettingFabricator;

require_once __DIR__ . '/../../../BaseHeadlessTest.php';

/**
 * Runs tests on SettingsManager.
 *
 * @group headless
 */
class CRM_ManualDirectDebit_Common_SettingsManagerTest extends BaseHeadlessTest {

  public function setUp() {
    SettingFabricator::fabricate();
  }

  public function testGetManualDirectDebitSettings() {
    $settingsManager = new CRM_ManualDirectDebit_Common_SettingsManager();
    $settings = $settingsManager->getManualDirectDebitSettings();
    $this->assertNotEmpty($settings['default_reference_prefix']);
    $this->assertNotEmpty($settings['minimum_reference_prefix_length']);
    $this->assertNotEmpty($settings['new_instruction_run_dates']);
    $this->assertNotEmpty($settings['payment_collection_run_dates']);
    $this->assertNotEmpty($settings['minimum_days_to_first_payment']);
  }

}
