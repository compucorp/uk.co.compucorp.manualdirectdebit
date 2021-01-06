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

  /**
   * @var string
   *   Path where the template for the auto renew section is soted.
   */
  private $templatePath;

  public function __construct(&$page) {
    $this->page = $page;
    $this->currentContributionId = CRM_Utils_Request::retrieve('id', 'Integer', $this->page, FALSE);
    $this->currentPaymentMethodId = CRM_ManualDirectDebit_Common_DirectDebitDataProvider::getPaymentInstrumentIdOfRecurrContribution($this->currentContributionId);
    $this->templatePath = CRM_ManualDirectDebit_ExtensionUtil::path() . '/templates';
  }

  /**
   * Injects 'Direct Debit Information' custom group inside 'Recurring
   * Contribution Detail' view
   *
   * @return bool
   */
  public function inject() {
    if (!CRM_ManualDirectDebit_Common_DirectDebitDataProvider::isPaymentMethodDirectDebit($this->currentPaymentMethodId)) {
      return FALSE;
    }

    $mandateId = CRM_ManualDirectDebit_BAO_RecurrMandateRef::getMandateIdForRecurringContribution($this->currentContributionId);

    if (is_null($mandateId)) {
      CRM_Core_Session::setStatus(t("Mandate doesn't exist"), $title = 'Error', $type = 'alert');

      return FALSE;
    }

    if ($mandateId) {
      $contactId = CRM_Utils_Request::retrieve('cid', 'Integer', $this->page);
      $contactType = civicrm_api3('Contact', 'getvalue', [
        'return' => 'contact_type',
        'id' => $contactId,
      ]);

      CRM_Core_Resources::singleton()->addVars('uk.co.compucorp.manualdirectdebit', [
        'contactType' => $contactType,
      ]);

      CRM_Core_Region::instance('page-body')->add([
        'template' => "{$this->templatePath}/CRM/ManualDirectDebit/Form/InjectDirectDebitInformation.tpl",
      ]);

      CRM_Core_Resources::singleton()
        ->addStyleFile('uk.co.compucorp.manualdirectdebit', 'css/directDebitMandate.css')
        ->addSetting([
          'urlData' => [
            'gid' => CRM_ManualDirectDebit_Common_DirectDebitDataProvider::getGroupIDByName("direct_debit_mandate"),
            'cid' => $contactId,
            'recId' => $mandateId,
            'mandateId' => $mandateId,
            'cgcount' => $this->getCgCount(),
            'recurringContribution' => $this->currentContributionId,
          ],
        ]);
    }
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
