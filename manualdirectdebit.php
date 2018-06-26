<?php

require_once 'manualdirectdebit.civix.php';

use CRM_ManualDirectDebit_ExtensionUtil as E;

/**
 * Implements hook_civicrm_config().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function manualdirectdebit_civicrm_config(&$config) {
  _manualdirectdebit_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_xmlMenu().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_xmlMenu
 */
function manualdirectdebit_civicrm_xmlMenu(&$files) {
  _manualdirectdebit_civix_civicrm_xmlMenu($files);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function manualdirectdebit_civicrm_install() {
  _manualdirectdebit_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_postInstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_postInstall
 */
function manualdirectdebit_civicrm_postInstall() {
  _manualdirectdebit_civix_civicrm_postInstall();
}

/**
 * Implements hook_civicrm_uninstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_uninstall
 */
function manualdirectdebit_civicrm_uninstall() {
  _manualdirectdebit_civix_civicrm_uninstall();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function manualdirectdebit_civicrm_enable() {
  _manualdirectdebit_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_disable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_disable
 */
function manualdirectdebit_civicrm_disable() {
  _manualdirectdebit_civix_civicrm_disable();
}

/**
 * Implements hook_civicrm_upgrade().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_upgrade
 */
function manualdirectdebit_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _manualdirectdebit_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implements hook_civicrm_managed().
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_managed
 */
function manualdirectdebit_civicrm_managed(&$entities) {
  _manualdirectdebit_civix_civicrm_managed($entities);
}

/**
 * Implements hook_civicrm_caseTypes().
 *
 * Generate a list of case-types.
 *
 * Note: This hook only runs in CiviCRM 4.4+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function manualdirectdebit_civicrm_caseTypes(&$caseTypes) {
  _manualdirectdebit_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implements hook_civicrm_angularModules().
 *
 * Generate a list of Angular modules.
 *
 * Note: This hook only runs in CiviCRM 4.5+. It may
 * use features only available in v4.6+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_angularModules
 */
function manualdirectdebit_civicrm_angularModules(&$angularModules) {
  _manualdirectdebit_civix_civicrm_angularModules($angularModules);
}

/**
 * Implements hook_civicrm_alterSettingsFolders().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_alterSettingsFolders
 */
function manualdirectdebit_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _manualdirectdebit_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

/**
 * Implements hook_civicrm_permission().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_permission/
 */
function manualdirectdebit_civicrm_permission(&$permissions) {
  $permissionsPrefix = 'CiviCRM : ';
  $permissions['can manage direct debit batches'] = $permissionsPrefix . ts('Can manage Direct Debit Batches');
}

/**
 * Implements hook_civicrm_navigationMenu().
 *
 */
function manualdirectdebit_civicrm_navigationMenu(&$menu) {
  $directDebitMenuItem = [
    'name' => ts('Direct Debit'),
    'url' => NULL,
    'permission' => 'administer CiviCRM',
    'operator' => NULL,
    'separator' => NULL,
  ];
  _manualdirectdebit_civix_insert_navigation_menu($menu, 'Administer/', $directDebitMenuItem);


  $subMenuItems = [
    [
      'name' => ts('Direct Debit Codes'),
      'url' => 'civicrm/admin/options/direct_debit_codes',
      'permission' => 'administer CiviCRM',
      'operator' => NULL,
      'separator' => NULL,
    ],
    [
      'name' => ts('Direct Debit Configuration'),
      'url' => 'civicrm/admin/direct_debit_configuration',
      'permission' => 'administer CiviCRM',
      'operator' => NULL,
      'separator' => NULL,
    ],
    [
      'name' => ts('Direct Debit Originator Number'),
      'url' => 'civicrm/admin/options/direct_debit_originator_number?reset=1',
      'permission' => 'administer CiviCRM',
      'operator' => NULL,
      'separator' => NULL,
    ],
  ];

  foreach ($subMenuItems as $menuItem) {
    _manualdirectdebit_civix_insert_navigation_menu($menu, 'Administer/' . $directDebitMenuItem['name'], $menuItem);
  }
}

/**
 * Implements hook_civicrm_postProcess().
 *
 */
function manualdirectdebit_civicrm_postProcess($formName, &$form) {

  $action = $form->getAction();

  switch ($formName) {
    case "CRM_Contact_Form_CustomData":
      if (isset($form->getVar('_submitValues')['recurrId']) && !empty($form->getVar('_submitValues')['recurrId'])) {
        $manualDirectDebit = new CRM_ManualDirectDebit_Hook_PostProcess_Contribution_DirectDebitMandate($form);
        $manualDirectDebit->changeMandateForRecurringContribution();
      };
      break;

    case "CRM_Contribute_Form_Contribution":
      if ($action == CRM_Core_Action::ADD) {
        $manualDirectDebit = new CRM_ManualDirectDebit_Hook_PostProcess_Contribution_DirectDebitMandate($form);
        $manualDirectDebit->checkPaymentOptionToCreateMandate();
      }
      break;

    case "CRM_Member_Form_Membership":
      if ($action == CRM_Core_Action::ADD) {
        $paymentInstrumentId = $form->getVar('_submitValues') ['payment_instrument_id'];
        if (CRM_ManualDirectDebit_Common_DirectDebitDataProvider::isPaymentMethodDirectDebit($paymentInstrumentId)) {
          $manualDirectDebit = new CRM_ManualDirectDebit_Hook_PostProcess_Membership_DirectDebitMandate($form);
          $manualDirectDebit->saveMandateData();
        }
      }
      break;

    case "CRM_Member_Form_MembershipRenewal":
      if ($action == CRM_Core_Action::RENEW) {
        $paymentInstrumentId = $form->getVar('_submitValues') ['payment_instrument_id'];
        if (CRM_ManualDirectDebit_Common_DirectDebitDataProvider::isPaymentMethodDirectDebit($paymentInstrumentId)) {
          $manualDirectDebit = new CRM_ManualDirectDebit_Hook_PostProcess_Membership_DirectDebitMandate($form);
          $manualDirectDebit->saveMandateData();
        }
      }
      break;
  }
}

/**
 * Implements hook_civicrm_pageRun().
 *
 */
function manualdirectdebit_civicrm_pageRun(&$page) {
  if (get_class($page) == 'CRM_Contribute_Page_Tab') {
    $contributionId = $page->getVar('_id');
    $pageProcessor = new CRM_ManualDirectDebit_Hook_PageRun_TabPage();
    $pageProcessor->setContributionId($contributionId);
    $pageProcessor->hideDirectDebitFields();

    CRM_Core_Resources::singleton()
      ->addScriptFile('uk.co.compucorp.manualdirectdebit', 'js/openContribution.js');
  }

  if (get_class($page) == 'CRM_Contribute_Page_ContributionRecur') {
    $injectCustomGroup = new CRM_ManualDirectDebit_Hook_PageRun_ContributionRecur_DirectDebitFieldsInjector($page);
    $injectCustomGroup->inject();
  }

  if (get_class($page) == 'CRM_Contact_Page_View_CustomData') {
    CRM_Core_Resources::singleton()
      ->addScriptFile('uk.co.compucorp.manualdirectdebit', 'js/mandateEdit.js');
  }
}

/**
 * Implements hook_civicrm_custom().
 *
 */
function manualdirectdebit_civicrm_custom($op, $groupID, $entityID, &$params) {
  if (CRM_ManualDirectDebit_Common_DirectDebitDataProvider::isDirectDebitCustomGroup($groupID)) {
    if ($op == 'create' || $op == 'edit') {
      $mandateDataGenerator = new CRM_ManualDirectDebit_Hook_Custom_DataGenerator($entityID, $params);
      $mandateDataGenerator->runDataGeneration();
    }

    if ($op == 'update') {
      $mandateDataGenerator = new CRM_ManualDirectDebit_Hook_Custom_DataGenerator($entityID, $params);
      $mandateDataGenerator->generateMandateData();
    }
  }
}

/**
 * Implements hook_civicrm_postSave_civicrm_contribution().
 */
function manualdirectdebit_civicrm_postSave_civicrm_contribution($dao) {
  $mandateContributionConnector = CRM_ManualDirectDebit_Hook_MandateContributionConnector::getInstance();
  $mandateContributionConnector->setContributionProperties($dao);
}

/**
 * Implements hook_civicrm_buildForm()
 */
function manualdirectdebit_civicrm_buildForm($formName, &$form) {
  if ($formName == 'CRM_Activity_Form_ActivityLinks') {
    $openContributionId = CRM_Utils_Request::retrieveValue('openContribution', 'Integer', FALSE);
    if ($openContributionId){
      $form->add('hidden', 'optionContributionId', $openContributionId);
    }
  }
  if ($formName == 'CRM_Contact_Form_CustomData') {
    $customData = new CRM_ManualDirectDebit_Hook_BuildForm_CustomData($form);
    $customData->run();
  }

  if ($formName == 'CRM_Custom_Form_CustomDataByType') {
    $customDataByType = new CRM_ManualDirectDebit_Hook_BuildForm_CustomDataByType($form);
    $customDataByType->run();
  }

  if ($formName === 'CRM_Member_Form_Membership' || $formName === 'CRM_Member_Form_MembershipRenewal') {
    $customGroupInjector = new CRM_ManualDirectDebit_Hook_BuildForm_InjectCustomGroup($form);
    $customGroupInjector->buildForm();
  }
}

/**
 * Implements hook_civicrm_validateForm()
 */
function manualdirectdebit_civicrm_validateForm($formName, &$fields, &$files, &$form, &$errors) {

  if ($formName == 'CRM_Member_Form_Membership' || $formName === 'CRM_Member_Form_MembershipRenewal') {
    $directDebitValidator = new CRM_ManualDirectDebit_Hook_ValidateForm_MandateValidator($form);
    $directDebitValidator->checkValidation();
  }
}

/**
 * Implements hook_civicrm_postSave_civicrm_membership_payment().
 */
function manualdirectdebit_civicrm_postSave_civicrm_membership_payment($dao) {
  $mandateCreator = new CRM_ManualDirectDebit_Hook_PostSave_MembershipPayment_MandateCreator($dao);
  $mandateCreator->assignMandateForContributions();
}

/**
 * Implements hook_civicrm_links().
 */
function manualdirectdebit_civicrm_links($op, $objectName, $objectId, &$links, &$mask, &$values) {
  $linkProvider = new CRM_ManualDirectDebit_Hook_Links_LinkProvider($links);

  if ($objectName == 'Contribution' && $op == 'contribution.selector.recurring') {
    $linkProvider->alterRecurContributionLinks($values, $objectId);
  }

  if ($objectName == 'Batch') {
    $linkProvider->alterBatchLinks($objectId);
  }
}

/**
 * Implements hook_membershipextras_postOfflineAutoRenewal()
 */
function manualdirectdebit_membershipextras_postOfflineAutoRenewal($membershipId, $recurContributionId) {
  $activity = new CRM_ManualDirectDebit_Hook_PostOfflineAutoRenewal_Activity($recurContributionId);
  $activity->process();
}

/**
 * Implements hook_civicrm_post()
 */
function manualdirectdebit_civicrm_post($op, $objectName, $objectId, &$objectRef) {
  if ($objectName == "ContributionRecur" && ($op == "create" || $op == "edit")) {
    $activity = new CRM_ManualDirectDebit_Hook_Post_RecurContribution_Activity($objectId, $op);
    $activity->process();
  }
}

