<?php

class CRM_ManualDirectDebit_Mail_Task_PDFLetterCommon extends CRM_Member_Form_Task_PDFLetterCommon {

  /**
   * Process the form after the input has been submitted and validated.
   *
   * @param CRM_Core_Form $form
   * @param $membershipIDs
   * @param $skipOnHold
   * @param $skipDeceased
   * @param $contactIDs
   */
  public static function postProcessMembers(&$form, $membershipIDs, $skipOnHold, $skipDeceased, $contactIDs) {
    $formValues = $form->controller->exportValues($form->getName());
    list($formValues, $categories, $html_message, $messageToken, $returnProperties) = self::processMessageTemplate($formValues);

    $failedMembershipIds = [];
    $generatedHtmlList = [];
    foreach ($membershipIDs as $membershipId) {
      try {
        $html = static::generateHTML(
          [$membershipId],
          $returnProperties,
          $skipOnHold,
          $skipDeceased,
          $messageToken,
          $html_message,
          $categories
        )[0];

        $dataCollector = new CRM_ManualDirectDebit_Mail_DataCollector_Membership($membershipId);
        $smartyParams = $dataCollector->retrieve();
        $generatedHtmlList[] = self::handleHtmlBySmarty($html, $smartyParams);
      }
      catch (Exception $e) {
        $failedMembershipIds[] = $membershipId;
      }
    }

    self::createActivities($form, $html_message, $contactIDs, $formValues['subject'], CRM_Utils_Array::value('campaign_id', $formValues));

    CRM_Utils_PDF_Utils::html2pdf($generatedHtmlList, "DirectDebitLetter.pdf", FALSE, $formValues);

    $form->postProcessHook();

    if (!empty($failedMembershipIds)) {
      $membershipIdsMessagePart = implode(', ', $failedMembershipIds);
      Civi::log()->warning('No Letters were generated for the membership(s) with the following Id(s):' . $membershipIdsMessagePart);
    }

    CRM_Utils_System::civiExit();
  }

  /**
   * Handles html by Smarty engine
   *
   * @param $html
   * @param $smartyParams
   *
   * @return bool|mixed|string
   */
  private static function handleHtmlBySmarty($html, $smartyParams) {
    $smarty = CRM_Core_Smarty::singleton();
    foreach ($smartyParams as $name => $value) {
      $smarty->assign($name, $value);
    }

    $htmlMessageSmartyHandled = $smarty->fetch("string:$html");

    return $htmlMessageSmartyHandled;
  }

  /**
   * Generate htmlfor pdf letters.
   *
   * @param array $membershipIDs
   * @param array $returnProperties
   * @param bool $skipOnHold
   * @param bool $skipDeceased
   * @param array $messageToken
   * @param $html_message
   * @param $categories
   *
   * @return array
   */
  public static function generateHTML($membershipIDs, $returnProperties, $skipOnHold, $skipDeceased, $messageToken, $html_message, $categories) {
    $memberships = CRM_Utils_Token::getMembershipTokenDetails($membershipIDs);
    $html = array();

    foreach ($membershipIDs as $membershipID) {
      $membership = $memberships[$membershipID];
      //get contact information
      $contactId = $membership['contact_id'];
      $params = array('contact_id' => $contactId);
      //getTokenDetails is much like calling the api contact.get function - but - with some minor
      //special handlings. It precedes the existence of the api
      list($contacts) = CRM_Utils_Token::getTokenDetails(
        $params,
        $returnProperties,
        $skipOnHold,
        $skipDeceased,
        NULL,
        $messageToken,
        'CRM_Contribution_Form_Task_PDFLetterCommon'
      );

      $tokenHtml = CRM_Utils_Token::replaceContactTokens($html_message, $contacts[$contactId], TRUE, $messageToken);
      $tokenHtml = CRM_Utils_Token::replaceEntityTokens('membership', $membership, $tokenHtml, $messageToken);
      $tokenHtml = CRM_Utils_Token::replaceHookTokens($tokenHtml, $contacts[$contactId], $categories, TRUE);

      $html[] = $tokenHtml;
    }

    return $html;
  }

}
