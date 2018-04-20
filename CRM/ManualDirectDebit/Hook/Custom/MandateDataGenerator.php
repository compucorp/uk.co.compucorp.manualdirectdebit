<?php

/**
 * This class Generates the required mandate fields automatically in case they are not submitted by the user
 */
class CRM_ManualDirectDebit_Hook_Custom_MandateDataGenerator {

  /**
   * Direct Debit Mandate table name
   *
   * @var string
   */
  const MANDATE_TABLE_NAME = 'civicrm_value_dd_mandate';

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

  public function __construct($entityID, $settings, &$params) {
    $this->settings = $settings;
    $this->savedFields = $params;
    $this->mandateId = $this->getLastMandateId($entityID);
  }

  /**
   * Gets id of last inserted Direct Debit Mandate
   *
   * @param $entityID
   *
   * @return int
   */
  private function getLastMandateId($entityID) {
    $sqlSelectedDebitMandateID = "SELECT MAX(`id`) AS id FROM `" . self::MANDATE_TABLE_NAME . "` WHERE `entity_id` = %1";
    $queryResult = CRM_Core_DAO::executeQuery($sqlSelectedDebitMandateID, [
      1 => [
        $entityID,
        'String',
      ],
    ]);
    $queryResult->fetch();
    $lastInsertedMandateId = $queryResult->id;

    return $lastInsertedMandateId;
  }

  /**
   * Finds which of necessary fields have to be generated
   *
   */
  public function generateMandateFieldsValues() {
    foreach ($this->savedFields as $field) {
      if (array_key_exists($field['column_name'], $this->fieldsToGenerate)) {
        $this->fieldsToGenerate[$field['column_name']] = $field['value'];
      }
    }

    if ($this->fieldsToGenerate['dd_ref'] == FALSE) {
      $this->fieldsToGenerate['dd_ref'] = $this->generateDirectDebitReference();
    }

    if ($this->fieldsToGenerate['start_date'] == FALSE) {
      $this->fieldsToGenerate['start_date'] = $this->generateStartDate();
    } else{
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
    return $this->settings['default_reference_prefix'] . $this->mandateId;
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
    $setValueTemplateFields = [];
    $fieldsValues = [];
    $i = 0;
    foreach ($this->fieldsToGenerate as $key => $field) {
      $setValueTemplateFields[] = self::MANDATE_TABLE_NAME . "." . $key . " = %" . ($i);
      $fieldsValues[$i] = [$field, ucfirst(gettype($field))];
      $i++;
    }
    $setValueTemplate = implode(', ', $setValueTemplateFields);

    $query = "UPDATE " . self::MANDATE_TABLE_NAME . " SET $setValueTemplate WHERE " . self::MANDATE_TABLE_NAME . ".id = $this->mandateId";
    CRM_Core_DAO::executeQuery($query, $fieldsValues);

    // sets mandate id, for saving dependency between mandate and contribution
    $mandateContributionConnector = CRM_ManualDirectDebit_Hook_MandateContributionConnector::getInstance();
    $mandateContributionConnector->setMandateId($this->mandateId);
  }

  /**
   * Gets mandate start date
   *
   * @return object
   */
  function getMandateStartDate(){
    return $this->fieldsToGenerate['start_date'];
  }

}
