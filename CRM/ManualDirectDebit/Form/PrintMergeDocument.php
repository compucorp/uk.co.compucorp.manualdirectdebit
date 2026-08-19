<?php

/**
 * Membership search task that prints Direct Debit letters.
 *
 * Direct Debit message templates are Smarty templates which rely on the
 * template parameters collected per membership by
 * CRM_ManualDirectDebit_Mail_DataCollector_Membership (bank/mandate details,
 * the Direct Debit logo, the instalment schedule, and so on). Core's letter
 * task knows nothing about those, so this task renders Direct Debit templates
 * itself and defers to core for every other template.
 */
class CRM_ManualDirectDebit_Form_PrintMergeDocument extends CRM_Member_Form_Task_PDFLetter {

  /**
   * Process the form after the input has been submitted and validated.
   *
   * @return void
   *
   * @throws \CRM_Core_Exception
   */
  public function postProcess() {
    $submitValues = $this->getVar('_submitValues');
    $messageTemplateId = empty($submitValues['template']) ? 0 : (int) $submitValues['template'];

    if ($messageTemplateId && CRM_ManualDirectDebit_Common_MessageTemplate::isDirectDebitTemplate($messageTemplateId)) {
      $this->setContactIDs();
      $this->postProcessDirectDebitMembers($this->_memberIds, $this->_contactIds);
      return;
    }

    parent::postProcess();
  }

  /**
   * Generates the Direct Debit letters for the given memberships.
   *
   * This mirrors CRM_Member_Form_Task_PDFLetter::postProcessMembers(), except
   * that each letter is rendered on its own so that a membership missing the
   * data a Direct Debit template needs is skipped and logged instead of
   * aborting the whole run.
   *
   * @param array $membershipIDs
   *   IDs of the memberships to generate letters for.
   * @param array $contactIDs
   *   IDs of the contacts the letters are addressed to.
   *
   * @return void
   *
   * @throws \CRM_Core_Exception
   */
  protected function postProcessDirectDebitMembers($membershipIDs, $contactIDs) {
    $formValues = $this->controller->exportValues($this->getName());
    list($formValues, $htmlMessage) = $this->processMessageTemplate($formValues);

    $failedMemberships = [];
    $generatedHtmlList = [];
    foreach ($membershipIDs as $membershipId) {
      try {
        $generatedHtmlList[] = $this->generateDirectDebitHTML($membershipId, $htmlMessage);
      }
      catch (Exception $e) {
        $failedMemberships[$membershipId] = $e->getMessage();
      }
    }

    $this->createActivities(
      $htmlMessage,
      $contactIDs,
      isset($formValues['subject']) ? $formValues['subject'] : NULL,
      isset($formValues['campaign_id']) ? $formValues['campaign_id'] : NULL
    );

    CRM_Utils_PDF_Utils::html2pdf($generatedHtmlList, 'DirectDebitLetter.pdf', FALSE, $formValues);

    $this->postProcessHook();

    $this->logFailedMemberships($failedMemberships);

    CRM_Utils_System::civiExit();
  }

  /**
   * Renders the Direct Debit letter for a single membership.
   *
   * Smarty is switched on explicitly here. Core's letter task honours the
   * CIVICRM_MAIL_SMARTY setting, which is disabled by default, but the Direct
   * Debit templates are written as Smarty templates: without it the collected
   * template parameters would be dropped and the letter would come out with
   * its markup unresolved. This matches how the Direct Debit emails are
   * rendered, where Smarty is on by default.
   *
   * @param int $membershipId
   *   ID of the membership to render the letter for.
   * @param string $htmlMessage
   *   Body of the selected message template.
   *
   * @return string
   *   The rendered letter.
   *
   * @throws \CRM_Core_Exception
   */
  protected function generateDirectDebitHTML($membershipId, $htmlMessage) {
    $contactId = civicrm_api3('Membership', 'getvalue', [
      'id' => $membershipId,
      'return' => 'contact_id',
    ]);

    $dataCollector = new CRM_ManualDirectDebit_Mail_DataCollector_Membership($membershipId);

    return CRM_Core_BAO_MessageTemplate::renderTemplate([
      'messageTemplate' => ['msg_html' => $htmlMessage],
      'contactId' => $contactId,
      'tokenContext' => ['membershipId' => $membershipId],
      'tplParams' => $dataCollector->retrieve(),
      'disableSmarty' => FALSE,
    ])['html'];
  }

  /**
   * Logs the memberships no letter could be generated for.
   *
   * @param array $failedMemberships
   *   Reason why the letter failed, keyed by membership ID.
   *
   * @return void
   */
  protected function logFailedMemberships($failedMemberships) {
    if (empty($failedMemberships)) {
      return;
    }

    $reasons = [];
    foreach ($failedMemberships as $membershipId => $reason) {
      $reasons[] = $membershipId . ': ' . $reason;
    }

    Civi::log()->warning(
      'No Letters were generated for the membership(s) with the following Id(s):' . implode(', ', array_keys($failedMemberships)),
      ['reasons' => $reasons]
    );
  }

}
