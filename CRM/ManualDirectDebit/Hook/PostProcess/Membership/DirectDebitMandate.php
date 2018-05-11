<?php

/**
 * Class provide fetching Direct Debit Mandate Data from Membership form
 * and launch saving in Database
 */
class CRM_ManualDirectDebit_Hook_PostProcess_Membership_DirectDebitMandate {

  /**
   * Form object that is being altered.
   *
   * @var object
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

  public function __construct($form) {
    $this->form = $form;
    $this->currentContactId = $this->form->getVar('_contactID');

    $mandateDataProvider = new CRM_ManualDirectDebit_Common_DirectDebitDataProvider();
    $this->listOfDirectDebitMandateCustomGroupFields = $mandateDataProvider->getMandateCustomFieldNames();
  }

  /**
   * Fetches Direct Debit Mandate Data
   */
  public function fetchMandateData() {
    $this->setMandateValues();

    $storageManager = new CRM_ManualDirectDebit_Common_MandateStorageManager();
    $storageManager->saveDirectDebitMandate($this->currentContactId, $this->mandateValues);
  }

  /**
   * Sets direct debit mandate values
   */
  private function setMandateValues() {
    $this->setMandateContacId();

    $submitValues = $this->form->getVar('_submitValues');

    foreach ($submitValues as $field => $value) {
      if ($this->isFieldIsPartOfDirectDebitCustomGroup($field) && $this->isValueNotEmpty($value)) {
        $this->mandateValues[$this->getColumnName($field)] = $value;
      }
    }
  }

  /**
   * Sets contact id
   */
  private function setMandateContacId() {
    $this->mandateValues['entity_id'] = $this->currentContactId;
  }

  /**
   * Checks if the `field` is a part of Direct Debit Mandate Custom Group
   *
   * @param $field
   *
   * @return bool
   */
  private function isFieldIsPartOfDirectDebitCustomGroup($field) {
    return in_array($field, $this->listOfDirectDebitMandateCustomGroupFields);
  }

  /**
   * Checks if `value` is not empty
   *
   * @param $value
   *
   * @return bool
   */
  private function isValueNotEmpty($value) {
    return isset($value) && !empty($value);
  }

  /**
   * Cleans 'fieldName' from prefix
   *
   * @param $fieldName
   *
   * @return string
   */
  private function getColumnName($fieldName) {
    if (substr($fieldName, 0, strlen(CRM_ManualDirectDebit_Common_DirectDebitDataProvider::PREFIX)) == CRM_ManualDirectDebit_Common_DirectDebitDataProvider::PREFIX) {
      $columnName = substr($fieldName, strlen(CRM_ManualDirectDebit_Common_DirectDebitDataProvider::PREFIX));
    }
    return $columnName;
  }

}
