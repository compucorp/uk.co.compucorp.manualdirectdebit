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
   * PreProcess function.
   */
  public function preProcess() {
    $this->batchID = CRM_Utils_Request::retrieve('bid', 'Positive') ? CRM_Utils_Request::retrieve('bid', 'Positive') : CRM_Utils_Array::value('batch_id', $_POST);
    $this->assign('entityID', $this->batchID);
    if (isset($this->batchID)) {
      $batch = new CRM_ManualDirectDebit_Batch_BatchHandler($this->batchID);
      $this->assign('statusID', $batch->getBatchStatusId());
      $this->assign('batchStatus', $batch->getBatchStatus());
      $this->assign('validStatus', $batch->validBatchStatus());

      $this->_values = civicrm_api3('Batch', 'getSingle', ['id' => $this->batchID]);
      CRM_Utils_System::setTitle(ts('New Direct Debit Instructions'));

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
      $values = json_decode($this->_values['data'], TRUE);
      $ddCode = civicrm_api3('OptionValue', 'getvalue', [
        'return' => "value",
        'option_group_id' => "direct_debit_codes",
        'name' => "new_direct_debit_i_setup",
      ]);

      $values['values']['dd_code'] = $ddCode;
      $values['values']['start_date'] = date('Y-m-d H:i:s');
      $this->assign($values['values']);
    }
  }

  /**
   * Builds the form object.
   *
   */
  public function buildQuickForm() {

    parent::buildQuickForm();
    if (CRM_Batch_BAO_Batch::checkBatchPermission('close', $this->_values['created_id'])) {
      if (CRM_Batch_BAO_Batch::checkBatchPermission('export', $this->_values['created_id'])) {
        $this->add('submit', 'export_batch', ts('Done and Export Batch'), ['formtarget' => '_blank']);
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
  }

  /**
   * postProcess function.
   *
   */
  public function postProcess() {
    $params = $this->controller->exportValues($this->_name);

    if (isset($params['export_batch'])) {
      $batch = new CRM_ManualDirectDebit_Batch_BatchHandler($this->batchID);
      $batch->createExportFile($params);
    }
  }

  /**
   * Get action links.
   *
   * @return array
   *
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
