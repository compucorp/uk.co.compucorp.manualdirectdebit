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
    $this->uninstallCustomInformation();
  }

  /**
   *  Uninstall custom information
   */
  private function uninstallCustomInformation() {
    $this->deleteField('OptionGroup', 'direct_debit_codes');
    $this->deleteField('OptionGroup', 'direct_debit_originator_number');
    $this->deleteField('CustomGroup', 'direct_debit_mandate');
    $this->deleteField('CustomGroup', 'direct_debit_information');
    $this->deleteField('UFGroup', 'Direct Debit Information', 'title');
    $this->deleteField('PaymentProcessor', 'OfflineDirectDebit', "payment_processor_type_id");
    $this->deleteField('PaymentProcessorType', 'OfflineDirectDebit');
  }

  /**
   * Function deletes custom field
   *
   * @param string $entityType clarify entity,
   * @param string $fieldIdentifier help to find id of field,
   * @param string $searchBy specify type of $fieldIdentifier
   */
  private function deleteField($entityType, $fieldIdentifier, $searchBy = 'name') {
    civicrm_api3($entityType, 'delete', [
      'id' => $this->getIdByName($entityType, $fieldIdentifier, $searchBy),
    ]);
  }

  /**
   * Function return id of $entityType field
   *
   * @param string $entityType specify group entity,
   * @param string $fieldIdentifier help to find id of field,
   * @param string $searchBy specify type of $fieldIdentifier
   *
   * @return int
   */
  private function getIdByName($entityType, $fieldIdentifier, $searchBy) {
    $id = civicrm_api3($entityType, 'getvalue', [
      'return' => "id",
      $searchBy => $fieldIdentifier,
    ]);

    return (int) $id;
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
