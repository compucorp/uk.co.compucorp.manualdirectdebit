<?php

class CRM_ManualDirectDebit_Form_PrintMergeDocument extends CRM_Member_Form_Task_PDFLetter {

  /**
   * Process the form after the input has been submitted and validated.
   *
   * @return void
   */
  public function postProcess() {
    $this->setContactIDs();
    $skipOnHold = isset($this->skipOnHold) ? $this->skipOnHold : FALSE;
    $skipDeceased = isset($this->skipDeceased) ? $this->skipDeceased : TRUE;

    $submitValues = $this->getVar('_submitValues');
    $messageTemplateId = FALSE;
    if (isset($submitValues['template']) && !empty($submitValues['template'])) {
      $messageTemplateId = (int) $submitValues['template'];
    }

    if ($messageTemplateId && CRM_ManualDirectDebit_Common_MessageTemplate::isDirectDebitTemplate($messageTemplateId)) {
      CRM_ManualDirectDebit_Mail_Task_PDFLetterCommon::postProcessMembers(
        $this, $this->_memberIds, $skipOnHold, $skipDeceased, $this->_contactIds
      );
      return;
    }

    CRM_Member_Form_Task_PDFLetterCommon::postProcessMembers(
      $this, $this->_memberIds, $skipOnHold, $skipDeceased, $this->_contactIds
    );
  }

}
