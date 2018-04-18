<?php

/**
 * This class launch required fields generator for different entities
 */
class CRM_ManualDirectDebit_Hook_Custom_DataGenerator {

  /**
   * Array of extension settings
   *
   * @var array
   */
  private $settings;

  /**
   * Contact entity ID
   *
   * @var int
   */
  private $entityID;

  /**
   * Parameters which submitted by form
   *
   * @var array
   */
  private $savedFields;

  public function __construct($entityID, &$params) {
    $this->entityID = $entityID;
    $this->savedFields = $params;
    $this->setManualDirectDebitSettings();
  }

  /**
   *  Sets `settings` property
   *
   */
  private function setManualDirectDebitSettings() {
    $this->settings = $this->getManualDirectDebitSettings();
  }

  /**
   * Gets all extension settings
   *
   * @return array
   */
  private function getManualDirectDebitSettings() {
    $settingFields = [
      'manualdirectdebit_default_reference_prefix',
      'manualdirectdebit_new_instruction_run_dates',
      'manualdirectdebit_payment_collection_run_dates',
      'manualdirectdebit_minimum_days_to_first_payment',
    ];
    $settingValues = civicrm_api3('setting', 'get', [
      'return' => $settingFields,
      'sequential' => 1,
    ]);

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
   * Generates and saves the required fields values if they are not supplied by the user.
   *
   */
  public function generate() {
    $mandateDataGenerator = new CRM_ManualDirectDebit_Hook_Custom_MandateDataGenerator($this->entityID, $this->settings, $this->savedFields);
    $mandateDataGenerator->generateMandateFieldsValues();
    $mandateDataGenerator->saveGeneratedMandateValues();
    $contributionDataGenerator = new CRM_ManualDirectDebit_Hook_Custom_ContributionDataGenerator($this->entityID, $this->settings);
    $contributionDataGenerator->setMandateStartDate($mandateDataGenerator->getMandateStartDate());
    $contributionDataGenerator->generateContributionFieldsValues();
    $contributionDataGenerator->saveGeneratedContributionValues();
  }

}
