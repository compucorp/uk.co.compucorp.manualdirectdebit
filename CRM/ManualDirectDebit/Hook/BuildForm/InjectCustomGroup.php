<?php

/**
 * Class provide injecting `Direct Debit Mandate` inside `Membership` form
 */
class CRM_ManualDirectDebit_Hook_BuildForm_InjectCustomGroup {

  /**
   * Path where template with new fields is stored.
   *
   * @var string
   */
  protected $templatePath;

  /**
   * Form object that is being altered.
   *
   * @var object
   */
  protected $form;

  public function __construct(&$form) {
    $this->form = $form;
    $this->templatePath = CRM_ManualDirectDebit_ExtensionUtil::path() . '/templates';
  }

  /**
   *  Builds form
   */
  public function buildForm() {
    $mandateDataProvider = new CRM_ManualDirectDebit_Common_DirectDebitDataProvider();
    $mandateCustomGroupFieldData = $mandateDataProvider->getMandateCustomFieldDataForBuildingForm();

    foreach ($mandateCustomGroupFieldData as $customField) {
      $this->form->add(
        $customField['html_type'],
        $customField['name'],
        $customField['label'],
        $customField['option_group_id'],
        $customField['is_required'],
        $customField['params']
      );

      if ($customField['data_type'] == 'Int') {
        $this->form->addRule($customField['name'], ts($customField['label'] . ' must be a number.'), 'numeric');
      }
    }

    $inputNames = $mandateDataProvider->getMandateCustomFieldNames();
    $this->form->assign('customInputNames', $inputNames);

    $minimumDaysToFirstPayment = $this->getMinimumDayForFirstPayment();
    $this->form->assign('minimumDaysToFirstPayment', $minimumDaysToFirstPayment);

    $directDebitPaymentInstrumentId = CRM_ManualDirectDebit_Common_DirectDebitDataProvider::getDirectDebitPaymentInstrumentId();
    $this->form->assign('directDebitPaymentInstrumentId', $directDebitPaymentInstrumentId);

    CRM_Core_Region::instance('page-body')->add([
      'template' => "{$this->templatePath}/CRM/ManualDirectDebit/Form/InjectCustomGroup.tpl",
    ]);
  }

  /**
   * Gets setting information about minimum days to first payment
   *
   * @return int
   */
  private function getMinimumDayForFirstPayment() {
    try {
      $minimumDaysToFirstPayment = CRM_ManualDirectDebit_Common_SettingsManager::getMinimumDayForFirstPayment();
    }
    catch (CiviCRM_API3_Exception $error) {
      $minimumDaysToFirstPayment = 0;
    }

    return $minimumDaysToFirstPayment;
  }

}
