<?php

/**
 * Class CRM_ManualDirectDebit_Hook_QueryObjects_Contribution.
 */
class CRM_ManualDirectDebit_Hook_QueryObjects_Contribution extends CRM_Contact_BAO_Query_Interface {

  /**
   * Obtains list of fields for the query.
   */
  public function &getFields() {
    $fields = [];

    return $fields;
  }

  /**
   * @inheritDoc
   */
  public function from($fieldName, $mode, $side) {
    if (!$this->isContributionSearchForm()) {
      return '';
    }

    $from = '';
    if ($fieldName == 'contribution_batch') {
      $from = "
        $side JOIN (
          SELECT civicrm_entity_batch.entity_id, civicrm_entity_batch.batch_id
          FROM civicrm_entity_batch, civicrm_batch
          WHERE civicrm_entity_batch.entity_table = 'civicrm_contribution'
          AND civicrm_entity_batch.batch_id = civicrm_batch.id
        ) payment_batches ON payment_batches.entity_id = civicrm_contribution.id
      ";
    }

    return $from;
  }

  /**
   * Alters where statement.
   */
  public function where(&$query) {
    if (!$this->isContributionSearchForm()) {
      return;
    }

    $batchLookupParams = CRM_Utils_Array::value('contribution_batch_id', $query->_paramLookup, []);
    if (!isset($batchLookupParams[0][1]) || !isset($batchLookupParams[0][2]) || intval($batchLookupParams[0][2]) === 0) {
      return;
    }

    foreach ($query->_where as $grouping => $clauses) {
      foreach ($clauses as $clauseKey => $analyzedClause) {
        if (stripos($analyzedClause, 'civicrm_entity_batch.batch_id') === FALSE) {
          continue;
        }

        $paymentBatchClause = CRM_Contact_BAO_Query::buildClause('payment_batches.batch_id', $batchLookupParams[0][1], $batchLookupParams[0][2]);
        $query->_where[$grouping][$clauseKey] = "
          (
            {$query->_where[$grouping][$clauseKey]}
            OR {$paymentBatchClause}
          )
        ";
      }
    }
  }

  /**
   * Checks if the current path is the one for contribution searches.
   *
   * @return bool
   */
  private function isContributionSearchForm() {
    if (CRM_Utils_System::currentPath() == 'civicrm/contribute/search') {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Implements getPanesMapper, required by getPanesMapper hook.
   *
   * @param array $panes
   *   Panes.
   */
  public function getPanesMapper(array &$panes) {
    return;
  }

}
