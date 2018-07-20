<?php

/**
 * Checks if template is direct debit template
 */
class CRM_ManualDirectDebit_Common_MessageTemplate {

  const SIGN_UP_MSG_TITLE = 'Direct Debit Payment Sign Up Notification';

  const PAYMENT_UPDATE_MSG_TITLE = 'Direct Debit Payment Update Notification';

  const COLLECTION_REMINDER_MSG_TITLE = 'Direct Debit Payment Collection Reminder';

  const AUTO_RENEW_MSG_TITLE = 'Direct Debit Auto-renew Notification';

  const MANDATE_UPDATE_MSG_TITLE = 'Direct Debit Mandate Update Notification';

  /**
   * Checks if template is direct debit template
   *
   * @param $templateId
   *
   * @return bool
   */
  public static function isDirectDebitTemplate($templateId) {
    $query = "
      SELECT template.id
      FROM civicrm_msg_template AS template
      WHERE template.id = %1 
        AND template.msg_title in (%2, %3, %4, %5, %6)
    ";

    $dao = CRM_Core_DAO::executeQuery($query, [
      1 => [$templateId, 'Integer'],
      2 => [self::SIGN_UP_MSG_TITLE, 'String'],
      3 => [self::PAYMENT_UPDATE_MSG_TITLE, 'String'],
      4 => [self::COLLECTION_REMINDER_MSG_TITLE, 'String'],
      5 => [self::AUTO_RENEW_MSG_TITLE, 'String'],
      6 => [self::MANDATE_UPDATE_MSG_TITLE, 'String'],
    ]);

    while ($dao->fetch()) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Gets 'message template id' by title
   *
   * @param $title
   *
   * @return int|bool
   */
  public static function getMessageTemplateId($title) {
    $messageTemplate = civicrm_api3('MessageTemplate', 'get', [
      'sequential' => 1,
      'return' => ["id"],
      'msg_title' => $title
    ]);

    return $messageTemplate['count'] == 1 ? $messageTemplate['values'][0]['id'] : FALSE;
  }

  /**
   * Gets 'message template title' by id
   *
   * @param $idMessageTemplate
   *
   * @return int|bool
   */
  public static function getMessageTemplateTitle($idMessageTemplate) {
    $messageTemplate = civicrm_api3('MessageTemplate', 'get', [
      'sequential' => 1,
      'return' => ["msg_title"],
      'id' => $idMessageTemplate
    ]);

    return $messageTemplate['count'] == 1 ? $messageTemplate['values'][0]['msg_title'] : FALSE;
  }

}
