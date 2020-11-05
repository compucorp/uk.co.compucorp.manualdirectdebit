<?php
use CRM_ManualDirectDebit_Batch_BatchHandler as BatchHandler;

/**
 * Page for displaying the list of financial batches
 */
class CRM_ManualDirectDebit_Page_BatchList extends CRM_Core_Page_Basic {

  /**
   * The action links that we need to display for the browse screen.
   *
   * @var array
   */
  public static $links = NULL;

  /**
   * Pager
   *
   * @var \CRM_Utils_Pager
   */
  protected $_pager;

  /**
   * List of machine names mapped to batch type value.
   *
   * @var array
   */
  private $batchTypeMachineNames = [];

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
          'extra' => 'onclick = "assignRemove( %%id%%, \'export\' );"',
        ],
        CRM_Core_Action::ENABLE => [
          'name' => ts('Submit'),
          'title' => ts('Submit Transaction'),
          'extra' => 'onclick = "submitBatch(%%id%%, \'%%submitMessage%%\');"',
        ],
        CRM_Core_Action::UPDATE => [
          'name' => ts('Update'),
          'url' => 'civicrm/direct_debit/batch-transaction',
          'qs' => 'reset=1&bid=%%id%%&action=update',
          'title' => ts('Update Transaction'),
        ],
        CRM_Core_Action::DISABLE => [
          'name' => ts('Discard'),
          'title' => ts('Discard Transaction'),
          'extra' => 'onclick = "assignRemove( %%id%%, \'discard\' );"',
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
    // get the requested action - default to 'browse'
    $action = CRM_Utils_Request::retrieve('action', 'String', $this, FALSE, 'browse');
    $this->assign('action', $action);

    $typeIdInRequest = CRM_Utils_Request::retrieve('type_id', 'String', $this, FALSE, NULL);
    $this->setPageTitle($typeIdInRequest);

    $param = $this->buildSearchParams($typeIdInRequest);
    $this->pager($param);
    [$param['offset'], $param['rowCount']] = $this->_pager->getOffsetAndRowCount();

    $this->assign('batches', $this->getBatchList($param));
    $this->assign('submittedMessage', BatchHandler::getSubmitAlertMessage($typeIdInRequest));
    $this->assign('type', $this->getEntityTypeName($typeIdInRequest));
    $this->assign('type_id', $typeIdInRequest);
    $this->assign('created_date_from', CRM_Utils_Request::retrieve('created_date_from', 'String', $this, FALSE, NULL));
    $this->assign('created_date_to', CRM_Utils_Request::retrieve('created_date_to', 'String', $this, FALSE, NULL));
    $this->assign('batchTypes', $this->getBatchTypeOptions());

    return parent::run();
  }

  /**
   * Sets the title for the page, depending on the given batch type id.
   *
   * @param int $batchTypeID
   */
  private function setPageTitle($batchTypeID) {
    switch ($this->getBatchTypeMachineName($batchTypeID)) {
      case BatchHandler::BATCH_TYPE_PAYMENTS:
        CRM_Utils_System::setTitle(ts('Manage Payment Batches'));
        break;

      case BatchHandler::BATCH_TYPE_INSTRUCTIONS:
      case BatchHandler::BATCH_TYPE_CANCELLATIONS:
        CRM_Utils_System::setTitle(ts('Manage Instruction Batches'));
        break;

      default:
        CRM_Utils_System::setTitle(ts('Manage Direct Debit Batches'));
    }
  }

  /**
   * Obtains machine name for the given batch type id.
   *
   * Returns machine name for the given type value, or an empty string if it is
   * not found.
   *
   * @param int $batchTypeID
   *
   * @return string
   */
  private function getBatchTypeMachineName($batchTypeID) {
    if (count($this->batchTypeMachineNames) === 0) {
      $this->batchTypeMachineNames = CRM_Core_OptionGroup::values('batch_type', FALSE, FALSE, FALSE, NULL, 'name');
    }

    return CRM_Utils_Array::value($batchTypeID, $this->batchTypeMachineNames, '');
  }

  /**
   * Builds parameters that will be used to search for batches.
   *
   * @param $typeIdInRequest
   *
   * @return array
   * @throws \CRM_Core_Exception
   */
  private function buildSearchParams($typeIdInRequest) {
    $createdDateFrom = CRM_Utils_Request::retrieve('created_date_from', 'String', $this, FALSE, NULL);
    if (!empty($createdDateFrom)) {
      $createdDateFrom .= ' 00:00:00';
    }

    $createdDateTo = CRM_Utils_Request::retrieve('created_date_to', 'String', $this, FALSE, NULL);
    if (!empty($createdDateTo)) {
      $createdDateTo .= ' 23:59:59';
    }

    $param = [
      'type_id' => $typeIdInRequest,
      'context' => '',
    ];

    switch (TRUE) {
      case !empty($createdDateFrom) && !empty($createdDateTo):
        $param['created_date'] = ['BETWEEN' => [$createdDateFrom, $createdDateTo]];
        break;

      case !empty($createdDateFrom):
        $param['created_date'] = ['>=' => $createdDateFrom];
        break;

      case !empty($createdDateTo):
        $param['created_date'] = ['<=' => $createdDateTo];
        break;
    }

    return $param;
  }

  /**
   * Obtains list of batches that meet the given search criteria.
   *
   * @param array $param
   *
   * @return array
   */
  private function getBatchList($param) {
    $batchList = CRM_ManualDirectDebit_Page_BatchTableListHandler::generateRows($param);
    $batchStatuses = CRM_Core_PseudoConstant::get('CRM_Batch_DAO_Batch', 'status_id', ['labelColumn' => 'name']);
    $batchTypeNames = CRM_Core_OptionGroup::values('batch_type', FALSE, FALSE, FALSE, NULL, 'label');

    foreach ($batchList as $id => &$batch) {
      $action = array_sum(array_keys($this->links()));

      if (!in_array($batchStatuses[$batch['status_id']], [
        'Open',
        'Reopened',
      ])) {
        $action -= CRM_Core_Action::ENABLE;
        $action -= CRM_Core_Action::UPDATE;
        $action -= CRM_Core_Action::DISABLE;
      }

      $submitMessage = base64_encode(BatchHandler::getSubmitAlertMessage($batch['type_id']));
      $batch['action'] = CRM_Core_Action::formLink(
        self::links(),
        $action,
        ['id' => $id, 'submitMessage' => $submitMessage],
        ts('more'),
        FALSE,
        '',
        'Batch',
        $id
      );

      $param = [];
      $param['entityTable'] = BatchHandler::BATCH_TYPE_PAYMENTS == $this->getBatchTypeMachineName($batch['type_id']) ? 'civicrm_contribution' : 'civicrm_value_dd_mandate';
      $batchTransaction = new CRM_ManualDirectDebit_Batch_Transaction($batch['id'], $param);
      $batch['transaction_count'] = $batchTransaction->getTotalNumber();
      $batch['batch_type_name'] = $batchTypeNames[$batch['type_id']];
    }

    return $batchList;
  }

  /**
   * Determines entity type name form the given batch type value.
   *
   * @param string $typeId
   *
   * @return string
   */
  private function getEntityTypeName($typeId) {
    switch ($this->getBatchTypeMachineName($typeId)) {
      case BatchHandler::BATCH_TYPE_PAYMENTS:
        $entityTypeName = 'Payment';
        break;

      case BatchHandler::BATCH_TYPE_INSTRUCTIONS:
      case BatchHandler::BATCH_TYPE_CANCELLATIONS:
        $entityTypeName = 'Instruction';
        break;

      default:
        $entityTypeName = '';
    }

    return $entityTypeName;
  }

  /**
   * Builds options for batch types for the search form.
   *
   * @return array
   */
  private function getBatchTypeOptions() {
    $condition = " AND (
      v.name = '" . BatchHandler::BATCH_TYPE_INSTRUCTIONS . "'
      OR v.name = '" . BatchHandler::BATCH_TYPE_PAYMENTS . "'
      OR v.name = '" . BatchHandler::BATCH_TYPE_CANCELLATIONS . "'
    )";
    $batchTypeNames = CRM_Core_OptionGroup::values('batch_type', FALSE, FALSE, FALSE, $condition, 'label');

    return ['' => 'All'] + $batchTypeNames;
  }

  /**
   * @param $batchSearchParameters
   */
  public function pager($batchSearchParameters) {
    $pagerParameters = [];

    $pagerParameters['status'] = ts('Group') . ' %%StatusMessage%%';
    $pagerParameters['csvString'] = NULL;
    $pagerParameters['buttonTop'] = 'PagerTopButton';
    $pagerParameters['buttonBottom'] = 'PagerBottomButton';
    $pagerParameters['rowCount'] = $this->get(CRM_Utils_Pager::PAGE_ROWCOUNT);
    if (!$pagerParameters['rowCount']) {
      $pagerParameters['rowCount'] = CRM_Utils_Pager::ROWCOUNT;;
    }

    $pagerParameters['total'] = $this->getBatchCount($batchSearchParameters);
    $this->_pager = new CRM_Utils_Pager($pagerParameters);
    $this->assign_by_ref('pager', $this->_pager);
  }

  /**
   * Counts total amount of batches that meet the given search criteria.
   *
   * @param array $batchSearchParameters
   *
   * @return array
   * @throws \CiviCRM_API3_Exception
   */
  private function getBatchCount($batchSearchParameters) {
    $apiParams = CRM_Batch_BAO_Batch::whereClause($batchSearchParameters);

    if (isset($batchSearchParameters['created_date'])) {
      $apiParams['created_date'] = $batchSearchParameters['created_date'];
    }

    return civicrm_api3('Batch', 'getCount', $batchSearchParameters);
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
