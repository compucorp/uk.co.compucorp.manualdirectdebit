<?php
use CRM_ManualDirectDebit_ExtensionUtil as E;

class CRM_ManualDirectDebit_Hook_PageRun_ViewCustomData {

  private $path;

  private $multiRecordDisplay;

  private $mode;

  /**
   * Mandate storage manager object.
   *
   * @var \CRM_ManualDirectDebit_Common_MandateStorageManager
   */
  private $mandateStorageManager;

  /**
   * Page that needs to be processed.
   *
   * @var \CRM_Contact_Page_View_CustomData
   */
  private $page;

  /**
   * CRM_ManualDirectDebit_Hook_PageRun_ViewCustomData constructor.
   *
   * @param string $path
   * @param string $multiRecordDisplay
   * @param string $mode
   * @param \CRM_ManualDirectDebit_Common_MandateStorageManager $mandateStorageManager
   * @param \CRM_Contact_Page_View_CustomData $page
   */
  public function __construct($path, $multiRecordDisplay, $mode, CRM_ManualDirectDebit_Common_MandateStorageManager $mandateStorageManager, CRM_Contact_Page_View_CustomData $page) {
    $this->path = $path;
    $this->multiRecordDisplay = $multiRecordDisplay;
    $this->mode = $mode;
    $this->mandateStorageManager = $mandateStorageManager;
    $this->page = $page;
  }

  /**
   * Processes the page.
   *
   * @throws \CRM_Core_Exception
   */
  public function process() {
    $this->addContactTypeAsVar();
    $this->addEditAndDeleteButtons();
  }

  /**
   * Adds the contact's type as a var to the page.
   */
  private function addContactTypeAsVar() {
    $contactId = $this->page->getVar('_contactId');
    CRM_Core_Resources::singleton()->addVars('uk.co.compucorp.manualdirectdebit', [
      'contactType' => _manualdirectdebit_getContactType($contactId),
    ]);
  }

  /**
   * Adds edit and delete mandate buttons to the page.
   *
   * @throws \CRM_Core_Exception
   */
  private function addEditAndDeleteButtons() {
    $groupId = $this->page->_groupId;
    $this->page->assign('groupId', $groupId);

    CRM_Core_Resources::singleton()
      ->addScriptFile('uk.co.compucorp.manualdirectdebit', 'js/mandateEdit.js');

    if ($this->path === 'civicrm/contact/view/cd' && $this->multiRecordDisplay === 'single' && $this->mode = 'view') {
      $mandateID = CRM_Utils_Request::retrieveValue('recId', 'Integer', 0);
      $this->page->assign('mandate_id', $mandateID);

      $contactId = $this->page->getVar('_contactId');
      $cgCount = $this->calculateCGCountForMandate($contactId, $mandateID);
      $this->page->assign('cgcount', $cgCount);

      CRM_Core_Region::instance('page-body')->add([
        'template' => E::path() . "/templates/CRM/Contact/Page/View/CustomDataEditButtons.tpl",
      ]);
    }
  }

  /**
   * Calculates what count is the given mandate within the contact's records.
   *
   * Ugh... civicrm's view to edit the mandate actually requires the mandate's
   * count, instead of the mandate ID, to build the form. For example, if a user
   * has mandates with ID's 20, 25 and 27, and you need to edit mandate 25, you
   * need to send the value "2" to the form, as mandate 25 is that contact's
   * second mandate...
   *
   * @param int $contactID
   * @param int $mandateID
   *
   * @return int
   */
  private function calculateCGCountForMandate($contactID, $mandateID) {
    $mandates = $this->mandateStorageManager->getMandatesForContact($contactID);
    $count = 1;

    foreach ($mandates as $checkedMandate) {
      if ($checkedMandate['id'] == $mandateID) {
        return $count;
      }

      $count++;
    }

    return 0;
  }

}
