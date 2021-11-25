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

  /**
   * Instance of Mandate Data Generator
   *
   * @var object
   */
  private $mandateDataGenerator;

  public function __construct($entityID, &$params) {
    $this->entityID = $entityID;
    $this->savedFields = $params;
    $settingsManager = new CRM_ManualDirectDebit_Common_SettingsManager();
    $this->settings = $settingsManager->getManualDirectDebitSettings();
    $this->mandateDataGenerator = new CRM_ManualDirectDebit_Hook_Custom_Mandate_MandateDataGenerator($this->entityID, $this->settings, $this->savedFields);
  }

  /**
   * Generates and saves the Mandate required fields.
   */
  public function generateMandateData() {
    $this->mandateDataGenerator->generateMandateFieldsValues();
    $this->mandateDataGenerator->saveGeneratedMandateValues();
  }

}
