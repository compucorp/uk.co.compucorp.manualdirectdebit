<?php

/**
 *  Provides 'Direct Debit Information' integration into 'Recurring
 * Contribution Detail' view
 */
class CRM_ManualDirectDebit_Hook_PageRun_ContributionRecur_DirectDebitFieldsInjector {

  /**
   * Recurring contribution page object from Hook
   *
   * @var object
   */
  private $page;

  public function __construct(&$page) {
    $this->page = $page;
  }

  /**
   * Injects 'Direct Debit Information' custom group inside 'Recurring
   * Contribution Detail' view
   *
   */
  public function inject() {
    $mandateId = $this->getMandateId();

    if ($mandateId) {
      CRM_Core_Resources::singleton()
        ->addStyleFile('uk.co.compucorp.manualdirectdebit', 'css/directDebitMandate.css');
      CRM_Core_Resources::singleton()
        ->addScriptFile('uk.co.compucorp.manualdirectdebit', 'js/directDebitInformation.js')
        ->addSetting([
          'urlData' => [
            'gid' => $this->getGroupIDbyName("direct_debit_mandate"),
            'cid' => CRM_Utils_Request::retrieve('cid', 'Integer', $this->page, FALSE),
            'recId' => $mandateId,
            'mandateId' => $mandateId,
          ],
        ]);
    }
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
    $currentContributionId = CRM_Utils_Request::retrieve('id', 'Integer', $this->page, FALSE);
    try {
      $mandateId = civicrm_api3('Contribution', 'get', [
        'sequential' => 1,
        'options' => ['limit' => 1],
        'return' => "custom_$mandateIdCustomFieldId",
        'contribution_recur_id' => $currentContributionId,
      ]);

      return $mandateId['values'][0]["custom_$mandateIdCustomFieldId"];
    } catch (CiviCRM_API3_Exception $e) {
      CRM_Core_Session::setStatus(t("Contribution doesn't exist"), $title = 'Error', $type = 'alert');

      return FALSE;
    }
  }

}
