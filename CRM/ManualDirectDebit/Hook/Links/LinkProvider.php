<?php

class CRM_ManualDirectDebit_Hook_Links_LinkProvider {

  /**
   * Array
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
   */
  public function alterRecurContributionLinks(&$values) {
    $this->links[] = [
      'name' => ts('Use a new mandate'),
      'url' => 'civicrm/contact/view/cd/edit',
      'title' => 'Use a new mandate',
      'qs' => 'reset=1&type=Individual&groupID=%%groupID%%&entityID=%%cid%%&cgcount=%%cgcount%%&multiRecordDisplay=single&mode=add&updatedRecId=%%recurContributionId%%',
      'class' => 'no-popup',
    ];

    $values['groupID'] = CRM_ManualDirectDebit_Common_DirectDebitDataProvider::getGroupIDByName("direct_debit_mandate");
    $values['entityID'] = CRM_Utils_Request::retrieve('cid', 'Integer');
    $values['cgcount'] = $this->getCgCount();
    $values['updatedRecId'] = $this->getRecurrContributionIds();
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
   * Gets id`s of all recurring contribution with 'direct debit' payment instrument
   *
   * @return array
   */
  private function getRecurrContributionIds() {
    $contribution = civicrm_api3('ContributionRecur', 'get', [
      'sequential' => 1,
      'return' => ["id"],
      'payment_instrument_id' => "direct_debit",
    ]);

    $ids = [];
    foreach ($contribution['values'] as $recurr) {
      $ids[] = $recurr['id'];
    }

    return $ids;
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
