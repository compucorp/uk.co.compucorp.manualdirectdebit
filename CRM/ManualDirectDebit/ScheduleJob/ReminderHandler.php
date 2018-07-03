<?php

/**
 * Handle reminder
 */
class CRM_ManualDirectDebit_ScheduleJob_ReminderHandler {

  /**
   * Requirements error message
   *
   * @var array
   */
  private $requirementsErrorMessage = '';

  /**
   * Init and handles reminder
   *
   * @return array
   */
  public function init() {
    $log = [];

    if (!$this->hasRequirements()) {
      $log['is_error'] = 1;
      $log['values'] = $this->requirementsErrorMessage;
      $log['error_message'] = $this->requirementsErrorMessage;

      return $log;
    }

    $reminder = new CRM_ManualDirectDebit_ScheduleJob_Reminder();
    $reminderLog = $reminder->run();
    $log['is_error'] = $reminder->isError();
    $log['values'] = $reminderLog;

    return $log;
  }

  /**
   * Check requirements
   *
   * @return bool
   */
  private function hasRequirements() {
    $requirements = TRUE;
    $reminderOffsetDays = CRM_ManualDirectDebit_ScheduleJob_ReminderOffsetDays::retrieve();
    if ($reminderOffsetDays === FALSE) {
      $requirements = FALSE;
      $this->requirementsErrorMessage = ts("'Days in advance for Collection Reminder' is required field. Please, fill it in Direct Debit Configuration.");
    }

    return $requirements;
  }

}
