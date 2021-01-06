<?php

/**
 * Implements hook to alter CRM_Financial_Form_Payment form to add options
 * related to DD when the payment method is chosen.
 */
class CRM_ManualDirectDebit_Hook_BuildForm_Payment {

  /**
   * Payment form object that is being altered.
   *
   * @var \CRM_Financial_Form_Payment
   */
  private $form;

  /**
   * Template path for the extension.
   *
   * @var string
   */
  private $templatePath;

  /**
   * List of DD codes in the system.
   *
   * @var array
   */
  private static $ddCodes;

  /**
   * CRM_ManualDirectDebit_Hook_BuildForm_Payment constructor.
   *
   * @param \CRM_Financial_Form_Payment $form
   */
  public function __construct(CRM_Financial_Form_Payment $form) {
    $this->form = $form;
    $this->templatePath = CRM_ManualDirectDebit_ExtensionUtil::path() . '/templates';
  }

  /**
   * Alters the form if direct debit was chosen as payment method.
   */
  public function buildForm() {
    if (!$this->form->paymentInstrumentID || !$this->isDirectDebitPaymentInstrument($this->form->paymentInstrumentID)) {
      return;
    }

    // Selecting mandate is not needed when recording payments
    if ($this->form->_formName == 'AdditionalPayment') {
      return;
    }

    $contactID = CRM_Utils_Request::retrieve('cid', 'Int');
    if (empty($contactID)) {
      CRM_Core_Session::setStatus(
        "In order to use Direct Debit as payment method, a contact must first be selected! ",
        "Error - No Contact Selected",
        'error'
      );

      return;
    }

    $this->addMandateSelectionField();
    $this->addJSCreateMandateEventHandler();
  }

  /**
   * Adds select field to choose mandates.
   */
  private function addMandateSelectionField() {
    $this->form->assign('paymentTypeLabel', ts('Direct Debit Mandate'));
    $templateVars = $this->form->get_template_vars();

    $paymentFields = $templateVars['paymentFields'];
    $paymentFields[] = 'mandate_id';
    $this->form->assign('paymentFields', $paymentFields);

    $requiredFields = $templateVars['requiredPaymentFields'];
    $requiredFields[] = 'mandate_id';
    $this->form->assign('requiredPaymentFields', $requiredFields);

    $extraAttributes = [];
    $isEditRecurContributionForm = $this->form->_formName == 'UpdateSubscription';
    if ($isEditRecurContributionForm) {
      $extraAttributes['disabled'] = TRUE;
    }

    $contactID = CRM_Utils_Request::retrieve('cid', 'Int');
    $this->form->setDefaults(['mandate_id' => $this->getNewlyCreatedMandateID($contactID)]);
    $this->form->add(
      'select',
      'mandate_id',
      'Mandate',
      $this->getMandateOptionsForContact($contactID),
      TRUE,
      $extraAttributes
    );
  }

  /**
   * Obtains ID or most recently created mandate not used on any contributions.
   *
   * @param int $contactID
   */
  private function getNewlyCreatedMandateID($contactID) {
    $sqlSelectDebitMandateID = "
      SELECT MAX(`id`) AS id
      FROM " . CRM_ManualDirectDebit_Common_MandateStorageManager::DIRECT_DEBIT_TABLE_NAME . "
      WHERE `entity_id` = %1
      AND id NOT IN (
        SELECT mandate_id
        FROM civicrm_value_dd_information
      )
    ";
    $queryResult = CRM_Core_DAO::executeQuery($sqlSelectDebitMandateID, [
      1 => [
        $contactID,
        'String',
      ],
    ]);
    $queryResult->fetch();

    return $queryResult->id;
  }

  /**
   * Inject JS logic to show mandate creation modal.
   */
  private function addJSCreateMandateEventHandler() {
    CRM_Core_Region::instance('billing-block-post')->add([
      'template' => "{$this->templatePath}/CRM/ManualDirectDebit/Form/Payment.tpl",
    ]);
  }

  /**
   * Obtains mandates for given contact.
   *
   * @param int $contactID
   *
   * @return array
   */
  private function getMandateOptionsForContact($contactID) {
    $config = CRM_Core_Config::singleton();
    $mandateStorage = new CRM_ManualDirectDebit_Common_MandateStorageManager();
    $mandates = $mandateStorage->getMandatesForContact($contactID);

    $result = ['-' => ' - select - '];
    foreach ($mandates as $currentMandate) {
      $result[$currentMandate['id']] =
        $currentMandate['dd_ref'] . ' - ' .
        $this->getMandateCode($currentMandate['dd_code']) . ' - ' .
        $currentMandate['ac_number'] . ' - ' .
        CRM_Utils_Date::customFormat($currentMandate['start_date'], $config->dateformatFull);
    }

    $result[0] = 'Create New Mandate...';

    return $result;
  }

  /**
   * Given a dd_code value, returns its corresponding label.
   *
   * @param string $codeID
   *
   * @return string
   */
  private function getMandateCode($codeID) {
    if (empty(self::$ddCodes)) {
      self::$ddCodes = CRM_Core_OptionGroup::values('direct_debit_codes');
    }

    return CRM_Utils_Array::value($codeID, self::$ddCodes, '');
  }

  /**
   * Checks if given payment instrument corresponds to Direct Debit.
   */
  private function isDirectDebitPaymentInstrument($paymentIsntrumentID) {
    $result = civicrm_api3('OptionValue', 'getsingle', [
      'sequential' => 1,
      'option_group_id' => 'payment_instrument',
      'value' => $paymentIsntrumentID,
      'options' => ['limit' => 0],
    ]);

    if ($result['name'] === 'direct_debit') {
      return TRUE;
    }

    return FALSE;
  }

}
