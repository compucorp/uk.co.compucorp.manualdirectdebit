<?php

class CRM_ManualDirectDebit_Mail_Task_Email_MembershipEmail extends CRM_ManualDirectDebit_Mail_Task_Email_AbstractEmailCommon {

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

    $subject = $formValues['subject'];

    // CRM-13378: Append CC and BCC information at the end of Activity Details and format cc and bcc fields
    $elements = array('cc_id', 'bcc_id');
    $additionalDetails = NULL;
    $ccValues = $bccValues = array();
    foreach ($elements as $element) {
      if (!empty($formValues[$element])) {
        $allEmails = explode(',', $formValues[$element]);
        foreach ($allEmails as $value) {
          list($contactId, $email) = explode('::', $value);
          $contactURL = CRM_Utils_System::url('civicrm/contact/view', "reset=1&force=1&cid={$contactId}", TRUE);
          switch ($element) {
            case 'cc_id':
              $ccValues['email'][] = '"' . $form->_contactDetails[$contactId]['sort_name'] . '" <' . $email . '>';
              $ccValues['details'][] = "<a href='{$contactURL}'>" . $form->_contactDetails[$contactId]['display_name'] . "</a>";
              break;

            case 'bcc_id':
              $bccValues['email'][] = '"' . $form->_contactDetails[$contactId]['sort_name'] . '" <' . $email . '>';
              $bccValues['details'][] = "<a href='{$contactURL}'>" . $form->_contactDetails[$contactId]['display_name'] . "</a>";
              break;
          }
        }
      }
    }

    $cc = $bcc = '';
    if (!empty($ccValues)) {
      $cc = implode(',', $ccValues['email']);
      $additionalDetails .= "\ncc : " . implode(", ", $ccValues['details']);
    }
    if (!empty($bccValues)) {
      $bcc = implode(',', $bccValues['email']);
      $additionalDetails .= "\nbcc : " . implode(", ", $bccValues['details']);
    }

    // CRM-5916: prepend case id hash to CiviCase-originating emails’ subjects
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
    $contributionIds = array();
    if ($form->getVar('_contributionIds')) {
      $contributionIds = $form->getVar('_contributionIds');
    }

    $membershipIds = array();
    if ($form->getVar('_memberIds')) {
      $membershipIds = $form->getVar('_memberIds');
    }

    $failedMembershipIds = [];
    foreach ($formattedContactDetails as $formattedContactDetail) {
      $contactId = $formattedContactDetail['contact_id'];
      $selectedMembershipIdsForCurrentContact = self::getSelectedMembershipIdsForCurrentContact($contactId, $membershipIds);
      foreach ($selectedMembershipIdsForCurrentContact as $membershipId) {
        try {
          $dataCollector = new CRM_ManualDirectDebit_Mail_DataCollector_Membership($membershipId);
          $tplParams = $dataCollector->retrieve();
          $contactDetail = [$formattedContactDetail];
          CRM_ManualDirectDebit_Mail_Task_Mail::sendDirectDebitEmail(
            $contactDetail,
            $subject,
            $formValues['text_message'],
            $formValues['html_message'],
            NULL,
            NULL,
            $from,
            $attachments,
            $cc,
            $bcc,
            array_keys($form->_toContactDetails),
            $additionalDetails,
            $contributionIds,
            CRM_Utils_Array::value('campaign_id', $formValues),
            $tplParams
          );
        }
        catch (Exception $e) {
          $failedMembershipIds[] = $membershipId;
        }
      }
    }

    if (!empty($failedMembershipIds)) {
      $membershipIdsMessagePart = implode(', ', $failedMembershipIds);
      Civi::log()->warning('No Emails were sent for the membership(s) with the following Id(s):' . $membershipIdsMessagePart);
    }

  }

  /**
   * Gets selected membership ids for current contact
   *
   * @param $contactId
   * @param $membershipIds
   *
   * @return array
   */
  private static function getSelectedMembershipIdsForCurrentContact($contactId, $membershipIds) {
    $validatedMembershipIds = self::validateIds($membershipIds);
    $validatedMembershipIdsImploded = implode(', ', $validatedMembershipIds);
    $query = "
      SELECT membership.id AS contribution_id
      FROM civicrm_membership AS membership
      WHERE membership.contact_id = %1
        AND membership.id IN(" . $validatedMembershipIdsImploded . ")
    ";

    $dao = CRM_Core_DAO::executeQuery($query, [
      1 => [(int) $contactId, 'Integer'],
    ]);

    $contactMembershipIds = [];
    while ($dao->fetch()) {
      $contactMembershipIds[] = $dao->contribution_id;
    }

    return $contactMembershipIds;
  }

}
