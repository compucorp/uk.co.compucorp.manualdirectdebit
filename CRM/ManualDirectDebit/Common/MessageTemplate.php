<?php

/**
 * Checks if template is direct debit template
 */
class CRM_ManualDirectDebit_Common_MessageTemplate {

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
      2 => ['Direct Debit Payment Sign Up Notification', 'String'],
      3 => ['Direct Debit Payment Update Notification', 'String'],
      4 => ['Direct Debit Payment Collection Reminder', 'String'],
      5 => ['Direct Debit Auto-renew Notification', 'String'],
      6 => ['Direct Debit Mandate Update Notification', 'String'],
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
   * @return bool
   */
  public static function getMessageTemplateId($title) {
    $query = "
      SELECT template.id AS template_id
      FROM civicrm_msg_template AS template
      WHERE template.id = %1
    ";

    $dao = CRM_Core_DAO::executeQuery($query, [1 => [$title, 'String']]);

    while ($dao->fetch()) {
      return $dao->template_id;
    }

    return FALSE;
  }

}
