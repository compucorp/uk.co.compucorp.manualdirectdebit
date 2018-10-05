<?php

use CRM_ManualDirectDebit_ExtensionUtil as E;

/**
 * Collection of upgrade steps.
 */
class CRM_ManualDirectDebit_Upgrader extends CRM_ManualDirectDebit_Upgrader_Base {

  /**
   * List of option values
   *
   * @var array
   */
  private $optionValues = [
    [
      "entityType" => "OptionValue",
      "searchValue" => "direct_debit",
      "optionGroup" => "payment_instrument",
    ],
    [
      "entityType" => "OptionValue",
      "searchValue" => "instructions_batch",
      "optionGroup" => "batch_type",
    ],
    [
      "entityType" => "OptionValue",
      "searchValue" => "dd_payments",
      "optionGroup" => "batch_type",
    ],
    [
      "entityType" => "OptionValue",
      "searchValue" => "Submitted",
      "optionGroup" => "batch_status",
    ],
    [
      "entityType" => "OptionValue",
      "searchValue" => "Discarded",
      "optionGroup" => "batch_status",
    ],
    [
      "entityType" => "OptionValue",
      "searchValue" => "new_direct_debit_recurring_payment",
      "optionGroup" => "activity_type",
    ],
    [
      "entityType" => "OptionValue",
      "searchValue" => "update_direct_debit_recurring_payment",
      "optionGroup" => "activity_type",
    ],
    [
      "entityType" => "OptionValue",
      "searchValue" => "direct_debit_payment_reminder",
      "optionGroup" => "activity_type",
    ],
    [
      "entityType" => "OptionValue",
      "searchValue" => "offline_direct_debit_auto_renewal",
      "optionGroup" => "activity_type",
    ],
    [
      "entityType" => "OptionValue",
      "searchValue" => "direct_debit_mandate_update",
      "optionGroup" => "activity_type",
    ],
  ];

  /**
   * List of scheduled jobs
   *
   * @var array
   */
  private $scheduledJobs = [
    [
      "entityType" => "Job",
      "searchValue" => "Send Direct Debit Payment Collection Reminders",
    ],
  ];

  /**
   * List of option groups
   *
   * @var array
   */
  private $optionGroups = [
    [
      "entityType" => "OptionGroup",
      "searchValue" => "direct_debit_codes",
    ],
    [
      "entityType" => "OptionGroup",
      "searchValue" => "direct_debit_originator_number",
    ],
  ];


  /**
   * List of processor types
   *
   * @var array
   */
  private $processorTypes = [
    [
      "entityType" => "PaymentProcessorType",
      "searchValue" => "OfflineDirectDebit",
    ],
  ];

  /**
   * List of UF groups
   *
   * @var array
   */
  private $ufGroups = [
    [
      "entityType" => "UFGroup",
      "searchValue" => "Direct Debit Information",
      "searchField" => "title",
    ],
  ];

  /**
   * List of custom groups
   *
   * @var array
   */
  private $customGroups = [
    "direct_debit_mandate",
    "direct_debit_information",
  ];

  /**
   * Message template param list
   *
   * @var array
   */
  public $messageTemplateParamList = [];

  public function install() {
    $this->createScheduledJob();
    $this->createMessageTemplates();
    $this->createDirectDebitNavigationMenu();
    $this->createDirectDebitPaymentInstrument();
    $this->createDirectDebitPaymentProcessorType();
    $this->createDirectDebitPaymentProcessor();
  }

  public function upgrade_0007() {
    $this->setDefaultDaysToBatchContributionsInAdvanceSetting();
    return TRUE;
  }

  private function setDefaultDaysToBatchContributionsInAdvanceSetting() {
    $configFields = CRM_ManualDirectDebit_Common_SettingsManager::getConfigFields();
    civicrm_api3('setting', 'create', [
      'manualdirectdebit_days_to_batch_contributions_in_advance' =>
        $configFields['manualdirectdebit_days_to_batch_contributions_in_advance']['default']
    ]);
  }

