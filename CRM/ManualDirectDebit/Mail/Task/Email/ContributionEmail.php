<?php
use CRM_ManualDirectDebit_Mail_Task_MailDetailsModel as MailDetailsModel;

class CRM_ManualDirectDebit_Mail_Task_Email_ContributionEmail extends CRM_ManualDirectDebit_Mail_Task_Email_AbstractEmailCommon {

  /**
   * Submit the form values.
   *
   * This is also accessible for testing.
   *
   * @param CRM_Core_Form $form
   * @param array $formValues
   *
   * @throws \CRM_Core_Exception
   */
  public function submit(&$form, $formValues) {
    $this->saveMessageTemplate($formValues);

    $from = CRM_Utils_Array::value('from_email_address', $formValues);
    $from = CRM_Utils_Mail::formatFromAddress($from);

    // CRM-13378: Append CC and BCC information at the end of Activity Details and format cc and bcc fields
    $additionalDetails = NULL;
    $ccValues = self::getCCValues($form, $formValues, 'cc_id');
    $bccValues = self::getCCValues($form, $formValues, 'bcc_id');

    $cc = $bcc = '';
    if (!empty($ccValues)) {
      $cc = implode(',', $ccValues['email']);
      $additionalDetails .= "\ncc : " . implode(", ", $ccValues['details']);
    }
    if (!empty($bccValues)) {
      $bcc = implode(',', $bccValues['email']);
      $additionalDetails .= "\nbcc : " . implode(", ", $bccValues['details']);
    }

    $subject = $formValues['subject'];
    if (isset($form->_caseId) && is_numeric($form->_caseId)) {
      $hash = substr(sha1(CIVICRM_SITE_KEY . $form->_caseId), 0, 7);
      $subject = "[case #$hash] $subject";
    }

    $attachments = array();
    CRM_Core_BAO_File::formatAttachment($formValues,
      $attachments,
      NULL, NULL
    );

    // format contact details array to handle multiple emails from same contact
    $formattedContactDetails = array();
    $tempEmails = array();
    foreach ($form->_contactIds as $key => $contactId) {
      // if we dont have details on this contactID, we should ignore
      // potentially this is due to the contact not wanting to receive email
      if (!isset($form->_contactDetails[$contactId])) {
        continue;
      }
      $email = $form->_toContactEmails[$key];
      // prevent duplicate emails if same email address is selected CRM-4067
      // we should allow same emails for different contacts
      $emailKey = "{$contactId}::{$email}";
      if (!in_array($emailKey, $tempEmails)) {
        $tempEmails[] = $emailKey;
        $details = $form->_contactDetails[$contactId];
        $details['email'] = $email;
        unset($details['email_id']);
        $formattedContactDetails[] = $details;
      }
    }

    $errors = [];
    $mailDetails = new MailDetailsModel();
    $mailDetails->setSubject($subject);
    $mailDetails->setFrom($from);
    $mailDetails->setCc($cc);
    $mailDetails->setBcc($bcc);
    $mailDetails->setAdditionalDetails($additionalDetails);
    $mailDetails->setAttachments($attachments);

    foreach ($formattedContactDetails as $formattedContactDetail) {
      self::sendEmailsForContact($form, $formValues, $mailDetails, $formattedContactDetail, $errors);
    }

    if ($errors) {
      CRM_Core_Error::statusBounce(ts('Errors found: %1', [1 => implode('; ', $errors)]));
    }
  }

  /**
   * Obtains list of CC values.
   *
   * @param $form
   * @param $formValues
   * @param $ccType
   *
   * @return array
   */
  private static function getCCValues($form, $formValues, $ccType) {
    $ccValues = [];

    if (!empty($formValues[$ccType])) {
      $allEmails = explode(',', $formValues[$ccType]);

      foreach ($allEmails as $value) {
        list($contactId, $email) = explode('::', $value);
        $contactURL = CRM_Utils_System::url('civicrm/contact/view', "reset=1&force=1&cid={$contactId}", TRUE);
        $ccValues['email'][] = '"' . $form->_contactDetails[$contactId]['sort_name'] . '" <' . $email . '>';
        $ccValues['details'][] = "<a href='{$contactURL}'>" . $form->_contactDetails[$contactId]['display_name'] . "</a>";
      }
    }

    return $ccValues;
  }

