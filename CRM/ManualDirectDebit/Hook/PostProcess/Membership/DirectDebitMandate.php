<?php

/**
 * Class provide fetching Direct Debit Mandate Data from Membership form
 * and launch saving in Database
 */
class CRM_ManualDirectDebit_Hook_PostProcess_Membership_DirectDebitMandate {

  /**
   * Form object that is being altered.
   *
   * @var \CRM_Member_Form
   */
  private $form;

  /**
   * List Of mandate custom group fields
   *
   * @var array
   */
  private $listOfDirectDebitMandateCustomGroupFields;

  /**
   * List of mandate date
   *
   * @var array
   */
  private $mandateValues;

  /**
   * Id of current mandate
   *
   * @var int
   */
  private $currentContactId;

  public function __construct(CRM_Member_Form $form) {
    $this->form = $form;
    $this->currentContactId = $this->form->getVar('_contactID');

    $mandateDataProvider = new CRM_ManualDirectDebit_Common_DirectDebitDataProvider();
    $this->listOfDirectDebitMandateCustomGroupFields = $mandateDataProvider->getMandateCustomFieldNames();
  }

  /**
   * Saves Direct Debit Mandate Data
   */
  public function saveMandateData() {
    $mandateID = $this->form->getSubmitValue('mandate_id');

    $storageManager = new CRM_ManualDirectDebit_Common_MandateStorageManager();

    $mandate = $storageManager->getMandate($mandateID);
    $mandateValues = $mandate->toArray();
    $storageManager->assignMandate($mandateID, $this->currentContactId);
    $storageManager->launchCustomHook($this->currentContactId, $mandateValues);
  }

}
