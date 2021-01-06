<?php

use CRM_ManualDirectDebit_ExtensionUtil as E;
use CRM_ManualDirectDebit_Common_SettingsManager as SettingsManager;

/**
 * Direct Debt Configuration form controller
 *
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC43/QuickForm+Reference
 */
class CRM_ManualDirectDebit_Form_Configurations extends CRM_Core_Form {

  /**
   * Contains array of names, which must be displayed
   * in Mandate configuration section
   *
   * @var string[]
   */

  private $mandateConfigs = [];

  /**
   * Contains array of names, which must be displayed
   * in Payment configuration section
   *
   * @var string[]
   */

  private $paymentConfigs = [];

  /**
   * Contains array of names, which must be displayed
   * in Reminder configuration section
   *
   * @var string[]
   */

  private $reminderConfig = [];

  /**
   * Contains array of names, which must be displayed
   * in Reminder configuration section
   *
   * @var string[]
   */

  private $batchConfig = [];

  public function buildQuickForm() {
    CRM_Utils_System::setTitle(E::ts('Direct Debit Configurations'));

    $fieldsWithHelp = [];
    $allowedConfigFields  = SettingsManager::getConfigFields();
    foreach ($allowedConfigFields as $name => $config) {
      $this->add(
        $config['html_type'],
        $name,
        ts($config['title']),
        CRM_Utils_Array::value('html_attributes', $config, []),
        $config['is_required'],
        CRM_Utils_Array::value('extra_data', $config, [])
      );

      if ($config['is_help']) {
        $fieldsWithHelp[$name] = $config['is_help'];
      }

      $this->divideConfigSections($name, $config['section']);
    }

    $this->addButtons([
      [
        'type' => 'submit',
        'name' => ts('Submit'),
        'isDefault' => TRUE,
      ],
      [
        'type' => 'cancel',
        'name' => ts('Cancel'),
      ],
    ]);

    $this->assign('mandateConfigSection', $this->mandateConfigs);
    $this->assign('paymentConfigSection', $this->paymentConfigs);
    $this->assign('reminderConfigSection', $this->reminderConfig);
    $this->assign('batchConfigSection', $this->batchConfig);
    $this->assign('fieldsWithHelp', $fieldsWithHelp);

  }

  public function postProcess() {
    $allowedConfigFields = SettingsManager::getConfigFields();
    $submittedValues = $this->exportValues();
    $valuesToSave = array_intersect_key($submittedValues, $allowedConfigFields);
    civicrm_api3('setting', 'create', $valuesToSave);
  }

  /**
   * Set defaults for form.
   *
   * @see CRM_Core_Form::setDefaultValues()
   */
  public function setDefaultValues() {
    $currentValues = civicrm_api3('setting', 'get',
      ['return' => array_keys(SettingsManager::getConfigFields())]);
    $defaults = [];
    $domainID = CRM_Core_Config::domainID();
    foreach ($currentValues['values'][$domainID] as $name => $value) {
      $defaults[$name] = $value;
    }

    return $defaults;
  }

  /**
   * Divides fields between UI sections
   *
   * @param $name
   * @param $section
   */
  private function divideConfigSections($name, $section) {
    switch ($section) {
      case 'mandate_config':
        $this->mandateConfigs[] = $name;
        break;

      case 'payment_config':
        $this->paymentConfigs[] = $name;
        break;

      case 'reminder_config':
        $this->reminderConfig[] = $name;
        break;

      case 'batch_config':
        $this->batchConfig[] = $name;
        break;

    }
  }

}
