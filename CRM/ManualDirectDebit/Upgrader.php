<?php

use CRM_ManualDirectDebit_ExtensionUtil as E;

/**
 * Collection of upgrade steps.
 */
class CRM_ManualDirectDebit_Upgrader extends CRM_ManualDirectDebit_Upgrader_Base {

  /**
   * Message template param list
   *
   * @var array
   */
  public $messageTemplateParamList = [];

  public function install() {
    $this->createMessageTemplates();
    $this->createDirectDebitNavigationMenu();
    $this->createDirectDebitPaymentInstrument();
    $this->createDirectDebitPaymentProcessorType();
    $this->createDirectDebitPaymentProcessor();

    /**
     *  ONLY FOR DEVELOPMENT PURPOSE
     */
    $development = new CRM_ManualDirectDebit_Common_DEVELOPMENT();
    $development->installExtraDataForTesting();
  }

  /**
   * Sets message template param list
   */
  public function setMessageTemplateParamList() {
    $this->messageTemplateParamList = [
      [
        'filePath' => $this->extensionDir . "/templates/CRM/ManualDirectDebit/MessageTemplate/PaymentSignUpNotification.tpl",
        'title' => 'Direct Debit Payment Sign Up Notification',
        'subject' => ts('Direct Debit Payment Sign Up Notification')
      ],
      [
        'filePath' => $this->extensionDir . "/templates/CRM/ManualDirectDebit/MessageTemplate/PaymentUpdateNotification.tpl",
        'title' => 'Direct Debit Payment Update Notification',
        'subject' => ts('Direct Debit Payment Update Notification')
      ],
      [
        'filePath' => $this->extensionDir . "/templates/CRM/ManualDirectDebit/MessageTemplate/PaymentCollectionReminder.tpl",
        'title' => 'Direct Debit Payment Collection Reminder',
        'subject' => ts('Direct Debit Payment Collection Reminder')
      ],
      [
        'filePath' => $this->extensionDir . "/templates/CRM/ManualDirectDebit/MessageTemplate/AutoRenewNotification.tpl",
        'title' => 'Direct Debit Auto-renew Notification',
        'subject' => ts('Direct Debit Auto-renew Notification')
      ],
      [
        'filePath' => $this->extensionDir . "/templates/CRM/ManualDirectDebit/MessageTemplate/MandateUpdateNotification.tpl",
        'title' => 'Direct Debit Mandate Update Notification',
        'subject' => ts('Direct Debit Mandate Update Notification')
      ],
    ];
  }

  /**
   * Creates message templates
   *
   * @throws \CiviCRM_API3_Exception
   */
  private function createMessageTemplates() {
    $this->setMessageTemplateParamList();

    foreach ($this->messageTemplateParamList as $messageTemplateParam) {
      $this->createMessageTemplate($messageTemplateParam);
    }
  }

  /**
   * Creates message template
   *
   * @param $params
   *
   * @throws \CiviCRM_API3_Exception
   */
  private function createMessageTemplate($params) {
    $messageHtml = '';
    if (file_exists($params['filePath'])) {
      $messageHtml = file_get_contents($params['filePath']);
    }
    else {
      CRM_Core_Session::setStatus(
        ts('Creating message template'),
        ts("Couldn't find default template at '". $params['filePath'] . "'"),
        'alert'
      );
    }

    civicrm_api3('MessageTemplate', 'create', [
      'msg_title' => $params['title'],
      'msg_subject' => $params['subject'],
      'is_reserved' => 0,
      'msg_html' => $messageHtml,
      'is_active' => 1,
      'msg_text' => 'N/A'
    ]);
  }

  public function uninstall() {
    $this->deleteMessageTemplates();
    $this->uninstallCustomInformation();
  }

  /**
   * Deletes 'CiviCRM Direct Debit' message template
   *
   * @throws \CiviCRM_API3_Exception
   */
  private function deleteMessageTemplates() {
    $this->setMessageTemplateParamList();

    foreach ($this->messageTemplateParamList as $messageTemplateParam) {
      civicrm_api3('MessageTemplate', 'get', [
        'msg_title' => $messageTemplateParam['title'],
        'api.MessageTemplate.delete' => ['id' => '$value.id'],
      ]);
    }
  }

  /**
   *  Uninstall custom information
   */
  private function uninstallCustomInformation() {
    $customValuesForUninstall = [
      [
        "entityType" => "OptionGroup",
        "searchValue" => "direct_debit_codes",
      ],
      [
        "entityType" => "OptionGroup",
        "searchValue" => "direct_debit_originator_number",
      ],
      [
        "entityType" => "CustomGroup",
        "searchValue" => "direct_debit_mandate",
      ],
      [
        "entityType" => "CustomGroup",
        "searchValue" => "direct_debit_information",
      ],
      [
        "entityType" => "UFGroup",
        "searchValue" => "Direct Debit Information",
        "searchField" => "title",
      ],
      [
        "entityType" => "OptionValue",
        "searchValue" => "direct_debit",
      ],
      [
        "entityType" => "PaymentProcessor",
        "searchValue" => "OfflineDirectDebit",
        "searchField" => "payment_processor_type_id",
      ],
      [
        "entityType" => "PaymentProcessorType",
        "searchValue" => "OfflineDirectDebit",
      ],
    ];

    foreach ($customValuesForUninstall as $customValue) {
      $this->deleteEntityRecord(
        $customValue['entityType'],
        $customValue['searchValue'],
        isset($customValue['searchField']) ? $customValue['searchField'] : 'name'
      );
    }
  }

