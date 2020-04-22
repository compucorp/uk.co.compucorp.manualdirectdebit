<?php

class CRM_ManualDirectDebit_Page_BatchSubmissionQueue extends CRM_Core_Page {

  private $queue;

  private $batchId;

  public function __construct($title = NULL, $mode = NULL) {
    parent::__construct($title, $mode);

    $this->queue = CRM_ManualDirectDebit_Queue_BatchSubmission::getQueue();
    $this->batchId =  CRM_Utils_Request::retrieveValue('batchId', 'Int');
  }

  public function run() {
    $this->enqueueBatchSubmissionTask();
    $this->runQueue();
  }

  private function enqueueBatchSubmissionTask() {
    $task = new CRM_Queue_Task(
      ['CRM_ManualDirectDebit_Queue_Task_BatchSubmission', 'run'],
      [$this->batchId]
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
