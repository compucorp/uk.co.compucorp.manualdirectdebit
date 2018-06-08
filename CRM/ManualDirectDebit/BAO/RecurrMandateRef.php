<?php

class CRM_ManualDirectDebit_BAO_RecurrMandateRef extends CRM_ManualDirectDebit_DAO_RecurrMandateRef {

  /**
   * Name of table which save dependency between recurring contribution and
   * mandate
   */
  const DIRECT_DEBIT_RECURRING_CONTRIBUTION_NAME = 'dd_contribution_recurr_mandate_ref';

  /**
   * @param $params
   * @param $defaults
   *
   * @return \CRM_ManualDirectDebit_BAO_RecurrMandateRef|null
   */
  public static function retrieve($params, $defaults) {
    $self = new self();
    $self->copyValues($params);

    if ($self->find(TRUE)) {
      CRM_Core_DAO::storeValues($self, $defaults);
      $self->free();
      return $self;
    }

    return NULL;
  }

  /**
   * @param $params
   *
   * @return \CRM_Core_DAO
   */
  public static function add($params) {
    $recurrMandateRef = new self();
    $recurrMandateRef->copyValues($params);

    return $recurrMandateRef->save();
  }

  /**
   * @param $params
   *
   * @return \CRM_Core_DAO
   */
  public static function create($params) {
    if (!empty($params['id'])) {
      CRM_Utils_Hook::pre('edit', self::getEntityName(), $params['id'], $params);
    }
    else {
      CRM_Utils_Hook::pre('create', self::getEntityName(), NULL, $params);
    }

    $entityData = self::add($params);

    if (is_a($entityData, 'CRM_Core_Error')) {
      return $entityData;
    }

    if (!empty($params['id'])) {
      CRM_Utils_Hook::post('edit', self::getEntityName(), $entityData->id, $entityData);
    }
    else {
      CRM_Utils_Hook::post('create', self::getEntityName(), $entityData->id, $entityData);
    }

    return $entityData;
  }

  /**
   * Gets id of recurring contribution
   *
   * @param $recurContributionId
   *
   * @return int|null
   */
  public static function getMandateIdForRecurringContribution($recurContributionId) {
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
   * Changes mandate id for recurring contribution
   *
   * @param $mandateId
   * @param $recurr
   */
  public static function changeMandateForRecurrContribution($mandateId, $recurr) {
    $query = "UPDATE " . self::DIRECT_DEBIT_RECURRING_CONTRIBUTION_NAME . " SET mandate_id = $mandateId WHERE recurr_id = $recurr";
    CRM_Core_DAO::executeQuery($query);
  }

}
