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

  /**
   * Recurring contribution Id
   *
   * @var int
   */

  private $currentContributionId;

  /**
   * Current payment method Id
   *
   * @var int
   */
  private $currentPaymentMethodId;

  public function __construct(&$page) {
    $this->page = $page;
    $this->currentContributionId = CRM_Utils_Request::retrieve('id', 'Integer', $this->page, FALSE);
    $this->currentPaymentMethodId = CRM_ManualDirectDebit_Common_DirectDebitDataProvider::getPaymentInstrumentIdOfRecurrContribution($this->currentContributionId);
  }

  /**
   * Injects 'Direct Debit Information' custom group inside 'Recurring
   * Contribution Detail' view
   *
   * @return bool
   */
  public function inject() {
    if(! CRM_ManualDirectDebit_Common_DirectDebitDataProvider::isPaymentMethodDirectDebit($this->currentPaymentMethodId)){
      return FALSE;
    }

    $mandateId = $this->getMandateId();

    if ($mandateId) {
      CRM_Core_Resources::singleton()
        ->addStyleFile('uk.co.compucorp.manualdirectdebit', 'css/directDebitMandate.css');
      CRM_Core_Resources::singleton()
        ->addScriptFile('uk.co.compucorp.manualdirectdebit', 'js/directDebitInformation.js')
        ->addSetting([
          'urlData' => [
            'gid' => CRM_ManualDirectDebit_Common_DirectDebitDataProvider::getGroupIDByName("direct_debit_mandate"),
            'cid' => CRM_Utils_Request::retrieve('cid', 'Integer', $this->page, FALSE),
            'recId' => $mandateId,
            'mandateId' => $mandateId,
          ],
        ]);
    }
  }

  /**
   * Gets id of mandate for recurrent contribution
   *
   * @return int
   */
  private function getMandateId() {
    $mandateIdCustomFieldId = CRM_ManualDirectDebit_Common_DirectDebitDataProvider::getCustomFieldIdByName("mandate_id");

    try {
      $mandateId = civicrm_api3('Contribution', 'get', [
        'sequential' => 1,
        'options' => ['limit' => 1],
        'return' => "custom_$mandateIdCustomFieldId",
        'contribution_recur_id' => $this->currentContributionId,
      ]);

      return $mandateId['values'][0]["custom_$mandateIdCustomFieldId"];
    } catch (CiviCRM_API3_Exception $e) {
      CRM_Core_Session::setStatus(t("Contribution doesn't exist"), $title = 'Error', $type = 'alert');

      return FALSE;
    }
  }

}
