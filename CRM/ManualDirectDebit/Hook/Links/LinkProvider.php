<?php

class CRM_ManualDirectDebit_Hook_Links_LinkProvider {

  /**
   * List of links
   *
   * @var array
   */
  private $links;

  public function __construct(&$links) {
    $this->links = &$links;
  }

  /**
   * Alters recurring contribution links
   *
   * @param $values
   * @param $recurringContributionId
   */
  public function alterRecurContributionLinks(&$values, $recurringContributionId) {
    $contactId = CRM_Utils_Request::retrieve('cid', 'Integer');
    $contactType = $this->getContactType($contactId);

    $this->links[] = [
      'name' => ts('Use a new mandate'),
      'url' => 'civicrm/contact/view/cd/edit',
      'title' => 'Use a new mandate',
      'qs' => 'reset=1&type=' . $contactType . '&groupID=%%groupID%%&entityID=%%cid%%&cgcount=%%cgcount%%&multiRecordDisplay=single&mode=add&updatedRecId=%%updatedRecId%%',
      'class' => 'no-popup',
    ];

    $values['groupID'] = CRM_ManualDirectDebit_Common_DirectDebitDataProvider::getGroupIDByName("direct_debit_mandate");
    $values['cid'] = $contactId;
    $values['cgcount'] = $this->getCgCount();
    $values['updatedRecId'] = $recurringContributionId;
  }

  private function getContactType($contactId) {
    return civicrm_api3('Contact', 'getvalue', [
      'return' => 'contact_type',
      'id' => $contactId,
    ]);
  }

  /**
   * Gets mandate cgcount
   *
   * @return int
   */
  private function getCgCount() {
    $maxMandateId = CRM_ManualDirectDebit_Common_DirectDebitDataProvider::getMaxMandateId();
    return $maxMandateId + 1;
  }

  /**
   * Alters batch links
   *
   * @param $objectId
   */
  public function alterBatchLinks($objectId) {
    $batch = CRM_Batch_BAO_Batch::findById($objectId);

    $instructionsBatchTypeId = CRM_Core_OptionGroup::getRowValues('batch_type', CRM_ManualDirectDebit_Batch_BatchHandler::BATCH_TYPE_INSTRUCTIONS, 'name', 'String', FALSE);
    if ($batch->type_id == $instructionsBatchTypeId['value']) {
      foreach ($this->links as &$link) {
        switch ($link['name']) {
          case 'Transactions':
            $link['url'] = 'civicrm/direct_debit/batch-transaction';
            break;
        }
      }
    }
  }

}
