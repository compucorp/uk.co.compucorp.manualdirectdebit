<?php

/**
 *  This class is responsible for displaying Direct Debit information block on
 *  the Contribution page
 */
class CRM_ManualDirectDebit_Hook_PageRun_TabPage {

  /**
   * Contribution Id
   *
   * @var int
   */
  private $contributionId;

  /**
   *  Hides Direct Debit information if the contribution doesn't has mandate
   *
   */
  public function hideDirectDebitFields() {
    if (isset($this->contributionId) && !empty($this->contributionId)) {

      if ($this->isCustomFieldNeedToHide($this->contributionId)) {

        CRM_Core_Resources::singleton()
          ->addScriptFile('uk.co.compucorp.manualdirectdebit', 'js/hideEmptyMandate.js');
      }
    }
  }

  /**
   * Checks if a mandate exist for current contribution
   *
   * @param $contributionId
   *
   * @return bool
   */
  private function isCustomFieldNeedToHide($contributionId) {
    $customFieldId = civicrm_api3('CustomField', 'getvalue', [
      'return' => "id",
      'name' => "mandate_id",
    ]);

    $mandateId = civicrm_api3('Contribution', 'getvalue', [
      'return' => "custom_$customFieldId",
      'id' => $contributionId,
    ]);

    return $mandateId == 0;
  }

  /**
   * Sets contribution Id
   *
   * @param $contributionId
   */
  public function setContributionId($contributionId) {
    $this->contributionId = $contributionId;
  }

}
