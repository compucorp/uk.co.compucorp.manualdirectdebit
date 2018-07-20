<?php

/**
 * Class provide hiding 'Direct Debit Information' custom group if it`s empty
 */
class CRM_ManualDirectDebit_Hook_BuildForm_CustomDataByType {

  /**
   * Form object that is being altered.
   *
   * @var object
   */
  private $form;

  /**
   * List of custom group elements
   *
   * @var array
   */
  private $customGroupTree;

  /**
   * Id of 'Direct Debit Information' custom group id
   *
   * @var int
   */
  private $directDebitInformationId;

  public function __construct($form) {
    $this->form = $form;
    $this->customGroupTree = $form->getVar('_groupTree');
    $this->directDebitInformationId = CRM_ManualDirectDebit_Common_DirectDebitDataProvider::getGroupIDByName("direct_debit_information");
  }

  /**
   * Checks if custom group 'Direct Debit Information' and launches hiding
   */
  public function run() {
    if ($this->checkIfDirectDebitInformationInGroupTree()) {
      $this->hideDirectDebitInformationIfEmpty();
    }
  }

  /**
   * Checks if Direct Debit Information exists in group tree
   *
   * @return bool
   */
  private function checkIfDirectDebitInformationInGroupTree() {
    return array_key_exists($this->directDebitInformationId, $this->customGroupTree);
  }

  /**
   *  Hides Direct Debit Information if it`s empty
   */
  private function hideDirectDebitInformationIfEmpty() {
    $customFieldId = CRM_ManualDirectDebit_Common_DirectDebitDataProvider::getCustomFieldIdByName("mandate_id");
    $mandateIdValue = $this->customGroupTree[$this->directDebitInformationId]['fields'][$customFieldId]['element_value'];

    if (!isset($mandateIdValue) || empty($mandateIdValue)) {
      unset($this->form->_groupTree[$this->directDebitInformationId]);
    }
  }

}
