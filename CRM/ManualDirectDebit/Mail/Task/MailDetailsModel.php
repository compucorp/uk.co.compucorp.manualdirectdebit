<?php

/**
 * Class CRM_ManualDirectDebit_Mail_Task_MailDetailsModel.
 */
class CRM_ManualDirectDebit_Mail_Task_MailDetailsModel {

  /**
   * Subject for the e-mail.
   *
   * @var string
   */
  private $subject;

  /**
   * From e-mail address.
   *
   * @var string
   */
  private $from;

  /**
   * CC addresses for the e-mail.
   *
   * @var string
   */
  private $cc;

  /**
   * Blind CC for the e-mail.
   *
   * @var string
   */
  private $bcc;

  /**
   * Additional details for the e-mail.
   *
   * @var string
   */
  private $additionalDetails;

  /**
   * Attachments to be included in the e-mail.
   *
   * @var array
   */
  private $attachments = [];

  /**
   * Specific contribution ID for the mail.
   *
   * @var string
   */
  private $contributionID;

  /**
   * List of contribution IDs associated to the e-mail.
   *
   * @var array
   */
  private $allContributionIDs = [];

  /**
   * Sets the subject of the e-mail.
   *
   * @param $subject
   */
  public function setSubject($subject) {
    $this->subject = $subject;
  }

  /**
   * Sets from e-mail address for the e-mail.
   *
   * @param $from
   */
  public function setFrom($from) {
    $this->from = $from;
  }

  /**
   * Sets the CC for the e-mail.
   *
   * @param $cc
   */
  public function setCc($cc) {
    $this->cc = $cc;
  }

  /**
   * Stes BCC dor the e-mail.
   *
   * @param $bcc
   */
  public function setBcc($bcc) {
    $this->bcc = $bcc;
  }

  /**
   * Sets additional detail for the e-mail.
   *
   * @param $additionalDetails
   */
  public function setAdditionalDetails($additionalDetails) {
    $this->additionalDetails = $additionalDetails;
  }

  /**
   * Sets the attachments for the e-mail.
   *
   * @param $attachments
   */
  public function setAttachments($attachments) {
    $this->attachments = $attachments;
  }

  /**
   * Sets list of contribution IDs for the e-mail.
   *
   * @param $contributionIDs
   */
  public function setAllContributionIDS($contributionIDs) {
    $this->allContributionIDs = $contributionIDs;
  }

  /**
   * Sets the specific contribution ID being used for the e-mail.
   *
   * @param $contributionID
   */
  public function setContributionID($contributionID) {
    $this->contributionID = $contributionID;
  }

  /**
   * Returns the subject of the e-mail.
   *
   * @return string
   */
  public function getSubject() {
    return $this->subject;
  }

  /**
   * Returns the from address for the e-mail.
   *
   * @return string
   */
  public function getFrom() {
    return $this->from;
  }

  /**
   * Returns the cc adresses for the e-mail.
   *
   * @return string
   */
  public function getCc() {
    return $this->cc;
  }

  /**
   * Returns bcc addresses for the e-mail.
   *
   * @return string
   */
  public function getBcc() {
    return $this->bcc;
  }

  /**
   * Returns additional details for the e-mail.
   *
   * @return string
   */
  public function getAdditionalDetails() {
    return $this->additionalDetails;
  }

  /**
   * Returns list of attachments.
   *
   * @return array
   */
  public function getAttachments() {
    return $this->attachments;
  }

  /**
   * Return contribution ID for the e-mail.
   *
   * @return string
   */
  public function getContributionID() {
    return $this->contributionID;
  }

  /**
   * Returns list of contributions that will be used for the e-mail.
   *
   * @return mixed
   */
  public function getAllContributionIDs() {
    return $this->allContributionIDs;
  }

}
