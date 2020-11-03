<?php
use CRM_ManualDirectDebit_Batch_BatchHandler as BatchHandler;

/**
 * Page for displaying list of Batch Transaction
 */
class CRM_ManualDirectDebit_Page_BatchTransaction extends CRM_Core_Page_Basic {

  /**
   * The action links that we need to display for the browse screen.
   *
   * @var array
   */
  protected $links = NULL;

  /**
   * @var int
   */
  protected $entityID;

  /**
   * Runs the page.
   */
  public function run() {
    // get the requested action - default to 'browse'
    $action = CRM_Utils_Request::retrieve('action', 'String', $this, FALSE, 'browse');

    if ($action == CRM_Core_Action::VIEW) {
      $batchID = CRM_Utils_Request::retrieve('bid', 'Positive') ?: CRM_Utils_Array::value('batch_id', $_POST);
      $batchTypes = CRM_Core_OptionGroup::values('batch_type', FALSE, FALSE, FALSE, NULL, 'name');
      $param = ['id' => $batchID, 'context' => ''];
      $batch = CRM_ManualDirectDebit_Page_BatchTableListHandler::generateRows($param);
      $batch = $batch[$batchID];
      $param['entityTable'] = $batchTypes[$batch['type_id']] == BatchHandler::BATCH_TYPE_PAYMENTS ? 'civicrm_contribution' : 'civicrm_value_dd_mandate';
      $batchTransaction = new CRM_ManualDirectDebit_Batch_Transaction($batch['id'], $param);

      $batchInfo = [
        'name' => $batch['name'],
        'transaction_count' => $batchTransaction->getTotalNumber(),
        'batch_status' => $batch['batch_status'],
        'created_date' => $batch['created_date'],
        'created_by' => $batch['created_by'],
      ];
      $type = BatchHandler::BATCH_TYPE_PAYMENTS == $batchTypes[$batch['type_id']] ? 'Payment' : 'Instruction';
      $submittedMessage = BatchHandler::getSubmitAlertMessage($batch['type_id']);

      $this->assign('batchInfo', $batchInfo);
      $this->assign('submittedMessage', $submittedMessage);
      $this->assign('type', $type);
    }

    // assign vars to templates
    $this->assign('action', $action);

    $this->entityID = CRM_Utils_Request::retrieve('bid', 'Positive');

    $this->edit($action, $this->entityID);

    return parent::run();
  }

  /**
   * Gets action Links.
   *
   * @return array
   *   (reference) of action links
   */
  public function &links() {
    return $this->links;
  }

  /**
   * Gets BAO Name.
   *
   * @return string
   *   Classname of BAO.
   */
  public function getBAOName() {
    return 'CRM_Batch_BAO_Batch';
  }

  /**
   * Gets name of edit form.
   *
   * @return string
   *   Classname of edit form.
   */
  public function editForm() {
    return 'CRM_ManualDirectDebit_Form_BatchTransaction';
  }

  /**
   * Gets edit form name.
   *
   * @return string
   *   name of this page.
   */
  public function editName() {
    return 'Batch';
  }

  /**
   * Gets user context.
   *
   * @param null $mode
   *
   * @return string
   *   user context.
   */
  public function userContext($mode = NULL) {
    return 'civicrm/direct_debit/batch-transaction';
  }

}
