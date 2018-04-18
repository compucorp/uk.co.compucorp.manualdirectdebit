<?php

/**
 * Class provide saving dependency between mandate and contribution
 *
 */
class CRM_ManualDirectDebit_Hook_MandateContributionConnector {

  /**
   * Instance of current class
   *
   * @var object
   */
  protected static $instance;

  /**
   * Id of current contribution
   *
   * @var int
   */
  private $contributionId;

  /**
   * Id of current recurring contribution
   *
   * @var int
   */
  private $contributionRecurId;

  /**
   * Id of current mandate
   *
   * @var int
   */
  private $mandateId;

  /**
   * Id of current payment Instrument
   *
   * @var int
   */
  private $currentPaymentInstrumentId;

  /**
   * Overrides constructor to prevent creating instance of class,
   * according to `Singleton` Pattern
   *
   * @var int
   */
  private function __construct() {
  }

  /**
   * Overrides clone to prevent creating instance of class,
   * according to `Singleton` Pattern
   *
   * @var int
   */
  private function __clone() {
  }

  /**
   * Overrides wakeup to prevent creating instance of class,
   * according to `Singleton` Pattern
   *
   * @var int
   */
  private function __wakeup() {
  }

  /**
   * Returns instance of current class, according to `Singleton` Pattern
   *
   * @return \CRM_ManualDirectDebit_Hook_MandateContributionConnector
   */
  public static function getInstance() {
    if (is_null(self::$instance)) {
      self::$instance = new self;
    }
    return self::$instance;
  }

  /**
   * Sets contribution properties
   *
   * @param $dao
   */
  public function setContributionProperties($dao) {
    $this->contributionId = $dao->id;
    $this->contributionRecurId = $dao->contribution_recur_id;
    $this->currentPaymentInstrumentId = $dao->payment_instrument_id;
  }

  /**
   * Sets mandate Id property, and if `contributionId` is exist launch process
   * of saving dependency
   *
   * @param $mandateId
   */
  public function setMandateId($mandateId) {
    $this->mandateId = $mandateId;

    if (isset($this->contributionId)) {
      $this->createDependency();
    }
  }

  /**
   * Checks types of dependency
   *
   */
  public function createDependency() {
    if (isset($this->contributionRecurId)) {
      if ($this->isDirectDebitPaymentProcessor()) {
        $this->assignRecurringContributionMandate();
        $this->assignContributionMandate();
      }
    }
    else {
      if ($this->isPaymentInstrumentDirectDebit()) {
        $this->assignContributionMandate();
      }
    }

    $this->refreshProperties();
  }

  /**
   * Checks if current contribution has Direct Debit Payment Processor
   *
   * @return bool
   */
  private function isDirectDebitPaymentProcessor() {
    $currentPaymentProcessorId = civicrm_api3('ContributionRecur', 'getvalue', [
      'return' => "payment_processor_id",
      'id' => $this->contributionRecurId,
    ]);

    $directDebitPaymentProcessorId = civicrm_api3('PaymentProcessor', 'getvalue', [
      'return' => "id",
      'name' => "Direct Debit",
    ]);
    return $directDebitPaymentProcessorId == $currentPaymentProcessorId;
  }

  /**
   * Assigns a mandate into recurring contribution
   *
   */
  private function assignRecurringContributionMandate() {
    $rows = [
      'recurr_id' => $this->contributionRecurId,
      'mandate_id' => $this->mandateId,
    ];
    CRM_ManualDirectDebit_BAO_RecurrMandateRef::create($rows);
  }

  /**
   * Assigns a mandate into contribution
   *
   */
  private function assignContributionMandate() {
    $mandateIdCustomFieldId = civicrm_api3('CustomField', 'getvalue', [
      'return' => "id",
      'name' => "mandate_id",
    ]);
    civicrm_api3('Contribution', 'create', [
      "custom_$mandateIdCustomFieldId" => $this->mandateId,
      'id' => $this->contributionId,
    ]);
  }

  /**
   * Checks if current contribution has Direct Debit Payment Instrument
   *
   * @return bool
   */
  private function isPaymentInstrumentDirectDebit() {
    $paymentInstrumentId = civicrm_api3('OptionValue', 'getvalue', array(
      'return' => "value",
      'label' => "Direct Debit",
    ));

    return $paymentInstrumentId == $this->currentPaymentInstrumentId;
  }

  /**
   * Refreshes all properties after saving dependency for reusing instance of class
   *
   */
  private function refreshProperties() {
    unset($this->mandateId);
    unset($this->contributionId);
    unset($this->contributionRecurId);
    unset($this->currentPaymentInstrumentId);
  }

}
