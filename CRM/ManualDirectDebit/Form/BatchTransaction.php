<?php


/**
 * This class generates form components for Batch Transaction
 */
class CRM_ManualDirectDebit_Form_BatchTransaction extends CRM_Contribute_Form {

  /**
   * The action links that we need to display for the browse screen.
   *
   * @var array
   */
  protected $links = NULL;

  /**
   * Batch id.
   *
   * @var integer
   */
  protected $batchID;

  /**
   * Batch.
   *
   * @var object
   */
  protected $batch;

  /**
   * PreProcess function.
   */
  public function preProcess() {
    $this->batchID = CRM_Utils_Request::retrieve('bid', 'Positive') ? CRM_Utils_Request::retrieve('bid', 'Positive') : CRM_Utils_Array::value('batch_id', $_POST);
    $this->assign('entityID', $this->batchID);
    if (isset($this->batchID)) {
      $batch = new CRM_ManualDirectDebit_Batch_BatchHandler($this->batchID);
      $this->batch = $batch->getBatch();
      $this->assign('statusID', $batch->getBatchStatusId());
      $this->assign('batchStatus', $batch->getBatchStatus());
      $this->assign('validStatus', $batch->validBatchStatus());
      $columnHeaders = [
        'created_by' => ts('Created By'),
        'status' => ts('Status'),
        'description' => ts('Description'),
        'payment_instrument' => ts('Payment Method'),
        'item_count' => ts('Expected Number of Items'),
        'assigned_item_count' => ts('Actual Number of Items'),
        'total' => ts('Expected Total Amount'),
        'assigned_total' => ts('Actual Total Amount'),
        'opened_date' => ts('Opened'),
      ];
      $this->assign('columnHeaders', $columnHeaders);

      $this->_values = civicrm_api3('Batch', 'getSingle', ['id' => $this->batchID]);
      $batchType = CRM_Core_OptionGroup::getRowValues('batch_type', $this->_values['type_id'], 'value', 'String', FALSE);
      CRM_Utils_System::setTitle($batchType['label']);

      $this->assignSearchProperties($batchType);
    }

    $customGroup = civicrm_api3('CustomGroup', 'getvalue', [
      'return' => "id",
      'name' => "direct_debit_mandate",
    ]);

    $this->assign('customGroup', $customGroup);
  }

  /**
   * Assigns properties for transaction
   *
   * @param array $batchType
   */
  private function assignSearchProperties($batchType) {
    $searchData = [];
    if ($batchType['name'] == 'instructions_batch') {
      $searchData = $this->assignInstructionsSearchProperties();
    }

    if ($batchType['name'] == 'dd_payments') {
      $searchData = $this->assignDDPaymentsSearchProperties();
    }

    $this->assign('searchData', json_encode($searchData));
    $this->assign('batchType_id', $batchType['value']);
  }

  /**
   * Assigns instruction batch properties for transaction
   */
  private function assignInstructionsSearchProperties() {
    $ddCodes = CRM_Core_OptionGroup::values('direct_debit_codes');
    $batchData = json_decode($this->_values['data'], TRUE);

    $this->addElement('hidden', 'entityTable', 'civicrm_value_dd_mandate');
    $this->assign('tableTitle', ts('Available instructions'));
    $this->assign('entityTable', 'civicrm_value_dd_mandate');

    if ($this->_action == CRM_Core_Action::VIEW) {
      CRM_Utils_System::setTitle(ts('New Direct Debit Instructions Batches - %1', [1 => $this->batch->title]));
    }

    return [
      [
        'name' => 'originator_number',
        'value' => $batchData['values']['originator_number'],
      ],
      [
        'name' => 'dd_code',
        'value' => [array_search('0N', $ddCodes)],
      ],
      [
        'name' => 'start_date',
        'value' => date('Y-m-d H:i:s'),
      ],
    ];
  }

  /**
   * Assigns direct debit payment batch properties for transaction
   */
  private function assignDDPaymentsSearchProperties() {
    $ddCodes = CRM_Core_OptionGroup::values('direct_debit_codes');
    $paymentInstrument = CRM_Core_OptionGroup::values('payment_instrument', FALSE, FALSE, FALSE, NULL, 'name');
    $recurStatus = $contributionStatus = CRM_Core_OptionGroup::values('contribution_status', FALSE, FALSE, FALSE, NULL, 'name');
    unset($recurStatus[array_search('Cancelled', $contributionStatus)]);

    $batchData = json_decode($this->_values['data'], TRUE);

    $this->addElement('hidden', 'entityTable', 'civicrm_contribution');
    $this->assign('tableTitle', ts('Available Payments'));
    $this->assign('entityTable', 'civicrm_contribution');

    if ($this->_action == CRM_Core_Action::VIEW) {
      CRM_Utils_System::setTitle(ts('Direct Debit Payment Batches - %1', [1 => $this->batch->title]));
    }

    return [
      [
        'name' => 'originator_number',
        'value' => $batchData['values']['originator_number'],
      ],
      [
        'name' => 'dd_code',
        'value' => [
          array_search('01', $ddCodes),
          array_search('17', $ddCodes),
        ],
      ],
      [
        'name' => 'receive_date',
        'value' => date('Y-m-d H:i:s'),
      ],
      [
        'name' => 'recur_status',
        'value' => array_keys($recurStatus),
      ],
      [
        'name' => 'contribution_status',
        'value' => [
          array_search('Pending', $contributionStatus),
          array_search('Cancelled', $contributionStatus),
        ],
      ],
      [
        'name' => 'payment_instrument',
        'value' => array_search('direct_debit', $paymentInstrument),
      ],
    ];
  }

