<?php

use CRM_ManualDirectDebit_BAO_RecurrMandateRef as RecurrMandateRef;

class CRM_ManualDirectDebit_Hook_PostOfflineAutoRenewal_Mandate {

  private $contributionRecurId;

  private $previousRecurContributionId;

  public function __construct($contributionRecurId, $previousRecurContributionId) {
    $this->contributionRecurId = $contributionRecurId;
    $this->previousRecurContributionId = $previousRecurContributionId;
  }

  public function process() {
    $this->linkMandateToTheNewRecurringContribution();
  }

  private function linkMandateToTheNewRecurringContribution() {
    $isMandateAlreadyLinked = RecurrMandateRef::getMandateIdForRecurringContribution($this->contributionRecurId);
    if ($isMandateAlreadyLinked) {
      return;
    }

    $mandateId = RecurrMandateRef::getMandateIdForRecurringContribution($this->previousRecurContributionId);
    if ($mandateId) {
      $params = [
        'recurr_id' => $this->contributionRecurId,
        'mandate_id' => $mandateId,
      ];

      CRM_ManualDirectDebit_BAO_RecurrMandateRef::create($params);
    }
  }

}
