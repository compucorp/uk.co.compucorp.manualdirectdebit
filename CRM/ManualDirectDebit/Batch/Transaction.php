<?php
use CRM_ManualDirectDebit_Batch_BatchHandler as BatchHandler;

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
   * @var bool
   */
  protected $notPresent;

  /**
   * Limit element
   *
   * @var bool
   */
  protected $total;

  /**
   * Searchable fields
   *
   * @var array
   */
  protected $searchableFields = [];

  /**
   * What column does select in SQL query
   *
   * @var array
   *
   * @code
   *  $returnValues = [
   *    'name' => 'tableName.column as alias',
   *  ]
   * @endcode
   */
  protected $returnValues = [];

  /**
   * What column does select in SQL query
   *
   * @var array
   *
   * @code
   *  $columnHeader = [
   *    'alias' => 'label',
   *  ]
   * @endcode
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

    if (empty($this->params['entityTable'])) {
      $this->params['entityTable'] = self::DD_MANDATE_TABLE;
    }

    $this->setSearchableFields();

    $this->setColumnHeader($columnHeader);
    $this->setReturnValues($returnValues);

    if ($this->params['entityTable'] == self::DD_MANDATE_TABLE) {
      $this->addReturnValues(['amount' => '0.00 as amount']);
    }
  }

  private function setSearchableFields() {
    $this->searchableFields = [
      'entity_id' => [
        'table' => self::DD_MANDATE_TABLE,
        'op' => '=',
        'field' => 'entity_id',
      ],
      'bank_name' => [
        'table' => self::DD_MANDATE_TABLE,
        'op' => '=',
        'field' => 'bank_name',
      ],
      'bank_street_address' => [
        'table' => self::DD_MANDATE_TABLE,
        'op' => '=',
        'field' => 'bank_street_address',
      ],
      'bank_city' => [
        'table' => self::DD_MANDATE_TABLE,
        'op' => '=',
        'field' => 'bank_city',
      ],
      'bank_county' => [
        'table' => self::DD_MANDATE_TABLE,
        'op' => '=',
        'field' => 'bank_county',
      ],
      'bank_postcode' => [
        'table' => self::DD_MANDATE_TABLE,
        'op' => '=',
        'field' => 'bank_postcode',
      ],
      'account_holder_name' => [
        'table' => self::DD_MANDATE_TABLE,
        'op' => '=',
        'field' => 'account_holder_name',
      ],
      'ac_number' => [
        'table' => self::DD_MANDATE_TABLE,
        'op' => '=',
        'field' => 'ac_number',
      ],
      'sort_code' => [
        'table' => self::DD_MANDATE_TABLE,
        'op' => '=',
        'field' => 'sort_code',
      ],
      'dd_code' => [
        'table' => self::DD_MANDATE_TABLE,
        'op' => 'IN',
        'field' => 'dd_code',
      ],
      'dd_ref' => [
        'table' => self::DD_MANDATE_TABLE,
        'op' => '=',
        'field' => 'dd_ref',
      ],
      'start_date' => [
        'table' => self::DD_MANDATE_TABLE,
        'op' => '<=',
        'field' => 'start_date',
      ],
      'authorisation_date' => [
        'table' => self::DD_MANDATE_TABLE,
        'op' => '=',
        'field' => 'authorisation_date',
      ],
      'originator_number' => [
        'table' => self::DD_MANDATE_TABLE,
        'op' => '=',
        'field' => 'originator_number',
      ],
      'contribution_status_id' => [
        'table' => 'civicrm_contribution',
        'op' => 'IN',
        'field' => 'contribution_status_id',
      ],
      'financial_type_id' => [
        'table' => 'civicrm_contribution',
        'op' => 'IN',
        'field' => 'financial_type_id',
      ],
      'contribution_currency_type' => [
        'table' => 'civicrm_contribution',
        'op' => 'IN',
        'field' => 'currency',
      ],
      'contribution_payment_instrument_id' => [
        'table' => 'civicrm_contribution',
        'op' => 'IN',
        'field' => 'payment_instrument_id',
      ],
      'contribution_test' => [
        'table' => 'civicrm_contribution',
        'op' => '=',
        'field' => 'is_test',
      ],
      'contribution_trxn_id' => [
        'table' => 'civicrm_contribution',
        'op' => '=',
        'field' => 'trxn_id',
      ],
      'invoice_number' => [
        'table' => 'civicrm_contribution',
        'op' => '=',
        'field' => 'invoice_number',
      ],
      'contribution_pay_later' => [
        'table' => 'civicrm_contribution',
        'op' => '=',
        'field' => 'is_pay_later',
      ],
      'cancel_reason' => [
        'table' => 'civicrm_contribution',
        'op' => '=',
        'field' => 'cancel_reason',
      ],
      'contribution_source' => [
        'table' => 'civicrm_contribution',
        'op' => '=',
        'field' => 'source',
      ],
      'contribution_page_id' => [
        'table' => 'civicrm_contribution',
        'op' => '=',
        'field' => 'contribution_page_id',
      ],
      'contribution_amount_low' => [
        'table' => 'civicrm_contribution',
        'op' => '>=',
        'field' => 'total_amount',
      ],
      'contribution_amount_high' => [
        'table' => 'civicrm_contribution',
        'op' => '<=',
        'field' => 'total_amount',
      ],
      'recur_status' => [
        'table' => 'civicrm_contribution_recur',
        'op' => 'IN',
        'field' => 'contribution_status_id',
      ],
      'contribution_recur_contribution_status_id' => [
        'table' => 'civicrm_contribution_recur',
        'op' => 'IN',
        'field' => 'contribution_status_id',
      ],
      'contact_tags' => [
        'table' => 'civicrm_entity_tag',
        'op' => 'IN',
        'field' => 'tag_id',
      ],
      'group' => [
        'table' => 'civicrm_group_contact',
        'op' => 'IN',
        'field' => 'group_id',
      ],
    ];
  }

  /**
   * Sets columns for rows
   *
   * @param array $columnHeader
   *
   * @return array
   */
  private function setColumnHeader($columnHeader = []) {
    $batch = (new BatchHandler($this->batchID));
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

      if ($batch->getBatchType() == BatchHandler::BATCH_TYPE_PAYMENTS) {
        $columnHeader['receive_date'] = ts('Received Date');
      }
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
    $batch = (new BatchHandler($this->batchID));
    if (empty($returnValues) || !is_array($returnValues)) {
      $returnValues = [
        'id' => $this->params['entityTable'] . '.id as id',
        'mandate_id' => self::DD_MANDATE_TABLE . '.id as mandate_id',
        'contact_id' => self::DD_MANDATE_TABLE . '.entity_id as contact_id',
        'name' => self::DD_MANDATE_TABLE . '.account_holder_name as name',
        'sort_code' => self::DD_MANDATE_TABLE . '.sort_code as sort_code',
        'account_number' => self::DD_MANDATE_TABLE . '.ac_number as account_number',
        'amount' => 'IF(civicrm_contribution.net_amount IS NOT NULL, civicrm_contribution.net_amount , 0.00) as amount',
        'reference_number' => self::DD_MANDATE_TABLE . '.dd_ref as reference_number',
        'transaction_type' => 'civicrm_option_value.label as transaction_type',
      ];

      if ($batch->getBatchType() == BatchHandler::BATCH_TYPE_PAYMENTS) {
        $returnValues['receive_date'] = 'DATE_FORMAT(civicrm_contribution.receive_date, "%d-%m-%Y") as receive_date';
      }
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
    $batch = (new BatchHandler($this->batchID));
    return $this->getBatchRows($batch);
  }

  /**
   * Gets prepared data about previously serialized mandates
   *
   * @param $mandateData
   * @param $batch
   *
   * @return array
   */
  private function getSavedRows($mandateData, $batch) {
    $rows = [];

    foreach ($mandateData['values']['mandates'] as $mandateId => $mandateValue) {
      $row = [];
      foreach ($mandateValue as $key => $value) {
        $row[$key] = $value;
      }

      $row['check'] = $this->getCheckRow($batch, $mandateId);

      if (!empty($mandateValue['contact_id'])) {
        switch ($batch->getBatchType()) {
          case BatchHandler::BATCH_TYPE_INSTRUCTIONS:
            $row['action'] = $this->getLinkToMandate($mandateId, $mandateValue['contact_id']);
            break;

          case BatchHandler::BATCH_TYPE_PAYMENTS:
            $row['action'] = $this->getLinkToContribution($mandateValue['contribute_id'], $mandateValue['contact_id']);
            break;
        }
      }

      $rows[$mandateId] = $row;
    }

    if (!$this->total) {
      if (!empty($this->params['rowCount']) &&
        $this->params['rowCount'] > 0
      ) {
        $rows = array_slice($rows, (int) $this->params['offset'], (int) $this->params['rowCount']);
      }
    }

    return $rows;
  }

  /**
   * Gets prepared data about appropriate mandates
   *
   * @param $batch
   *
   * @return array
   */
  private function getBatchRows($batch) {
    if ($batch->getBatchType() === BatchHandler::BATCH_TYPE_PAYMENTS) {
      $items = $this->getDDPayments();
    }
    else {
      $items = $this->getDDMandateInstructions();
    }

    $rows = [];
    foreach ($items as $item) {
      $row = [];
      foreach ($this->columnHeader as $columnKey => $columnValue) {
        if (isset($item[$columnKey])) {
          $row[$columnKey] = $item[$columnKey];
        }
        else {
          $row[$columnKey] = NULL;
        }
      }

      $row['check'] = $this->getCheckRow($batch, $item['id']);

      switch ($batch->getBatchType()) {
        case BatchHandler::BATCH_TYPE_INSTRUCTIONS:
        case BatchHandler::BATCH_TYPE_CANCELLATIONS:
          if (!empty($item['contact_id'])) {
            $row['action'] = $this->getLinkToMandate($item['id'], $item['contact_id']);
          }

          $rows[$item['mandate_id']] = $row;
          break;

        case BatchHandler::BATCH_TYPE_PAYMENTS:
          if (isset($item['contribute_id'])) {
            $contributionId = $item['contribute_id'];
          }
          else {
            $contributionId = $item['id'];
          }

          if (!empty($item['contact_id'])) {
            $row['action'] = $this->getLinkToContribution($contributionId, $item['contact_id']);
          }

          $rows[$contributionId] = $row;
          break;
      }
    }

    return $rows;
  }

  /**
   * Gets check row
   *
   * @param $batch
   * @param $mandateId
   *
   * @return string|null
   */
  private function getCheckRow($batch, $mandateId) {
    $rowCheck = NULL;

    if ($batch->validBatchStatus()) {
      if ($this->notPresent) {
        $js = "enableActions('x')";
        $rowCheck = "<input type='checkbox' id='mark_x_" . $mandateId . "' name='mark_x_" . $mandateId . "' value='1' onclick={$js}></input>";
      }
      else {
        $js = "enableActions('y')";
        $rowCheck = "<input type='checkbox' id='mark_y_" . $mandateId . "' name='mark_y_" . $mandateId . "' value='1' onclick={$js}></input>";
      }
    }

    return $rowCheck;
  }

  /**
   * Returns array of rows.
   *
   * @param CRM_Core_DAO $dao
   * @return array
   */
  private function fetchRows($dao) {
    $rows = [];
    while ($dao->fetch()) {
      $row = [];
      foreach ($this->returnValues as $key => $value) {
        if (isset($dao->$key)) {
          $row[$key] = $dao->$key;
        }
        else {
          $row[$key] = NULL;
        }
      }

      $row['amount'] = $this->formatAmount($row['amount']);

      $rows[] = $row;
    }

    return $rows;
  }

  /**
   * Returns array of Direct Debit mandate instructions
   *
   * @return array
   */
  public function getDDMandateInstructions() {
    $query = $this->getDDMandateInstructionsQuery();
    $dao = CRM_Core_DAO::executeQuery($query);
    return $this->fetchRows($dao);
  }

  /**
   * Returns a query for Direct Debit mandate instructions
   *
   * @return array
   */
  private function getDDMandateInstructionsQuery() {
    $query = CRM_Utils_SQL_Select::from(self::DD_MANDATE_TABLE);
    $query->join('contact', 'INNER JOIN civicrm_contact ON ' . self::DD_MANDATE_TABLE . '.entity_id = civicrm_contact.id');
    $query->join('civicrm_option_group', 'INNER JOIN civicrm_option_group ON civicrm_option_group.name = "direct_debit_codes"');
    $query->join('civicrm_option_value', 'INNER JOIN civicrm_option_value ON civicrm_option_group.id = civicrm_option_value.option_group_id AND civicrm_option_value.value = ' . self::DD_MANDATE_TABLE . '.dd_code');
    $query->where('civicrm_contact.is_deleted IS NULL OR civicrm_contact.is_deleted = 0');

    if ($this->notPresent) {
      $batchStatus = CRM_Core_PseudoConstant::get('CRM_Batch_DAO_Batch', 'status_id', ['labelColumn' => 'name']);
      $excluded = CRM_Utils_SQL_Select::from(self::DD_MANDATE_TABLE);
      $excluded->select($this->params['entityTable'] . '.id');
      $excluded->join('entity_batch', 'INNER JOIN civicrm_entity_batch ON civicrm_entity_batch.entity_id = ' . $this->params['entityTable'] . '.id AND civicrm_entity_batch.entity_table = \'' . $this->params['entityTable'] . '\'');
      $excluded->join('batch', 'INNER JOIN civicrm_batch ON civicrm_entity_batch.batch_id = civicrm_batch.id');
      $excluded->join('current_batch', 'INNER JOIN civicrm_batch current_batch ON current_batch.id = ' . $this->batchID);
      $excluded->where('civicrm_batch.status_id <> ' . CRM_Utils_Array::key('Discarded', $batchStatus));
      $excluded->where('civicrm_batch.type_id = current_batch.type_id');

      $query->where($this->params['entityTable'] . '.id NOT IN (' . $excluded->toSQL() . ')');
    }
    else {
      $query->join('entity_batch', 'INNER JOIN civicrm_entity_batch ON civicrm_entity_batch.entity_id = ' . self::DD_MANDATE_TABLE . '.id AND civicrm_entity_batch.entity_table = \'' . self::DD_MANDATE_TABLE . '\'');
      $query->where('civicrm_entity_batch.batch_id = !entityID', ['entityID' => $this->batchID]);
    }

    //select
    $query->select(implode(' , ', $this->returnValues));

    foreach ($this->searchableFields as $k => $field) {
      if (isset($this->params[$k])) {
        if ($field['op'] == 'IN') {
          $query->where("{$field['table']}.{$field['field']} {$field['op']} (@{$k})", [$k => explode(',', $this->params[$k])]);
        }
        else {
          $query->where("{$field['table']}.{$field['field']} {$field['op']} @{$k}", [$k => $this->params[$k]]);
        }
      }
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

    return $query->toSQL();
  }

  /**
   * Returns array of Direct Debit payments
   *
   * @return array
   */
  public function getDDPayments() {
    $query = $this->getDDPaymentsQuery();
    $dao = CRM_Core_DAO::executeQuery($query);
    return $this->fetchRows($dao);
  }

  /**
   * Returns a query for Direct Debit payments
   *
   * @return array
   */
  private function getDDPaymentsQuery() {
    $query = CRM_Utils_SQL_Select::from(self::DD_MANDATE_TABLE);
    $query->join('value_dd_information', 'INNER JOIN civicrm_value_dd_information ON civicrm_value_dd_information.mandate_id = civicrm_value_dd_mandate.id');
    $query->join('contribution', 'INNER JOIN civicrm_contribution ON civicrm_contribution.id = civicrm_value_dd_information.entity_id');
    $query->join('contact', 'INNER JOIN civicrm_contact ON civicrm_contribution.contact_id = civicrm_contact.id');
    $query->join('contribution_recur', 'INNER JOIN civicrm_contribution_recur ON civicrm_contribution.contribution_recur_id = civicrm_contribution_recur.id');
    $query->join('civicrm_option_group', 'INNER JOIN civicrm_option_group ON civicrm_option_group.name = "direct_debit_codes"');
    $query->join('civicrm_option_value', 'INNER JOIN civicrm_option_value ON civicrm_option_group.id = civicrm_option_value.option_group_id AND civicrm_option_value.value = ' . self::DD_MANDATE_TABLE . '.dd_code');

    //select
    $query->select(implode(' , ', $this->returnValues));

    $query->distinct(TRUE);

    foreach ($this->searchableFields as $k => $field) {
      if (!isset($this->params[$k])) {
        continue;
      }
      if ($field['table'] === 'civicrm_entity_tag') {
        $query->join('civicrm_entity_tag', 'INNER JOIN civicrm_entity_tag ON civicrm_entity_tag.entity_id = civicrm_contact.id AND civicrm_entity_tag.entity_table = \'civicrm_contact\'');
      }
      if ($field['table'] === 'civicrm_group_contact') {
        $query->join('civicrm_group_contact', 'INNER JOIN civicrm_group_contact ON civicrm_group_contact.contact_id = civicrm_contact.id AND civicrm_group_contact.status = \'Added\'');
      }

      if ($field['op'] == 'IN') {
        $query->where("{$field['table']}.{$field['field']} {$field['op']} (@{$k})", [$k => explode(',', $this->params[$k])]);
      }
      else {
        $query->where("{$field['table']}.{$field['field']} {$field['op']} @{$k}", [$k => $this->params[$k]]);
      }
    }

    $this->addContributionReceiveDateCondition($query);
    $this->addSortNameCondition($query);

    if ($this->notPresent) {
      $batchStatus = CRM_Core_PseudoConstant::get('CRM_Batch_DAO_Batch', 'status_id', ['labelColumn' => 'name']);
      $excluded = CRM_Utils_SQL_Select::from(self::DD_MANDATE_TABLE);
      $excluded->select($this->params['entityTable'] . '.id');

      if ($this->params['entityTable'] == 'civicrm_contribution') {
        $excluded->join('value_dd_information', 'INNER JOIN civicrm_value_dd_information ON civicrm_value_dd_information.mandate_id = civicrm_value_dd_mandate.id');
        $excluded->join('contribution', 'INNER JOIN civicrm_contribution ON civicrm_contribution.id = civicrm_value_dd_information.entity_id');
      }
      $excluded->join('entity_batch', 'INNER JOIN civicrm_entity_batch ON civicrm_entity_batch.entity_id = ' . $this->params['entityTable'] . '.id AND civicrm_entity_batch.entity_table = \'' . $this->params['entityTable'] . '\'');
      $excluded->join('batch', 'INNER JOIN civicrm_batch ON civicrm_entity_batch.batch_id = civicrm_batch.id');
      $excluded->join('current_batch', 'INNER JOIN civicrm_batch current_batch ON current_batch.id = ' . $this->batchID);
      $excluded->where('civicrm_batch.status_id <> ' . CRM_Utils_Array::key('Discarded', $batchStatus));
      $excluded->where('civicrm_batch.type_id = current_batch.type_id');

      $query->where($this->params['entityTable'] . '.id NOT IN (' . $excluded->toSQL() . ')');
    }
    else {
      $query->join('entity_batch', 'INNER JOIN civicrm_entity_batch ON civicrm_entity_batch.entity_id = ' . $this->params['entityTable'] . '.id AND civicrm_entity_batch.entity_table = \'' . $this->params['entityTable'] . '\'');
      $query->where('civicrm_entity_batch.batch_id = !entityID', ['entityID' => $this->batchID]);
    }

    if (!empty($this->params['sortBy'])) {
      $query->orderBy($this->params['sortBy']);
    }
    else {
      $query->orderBy('civicrm_contribution.id');
    }

    if (!$this->total) {
      if (!empty($this->params['rowCount']) &&
        $this->params['rowCount'] > 0
      ) {
        $query->limit((int) $this->params['rowCount'], (int) $this->params['offset']);
      }
    }

    $query->where('civicrm_contact.is_deleted IS NULL OR civicrm_contact.is_deleted = 0');
    $query->where('civicrm_contribution.is_test IS NULL OR civicrm_contribution.is_test = 0');

    return $query->toSQL();
  }

  private function formatAmount($amount) {
    $decimalPoints = 2;
    $roundedAmount = (float) round($amount, $decimalPoints);
    return number_format($roundedAmount, $decimalPoints);
  }

  /**
   * Gets total rows
   *
   * @return int
   */
  public function getTotalNumber() {
    $this->total = TRUE;

    $batch = (new BatchHandler($this->batchID));
    if ($batch->getBatchType() === BatchHandler::BATCH_TYPE_PAYMENTS) {
      $query = $this->getDDPaymentsQuery();
    }
    else {
      $query = $this->getDDMandateInstructionsQuery();
    }
    $dao = CRM_Core_DAO::executeQuery($query);

    return $dao->N;
  }

  /**
   * Adds new properties for SQL select function
   *
   * @param array $returnValues
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
   * @param array $columnHeader
   *
   * @return array
   */
  public function addColumnHeader($columnHeader) {

    $this->columnHeader = array_merge($this->columnHeader, $columnHeader);

    return $this->columnHeader;
  }

  /**
   * Gets link to mandate
   *
   * @param $mandateID
   * @param $contactId
   *
   * @return string|null
   */
  private function getLinkToMandate($mandateID, $contactId) {
    $mandateCustomGroupId = CRM_ManualDirectDebit_Common_DirectDebitDataProvider::getGroupIDByName('direct_debit_mandate');
    $linkToMandate = CRM_Core_Action::formLink(
      [
        'view' => [
          'name' => ts('View'),
          'title' => ts('View Mandate'),
          'url' => "civicrm/contact/view/cd",
          'qs' => 'reset=1&type=Individual&gid=%%mandate_custom_group_id%%&cid=%%contact_id%%&multiRecordDisplay=single&mode=view&recId=%%mandate_id%%',
        ],
      ],
      NULL,
      [
        'contact_id' => $contactId,
        'mandate_custom_group_id' => $mandateCustomGroupId,
        'mandate_id' => $mandateID,
      ]
    );

    return $linkToMandate;
  }

  /**
   * Gets link to contribution
   *
   * @param $contributionId
   * @param $contactId
   *
   * @return string
   */
  private function getLinkToContribution($contributionId, $contactId) {

    $linkToRecurringContribution = CRM_Core_Action::formLink(
      [
        'view' => [
          'name' => ts('View'),
          'title' => ts('View Contribution'),
          'url' => "civicrm/contact/view",
          'qs' => "reset=1&openContribution=%%contribution_id%%&cid=%%contact_id%%&action=view&context=contribution&selectedChild=contribute",
        ],
      ],
      NULL,
      [
        'contribution_id' => $contributionId,
        'contact_id' => $contactId,
      ]
    );

    return strtr($linkToRecurringContribution, ['action-item' => '']);
  }

  /**
   * Add query where condition as per relative receive date.
   *
   * @param $query
   */
  private function addContributionReceiveDateCondition(&$query) {
    if (!empty($this->params['receive_date_relative'])) {
      $relativeDate = explode('.', $this->params['receive_date_relative']);
      $date = CRM_Utils_Date::relativeToAbsolute($relativeDate[0], $relativeDate[1]);
      $query->where('DATE_FORMAT(civicrm_contribution.receive_date, "%Y%m%d") >= @receive_date_start', ['receive_date_start' => date('Ymd', strtotime($date['from']))]);
      $query->where('DATE_FORMAT(civicrm_contribution.receive_date, "%Y%m%d") <= @receive_date_end', ['receive_date_end' => date('Ymd', strtotime($date['to']))]);
    }
    if (!empty($this->params['receive_date_low'])) {
      $query->where('DATE_FORMAT(civicrm_contribution.receive_date, "%Y%m%d") >= @receive_date_start',
                     ['receive_date_start' => date('Ymd', strtotime($this->params['receive_date_low']))]
                   );
    }
    if (!empty($this->params['receive_date_high'])) {
      $query->where('DATE_FORMAT(civicrm_contribution.receive_date, "%Y%m%d") <= @receive_date_end',
                     ['receive_date_end' => date('Ymd', strtotime($this->params['receive_date_high']))]
                   );
    }
  }

  /**
   * Add query where condition for contact name/email.
   *
   * @param $query
   */
  private function addSortNameCondition(&$query) {
    if (!empty($this->params['sort_name'])) {
      $sort_name = $this->params['sort_name'];
      if (mb_strpos($sort_name, '%') === FALSE) {
        $sort_name = "%{$sort_name}%";
      }
      $query->join('email', 'LEFT JOIN civicrm_email ON (civicrm_contact.id = civicrm_email.contact_id AND civicrm_email.is_primary = 1)');
      $query->where('civicrm_contact.sort_name LIKE @sort_name OR civicrm_contact.nick_name LIKE @sort_name OR civicrm_email.email LIKE @sort_name', ['sort_name' => $sort_name]);
    }
  }

}
