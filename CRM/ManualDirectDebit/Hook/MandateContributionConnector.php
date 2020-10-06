<?php

/**
 * Class provide saving dependency between mandate and contribution
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
      self::$instance = new self();
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

    if ($dao->id == $this->contributionId && empty($dao->contribution_recur_id)) {
      return;
    }
    $this->contributionRecurId = $dao->contribution_recur_id;

    if ($dao->id == $this->contributionId && empty($dao->payment_instrument_id)) {
      return;
    }
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
    $mandateStorage = new CRM_ManualDirectDebit_Common_MandateStorageManager();

    if (isset($this->contributionRecurId)) {

      $currentPaymentProcessorId = CRM_ManualDirectDebit_Common_DirectDebitDataProvider::getCurrentPaymentProcessorId($this->contributionRecurId);
      if (CRM_ManualDirectDebit_Common_DirectDebitDataProvider::isDirectDebitPaymentProcessor($currentPaymentProcessorId)) {
        $mandateStorage->assignRecurringContributionMandate($this->contributionRecurId, $this->mandateId);
        $mandateStorage->assignContributionMandate($this->contributionId, $this->mandateId);
      }
    }
    else {
      if (isset($this->currentPaymentInstrumentId) &&
        CRM_ManualDirectDebit_Common_DirectDebitDataProvider::isPaymentMethodDirectDebit(
          $this->currentPaymentInstrumentId)) {
        $mandateStorage->assignContributionMandate($this->contributionId, $this->mandateId);
      }
    }

    $this->refreshProperties();
  }

  /**
   * Refreshes all properties after saving dependency for reusing instance of
   * class
   */
  private function refreshProperties() {
    unset($this->mandateId);
    unset($this->contributionId);
    unset($this->contributionRecurId);
    unset($this->currentPaymentInstrumentId);
  }

}
