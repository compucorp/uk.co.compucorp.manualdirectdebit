<?php

class CRM_ManualDirectDebit_Page_BatchSubmissionQueue extends CRM_Core_Page {

  const BATCH_LIMIT = 50;

  private $queue;

  private $batchId;

  private $taskItemRecords = [];

  public function __construct($title = NULL, $mode = NULL) {
    parent::__construct($title, $mode);

    $this->queue = CRM_ManualDirectDebit_Queue_BatchSubmission::getQueue();
    $this->batchId =  CRM_Utils_Request::retrieveValue('batchId', 'Int');
  }

  public function run() {
    if (!$this->validBatchStatus()) {
      CRM_Core_Session::setStatus('The batch is not in a valid status for submission', '', 'error');

      return;
    }

    $this->addTasksToQueue();
    $this->runQueue();
  }

  private function validBatchStatus() {
    return in_array($this->getBatchStatus(), ['Open', 'Reopened']);
  }

  private function getBatchStatus() {
    $statusID = CRM_Core_DAO::getFieldValue('CRM_Batch_DAO_Batch', $this->batchId, 'status_id');
    $batchStatuses = CRM_Core_PseudoConstant::get('CRM_Batch_DAO_Batch', 'status_id', [
      'labelColumn' => 'name',
      'status' => " v.value={$statusID}",
    ]);
    return $batchStatuses[$statusID];
  }

  private function addTasksToQueue() {
    $batchType = $this->getBatchType();
    switch ($batchType) {
      case 'instructions_batch':
        $this->addInstructionsQueueTasks();
        break;
      case 'dd_payments':
        $this->addPaymentsQueueTasks();
        break;
    }

    $this->addBatchSubmissionCompletionTask();
  }

  private function getBatchType() {
    $batch = CRM_Batch_DAO_Batch::findById($this->batchId);
    $batchTypes = CRM_Core_PseudoConstant::get('CRM_Batch_DAO_Batch', 'type_id', ['labelColumn' => 'name']);
    return $batchTypes[$batch->type_id];
  }

  private function addInstructionsQueueTasks() {
    $rows = civicrm_api3('EntityBatch', 'get', [
      'return' => ['entity_id'],
      'options' => ['limit' => 0],
      'sequential' => 1,
      'batch_id' => $this->batchId,
    ]);

    if (empty($rows['values'])) {
      return;
    }
    $rows = $rows['values'];

    foreach ($rows as $row) {
      if (count($this->taskItemRecords) >= self::BATCH_LIMIT) {
        $this->addInstructionsQueueTaskItem();
        $this->taskItemRecords = [];
      }

      $mandateId = CRM_Utils_Array::value('entity_id', $row);
      $this->taskItemRecords[] = ['mandate_id' => $mandateId];
    }

    if (!empty($this->taskItemRecords)) {
      $this->addInstructionsQueueTaskItem();
    }
  }

  private function addInstructionsQueueTaskItem() {
    $task = new CRM_Queue_Task(
      ['CRM_ManualDirectDebit_Queue_Task_BatchSubmission_InstructionItem', 'run'],
      [$this->taskItemRecords],
      ''
    );
    $this->queue->createItem($task);
  }

  private function addPaymentsQueueTasks() {
    $sqlQuery = 'SELECT mandate.entity_id as contribution_id, mandate.mandate_id as mandate_id 
               FROM s1mev2civi_mstkv.civicrm_entity_batch entity_batch 
               LEFT JOIN civicrm_value_dd_information mandate ON entity_batch.entity_id = mandate.entity_id  
               WHERE entity_batch.batch_id = %1;';
    $result = CRM_Core_DAO::executeQuery($sqlQuery, [
      1 => [$this->batchId, 'Integer'],
    ]);

    while ($result->fetch()) {
      if (count($this->taskItemRecords) >= self::BATCH_LIMIT) {
        $this->addPaymentQueueTaskItem();
        $this->taskItemRecords = [];
      }

      $row = $result->toArray();
      $mandateId = CRM_Utils_Array::value('mandate_id', $row);
      $contributionId = CRM_Utils_Array::value('contribution_id', $row);
      $this->taskItemRecords[] = ['mandate_id' => $mandateId, 'contribution_id' => $contributionId];
    }

    if (!empty($this->taskItemRecords)) {
      $this->addPaymentQueueTaskItem();
    }
  }


  private function addPaymentQueueTaskItem() {
    $task = new CRM_Queue_Task(
      ['CRM_ManualDirectDebit_Queue_Task_BatchSubmission_PaymentItem', 'run'],
      [$this->taskItemRecords],
      ''
    );
    $this->queue->createItem($task);
  }

  private function addBatchSubmissionCompletionTask() {
    $task = new CRM_Queue_Task(
      ['CRM_ManualDirectDebit_Queue_Task_BatchSubmission_Completion', 'run'],
      [$this->batchId],
      'Batch Submission Complete'
    );
    $this->queue->createItem($task);
  }

  private function runQueue() {
    $runner = new CRM_Queue_Runner([
      'title' => ts('Submitting the batch, this may take a while depending on how many records are processed ..'),
      'queue' => $this->queue,
      'errorMode'=> CRM_Queue_Runner::ERROR_ABORT,
      'onEnd' => array('CRM_ManualDirectDebit_Page_BatchSubmissionQueue', 'onEnd'),
      'onEndUrl' => CRM_Utils_System::url('civicrm/direct_debit/batch-transaction', ['reset' => 1, 'action' => 'view', 'bid' => $this->batchId]),
    ]);

    $runner->runAllViaWeb();
  }

  public static function onEnd(CRM_Queue_TaskContext $ctx) {
    $message = ts('Batch Submission Completed');
    CRM_Core_Session::setStatus($message, '', 'success');
  }
}
