<?php

class CRM_ManualDirectDebit_Queue_Task_BatchSubmission_Completion {

  public static function run(CRM_Queue_TaskContext $ctx, $batchId) {
    $session = CRM_Core_Session::singleton();
    $loggedInUserId = $session->get('userID');

    civicrm_api3('Batch', 'create', [
      'id' => $batchId,
      'status_id' => 'Submitted',
      'modified_date' => date('YmdHis'),
      'modified_id' => $loggedInUserId
    ]);

    return TRUE;
  }

}
