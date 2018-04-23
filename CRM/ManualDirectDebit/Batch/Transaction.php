<?php

/**
 * This class is used to retrieve and display a range of direct debit mandates
 * that match the given criteria.
 */
class CRM_ManualDirectDebit_Batch_Transaction {

  /**
   * Table name of "Direct Debit Mandate" custom group
   *
   * @var string
   */
  const DD_MANDATE_TABLE = 'civicrm_value_dd_mandate';

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
    'entity_id' => [
      'op' => '=',
      'field' => self::DD_MANDATE_TABLE . '.entity_id',
    ],
    'bank_name' => [
      'op' => '=',
      'field' => self::DD_MANDATE_TABLE . '.bank_name',
    ],
    'bank_street_address' => [
      'op' => '=',
      'field' => self::DD_MANDATE_TABLE . '.bank_street_address',
    ],
    'bank_city' => [
      'op' => '=',
      'field' => self::DD_MANDATE_TABLE . '.bank_city',
    ],
    'bank_county' => [
      'op' => '=',
      'field' => self::DD_MANDATE_TABLE . '.bank_county',
    ],
    'bank_postcode' => [
      'op' => '=',
      'field' => self::DD_MANDATE_TABLE . '.bank_postcode',
    ],
    'account_holder_name' => [
      'op' => '=',
      'field' => self::DD_MANDATE_TABLE . '.account_holder_name',
    ],
    'ac_number' => [
      'op' => '=',
      'field' => self::DD_MANDATE_TABLE . '.ac_number',
    ],
    'sort_code' => [
      'op' => '=',
      'field' => self::DD_MANDATE_TABLE . '.sort_code',
    ],
    'dd_code' => [
      'op' => 'IN',
      'field' => self::DD_MANDATE_TABLE . '.dd_code',
    ],
    'dd_ref' => [
      'op' => '=',
      'field' => self::DD_MANDATE_TABLE . '.dd_ref',
    ],
    'start_date' => [
      'op' => '<=',
      'field' => self::DD_MANDATE_TABLE . '.start_date',
    ],
    'authorisation_date' => [
      'op' => '=',
      'field' => self::DD_MANDATE_TABLE . '.authorisation_date',
    ],
    'collection_day' => [
      'op' => '=',
      'field' => self::DD_MANDATE_TABLE . '.collection_day',
    ],
    'originator_number' => [
      'op' => '=',
      'field' => self::DD_MANDATE_TABLE . '.originator_number',
    ],
    'payment_instrument' => [
      'op' => 'IN',
      'field' => 'civicrm_contribution.payment_instrument_id',
    ],
    'contribution_status' => [
      'op' => 'IN',
      'field' => 'civicrm_contribution.contribution_status_id',
    ],
    'receive_date' => [
      'op' => '<=',
      'field' => 'civicrm_contribution.receive_date',
    ],
    'recur_status' => [
      'op' => 'IN',
      'field' => 'civicrm_contribution_recur.contribution_status_id',
    ],
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
   *    Parameters for SQL query (WHERE, ORDER BY, LIMIT)
   * @param array $columnHeader
   *    What should the result set include (web/email/csv)
   * @param array $returnValues
   *    List of columns returned by SQL query
   * @param bool $notPresent
   *    Batch ID not presents in civicrm_entity_batch table.
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
   * @return array
   */
  private function setColumnHeader($columnHeader = []) {

    if (empty($columnHeader)) {
      $columnHeader = [
        'contact_id' => ts('ID'),
        'name' => ts('Account Holder Name'),
        'sort_code' => ts('Sort code'),
        'account_number' => ts('Account Number'),
        'amount' => ts('Amount'),
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
   * @return array
   */
  private function setReturnValues($returnValues = []) {
    if (empty($returnValues) || !is_array($returnValues)) {
      $returnValues = [
        'id' => self::DD_MANDATE_TABLE . '.id as id',
        'contact_id' => self::DD_MANDATE_TABLE . '.entity_id as contact_id',
        'name' => self::DD_MANDATE_TABLE . '.account_holder_name as name',
        'sort_code' => self::DD_MANDATE_TABLE . '.sort_code as sort_code',
        'account_number' => self::DD_MANDATE_TABLE . '.ac_number as account_number',
        'amount' => 'IF(civicrm_contribution.net_amount IS NOT NULL, civicrm_contribution.net_amount , 0) as amount',
        'reference_number' => self::DD_MANDATE_TABLE . '.dd_ref as reference_number',
        'transaction_type' => 'civicrm_option_value.label as transaction_type',
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
   * @return \CRM_Core_DAO
   */
  public function getDDMandateInstructions() {

    $query = CRM_Utils_SQL_Select::from(self::DD_MANDATE_TABLE);
    $query->join('value_dd_information', 'LEFT JOIN civicrm_value_dd_information ON civicrm_value_dd_information.mandate_id = civicrm_value_dd_mandate.id');
    $query->join('contribution', 'LEFT JOIN civicrm_contribution ON civicrm_contribution.id = civicrm_value_dd_information.entity_id');
    $query->join('contribution_recur', 'LEFT JOIN civicrm_contribution_recur ON civicrm_contribution.contribution_recur_id = civicrm_contribution_recur.id');
    $query->join('entity_batch', 'LEFT JOIN civicrm_entity_batch ON civicrm_entity_batch.entity_id = ' . $this->params['entityTable'] . '.id AND civicrm_entity_batch.entity_table = \'' . $this->params['entityTable'] . '\'');
    $query->join('civicrm_option_group', 'LEFT JOIN civicrm_option_group ON civicrm_option_group.name = "direct_debit_codes"');
    $query->join('civicrm_option_value', 'LEFT JOIN civicrm_option_value ON civicrm_option_group.id = civicrm_option_value.option_group_id AND civicrm_option_value.value = ' . $this->params['entityTable'] . '.dd_code');

    //select
    $query->select(implode(' , ', $this->returnValues));

    foreach ($this->searchableFields as $k => $field) {
      if (isset($this->params[$k])) {
        if($field['op'] == 'IN'){
          $query->where($field['field'] . ' ' . $field['op'] . ' (@' . $k . ')', [$k => explode(',', $this->params[$k])]);
        } else {
          $query->where($field['field'] . ' ' . $field['op'] . ' @' . $k, [$k => $this->params[$k]]);
        }
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
      $query->orderBy(self::DD_MANDATE_TABLE . '.id');
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
