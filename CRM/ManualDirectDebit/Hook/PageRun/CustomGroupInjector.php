<?php

/**
 *  Provides 'Direct Debit Information' integration into 'Recurring Contribution Detail' view
 */
class CRM_ManualDirectDebit_Hook_PageRun_CustomGroupInjector {

  /**
   * Id of Contact
   *
   * @var int
   */
  private $contactId;

  /**
   * Id of Direct Debit Mandate Custom Group
   *
   * @var int
   */
  private $directDebitMandateCustomGroupId;

  /**
   * Mandate id for appropriate contribution
   *
   * @var int
   */
  private $appropriateMandateId;

  /**
   * Contribution id for appropriate recurring contribution
   *
   * @var int
   */
  private $appropriateContributionId;

  public function __construct() {
    $this->contactId = CRM_Utils_Request::retrieve('cid', 'Integer', $page, FALSE);
    $this->directDebitMandateCustomGroupId = $this->getGroupIDbyName("direct_debit_mandate");

    $recurrentContributionId = CRM_Utils_Request::retrieve('id', 'Integer', $page, FALSE);

    $this->appropriateMandateId = $this->getAppropriateMandateId($recurrentContributionId);
    $this->appropriateContributionId = $this->getAppropriateContributionId($recurrentContributionId);
  }

  /**
   * Injects 'Direct Debit Information' custom group inside 'Recurring Contribution Detail' view
   *
   */
  public function injectDirectDebitInformation() {
    CRM_Core_Resources::singleton()
      ->addScriptFile('uk.co.compucorp.manualdirectdebit', 'js/directDebitInformation.js')
      ->addSetting([
        'urlData' => [
          'gid' => $this->directDebitMandateCustomGroupId,
          'cid' => $this->contactId,
          'recId' => $this->appropriateContributionId,
          'mandateId' => $this->appropriateMandateId,
        ],
      ]);
  }

  /**
   * Gets id of custom group by name
   *
   * @param $customGroupName
   *
   * @return int
   */
  private function getGroupIDByName($customGroupName) {
    return civicrm_api3('CustomGroup', 'getvalue', [
      'return' => "id",
      'name' => $customGroupName,
    ]);
  }

  /**
   * Gets id of custom field by name
   *
   * @param $customFieldName
   *
   * @return int
   */
  private function getCustomFieldIdByName($customFieldName) {
    return civicrm_api3('CustomField', 'getvalue', [
      'return' => "id",
      'name' => $customFieldName,
    ]);
  }

  /**
   * Gets id of mandate for recurrent contribution
   *
   * @param $recurrentContributionId
   *
   * @return int
   */
  private function getAppropriateMandateId($recurrentContributionId) {
    $mandateIdCustomFieldId = $this->getCustomFieldIdByName("mandate_id");
    try {
      $appropriateMandateId = civicrm_api3('Contribution', 'getvalue', [
        'return' => "custom_$mandateIdCustomFieldId",
        'contribution_recur_id' => $recurrentContributionId,
      ]);
      return $appropriateMandateId;

    }
    catch (CiviCRM_API3_Exception $e) {
      CRM_Core_Session::setStatus(t("Contribution doesn't exist"), $title = 'Error', $type = 'alert');
      return FALSE;
    }
  }

  /**
   * Gets id of appropriate individual contribution for recurrent contribution
   *
   * @param $recurrentContributionId
   *
   * @return int
   */
  private function getAppropriateContributionId($recurrentContributionId) {
    try {
      $appropriateContributionId = civicrm_api3('Contribution', 'getvalue', [
        'return' => "id",
        'contribution_recur_id' => $recurrentContributionId,
      ]);
      return $appropriateContributionId;
    }
    catch (CiviCRM_API3_Exception $e) {
      CRM_Core_Session::setStatus(t("Contribution doesn't exist"), $title = 'Error', $type = 'alert');
      return FALSE;
    }
  }

}
