<?php

class CRM_ManualDirectDebit_Queue_Task_BatchSubmission {

  public static function run(CRM_Queue_TaskContext $ctx, $batchId) {
    $batchHandler = new CRM_ManualDirectDebit_Batch_BatchHandler($batchId);
    if ($batchHandler->submitBatch()) {
      $session = CRM_Core_Session::singleton();
      $loggedInUserId = $session->get('userID');

      civicrm_api3('Batch', 'create', [
        'id' => $batchId,
        'status_id' => 'Submitted',
        'modified_date' => date('YmdHis'),
        'modified_id' => $loggedInUserId
      ]);
    }

    return TRUE;
  }

}
