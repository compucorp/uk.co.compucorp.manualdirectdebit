<?php

/**
 * Class provide reading and writing 'Direct Debit Mandate' and it`s dependency
 * into Data Base
 */
class CRM_ManualDirectDebit_Common_MandateStorageManager {

  /**
   * Direct debit mandate table name
   */
  const DIRECT_DEBIT_TABLE_NAME = 'civicrm_value_dd_mandate';

  /**
   * Name of table which save dependency between recurring contribution and
   * mandate
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
    $mandateIdCustomFieldId = CRM_ManualDirectDebit_Common_DirectDebitDataProvider::getCustomFieldIdByName("mandate_id");

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

    if (isset($queryResult->id) && !empty($queryResult->id)) {
      return $queryResult->id;
    } else {
      return NULL;
    }
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

    $this->assignMandate($mandateId, $currentContactId);

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
  private function setMandateForCreatingDependency($mandateId) {
    $mandateContributionConnector = CRM_ManualDirectDebit_Hook_MandateContributionConnector::getInstance();
    $mandateContributionConnector->setMandateId($mandateId);
  }

  /**
   * Assign mandate to contribution and recurring contributions after
   * submitting Membership form
   *
   * @param $mandateId
   * @param $contactId
   */
  private function assignMandate($mandateId, $contactId) {
    $sqlSelectDebitMandateID = "SELECT MAX(`id`) AS id FROM civicrm_contribution_recur WHERE `contact_id` = %1";
    $queryResult = CRM_Core_DAO::executeQuery($sqlSelectDebitMandateID, [
      1 => [
        $contactId,
        'String',
      ],
    ]);

    $queryResult->fetch();
    $lastInsertedRecurrContribution = $queryResult->id;
    $this->assignRecurringContributionMandate($lastInsertedRecurrContribution, $mandateId);

    $contributions = $this->getContributionsForMandate($lastInsertedRecurrContribution, $contactId);

    foreach ($contributions as $contributionId) {
      $this->assignContributionMandate($contributionId, $mandateId);
    }
  }

  /**
   * Gets contribution ids, which must be assigned to mandate depending on
   * which extensions are installed first
   *
   * @param $lastInsertedRecurrContribution
   * @param $contactId
   *
   * @return array
   */
  private function getContributionsForMandate($lastInsertedRecurrContribution, $contactId) {
    $contributionIds = [];

    /**
     * If DirectDebit was installed first, it gets last created contribution id for current user
     */
    if ($this->isDirectDebitInstalledBeforeMembershipExtras()) {
      $sqlContributionID = "SELECT MAX(`id`) AS id FROM civicrm_contribution WHERE `contact_id` = %1";
      $queryResult = CRM_Core_DAO::executeQuery($sqlContributionID, [
        1 => [
          $contactId,
          'String',
        ],
      ]);
      $queryResult->fetch();
      $contributionIds[] = $queryResult->id;
    } else {

      /**
       * If Membership was installed first, it gets all contributions for last inserted recurring contribution
       * for current contact
       */
      $allContributionIdsForRecurr = civicrm_api3('Contribution', 'get', [
        'sequential' => 1,
        'return' => ["id"],
        'contribution_recur_id' => $lastInsertedRecurrContribution,
      ]);

      foreach ($allContributionIdsForRecurr['values'] as $value) {
        $contributionIds[] = $value['contribution_id'];
      }

    }
    return $contributionIds;
  }

  /**
   * Checks if 'Manual Direct Debit' extension was installed before 'Membership
   * Exras'
   */
  private function isDirectDebitInstalledBeforeMembershipExtras() {
    $sqlManualDirectDebitId = "SELECT id FROM civicrm_extension WHERE `full_name` = 'uk.co.compucorp.manualdirectdebit'";
    $manualdirectdebitIdQueryResult = CRM_Core_DAO::executeQuery($sqlManualDirectDebitId);
    $manualdirectdebitIdQueryResult->fetch();
    $manualdirectdebitId = $manualdirectdebitIdQueryResult->id;

    $sqlMembershipExtrasId = "SELECT id FROM civicrm_extension WHERE `full_name` = 'uk.co.compucorp.membershipextras'";
    $membershipQueryResult = CRM_Core_DAO::executeQuery($sqlMembershipExtrasId);
    if ($membershipQueryResult->N > 0) {
      $membershipQueryResult->fetch();
      $membershipId = $membershipQueryResult->id;
      return $manualdirectdebitId < $membershipId;
    }

    return TRUE;
  }

}
