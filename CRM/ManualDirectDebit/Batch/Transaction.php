<?php

/**
 *
 * This class is used to retrieve and display a range of direct debit mandates
 * that match the given criteria.
 *
 */
class CRM_ManualDirectDebit_Batch_Transaction {

  /**
   * Table name of "Direct Debit Mandate" custom group
   *
   * @var string
   */
  protected $directDebitMandateTable = 'civicrm_value_dd_mandate';

  /**
   * Batch ID
   *
   * @var int
   */
  protected $batchID;

  /**
   * Search element
   *
   * @var boolean
   */
  protected $notPresent;

  /**
   * Limit element
   *
   * @var boolean
   */
  protected $total;

  /**
   * Searchable fields
   *
   * @var array
   */
  protected $searchableFields = [
    'entity_id',
    'bank_name',
    'bank_street_address',
    'bank_city',
    'bank_county',
    'bank_postcode',
    'account_holder_name',
    'ac_number',
    'sort_code',
    'dd_code',
    'dd_ref',
    'start_date',
    'authorisation_date',
    'collection_day',
    'originator_number',
  ];

  /**
   * What column does select in SQL query
   *
   * @code
   *
   *  $returnValues = [
   *    'name' => 'tableName.column as alias',
   *  ]
   *
   * @endcode
   *
   * @var array
   */
  protected $returnValues = [];

  /**
   * What column does select in SQL query
   *
   * @code
   *
   *  $columnHeader = [
   *    'alias' => 'label',
   *  ]
   *
   * @endcode
   *
   * @var array
   */
  protected $columnHeader = [];

  /**
   * Search, order, limits params
   *
   * @var array
   */
  protected $params;

  /**
   * CRM_ManualDirectDebit_Batch_Transaction constructor.
   *
   * @param int $batchID
   * @param array $params
   * @param array $columnHeader
   * @param array $returnValues
   * @param bool $notPresent
   */
  public function __construct($batchID, $params, $columnHeader = [], $returnValues = [], $notPresent = FALSE) {
    $this->batchID = $batchID;
    $this->notPresent = $notPresent;
    $this->params = $params;
    $this->setColumnHeader($columnHeader);
    $this->setReturnValues($returnValues);
  }


  /**
   * Sets columns for rows
   *
   * @param array $columnHeader
   *
   * @return array|bool
   */
  private function setColumnHeader($columnHeader = []) {

    if (empty($columnHeader)) {
      $columnHeader = [
        'contact_id' => ts('ID'),
        'name' => ts('Account Holder Name'),
        'sort_code' => ts('Sort code'),
        'account_number' => ts('Account Number'),
        'reference_number' => ts('Reference Number'),
        'transaction_type' => ts('Transaction Type'),
      ];

    }

    $this->columnHeader = $columnHeader;

    return $this->columnHeader;
  }

  /**
   * Prepares fields for SQL select function
   *
   * @param array $returnValues
   *
   * @return array|bool
   */
  private function setReturnValues($returnValues = []) {
    if (!is_array($returnValues)) {
      return FALSE;
    }

    if (empty($returnValues)) {
      $returnValues = [
        'id' => $this->directDebitMandateTable . '.id as id',
        'contact_id' => $this->directDebitMandateTable . '.entity_id as contact_id',
        'name' => $this->directDebitMandateTable . '.account_holder_name as name',
        'sort_code' => $this->directDebitMandateTable . '.sort_code as sort_code',
        'account_number' => $this->directDebitMandateTable . '.ac_number as account_number',
        'reference_number' => $this->directDebitMandateTable . '.dd_ref as reference_number',
        'transaction_type' => $this->directDebitMandateTable . '.dd_code as transaction_type',
      ];
    }

    $this->returnValues = $returnValues;

    return $this->returnValues;
  }

