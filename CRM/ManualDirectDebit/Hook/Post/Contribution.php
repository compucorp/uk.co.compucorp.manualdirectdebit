<?php

use CRM_ManualDirectDebit_Common_CollectionReminderSendFlagManager as CollectionReminderSendFlagManager;

class CRM_ManualDirectDebit_Hook_Post_Contribution {

  private $contributionId;

  public function __construct($contributionId) {
    $this->contributionId = $contributionId;
  }

  public function process() {
    $this->createCollectionReminderSendFlag();
  }

  private function createCollectionReminderSendFlag() {
    CollectionReminderSendFlagManager::setIsNotificationSentToUnsent($this->contributionId);
  }

}
