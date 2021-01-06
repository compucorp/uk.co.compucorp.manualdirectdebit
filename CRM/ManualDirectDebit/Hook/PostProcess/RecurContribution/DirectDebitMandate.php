<?php

class CRM_ManualDirectDebit_Hook_PostProcess_RecurContribution_DirectDebitMandate {

  private $recurContributionId;

  private $mandateId;

  public function __construct($form) {
    $this->recurContributionId = $form->getVar('contributionRecurID');

    if (!empty($form->getSubmitValue('mandate_id'))) {
      $this->mandateId = $form->getSubmitValue('mandate_id');
    }

  }

  public function saveMandateData() {
    if (empty($this->mandateId)) {
      return;
    }

    $mandateManager = new CRM_ManualDirectDebit_Common_MandateStorageManager();
    $mandateManager->assignRecurringContributionMandate($this->recurContributionId, $this->mandateId);
    $relatedContributions = $this->getRelatedPendingContributions();
    foreach ($relatedContributions as $contribution) {
      $mandateManager->assignContributionMandate($contribution['id'], $this->mandateId);
    }
  }

  private function getRelatedPendingContributions() {
    $result = civicrm_api3('Contribution', 'get', [
      'sequential' => 1,
      'return' => ['id'],
      'contribution_status_id' => 'Pending',
      'contribution_recur_id' => $this->recurContributionId,
      'options' => ['limit' => 0],
    ]);

    if ($result['count'] > 0) {
      return $result['values'];
    }

    return [];
  }

}
