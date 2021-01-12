<?php

use CRM_ManualDirectDebit_ExtensionUtil as E;

/**
 * Form controller class
 *
 * @see https://wiki.civicrm.org/confluence/display/CRMDOC/QuickForm+Reference
 */
class CRM_ManualDirectDebit_Form_Mandate_Create extends CRM_Core_Form {

  /**
   * List Of mandate custom group fields
   *
   * @var array
   */
  private $listOfDirectDebitMandateCustomGroupFields;

  public function __construct($state = NULL, $action = CRM_Core_Action::NONE, $method = 'post', $name = NULL) {
    parent::__construct($state, $action, $method, $name);

    $mandateDataProvider = new CRM_ManualDirectDebit_Common_DirectDebitDataProvider();
    $this->listOfDirectDebitMandateCustomGroupFields = $mandateDataProvider->getMandateCustomFieldNames();
  }

  public function buildQuickForm() {
    CRM_Utils_System::setTitle(E::ts('Create Mandate'));

    $mandateDataProvider = new CRM_ManualDirectDebit_Common_DirectDebitDataProvider();
    $mandateCustomGroupFieldData = $mandateDataProvider->getMandateCustomFieldDataForBuildingForm();

    foreach ($mandateCustomGroupFieldData as $customField) {
      $this->add(
        $customField['html_type'],
        $customField['name'],
        $customField['label'],
        $customField['option_group_id'],
        $customField['is_required'],
        $customField['params']
      );

      if ($customField['data_type'] == 'Int') {
        $this->addRule($customField['name'], ts($customField['label'] . ' must be a number.'), 'numeric');
      }
    }

    $minimumDaysToFirstPayment = $this->getMinimumDayForFirstPayment();
    $this->assign('minimumDaysToFirstPayment', $minimumDaysToFirstPayment);

    $directDebitPaymentInstrumentId = CRM_ManualDirectDebit_Common_DirectDebitDataProvider::getDirectDebitPaymentInstrumentId();
    $this->assign('directDebitPaymentInstrumentId', $directDebitPaymentInstrumentId);

    $this->addButtons([
      [
        'type' => 'submit',
        'name' => E::ts('Save'),
        'isDefault' => TRUE,
      ],
      [
        'type' => 'cancel',
        'name' => E::ts('Cancel'),
        'isDefault' => FALSE,
      ],
    ]);

    // export form elements
    $this->assign('elementNames', $this->getRenderableElementNames());
  }

  /**
   * @inheritdoc
   */
  public function postProcess() {
    $contactID = CRM_Utils_Request::retrieve('contact_id', 'Int', $this);
    $mandateValues = $this->extractMandateValues($contactID);

    $storageManager = new CRM_ManualDirectDebit_Common_MandateStorageManager();
    $mandate = $storageManager->saveDirectDebitMandate($contactID, $mandateValues);

    CRM_Core_Session::setStatus(
      E::ts('Mandate created with reference number %1', [
        1 => $mandate->dd_ref,
      ]),
      'Mandate Created',
      'success'
    );
  }

  /**
   * Extracts mandates data from the provided values.
   *
   * @param int $contactID
   *
   * @return array
   */
  private function extractMandateValues($contactID) {
    $mandateValues = [];
    $submitFiles = $this->getVar('_submitFiles');
    $authorisationFileName = CRM_ManualDirectDebit_Common_DirectDebitDataProvider::PREFIX . 'authorisation_file';
    $authorisationFile = $submitFiles[$authorisationFileName];

    if ($this->isFileAttached($authorisationFile)) {
      $mandateValues['authorisation_file'] = $this->storeMandateFile($contactID, $authorisationFile);
    }

    $mandateValues['entity_id'] = $contactID;

    $values = $this->exportValues();
    foreach ($values as $field => $value) {
      if ($this->isFieldIsPartOfDirectDebitCustomGroup($field) && $this->isValueNotEmpty($value)) {
        $mandateValues[$this->getColumnName($field)] = $value;
      }
    }

    return $mandateValues;
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
    $columnName = '';

    $fieldNamePrefix = substr($fieldName, 0, strlen(CRM_ManualDirectDebit_Common_DirectDebitDataProvider::PREFIX));
    if ($fieldNamePrefix == CRM_ManualDirectDebit_Common_DirectDebitDataProvider::PREFIX) {
      $columnName = substr($fieldName, strlen(CRM_ManualDirectDebit_Common_DirectDebitDataProvider::PREFIX));
    }

    return $columnName;
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
   * Save file and assign its Id to mandate
   *
   * @param int $contactID
   * @param array $file
   *
   * @return int
   *
   * @throws \Exception
   */
  private function storeMandateFile($contactID, $file) {
    $transaction = new CRM_Core_Transaction();

    try {
      CRM_Core_BAO_File::filePostProcess(
        $file['tmp_name'],
        NULL,
        CRM_ManualDirectDebit_Common_MandateStorageManager::DIRECT_DEBIT_TABLE_NAME,
        $contactID,
        NULL,
        TRUE,
        NULL,
        'uploadedMandateFile',
        $file['type']
      );

      $sqlSelectDebitMandateID = "SELECT MAX(id) as id FROM `civicrm_file`";
      $queryResult = CRM_Core_DAO::executeQuery($sqlSelectDebitMandateID);
      $queryResult->fetch();
    }
    catch (Exception $exception) {
      $transaction->rollback();

      throw $exception;
    }

    $transaction->commit();
    if (isset($queryResult->id) && !empty($queryResult->id)) {
      return $queryResult->id;
    }
  }

  /**
   * Get the fields/elements defined in this form.
   *
   * @return array (string)
   */
  public function getRenderableElementNames() {
    $elementNames = array();

    foreach ($this->_elements as $element) {
      $label = $element->getLabel();

      if (!empty($label)) {
        $elementNames[] = $element->getName();
      }
    }

    return $elementNames;
  }

  /**
   * Gets setting information about minimum days to first payment
   *
   * @return int
   */
  private function getMinimumDayForFirstPayment() {
    try {
      $minimumDaysToFirstPayment = CRM_ManualDirectDebit_Common_SettingsManager::getMinimumDayForFirstPayment();
    }
    catch (CiviCRM_API3_Exception $error) {
      $minimumDaysToFirstPayment = 0;
    }

    return $minimumDaysToFirstPayment;
  }

}
