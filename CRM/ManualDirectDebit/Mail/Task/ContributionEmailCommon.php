<?php

class CRM_ManualDirectDebit_Mail_Task_ContributionEmailCommon extends CRM_Contact_Form_Task_EmailCommon {

  /**
   * Process the form after the input has been submitted and validated.
   *
   * @param CRM_Core_Form $form
   *
   * @throws \CRM_Core_Exception
   */
  public static function postProcess(&$form) {
    self::bounceIfSimpleMailLimitExceeded(count($form->_contactIds));

    // check and ensure that
    $formValues = $form->controller->exportValues($form->getName());
    self::submit($form, $formValues);
  }

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
  public static function submit(&$form, $formValues) {
    self::saveMessageTemplate($formValues);

    $from = CRM_Utils_Array::value($formValues['fromEmailAddress'], $form->_emails);
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

    foreach ($formattedContactDetails as $formattedContactDetail) {
      $contactId = $formattedContactDetail['contact_id'];
      $selectedContributionIdsForCurrentContact = self::getSelectedContributionIdsForCurrentContact($contactId, $contributionIds);
      foreach ($selectedContributionIdsForCurrentContact as $contributionId) {
        $dataCollector = new CRM_ManualDirectDebit_Mail_DataCollector_Contribution($contributionId);
        $tplParams = $dataCollector->retrieve();
        $contactDetail = [$formattedContactDetail];
        CRM_ManualDirectDebit_Mail_Task_Mail::sendEmail(
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
        AND contribution.id IN(". $validatedContributionIdsImploded .")
    ";

    $dao = CRM_Core_DAO::executeQuery($query, [
      1 => [(int) $contactId, 'Integer']
    ]);

    $contactContributionIds = [];
    while ($dao->fetch()) {
      $contactContributionIds[] = $dao->contribution_id;
    }

    return $contactContributionIds;
  }

  /**
   * Validates ids
   *
   * @param $ids
   *
   * @return array
   */
  private static function validateIds($ids) {
    if (is_array($ids)) {
      $validatedIds = [];
      foreach ($ids as $id) {
        $validatedIds[] = (int) $id;
      }

      return $validatedIds;
    }

    return [];
  }

}