  public function upgrade_0006() {
    try {
      $this->createMessageTemplates();

      return TRUE;
    } catch (CiviCRM_API3_Exception $e) {
      return FALSE;
    }
  }

  public function upgrade_0005() {
    $this->setDefaultMinimumMandateReferenceLength();
    return TRUE;
  }

  private function setDefaultMinimumMandateReferenceLength() {
    $configFields = CRM_ManualDirectDebit_Common_SettingsManager::getConfigFields();
    civicrm_api3('setting', 'create', [
        'manualdirectdebit_minimum_reference_prefix_length' =>
        $configFields['manualdirectdebit_minimum_reference_prefix_length']['default']
      ]);
  }

  public function upgrade_0004() {
    try {
      $this->createMessageTemplates();

      return TRUE;
    } catch (CiviCRM_API3_Exception $e) {
      return FALSE;
    }
  }

  public function upgrade_0003() {
    try {
      $this->createScheduledJob();

      return TRUE;
    } catch (Exception $e) {
      return FALSE;
    }
  }

  /**
   * Sets message template param list
   */
  public function setMessageTemplateParamList() {
    $templates = [
      CRM_ManualDirectDebit_Common_MessageTemplate::SIGN_UP_MSG_TITLE => 'PaymentSignUpNotification.tpl',
      CRM_ManualDirectDebit_Common_MessageTemplate::PAYMENT_UPDATE_MSG_TITLE => 'PaymentUpdateNotification.tpl',
      CRM_ManualDirectDebit_Common_MessageTemplate::COLLECTION_REMINDER_MSG_TITLE => 'PaymentCollectionReminder.tpl',
      CRM_ManualDirectDebit_Common_MessageTemplate::AUTO_RENEW_MSG_TITLE => 'AutoRenewNotification.tpl',
      CRM_ManualDirectDebit_Common_MessageTemplate::MANDATE_UPDATE_MSG_TITLE => 'MandateUpdateNotification.tpl',
    ];

    foreach ($templates as $title => $fileName) {
      $this->messageTemplateParamList[] = [
        'filePath' => $this->extensionDir . "/templates/CRM/ManualDirectDebit/MessageTemplate/$fileName",
        'title' => $title,
        'subject' => ts($title),
      ];
    }
  }

  /**
   * Creates message templates
   */
  private function createMessageTemplates() {
    $this->setMessageTemplateParamList();

    foreach ($this->messageTemplateParamList as $messageTemplateParam) {
      if($this->isEntityAlreadyExist("MessageTemplate", $messageTemplateParam['title'], 'msg_title')){
        $this->deleteMessageTemplate($messageTemplateParam['title']);
      }

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
        ts("Couldn't find default template at '" . $params['filePath'] . "'"),
        'alert'
      );
    }

