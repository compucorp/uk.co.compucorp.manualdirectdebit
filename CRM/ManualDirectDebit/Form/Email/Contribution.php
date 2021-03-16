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
    $isDirectDebitTemplate = CRM_ManualDirectDebit_Common_MessageTemplate::isDirectDebitTemplate($messageTemplateId);
    if (!$isDirectDebitTemplate) {
      parent::postProcess();
      return;
    }

    $contribuitonEmailCommon = new CRM_ManualDirectDebit_Mail_Task_Email_ContributionEmail();
    $contribuitonEmailCommon->postProcess($this);
  }

}
