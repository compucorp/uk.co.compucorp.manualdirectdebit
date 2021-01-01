<?php

class CRM_ManualDirectDebit_Queue_Task_BatchSubmission_Completion {

  public static function run(CRM_Queue_TaskContext $ctx, $batchId) {
    $session = CRM_Core_Session::singleton();
    $loggedInUserId = $session->get('userID');

    $sqlQuery = 'SELECT COUNT(*) AS item_count
               FROM civicrm_entity_batch
               WHERE civicrm_entity_batch.batch_id = %1;';
    $itemCount = CRM_Core_DAO::singleValueQuery($sqlQuery, [
      1 => [$batchId, 'Integer'],
    ]);

    civicrm_api3('Batch', 'create', [
      'id' => $batchId,
      'status_id' => 'Submitted',
      'modified_date' => date('YmdHis'),
      'modified_id' => $loggedInUserId,
      'item_count' => $itemCount,
    ]);

    $ctx->log->info('Changing batch with Id : ' . $batchId . ' to Submitted');

    return TRUE;
  }

}
