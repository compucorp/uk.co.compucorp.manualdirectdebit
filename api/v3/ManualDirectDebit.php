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

function civicrm_api3_manual_direct_debit_deletemandate($params) {
  try {
    $mandateManager = new CRM_ManualDirectDebit_Common_MandateStorageManager();
    $mandateManager->deleteMandate($params['mandate_id']);
  } catch (Exception $e) {
    return civicrm_api3_create_error($e->getMessage(), $params);
  }

  return civicrm_api3_create_success(
    TRUE,
    $params
  );
}