  /**
   * Gets transaction rows what assign/not assign to batch
   *
   * @return array
   */
  public function getRows() {

    $mandateItems = $this->getDDMandateInstructions();

    $batch = new CRM_ManualDirectDebit_Batch_BatchHandler($this->batchID);
    $ddCodes = CRM_Core_OptionGroup::values('direct_debit_codes');

    $rows = [];
    while ($mandateItems->fetch()) {
      $row = [];
      foreach ($this->columnHeader as $columnKey => $columnValue) {
        if (isset($mandateItems->$columnKey)) {
          if ($columnKey == 'transaction_type') {
            $ddCode = CRM_Utils_Array::value($mandateItems->$columnKey, $ddCodes);
            $row[$columnKey] = $ddCode;
            continue;
          }
          $row[$columnKey] = $mandateItems->$columnKey;
        }
        else {
          $row[$columnKey] = NULL;
        }
      }

      if ($batch->validBatchStatus()) {
        if ($this->notPresent) {
          $js = "enableActions('x')";
          $row['check'] = "<input type='checkbox' id='mark_x_" . $mandateItems->id . "' name='mark_x_" . $mandateItems->id . "' value='1' onclick={$js}></input>";
        }
        else {
          $js = "enableActions('y')";
          $row['check'] = "<input type='checkbox' id='mark_y_" . $mandateItems->id . "' name='mark_y_" . $mandateItems->id . "' value='1' onclick={$js}></input>";
        }
      }
      else {
        $row['check'] = NULL;
      }

      $rows[$mandateItems->id] = $row;
    }

    return $rows;
  }

  /**
   * Returns DAO of Direct Debit mandate instructions
   *
   * @return object
   */
  private function getDDMandateInstructions() {

    $query = CRM_Utils_SQL_Select::from($this->directDebitMandateTable);
    $query->join('entity_batch', 'LEFT JOIN civicrm_entity_batch ON civicrm_entity_batch.entity_id = ' . $this->directDebitMandateTable . '.id AND civicrm_entity_batch.entity_table = \'' . $this->directDebitMandateTable . '\'');
    //select
    $query->select(implode(' , ', $this->returnValues));

    foreach ($this->searchableFields as $field) {
      if (isset($this->params[$field])) {
        if ($field == 'start_date') {
          $query->where($field . ' < @' . $field, [$field => $this->params[$field]]);
          continue;
        }
        $query->where($field . ' = @' . $field, [$field => $this->params[$field]]);
      }
    }

    if ($this->notPresent) {
      $query->where('civicrm_entity_batch.batch_id IS NULL');
    }
    else {
      $query->where('civicrm_entity_batch.batch_id = !entityID', ['entityID' => $this->batchID]);
    }

    if (!empty($this->params['sortBy'])) {
      $query->orderBy($this->params['sortBy']);
    }
    else {
      $query->orderBy($this->directDebitMandateTable . '.id');
    }

    if (!$this->total) {
      if (!empty($this->params['rowCount']) &&
        $this->params['rowCount'] > 0
      ) {
        $query->limit((int) $this->params['rowCount'], (int) $this->params['offset']);
      }
    }

    $mandateItems = CRM_Core_DAO::executeQuery($query->toSQL());
    return $mandateItems;
  }

  /**
   * Gets total rows
   *
   * @return int
   */
  public function getTotalNumber() {
    $this->total = TRUE;
    return count($this->getDDMandateInstructions()
      ->fetchAll());
  }

  /**
   * Adds new properties for SQL select function
   *
   * @param $returnValues
   *
   * @return array
   */
  public function addReturnValues($returnValues) {

    $this->returnValues = array_merge($this->returnValues, $returnValues);

    return $this->returnValues;
  }


  /**
   * Adds new columns for rows
   *
   * @param $columnHeader
   *
   * @return array
   */
  public function addColumnHeader($columnHeader) {

    $this->columnHeader = array_merge($this->columnHeader, $columnHeader);

    return $this->columnHeader;
  }

}
