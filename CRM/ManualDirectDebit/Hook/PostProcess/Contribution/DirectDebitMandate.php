<?php

/**
 *  Create an empty mandate and connect it to a new contribution
 */
class CRM_ManualDirectDebit_Hook_PostProcess_Contribution_DirectDebitMandate {

  /**
   * Id of newly generated mandate
   *
   * @var int
   */
  private $mandateID;

  /**
   * Contribution form object from Hook
   *
   * @var object
   */
  private $form;

  public function __construct(&$form) {
    $this->form = $form;
  }

  public function create() {
    if ($this->form->_params['is_recur']) {
      if ($this->form->_paymentProcessor['name'] == "Direct Debit") {
        $this->mandateID = $this->createMandate();
        $this->assignContributionMandate();
        $this->assignRecurringContributionMandate();
      }
    }
    else {
      $this->mandateID = $this->createMandate();
      $this->assignContributionMandate();
    }
  }

  /**
   * Assign a newly generated mandate into appropriate contribution
   *
   */
  public function assignContributionMandate() {
    $currentContributionID = (int) $this->form->getVar('_id');
    $mandateIdCustomFieldId = civicrm_api3('CustomField', 'getvalue', [
      'return' => "id",
      'name' => "mandate_id",
    ]);

    civicrm_api3('Contribution', 'create', [
      "custom_$mandateIdCustomFieldId" => $this->mandateID,
      'id' => $currentContributionID,
    ]);
  }

  /**
   * Assign a newly generated mandate into appropriate recurring contribution
   *
   */
  public function assignRecurringContributionMandate() {
    $rows = [
      'recurr_id' => $this->form->_params['contributionRecurID'],
      'mandate_id' => $this->mandateID,
    ];
    CRM_ManualDirectDebit_BAO_RecurrMandateRef::create($rows);
  }

  /**
   * Creates a new direct debit mandate and returns id of the last inserted one
   *
   * @return int
   */
  private function createMandate() {
    $tableName = 'civicrm_value_dd_mandate';
    $sqlInsertedInDirectDebitMandate = "INSERT INTO `$tableName` (`entity_id`) VALUES (%1)";
    CRM_Core_DAO::executeQuery($sqlInsertedInDirectDebitMandate, [
      1 => [
        $this->form->getVar('_contactID'),
        'String',
      ],
    ]);

    $sqlSelectedDebitMandateID = "SELECT MAX(`id`) AS id FROM `$tableName` WHERE `entity_id` = %1";
    $queryResult = CRM_Core_DAO::executeQuery($sqlSelectedDebitMandateID, [
      1 => [
        $this->form->getVar('_contactID'),
        'String',
      ],
    ]);
    $queryResult->fetch();
    $generatedMandateId = $queryResult->id;

    return $generatedMandateId;
  }

}
