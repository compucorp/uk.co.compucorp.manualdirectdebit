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
    try {
      $dataCollector = new CRM_ManualDirectDebit_Mail_DataCollector_RecurringContribution($recurringContributionId);
      $tplParams = $dataCollector->retrieve();
      $email = $dataCollector->retrieveEmail();
      $messageTemplateId = CRM_ManualDirectDebit_Common_MessageTemplate::getMessageTemplateId(
        CRM_ManualDirectDebit_Common_MessageTemplate::SING_UP
      );

      return $this->sendEmail($email, $messageTemplateId, $tplParams);
    }
    catch (CiviCRM_API3_Exception $e) {
      return FALSE;
    }
  }

  /**
   * Sends mail "Direct Debit Payment Update Notification"
   *
   * @param $recurringContributionId
   *
   * @return bool
   */
  public function sendPaymentUpdateNotification($recurringContributionId) {
    try {
      $dataCollector = new CRM_ManualDirectDebit_Mail_DataCollector_RecurringContribution($recurringContributionId);
      $tplParams = $dataCollector->retrieve();
      $email = $dataCollector->retrieveEmail();
      $messageTemplateId = CRM_ManualDirectDebit_Common_MessageTemplate::getMessageTemplateId(
        CRM_ManualDirectDebit_Common_MessageTemplate::PAYMENT_UPDATE
      );

      return $this->sendEmail($email, $messageTemplateId, $tplParams);
    }
    catch (CiviCRM_API3_Exception $e) {
      return FALSE;
    }
  }

  /**
   * Sends mail "Direct Debit Payment Collection Reminder"
   *
   * @param $contributionId
   *
   * @return bool
   */
  public function sendPaymentCollectionReminder($contributionId) {
    try {
      $dataCollector = new CRM_ManualDirectDebit_Mail_DataCollector_Contribution($contributionId);
      $tplParams = $dataCollector->retrieve();
      $email = $dataCollector->retrieveEmail();
      $messageTemplateId = CRM_ManualDirectDebit_Common_MessageTemplate::getMessageTemplateId(
        CRM_ManualDirectDebit_Common_MessageTemplate::COLLECTION_REMINDER
      );

      return $this->sendEmail($email, $messageTemplateId, $tplParams);
    }
    catch (CiviCRM_API3_Exception $e) {
      return FALSE;
    }
  }

  /**
   * Sends mail "Direct Debit Auto-renew Notification"
   *
   * @param $recurringContributionId
   *
   * @return bool
   */
  public function sendAutoRenewNotification($recurringContributionId) {
    try {
      $dataCollector = new CRM_ManualDirectDebit_Mail_DataCollector_RecurringContribution($recurringContributionId);
      $tplParams = $dataCollector->retrieve();
      $email = $dataCollector->retrieveEmail();
      $messageTemplateId = CRM_ManualDirectDebit_Common_MessageTemplate::getMessageTemplateId(
        CRM_ManualDirectDebit_Common_MessageTemplate::AUTO_RENEW
      );

      return $this->sendEmail($email, $messageTemplateId, $tplParams);
    }
    catch (CiviCRM_API3_Exception $e) {
      return FALSE;
    }
  }

  /**
   * Sends mail "Direct Debit Mandate Update Notification"
   *
   * @param $mandateId
   *
   * @return bool
   */
  public function sendMandateUpdateNotification($mandateId) {
    try {
      $dataCollector = new CRM_ManualDirectDebit_Mail_DataCollector_Mandate($mandateId);
      $tplParams = $dataCollector->retrieve();
      $email = $dataCollector->retrieveEmail();
      $messageTemplateId = CRM_ManualDirectDebit_Common_MessageTemplate::getMessageTemplateId(
        CRM_ManualDirectDebit_Common_MessageTemplate::MANDATE_UPDATE
      );

      return $this->sendEmail($email, $messageTemplateId, $tplParams);
    }
    catch (CiviCRM_API3_Exception $e) {
      return FALSE;
    }
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
    try {
      $dataCollector = new CRM_ManualDirectDebit_Mail_DataCollector_Contribution($contributionId);
      $tplParams = $dataCollector->retrieve();
      $email = $dataCollector->retrieveEmail();

      return $this->sendEmail($email, $messageTemplateId, $tplParams);
    }
    catch (CiviCRM_API3_Exception $e) {
      return FALSE;
    }
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
    try {
      $dataCollector = new CRM_ManualDirectDebit_Mail_DataCollector_Membership($membershipId);
      $tplParams = $dataCollector->retrieve();
      $email = $dataCollector->retrieveEmail();

      return $this->sendEmail($email, $messageTemplateId, $tplParams);
    }
    catch (CiviCRM_API3_Exception $e) {
      return FALSE;
    }
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
    try {
      $dataCollector = new CRM_ManualDirectDebit_Mail_DataCollector_RecurringContribution($recurContributionId);
      $tplParams = $dataCollector->retrieve();
      $email = $dataCollector->retrieveEmail();

      return $this->sendEmail($email, $messageTemplateId, $tplParams);
    }
    catch (CiviCRM_API3_Exception $e) {
      return FALSE;
    }
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
    try {
      $dataCollector = new CRM_ManualDirectDebit_Mail_DataCollector_Mandate($mandateId);
      $tplParams = $dataCollector->retrieve();
      $email = $dataCollector->retrieveEmail();

      return $this->sendEmail($email, $messageTemplateId, $tplParams);
    }
    catch (CiviCRM_API3_Exception $e) {
      return FALSE;
    }
  }

  /**
   * Sends mail
   *
   * @param $email
   * @param $templateId
   *
   * @param $tplParams
   *
   * @return bool
   */
  private function sendEmail($email, $templateId, $tplParams) {
    if (!$email) {
      return FALSE;
    }

    try {
      civicrm_api3('MessageTemplate', 'send', [
        'id' => $templateId,
        'template_params' => $tplParams,
        'from' => $this->senderEmailName . " <" . $this->senderEmailAddress . ">",
        'to_email' => $email,
      ]);

      return TRUE;
    }
    catch (CiviCRM_API3_Exception $e) {
      return FALSE;
    }
  }

}
