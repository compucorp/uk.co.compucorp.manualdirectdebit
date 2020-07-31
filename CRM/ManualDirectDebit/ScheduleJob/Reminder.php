<?php

use CRM_ManualDirectDebit_Common_CollectionReminderSendFlagManager as CollectionReminderSendFlagManager;

/**
 * Run reminder
 */
class CRM_ManualDirectDebit_ScheduleJob_Reminder {

  /**
   * Log
   *
   * @var array
   */
  private $log = '';

  /**
   * Is reminder error
   *
   * @var array
   */
  private $isError = FALSE;

  /**
   * Runs reminder
   *
   * @return array
   */
  public function run() {
    $this->setLog(ts(""));
    $targetContributionDataList = CRM_ManualDirectDebit_ScheduleJob_TargetContribution::retrieve();

    if (empty($targetContributionDataList)) {
      $this->setLog(ts("Haven't found appropriate contributions."));

      return $this->log;
    }

    foreach ($targetContributionDataList as $targetContributionData) {
      $this->processTargetContribution($targetContributionData);
    }

    return $this->log;
  }

  /**
   * Processes the given contribution to send the collection reminder.
   *
   * @param array $targetContributionData
   */
  private function processTargetContribution($targetContributionData) {
    $this->setLog(ts("Processing contribution with id = %1", [1 => $targetContributionData['contributionId']]));

    if (empty($targetContributionData['email'])) {
      $this->setLog(ts(
        "Could not send email for contribution with id = %1. Related contact doesn't have an e-mail or has the 'do not send e-mail' flag set.",
        [1 => $targetContributionData['contributionId']]
      ));
      $this->setLog(ts(""));

      return;
    }

    try {
      $this->sendEmail($targetContributionData['contributionId']);
      $this->createActivity(
        $targetContributionData['contributionId'],
        $targetContributionData['contactId']
      );
    }
    catch (Exception $e) {
      $this->setLog(ts("Exception found processing contribution with id %1: %2", [
        1 => $targetContributionData['contributionId'],
        2 => $e->getMessage(),
      ]));
    }

    $this->setLog(ts(""));
  }

  /**
   * Sets log
   *
   * @param $message
   */
  private function setLog($message) {
    $this->log .= $message . '<br />';
  }

  /**
   * Checks if reminder has error
   * Returns 0 or 1
   *
   * @return int
   */
  public function isError() {
    if ($this->isError) {
      return 1;
    }

    return 0;
  }

  /**
   * Creates activity
   *
   * @param $contributionId
   * @param $contactId
   */
  private function createActivity($contributionId, $contactId) {
    $activityId = CRM_ManualDirectDebit_Common_Activity::create(
      "Direct Debit Payment Collection Reminder",
      "direct_debit_payment_reminder",
      $contributionId,
      CRM_ManualDirectDebit_Common_User::getAdminContactId(),
      $contactId
    );

    if ($activityId) {
      $this->setLog(ts("Activity was created."));
    }
    else {
      $this->setLog(ts("Error. Activity was not created."));
    }
  }

  /**
   * Sends email
   *
   * @param $contributionId
   */
  private function sendEmail($contributionId) {
    $notification = new CRM_ManualDirectDebit_Mail_Notification();
    $result = $notification->sendPaymentCollectionReminder($contributionId);

    if ($result) {
      $this->setLog(ts("Email was sent."));
      $this->updateNotificationSentFlag($contributionId);
    }
    else {
      $this->setLog(ts("Email was not sent."));
    }
  }

  private function updateNotificationSentFlag($contributionId) {
    CollectionReminderSendFlagManager::setIsNotificationSentToSent($contributionId);
  }

}
