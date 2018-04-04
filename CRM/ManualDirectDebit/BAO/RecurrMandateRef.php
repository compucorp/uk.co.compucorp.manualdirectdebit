<?php

class CRM_ManualDirectDebit_BAO_RecurrMandateRef extends CRM_ManualDirectDebit_DAO_RecurrMandateRef {

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

}
