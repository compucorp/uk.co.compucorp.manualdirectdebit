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
      'url' => 'civicrm/admin/options/direct_debit_originator_number',
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

  if ($formName == 'CRM_Contribute_Form_Contribution' && $action == CRM_Core_Action::ADD) {
    $contributionId = (int) $form->getVar('_id');
    $assignedContactId = (int) $form->getVar('_contactID');
    $mandateId = getIdOfNewMandate($assignedContactId);
    if ($form->_params['is_recur'] == 1) {
      $recurrContributionId = $form->_params['contributionRecurID'];
      saveDependencyBetweenContributionAndMandate($recurrContributionId, $mandateId);
    }

    saveMandateInContribution($contributionId, $mandateId);
  }
}

/**
 * Gets id of last inserted direct debit mandate
 *
 * @param $assignedContactId
 *
 * @return int
 */
function getIdOfNewMandate($assignedContactId) {
  $tableName = 'civicrm_value_dd_mandate';

  createNewMandate($assignedContactId, $tableName);

  $query = CRM_Utils_SQL_Select::from($tableName);
  $query->select('LAST_INSERT_ID()');
  $result = CRM_Core_DAO::executeQuery($query->toSQL());
  $fetched = $result->fetchAll();
  $lastInsertedMandateId = $fetched[0]['LAST_INSERT_ID()'];

  return (int) $lastInsertedMandateId;
}

/**
 * Creates new direct debit mandate
 *
 * @param $assignedContactId
 * @param $tableName
 */
function createNewMandate($assignedContactId, $tableName) {
  $rows[] = [
    'entity_id' => $assignedContactId,
  ];

  $insert = CRM_Utils_SQL_Insert::into($tableName);
  $insert->rows($rows);
  CRM_Core_DAO::executeQuery($insert->toSQL());
}


/**
 * Inserts into data base dependency between contribution and mandate
 *
 * @param $recurrId
 * @param $mandateId
 */
function saveDependencyBetweenContributionAndMandate($recurrId, $mandateId) {
  $rows = [
    'recurr_id' => $recurrId,
    'mandate_id' => $mandateId
  ];
  CRM_ManualDirectDebit_BAO_RecurrMandateRef::create($rows);
}

/**
 * Inserts into Direct Debit Mandate table mandate id
 *
 * @param $assignedContactId
 * @param $mandateId
 */
function saveMandateInContribution($contributionId, $mandateId) {
  $mandateIdCustomFieldId = getMandateCustomFieldId();
  civicrm_api3('Contribution', 'create', [
    "custom_$mandateIdCustomFieldId" => $mandateId,
    'id' => $contributionId,
  ]);
}

/**
 * Gets id of custom value
 *
 * @return array
 */
function getMandateCustomFieldId() {
  $mandateIdCustomFieldId = civicrm_api3('CustomField', 'getvalue', [
    'return' => "id",
    'name' => "mandate_id",
  ]);

  return $mandateIdCustomFieldId;
}

/**
 * Implements hook_civicrm_pageRun().
 *
 */
function manualdirectdebit_civicrm_pageRun(&$page) {
  if (get_class($page) == 'CRM_Contribute_Page_ContributionRecur') {
    $contactId = CRM_Utils_Request::retrieve('cid', 'Integer', $page, FALSE);
    $recurrentContributionId = CRM_Utils_Request::retrieve('id', 'Integer', $page, FALSE);
    $groupId = civicrm_api3('CustomGroup', 'getvalue', [
      'return' => "id",
      'name' => "direct_debit_mandate",
    ]);

    $mandateIdCustomFieldId = getMandateCustomFieldId();

    try {
      $mandateId = civicrm_api3('Contribution', 'getvalue', [
        'return' => "custom_$mandateIdCustomFieldId",
        'contribution_recur_id' => $recurrentContributionId,
      ]);

      $contributionId = civicrm_api3('Contribution', 'getvalue', [
        'return' => "id",
        'contribution_recur_id' => $recurrentContributionId,
      ]);
    }
    catch (CiviCRM_API3_Exception $e) {
      CRM_Core_Session::setStatus(t("Contribution don't exist"), $title = 'Error', $type = 'alert');
      return FALSE;
    }

    CRM_Core_Resources::singleton()
      ->addScriptFile('uk.co.compucorp.manualdirectdebit', 'js/directDebitInformation.js')
      ->addSetting([
        'urlData' => [
          'gid' => $groupId,
          'cid' => $contactId,
          'recId' => $contributionId,
          'mandateId' => $mandateId,
        ],
      ]);
  }
}
