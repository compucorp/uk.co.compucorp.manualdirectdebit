<?php

class CRM_ManualDirectDebit_Mail_Task_Mail extends CRM_Activity_BAO_Activity {

  /**
   * Send the message to all the contacts.
   *
   * Also insert a contact activity in each contacts record.
   *
   * @param $tplParams
   * @param array $contactDetails
   *   The array of contact details to send the email.
   * @param string $subject
   *   The subject of the message.
   * @param $text
   * @param $html
   * @param string $emailAddress
   *   Use this 'to' email address instead of the default Primary address.
   * @param int $userID
   *   Use this userID if set.
   * @param string $from
   * @param array $attachments
   *   The array of attachments if any.
   * @param string $cc
   *   Cc recipient.
   * @param string $bcc
   *   Bcc recipient.
   * @param array $contactIds
   *   Contact ids.
   * @param string $additionalDetails
   *   The additional information of CC and BCC appended to the activity Details.
   * @param array $contributionIds
   * @param int $campaignId
   *
   * @return array
   *   ( sent, activityId) if any email is sent and activityId
   * @throws \CRM_Core_Exception
   */
  public static function sendDirectDebitEmail(
    &$contactDetails,
    &$subject,
    &$text,
    &$html,
    $emailAddress,
    $userID = NULL,
    $from = NULL,
    $attachments = NULL,
    $cc = NULL,
    $bcc = NULL,
    $contactIds = NULL,
    $additionalDetails = NULL,
    $contributionIds = NULL,
    $campaignId = NULL,
    $tplParams = []
  ) {
    // get the contact details of logged in contact, which we set as from email
    if ($userID == NULL) {
      $userID = CRM_Core_Session::getLoggedInContactID();
    }

    list($fromDisplayName, $fromEmail, $fromDoNotEmail) = CRM_Contact_BAO_Contact::getContactDetails($userID);
    if (!$fromEmail) {
      return array(count($contactDetails), 0, count($contactDetails));
    }
    if (!trim($fromDisplayName)) {
      $fromDisplayName = $fromEmail;
    }

    // CRM-4575
    // token replacement of addressee/email/postal greetings
    // get the tokens added in subject and message
    $subjectToken = CRM_Utils_Token::getTokens($subject);
    $messageToken = CRM_Utils_Token::getTokens($text);
    $messageToken = array_merge($messageToken, CRM_Utils_Token::getTokens($html));
    $allTokens = array_merge($messageToken, $subjectToken);

    if (!$from) {
      $from = "$fromDisplayName <$fromEmail>";
    }

    //create the meta level record first ( email activity )
    $activityTypeID = CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_type_id',
      'Email'
    );

    // CRM-6265: save both text and HTML parts in details (if present)
    if ($html and $text) {
      $details = "-ALTERNATIVE ITEM 0-\n$html$additionalDetails\n-ALTERNATIVE ITEM 1-\n$text$additionalDetails\n-ALTERNATIVE END-\n";
    }
    else {
      $details = $html ? $html : $text;
      $details .= $additionalDetails;
    }

    $activityParams = array(
      'source_contact_id' => $userID,
      'activity_type_id' => $activityTypeID,
      'activity_date_time' => date('YmdHis'),
      'subject' => $subject,
      'details' => $details,
      // FIXME: check for name Completed and get ID from that lookup
      'status_id' => 2,
      'campaign_id' => $campaignId,
    );

    // CRM-5916: strip [case #…] before saving the activity (if present in subject)
    $activityParams['subject'] = preg_replace('/\[case #([0-9a-h]{7})\] /', '', $activityParams['subject']);

    // add the attachments to activity params here
    if ($attachments) {
      // first process them
      $activityParams = array_merge($activityParams,
        $attachments
      );
    }

    $activity = self::create($activityParams);

    // get the set of attachments from where they are stored
    $attachments = CRM_Core_BAO_File::getEntityFile('civicrm_activity',
      $activity->id
    );
    $returnProperties = array();
    if (isset($messageToken['contact'])) {
      foreach ($messageToken['contact'] as $key => $value) {
        $returnProperties[$value] = 1;
      }
    }

