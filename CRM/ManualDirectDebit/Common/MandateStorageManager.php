<?php

/**
 * Class provide reading and writing 'Direct Debit Mandate' and it`s dependency into Data Base
 */
class CRM_ManualDirectDebit_Common_MandateStorageManager {

  /**
   * Direct debit mandate table name
   */
  const DIRECT_DEBIT_TABLE_NAME = 'civicrm_value_dd_mandate';

  /**
   * Name of table which save dependency between recurring contribution and mandate
   */
  const DIRECT_DEBIT_RECURRING_CONTRIBUTION_NAME = 'dd_contribution_recurr_mandate_ref';

  /**
   * Assigns depandency between contribution and mandate
   *
   * @param $contributionId
   * @param $mandateID
   *
   */
  public function assignContributionMandate($contributionId, $mandateID) {
    $mandateIdCustomFieldId = civicrm_api3('CustomField', 'getvalue', [
      'return' => "id",
      'name' => "mandate_id",
    ]);

    civicrm_api3('Contribution', 'create', [
      'id' => $contributionId,
      "custom_$mandateIdCustomFieldId" => $mandateID,
    ]);
  }

  /**
   * Assigns dependency between recurring contribution and mandate
   *
   * @param $contributionId
   * @param $mandateId
   */
  public function assignRecurringContributionMandate($contributionId, $mandateId) {
    $rows = [
      'recurr_id' => $contributionId,
      'mandate_id' => $mandateId,
    ];

    CRM_ManualDirectDebit_BAO_RecurrMandateRef::create($rows);
  }

  /**
   * Gets mandate for last inserted id for current contact
   *
   * @param $currentContactId
   *
   * @return int|null
   */
  public function getLastInsertedMandateId($currentContactId) {
    $sqlSelectDebitMandateID = "SELECT MAX(`id`) AS id FROM " . self::DIRECT_DEBIT_TABLE_NAME . " WHERE `entity_id` = %1";
    $queryResult = CRM_Core_DAO::executeQuery($sqlSelectDebitMandateID, [
      1 => [
        $currentContactId,
        'String',
      ],
    ]);
    $queryResult->fetch();

    return $queryResult->id;
  }

  /**
   * Gets id of recurring contribution
   *
   * @return int|null
   */
  public function getMandateForCurrentRecurringContribution($recurContributionId) {
    $sqlSelectDebitMandateID = "SELECT `mandate_id` AS id 
      FROM " . self::DIRECT_DEBIT_RECURRING_CONTRIBUTION_NAME . " 
      WHERE `recurr_id` = %1";

    $queryResult = CRM_Core_DAO::executeQuery($sqlSelectDebitMandateID, [
      1 => [
        $recurContributionId,
        'String',
      ],
    ]);
    $queryResult->fetch();

    return $queryResult->id;
  }

  /**
   * Saves direct debit mandate data
   *
   * @param $currentContactId
   * @param $mandateValues
   */
  public function saveDirectDebitMandate($currentContactId, $mandateValues) {
    // protect Data from SQL injection
    $columnName = [];
    $valuesId = [];
    $values = [];

    $i = 0;
    foreach ($mandateValues as $key => $value) {
      $columnName[] = $key;
      $valuesId[] = "%" . $i;
      $values[$i] = [
        $value,
        ucfirst(gettype($value)),
      ];

      $i++;
    }

    $keys = implode(', ', $columnName);
    $valuesId = implode(', ', $valuesId);

    // write into Database
    $transaction = new CRM_Core_Transaction();
    try {
      $sqlInsertInDirectDebitMandate = "INSERT INTO " . self::DIRECT_DEBIT_TABLE_NAME . " ($keys) VALUES ($valuesId)";
      CRM_Core_DAO::executeQuery($sqlInsertInDirectDebitMandate, $values);

      $mandateId = $this->getLastInsertedMandateId($currentContactId);

    } catch (Exception $exception) {
      $transaction->rollback();

      throw $exception;
    }

    $this->setMandateForCreatingDependency($mandateId);
  }

  /**
   * Creates a new empty direct debit mandate
   *
   * @param $currentContactId
   */
  public function createEmptyMandate($currentContactId) {
    $transaction = new CRM_Core_Transaction();
    try {
      $sqlInsertInDirectDebitMandate = "INSERT INTO " . self::DIRECT_DEBIT_TABLE_NAME . " (`entity_id`) VALUES (%1)";
      CRM_Core_DAO::executeQuery($sqlInsertInDirectDebitMandate, [
        1 => [
          $currentContactId,
          'String',
        ],
      ]);

      $mandateId = $this->getLastInsertedMandateId($currentContactId);
    } catch (Exception $exception) {
      $transaction->rollback();
      throw $exception;
    }

    $this->setMandateForCreatingDependency($mandateId);
  }

  /**
   * Updates mandate with generated value
   *
   * @param $mandateValues
   * @param $mandateId
   */
  public function updateMandateId($mandateValues, $mandateId) {
    // protect Data from SQL injection
    $setValueTemplateFields = [];
    $fieldsValues = [];

    $i = 0;
    foreach ($mandateValues as $key => $field) {
      $setValueTemplateFields[] = self::DIRECT_DEBIT_TABLE_NAME . "." . $key . " = %" . ($i);
      $fieldsValues[$i] = [$field, ucfirst(gettype($field))];
      $i++;
    }
    $setValueTemplate = implode(', ', $setValueTemplateFields);

    // write into Data Base
    $query = "UPDATE " . self::DIRECT_DEBIT_TABLE_NAME . " 
    SET $setValueTemplate 
    WHERE " . self::DIRECT_DEBIT_TABLE_NAME . ".id = $mandateId";
    CRM_Core_DAO::executeQuery($query, $fieldsValues);

    $this->setMandateForCreatingDependency($mandateId);
  }

  /**
   * Sets mandate id, for saving dependency between mandate and contribution
   *
   * @param $mandateId
   */
  private function setMandateForCreatingDependency($mandateId){
    $mandateContributionConnector = CRM_ManualDirectDebit_Hook_MandateContributionConnector::getInstance();
    $mandateContributionConnector->setMandateId($mandateId);
  }

}
