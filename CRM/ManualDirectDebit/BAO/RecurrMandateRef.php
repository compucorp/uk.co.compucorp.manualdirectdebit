<?php

class CRM_ManualDirectDebit_BAO_RecurrMandateRef extends CRM_ManualDirectDebit_DAO_RecurrMandateRef {

  /**
   * @param $params
   * @param $defaults
   *
   * @return \CRM_ManualDirectDebit_BAO_RecurrMandateRef|null
   */
  public static function retrieve($params, $defaults) {
    $object = new self();
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
  public static function add($params) {
    $entity = new self();
    $entity->copyValues($params);

    return $entity->save();
  }

  /**
   * @param $params
   *
   * @return \CRM_Core_DAO
   */
  public static function &create($params) {
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
   * Gets all rows
   *
   * @return array
   */
  public static function getAll() {
    $query = CRM_Utils_SQL_Select::from(self::getTableName())
      ->toSQL();

    return CRM_Core_DAO::executeQuery($query)->fetchAll();
  }

}
