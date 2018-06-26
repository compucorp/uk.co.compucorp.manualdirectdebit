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
   * Saves Direct Debit Mandate Data
   */
  public function saveMandateData() {
    $this->setMandateValues();

    $storageManager = new CRM_ManualDirectDebit_Common_MandateStorageManager();
    $storageManager->saveDirectDebitMandate($this->currentContactId, $this->mandateValues);
  }

  /**
   * Sets direct debit mandate values
   */
  private function setMandateValues() {
    $submitFiles = $this->form->getVar('_submitFiles');
    $authorisationFileName = CRM_ManualDirectDebit_Common_DirectDebitDataProvider::PREFIX . 'authorisation_file';
    $authorisationFile = $submitFiles[$authorisationFileName];
    if ($this->isFileAttached($authorisationFile)) {
      $this->setMandateFile($authorisationFile);
    }

    $this->setMandateContacId();

    $submitValues = $this->form->getVar('_submitValues');

    foreach ($submitValues as $field => $value) {
      if ($this->isFieldIsPartOfDirectDebitCustomGroup($field) && $this->isValueNotEmpty($value)) {
        $this->mandateValues[$this->getColumnName($field)] = $value;
      }
    }

    unset($this->mandateValues['dd_ref']);
  }

  /**
   * Checks if 'directDebitMandate_authorisation_file' was added in mandate
   *
   * @param $submitFiles
   *
   * @return bool
   */
  private function isFileAttached($submitFiles) {
    return !empty($submitFiles['tmp_name']);
  }

  /**
   * Save file and assign it Id to mandate
   *
   * @param $file
   */
  private function setMandateFile($file) {
    $transaction = new CRM_Core_Transaction();

    try {
      CRM_Core_BAO_File::filePostProcess(
        $file['tmp_name'],
        NULL,
        CRM_ManualDirectDebit_Common_MandateStorageManager::DIRECT_DEBIT_TABLE_NAME,
        $this->currentContactId,
        NULL,
        TRUE,
        NULL,
        'uploadedMandateFile',
        $file['type']
      );

      $sqlSelectDebitMandateID = "SELECT MAX(id) as id FROM `civicrm_file`";
      $queryResult = CRM_Core_DAO::executeQuery($sqlSelectDebitMandateID);
      $queryResult->fetch();

      if (isset($queryResult->id) && !empty($queryResult->id)) {
        $this->mandateValues['authorisation_file'] = $queryResult->id;
      }
    } catch (Exception $exception) {
      $transaction->rollback();
      throw $exception;
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
   * @param string $fieldName
   *
   * @return bool
   */
  private function isFieldIsPartOfDirectDebitCustomGroup($fieldName) {
    return in_array($fieldName, $this->listOfDirectDebitMandateCustomGroupFields);
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
    $fieldNamePrefix = substr($fieldName, 0, strlen(CRM_ManualDirectDebit_Common_DirectDebitDataProvider::PREFIX));
    if ($fieldNamePrefix == CRM_ManualDirectDebit_Common_DirectDebitDataProvider::PREFIX) {
      $columnName = substr($fieldName, strlen(CRM_ManualDirectDebit_Common_DirectDebitDataProvider::PREFIX));
    }

    return $columnName;
  }

}
