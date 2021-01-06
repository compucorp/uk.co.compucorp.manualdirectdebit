<?php

/**
 * Gets contact id
 */
class CRM_ManualDirectDebit_Common_User {

  /**
   * Gets logged contact's id
   *
   * @return int
   */
  public static function getLoggedContactId() {
    return CRM_Core_Session::singleton()->getLoggedInContactID();
  }

  /**
   * Gets admin's contact id
   *
   * @return int
   */
  public static function getAdminContactId() {
    if (!self::isDrupal()) {
      return FALSE;
    }

    $drupalAdminId = self::getDrupalAdminUserId();

    if (!$drupalAdminId) {
      return FALSE;
    }

    $contactId = self::getContactIdByDrupalAdminId($drupalAdminId);

    if (!$contactId) {
      return FALSE;
    }

    return $contactId;
  }

  /**
   * Gets drupal admin id
   *
   * @return null
   */
  private static function getDrupalAdminUserId() {
    $dbSelect = db_select('users', 'users');
    $dbSelect->join('users_roles', 'roles', 'roles.uid = users.uid');
    $drupalUserList = $dbSelect->fields('users', ['uid'])
      ->where('roles.rid = ' . variable_get('user_admin_role'))
      ->range(0, 1)
      ->execute()
      ->fetchAll();

    foreach ($drupalUserList as $drupalUser) {
      return $drupalUser->uid;
    }

    return FALSE;
  }

  /**
   * Gets contact id by drupal contact id
   *
   * @param $drupalAdminId
   *
   * @return int|null
   */
  private static function getContactIdByDrupalAdminId($drupalAdminId) {
    try {
      $contactId = civicrm_api3('UFMatch', 'getvalue', [
        'return' => "contact_id",
        'uf_id' => $drupalAdminId,
        'options' => ['limit' => 1],
      ]);

      return (int) $contactId;
    }
    catch (CiviCRM_API3_Exception $e) {
      return FALSE;
    }
  }

  /**
   * Checks if current cms is drupal
   *
   * @return bool
   */
  private static function isDrupal() {
    return function_exists('drupal_get_destination');
  }

}
