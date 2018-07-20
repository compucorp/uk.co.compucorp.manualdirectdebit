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

    $generatedHtmlList = [];
    foreach ($membershipIDs as $membershipId) {
      $dataCollector = new CRM_ManualDirectDebit_Mail_DataCollector_Membership($membershipId);
      $smartyParams = $dataCollector->retrieve();
      $htmlMessageSmartyHandled = self::handleHtmlBySmarty($html_message, $smartyParams);

      $generatedHtmlList[] = self::generateHTML(
        [$membershipId],
        $returnProperties,
        $skipOnHold,
        $skipDeceased,
        $messageToken,
        $htmlMessageSmartyHandled,
        $categories
      )[0];
    }

    self::createActivities($form, $html_message, $contactIDs, $formValues['subject'], CRM_Utils_Array::value('campaign_id', $formValues));

    CRM_Utils_PDF_Utils::html2pdf($generatedHtmlList, "DirectDebitLetter.pdf", FALSE, $formValues);

    $form->postProcessHook();

    CRM_Utils_System::civiExit(1);
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

}
