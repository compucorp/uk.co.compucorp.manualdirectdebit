<?php

/**
 * Class provide assigning appropriate 'Mandate' for each Contribution which assign
 *   to 'Membership Payment'
 */
class CRM_ManualDirectDebit_Hook_PostSave_MembershipPayment_MandateCreator {

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
  private $recurContributionId;

  /**
   * Id of current contact
   *
   * @var int
   */
  private $contactId;

  /**
   * Object which manage writing and reading Data from DB
   *
   * @var CRM_ManualDirectDebit_Common_MandateStorageManager
   */
  private $mandateStorage;

  public function __construct($dao) {
    $this->contributionId = $dao->contribution_id;
    $this->contactId = $this->getContactId();
    $this->recurContributionId = $this->getRecurringContributionId();
    $this->mandateStorage = new CRM_ManualDirectDebit_Common_MandateStorageManager();
  }

  /**
   * Gets id of current Contact
   *
   * @return int
   */
  private function getContactId() {
    return civicrm_api3('Contribution', 'getvalue', [
      'return' => "contact_id",
      'id' => $this->contributionId,
    ]);
  }

  /**
   * Gets id of current recurring contribution
   *
   * @return int
   */
  private function getRecurringContributionId() {
    return civicrm_api3('Contribution', 'getvalue', [
      'return' => "contribution_recur_id",
      'id' => $this->contributionId,
    ]);
  }

  /**
   * Assign mandate for contribution
   */
  public function assignMandateForContributions() {

    if (!$this->isContributionRecurring() || !$this->isCurrentPaymentInstrumentDirectDebit()) {
      return FALSE;
    }

    $mandateId = CRM_ManualDirectDebit_BAO_RecurrMandateRef::getMandateIdForRecurringContribution($this->recurContributionId);

    if (isset($mandateId) && !empty($mandateId)) {
      $this->mandateStorage->assignContributionMandate($this->contributionId, $mandateId);
    }
  }

  /**
   * Checks if current contribution is recurring
   *
   * @return bool
   */
  private function isContributionRecurring() {
    return isset($this->recurContributionId) && !empty($this->recurContributionId);
  }

  /**
   *  Checks if current payment processor is Direct Debit
   *
   * @return bool
   */
  private function isCurrentPaymentInstrumentDirectDebit() {
    $currentPaymentInstrument = civicrm_api3('Contribution', 'getvalue', [
      'sequential' => 1,
      'return' => "payment_instrument_id",
      'contribution_recur_id' => $this->recurContributionId,
      'options' => ['limit' => 1],
    ]);

    return CRM_ManualDirectDebit_Common_DirectDebitDataProvider::isPaymentMethodDirectDebit($currentPaymentInstrument);
  }

}
