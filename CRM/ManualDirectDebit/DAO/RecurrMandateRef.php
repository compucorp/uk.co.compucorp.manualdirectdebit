<?php

require_once 'CRM/Core/DAO.php';
require_once 'CRM/Utils/Type.php';

class CRM_ManualDirectDebit_DAO_RecurrMandateRef extends CRM_Core_DAO {

  /**
   * Static instance to hold the table name.
   *
   * @var string
   */
  static $_tableName = 'dd_contribution_recurr_mandate_ref';

  /**
   * Static entity name.
   *
   * @var string
   */
  static $entityName = 'RecurrMandateRef';

  /**
   * Should CiviCRM log any modifications to this table in the civicrm_log
   * table.
   *
   * @var boolean
   */
  static $_log = TRUE;

  /**
   * Unique ID
   *
   * @var int unsigned
   */
  public $id;

  /**
   * Recurr id
   *
   * @var int
   */
  public $recurr_id;

  /**
   * Mandate id
   *
   * @var int
   */
  public $mandate_id;

  /**
   * Returns all the column names of this table
   *
   * @return array
   */
  static function &fields() {
    if (!isset(Civi::$statics[__CLASS__]['fields'])) {
      Civi::$statics[__CLASS__]['fields'] = [
        'id' => [
          'name' => 'id',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('id'),
          'description' => 'id',
          'required' => TRUE,
          'import' => TRUE,
          'where' => self::getTableName() . '.id',
          'headerPattern' => '',
          'dataPattern' => '',
          'export' => TRUE,
          'table_name' => self::getTableName(),
          'entity' => self::getEntityName(),
          'bao' => 'CRM_ManualDirectDebit_BAO_RecurrMandateRef',
        ],
        'recurr_id' => [
          'name' => 'recurr_id',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('Reccuring Contribution ID'),
          'description' => 'Reccuring Contribution ID',
          'required' => TRUE,
          'import' => TRUE,
          'where' => self::getTableName() . '.recurr_id',
          'headerPattern' => '',
          'dataPattern' => '',
          'export' => TRUE,
          'table_name' => self::getTableName(),
          'entity' => self::getEntityName(),
          'bao' => 'CRM_ManualDirectDebit_BAO_RecurrMandateRef',
        ],
        'mandate_id' => [
          'name' => 'mandate_id',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('Mandate id'),
          'description' => 'mandate_id',
          'required' => FALSE,
          'import' => FALSE,
          'where' => self::getTableName() . '.mandate_id',
          'headerPattern' => '',
          'dataPattern' => '',
          'export' => TRUE,
          'table_name' => self::getTableName(),
          'entity' => self::getEntityName(),
          'bao' => 'CRM_ManualDirectDebit_BAO_RecurrMandateRef',
        ],
      ];
      CRM_Core_DAO_AllCoreTables::invoke(__CLASS__, 'fields_callback', Civi::$statics[__CLASS__]['fields']);
    }

    return Civi::$statics[__CLASS__]['fields'];
  }

  /**
   * Returns the names of this table
   *
   * @return string
   */
  static function getTableName() {
    return self::$_tableName;
  }

  /**
   * Returns entity name
   *
   * @return string
   */
  static function getEntityName() {
    return self::$entityName;
  }

  /**
   * Returns the list of fields that can be exported
   *
   * @param bool $prefix
   *
   * @return array
   */
  static function &export($prefix = FALSE) {
    $exportedListOfFields = CRM_Core_DAO_AllCoreTables::getExports(__CLASS__, self::getTableName(), $prefix, []);

    return $exportedListOfFields;
  }

  /**
   * Return a mapping from field-name to the corresponding key (as used in
   * fields()).
   *
   * @return array
   *   Array(string $name => string $uniqueName).
   */
  static function &fieldKeys() {
    if (!isset(Civi::$statics[__CLASS__]['fieldKeys'])) {
      Civi::$statics[__CLASS__]['fieldKeys'] = array_flip(CRM_Utils_Array::collect('name', self::fields()));
    }

    return Civi::$statics[__CLASS__]['fieldKeys'];
  }

}
