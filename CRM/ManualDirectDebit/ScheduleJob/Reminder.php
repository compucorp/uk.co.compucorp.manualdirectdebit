<?php

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
      $this->setLog(ts("Haven't appropriate contributions."));
      return $this->log;
    }

    foreach ($targetContributionDataList as $targetContributionData) {
      $this->setLog(ts("Contribution with id = %1", [1 => $targetContributionData['contributionId']]));

      if (!empty($targetContributionData['email'])) {
        $this->sendEmail($targetContributionData['contributionId']);
      }
      else {
        $this->setLog(ts("Email not sent. Related contact haven't email."));
      }

      $this->createActivity(
        $targetContributionData['contributionId'],
        $targetContributionData['contactId']
      );

      $this->setLog(ts(""));
    }

    return $this->log;
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
    //TODO: send mail notification
//    $notification = new CRM_ManualDirectDebit_Mail_Notification();
//    $result = $notification->sendPaymentCollectionReminder($contributionId);
    $result = FALSE;

    if ($result) {
      $this->setLog(ts("Email was sent."));
    }
    else {
      $this->setLog(ts("Error. Email was not sent."));
    }
  }

}
