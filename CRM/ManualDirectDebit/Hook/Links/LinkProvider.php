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
    $this->links[] = [
      'name' => ts('Use a new mandate'),
      'url' => 'civicrm/contact/view/cd/edit',
      'title' => 'Use a new mandate',
      'qs' => 'reset=1&type=Individual&groupID=%%groupID%%&entityID=%%cid%%&cgcount=%%cgcount%%&multiRecordDisplay=single&mode=add&updatedRecId=%%updatedRecId%%',
      'class' => 'no-popup',
    ];

    $values['groupID'] = CRM_ManualDirectDebit_Common_DirectDebitDataProvider::getGroupIDByName("direct_debit_mandate");
    $values['cid'] = CRM_Utils_Request::retrieve('cid', 'Integer');
    $values['cgcount'] = $this->getCgCount();
    $values['updatedRecId'] = $recurringContributionId;
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

    $instructionsBatchTypeId = CRM_Core_OptionGroup::getRowValues('batch_type', 'instructions_batch', 'name', 'String', FALSE);
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
