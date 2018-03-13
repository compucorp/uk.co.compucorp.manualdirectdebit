<?php

use CRM_ManualDirectDebit_ExtensionUtil as E;

/**
 * Collection of upgrade steps.
 */
class CRM_ManualDirectDebit_Upgrader extends CRM_ManualDirectDebit_Upgrader_Base {

  public function install() {
    $this->createDirectDebitPaymentProcessorType();
    $this->createDirectDebitPaymentProcessor();
  }

  public function uninstall() {
    $this->uninstallCustomInformation();
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
        isset($customValue['searchField']) ? $customValue['searchField'] : ""
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
  private function deleteEntityRecord($entityType, $searchValue, $searchField = 'name') {
    civicrm_api3($entityType, 'get', [
      $searchField => $searchValue,
      'api.' . $entityType . '.delete' => ['id' => '$value.id'],
    ]);
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
      "billing_mode" => "1",
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
    ];
    civicrm_api3('PaymentProcessor', 'create', $paymentProcessor);
  }

}
