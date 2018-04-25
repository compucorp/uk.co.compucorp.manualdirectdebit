<?php

/**
 *  Create an empty mandate and connect it to a new contribution
 */
class CRM_ManualDirectDebit_Hook_PostProcess_Contribution_DirectDebitMandate {

  /**
   * Contribution form object from Hook
   *
   * @var object
   */
  private $form;

  public function __construct(&$form) {
    $this->form = $form;
  }

  /**
   * Checks if payment option appropriate for creating mandate
   */
  public function checkPaymentOptionToCreateMandate() {
    $isRecurring = isset($this->form->getVar('_params')['is_recur']) && !empty($this->form->getVar('_params')['is_recur']);

    if ($isRecurring){
      $selectedPaymentProcessor = $this->form->getVar('_params')['payment_processor_id'];
      $directDebitPaymentProcessor = civicrm_api3('PaymentProcessor', 'getvalue', array(
        'return' => "id",
        'name' => "direct debit",
      ));

      if($selectedPaymentProcessor == $directDebitPaymentProcessor){
        $this->createMandate();
      }
    } else {
      $selectedPaymentInstrument = $this->form->getVar('_params')['payment_instrument_id'];
      $directDebitPaymentInstrument = civicrm_api3('OptionValue', 'getvalue', array(
        'return' => "value",
        'name' => "direct_debit",
      ));

      if ($selectedPaymentInstrument == $directDebitPaymentInstrument){
        $this->createMandate();
      }
    }
  }

  /**
   * Creates a new direct debit mandate and returns id of the last inserted one
   */
  private function createMandate() {
    $tableName = 'civicrm_value_dd_mandate';

    $transaction = new CRM_Core_Transaction();
    try {
      $sqlInsertedInDirectDebitMandate = "INSERT INTO `$tableName` (`entity_id`) VALUES (%1)";
      CRM_Core_DAO::executeQuery($sqlInsertedInDirectDebitMandate, [
        1 => [
          $this->form->getVar('_contactID'),
          'String',
        ],
      ]);

      $sqlSelectedDebitMandateID = "SELECT MAX(`id`) AS id FROM `$tableName` WHERE `entity_id` = %1";
      $queryResult = CRM_Core_DAO::executeQuery($sqlSelectedDebitMandateID, [
        1 => [
          $this->form->getVar('_contactID'),
          'String',
        ],
      ]);
      $queryResult->fetch();
      $generatedMandateId = $queryResult->id;
    } catch (Exception $exception) {
      $transaction->rollback();
      throw $exception;
    }

    // sets mandate id, for saving dependency between mandate and contribution
    $mandateContributionConnector = CRM_ManualDirectDebit_Hook_MandateContributionConnector::getInstance();
    $mandateContributionConnector->setMandateId($generatedMandateId);
  }

}