    civicrm_api3('MessageTemplate', 'create', [
      'msg_title' => $params['title'],
      'msg_subject' => $params['subject'],
      'is_reserved' => 0,
      'msg_html' => $messageHtml,
      'is_active' => 1,
      'msg_text' => 'N/A',
    ]);
  }

  /**
   * Deletes 'CiviCRM Direct Debit' message template
   *
   * @throws \CiviCRM_API3_Exception
   */
  private function deleteMessageTemplates() {
    $this->setMessageTemplateParamList();

    foreach ($this->messageTemplateParamList as $messageTemplateParam) {
      $this->deleteMessageTemplate($messageTemplateParam['title']);
    }
  }

  /**
   * Installs scheduled job
   */
  private function createScheduledJob() {
    $domainID = CRM_Core_Config::domainID();

    if($this->isEntityAlreadyExist("Job", 'Send Direct Debit Payment Collection Reminders', 'name')){
      $this->alterEntity('Job','Send Direct Debit Payment Collection Reminders','name',FALSE,'uninstall');
    }

    $params = [
      'name' => 'Send Direct Debit Payment Collection Reminders',
      'description' => 'Send Direct Debit Payment Collection Reminders',
      'api_entity' => 'ManualDirectDebit',
      'api_action' => 'run',
      'run_frequency' => 'Daily',
      'domain_id' => $domainID,
      'is_active' => 0,
      'parameters' => '',
    ];

    CRM_Core_BAO_Job::create($params);
  }

  /**
   * Checks if entity exists
   *
   * @param $entityType
   * @param $searchValue
   * @param $searchField
   *
   * @return bool
   */
  private function isEntityAlreadyExist($entityType, $searchValue, $searchField) {
    $result = civicrm_api3($entityType, 'getcount', [
      $searchField => $searchValue,
    ]);

    return $result >= 1;
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
        'label' => ts('Create Payment Collection Batch'),
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
        'label' => ts('View Payment Collection Batches'),
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

  /**
   * Installs the 'Direct Debit' payment instrument
   */
  private function createDirectDebitPaymentInstrument() {
    $paymentInstrument = [
      'option_group_id' => "payment_instrument",
      'label' => "Direct Debit",
      'name' => "direct_debit",
      'is_active' => 0,
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
      "user_name_label" => ts("User Name"),
      "class_name" => "Payment_Manual",
      "billing_mode" => CRM_Core_Payment::BILLING_MODE_BUTTON,
      "is_recur" => "1",
      "payment_type" => CRM_Core_Payment::PAYMENT_TYPE_DIRECT_DEBIT,
      'is_active' => 0,
    ];
    civicrm_api3('PaymentProcessorType', 'create', $paymentProcessorType);
  }

  /**
   * Installs the 'Direct Debit' payment processor
   */
  private function createDirectDebitPaymentProcessor() {
    $defaultProcessorPrams = [
      'name' => 'Direct Debit',
      'description' => '',
      'payment_processor_type_id' => 'OfflineDirectDebit',
      'domain_id' => CRM_Core_Config::domainID(),
      'payment_instrument_id' => 'direct_debit',
      'is_active' => 1,
      'class_name' => 'Payment_Manual',
      'is_recur' => '1',
    ];

    $paramsPerType = [
      'live' => [
        'is_test' => '0',
        'user_name' => 'Live',
        'url_site' => 'https://live.civicrm.org',
        'url_recur' => 'https://liverecurr.civicrm.org',
      ],
      'test' => [
        'is_test' => '1',
        'user_name' => 'Test',
        'url_site' => 'https://test.civicrm.org',
        'url_recur' => 'https://testrecurr.civicrm.org',
      ],
    ];

    foreach($paramsPerType as $typeParams) {
      $params = array_merge($defaultProcessorPrams, $typeParams);
      civicrm_api3('PaymentProcessor', 'create', $params);
    }
  }

  public function postInstall() {
    $this->setDefaultSettingValues();
  }

  private function setDefaultSettingValues() {
    $configFields = CRM_ManualDirectDebit_Common_SettingsManager::getConfigFields();
    foreach ($configFields  as $name => $config) {
      civicrm_api3('setting', 'create', [$name => $config['default']]);
    }
  }

  public function onEnable() {
    $this->alterNavigationMenu("direct_debit", "enable");
    $this->alterEntitiesValues('enable');
    $this->alterCustomGroups('enable');
  }

  public function uninstall() {
    $this->deletePaymentProcessor();
    $this->alterEntitiesValues('uninstall');
    $this->alterCustomGroups('uninstall');
    $this->deleteDirectDebitNavigationMenu();
    $this->deleteMessageTemplates();
  }

  public function onDisable() {
    $this->alterNavigationMenu("direct_debit", "disable");
    $this->alterEntitiesValues('disable');
    $this->alterCustomGroups('disable');
  }

  /**
   * Alters navigation menu
   *
   * @param $menuItem
   * @param $action
   */
  private function alterNavigationMenu($menuItem, $action) {
    $isActive = $action === 'enable' ? 1 : 0;
    CRM_Core_DAO::executeQuery("UPDATE civicrm_navigation SET is_active = %1 WHERE name = %2", [
      1 => [$isActive, 'Integer'],
      2 => [$menuItem, 'String'],
    ]);

    CRM_Core_BAO_Navigation::resetNavigation();
  }

  /**
   * Alters each custom value
   *
   * @param $action
   */
  private function alterEntitiesValues($action) {
    $entities = array_merge(
      $this->optionValues,
      $this->scheduledJobs,
      $this->optionGroups,
      $this->processorTypes,
      $this->ufGroups
    );

    foreach ($entities as $customEntityValue) {
      $this->alterEntity(
        $customEntityValue['entityType'],
        $customEntityValue['searchValue'],
        isset($customEntityValue['searchField']) ? $customEntityValue['searchField'] : 'name',
        isset($customEntityValue['optionGroup']) ? $customEntityValue['optionGroup'] : FALSE,
        $action
      );
    }
  }

  /**
   * Alters each custom group
   *
   * @param $action
   */
  private function alterCustomGroups($action) {
    foreach ($this->customGroups as $customGroup) {
      $this->alterCustomGroup(
        $customGroup,
        $action
      );
    }
  }

  /**
   * Alters custom group
   *
   * @param $searchValue
   * @param $action
   */
  private function alterCustomGroup($searchValue, $action) {
    $customGroup = civicrm_api3('CustomGroup', 'getsingle', [
      'name' => $searchValue,
      'return' => ['id'],
    ]);

    switch ($action) {
      case 'enable':
        if (isset($customGroup['id'])) {
          civicrm_api3('CustomGroup', 'create', [
            'id' => $customGroup['id'],
            'is_active' => 1,
          ]);
        }
        break;

      case 'disable':
        if (isset($customGroup['id'])) {
          civicrm_api3('CustomGroup', 'create', [
            'id' => $customGroup['id'],
            'is_active' => 0,
          ]);
        }
        break;

      case 'uninstall':
        civicrm_api3('CustomGroup', 'get', [
          'name' => $searchValue,
          'api.CustomGroup.delete' => ['id' => '$value.id'],
        ]);
        break;
    }
  }

  /**
   * Alters custom entity
   *
   * @param $entityType
   * @param $searchValue
   * @param $searchField
   * @param $optionGroup
   * @param $action
   */
  private function alterEntity($entityType, $searchValue, $searchField, $optionGroup, $action) {
    $alterCustomEntity = [];

    switch ($action) {
      case 'enable':
        $alterCustomEntity = [
          $searchField => $searchValue,
          'api.' . $entityType . '.create' => [
            'id' => '$value.id',
            'is_active' => 1,
          ],
        ];
        break;

      case 'disable':
        $alterCustomEntity = [
          $searchField => $searchValue,
          'api.' . $entityType . '.create' => [
            'id' => '$value.id',
            'is_active' => 0,
          ],
        ];
        break;

      case 'uninstall':
        $alterCustomEntity = [
          $searchField => $searchValue,
          'api.' . $entityType . '.delete' => ['id' => '$value.id'],
        ];
        break;
    }

    if ($optionGroup !== FALSE) {
      $alterCustomEntity = ['option_group_id' => $optionGroup] + $alterCustomEntity;
    }

    civicrm_api3($entityType, 'get', $alterCustomEntity);
  }

  /**
   * Deletes Direct Debit payment processor
   */
  private function deletePaymentProcessor() {
    foreach([0, 1] as $isTest) {
      civicrm_api3('PaymentProcessor', 'get', [
        'name' => 'Direct Debit',
        'is_test' => $isTest,
        'api.PaymentProcessor.delete' => ['id' => '$value.id'],
      ]);
    }
  }

  /**
   * Deletes Direct Debit navigation menu
   */
  private function deleteDirectDebitNavigationMenu() {
    $menuItems = [
      'direct_debit',
      'create_new_instructions_batch',
      'export_direct_debit_payments',
      'view_new_instruction_batches',
      'view_payment_batches',
    ];

    foreach ($menuItems as $item) {
      $this->removeNav($item);
    }
    CRM_Core_BAO_Navigation::resetNavigation();
  }

  /**
   * Deletes message templates
   *
   * @param $messageTitle
   */
  private function deleteMessageTemplate($messageTitle) {
    civicrm_api3('MessageTemplate', 'get', [
      'msg_title' => $messageTitle,
      'api.MessageTemplate.delete' => ['id' => '$value.id'],
    ]);
  }

}
