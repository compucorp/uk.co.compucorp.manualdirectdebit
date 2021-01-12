<?php

class CRM_ManualDirectDebit_Queue_Build_InstructionsQueueBuilder extends CRM_ManualDirectDebit_Queue_Build_BaseBuilder {

  public function buildQueue() {
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
      if (count($this->taskItemRecords) >= $this->batchRecordsLimit) {
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
    $mandateIds = implode(', ', array_column($this->taskItemRecords, 'mandate_id'));
    $taskTitle = 'Processing the mandates with these Ids : ' . $mandateIds;

    $task = new CRM_Queue_Task(
      ['CRM_ManualDirectDebit_Queue_Task_BatchSubmission_InstructionItem', 'run'],
      [$this->taskItemRecords],
      $taskTitle
    );
    $this->queue->createItem($task);
  }

}
