<?php

/**
 * Generates necessary field if it wasn't filed by user
 */
class CRM_ManualDirectDebit_Hook_Custom_NecessaryFieldGenerator {

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
   * Array of necessary fields, which must to be generated
   *
   * @var array
   */
  private $necessaryFields = [
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

  public function __construct($entityID) {
    $this->entityID = $entityID;
    $this->mandateId = $this->getMandateId();
    $this->settings = $this->getManualDirectDebitSettings();
  }

  /**
   * Launches process of necessary field generation
   *
   * @param $params
   */
  public function create(&$params) {
    $this->findFieldWhichNeedToBeGenerate($params);
    $this->generateValues();
    $this->saveParameters();
    $this->generateContributionReceiveDate();
  }

  /**
   * Finds which of necessary fields have to be generated
   *
   * @param $savedFields
   */
  private function findFieldWhichNeedToBeGenerate($savedFields) {
    foreach ($savedFields as $field) {
      if (in_array($field['column_name'], $this->necessaryFields)) {
        $this->necessaryFields[$field['column_name']] = $field['value'];
      }
    }
  }

  /**
   * Generates necessary fields
   */
  private function generateValues() {

    if ($this->necessaryFields['collection_day'] === FALSE) {
      $this->necessaryFields['collection_day'] = $this->generateCollectionDay();
    }

    if ($this->necessaryFields['dd_ref'] === FALSE) {
      $this->necessaryFields['dd_ref'] = $this->generateDirectDebitReference();
    }

    if ($this->necessaryFields['authorisation_date'] === FALSE) {
      $this->necessaryFields['authorisation_date'] = $this->generateAuthorizationDate();
    }

    if ($this->necessaryFields['start_date'] === FALSE) {
      $startDateGenerator = new CRM_ManualDirectDebit_Hook_Custom_StartDateGenerator($this->necessaryFields['collection_day']);
      $this->necessaryFields['start_date'] = $startDateGenerator->calculateStartDate();
    }
  }

  /**
   * Saves all generated values
   */
  private function saveParameters() {
    $tableName = "civicrm_value_dd_mandate";
    $setValueTemplateFields = [];
    $fieldsValues = [];
    $i = 0;
    foreach ($this->necessaryFields as $key => $necessaryField) {
      $setValueTemplateFields[] = $tableName . "." . $key . " = %" . ($i);
      $fieldsValues[$i] = [$necessaryField, ucfirst(gettype($necessaryField))];
      $i++;
    }
    $setValueTemplate = implode(', ', $setValueTemplateFields);

    $query = "UPDATE $tableName SET $setValueTemplate WHERE $tableName.id = $this->mandateId";
    CRM_Core_DAO::executeQuery($query, $fieldsValues);
  }

  /**
   * Sets contribution `receive_date` to mandate 'start date'
   */
  private function generateContributionReceiveDate() {
    civicrm_api3('Contribution', 'get', [
      'return' => "id",
      'contact_id' => $this->entityID,
      'options' => ['limit' => 1, 'sort' => "contribution_id DESC"],
      'api.Contribution.create' => [
        'id' => '$value.id',
        'receive_date' => $this->necessaryFields['start_date'],
      ],
    ]);
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
   * Generates collection date
   *
   * @return int
   */
  private function generateCollectionDay() {

    $closestNewInstructionRunDate = $this->findClosestAppropriateDate($this->settings['new_instruction_run_dates'], date('Y-m-d'));
    $closestNewInstructionRunDateWithOffset = $closestNewInstructionRunDate + $this->settings['minimum_days_to_first_payment'];

    return $this->findClosestAppropriateDate($this->settings['payment_collection_run_dates'], $closestNewInstructionRunDateWithOffset);
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
  private function findClosestAppropriateDate($possibleRunDates, $selectedDate) {
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
   * Gets all extension settings
   *
   * @return array
   */
  private function getManualDirectDebitSettings() {
    $settings = [];
    $settings['default_reference_prefix'] = Civi::settings()
      ->get('manualdirectdebit_default_reference_prefix');
    $settings['new_instruction_run_dates'] = $this->incrementAllArrayValues(Civi::settings()
      ->get('manualdirectdebit_new_instruction_run_dates'));
    $settings['payment_collection_run_dates'] = $this->incrementAllArrayValues(Civi::settings()
      ->get('manualdirectdebit_payment_collection_run_dates'));
    $settings['minimum_days_to_first_payment'] = Civi::settings()
      ->get('manualdirectdebit_minimum_days_to_first_payment');

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
   * Gets id of last inserted Direct Debit Mandate
   *
   * @return int
   */
  private function getMandateId() {
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

}
