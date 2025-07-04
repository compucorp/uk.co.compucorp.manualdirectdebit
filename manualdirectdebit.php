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
 * Implements hook_civicrm_install().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function manualdirectdebit_civicrm_install() {
  _manualdirectdebit_civix_civicrm_install();
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
 * Implements hook_civicrm_permission().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_permission/
 */
function manualdirectdebit_civicrm_permission(&$permissions) {
  $permissionsPrefix = 'CiviCRM : ';
  $permissions['can manage direct debit batches'] = [
    'label' => $permissionsPrefix . ts('Can manage Direct Debit Batches'),
    'description' => $permissionsPrefix . ts('Can manage Direct Debit Batches'),
  ];

  $permissions['administer ManualDirectDebit'] = [
    'label' => E::ts('MembershipExtras: administer Manual Direct Debit'),
    'description' => E::ts('Perform all Manual Direct Debit administration tasks in CiviCRM'),
  ];
}

/**
 * Implements hook_civicrm_alterAPIPermissions().
 *
 */
function manualdirectdebit_civicrm_alterAPIPermissions($entity, $action, &$params, &$permissions) {
  if ($entity == 'manual_direct_debit' && $action == 'deletemandate') {
    $permissions[$entity][$action] = ['administer ManualDirectDebit'];
  }
}

/**
 * Implements hook_civicrm_navigationMenu().
 *
 */
function manualdirectdebit_civicrm_navigationMenu(&$menu) {
  $directDebitMenuItem = [
    'name' => ts('Direct Debit'),
    'url' => NULL,
    'permission' => 'administer CiviCRM, administer ManualDirectDebit',
    'operator' => 'OR',
    'separator' => NULL,
  ];
  _manualdirectdebit_civix_insert_navigation_menu($menu, 'Administer/', $directDebitMenuItem);

  $subMenuItems = [
    [
      'name' => ts('Direct Debit Codes'),
      'url' => 'civicrm/admin/options/direct_debit_codes',
      'permission' => 'administer CiviCRM, administer ManualDirectDebit',
      'operator' => 'OR',
      'separator' => NULL,
    ],
    [
      'name' => ts('Direct Debit Configuration'),
      'url' => 'civicrm/admin/direct_debit_configuration',
      'permission' => 'administer CiviCRM, administer ManualDirectDebit',
      'operator' => 'OR',
      'separator' => NULL,
    ],
    [
      'name' => ts('Direct Debit Originator Number'),
      'url' => 'civicrm/admin/options/direct_debit_originator_number?reset=1',
      'permission' => 'administer CiviCRM, administer ManualDirectDebit',
      'operator' => 'OR',
      'separator' => NULL,
    ],
  ];

  foreach ($subMenuItems as $menuItem) {
    _manualdirectdebit_civix_insert_navigation_menu($menu, 'Administer/' . $directDebitMenuItem['name'], $menuItem);
  }
}

/**
 * Implements hook_civicrm_postProcess().
 */
function manualdirectdebit_civicrm_postProcess($formName, &$form) {
  $action = $form->getAction();
  $mandateID = $form->getSubmitValue('mandate_id');
  $paymentInstrumentId = CRM_Utils_Array::value('payment_instrument_id', $form->getVar('_submitValues'));
  $isDirectDebit = CRM_ManualDirectDebit_Common_DirectDebitDataProvider::isPaymentMethodDirectDebit($paymentInstrumentId);

  switch (TRUE) {
    case $formName == 'CRM_Contribute_Form_UpdateSubscription' && $action == CRM_Core_Action::UPDATE:
      $manualDirectDebit = new CRM_ManualDirectDebit_Hook_PostProcess_RecurContribution_DirectDebitMandate($form);
      $manualDirectDebit->saveMandateData();
      break;

    case $formName == 'CRM_Member_Form_Membership' && $action == CRM_Core_Action::ADD && $isDirectDebit:
      $manualDirectDebit = new CRM_ManualDirectDebit_Hook_PostProcess_Membership_DirectDebitMandate($form);
      $manualDirectDebit->saveMandateData();
      break;

    case $formName == 'CRM_Member_Form_MembershipRenewal' && $action == CRM_Core_Action::RENEW && $isDirectDebit:
      $manualDirectDebit = new CRM_ManualDirectDebit_Hook_PostProcess_Membership_DirectDebitMandate($form);
      $manualDirectDebit->saveMandateData();
      break;

    case $formName == 'CRM_Contribute_Form_Contribution' && $action == CRM_Core_Action::ADD:
    case $action == CRM_Core_Action::ADD && !empty($mandateID) && $isDirectDebit:
    case $action == CRM_Core_Action::UPDATE && !empty($mandateID) && $isDirectDebit:
      $manualDirectDebit = new CRM_ManualDirectDebit_Hook_PostProcess_Contribution_DirectDebitMandate($form);
      $manualDirectDebit->setCurrentContactId($form->getVar('_contactID'));
      $manualDirectDebit->saveMandateData();
      break;

    case $formName == 'CRM_Contact_Form_CustomData':
      $isDirectDebitGroup = CRM_ManualDirectDebit_Common_DirectDebitDataProvider::isDirectDebitCustomGroup($form->getVar('_groupID'));
      if ($isDirectDebitGroup) {
        $manualDirectDebit = new CRM_ManualDirectDebit_Hook_PostProcess_Contribution_DirectDebitMandate($form);
        $manualDirectDebit->run();
      }
      break;
  }
}

/**
 * Implements hook_civicrm_pageRun().
 */
function manualdirectdebit_civicrm_pageRun(&$page) {
  switch (get_class($page)) {
    case 'CRM_Contribute_Page_ContributionRecur':
      $injectCustomGroup = new CRM_ManualDirectDebit_Hook_PageRun_ContributionRecur_DirectDebitFieldsInjector($page);
      $injectCustomGroup->inject();
      break;

    case 'CRM_Contact_Page_View_CustomData':
      $path = CRM_Utils_System::currentPath();
      $multiRecordDisplay = CRM_Utils_Request::retrieveValue('multiRecordDisplay', 'String', '');
      $mode = CRM_Utils_Request::retrieveValue('mode', 'String', '');
      $mandateStorageManager = new CRM_ManualDirectDebit_Common_MandateStorageManager();

      $pageProcessor = new CRM_ManualDirectDebit_Hook_PageRun_ViewCustomData($path, $multiRecordDisplay, $mode, $mandateStorageManager, $page);
      $pageProcessor->process();
      break;

    case 'CRM_Contribute_Page_Tab':
      $contributionId = $page->getVar('_id');
      $pageProcessor = new CRM_ManualDirectDebit_Hook_PageRun_TabPage();
      $pageProcessor->setContributionId($contributionId);
      $pageProcessor->hideDirectDebitFields();

      CRM_Core_Resources::singleton()
        ->addScriptFile('uk.co.compucorp.manualdirectdebit', 'js/openContribution.js')
        ->addScriptFile('uk.co.compucorp.manualdirectdebit', 'js/paymentMethodMandateSelection.js');
      break;

    case 'CRM_Member_Page_Tab':
    case 'CRM_Event_Page_Tab':
      CRM_Core_Resources::singleton()
        ->addScriptFile('uk.co.compucorp.manualdirectdebit', 'js/paymentMethodMandateSelection.js');
      break;

    case CRM_ManualDirectDebit_Page_BatchList::class:
      CRM_Core_Resources::singleton()->addStyleFile(E::LONG_NAME, 'css/batchSearch.css');
      break;
  }
}

function _manualdirectdebit_getContactType($contactId) {
  return civicrm_api3('Contact', 'getvalue', [
    'return' => 'contact_type',
    'id' => $contactId,
  ]);
}

/**
 * Implements hook_civicrm_custom().
 */
function manualdirectdebit_civicrm_custom($op, $groupID, $entityID, &$params) {
  if (CRM_ManualDirectDebit_Common_DirectDebitDataProvider::isDirectDebitCustomGroup($groupID)) {
    if (in_array($op, ['create', 'edit', 'update'])) {
      $mandateDataGenerator = new CRM_ManualDirectDebit_Hook_Custom_DataGenerator($entityID, $params);
      $mandateDataGenerator->generateMandateData();
    }

    if ($op == 'edit' || $op == 'update') {
      $mandateStorageManager = new CRM_ManualDirectDebit_Common_MandateStorageManager();
      $cancellationChecker = new CRM_ManualDirectDebit_Hook_Custom_CancellationBatchChecker($entityID, $params, $mandateStorageManager);
      $cancellationChecker->process();
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
 * Implements hook_civicrm_post().
 */
function manualdirectdebit_civicrm_post($op, $objectName, $objectId, &$objectRef) {
  if ($op == 'create' && $objectName == 'Contribution') {
    $postContributionHook = new CRM_ManualDirectDebit_Hook_Post_Contribution($objectId);
    $postContributionHook->process();
  }
}

/**
 * Implements hook_civicrm_buildForm().
 */
function manualdirectdebit_civicrm_buildForm($formName, &$form) {
  if ($formName == 'CRM_Activity_Form_ActivityLinks') {
    $openContributionId = CRM_Utils_Request::retrieveValue('openContribution', 'Integer', FALSE);
    if ($openContributionId) {
      $form->add('hidden', 'optionContributionId', $openContributionId);
    }
  }

  if ($formName == 'CRM_Contact_Form_CustomData') {
    if (CRM_ManualDirectDebit_Common_DirectDebitDataProvider::isDirectDebitCustomGroup($form->getVar('_groupID'))) {
      $customData = new CRM_ManualDirectDebit_Hook_BuildForm_CustomData($form);
      $customData->run();
    }
  }

  if ($formName == 'CRM_Custom_Form_CustomDataByType') {
    $customDataByType = new CRM_ManualDirectDebit_Hook_BuildForm_CustomDataByType($form);
    $customDataByType->run();
  }

  if ($formName === 'CRM_Member_Form_Membership' || $formName === 'CRM_Member_Form_MembershipRenewal') {
    $formBuilder = new CRM_ManualDirectDebit_Hook_BuildForm_Membership($form);
    $formBuilder->buildForm();
  }

  if ($formName === 'CRM_Financial_Form_Payment') {
    $formBuilder = new CRM_ManualDirectDebit_Hook_BuildForm_Payment($form);
    $formBuilder->buildForm();
  }

  if ($formName === 'CRM_Contribute_Form_UpdateSubscription') {
    $formBuilder = new CRM_ManualDirectDebit_Hook_BuildForm_UpdateSubscription($form);
    $formBuilder->buildForm();
  }
}

/**
 * Implements hook_civicrm_validateForm().
 */
function manualdirectdebit_civicrm_validateForm($formName, &$fields, &$files, &$form, &$errors) {
  if ($formName == 'CRM_Member_Form_Membership' || $formName === 'CRM_Member_Form_MembershipRenewal') {
    $directDebitValidator = new CRM_ManualDirectDebit_Hook_ValidateForm_MandateValidator($form, $fields, $errors);
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
 * Implements hook_membershipextras_postOfflineAutoRenewal().
 */
function manualdirectdebit_membershipextras_postOfflineAutoRenewal($membershipId, $recurContributionId, $previousRecurContributionId) {
  $activity = new CRM_ManualDirectDebit_Hook_PostOfflineAutoRenewal_Activity($recurContributionId);
  $activity->process();

  $mandate = new CRM_ManualDirectDebit_Hook_PostOfflineAutoRenewal_Mandate($recurContributionId, $previousRecurContributionId);
  $mandate->process();
}

/**
 * Implements hook_membershipextras_calculateContributionReceiveDate().
 */
function manualdirectdebit_membershipextras_calculateContributionReceiveDate($contributionNumber, &$receiveDate, $contributionCreationParams) {
  $settingsManager = new CRM_ManualDirectDebit_Common_SettingsManager();
  switch ($contributionNumber) {
    case 1:
      $receiveDateCalculator = new CRM_ManualDirectDebit_Hook_CalculateContributionReceiveDate_FirstContribution(
        $receiveDate,
        $contributionCreationParams,
        $settingsManager
      );
      $receiveDateCalculator->process();
      break;

    case 2:
      $receiveDateCalculator = new CRM_ManualDirectDebit_Hook_CalculateContributionReceiveDate_SecondContribution(
        $receiveDate,
        $contributionCreationParams,
        $settingsManager
      );
      $receiveDateCalculator->process();
      break;

    default:
      $receiveDateCalculator = new CRM_ManualDirectDebit_Hook_CalculateContributionReceiveDate_OtherContribution(
        $receiveDate,
        $contributionCreationParams,
        $settingsManager
      );
      $receiveDateCalculator->process();
  }
}

function manualdirectdebit_civicrm_searchTasks($objectName, &$tasks) {
  if ($objectName == 'contribution') {
    $tasks[] = [
      'title' => 'Send Direct Debit Notifications',
      'class' => 'CRM_ManualDirectDebit_Form_Email_Contribution',
      'result' => FALSE,
    ];
  }

  if ($objectName == 'membership') {
    $tasks[] = [
      'title' => 'Send Direct Debit Notifications',
      'class' => 'CRM_ManualDirectDebit_Form_Email_Membership',
      'result' => FALSE,
    ];
    $tasks[] = [
      'title' => 'Print Direct Debit Letters',
      'class' => 'CRM_ManualDirectDebit_Form_PrintMergeDocument',
      'result' => FALSE,
    ];
  }
}

function manualdirectdebit_civicrm_container($container) {
  $priorityHigherThanCoreServices = -1;
  $container->findDefinition('dispatcher')
    ->addMethodCall('addListener',
      array('civi.token.render', '_manualdirectdebit_civicrm_tokenRenderEventListener', $priorityHigherThanCoreServices));
}

function _manualdirectdebit_civicrm_tokenRenderEventListener($event) {
  $tokenRenderListener = new CRM_ManualDirectDebit_Event_Listener_TokenRender($event);
  $tokenRenderListener->replaceDirectDebitTokens();
}

/**
 * Implements hook_civicrm_queryObjects().
 */
function manualdirectdebit_civicrm_queryObjects(&$queryObjects, $type) {
  if ($type === 'Contact') {
    $queryObjects[] = new CRM_ManualDirectDebit_Hook_QueryObjects_Contribution();
  }
}
