<?php

/**
 * Gets reminder offset in days from settings
 */
class CRM_ManualDirectDebit_ScheduleJob_ReminderOffsetDays {
  /**
   * Reminder offset in days
   *
   * @var null|int|false
   */
  private static $reminderOffsetDays = NULL;

  /**
   * Retrieves reminder offset in days
   *
   * @return string
   */
  public static function retrieve() {
    if (is_null(self::$reminderOffsetDays)) {
      self::$reminderOffsetDays = self::getReminderOffsetDays();
    }

    return self::$reminderOffsetDays;
  }

  /**
   * Gets reminder offset in seconds from settings
   *
   * @return string
   */
  private static function getReminderOffsetDays() {
    $domainID = CRM_Core_Config::domainID();
    $settingName = "manualdirectdebit_days_in_advance_for_collection_reminder";
    try {
      $settings = civicrm_api3('setting', 'get', [
        'return' => [$settingName],
      ]);
    }
    catch (CiviCRM_API3_Exception $e) {
      return FALSE;
    }

    if (isset($settings['values'][$domainID][$settingName])) {
      return (int) $settings['values'][$domainID][$settingName];
    }

    return FALSE;
  }

}
