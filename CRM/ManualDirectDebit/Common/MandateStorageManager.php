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
   * Names available for dd_code field.
   */
  const DD_CODE_NAME_CANCELDIRECTDEBIT = 'cancel_a_direct_debit';

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
   * @param $contributionRecurId
   * @param $mandateId
   */
  public function assignRecurringContributionMandate($contributionRecurId, $mandateId) {
    $existingMandateId = CRM_ManualDirectDebit_BAO_RecurrMandateRef::getMandateIdForRecurringContribution($contributionRecurId);
    if ($existingMandateId && $existingMandateId == $mandateId) {
      return;
    }

    $activityType = 'create';
    $params = [
      'recurr_id' => $contributionRecurId,
      'mandate_id' => $mandateId,
    ];
    if ($existingMandateId) {
      $activityType = 'update';
      $mandateReferenceId = CRM_ManualDirectDebit_BAO_RecurrMandateRef::getMandateReferenceId($existingMandateId, $contributionRecurId);
      $params['id'] = $mandateReferenceId;
    }
    CRM_ManualDirectDebit_BAO_RecurrMandateRef::create($params);

    $activity = new CRM_ManualDirectDebit_Hook_Post_RecurContribution_Activity($contributionRecurId, $activityType);
    $activity->process();
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
   * Saves direct debit mandate data
   *
   * @param $currentContactId
   * @param $mandateValues
   *
   * @return \CRM_Core_DAO|object
   * @throws \Exception
   */
  public function saveDirectDebitMandate($currentContactId, $mandateValues) {
    // protect Data from SQL injection
    $columnName = [];
    $valuesId = [];
    $values = [];

    $i = 0;
    foreach ($mandateValues as $key => $value) {
      if (!isset($value)) {
        continue;
      }

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
      $this->launchCustomHook($currentContactId, $mandateValues);
    }
    catch (Exception $exception) {
      $transaction->rollback();

      throw $exception;
    }
    $transaction->commit();

    return $this->getMandate($mandateId);
  }

  /**
   * Launches custom hook
   *
   * @param $currentContactId
   * @param $mandateValues
   */
  public function launchCustomHook($currentContactId, $mandateValues) {
    $directDebitMandateId = civicrm_api3('CustomGroup', 'getvalue', [
      'return' => 'id',
      'name' => 'direct_debit_mandate',
    ]);

    $mandateFields = civicrm_api3('CustomField', 'get', [
      'sequential' => 1,
      'custom_group_id' => 'direct_debit_mandate',
    ])['values'];

    foreach ($mandateFields as &$currentField) {
      if (isset($mandateValues[$currentField['column_name']])) {
        $currentField['value'] = $mandateValues[$currentField['column_name']];
      }
    }

    unset($currentField);

    CRM_Utils_Hook::custom('update', $directDebitMandateId, $currentContactId, $mandateFields);
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
  public function setMandateForCreatingDependency($mandateId) {
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
  public function assignMandate($mandateId, $contactId) {
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
   * @param $recurContribution
   * @param $contactId
   *
   * @return array
   */
  private function getContributionsForMandate($recurContribution, $contactId) {
    $contributionIds = [];

    if ($this->isMembershipExtrasInstalled()) {
      /**
       * If Membership was installed first, it gets all contributions for last inserted recurring contribution
       * for current contact
       */
      $allContributionIdsForRecurr = civicrm_api3('Contribution', 'get', [
        'sequential' => 1,
        'return' => ["id"],
        'contribution_recur_id' => $recurContribution,
      ]);

      foreach ($allContributionIdsForRecurr['values'] as $value) {
        $contributionIds[] = $value['contribution_id'];
      }
    }
    else {
      /**
       * If DirectDebit was installed first, it gets last created contribution id for current user
       */
      $sqlContributionID = "SELECT MAX(`id`) AS id FROM civicrm_contribution WHERE `contact_id` = %1";
      $queryResult = CRM_Core_DAO::executeQuery($sqlContributionID, [
        1 => [
          $contactId,
          'String',
        ],
      ]);
      $queryResult->fetch();
      $contributionIds[] = $queryResult->id;
    }

    return $contributionIds;
  }

  /**
   * Checks if extension 'membershipextras' is installed
   *
   * @return bool
   */
  private function isMembershipExtrasInstalled() {
    $membershipExtension = new CRM_Core_DAO_Extension();
    $membershipExtension->full_name = 'uk.co.compucorp.membershipextras';
    $membershipExtension->find(TRUE);

    $isMembershipExist = $membershipExtension->id && $membershipExtension->is_active == 1;

    return $isMembershipExist ? TRUE : FALSE;
  }

  /**
   * Changes mandate id for contribution
   *
   * @param $mandateId
   * @param $oldMandateId
   */
  public function changeMandateForContribution($mandateId, $oldMandateId) {
    $completedStatusId = CRM_ManualDirectDebit_Common_OptionValue::getValueForOptionValue(
      'contribution_status', 'Completed'
    );

    $query = "UPDATE `civicrm_value_dd_information` AS dd_information
              LEFT JOIN `civicrm_contribution` AS contribution ON dd_information.entity_id = contribution.id
              SET dd_information.mandate_id = %1
              WHERE dd_information.mandate_id = %2
              AND contribution.contribution_status_id != %3";
    CRM_Core_DAO::executeQuery($query, [
      1 => [$mandateId, 'String'],
      2 => [$oldMandateId, 'String'],
      3 => [$completedStatusId, 'Integer'],
    ]);
  }

  /**
   * Deletes mandate and all references to it.
   *
   * @param $mandateID
   *
   * @throws \Exception
   */
  public function deleteMandate($mandateID) {
    $transaction = new CRM_Core_Transaction();

    try {
      $recurrMandateRef = new CRM_ManualDirectDebit_BAO_RecurrMandateRef();
      $recurrMandateRef->mandate_id = $mandateID;
      $recurrMandateRef->delete();

      $query = '
        DELETE FROM `civicrm_value_dd_information`
        WHERE civicrm_value_dd_information.mandate_id = %1
      ';
      CRM_Core_DAO::executeQuery($query, [
        1 => [$mandateID, 'String'],
      ]);

      $dao = CRM_Core_BAO_CustomGroup::class;
      $groupID = CRM_Core_DAO::getFieldValue($dao, 'direct_debit_mandate', 'id', 'name');
      CRM_Core_BAO_CustomValue::deleteCustomValue($mandateID, $groupID);
    }
    catch (Exception $e) {
      $transaction->rollback();
      $message = "An error occurred deleting mandate with id ({$mandateID}): " . $e->getMessage();

      throw new Exception($message);
    }

    $transaction->commit();
  }

  /**
   * Returns an object with the mandates data.
   *
   * @param int $mandateID
   *
   * @return \CRM_Core_DAO|object
   */
  public function getMandate($mandateID) {
    $sqlSelectDebitMandateID = '
      SELECT *
      FROM ' . self::DIRECT_DEBIT_TABLE_NAME . '
      WHERE id = %1
    ';
    $queryResult = CRM_Core_DAO::executeQuery($sqlSelectDebitMandateID, [
      1 => [$mandateID, 'Int'],
    ]);
    $queryResult->fetch();

    return $queryResult;
  }

  /**
   * Obtains mandates for given contact ID.
   *
   * @param int $contactID
   *
   * @return array
   */
  public function getMandatesForContact($contactID) {
    $sqlSelectDebitMandateID = '
      SELECT *
      FROM ' . self::DIRECT_DEBIT_TABLE_NAME . '
      WHERE `entity_id` = %1
    ';
    $queryResult = CRM_Core_DAO::executeQuery($sqlSelectDebitMandateID, [
      1 => [$contactID, 'String'],
    ]);

    $result = [];
    while ($queryResult->fetch()) {
      $result[] = $queryResult->toArray();
    }

    return $result;
  }

}
