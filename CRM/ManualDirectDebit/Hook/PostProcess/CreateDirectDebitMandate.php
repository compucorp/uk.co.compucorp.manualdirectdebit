<?php

/**
 *  Create an empty mandate and connect it to a new contribution
 */
class CRM_ManualDirectDebit_Hook_PostProcess_CreateDirectDebitMandate {

  /**
   * Id of newly generated mandate
   *
   * @var int
   */
  private $mandateId;

  public function __construct($assignedContactId) {
    $this->mandateId = $this->createMandate($assignedContactId);
  }

  /**
   * Assign a newly generated mandate into appropriate contribution
   *
   * @param $contributionId
   */
  public function assignMandateIdIntoContribution($contributionId) {
    $mandateIdCustomFieldId = civicrm_api3('CustomField', 'getvalue', [
      'return' => "id",
      'name' => "mandate_id",
    ]);

    civicrm_api3('Contribution', 'create', [
      "custom_$mandateIdCustomFieldId" => $this->mandateId,
      'id' => $contributionId,
    ]);
  }

  /**
   * Assign a newly generated mandate into appropriate recurring contribution
   *
   * @param $recurrContributionId
   */
  public function assignMandateIdIntoRecurringContribution($recurrContributionId) {
      $rows = [
        'recurr_id' => $recurrContributionId,
        'mandate_id' => $this->mandateId,
      ];
      CRM_ManualDirectDebit_BAO_RecurrMandateRef::create($rows);
  }

  /**
   * Creates a new direct debit mandate and returns id of the last inserted one
   *
   * @param $assignedContactId
   *
   * @return int
   */
  private function createMandate($assignedContactId) {
    $tableName = 'civicrm_value_dd_mandate';
    $sqlInsertedInDirectDebitMandate = "INSERT INTO `$tableName` (`entity_id`) VALUES (%1)";
    CRM_Core_DAO::executeQuery($sqlInsertedInDirectDebitMandate, [1 => [$assignedContactId, 'String']]);

    $sqlSelectedDebitMandateID = "SELECT MAX(`id`) AS id FROM `$tableName` WHERE `entity_id` = %1";
    $queryResult = CRM_Core_DAO::executeQuery($sqlSelectedDebitMandateID, [1 => [$assignedContactId, 'String']]);
    $queryResult->fetch();
    $generatedMandateId = $queryResult->id;

    return $generatedMandateId;
  }

}
