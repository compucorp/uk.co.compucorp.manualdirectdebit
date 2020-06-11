<?php

use CRM_ManualDirectDebit_ExtensionUtil as E;

/**
 * Form Manual Direct Debit Sign up Form
 *
 */
class CRM_ManualDirectDebit_Form_SignUp extends CRM_Core_Form {

  private $contributionId;

  public function preProcess() {
    parent::preProcess();
    $this->contributionId = CRM_Utils_Request::retrieveValue('contribution_id', 'String', NULL);
    if ($this->contributionId == NULL) {
      CRM_Utils_System::redirect('/');
    }
    CRM_Utils_System::setTitle(E::ts('Direct Debit Sign up'));
  }

  public function buildQuickForm() {
    $errorMessage = NULL;
    $contribution = civicrm_api3('Contribution', 'get', [
      'sequential' => 1,
      'id' => $this->contributionId,
      'contribution_status_id' => "Pending",
    ]);

    if ($contribution['count'] == 0) {
      $errorMessage = E::ts('This invoice is already paid. Please contact the administrator for the correct link to setup direct debit.');
    } else if (is_null($contribution['value']['contribution_recur_id'])) {
      $errorMessage = E::ts('This invoice is not part of a payment plan. Please contact the administrator for the correct link to set up a direct debit.');
    }

    if ($errorMessage != NULL) {
      $this->assign('errorMessage', $errorMessage);
      return;
    }

    parent::buildQuickForm();
  }

  public function postProcess() {
    parent::postProcess();
  }

}
