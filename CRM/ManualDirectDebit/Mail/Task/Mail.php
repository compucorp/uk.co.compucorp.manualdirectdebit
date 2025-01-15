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

    [$fromDisplayName, $fromEmail, $fromDoNotEmail] = CRM_Contact_BAO_Contact::getContactDetails($userID);
    if (!$fromEmail) {
      return [count($contactDetails), 0, count($contactDetails)];
    }
    if (!trim($fromDisplayName)) {
      $fromDisplayName = $fromEmail;
    }

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

    $activityParams = [
      'source_contact_id' => $userID,
      'activity_type_id' => $activityTypeID,
      'activity_date_time' => date('YmdHis'),
      'subject' => $subject,
      'details' => $details,
      // FIXME: check for name Completed and get ID from that lookup
      'status_id' => 2,
      'campaign_id' => $campaignId,
    ];

    // CRM-5916: strip [case #…] before saving the activity (if present in subject)
    $activityParams['subject'] = preg_replace('/\[.*#([0-9a-h]{7})\] /', '', $activityParams['subject']);

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

    $contributionDetails = [];
    if (!empty($contributionIds)) {
      $contributionDetails = \Civi\Api4\Contribution::get(FALSE)
        ->setSelect(['contact_id'])
        ->addWhere('id', 'IN', $contributionIds)
        ->execute()
        // Only the last contribution per contact is resolved to tokens.
        ->indexBy('contact_id');
    }

    $sent = [];
    foreach ($contactDetails as $values) {
      $tokenContext = [];
      $contactId = $values['contact_id'];
      $emailAddress = $values['email'];

      if (!empty($contributionDetails)) {
        $tokenContext['contributionId'] = $contributionDetails[$contactId]['id'];
      }

      $tokenSubject = $subject;

      $renderedTemplate = CRM_Core_BAO_MessageTemplate::renderTemplate([
        'messageTemplate' => [
          'msg_text' => $text,
          'msg_html' => $html,
          'msg_subject' => $tokenSubject,
        ],
        'tokenContext' => $tokenContext,
        'contactId' => $contactId,
        'disableSmarty' => FALSE,
        'tplParams' => $tplParams,
      ]);

      $sent = FALSE;
      if (self::sendMessage(
        $from,
        $userID,
        $contactId,
        $renderedTemplate['subject'],
        $renderedTemplate['text'],
        $renderedTemplate['html'],
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

    return [$sent, $activity->id];
  }

}
