<?php

use CRM_ManualDirectDebit_ExtensionUtil as E;

/**
 * Collection of upgrade steps.
 */
class CRM_ManualDirectDebit_Upgrader extends CRM_ManualDirectDebit_Upgrader_Base {

  public function install() {
    $this->executeSqlFile('sql/install.sql');
    $this->createDirectDebitPaymentProcessor();
  }

  public function uninstall() {
    $this->executeSqlFile('sql/uninstall.sql');
  }

  /**
   * Will install the DirectDebit payment processor
   */
  private function createDirectDebitPaymentProcessor() {
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
