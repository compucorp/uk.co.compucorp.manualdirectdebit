<?php

/**
 * Page for displaying the list of financial batches
 */
class CRM_ManualDirectDebit_Page_BatchList extends CRM_Core_Page_Basic {

  /**
   * The action links that we need to display for the browse screen.
   *
   * @var array
   */
  static $links = NULL;

  /**
   * Pager
   *
   * @var \CRM_Utils_Pager
   */
  protected $_pager;

  /**
   * Get BAO Name.
   *
   * @return string
   *   Classname of BAO.
   */
  public function getBAOName() {
    return 'CRM_Batch_BAO_Batch';
  }

  /**
   * Get action Links.
   *
   * @return array
   *   (reference) of action links
   */
  public function &links() {
    if (!(self::$links)) {
      self::$links = [
        CRM_Core_Action::VIEW => [
          'name' => ts('View'),
          'url' => 'civicrm/direct_debit/batch-transaction',
          'qs' => 'reset=1&bid=%%id%%&action=view',
          'title' => ts('View Transaction'),
        ],
        CRM_Core_Action::EXPORT => [
          'name' => ts('Export'),
          'title' => ts('Export Transaction'),
          'extra' => 'onclick = "assignRemove( %%id%%,\'' . 'export' . '\' );"',
        ],
        CRM_Core_Action::ENABLE => [
          'name' => ts('Submit'),
          'title' => ts('Submit Transaction'),
          'extra' => 'onclick = "assignRemove( %%id%%,\'' . 'submit' . '\' );"',
        ],
        CRM_Core_Action::DISABLE => [
          'name' => ts('Discard'),
          'title' => ts('Discard Transaction'),
          'extra' => 'onclick = "assignRemove( %%id%%,\'' . 'discard' . '\' );"',
        ],
      ];
    }
    return self::$links;
  }

  /**
   * Run the page.
   *
   * This method is called after the page is created. It checks for the
   * type of action and executes that action.
   * Finally it calls the parent's run method.
   */
  public function run() {
    // get the requested action
    $action = CRM_Utils_Request::retrieve('action', 'String', $this, FALSE, 'browse'); // default to 'browse'
    $this->assign('action', $action);
    $batchTypes = CRM_Core_OptionGroup::values('batch_type', FALSE, FALSE, FALSE, NULL, 'name');

    $typeId = CRM_Utils_Request::retrieve('type_id', 'String', $this, FALSE);
    $typeId = $typeId ?: array_search('instructions_batch', $batchTypes);

    $param = [
      'type_id' => $typeId,
      'context' => '',
    ];

    $this->pager($param);

    list($param['offset'], $param['rowCount']) = $this->_pager->getOffsetAndRowCount();

    $batchList = CRM_ManualDirectDebit_Page_BatchTableListHandler::generateRows($param);
    $batchStatuses = CRM_Core_PseudoConstant::get('CRM_Batch_DAO_Batch', 'status_id', ['labelColumn' => 'name']);

    $param = [];
    foreach ($batchList as $id => &$batch) {
      $action = array_sum(array_keys($this->links()));

      if (!in_array($batchStatuses[$batch['status_id']], [
        'Open',
        'Reopened',
      ])) {
        $action -= CRM_Core_Action::ENABLE;
        $action -= CRM_Core_Action::DISABLE;
      }

      $batch['action'] = CRM_Core_Action::formLink(self::links(), $action,
        ['id' => $id], ts('more'), FALSE, '', 'Batch', $id
      );

      $param['entityTable'] = 'dd_payments' == $batchTypes[$batch['type_id']] ? 'civicrm_contribution' : 'civicrm_value_dd_mandate';
      $batchTransaction = new CRM_ManualDirectDebit_Batch_Transaction($batch['id'], $param);
      $batch['transaction_count'] = $batchTransaction->getTotalNumber();
    }

    if('dd_payments' == $batchTypes[$typeId]){
      $type = 'Payment';
      CRM_Utils_System::setTitle(ts('View Payment Batches'));
    } else {
      $type = 'Instruction';
      CRM_Utils_System::setTitle(ts('View New Instruction Batches'));
    }

    $submittedMessage = CRM_ManualDirectDebit_Batch_BatchHandler::getSubmitAlertMessage($typeId);
    $this->assign('submittedMessage', $submittedMessage);
    $this->assign('batches', $batchList);
    $this->assign('type', $type);

    return parent::run();
  }

  /**
   * @param $param
   */
  public function pager($param) {
    $params = [];

    $params['status'] = ts('Group') . ' %%StatusMessage%%';
    $params['csvString'] = NULL;
    $params['buttonTop'] = 'PagerTopButton';
    $params['buttonBottom'] = 'PagerBottomButton';
    $params['rowCount'] = $this->get(CRM_Utils_Pager::PAGE_ROWCOUNT);
    if (!$params['rowCount']) {
      $params['rowCount'] = CRM_Utils_Pager::ROWCOUNT;;
    }

    $params['total'] = CRM_Batch_BAO_Batch::getBatchCount($param);
    $this->_pager = new CRM_Utils_Pager($params);
    $this->assign_by_ref('pager', $this->_pager);
  }

  /**
   * Get name of edit form.
   *
   * @return string
   *   Classname of edit form.
   */
  public function editForm() {
    return 'CRM_ManualDirectDebit_Form_BatchTransaction';
  }

  /**
   * Get edit form name.
   *
   * @return string
   *   name of this page.
   */
  public function editName() {
    return 'Batches';
  }

  /**
   * Get user context.
   *
   * @param null $mode
   *
   * @return string
   *   user context.
   */
  public function userContext($mode = NULL) {
    return 'civicrm/direct_debit/batch-list';
  }

}
