<?php
use CRM_ManualDirectDebit_ExtensionUtil as E;

class CRM_ManualDirectDebit_Page_Setup_Confirmation extends CRM_Core_Page {

  public function run() {
    CRM_Utils_System::setTitle(E::ts('Direct Debit Setup Confirmation'));
    parent::run();
  }

}
