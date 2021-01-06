<?php

class CRM_ManualDirectDebit_Queue_BatchSubmission {

  const QUEUE_NAME = 'uk.co.compucorp.manualdirectdebit.queue.batchsubmission';

  private static $queue;

  public static function getQueue() {
    if (!self::$queue) {
      self::$queue = CRM_Queue_Service::singleton()->create([
        'type' => 'Sql',
        'name' => self::QUEUE_NAME,
        'reset' => FALSE,
      ]);
    }

    return self::$queue;
  }

}
