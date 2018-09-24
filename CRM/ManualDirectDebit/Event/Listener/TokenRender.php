<?php

use CRM_ManualDirectDebit_Common_MessageTemplate as MessageTemplate;
use CRM_ManualDirectDebit_Mail_DataCollector_RecurringContribution as RecurringContributionDataCollector;

class CRM_ManualDirectDebit_Event_Listener_TokenRender {

  private $event;

  private $templateId = 0;

  public function __construct($event) {
    $this->event = $event;

    if (!empty($event->context['actionSchedule']->msg_template_id)) {
      $this->templateId = $event->context['actionSchedule']->msg_template_id;
    }
  }

  public function replaceDirectDebitTokens() {
    if (!MessageTemplate::isDirectDebitTemplate($this->templateId)) {
      return;
    }

    $tokenDataCollector = $this->getTokenDataCollector();
    if (empty($tokenDataCollector)) {
      return;
    }

    $this->replaceTemplateTokens($tokenDataCollector);
  }

  private function getTokenDataCollector() {
    $dataCollector = NULL;

    $templateTitle = MessageTemplate::getMessageTemplateTitle($this->templateId);
    $recurringContributionId = $this->event->context['actionSearchResult']->source_record_id;
    switch ($templateTitle) {
      case MessageTemplate::SIGN_UP_MSG_TITLE:
      case MessageTemplate::PAYMENT_UPDATE_MSG_TITLE:
      case MessageTemplate::AUTO_RENEW_MSG_TITLE:
        $dataCollector = new RecurringContributionDataCollector($recurringContributionId);
        break;
    }

    return $dataCollector;
  }

  private function replaceTemplateTokens($tokenDataCollector) {
    $templateParams = $tokenDataCollector->retrieve();
    $smarty = CRM_Core_Smarty::singleton();
    foreach ($templateParams as $name => $value) {
      $smarty->assign($name, $value);
    }

    $renderedTemplateText = $smarty->fetch("string:{$this->event->string}");
    $this->event->string = $renderedTemplateText;
  }

}
