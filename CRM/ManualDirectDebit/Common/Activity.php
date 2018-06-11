<?php

/**
 * Creates activity
 */
class CRM_ManualDirectDebit_Common_Activity {

  /**
   * Creates activity
   *
   * @param string $subject
   * @param string $activityTypeName
   * @param $addedByContactId
   * @param $withContactId
   * @param $sourceRecordId
   *
   * @return int|bool
   */
  public static function create($subject, $activityTypeName, $sourceRecordId, $addedByContactId, $withContactId) {
    CRM_Core_Session::setStatus(
      ts('Subject "%1"', [1 => $subject]), ts("Activity was created")
    );
    $activityParams = [
      'subject' => $subject,
      'activity_type_id' => $activityTypeName,
      'activity_date_time' => date('YmdHis'),
      'target_id' => $withContactId,
      'source_record_id' => $sourceRecordId
    ];

    if (!empty($addedByContactId)) {
      $activityParams['source_contact_id'] = $addedByContactId;
    }

    try {
      $activity = civicrm_api3('Activity', 'create', $activityParams);
    }
    catch (CiviCRM_API3_Exception $e) {
      return FALSE;
    }

    return (int) $activity['id'];
  }


  /**
   * Gets activity type name by activity id
   *
   * @param $activityId
   *
   * @return bool|int
   */
  public static function getActivityTypeId($activityId) {
    if (empty($activityId)) {
      return FALSE;
    }

    try {
      $activityTypeId = civicrm_api3('Activity', 'getvalue', [
        'return' => "activity_type_id",
        'id' => $activityId,
      ]);

      return (int) $activityTypeId;
    } catch (CiviCRM_API3_Exception $e) {
      return FALSE;
    }
  }

  /**
   * Gets activity type name by activity id
   *
   * @param $activityId
   *
   * @return bool|String
   */
  public static function getActivityTypeName($activityId) {
    $activityTypeId = self::getActivityTypeId($activityId);
    if (!$activityTypeId) {
      return FALSE;
    }

    try {
      $activityName = civicrm_api3('OptionValue', 'getvalue', [
        'sequential' => 1,
        'return' => "name",
        'option_group_id' => 'activity_type',
        'value' => $activityTypeId,
        'options' => ['limit' => 1],
      ]);

      return (string) $activityName;
    } catch (CiviCRM_API3_Exception $e) {
      return FALSE;
    }
  }

  /**
   * Gets 'activity record id' by 'activity id'
   *
   * @param $activityId
   *
   * @return bool|String
   */
  public static function getActivityRecordId($activityId) {
    try {
      $activityRecordId = civicrm_api3('Activity', 'getvalue', [
        'sequential' => 1,
        'return' => "source_record_id",
        'id' => $activityId
      ]);

      return (int) $activityRecordId;
    } catch (CiviCRM_API3_Exception $e) {
      return FALSE;
    }
  }

}
