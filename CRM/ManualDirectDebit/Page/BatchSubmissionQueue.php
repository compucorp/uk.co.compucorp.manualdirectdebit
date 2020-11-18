<?php

use CRM_ManualDirectDebit_Queue_Build_InstructionsQueueBuilder as InstructionsQueueBuilder;
use CRM_ManualDirectDebit_Queue_Build_PaymentsQueueBuilder as PaymentsQueueBuilder;
use CRM_ManualDirectDebit_Batch_BatchHandler as BatchHandler;
use CRM_ManualDirectDebit_Common_MandateStorageManager as MandateStorageManager;

class CRM_ManualDirectDebit_Page_BatchSubmissionQueue extends CRM_Core_Page {

  private $queue;

  private $batchId;

  public function __construct($title = NULL, $mode = NULL) {
    parent::__construct($title, $mode);

    $this->queue = CRM_ManualDirectDebit_Queue_BatchSubmission::getQueue();
    $this->batchId = CRM_Utils_Request::retrieveValue('batchId', 'Int');
  }

  /**
   * Runs batch submission.
   */
  public function run() {
    try {
      $this->validateBatch();
      $this->addTasksToQueue();
      $this->runQueue();
    }
    catch (Exception $e) {
      CRM_Core_Session::setStatus($e->getMessage(), 'Error Submitting Batch!', 'error');
      $this->redirectToBatchViewPage();
    }
  }

  /**
   * Validates the batch is ok to be submitted.
   *
   * @throws \Exception
   */
  private function validateBatch() {
    $this->validBatchStatus();

    if ($this->getBatchType() === BatchHandler::BATCH_TYPE_CANCELLATIONS) {
      $this->validateCancellationsBatch();
    }
  }

  /**
   * Validates the status of the batch is appropriate to be submietted.
   *
   * Status of the batch needs to be either 'Opened' or 'Reopened' in order to
   * be submitted.
   *
   * @throws \Exception
   */
  private function validBatchStatus() {
    if (!in_array($this->getBatchStatus(), ['Open', 'Reopened'])) {
      throw new Exception('The batch is not in a valid status for submission');
    }
  }

  /**
   * Validates the batch to make sure there are no active mandates.
   *
   * @throws \Exception
   */
  private function validateCancellationsBatch() {
    $nonCancelledMandates = $this->getNonCancelledMandates();
    if (count($nonCancelledMandates) > 0) {
      $message = ts('The batch contains the following mandates that no longer have the status of 0C:');
      $message .= '<ul><li>';
      $message .= implode('</li><li>', $nonCancelledMandates);
      $message .= '</li></ul>';

      throw new Exception($message);
    }
  }

  /**
   * Obtains list of non-cancelled mandates for the batch.
   *
   * @return array
   * @throws \CiviCRM_API3_Exception
   */
  private function getNonCancelledMandates() {
    $result = civicrm_api3('EntityBatch', 'get', [
      'sequential' => 1,
      'batch_id' => $this->batchId,
      'entity_table' => MandateStorageManager::DIRECT_DEBIT_TABLE_NAME,
    ]);

    if ($result['count'] < 1) {
      return [];
    }

    $man = new MandateStorageManager();
    $ddCodes = CRM_Core_OptionGroup::values('direct_debit_codes', FALSE, FALSE, FALSE, NULL, 'name');

    $noncancelledMandates = [];
    foreach ($result['values'] as $batchItem) {
      $mandate = $man->getMandate($batchItem['entity_id']);
      if ($ddCodes[$mandate->dd_code] != MandateStorageManager::DD_CODE_NAME_CANCELDIRECTDEBIT) {
        $noncancelledMandates[] = $mandate->dd_ref;
      }
    }

    return $noncancelledMandates;
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
      case BatchHandler::BATCH_TYPE_INSTRUCTIONS:
        $queueBuilder = new InstructionsQueueBuilder($this->queue, $this->batchId);
        $queueBuilder->buildQueue();
        $this->addBatchSubmissionCompletionTask();
        break;

      case BatchHandler::BATCH_TYPE_PAYMENTS:
        $queueBuilder = new PaymentsQueueBuilder($this->queue, $this->batchId);
        $queueBuilder->buildQueue();
        $this->addBatchSubmissionCompletionTask();
        break;

      case BatchHandler::BATCH_TYPE_CANCELLATIONS:
        $this->addBatchSubmissionCompletionTask();
        break;
    }
  }

  private function getBatchType() {
    $batch = CRM_Batch_DAO_Batch::findById($this->batchId);
    $batchTypes = CRM_Core_PseudoConstant::get('CRM_Batch_DAO_Batch', 'type_id', ['labelColumn' => 'name']);

    return $batchTypes[$batch->type_id];
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
      'errorMode' => CRM_Queue_Runner::ERROR_ABORT,
      'onEnd' => array('CRM_ManualDirectDebit_Page_BatchSubmissionQueue', 'onEnd'),
      'onEndUrl' => CRM_Utils_System::url('civicrm/direct_debit/batch-transaction', ['reset' => 1, 'action' => 'view', 'bid' => $this->batchId]),
    ]);

    $runner->runAllViaWeb();
  }

  public static function onEnd(CRM_Queue_TaskContext $ctx) {
    $message = ts('Batch Submission Completed');
    CRM_Core_Session::setStatus($message, '', 'success');
  }

  /**
   * Redirects to BatchView Page.
   */
  private function redirectToBatchViewPage() {
    $redirectPath = 'civicrm/direct_debit/batch-transaction';
    $redirectParams = http_build_query(['reset' => 1, 'bid' => $this->batchId, 'action' => 'view']);
    $redirectURL = CRM_Utils_System::url($redirectPath, $redirectParams);

    CRM_Utils_System::redirect($redirectURL);
  }

}
