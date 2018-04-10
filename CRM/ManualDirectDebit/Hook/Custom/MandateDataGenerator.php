<?php

/**
 * This class Generates the required mandate fields automatically in case they are not submitted by the user
 */
class CRM_ManualDirectDebit_Hook_Custom_MandateDataGenerator {

  /**
   * Primary ID of Direct Debit Mandate
   *
   * @var int
   */
  private $mandateId;

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
   * List of the mandate fields to be generated
   *
   * @var array
   */
  private $fieldsToGenerate = [
    'collection_day' => FALSE,
    'dd_ref' => FALSE,
    'authorisation_date' => FALSE,
    'start_date' => FALSE,
  ];

  /**
   * Array of extension settings
   *
   * @var array
   */
  private $settings;

  public function __construct($entityID, &$params) {
    $this->entityID = $entityID;
    $this->savedFields = $params;
    $this->setMandateId();
    $this->setManualDirectDebitSettings();
  }

  /**
   * Sets `mandateId` property
   *
   */
  private function setMandateId() {
    $this->mandateId = $this->getLastMandateId();
  }

  /**
   * Gets id of last inserted Direct Debit Mandate
   *
   * @return int
   */
  private function getLastMandateId() {
    $tableName = 'civicrm_value_dd_mandate';
    $sqlSelectedDebitMandateID = "SELECT MAX(`id`) AS id FROM `$tableName` WHERE `entity_id` = %1";
    $queryResult = CRM_Core_DAO::executeQuery($sqlSelectedDebitMandateID, [
      1 => [
        $this->entityID,
        'String',
      ],
    ]);
    $queryResult->fetch();
    $lastInsertedMandateId = $queryResult->id;

    return $lastInsertedMandateId;
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
   * Generates and saves the required mandate fields  values if they are not supplied by the user.
   *
   */
  public function generate() {
    $this->generateFieldsValues();
    $this->saveGeneratedValues();
    $this->updateContributionReceiveDate();
  }

  /**
   * Finds which of necessary fields have to be generated
   *
   */
  private function generateFieldsValues() {
    foreach ($this->savedFields as $field) {
      if (in_array($field['column_name'], $this->fieldsToGenerate)) {
        $this->fieldsToGenerate[$field['column_name']] = $field['value'];
      }
    }

    if ($this->fieldsToGenerate['collection_day'] === FALSE) {
      $this->fieldsToGenerate['collection_day'] = $this->generateCollectionDay();
    }

    if ($this->fieldsToGenerate['dd_ref'] === FALSE) {
      $this->fieldsToGenerate['dd_ref'] = $this->generateDirectDebitReference();
    }

    if ($this->fieldsToGenerate['authorisation_date'] === FALSE) {
      $this->fieldsToGenerate['authorisation_date'] = $this->generateAuthorizationDate();
    }

    if ($this->fieldsToGenerate['start_date'] === FALSE) {
      $startDateGenerator = new CRM_ManualDirectDebit_Hook_Custom_MandateStartDateGenerator($this->fieldsToGenerate['collection_day']);
      $this->fieldsToGenerate['start_date'] = $startDateGenerator->generate();
    }
  }

  /**
   * Generates collection date
   *
   * @return int
   */
  private function generateCollectionDay() {
    $closestNewInstructionRunDate = $this->findClosestDate($this->settings['new_instruction_run_dates'], date('Y-m-d'));
    $closestNewInstructionRunDateWithOffset = $closestNewInstructionRunDate + $this->settings['minimum_days_to_first_payment'];

    return $this->findClosestDate($this->settings['payment_collection_run_dates'], $closestNewInstructionRunDateWithOffset);
  }

  /**
   * Gets closest date to 'selectedDate' in array 'possibleRunDates'. If it`s
   * not exists it gets lowest value in 'possibleRunDates'
   *
   * @param $possibleRunDates
   * @param $selectedDate
   *
   * @return int|mixed
   */
  private function findClosestDate($possibleRunDates, $selectedDate) {
    $closestDay = 0;
    foreach ($possibleRunDates as $possibleDate) {
      if ($possibleDate > $selectedDate) {
        $closestDay = $possibleDate;
        break;
      }
    }

    if ($closestDay === 0) {
      $closestDay = min($possibleRunDates);
    }

    return $closestDay;
  }

  /**
   * Generates 'Direct Debit reference' by adding current mandate id to
   * predeclared 'default_reference_prefix'
   *
   * @return string
   */
  private function generateDirectDebitReference() {
    return $this->settings['default_reference_prefix'] . $this->mandateId;
  }

  /**
   * Gets current day and set it like authorization date
   *
   * @return string
   */
  private function generateAuthorizationDate() {
    return (new DateTime())->format('Y-m-d H:i:s');
  }

  /**
   * Saves all generated values
   */
  private function saveGeneratedValues() {
    $tableName = "civicrm_value_dd_mandate";
    $setValueTemplateFields = [];
    $fieldsValues = [];
    $i = 0;
    foreach ($this->fieldsToGenerate as $key => $field) {
      $setValueTemplateFields[] = $tableName . "." . $key . " = %" . ($i);
      $fieldsValues[$i] = [$field, ucfirst(gettype($field))];
      $i++;
    }
    $setValueTemplate = implode(', ', $setValueTemplateFields);

    $query = "UPDATE $tableName SET $setValueTemplate WHERE $tableName.id = $this->mandateId";
    CRM_Core_DAO::executeQuery($query, $fieldsValues);
  }

  /**
   * Sets contribution `receive_date` to mandate 'start date'
   */
  private function updateContributionReceiveDate() {
    civicrm_api3('Contribution', 'get', [
      'return' => "id",
      'contact_id' => $this->entityID,
      'options' => ['limit' => 1, 'sort' => "contribution_id DESC"],
      'api.Contribution.create' => [
        'id' => '$value.id',
        'receive_date' => $this->fieldsToGenerate['start_date'],
      ],
    ]);
  }

}
