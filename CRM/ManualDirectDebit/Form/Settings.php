<?php
/*--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
+--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2017                                |
+--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +-------------------------------------------------------------------*/

use CRM_ManualDirectDebit_ExtensionUtil as E;

/**
 * Form controller class
 *
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC43/QuickForm+Reference
 */
class CRM_ManualDirectDebit_Form_Settings extends CRM_Core_Form {

  private $_settingFilter = ['group' => 'manualdirectdebit'];

  private $_settings = [];

  private $_mandateConfigs = [];

  private $_paymentConfigs = [];

  /**
   * Build Quick Form
   */
  public function buildQuickForm() {
    CRM_Utils_System::setTitle(E::ts('Direct Debit Configurations'));
    $settings = $this->getFormSettings();

    //  array will contain name of fields which contain appropriate help texts
    $isHelp = [];

    //  getting our field elements
    foreach ($settings as $name => $setting) {
      if (isset($setting['quick_form_type'])) {

        // create and specify setting field
        $this->add(
          $setting['html_type'],
          $name,
          ts($setting['title']),
          CRM_Utils_Array::value('html_attributes', $setting, []),
          $setting['is_required'],
          CRM_Utils_Array::value('extra_data', $setting, [])
        );

        // detect if field has appropriate help text
        if ($setting['is_help']) {
          $isHelp[$name] = $setting['is_help'];
        }

        $this->divideBetweenUISections($setting);
      }
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

    // export form elements
    $this->assign('mandateConfigSection', $this->_mandateConfigs);
    $this->assign('paymentConfigSection', $this->_paymentConfigs);
    $this->assign('isHelp', $isHelp);
    parent::buildQuickForm();
  }

  /**
   * After submit actions
   */
  public function postProcess() {
    $settings = $this->getFormSettings();
    $submittedValues = $this->exportValues();
    $values = array_intersect_key($submittedValues, $settings);
    try {
      civicrm_api('setting', 'create', $values + ['version' => 3]);
    }
    catch (Exception $e) {
      CRM_Core_Session::setStatus($e->getMessage(), ts('Error'), 'error');
    }
    parent::postProcess();
  }

  /**
   * Get the settings we are going to allow to be set on this form.
   *
   * @return array
   */
  public function getFormSettings() {
    if (empty($this->_settings)) {
      try {
        $this->_settings = civicrm_api('setting', 'getfields',
          [
            'filters' => $this->_settingFilter,
            'version' => 3,
          ]);
      }
      catch (Exception $e) {
        CRM_Core_Session::setStatus($e->getMessage(), ts('Error'), 'error');
        return [];
      }
    }
    return $this->_settings['values'];
  }

  /**
   * Set defaults for form.
   *
   * @see CRM_Core_Form::setDefaultValues()
   */
  public function setDefaultValues() {
    try {
      $existing = civicrm_api('setting', 'get',
        [
          'return' => array_keys($this->getFormSettings()),
          'version' => 3,
        ]);
    }
    catch (Exception $e) {
      CRM_Core_Session::setStatus($e->getMessage(), ts('Error'), 'error');
      return [];
    }
    $defaults = [];
    $domainID = CRM_Core_Config::domainID();
    foreach ($existing['values'][$domainID] as $name => $value) {
      $defaults[$name] = $value;
    }
    return $defaults;
  }

  /**
   * @param $setting
   */
  private function divideBetweenUISections($setting) {
    switch ($setting['section']) {
      case 'mandate_config':
        $this->_mandateConfigs[] = $setting['name'];
        break;

      case 'payment_config':
        $this->_paymentConfigs[] = $setting['name'];
        break;

    }
  }

}
