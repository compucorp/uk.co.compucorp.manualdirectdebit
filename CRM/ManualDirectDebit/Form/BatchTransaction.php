<?php
use CRM_ManualDirectDebit_Batch_BatchHandler as BatchHandler;

/**
 * This class generates form components for Batch Transaction
 */
class CRM_ManualDirectDebit_Form_BatchTransaction extends CRM_Contribute_Form_Search {

  /**
   * The action links that we need to display for the browse screen.
   *
   * @var array
   */
  protected $links = NULL;

  /**
   * Batch id.
   *
   * @var int
   */
  protected $batchID;

  /**
   * Batch.
   *
   * @var object
   */
  protected $batch;

  /**
   * List of values associated to the batch.
   *
   * @var array
   */
  private $_values;

  /**
   * PreProcess function.
   */
  public function preProcess() {
    $this->batchID = CRM_Utils_Request::retrieve('bid', 'Positive') ? CRM_Utils_Request::retrieve('bid', 'Positive') : CRM_Utils_Array::value('batch_id', $_POST);
    $this->assign('entityID', $this->batchID);
    if (isset($this->batchID)) {
      $batch = new BatchHandler($this->batchID);
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
    if ($batchType['name'] == BatchHandler::BATCH_TYPE_INSTRUCTIONS) {
      $searchData = $this->assignInstructionsSearchProperties();
    }

    if ($batchType['name'] == BatchHandler::BATCH_TYPE_PAYMENTS) {
      $searchData = $this->assignDDPaymentsSearchProperties();
      // Show filters only for create DD payments batch page.
      $this->assign('showFilters', TRUE);
      // Show "receive date" column only for DD payments batches.
      $this->assign('showReceiveDateColumn', TRUE);
    }

    if ($batchType['name'] == BatchHandler::BATCH_TYPE_CANCELLATIONS) {
      $searchData = $this->assignCancellationsSearchProperties();
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
   * Assigns search properties to the form.
   *
   * @return array[]
   */
  private function assignCancellationsSearchProperties() {
    CRM_Utils_System::setTitle(ts('Cancelled Instructions'));

    $this->addElement('hidden', 'entityTable', 'civicrm_value_dd_mandate');
    $this->assign('tableTitle', ts('Available instructions'));
    $this->assign('entityTable', 'civicrm_value_dd_mandate');

    $ddCodes = CRM_Core_OptionGroup::values('direct_debit_codes');
    $batchData = json_decode($this->_values['data'], TRUE);

    return [
      [
        'name' => 'originator_number',
        'value' => $batchData['values']['originator_number'],
      ],
      [
        'name' => 'dd_code',
        'value' => [array_search('0C', $ddCodes)],
      ],
    ];
  }

  /**
   * Assigns direct debit payment batch properties for transaction
   */
  private function assignDDPaymentsSearchProperties() {
    $ddCodes = CRM_Core_OptionGroup::values('direct_debit_codes');
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
        'name' => 'recur_status',
        'value' => array_keys($recurStatus),
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
        $this->add('submit', 'save_batch', ts('Save'));
        $this->add('submit', 'save_and_export_batch', ts('Save and Export Batch'), ['formtarget' => '_blank']);
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

    if (isset($params['export_batch']) || isset($params['save_and_export_batch'])) {
      $batch = new BatchHandler($this->batchID);
      $batch->createExportFile();
    }

    $this->redirectToBatchViewPage();
  }

  private function redirectToBatchViewPage() {
    $redirectPath = 'civicrm/direct_debit/batch-list';
    $redirectParams = http_build_query(['reset' => 1, 'type_id' => $this->_values['type_id']]);
    $redirectURL = CRM_Utils_System::url($redirectPath, $redirectParams);
    CRM_Utils_System::redirect($redirectURL);
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

}
