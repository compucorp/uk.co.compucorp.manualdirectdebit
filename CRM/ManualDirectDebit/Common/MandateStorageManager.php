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

    // Creates "New Direct Debit Recurring Payment" activity
    $activity = new CRM_ManualDirectDebit_Hook_Post_RecurContribution_Activity($contributionId, 'create');
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
    $transaction->commit();

    $this->assignMandate($mandateId, $currentContactId);

    $this->launchCustomHook($currentContactId, $mandateValues);
  }

  /**
   * Launches custom hook
   *
   * @param $currentContactId
   * @param $mandateValues
   */
  private function launchCustomHook($currentContactId, $mandateValues) {
    $directDebitMandateId = civicrm_api3('CustomGroup', 'getvalue', [
      'return' => "id",
      'name' => "direct_debit_mandate",
    ]);

    $mandateFields = civicrm_api3('CustomField', 'get', [
      'sequential' => 1,
      'custom_group_id' => "direct_debit_mandate",
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
    $transaction->commit();

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
   * @param $recurContribution
   * @param $contactId
   *
   * @return array
   */
  private function getContributionsForMandate($recurContribution, $contactId) {
    $contributionIds = [];

    if ($this->isMembershipExtrasInstalled() && $this->isAmountOfInstallmentsEqualAmountOfContributions($recurContribution)) {
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
   * Checks if amount of installments equal to amount of contributions
   *
   * @param $recurContribution
   *
   * @return bool
   */
  private function isAmountOfInstallmentsEqualAmountOfContributions($recurContribution) {
    $contributionRecurDao = CRM_Contribute_BAO_ContributionRecur::findById($recurContribution);
    $amountOfInstallments = $contributionRecurDao->installments;

    $amountOfContributions = civicrm_api3('Contribution', 'getcount', [
      'contribution_recur_id' => $recurContribution,
    ]);

    return $amountOfInstallments == $amountOfContributions ? TRUE : FALSE;
  }


  /**
   * Changes mandate id for contribution
   *
   * @param $mandateId
   * @param $oldMandateId
   */
  public function changeMandateForContribution($mandateId, $oldMandateId) {
    $completedStatusId = CRM_ManualDirectDebit_Common_OptionValue::getValueForOptionValue('contribution_status', 'Completed');

    $query = "UPDATE `civicrm_value_dd_information` AS dd_information 
              LEFT JOIN `civicrm_contribution` AS contribution ON dd_information.entity_id = contribution.id 
              SET dd_information.mandate_id = $mandateId 
              WHERE dd_information.mandate_id = $oldMandateId 
              AND contribution.contribution_status_id != $completedStatusId";
    CRM_Core_DAO::executeQuery($query);
  }

}
