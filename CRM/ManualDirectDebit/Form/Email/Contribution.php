<?php

/**
 * Class overrides 'postProcess' method for adding custom data into template
 */
class CRM_ManualDirectDebit_Form_Email_Contribution extends CRM_Contribute_Form_Task_Email {

  /**
   * @throws \CRM_Core_Exception
   */
  public function postProcess() {
    $messageTemplateId = $this->getVar('_submitValues')['template'];
    if (CRM_ManualDirectDebit_Common_MessageTemplate::isDirectDebitTemplate($messageTemplateId)) {
      CRM_ManualDirectDebit_Mail_Task_ContributionEmailCommon::postProcess($this);
    }
    else {
      CRM_Contact_Form_Task_EmailCommon::postProcess($this);
    }
  }

}
