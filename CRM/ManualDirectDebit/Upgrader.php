<?php

use CRM_ManualDirectDebit_ExtensionUtil as E;

/**
 * Collection of upgrade steps.
 */
class CRM_ManualDirectDebit_Upgrader extends CRM_ManualDirectDebit_Upgrader_Base {

  private $customValues = [
    [
      "entityType" => "OptionGroup",
      "searchValue" => "direct_debit_codes",
    ],
    [
      "entityType" => "OptionGroup",
      "searchValue" => "direct_debit_originator_number",
    ],
    [
      "entityType" => "UFGroup",
      "searchValue" => "Direct Debit Information",
      "searchField" => "title",
    ],
    [
      "entityType" => "PaymentProcessorType",
      "searchValue" => "OfflineDirectDebit",
    ],
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

  private $customGroups = [
    "direct_debit_mandate",
    "searchValue" => "direct_debit_information",
  ];


  public function install() {
    $this->createDirectDebitNavigationMenu();
    $this->createDirectDebitPaymentInstrument();
    $this->createDirectDebitPaymentProcessorType();
    $this->createDirectDebitPaymentProcessor();
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
      "user_name_label" => ts("Offline Direct Debit"),
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
    $paymentProcessor = [
      'name' => 'Direct Debit',
      'description' => '',
      'payment_processor_type_id' => 'OfflineDirectDebit',
      'domain_id' => CRM_Core_Config::domainID(),
      'payment_instrument_id' => 'direct_debit',
      'is_active' => 1,
    ];
    civicrm_api3('PaymentProcessor', 'create', $paymentProcessor);
  }

  public function onEnable() {
    $this->alterNavigationMenu("direct_debit", "enable");
    $this->alterCustomValues('enable');
    $this->alterCustomGroups('enable');
  }

  public function uninstall() {
    $this->deletePaymentProcessor();
    $this->alterCustomValues('uninstall');
    $this->alterCustomGroups('uninstall');
    $this->deleteDirectDebitNavigationMenu();
    $this->deleteMessageTemplates();
  }

  public function onDisable() {
    $this->alterNavigationMenu("direct_debit", "disable");
    $this->alterCustomValues('disable');
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
  private function alterCustomValues($action) {
    foreach ($this->customValues as $customValue) {
      $this->alterEntity(
        $customValue['entityType'],
        $customValue['searchValue'],
        isset($customValue['searchField']) ? $customValue['searchField'] : 'name',
        isset($customValue['optionGroup']) ? $customValue['optionGroup'] : FALSE,
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
          CRM_Core_BAO_CustomGroup::setIsActive((int) $customGroup['id'], 1);
        }
        break;

      case 'disable':
        if (isset($customGroup['id'])) {
          CRM_Core_BAO_CustomGroup::setIsActive((int) $customGroup['id'], 0);
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
    civicrm_api3('PaymentProcessor', 'get', [
      'name' => "Direct Debit",
      'api.PaymentProcessor.delete' => ['id' => '$value.id'],
    ]);
  }

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

}