  /**
   * Deletes a record for the specified CiviCRM entity based on
   * the search field and search value.
   *
   * @param string $entityType
   *   The entity type for the record we want to remove (e.g OptionValue,
   *   PaymentProcessor ..etc). it should be a valid Custom or CiviCRM core
   *   entity type.
   * @param string $searchValue
   *   The search value to find the record that we want to remove
   * @param string $searchField
   *   The field that we search the search value against.
   */
  private function deleteEntityRecord($entityType, $searchValue, $searchField) {
    civicrm_api3($entityType, 'get', [
      $searchField => $searchValue,
      'api.' . $entityType . '.delete' => ['id' => '$value.id'],
    ]);
  }

  /**
   * Installs the 'Direct Debit' payment instrument
   */
  private function createDirectDebitPaymentInstrument() {
    $paymentInstrument = [
      'option_group_id' => "payment_instrument",
      'label' => "Direct Debit",
      'name' => "direct_debit",
    ];
    civicrm_api3('OptionValue', 'create', $paymentInstrument);
  }

  /**
   * Installs the 'Direct Debit' payment processor Type
   */
  private function createDirectDebitPaymentProcessorType() {
    $paymentProcessorType = [
      "name" => "OfflineDirectDebit",
      "title" => ts("Offline Direct Debit"),
      "description" => ts("Payment processor"),
      "is_active" => 1,
      "user_name_label" => ts("Offline Direct Debit"),
      "class_name" => "Payment_Manual",
      "billing_mode" => CRM_Core_Payment::BILLING_MODE_BUTTON,
      "is_recur" => "1",
      "payment_type" => CRM_Core_Payment::PAYMENT_TYPE_DIRECT_DEBIT,
    ];
    civicrm_api3('PaymentProcessorType', 'create', $paymentProcessorType);
  }

  /**
   * Installs the 'Direct Debit' payment processor
   */
  private function createDirectDebitPaymentProcessor() {
    $paymentProcessor = [
      'name' => ts('Direct Debit'),
      'description' => '',
      'payment_processor_type_id' => 'OfflineDirectDebit',
      'domain_id' => CRM_Core_Config::domainID(),
      'is_active' => 1,
      'payment_instrument_id' => 'direct_debit',
    ];
    civicrm_api3('PaymentProcessor', 'create', $paymentProcessor);
  }

  /**
   * Creates Direct Debit navigation menu items
   */
  private function createDirectDebitNavigationMenu() {
    $batchTypes = CRM_Core_OptionGroup::values('batch_type', FALSE, FALSE, FALSE, NULL, 'name');
    $menuItems = [
        [
          'label' => ts('Direct Debit'),
          'name' => 'direct_debit',
          'url' => NULL,
          'permission' => 'can manage direct debit batches',
          'separator' => NULL,
          'parent_name' => 'menumain',
        ],
        [
          'label' => ts('Create New Instructions Batch'),
          'name' => 'create_new_instructions_batch',
          'url' => 'civicrm/direct_debit/batch?reset=1&action=add&type_id=' . array_search('instructions_batch', $batchTypes),
          'permission' => 'can manage direct debit batches',
          'separator' => 1,
          'parent_name' => 'direct_debit',
        ],
        [
          'label' => ts('Export Direct Debit Payments'),
          'name' => 'export_direct_debit_payments',
          'url' => 'civicrm/direct_debit/batch?reset=1&action=add&type_id=' . array_search('dd_payments', $batchTypes),
          'permission' => 'can manage direct debit batches',
          'operator' => NULL,
          'separator' => NULL,
          'parent_name' => 'direct_debit',
        ],
        [
          'label' => ts('View New Instruction Batches'),
          'name' => 'view_new_instruction_batches',
          'url' => 'civicrm/direct_debit/batch-list?reset=1&type_id=' . array_search('instructions_batch', $batchTypes),
          'permission' => 'can manage direct debit batches',
          'operator' => NULL,
          'separator' => 1,
          'parent_name' => 'direct_debit',
        ],
        [
          'label' => ts('View Payment Batches'),
          'name' => 'view_payment_batches',
          'url' => 'civicrm/direct_debit/batch-list?reset=1&type_id=' . array_search('dd_payments', $batchTypes),
          'permission' => 'can manage direct debit batches',
          'operator' => NULL,
          'separator' => NULL,
          'parent_name' => 'direct_debit',
        ],
      ];

    foreach ($menuItems as $item) {
      $this->addNav($item);
    }
    CRM_Core_BAO_Navigation::resetNavigation();
  }

  /**
   * Adds navigation menu item
   *
   * @param array $menuItem
   */
  private function addNav($menuItem) {
    $this->removeNav($menuItem['name']);
    $menuItem['is_active'] = 1;
    $menuItem['parent_id'] = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_Navigation', $menuItem['parent_name'], 'id', 'name');
    unset($menuItem['parent_name']);
    CRM_Core_BAO_Navigation::add($menuItem);
  }

  /**
   * Removes navigation menu item
   *
   * @param string $name
   *   The name of the item in `civicrm_navigation`.
   */
  private function removeNav($name) {
    CRM_Core_DAO::executeQuery("DELETE FROM `civicrm_navigation` WHERE name IN (%1)", [
      1 => [$name, 'String'],
    ]);
  }

}
