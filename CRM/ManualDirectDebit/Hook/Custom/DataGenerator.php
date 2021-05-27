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
   * Generates and saves the required fields values if they are not supplied by the user.
   */
  public function runDataGeneration() {
    $isThereExistingMandateReference = $this->isThereExistingMandateReference();
    $this->generateMandateData();

    if (!$isThereExistingMandateReference) {
      $this->generateContributionData();
    }
  }

  /**
   * Checks if the recur contribution
   * already has a mandate assigned to it
   * or not.
   *
   * @return bool
   */
  private function isThereExistingMandateReference() {
    $contactLastRecurContribution = civicrm_api3('ContributionRecur', 'get', [
      'sequential' => 1,
      'return' => ['id'],
      'contact_id' => $this->entityID,
      'options' => ['limit' => 1, 'sort' => 'contribution_recur_id DESC'],
    ]);

    $mandateReference = NULL;
    if (!empty($contactLastRecurContribution['values'][0]['id'])) {
      $contributionRecurId = $contactLastRecurContribution['values'][0]['id'];
      $mandateReference = CRM_ManualDirectDebit_BAO_RecurrMandateRef::getMandateIdForRecurringContribution($contributionRecurId);
    }

    if (!empty($mandateReference)) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Generates and saves the Mandate required fields.
   */
  public function generateMandateData() {
    $this->mandateDataGenerator->generateMandateFieldsValues();
    $this->mandateDataGenerator->saveGeneratedMandateValues();
  }

  /**
   * Generates and saves the Contribution required fields.
   */
  private function generateContributionData() {
    $contributionDataGenerator = new CRM_ManualDirectDebit_Hook_Custom_Contribution_ContributionDataGenerator($this->entityID, $this->settings);
    $contributionDataGenerator->setMandateStartDate($this->mandateDataGenerator->getMandateStartDate());
    $contributionDataGenerator->generateContributionFieldsValues();
    $contributionDataGenerator->saveGeneratedContributionValues();
  }

}