    if (isset($subjectToken['contact'])) {
      foreach ($subjectToken['contact'] as $key => $value) {
        if (!isset($returnProperties[$value])) {
          $returnProperties[$value] = 1;
        }
      }
    }

    // get token details for contacts, call only if tokens are used
    $details = array();
    if (!empty($returnProperties) || !empty($tokens) || !empty($allTokens)) {
      list($details) = CRM_Utils_Token::getTokenDetails(
        $contactIds,
        $returnProperties,
        NULL, NULL, FALSE,
        $allTokens,
        'CRM_Activity_BAO_Activity'
      );
    }

    // call token hook
    $tokens = array();
    CRM_Utils_Hook::tokens($tokens);
    $categories = array_keys($tokens);
    $smarty = CRM_Core_Smarty::singleton();
    $escapeSmarty = TRUE;

    $contributionDetails = array();
    if (!empty($contributionIds)) {
      $contributionDetails = CRM_Contribute_BAO_Contribution::replaceContributionTokens(
        $contributionIds,
        $subject,
        $subjectToken,
        $text,
        $html,
        $messageToken,
        $escapeSmarty
      );
    }

    $sent = $notSent = array();
    foreach ($contactDetails as $values) {
      $contactId = $values['contact_id'];
      $emailAddress = $values['email'];

      if (!empty($contributionDetails)) {
        $subject = $contributionDetails[$contactId]['subject'];
        $text = $contributionDetails[$contactId]['text'];
        $html = $contributionDetails[$contactId]['html'];
      }

      if (!empty($details) && is_array($details["{$contactId}"])) {
        // unset email from details since it always returns primary email address
        unset($details["{$contactId}"]['email']);
        unset($details["{$contactId}"]['email_id']);
        $values = array_merge($values, $details["{$contactId}"]);
      }

      $tokenSubject = CRM_Utils_Token::replaceContactTokens($subject, $values, FALSE, $subjectToken, FALSE, $escapeSmarty);
      $tokenSubject = CRM_Utils_Token::replaceHookTokens($tokenSubject, $values, $categories, FALSE, $escapeSmarty);

      // CRM-4539
      if ($values['preferred_mail_format'] == 'Text' || $values['preferred_mail_format'] == 'Both') {
        $tokenText = CRM_Utils_Token::replaceContactTokens($text, $values, FALSE, $messageToken, FALSE, $escapeSmarty);
        $tokenText = CRM_Utils_Token::replaceHookTokens($tokenText, $values, $categories, FALSE, $escapeSmarty);
      }
      else {
        $tokenText = NULL;
      }

      if ($values['preferred_mail_format'] == 'HTML' || $values['preferred_mail_format'] == 'Both') {
        $tokenHtml = CRM_Utils_Token::replaceContactTokens($html, $values, TRUE, $messageToken, FALSE, $escapeSmarty);
        $tokenHtml = CRM_Utils_Token::replaceHookTokens($tokenHtml, $values, $categories, TRUE, $escapeSmarty);
      }
      else {
        $tokenHtml = NULL;
      }

      // also add the contact tokens to the template
      $smarty->assign_by_ref('contact', $values);
      foreach ($tplParams as $name => $value) {
        $smarty->assign($name, $value);
      }

      $tokenSubject = $smarty->fetch("string:$tokenSubject");
      $tokenText = $smarty->fetch("string:$tokenText");
      $tokenHtml = $smarty->fetch("string:$tokenHtml");

      $sent = FALSE;
      if (self::sendMessage(
        $from,
        $userID,
        $contactId,
        $tokenSubject,
        $tokenText,
        $tokenHtml,
        $emailAddress,
        $activity->id,
        $attachments,
        $cc,
        $bcc
      )
      ) {
        $sent = TRUE;
      }
    }

    return array($sent, $activity->id);
  }

}
