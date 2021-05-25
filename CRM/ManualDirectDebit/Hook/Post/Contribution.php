<?php

use CRM_ManualDirectDebit_Common_CollectionReminderSendFlagManager as CollectionReminderSendFlagManager;
use CRM_ManualDirectDebit_Hook_Custom_Contribution_ContributionDataGenerator as ContributionDataGenerator;
use CRM_ManualDirectDebit_Common_MandateStorageManager as MandateStorageManager;

class CRM_ManualDirectDebit_Hook_Post_Contribution {

  private $contributionId;
  private $contactId;
  private $contributionRecurId;
  private $mandateContributionConnector;

  public function __construct($contributionId, $contactId, $contributionRecurId) {
    $this->contributionId = $contributionId;
    $this->contactId = $contactId;
    $this->contributionRecurId = $contributionRecurId;
    $this->mandateContributionConnector = CRM_ManualDirectDebit_Hook_MandateContributionConnector::getInstance();
  }

  public function process() {
    $this->createCollectionReminderSendFlag();
    $this->processTheWebformSubmission();
  }

  /**
   * Processes the webform submission.
   */
  private function processTheWebformSubmission() {
    // The module webform_civicrm will load the class
    // wf_crm_webform_postprocess in the hook_webform_submission_presave hook.
    // If it is loaded then this is a webform submission.
    if (!class_exists('wf_crm_webform_postprocess')) {
      return;
    }

    $isThereExistingMandateReference = $this->isThereExistingMandateReference();
    if ($isThereExistingMandateReference) {
      return;
    }

    $mandateId = $this->mandateContributionConnector->getMandateId();
    if (!$mandateId) {
      $errorMessage = 'Failed to get the mandateId from mandateContributionConnector for the contribution with Id: ' . $this->contributionId;
      Civi::log()->error($errorMessage);
      return;
    }

    $mandateStartDate = $this->getMandateStartDate($mandateId);
    $manualDirectDebitSettings = $this->getManualDirectDebitSettings();
    $this->generateContributionData($mandateStartDate);
    $this->createDependency();
  }

  private function createCollectionReminderSendFlag() {
    CollectionReminderSendFlagManager::setIsNotificationSentToUnsent($this->contributionId);
  }

  /**
   * Generates and saves the Contribution required fields.
   */
  private function generateContributionData($manualDirectDebitSettings, $mandateStartDate) {
    $contributionDataGenerator = new ContributionDataGenerator($this->contactId, $manualDirectDebitSettings, $mandateStartDate);
    $contributionDataGenerator->generateContributionFieldsValues();
    $contributionDataGenerator->saveGeneratedContributionValues();
  }

  /**
   * Checks if the recur contribution
   * already has a mandate assigned to it
   * or not.
   *
   * @return bool
   */
  private function isThereExistingMandateReference() {
    $mandateReference = NULL;
    if ($this->contributionRecurId) {
      $mandateReference = CRM_ManualDirectDebit_BAO_RecurrMandateRef::getMandateIdForRecurringContribution($this->contributionRecurId);
    }

    return $mandateReference;
  }

  /**
   * Creates dependency between mandate and contribution
   */
  private function createDependency() {
    $this->mandateContributionConnector->createDependency();
  }

  /**
   * Gets mandate start date.
   *
   * @param $mandateId
   */
  private function getMandateStartDate($mandateId) {
    $mandateStorageManager = new MandateStorageManager();
    $mandate = $mandateStorageManager->getMandate($mandateId);
    $mandateStartDate = $mandate->start_date;

    return $mandateStartDate;
  }

  /**
   * Gets manual direct debit settings.
   */
  private function getManualDirectDebitSettings() {
    $settingsManager = new CRM_ManualDirectDebit_Common_SettingsManager();
    $manualDirectDebitSettings = $settingsManager->getManualDirectDebitSettings();

    return $manualDirectDebitSettings;
  }

}
