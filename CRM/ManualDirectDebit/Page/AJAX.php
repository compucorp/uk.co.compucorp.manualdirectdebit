<?php

/**
 * This class contains all the function that are called using AJAX
 */
class CRM_ManualDirectDebit_Page_AJAX {

  /**
   * Prepares Direct Debit mandates' transactions items
   *
   * @return string
   */
  public static function getInstructionTransactionsList() {
    $sortMapper = [
      0 => '',
      1 => 'id',
      2 => 'name',
      3 => 'sort_code',
      4 => 'account_number',
      5 => 'amount',
      6 => 'reference_number',
      7 => 'transaction_type',
    ];

    $sEcho = CRM_Utils_Request::retrieveValue('sEcho', 'Integer');
    $return = CRM_Utils_Request::retrieveValue('return', 'Boolean', FALSE);
    $offset = CRM_Utils_Request::retrieveValue('iDisplayStart', 'Integer', 0);
    $rowCount = CRM_Utils_Request::retrieveValue('iDisplayLength', 'Integer', 25);
    $context = CRM_Utils_Request::retrieveValue('context', 'String', NULL);
    $entityID = CRM_Utils_Request::retrieveValue('entityID', 'String', NULL);
    $notPresent = CRM_Utils_Request::retrieveValue('notPresent', 'String', NULL);

    $sort = CRM_Utils_Array::value(CRM_Utils_Request::retrieveValue('iSortCol_0', 'Integer', NULL), $sortMapper);
    $sortOrder = CRM_Utils_Request::retrieveValue('sSortDir_0', 'String', 'asc');

    $params = $_POST;

    if ($sort && $sortOrder) {
      $params['sortBy'] = $sort . ' ' . $sortOrder;
    }

    $params['page'] = ($offset / $rowCount) + 1;
    $params['rp'] = $rowCount;

    $params['context'] = $context;
    $params['offset'] = ($params['page'] - 1) * $params['rp'];
    $params['rowCount'] = $params['rp'];
    $params['sort'] = CRM_Utils_Array::value('sortBy', $params);
    $params['total'] = 0;

    $batchTransaction = new CRM_ManualDirectDebit_Batch_Transaction($entityID, $params, [], [], $notPresent);
    $batchTransaction->addColumnHeader(['amount' => ts('Amount')]);
    $batchTransaction->addReturnValues(['amount' => '0 as amount']);

    $mandateItems = $batchTransaction->getRows();

    $iFilteredTotal = $iTotal = $batchTransaction->getTotalNumber();

    $selectorElements = [
      'check',
      'contact_id',
      'name',
      'sort_code',
      'account_number',
      'amount',
      'reference_number',
      'transaction_type',
    ];

    if ($return) {
      return CRM_Utils_JSON::encodeDataTableSelector($mandateItems, $sEcho, $iTotal, $iFilteredTotal, $selectorElements);
    }
    CRM_Utils_System::setHttpHeader('Content-Type', 'application/json');
    echo CRM_Utils_JSON::encodeDataTableSelector($mandateItems, $sEcho, $iTotal, $iFilteredTotal, $selectorElements);
    CRM_Utils_System::civiExit();
  }


  /**
   * Callback to perform action on batch records.
   */
  public static function bulkAssignRemove() {
    $mandatesIDs = CRM_Utils_Request::retrieveValue('ID', 'Memo');
    $entityID = CRM_Utils_Request::retrieveValue('entityID', 'String');
    $actions = CRM_Utils_Request::retrieveValue('actions', 'String');

    foreach ($mandatesIDs as $key => $value) {
      if ((substr($value, 0, 7) == "mark_x_" && $actions == 'Assign') || (substr($value, 0, 7) == "mark_y_" && $actions == 'Remove')) {
        $mandates = explode("_", $value);
        $mandatesIDs[$key] = $mandates[2];
      }
    }

    foreach ($mandatesIDs as $key => $value) {

      if ($actions == 'Remove' || $actions == 'Assign') {
        $params = [
          'entity_id' => $value,
          'entity_table' => 'civicrm_value_dd_mandate',
          'batch_id' => $entityID,
        ];
        if ($actions == 'Assign') {
          $updated = CRM_Batch_BAO_EntityBatch::create($params);
        }
        else {
          $updated = CRM_Batch_BAO_EntityBatch::del($params);
        }
      }
    }
    if (!empty($updated)) {
      $status = ['status' => 'record-updated-success'];
    }
    else {
      $status = ['status' => ts('Can not %1 mandate', [1 => $actions])];
    }
    CRM_Utils_JSON::output($status);
  }

  /**
   * Callback to perform action on batch records.
   */
  public static function assignRemove() {
    $op = CRM_Utils_Request::retrieveValue('op', 'String');
    $recordBAO = CRM_Utils_Request::retrieveValue('recordBAO', 'String');

    $records = [];
    foreach (CRM_Utils_Request::retrieveValue('records', 'String', []) as $record) {
      $recordID = CRM_Utils_Type::escape($record, 'Positive', FALSE);
      if ($recordID) {
        $records[] = $recordID;
      }
    }

    $entityID = CRM_Utils_Request::retrieveValue('entityID', 'Positive');
    $methods = [
      'assign' => 'create',
      'remove' => 'del',
    ];

    $response = ['status' => 'record-updated-fail'];
    // first munge and clean the recordBAO and get rid of any non alpha numeric characters
    $recordBAO = CRM_Utils_String::munge($recordBAO);
    $recordClass = explode('_', $recordBAO);
    // make sure recordClass is in the CRM namespace and
    // at least 3 levels deep
    if ($recordClass[0] == 'CRM' && count($recordClass) >= 3) {
      foreach ($records as $recordID) {
        $params = [];
        switch ($op) {
          case 'assign':
          case 'remove':
            $params = [
              'entity_id' => $recordID,
              'entity_table' => 'civicrm_value_dd_mandate',
              'batch_id' => $entityID,
            ];
            break;
        }

        if (method_exists($recordBAO, $methods[$op]) && !empty($params)) {
          $updated = call_user_func_array([
            $recordBAO,
            $methods[$op],
          ], [ & $params]);
          if ($updated) {
            $redirectStatus = $updated->status_id;
            $response = [
              'status' => 'record-updated-success',
              'status_id' => $redirectStatus,
            ];
          }
        }
      }
    }
    CRM_Utils_JSON::output($response);
  }

}
