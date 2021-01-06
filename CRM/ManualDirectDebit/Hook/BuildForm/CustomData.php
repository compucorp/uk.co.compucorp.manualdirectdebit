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

  /**
   * Id of DirectDebit Mandate Custom Group
   *
   * @var int
   */
  private $directDebitMandateId;

  public function __construct($form) {
    $this->form = $form;
    $this->templatePath = CRM_ManualDirectDebit_ExtensionUtil::path() . '/templates';
    $this->directDebitMandateId = CRM_ManualDirectDebit_Common_DirectDebitDataProvider::getGroupIDByName("direct_debit_mandate");
  }

  /**
   *  Checks if custom group 'Direct Debit Mandate' and launches hiding
   */
  public function run() {
    if ($this->checkIfDirectDebitMandateInGroupTree()) {
      $this->hideSaveAndNewButton();

      if ($this->isAddOperation()) {
        $this->hideDdRef();
      }
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
    $customGroupTree = $this->form->getVar('_groupTree');

    return array_key_exists($this->directDebitMandateId, $customGroupTree);
  }

  /**
   *  Hides 'Save and New' button
   */
  private function hideSaveAndNewButton() {
    $buttonsGroup = $this->form->getElement('buttons');
    foreach ($buttonsGroup->_elements as $key => $button) {
      if ($button->_attributes['value'] == "Save and New") {
        unset($buttonsGroup->_elements[$key]);
      }
    }
  }

  private function isAddOperation() {
    $mode = CRM_Utils_Request::retrieve('mode', 'String', $this->form);
    return $mode === 'add';
  }

  /**
   *  Hides 'DD ref' custom field
   */
  private function hideDdRef() {
    $customFieldId = CRM_ManualDirectDebit_Common_DirectDebitDataProvider::getCustomFieldIdByName("dd_ref");
    $ddRefElementNameId = $this->form->_groupTree[$this->directDebitMandateId]['fields'][$customFieldId]['element_name'];
    unset($this->form->_groupTree[$this->directDebitMandateId]['fields'][$customFieldId]);
    foreach ($this->form->_required as $requiredFieldsId => $requiredFieldsName) {
      if ($requiredFieldsName == $ddRefElementNameId) {
        unset($this->form->_required[$requiredFieldsId]);
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

  /**
   *  Adds hidden mandate id
   */
  private function addMandateIdHiddenValue() {
    $mandateId = CRM_Utils_Request::retrieve('mandateId', 'Integer', $this->form, FALSE);

    if (isset($mandateId) && !empty($mandateId)) {
      $this->form->add('hidden', 'mandateId', $mandateId);
    }
  }

  /**
   *  Adds send mail checkbox
   */
  private function addSendMailCheckbox() {
    $this->form->add('checkbox', 'send_mandate_update_notification_to_the_contact', ts('Send mandate update notification to the contact?'), NULL);

    CRM_Core_Region::instance('page-body')->add([
      'template' => "{$this->templatePath}/CRM/ManualDirectDebit/Form/SendMandateNotification.tpl",
    ]);
  }

}
