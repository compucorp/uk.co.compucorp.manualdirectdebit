<?php

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
   * @var integer
   */
  protected $entityID;

  /**
   * Runs the page.
   *
   */
  public function run() {
    // get the requested action
    $action = CRM_Utils_Request::retrieve('action', 'String', $this, FALSE, 'browse'); // default to 'browse'

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
   *
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
   *
   */
  public function editForm() {
    return 'CRM_ManualDirectDebit_Form_BatchTransaction';
  }

  /**
   * Gets edit form name.
   *
   * @return string
   *   name of this page.
   *
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
   *
   */
  public function userContext($mode = NULL) {
    return 'civicrm/direct_debit/batch-transaction';
  }

}