  /**
   * Sends emails for the contributions that were selected for the contact.
   *
   * @param object $form
   * @param array $formValues
   * @param \CRM_ManualDirectDebit_Mail_Task_MailDetailsModel $mailDetails
   * @param array $formattedContactDetail
   * @param array $errors
   */
  private static function sendEmailsForContact($form, $formValues, MailDetailsModel $mailDetails, $formattedContactDetail, &$errors) {
    $contributionIds = array();
    if ($form->getVar('_contributionIds')) {
      $contributionIds = $form->getVar('_contributionIds');
    }
    $mailDetails->setAllContributionIDS($contributionIds);

    $contactId = $formattedContactDetail['contact_id'];
    $selectedContributionIdsForCurrentContact = self::getSelectedContributionIdsForCurrentContact($contactId, $contributionIds);
    foreach ($selectedContributionIdsForCurrentContact as $contributionId) {
      $mailDetails->setContributionID($contributionId);
      self::sendEmailForContribution($form, $formValues, $mailDetails, $formattedContactDetail, $errors);
    }
  }

  /**
   * Performs the sending of the email.
   *
   * @param object $form
   * @param array $formValues
   * @param \CRM_ManualDirectDebit_Mail_Task_MailDetailsModel $mailDetails
   * @param array $formattedContactDetail
   * @param array $errors
   */
  private static function sendEmailForContribution($form, $formValues, MailDetailsModel $mailDetails, $formattedContactDetail, &$errors) {
    try {
      $dataCollector = new CRM_ManualDirectDebit_Mail_DataCollector_Contribution($mailDetails->getContributionID());
      $tplParams = $dataCollector->retrieve();
      $contactDetail = [$formattedContactDetail];
      $subject = $mailDetails->getSubject();
      CRM_ManualDirectDebit_Mail_Task_Mail::sendDirectDebitEmail(
        $contactDetail,
        $subject,
        $formValues['text_message'],
        $formValues['html_message'],
        NULL,
        NULL,
        $mailDetails->getFrom(),
        $mailDetails->getAttachments(),
        $mailDetails->getCc(),
        $mailDetails->getBcc(),
        array_keys($form->_toContactDetails),
        $mailDetails->getAdditionalDetails(),
        $mailDetails->getAllContributionIDs(),
        CRM_Utils_Array::value('campaign_id', $formValues),
        $tplParams
      );
    }
    catch (Exception $e) {
      $contactId = $formattedContactDetail['contact_id'];
      $errors[] = ts('Exception found processing e-mail for contact with ID %1: %2', [
        1 => $contactId,
        2 => $e->getMessage(),
      ]);
    }
  }

  /**
   * Gets selected contribution ids for current contact
   *
   * @param $contactId
   * @param $contributionIds
   *
   * @return array
   */
  private static function getSelectedContributionIdsForCurrentContact($contactId, $contributionIds) {
    $validatedContributionIds = self::validateIds($contributionIds);
    $validatedContributionIdsImploded = implode(', ', $validatedContributionIds);
    $query = "
      SELECT contribution.id AS contribution_id
      FROM civicrm_contribution AS contribution
      WHERE contribution.contact_id = %1
        AND contribution.id IN(" . $validatedContributionIdsImploded . ")
    ";

    $dao = CRM_Core_DAO::executeQuery($query, [
      1 => [(int) $contactId, 'Integer'],
    ]);

    $contactContributionIds = [];
    while ($dao->fetch()) {
      $contactContributionIds[] = $dao->contribution_id;
    }

    return $contactContributionIds;
  }

}
