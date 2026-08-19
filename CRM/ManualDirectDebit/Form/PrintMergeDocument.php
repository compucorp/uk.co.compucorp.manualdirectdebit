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
      $this->postProcessDirectDebitMembers($this->_memberIds);
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
   *
   * @return void
   *
   * @throws \CRM_Core_Exception
   */
  protected function postProcessDirectDebitMembers($membershipIDs) {
    $formValues = $this->controller->exportValues($this->getName());
    list($formValues, $htmlMessage) = $this->processMessageTemplate($formValues);

    $membershipContacts = $this->getContactIdsKeyedByMembership($membershipIDs);

    $failedMemberships = [];
    $generatedHtmlList = [];
    // Keyed by contact ID so a contact holding several memberships is only
    // recorded once, however many letters they are sent.
    $letterContactIds = [];
    foreach ($membershipIDs as $membershipId) {
      $contactId = isset($membershipContacts[$membershipId]) ? $membershipContacts[$membershipId] : NULL;

      try {
        $generatedHtmlList[] = $this->generateDirectDebitHTML($membershipId, $contactId, $htmlMessage);
        $letterContactIds[$contactId] = TRUE;
      }
      catch (Exception $e) {
        $failedMemberships[$membershipId] = $e->getMessage();
      }
    }

    // Nothing to put in the document. Say so, rather than handing the user a
    // blank PDF, which is what an empty list would otherwise produce.
    if (empty($generatedHtmlList)) {
      $this->logFailedMemberships($failedMemberships);
      CRM_Core_Error::statusBounce(ts('No Direct Debit letters could be generated for the selected memberships. Check the log for the reason.'));
    }

    // Only the memberships that produced a letter, so nobody is recorded as
    // having been written to when they were not.
    $this->createActivities(
      $htmlMessage,
      array_keys($letterContactIds),
      isset($formValues['subject']) ? $formValues['subject'] : NULL,
      isset($formValues['campaign_id']) ? $formValues['campaign_id'] : NULL
    );

    CRM_Utils_PDF_Utils::html2pdf($generatedHtmlList, 'DirectDebitLetter.pdf', FALSE, $formValues);

    $this->postProcessHook();

    $this->logFailedMemberships($failedMemberships);

    CRM_Utils_System::civiExit();
  }

  /**
   * Returns the contact each of the given memberships belongs to.
   *
   * Read in one query rather than per membership, since the letters are
   * generated in bulk.
   *
   * @param array $membershipIDs
   *   IDs of the memberships to look up.
   *
   * @return array
   *   Contact ID, keyed by membership ID.
   */
  protected function getContactIdsKeyedByMembership($membershipIDs) {
    if (empty($membershipIDs)) {
      return [];
    }

    $memberships = \Civi\Api4\Membership::get(FALSE)
      ->addSelect('id', 'contact_id')
      ->addWhere('id', 'IN', $membershipIDs)
      ->execute();

    $contactIds = [];
    foreach ($memberships as $membership) {
      $contactIds[$membership['id']] = $membership['contact_id'];
    }

    return $contactIds;
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
   * @param int $contactId
   *   ID of the contact the membership belongs to.
   * @param string $htmlMessage
   *   Body of the selected message template.
   *
   * @return string
   *   The rendered letter.
   *
   * @throws \CRM_Core_Exception
   */
  protected function generateDirectDebitHTML($membershipId, $contactId, $htmlMessage) {
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
