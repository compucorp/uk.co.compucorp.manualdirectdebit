<?php

class CRM_ManualDirectDebit_Common_CollectionReminderSendFlagManager {

  public static function setIsNotificationSentToUnsent($contributionId) {
    self::setIsNotificationSentValue($contributionId, 0);
  }

  public static function setIsNotificationSentToSent($contributionId) {
    self::setIsNotificationSentValue($contributionId, 1);
  }

  private static function setIsNotificationSentValue($contributionId, $value) {
    $isNotificationSentCustomFieldId = self::getIsNotificationSentCustomFieldId();
    if ($isNotificationSentCustomFieldId) {
      civicrm_api3('Contribution', 'create', [
        'id' => $contributionId,
        'custom_' . $isNotificationSentCustomFieldId => $value,
      ]);
    }
  }

  private static function getIsNotificationSentCustomFieldId() {
    try {
      return civicrm_api3('CustomField', 'getvalue', [
        'return' => 'id',
        'name' => 'is_notification_sent',
      ]);
    }
    catch (CRM_Core_Exception $e) {
      return NULL;
    }
  }

}
