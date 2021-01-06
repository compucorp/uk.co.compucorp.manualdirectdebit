<?php

class CRM_ManualDirectDebit_Queue_Task_BatchSubmission_InstructionItem {

  public static function run(CRM_Queue_TaskContext $ctx, $batchTaskItems) {
    $processingStartTime = microtime(TRUE);

    foreach ($batchTaskItems as $batchTaskItem) {
      try {
        self::updateDDMandate('first_time_payment', $batchTaskItem['mandate_id']);;
      }
      catch (Exception $e) {
        $errorMessage = 'Failed to process mandate with Id: ' . $batchTaskItem['mandate_id'] . ' - Error message : ' . $e->getMessage();
        $ctx->log->err($errorMessage);
      }
    }

    $totalExecutionTime = (microtime(TRUE) - $processingStartTime);
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

}
