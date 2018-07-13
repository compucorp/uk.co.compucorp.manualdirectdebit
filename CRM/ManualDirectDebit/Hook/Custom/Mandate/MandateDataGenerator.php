<?php

/**
 * This class Generates the required mandate fields automatically in case they
 * are not submitted by the user
 */
class CRM_ManualDirectDebit_Hook_Custom_Mandate_MandateDataGenerator {

  /**
   * List of the mandate fields to be generated
   *
   * @var array
   */
  private $fieldsToGenerate = [
    'dd_ref' => FALSE,
    'start_date' => FALSE,
  ];

  /**
   * Array of extension settings
   *
   * @var array
   */
  private $settings;

  /**
   * Parameters which submitted by form
   *
   * @var array
   */
  private $savedFields;

  /**
   * Primary ID of Direct Debit Mandate
   *
   * @var int
   */
  private $mandateId;

  /**
   * Object which manage writing and reading Data from DB
   *
   * @var CRM_ManualDirectDebit_Common_MandateStorageManager
   */
  private $mandateStorage;

  public function __construct($currentContactId, $settings, &$params) {
    $this->settings = $settings;
    $this->savedFields = $params;
    $this->mandateStorage = new CRM_ManualDirectDebit_Common_MandateStorageManager();
    $this->mandateId = $this->mandateStorage->getLastInsertedMandateId($currentContactId);
  }

  /**
   * Finds which of necessary fields have to be generated
   *
   */
  public function generateMandateFieldsValues() {
    foreach ($this->savedFields as $field) {
      $isFieldNameExist = isset($field['column_name']) && !empty($field['column_name']);

      if ($isFieldNameExist) {
        $isFieldNeedToBeGenerated = array_key_exists($field['column_name'], $this->fieldsToGenerate);

        if ($isFieldNeedToBeGenerated) {
          $this->fieldsToGenerate[$field['column_name']] = $field['value'];
        }
      }
    }

    if ($this->fieldsToGenerate['dd_ref'] == FALSE) {
      $this->fieldsToGenerate['dd_ref'] = $this->generateDirectDebitReference();
    }

    if ($this->fieldsToGenerate['start_date'] == FALSE) {
      $this->fieldsToGenerate['start_date'] = $this->generateStartDate();
    }
    else {
      $date = new DateTime();

      $this->fieldsToGenerate['start_date'] = $date->setTimestamp(
        strtotime($this->fieldsToGenerate['start_date'])
      )->format('Y-m-d H:i:s');
    }
  }

  /**
   * Generates 'Direct Debit reference' by adding current mandate id to
   * predeclared 'default_reference_prefix'
   *
   * @return string
   */
  private function generateDirectDebitReference() {
    $settingsManager = new CRM_ManualDirectDebit_Common_SettingsManager();
    $minimumReferencePrefixLength = (int) $settingsManager->getManualDirectDebitSettings()['minimum_reference_prefix_length'];
    return $this->settings['default_reference_prefix'] . str_pad($this->mandateId, $minimumReferencePrefixLength, '0', STR_PAD_LEFT);
  }

  /**
   * Gets current day and set it like authorization date
   *
   * @return string
   */
  private function generateStartDate() {
    return (new DateTime())->format('Y-m-d H:i:s');
  }

  /**
   * Saves all generated values
   */
  public function saveGeneratedMandateValues() {
    $this->mandateStorage->updateMandateId($this->fieldsToGenerate, $this->mandateId);
  }

  /**
   * Gets mandate start date
   *
   * @return object
   */
  public function getMandateStartDate() {
    return $this->fieldsToGenerate['start_date'];
  }

}
