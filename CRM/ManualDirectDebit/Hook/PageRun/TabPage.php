<?php

/**
 *  This class is responsible for displaying Direct Debit information block on
 *  the Contribution page
 */
class CRM_ManualDirectDebit_Hook_PageRun_TabPage {

  /**
   * Contribution Id
   *
   * @var
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

  /**
   *  Changes recurring contribution buttons
   */
  public function changeRecurringContributionButtons() {
    CRM_Core_Resources::singleton()
      ->addScriptFile('uk.co.compucorp.manualdirectdebit', 'js/changeRecurringContributionButton.js')
      ->addSetting([
        'urlData' => [
          'groupID' => CRM_ManualDirectDebit_Common_DirectDebitDataProvider::getGroupIDByName("direct_debit_mandate"),
          'cid' => CRM_Utils_Request::retrieve('cid', 'Integer'),
          'cgcount' => $this->getCgCount(),
        ],
        'recurringContributions' => [
          'listOfRecurrContributions' => $this->getRecurrContributionIds(),
        ],
      ]);
  }

  /**
   * Gets id`s of all recurring contribution with 'direct debit' payment instrument
   *
   * @return array
   */
  private function getRecurrContributionIds() {
    $contribution = civicrm_api3('ContributionRecur', 'get', [
      'sequential' => 1,
      'return' => ["id"],
      'payment_instrument_id' => "direct_debit",
    ]);

    $ids = [];
    foreach ($contribution['values'] as $recurr) {
      $ids[] = $recurr['id'];
    }

    return $ids;
  }

  /**
   * Gets mandate cgcount
   *
   * @return int
   */
  private function getCgCount() {
    $maxMandateId = CRM_ManualDirectDebit_Common_DirectDebitDataProvider::getMaxMandateId();
    return $maxMandateId + 1;
  }

}