  /**
   * Builds the form object.
   */
  public function buildQuickForm() {

    parent::buildQuickForm();
    if (CRM_Batch_BAO_Batch::checkBatchPermission('close', $this->_values['created_id'])) {
      if (CRM_Batch_BAO_Batch::checkBatchPermission('export', $this->_values['created_id'])) {
        $this->add('submit', 'export_batch', ts('Export Batch'), ['formtarget' => '_blank']);
        $this->add('submit', 'done_and_export_batch', ts('Done and Export Batch'), ['formtarget' => '_blank']);
        $this->add('submit', 'submitted', ts('Submit'));
        $this->add('submit', 'discard', ts('Discard'));
      }
    }

    $this->_group = CRM_Core_PseudoConstant::nestedGroup();

    CRM_Contribute_BAO_Query::buildSearchForm($this);
    $this->addElement('checkbox', 'toggleSelects', NULL, NULL);

    $this->add('select',
      'trans_remove',
      ts('Task'),
      ['' => ts('- actions -')] + ['Remove' => ts('Remove from Batch')]);

    $this->add('submit', 'rSubmit', ts('Go'),
      [
        'class' => 'crm-form-submit',
        'id' => 'GoRemove',
      ]);

    $this->addButtons(
      [
        [
          'type' => 'submit',
          'name' => ts('Search'),
          'isDefault' => TRUE,
        ],
      ]
    );

    $this->addElement('checkbox', 'toggleSelect', NULL, NULL);
    $this->add('select',
      'trans_assign',
      ts('Task'),
      ['' => ts('- actions -')] + ['Assign' => ts('Assign to Batch')]);

    $this->add('submit', 'submit', ts('Go'),
      [
        'class' => 'crm-form-submit',
        'id' => 'Go',
      ]);

    $this->addElement('hidden', 'batch_id', $this->batchID);

    $this->add('text', 'name', ts('Batch Name'));

    $customGroup = civicrm_api3('CustomGroup', 'getvalue', [
      'return' => "id",
      'name' => "direct_debit_mandate",
    ]);

    $this->assign('customGroup', $customGroup);
  }

  /**
   * postProcess function.
   */
  public function postProcess() {
    $params = $this->controller->exportValues($this->_name);

    $batchHandles = new CRM_ManualDirectDebit_Batch_BatchHandler($this->batchID);
    $batchSerializedValues = $batchHandles->getBatchValues();

    if (!isset($batchSerializedValues['values']['mandates']) || empty($batchSerializedValues['values']['mandates'])) {
      $returnValues = [
        'mandate_id' => CRM_ManualDirectDebit_Batch_Transaction::DD_MANDATE_TABLE . '.id as mandate_id',
        'contact_id' => CRM_ManualDirectDebit_Batch_Transaction::DD_MANDATE_TABLE . '.entity_id as contact_id',
        'name' => CRM_ManualDirectDebit_Batch_Transaction::DD_MANDATE_TABLE . '.account_holder_name as name',
        'sort_code' => CRM_ManualDirectDebit_Batch_Transaction::DD_MANDATE_TABLE . '.sort_code as sort_code',
        'account_number' => CRM_ManualDirectDebit_Batch_Transaction::DD_MANDATE_TABLE . '.ac_number as account_number',
        'amount' => 'IF(civicrm_contribution.net_amount IS NOT NULL, civicrm_contribution.net_amount , 0) as amount',
        'reference_number' => CRM_ManualDirectDebit_Batch_Transaction::DD_MANDATE_TABLE . '.dd_ref as reference_number',
        'transaction_type' => 'civicrm_option_value.label as transaction_type',
      ];

      if ($batchHandles->getBatchType() == 'dd_payments'){
        $returnValues['contribute_id'] = 'civicrm_contribution.id as contribute_id';
      };
      $mandateCurrentState['values']['mandates'] = $batchHandles->getMandateCurrentState($returnValues);

      $this->updateBatchValues($batchSerializedValues, $mandateCurrentState);
    }

    if (isset($params['export_batch']) || isset($params['done_and_export_batch'])) {
      $batch = new CRM_ManualDirectDebit_Batch_BatchHandler($this->batchID);
      $batch->createExportFile();
    }
  }

  /**
   * Get action links.
   *
   * @return array
   */
  public function &links() {
    if (!($this->links)) {
      $this->links = [
        'view' => [
          'name' => ts('View'),
          'url' => 'civicrm/contact/view/contribution',
          'qs' => 'reset=1&id=%%contid%%&cid=%%cid%%&action=view&context=contribution&selectedChild=contribute',
          'title' => ts('View Contribution'),
        ],
        'assign' => [
          'name' => ts('Assign'),
          'ref' => 'disable-action',
          'title' => ts('Assign Transaction'),
          'extra' => 'onclick = "assignRemove( %%id%%,\'' . 'assign' . '\' );"',
        ],
      ];
    }

    return $this->links;
  }

  /**
   * Updates current batch valuesvalues
   */
  private function updateBatchValues($batchSerializedValues, $mandateCurrentState) {
    $updatedBatch['values'] = array_merge($batchSerializedValues['values'], $mandateCurrentState['values']);

    $serializedBatchValues = json_encode($updatedBatch);

    civicrm_api3('Batch', 'create', [
      'id' => $this->batchID,
      'data' => $serializedBatchValues,
    ]);
  }

}
