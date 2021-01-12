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
    $activityParams = [
      'subject' => $subject,
      'activity_type_id' => $activityTypeName,
      'activity_date_time' => date('YmdHis'),
      'target_id' => $withContactId,
      'source_record_id' => $sourceRecordId,
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

}
