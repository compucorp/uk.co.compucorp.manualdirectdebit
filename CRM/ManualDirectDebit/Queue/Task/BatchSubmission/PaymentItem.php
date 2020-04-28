<?php

class CRM_ManualDirectDebit_Queue_Task_BatchSubmission_PaymentItem {

  public static function run(CRM_Queue_TaskContext $ctx, $batchTaskItems) {
    $processingStartTime = microtime(TRUE);

    foreach ($batchTaskItems as $batchTaskItem) {
      try {
        if (!empty($batchTaskItem['mandate_id'])) {
          self::updateDDMandate('recurring_contribution', $batchTaskItem['mandate_id']);
        }

        self::recordContributionPayment($batchTaskItem['contribution_id']);
      }
      catch (Exception $e) {
        $errorMessage = 'Failed to process contribution with Id: ' . $batchTaskItem['contribution_id'] . ' - Error message : ' . $e->getMessage();
        $ctx->log->err($errorMessage);
      }
    }

    $totalExecutionTime = (microtime(true) - $processingStartTime);
    $endProcessingMessage = 'Finished processing the task In : ' . $totalExecutionTime . 'Seconds';
    $ctx->log->info($endProcessingMessage);

    return TRUE;
  }

  /**
   * Updates Direct Debits Mandates code.
   *
   * @param string $codeName
   * @param string $mandateId
   */
  private static function updateDDMandate($codeName, $mandateId) {
    $ddCodes = CRM_Core_OptionGroup::values('direct_debit_codes', FALSE, FALSE, FALSE, NULL, 'name');
    $query = 'UPDATE civicrm_value_dd_mandate SET civicrm_value_dd_mandate.dd_code = "' . array_search($codeName, $ddCodes) . '" WHERE civicrm_value_dd_mandate.id = ' . $mandateId;
    CRM_Core_DAO::executeQuery($query);
  }

  /**
   * Updates Contribution status and calls transition components to update
   * related entities (like memberships).
   *
   * @param int $contributionId
   */
  private static function recordContributionPayment($contributionId) {
    $originalStatusID = civicrm_api3('Contribution', 'getvalue', [
      'return' => 'contribution_status_id',
      'id' => $contributionId,
    ])['result'];

    $result = civicrm_api3('Contribution', 'create', [
      'id' => $contributionId,
      'contribution_status_id' => 'Completed',
      'payment_instrument_id' => 'direct_debit',
    ]);
    $contribution = array_shift($result['values']);

    CRM_Contribute_BAO_Contribution::transitionComponentWithReturnMessage($contribution['id'],
      $contribution['contribution_status_id'],
      $originalStatusID,
      $contribution['receive_date']
    );
  }

}
