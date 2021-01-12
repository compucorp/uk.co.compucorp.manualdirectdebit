<?php

/**
 * Checks if template is direct debit template
 */
class CRM_ManualDirectDebit_Common_MessageTemplate {

  const SIGN_UP_MSG_NAME = 'payment_sign_up_notification';

  const PAYMENT_UPDATE_MSG_NAME = 'payment_update_notification';

  const COLLECTION_REMINDER_MSG_NAME = 'payment_collection_reminder';

  const AUTO_RENEW_MSG_NAME = 'auto_renew_notification';

  const MANDATE_UPDATE_MSG_NAME = 'mandate_update_notification';

  public static function getDefaultDirectDebitTemplates() {
    return [
        [
          'name' => self::SIGN_UP_MSG_NAME,
          'templateFile' => 'PaymentSignUpNotification.tpl',
          'title' => 'Direct Debit Payment Sign Up Notification',
        ],
        [
          'name' => self::PAYMENT_UPDATE_MSG_NAME,
          'templateFile' => 'PaymentUpdateNotification.tpl',
          'title' => 'Direct Debit Payment Update Notification',
        ],
        [
          'name' => self::COLLECTION_REMINDER_MSG_NAME,
          'templateFile' => 'PaymentCollectionReminder.tpl',
          'title' => 'Direct Debit Payment Collection Reminder',
        ],
        [
          'name' => self::AUTO_RENEW_MSG_NAME,
          'templateFile' => 'AutoRenewNotification.tpl',
          'title' => 'Direct Debit Auto-renew Notification',
        ],
        [
          'name' => self::MANDATE_UPDATE_MSG_NAME,
          'templateFile' => 'MandateUpdateNotification.tpl',
          'title' => 'Direct Debit Mandate Update Notification',
        ],
    ];
  }

  /**
   * Checks if template is direct debit template
   *
   * @param $templateId
   *
   * @return bool
   */
  public static function isDirectDebitTemplate($templateId) {
    $isDDTemplateCustomFieldId = civicrm_api3('CustomField', 'getvalue', [
      'return' => 'id',
      'custom_group_id' => 'direct_debit_message_template',
      'name' => 'is_direct_debit_template',
    ]);

    $template = civicrm_api3('MessageTemplate', 'get', [
      'sequential' => 1,
      'id' => $templateId,
      'custom_' . $isDDTemplateCustomFieldId => 1,
    ]);

    if (empty($template['id'])) {
      return FALSE;
    }

    return TRUE;
  }

  public static function getMessageTemplateIdByTitle($title) {
    $messageTemplate = civicrm_api3('MessageTemplate', 'get', [
      'sequential' => 1,
      'return' => ['id'],
      'msg_title' => $title,
    ]);

    return $messageTemplate['count'] == 1 ? $messageTemplate['values'][0]['id'] : FALSE;
  }

  public static function getTemplateIdByName($templateName) {
    $machineNameCustomFieldId = civicrm_api3('CustomField', 'getvalue', [
      'return' => 'id',
      'custom_group_id' => 'direct_debit_message_template',
      'name' => 'template_machine_name',
    ]);

    $template = civicrm_api3('MessageTemplate', 'get', [
      'sequential' => 1,
      'custom_' . $machineNameCustomFieldId => $templateName,
    ]);

    if (empty($template['id'])) {
      return NULL;
    }

    return $template['id'];
  }

}
