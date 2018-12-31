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
   * CRM_ManualDirectDebit_Hook_BuildForm_UpdateSubscription constructor.
   *
   * @param \CRM_Contribute_Form_UpdateSubscription $form
   */
  public function __construct(CRM_Contribute_Form_UpdateSubscription $form) {
    $this->form = $form;
  }

  /**
   * Implements buildForm hook.
   */
  public function buildForm() {
    $contactID = CRM_Utils_Request::retrieve('cid', 'Positive', $this->form);
    CRM_Core_Resources::singleton()->addVars('coreForm', array('contact_id' => (int) $contactID));

    $recurringContributionID = CRM_Utils_Request::retrieve('crid', 'Positive', $this->form);
    $selectedMandateID = CRM_ManualDirectDebit_BAO_RecurrMandateRef::getMandateIdForRecurringContribution($recurringContributionID);

    if ($selectedMandateID) {
      CRM_Core_Resources::singleton()->addVars('coreForm', array('selected_mandate_id' => (int) $selectedMandateID));
    }
  }

}
