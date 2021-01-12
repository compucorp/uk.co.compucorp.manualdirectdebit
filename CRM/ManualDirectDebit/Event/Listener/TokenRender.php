<?php

use CRM_ManualDirectDebit_Common_MessageTemplate as MessageTemplate;

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

    $activityTypeId = $this->event->context['actionSearchResult']->activity_type_id;
    $activityTypeName = $this->getActivityTypeNameById($activityTypeId);
    switch ($activityTypeName) {
      case 'new_direct_debit_recurring_payment':
      case 'update_direct_debit_recurring_payment':
      case 'offline_direct_debit_auto_renewal':
        $recurringContributionId = $this->event->context['actionSearchResult']->source_record_id;
        $dataCollector = new CRM_ManualDirectDebit_Mail_DataCollector_RecurringContribution($recurringContributionId);
        break;

      case 'direct_debit_payment_reminder':
        $contributionId = $this->event->context['actionSearchResult']->source_record_id;
        $dataCollector = new CRM_ManualDirectDebit_Mail_DataCollector_Contribution($contributionId);
        break;

      case 'direct_debit_mandate_update':
        $mandateId = $this->event->context['actionSearchResult']->source_record_id;
        $dataCollector = new CRM_ManualDirectDebit_Mail_DataCollector_Mandate($mandateId);
        break;
    }

    return $dataCollector;
  }

  private function getActivityTypeNameById($id) {
    $optionValue = civicrm_api3('OptionValue', 'get', [
      'sequential' => 1,
      'option_group_id' => 'activity_type',
      'value' => $id,
    ]);

    if (empty($optionValue['count'])) {
      return NULL;
    }

    return $optionValue['values'][0]['name'];
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
