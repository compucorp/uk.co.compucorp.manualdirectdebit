<?php

class CRM_ManualDirectDebit_Form_Email_Membership extends CRM_Member_Form_Task_Email {

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

    $membershipEmailCommon = new CRM_ManualDirectDebit_Mail_Task_Email_MembershipEmail();
    $membershipEmailCommon->postProcess($this);

  }

}
