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
  protected $searchableFields = [];

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
      'contribution_status' => [
        'op' => 'IN',
        'field' => 'civicrm_contribution.contribution_status_id',
      ],
      'recur_status' => [
        'op' => 'IN',
        'field' => 'civicrm_contribution_recur.contribution_status_id',
      ],
      'financial_type_id' => [
        'op' => 'IN',
        'field' => 'civicrm_contribution.financial_type_id',
      ],
      'contribution_currency_type' => [
        'op' => 'IN',
        'field' => 'civicrm_contribution.currency',
      ],
      'contribution_payment_instrument_id' => [
        'op' => 'IN',
        'field' => 'civicrm_contribution.payment_instrument_id',
      ],
      'contribution_test' => [
        'op' => '=',
        'field' => 'civicrm_contribution.is_test',
      ],
      'contribution_trxn_id' => [
        'op' => '=',
        'field' => 'civicrm_contribution.trxn_id',
      ],
      'invoice_number' => [
        'op' => '=',
        'field' => 'civicrm_contribution.invoice_number',
      ],
      'contribution_pay_later' => [
        'op' => '=',
        'field' => 'civicrm_contribution.is_pay_later',
      ],
      'cancel_reason' => [
        'op' => '=',
        'field' => 'civicrm_contribution.cancel_reason',
      ],
      'contribution_source' => [
        'op' => '=',
        'field' => 'civicrm_contribution.source',
      ],
      'contribution_page_id' => [
        'op' => '=',
        'field' => 'civicrm_contribution.contribution_page_id',
      ],
      'contribution_recur_contribution_status_id' => [
        'op' => 'IN',
        'field' => 'civicrm_contribution_recur.contribution_status_id',
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

      if($batch->getBatchType() == 'dd_payments') {
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

      if($batch->getBatchType() == 'dd_payments') {
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
          case "instructions_batch":
            $row['action'] = $this->getLinkToMandate($mandateValue['contact_id']);
            break;

          case "dd_payments":
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
    $mandateItems = $this->getDDMandateInstructions();

    $rows = [];
    foreach ($mandateItems as $mandateItem) {
      $row = [];
      foreach ($this->columnHeader as $columnKey => $columnValue) {
        if (isset($mandateItem[$columnKey])) {
          $row[$columnKey] = $mandateItem[$columnKey];
        }
        else {
          $row[$columnKey] = NULL;
        }
      }

      $row['check'] = $this->getCheckRow($batch, $mandateItem['id']);

      switch ($batch->getBatchType()) {
        case BatchHandler::BATCH_TYPE_INSTRUCTIONS:
        case BatchHandler::BATCH_TYPE_CANCELLATIONS:
          if (!empty($mandateItem['contact_id'])) {
            $row['action'] = $this->getLinkToMandate($mandateItem['contact_id']);
          }

          $rows[$mandateItem['mandate_id']] = $row;
          break;

        case BatchHandler::BATCH_TYPE_PAYMENTS:
          if (isset($mandateItem['contribute_id'])) {
            $contributionId = $mandateItem['contribute_id'];
          }
          else {
            $contributionId = $mandateItem['id'];
          }

          if (!empty($mandateItem['contact_id'])) {
            $row['action'] = $this->getLinkToContribution($contributionId, $mandateItem['contact_id']);
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
   * Returns array of Direct Debit mandate instructions
   *
   * @return array
   */
  public function getDDMandateInstructions() {

    $query = CRM_Utils_SQL_Select::from(self::DD_MANDATE_TABLE);
    $query->join('value_dd_information', 'LEFT JOIN civicrm_value_dd_information ON civicrm_value_dd_information.mandate_id = civicrm_value_dd_mandate.id');
    $query->join('contribution', 'LEFT JOIN civicrm_contribution ON civicrm_contribution.id = civicrm_value_dd_information.entity_id');
    $query->join('contact', 'LEFT JOIN civicrm_contact ON civicrm_contribution.contact_id = civicrm_contact.id');
    $query->join('contribution_recur', 'LEFT JOIN civicrm_contribution_recur ON civicrm_contribution.contribution_recur_id = civicrm_contribution_recur.id');
    $query->join('entity_batch', 'LEFT JOIN civicrm_entity_batch ON civicrm_entity_batch.entity_id = ' . $this->params['entityTable'] . '.id AND civicrm_entity_batch.entity_table = \'' . $this->params['entityTable'] . '\'');
    $query->join('civicrm_option_group', 'LEFT JOIN civicrm_option_group ON civicrm_option_group.name = "direct_debit_codes"');
    $query->join('civicrm_option_value', 'LEFT JOIN civicrm_option_value ON civicrm_option_group.id = civicrm_option_value.option_group_id AND civicrm_option_value.value = ' . self::DD_MANDATE_TABLE . '.dd_code');

    //select
    $query->select(implode(' , ', $this->returnValues));

    $query->distinct(TRUE);

    foreach ($this->searchableFields as $k => $field) {
      if (isset($this->params[$k])) {
        if ($field['op'] == 'IN') {
          $query->where($field['field'] . ' ' . $field['op'] . ' (@' . $k . ')', [$k => explode(',', $this->params[$k])]);
        }
        else {
          $query->where($field['field'] . ' ' . $field['op'] . ' @' . $k, [$k => $this->params[$k]]);
        }
      }
    }

    $this->addContributionReceiveDateCondition($query);

    $this->addContributionCancelDateCondition($query);


    if ($this->notPresent) {
      $batchStatus = CRM_Core_PseudoConstant::get('CRM_Batch_DAO_Batch', 'status_id', ['labelColumn' => 'name']);
      $excluded = CRM_Utils_SQL_Select::from(self::DD_MANDATE_TABLE);
      $excluded->select($this->params['entityTable'] . '.id');

      if ($this->params['entityTable'] == 'civicrm_contribution') {
        $excluded->join('value_dd_information', 'LEFT JOIN civicrm_value_dd_information ON civicrm_value_dd_information.mandate_id = civicrm_value_dd_mandate.id');
        $excluded->join('contribution', 'LEFT JOIN civicrm_contribution ON civicrm_contribution.id = civicrm_value_dd_information.entity_id');
      }
      $excluded->join('entity_batch', 'LEFT JOIN civicrm_entity_batch ON civicrm_entity_batch.entity_id = ' . $this->params['entityTable'] . '.id AND civicrm_entity_batch.entity_table = \'' . $this->params['entityTable'] . '\'');
      $excluded->join('batch', 'LEFT JOIN civicrm_batch ON civicrm_entity_batch.batch_id = civicrm_batch.id');
      $excluded->where('civicrm_batch.status_id <> ' . CRM_Utils_Array::key('Discarded', $batchStatus));

      $query->where($this->params['entityTable'] . '.id NOT IN (' . $excluded->toSQL() . ')');
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

    $query->where('civicrm_contact.is_deleted IS NULL OR civicrm_contact.is_deleted = 0');
    $query->where('civicrm_contribution.is_test IS NULL OR civicrm_contribution.is_test = 0');

    $mandateItems = CRM_Core_DAO::executeQuery($query->toSQL());

    $rows = [];
    while($mandateItems->fetch()) {
      $mandateItem = [];
      foreach ($this->returnValues as $key => $value) {
        if (isset($mandateItems->$key)) {
          $mandateItem[$key] = $mandateItems->$key;
        }
        else {
          $mandateItem[$key] = NULL;
        }
      }

      $mandateItem['amount'] = $this->formatAmount($mandateItem['amount']);

      $rows[] = $mandateItem;
    }

    return $rows;
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

    return count($this->getDDMandateInstructions());
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
   * @param $contactId
   *
   * @return string
   */
  private function getLinkToMandate($contactId) {
    $mandateCustomGroupId = CRM_ManualDirectDebit_Common_DirectDebitDataProvider::getGroupIDByName('direct_debit_mandate');
    $linkToMandate = CRM_Core_Action::formLink(
      [
        'view' => [
          'name' => ts('View'),
          'title' => ts('View Mandate'),
          'url' => "civicrm/contact/view/cd",
          'qs' => "reset=1&cid=%%contact_id%%&selectedChild=custom_%%mandate_custom_group_id%%&gid=%%mandate_custom_group_id%%",
        ],
      ],
      NULL,
      [
        'contact_id' => $contactId,
        'mandate_custom_group_id' => $mandateCustomGroupId,
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
          'url' => "civicrm/contact/view/contribution",
          'qs' => "reset=1&id=%%contribution_id%%&cid=%%contact_id%%&action=view&context=contribution&selectedChild=contribute",
        ],
      ],
      NULL,
      [
        'contribution_id' => $contributionId,
        'contact_id' => $contactId,
      ]
    );

    return $linkToRecurringContribution;
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
    if(!empty($this->params['receive_date_high'])) {
      $query->where('DATE_FORMAT(civicrm_contribution.receive_date, "%Y%m%d") <= @receive_date_end',
                     ['receive_date_end' => date('Ymd', strtotime($this->params['receive_date_high']))]
                   );
    }
  }

  /**
   * Add query where condition as per relative cancel date.
   *
   * @param $query
   */
  private function addContributionCancelDateCondition(&$query) {
    if (!empty($this->params['contribution_cancel_date_relative'])) {
      $relativeDate = explode('.', $this->params['contribution_cancel_date_relative']);
      $date = CRM_Utils_Date::relativeToAbsolute($relativeDate[0], $relativeDate[1]);
      $query->where('civicrm_contribution.cancel_date >= @cancel_date_start', ['cancel_date_start' => $date['from']]);
      $query->where('civicrm_contribution.cancel_date <= @cancel_date_end', ['cancel_date_end' => $date['to']]);
    }
    if (!empty($this->params['contribution_cancel_date_low'])) {
      $query->where('civicrm_contribution.cancel_date >= @cancel_date_start',
                     ['cancel_date_start' => date('Ymd', strtotime($this->params['contribution_cancel_date_low']))]
                   );
    }
    if(!empty(!empty($this->params['contribution_cancel_date_high']))) {
      $query->where('civicrm_contribution.cancel_date <= @cancel_date_end',
                     ['cancel_date_end' => date('Ymd', strtotime($this->params['contribution_cancel_date_high']))]
                   );
    }
  }

}
