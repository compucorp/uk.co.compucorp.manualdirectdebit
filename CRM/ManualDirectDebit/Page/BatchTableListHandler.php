<?php


class CRM_ManualDirectDebit_Page_BatchTableListHandler {

  /**
   * Generates the batches table
   * rows to be used in table template.
   *
   * @param array $filterParams
   *   Batches filtering parameters
   *
   * @return array
   */
  public static function generateRows($filterParams) {
    $batches = self::getPageBatches($filterParams);

    $batch = new CRM_Batch_BAO_Batch();
    $defaultBatchlinks = $batch->links();

    $batchStatuses = CRM_Core_PseudoConstant::get('CRM_Batch_DAO_Batch', 'status_id');

    $rows = [];
    foreach ($batches as $batchRowValues) {
      $batchRowValues['batch_status'] = $batchStatuses[$batchRowValues['status_id']];
      $batchRowValues['created_by'] = $batchRowValues['created_id.sort_name'];
      self::generateRowLinks($defaultBatchlinks, $batchRowValues);
      $rows[$batchRowValues['id']] = $batchRowValues;
    }

    return $rows;
  }

  /**
   * Gets the list of batches to be viewed
   * based on the filter parameters.
   *
   * @param array $filterParams
   *
   * @return array
   */
  private static function getPageBatches($filterParams) {
    $apiParams['status_id'] = ['NOT IN' => ['Data Entry']];

    if (!empty($filterParams['id'])) {
      $apiParams['id'] = $filterParams['id'];
    }

    if (!empty($filterParams['type_id'])) {
      $apiParams['type_id'] = $filterParams['type_id'];
    }

    if (!empty($filterParams['created_date'])) {
      $apiParams['created_date'] = $filterParams['created_date'];
    }

    if (!empty($filterParams['rowCount']) && is_numeric($filterParams['rowCount'])
      && is_numeric($filterParams['offset']) && $filterParams['rowCount'] > 0
    ) {
      $apiParams['options'] = ['offset' => $filterParams['offset'], 'limit' => $filterParams['rowCount']];
    }

    $apiParams['options']['sort'] = 'id DESC';
    if (!empty($filterParams['sort'])) {
      $apiParams['options']['sort'] = CRM_Utils_Type::escape($filterParams['sort'], 'String');
    }

    $apiParams['return'] = [
      'id',
      'name',
      'created_date',
      'status_id',
      'type_id',
      'mode_id',
      'total',
      'item_count',
      'created_id.sort_name',
    ];

    $result = civicrm_api3('Batch', 'get', $apiParams);
    if (!empty($result['values'])) {
      return $result['values'];
    }

    return [];
  }

  /**
   * Generates the links for a single row.
   *
   * @param array $defaultBatchlinks
   *   The batch entity default links
   *
   * @param array $rowValues
   *  The values of the row to generate links for.
   *
   * @return string
   */
  private static function generateRowLinks($defaultBatchlinks, $rowValues) {
    $batchStatuses = CRM_Core_PseudoConstant::get('CRM_Batch_DAO_Batch', 'status_id');

    $rowLinks = $defaultBatchlinks;
    $linksMask = array_sum(array_keys($rowLinks));

    if ($batchStatuses[$rowValues['status_id']] == 'Closed') {
      $rowLinks = [];
    }
    $linksValues = ['id' => $rowValues['id'], 'status' => $rowValues['status_id']];

    if ($batchStatuses[$rowValues['status_id']] == 'Exported') {
      $exportActivity = civicrm_api3('Activity', 'get', [
        'return' => ['id'],
        'sequential' => 1,
        'source_record_id' => $rowValues['id'],
        'activity_type_id' => 'Export Accounting Batch',
        'options' => ['limit' => 1],
      ]);

      if (!empty($exportActivity['id'])) {
        $fid = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_EntityFile', $exportActivity['id'], 'file_id', 'entity_id');
        $linksValues = array_merge(['eid' => $exportActivity['id'], 'fid' => $fid], $linksValues);
      }
    }

    return CRM_Core_Action::formLink(
      $rowLinks,
      $linksMask,
      $linksValues,
      ts('more'),
      FALSE,
      'batch.selector.row',
      'Batch',
      $rowValues['id']
    );
  }

}
