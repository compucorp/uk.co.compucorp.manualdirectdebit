<?php

use CRM_ManualDirectDebit_Common_DirectDebitDataProvider as DirectDebitDataProvider;

class CRM_ManualDirectDebit_Hook_BuildForm_Membership {

  protected $templatesPath;

  /**
   * Form object that is being altered.
   *
   * @var object
   */
  protected $form;

  public function __construct(&$form) {
    $this->form = $form;
    $this->templatesPath = CRM_ManualDirectDebit_ExtensionUtil::path() . '/templates';
  }

  /**
   *  Builds form
   */
  public function buildForm() {
    $this->addMandateRelatedJSGlobalVariables();
    $this->changePaymentStatusOptionToPendingWhenDDPaymentMethodIsSelected();
  }

  /**
   * Adds global variables required to maintain status of mandate field.
   */
  private function addMandateRelatedJSGlobalVariables() {
    CRM_Core_Resources::singleton()->addVars('coreForm', array('empty_mandate_id' => FALSE));
  }

  private function changePaymentStatusOptionToPendingWhenDDPaymentMethodIsSelected() {
    $directDebitPaymentInstrumentId = DirectDebitDataProvider::getDirectDebitPaymentInstrumentId();
    $this->form->assign('directDebitPaymentInstrumentId', $directDebitPaymentInstrumentId);

    $pendingPaymentStatusID = $this->getPendingPaymentStatusID();
    $this->form->assign('pendingPaymentStatusID', $pendingPaymentStatusID);

    CRM_Core_Region::instance('page-body')->add([
      'template' => "{$this->templatesPath}/CRM/ManualDirectDebit/Form/Membership/DDPaymentMethodWatcher.tpl",
    ]);
  }

  private function getPendingPaymentStatusID() {
    return civicrm_api3('OptionValue', 'getvalue', [
      'return' => 'value',
      'option_group_id' => 'contribution_status',
      'name' => 'Pending',
    ]);
  }

}
