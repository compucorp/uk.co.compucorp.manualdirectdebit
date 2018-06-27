<?php

/**
* Sends mails
*/
class CRM_ManualDirectDebit_Mail_Notification {

  /**
   * Default domain email name
   *
   * @var array
   */
  const DEFAULT_DOMAIN_EMAIL_NAME = "CiviCRM";

  /**
   * Sender email name
   *
   * @var string
   */
  private $senderEmailName;

  /**
   * Sender email address
   *
   * @var string
   */
  private $senderEmailAddress;

  /**
   * CRM_ManualDirectDebit_Mail_Notification constructor.
   *
   * @throws \Exception
   */
  public function __construct() {
    $senderEmail = CRM_Core_BAO_Domain::getNameAndEmail();
    $this->setSenderEmailName($senderEmail[0]);
    $this->setSenderEmailAddress($senderEmail[1]);
  }

  /**
   * Sets the sender's configured name or defines the default one
   *
   * @param string $senderEmailName
   */
  private function setSenderEmailName($senderEmailName) {
    if (empty($senderEmailName)) {
      $this->senderEmailName = self::DEFAULT_DOMAIN_EMAIL_NAME;
    }
    else {
      $this->senderEmailName = $senderEmailName;
    }
  }

  /**
   * Sets the sender's configured address or defines the default one
   *
   * @param string $senderEmailAddress
   */
  private function setSenderEmailAddress($senderEmailAddress) {
    if (empty($senderEmailAddress)) {
      $this->senderEmailAddress = "";
    }
    else {
      $this->senderEmailAddress = $senderEmailAddress;
    }
  }

  /**
   * Sends mail "Direct Debit Payment Sign Up Notification"
   *
   * @param $recurringContributionId
   *
   * @return bool
   */
  public function sendPaymentSignUpNotify($recurringContributionId) {
    $dataCollector = new CRM_ManualDirectDebit_Mail_DataCollector_RecurringContribution($recurringContributionId);
    return $this->sendEmail($dataCollector, CRM_ManualDirectDebit_Common_MessageTemplate::SIGN_UP_MSG_TITLE);
  }

  /**
   * Sends mail "Direct Debit Payment Update Notification"
   *
   * @param $recurringContributionId
   *
   * @return bool
   */
  public function sendPaymentUpdateNotification($recurringContributionId) {
    $dataCollector = new CRM_ManualDirectDebit_Mail_DataCollector_RecurringContribution($recurringContributionId);
    return $this->sendEmail($dataCollector, CRM_ManualDirectDebit_Common_MessageTemplate::PAYMENT_UPDATE_MSG_TITLE);
  }

  /**
   * Sends mail "Direct Debit Payment Collection Reminder"
   *
   * @param $contributionId
   *
   * @return bool
   */
  public function sendPaymentCollectionReminder($contributionId) {
    $dataCollector = new CRM_ManualDirectDebit_Mail_DataCollector_Contribution($contributionId);
    return $this->sendEmail($dataCollector, CRM_ManualDirectDebit_Common_MessageTemplate::COLLECTION_REMINDER_MSG_TITLE);
  }

  /**
   * Sends mail "Direct Debit Auto-renew Notification"
   *
   * @param $recurringContributionId
   *
   * @return bool
   */
  public function sendAutoRenewNotification($recurringContributionId) {
    $dataCollector = new CRM_ManualDirectDebit_Mail_DataCollector_RecurringContribution($recurringContributionId);
    return $this->sendEmail($dataCollector, CRM_ManualDirectDebit_Common_MessageTemplate::AUTO_RENEW_MSG_TITLE);
  }

  /**
   * Sends mail "Direct Debit Mandate Update Notification"
   *
   * @param $mandateId
   *
   * @return bool
   */
  public function sendMandateUpdateNotification($mandateId) {
    $dataCollector = new CRM_ManualDirectDebit_Mail_DataCollector_Mandate($mandateId);
    return $this->sendEmail($dataCollector, CRM_ManualDirectDebit_Common_MessageTemplate::MANDATE_UPDATE_MSG_TITLE);
  }

  /**
   * Sends mail by 'contribution id' and 'template id'
   *
   * @param $contributionId
   * @param $messageTemplateId
   *
   * @return bool
   */
  public function notifyByContributionId($contributionId, $messageTemplateId) {
    $dataCollector = new CRM_ManualDirectDebit_Mail_DataCollector_Contribution($contributionId);
    $messageTemplateTitle = CRM_ManualDirectDebit_Common_MessageTemplate::getMessageTemplateTitle($messageTemplateId);
    return $this->sendEmail($dataCollector, $messageTemplateTitle);
  }

  /**
   * Sends mail by 'membership id' and 'template id'
   *
   * @param $membershipId
   * @param $messageTemplateId
   *
   * @return bool
   */
  public function notifyByMembershipId($membershipId, $messageTemplateId) {
    $dataCollector = new CRM_ManualDirectDebit_Mail_DataCollector_Membership($membershipId);
    $messageTemplateTitle = CRM_ManualDirectDebit_Common_MessageTemplate::getMessageTemplateTitle($messageTemplateId);
    return $this->sendEmail($dataCollector, $messageTemplateTitle);
  }

  /**
   * Sends mail by 'recurring contribution id' and 'template id'
   *
   * @param $recurContributionId
   * @param $messageTemplateId
   *
   * @return bool
   */
  public function notifyByRecurContributionId($recurContributionId, $messageTemplateId) {
    $dataCollector = new CRM_ManualDirectDebit_Mail_DataCollector_RecurringContribution($recurContributionId);
    $messageTemplateTitle = CRM_ManualDirectDebit_Common_MessageTemplate::getMessageTemplateTitle($messageTemplateId);
    return $this->sendEmail($dataCollector, $messageTemplateTitle);
  }

  /**
   * Sends mail by 'mandate id' and 'template id'
   *
   * @param $mandateId
   * @param $messageTemplateId
   *
   * @return bool
   */
  public function notifyByMandateId($mandateId, $messageTemplateId) {
    $dataCollector = new CRM_ManualDirectDebit_Mail_DataCollector_Mandate($mandateId);
    $messageTemplateTitle = CRM_ManualDirectDebit_Common_MessageTemplate::getMessageTemplateTitle($messageTemplateId);
    return $this->sendEmail($dataCollector, $messageTemplateTitle);
  }

  /**
   * Sends mail
   *
   * @param CRM_ManualDirectDebit_Mail_DataCollector_Base $collector
   * @param $templateTitle
   *
   * @return bool
   *
   */
  private function sendEmail($collector, $templateTitle) {
    $templateId = CRM_ManualDirectDebit_Common_MessageTemplate::getMessageTemplateId($templateTitle);
    $tplParams = $collector->retrieve();
    $contactEmailData = $collector->retrieveContactEmailData();

    if (empty($contactEmailData['email']) || $contactEmailData['do_not_email'] == 1 ) {
      return FALSE;
    }

    try {
      civicrm_api3('MessageTemplate', 'send', [
        'id' => $templateId,
        'template_params' => $tplParams,
        'from' => $this->senderEmailName . " <" . $this->senderEmailAddress . ">",
        'to_email' => $contactEmailData['email'],
      ]);

      return TRUE;
    }
    catch (CiviCRM_API3_Exception $e) {
      return FALSE;
    }
  }

}
