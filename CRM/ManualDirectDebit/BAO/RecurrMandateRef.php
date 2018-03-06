<?php

class CRM_ManualDirectDebit_BAO_RecurrMandateRef extends CRM_ManualDirectDebit_DAO_RecurrMandateRef {

  /**
   * @param $params
   * @param $defaults
   *
   * @return \CRM_ManualDirectDebit_BAO_RecurrMandateRef|null
   */
  public static function retrieve(&$params, &$defaults) {
    $object = new CRM_ManualDirectDebit_BAO_RecurrMandateRef();
    $object->copyValues($params);

    if ($object->find(TRUE)) {
      CRM_Core_DAO::storeValues($object, $defaults);
      $object->free();
      return $object;
    }

    return NULL;
  }

  /**
   * @param $params
   *
   * @return \CRM_Core_DAO
   */
  public static function add(&$params) {
    $entity = new CRM_ManualDirectDebit_BAO_RecurrMandateRef();
    $entity->copyValues($params);

    return $entity->save();
  }

  /**
   * @param $params
   *
   * @return \CRM_Core_DAO
   */
  public static function &create(&$params) {
    $transaction = new CRM_ManualDirectDebit_BAO_RecurrMandateRef();

    if (!empty($params['id'])) {
      CRM_Utils_Hook::pre('edit', self::getEntityName(), $params['id'], $params);
    }
    else {
      CRM_Utils_Hook::pre('create', self::getEntityName(), NULL, $params);
    }

    $entityData = self::add($params);

    if (is_a($entityData, 'CRM_Core_Error')) {
      $transaction->rollback();
      return $entityData;
    }

    $transaction->commit();

    if (!empty($params['id'])) {
      CRM_Utils_Hook::post('edit', self::getEntityName(), $entityData->id, $entityData);
    }
    else {
      CRM_Utils_Hook::post('create', self::getEntityName(), $entityData->id, $entityData);
    }

    return $entityData;
  }

  /**
   * Delete entity
   *
   * @param int $id
   */
  public static function deleteEntity($id) {
    $entity = new CRM_ManualDirectDebit_BAO_RecurrMandateRef();
    $entity->id = $id;
    $params = [];
    if ($entity->find(TRUE)) {
      CRM_Utils_Hook::pre('delete', self::getEntityName(), $entity->id, $params);
      $entity->delete();
      CRM_Utils_Hook::post('delete', self::getEntityName(), $entity->id, $entity);
    }
  }

  /**
   * Gets all rows
   *
   * @return array
   */
  public static function getAll() {
    $query = CRM_Utils_SQL_Select::from(CRM_ManualDirectDebit_BAO_RecurrMandateRef::getTableName())
      ->toSQL();

    return CRM_Core_DAO::executeQuery($query)->fetchAll();
  }

}
