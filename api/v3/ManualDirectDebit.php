<?php

/**
 * This API get called when run schedule job "Send Direct Debit Payment Collection Reminders"
 *
 * @param $params
 *
 * @return mixed
 * @throws \Exception
 */
function civicrm_api3_manual_direct_debit_run($params) {
  return (new CRM_ManualDirectDebit_ScheduleJob_ReminderHandler())->init();
}
