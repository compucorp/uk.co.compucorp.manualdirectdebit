<?php

/**
 * Implements hook on buildForm event of the UpdateSubscription form.
 */
class CRM_ManualDirectDebit_Hook_BuildForm_UpdateSubscription {

  /**
   * Form that is being built.
   *
   * @var CRM_Contribute_Form_UpdateSubscription
   */
  private $form;

  /**
   * Path to where extension templates are physically stored.
   *
   * @var string
   */
  private $templatePath;

  /**
   * CRM_ManualDirectDebit_Hook_BuildForm_UpdateSubscription constructor.
   *
   * @param \CRM_Contribute_Form_UpdateSubscription $form
   */
  public function __construct(CRM_Contribute_Form_UpdateSubscription $form) {
    $this->form = $form;
    $this->templatePath = CRM_ManualDirectDebit_ExtensionUtil::path() . '/templates';
  }

  /**
   * Implements buildForm hook.
   */
  public function buildForm() {
    $this->addContactIDToCoreFormJSVariable();
    $this->addMandateIDToCoreFormJSVariable();
    $this->preventChangingDirectDebitPaymentMethod();
  }

  /**
   * On UpdateSubscription form, payment details are loaded in by an ajax call
   * and sends the contact_id, if it is defined in CRM.coreForm.contact_id. If
   * it's not defined, contact_id is determined by CiviCRM, defaulting to the
   * current user. This contact ID is needed to obtain the list of mandates the
   * contact has, and thus allow them to be selected in the form.
   *
   * This method sets the contact ID on the CRM.coreForm global variable, used
   * by CiviCRM to make the call that loads the fields associated to the payment
   * method.
   */
  private function addContactIDToCoreFormJSVariable() {
    $contactID = CRM_Utils_Request::retrieve('cid', 'Positive', $this->form);
    CRM_Core_Resources::singleton()->addVars('coreForm', array('contact_id' => (int) $contactID));
  }

  /**
   * Sets the mandate ID asociated to the recurring contribution as a global JS
   * variable added to CRM.coreForm.selected_mandate_id, so it can be used to
   * select the appropriate option on the selection box combo.
   */
  private function addMandateIDToCoreFormJSVariable() {
    $recurringContributionID = CRM_Utils_Request::retrieve('crid', 'Positive', $this->form);
    $selectedMandateID = CRM_ManualDirectDebit_BAO_RecurrMandateRef::getMandateIdForRecurringContribution($recurringContributionID);

    if ($selectedMandateID) {
      CRM_Core_Resources::singleton()->addVars('coreForm', array('selected_mandate_id' => (int) $selectedMandateID));
    }
  }

  private function preventChangingDirectDebitPaymentMethod() {
    CRM_Core_Region::instance('page-body')->add([
      'template' => "{$this->templatePath}/CRM/Member/Form/UpdateSubscriptionModifications.tpl",
    ]);
  }

}
