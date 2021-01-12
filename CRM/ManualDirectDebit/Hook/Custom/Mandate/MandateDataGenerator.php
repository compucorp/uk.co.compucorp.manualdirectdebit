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
    'authorisation_date' => FALSE,
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

    if ($this->fieldsToGenerate['dd_ref'] == FALSE || $this->fieldsToGenerate['dd_ref'] == 'DD Ref') {
      $this->fieldsToGenerate['dd_ref'] = $this->generateDirectDebitReference();
    }

    if ($this->fieldsToGenerate['start_date'] == FALSE) {
      $this->fieldsToGenerate['start_date'] = $this->generateCurrentDateAndTime();
    }
    else {
      $this->fieldsToGenerate['start_date'] = $this->formatDate($this->fieldsToGenerate['start_date']);
    }

    if ($this->fieldsToGenerate['authorisation_date'] == FALSE) {
      $this->fieldsToGenerate['authorisation_date'] = $this->generateCurrentDateAndTime();
    }
    else {
      $this->fieldsToGenerate['authorisation_date'] = $this->formatDate($this->fieldsToGenerate['authorisation_date']);
    }
  }

  /**
   * Generates 'Direct Debit reference' by adding current mandate id to
   * predeclared 'default_reference_prefix'
   *
   * @return string
   */
  private function generateDirectDebitReference() {
    $prefixLength = strlen($this->settings['default_reference_prefix']);
    $mandateIdLength = 0;
    if ($this->settings['minimum_reference_prefix_length'] > $prefixLength) {
      $mandateIdLength = $this->settings['minimum_reference_prefix_length'] - $prefixLength;
    }
    $mandateIdPart = str_pad($this->mandateId, $mandateIdLength, '0', STR_PAD_LEFT);

    return $this->settings['default_reference_prefix'] . $mandateIdPart;
  }

  /**
   * Formats given date in Y-m-d H:i:s format.
   *
   * @param string $dateString
   *
   * @return string
   */
  private function formatDate($dateString) {
    try {
      $date = new DateTime();
      $date->setTimestamp(strtotime($dateString));
      $formattedDate = $date->format('Y-m-d H:i:s');
    }
    catch (\Exception $e) {
      $formattedDate = $this->generateCurrentDateAndTime();
    }

    return $formattedDate;
  }

  /**
   * Gets current day and set it like authorization date
   *
   * @return string
   */
  private function generateCurrentDateAndTime() {
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
