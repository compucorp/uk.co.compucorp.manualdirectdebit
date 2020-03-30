<?php

/**
 *  Check condition for creating empty mandate for Contribution
 */
class CRM_ManualDirectDebit_Hook_PostProcess_Contribution_DirectDebitMandate {

  /**
   * Contribution form object from Hook
   *
   * @var object
   */
  private $form;

  /**
   * Object which manage writing and reading Data from DB
   *
   * @var CRM_ManualDirectDebit_Common_MandateStorageManager
   */
  private $mandateStorage;

  /**
   * Id of current contact
   *
   * @var int
   */
  private $currentContactId;

  /**
   * Id of current mandate
   *
   * @var int
   */
  private $mandateId;

  public function __construct(&$form) {
    $this->mandateStorage = new CRM_ManualDirectDebit_Common_MandateStorageManager();
    $this->form = $form;
  }

  /**
   * Sets Id of current contact
   *
   * @param $contactId
   */
  public function setCurrentContactId($contactId) {
    $this->currentContactId = $contactId;
  }

  /**
   *  Sets id of current mandate
   */
  public function setCurrentMandateId() {
    if (isset($this->form->getVar('_submitValues')['mandateId']) && !empty($this->form->getVar('_submitValues')['mandateId'])) {
      $this->mandateId = $this->form->getVar('_submitValues')['mandateId'];
    }
  }

  public function saveMandateData() {
    $mandateID = $this->form->getSubmitValue('mandate_id');

    $mandateContributionConnector = CRM_ManualDirectDebit_Hook_MandateContributionConnector::getInstance();
    $mandateContributionConnector->setMandateId($mandateID);
  }

  /**
   *  Launches all required processes after saving mandate
   */
  public function run() {
    $this->setCurrentContactId($this->form->getVar('_entityId'));
    $this->setCurrentMandateId();

    $this->checkChangingMandateContribution();
    $this->checkMailNotification();
    $this->createMandateActivity();
  }

  /**
   * Changes mandate for recurring contribution
   *
   * @return bool
   */
  public function checkChangingMandateContribution() {
    if (!isset($this->form->getVar('_submitValues')['recurrId'])
      || empty($this->form->getVar('_submitValues')['recurrId'])) {
      return FALSE;
    }

    $recurringContributionId = $this->form->getVar('_submitValues')['recurrId'];

    $oldMandateId = CRM_ManualDirectDebit_BAO_RecurrMandateRef::getMandateIdForRecurringContribution($recurringContributionId);
    $this->mandateId = $this->mandateStorage->getLastInsertedMandateId($this->currentContactId);

    $mandateManager = new CRM_ManualDirectDebit_Common_MandateStorageManager();
    $mandateManager->assignRecurringContributionMandate($recurringContributionId, $this->mandateId);

    if (!empty($oldMandateId)) {
      $this->mandateStorage->changeMandateForContribution($this->mandateId, $oldMandateId);
    }
    else {
      $this->relateMandateToExistingContributions($recurringContributionId, $this->mandateId);
    }

    $this->redirectToContributionTab();
  }

  /**
   * Relates the given mandate to all the contributions under the given
   * recurring contribution ID.
   *
   * @param int $recurringContributionId
   * @param int $mandateID
   */
  private function relateMandateToExistingContributions($recurringContributionId, $mandateID) {
    $contributions = civicrm_api3('Contribution', 'get', [
      'sequential' => 1,
      'contribution_recur_id' => $recurringContributionId,
      'options' => ['limit' => 0],
    ]);

    if ($contributions['count'] < 1) {
      return;
    }

    foreach ($contributions['values'] as $payment) {
      $this->mandateStorage->assignContributionMandate($payment['id'], $mandateID);
    }
  }

  /**
   * Redirects to contribution tab
   */
  private function redirectToContributionTab() {
    $this->form->controller->setDestination(
      CRM_Utils_System::url('civicrm/contact/view', http_build_query([
        'action' => 'browse',
        'reset' => 1,
        'cid' => $this->currentContactId,
        'selectedChild' => 'contribute',
      ])
    ));
  }

  /**
   * Sends mail if appropriate checkbox is checked on
   */
  public function checkMailNotification() {
    if (!isset($this->form->getVar('_submitValues')['send_mandate_update_notification_to_the_contact'])
      || empty($this->form->getVar('_submitValues')['send_mandate_update_notification_to_the_contact'])) {
      return;
    }

    $valueOfSendMailCheckbox = $this->form->getVar('_submitValues')['send_mandate_update_notification_to_the_contact'];

    $isSendMailCheckboxTurnedOn = $valueOfSendMailCheckbox == 1;

    if ($isSendMailCheckboxTurnedOn) {
      $notification = new CRM_ManualDirectDebit_Mail_Notification();
      $notification->sendMandateUpdateNotification($this->mandateId);
    }
  }

  /**
   * Creates mandate activity
   */
  public function createMandateActivity() {
    $contactRelatedToContribution = $this->currentContactId;
    CRM_ManualDirectDebit_Common_Activity::create(
      "Direct Debit Mandate Update",
      "direct_debit_mandate_update",
      $this->mandateId,
      CRM_ManualDirectDebit_Common_User::getAdminContactId(),
      $contactRelatedToContribution
    );
  }

}
