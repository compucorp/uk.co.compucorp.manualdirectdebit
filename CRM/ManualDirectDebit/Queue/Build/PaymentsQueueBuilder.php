<?php

class CRM_ManualDirectDebit_Queue_Build_PaymentsQueueBuilder extends CRM_ManualDirectDebit_Queue_Build_BaseBuilder {

  public function buildQueue() {
    $sqlQuery = 'SELECT mandate.entity_id as contribution_id, mandate.mandate_id as mandate_id 
               FROM civicrm_entity_batch entity_batch 
               LEFT JOIN civicrm_value_dd_information mandate ON entity_batch.entity_id = mandate.entity_id  
               WHERE entity_batch.batch_id = %1;';
    $result = CRM_Core_DAO::executeQuery($sqlQuery, [
      1 => [$this->batchId, 'Integer'],
    ]);

    while ($result->fetch()) {
      if (count($this->taskItemRecords) >= $this->batchRecordsLimit) {
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
    $contributionIds = implode(', ', array_column($this->taskItemRecords, 'contribution_id'));
    $taskTitle = 'Processing the contributions with these Ids : ' . $contributionIds;

    $task = new CRM_Queue_Task(
      ['CRM_ManualDirectDebit_Queue_Task_BatchSubmission_PaymentItem', 'run'],
      [$this->taskItemRecords],
      $taskTitle
    );
    $this->queue->createItem($task);
  }

}
