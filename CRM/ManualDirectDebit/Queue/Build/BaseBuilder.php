<?php

abstract class CRM_ManualDirectDebit_Queue_Build_BaseBuilder {

  protected $batchId;

  protected $queue;

  protected $taskItemRecords = [];

  protected $batchRecordsLimit;

  /**
   * CRM_ManualDirectDebit_Queue_Build_BaseBuilder constructor.
   *
   * @param CRM_Queue_Queue $queue
   * @param int $batchId
   */
  public function __construct($queue, $batchId) {
    $this->queue = $queue;
    $this->batchId = $batchId;
    $this->batchRecordsLimit = CRM_ManualDirectDebit_Common_SettingsManager::getBatchSubmissionRecordsPerTaskLimit();
  }

  abstract public function buildQueue();

}
