<?php

/**
 * Class provide hiding "Save and New" button from Direct Debit modal window
 */
class CRM_ManualDirectDebit_Hook_BuildForm_CustomData {

  /**
   * Path where template with new fields is stored.
   *
   * @var string
   */
  private $templatePath;

  /**
   * Form object that is being altered.
   *
   * @var object
   */
  private $form;

  public function __construct($form) {
    $this->form = $form;
    $this->templatePath = CRM_ManualDirectDebit_ExtensionUtil::path() . '/templates';
  }

  /**
   *  Checks if custom group 'Direct Debit Mandate' and launches hiding
   */
  public function run() {
    if ($this->checkIfDirectDebitMandateInGroupTree()) {
      $this->hideButton();
    }

    $this->checkRecurringContribution();
    $this->addMandateIdHiddenValue();

    $this->addSendMailCheckbox();
  }

  /**
   * Checks if 'Direct Debit Mandate' exists in group tree
   *
   * @return bool
   */
  private function checkIfDirectDebitMandateInGroupTree() {
    $directDebitMandateId = CRM_ManualDirectDebit_Common_DirectDebitDataProvider::getGroupIDByName("direct_debit_mandate");
    $customGroupTree = $this->form->getVar('_groupTree');

    return array_key_exists($directDebitMandateId, $customGroupTree);
  }

  /**
   *  Hides 'Save and New' button
   */
  private function hideButton() {
    $buttonsGroup = $this->form->getElement('buttons');
    foreach ($buttonsGroup->_elements as $key => $button) {
      if ($button->_attributes['value'] == "Save and New") {
        unset($buttonsGroup->_elements[$key]);
      }
    }
  }

  /**
   *  Adds hidden recurring contribution id if it was updated
   */
  private function checkRecurringContribution() {
    $recurrForUpdate = CRM_Utils_Request::retrieve('updatedRecId', 'Integer', $this->form, FALSE);

    if (isset($recurrForUpdate) && !empty($recurrForUpdate)) {
      $this->form->add('hidden', 'recurrId', $recurrForUpdate);
    }
  }

}
