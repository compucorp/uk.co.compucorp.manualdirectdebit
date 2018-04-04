<?php

/**
 *  Provides 'Direct Debit Information' integration into 'Recurring Contribution Detail' view
 */
class CRM_ManualDirectDebit_Hook_PageRun_ContributionRecur_DirectDebitFieldsInjector {

  /**
   * Injects 'Direct Debit Information' custom group inside 'Recurring Contribution Detail' view
   *
   */
  public function inject() {
    CRM_Core_Resources::singleton()
      ->addScriptFile('uk.co.compucorp.manualdirectdebit', 'js/directDebitInformation.js')
      ->addSetting([
        'urlData' => [
          'gid' => $this->getGroupIDbyName("direct_debit_mandate"),
          'cid' => CRM_Utils_Request::retrieve('cid', 'Integer', $page, FALSE),
          'recId' => $this->getContributionId(),
          'mandateId' => $this->getMandateId(),
        ],
      ]);
  }

  /**
   * Gets id of custom group by name
   *
   * @param $customGroupName
   *
   * @return int
   */
  private function getGroupIDByName($customGroupName) {
    return civicrm_api3('CustomGroup', 'getvalue', [
      'return' => "id",
      'name' => $customGroupName,
    ]);
  }

  /**
   * Gets id of custom field by name
   *
   * @param $customFieldName
   *
   * @return int
   */
  private function getCustomFieldIdByName($customFieldName) {
    return civicrm_api3('CustomField', 'getvalue', [
      'return' => "id",
      'name' => $customFieldName,
    ]);
  }

  /**
   * Gets id of mandate for recurrent contribution
   *
   * @return int
   */
  private function getMandateId() {
    $mandateIdCustomFieldId = $this->getCustomFieldIdByName("mandate_id");
    $currentContributionId = CRM_Utils_Request::retrieve('id', 'Integer', $page, FALSE);
    try {
      $mandateId = civicrm_api3('Contribution', 'getvalue', [
        'return' => "custom_$mandateIdCustomFieldId",
        'contribution_recur_id' => $currentContributionId,
      ]);

      return $mandateId;
    }
    catch (CiviCRM_API3_Exception $e) {
      CRM_Core_Session::setStatus(t("Contribution doesn't exist"), $title = 'Error', $type = 'alert');

      return FALSE;
    }
  }

  /**
   * Gets id of individual contribution for recurrent contribution
   *
   * @return int
   */
  private function getContributionId() {
    $currentContributionId = CRM_Utils_Request::retrieve('id', 'Integer', $page, FALSE);
    try {
      $contributionId = civicrm_api3('Contribution', 'getvalue', [
        'return' => "id",
        'contribution_recur_id' => $currentContributionId,
      ]);

      return $contributionId;
    }
    catch (CiviCRM_API3_Exception $e) {
      CRM_Core_Session::setStatus(t("Contribution doesn't exist"), $title = 'Error', $type = 'alert');

      return FALSE;
    }
  }

}
