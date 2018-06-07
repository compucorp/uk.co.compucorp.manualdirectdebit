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
    $settingsManager = new CRM_ManualDirectDebit_Common_SettingsManager();
    $this->settings = $settingsManager->getManualDirectDebitSettings();
  }

  /**
   * Generates and saves the required fields values if they are not supplied by the user.
   */
  public function generate() {
    $mandateDataGenerator = new CRM_ManualDirectDebit_Hook_Custom_Mandate_MandateDataGenerator($this->entityID, $this->settings, $this->savedFields);
    $mandateDataGenerator->generateMandateFieldsValues();
    $mandateDataGenerator->saveGeneratedMandateValues();
    $contributionDataGenerator = new CRM_ManualDirectDebit_Hook_Custom_Contribution_ContributionDataGenerator($this->entityID, $this->settings);
    $contributionDataGenerator->setMandateStartDate($mandateDataGenerator->getMandateStartDate());
    $contributionDataGenerator->generateContributionFieldsValues();
    $contributionDataGenerator->saveGeneratedContributionValues();
  }

}
