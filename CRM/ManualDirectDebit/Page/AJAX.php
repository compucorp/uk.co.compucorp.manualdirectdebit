<?php
use CRM_ManualDirectDebit_Batch_BatchHandler as BatchHandler;

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
    $entityTable = CRM_Utils_Request::retrieveValue('entityTable', 'String', NULL);
    $notPresent = CRM_Utils_Request::retrieveValue('notPresent', 'String', NULL);

    $batch = (new BatchHandler($entityID));
    if ($batch->getBatchType() == BatchHandler::BATCH_TYPE_PAYMENTS) {
      $sortMapper[] = 'receive_date';
    }

    $sort = CRM_Utils_Array::value(CRM_Utils_Request::retrieveValue('iSortCol_0', 'Integer', NULL), $sortMapper);
    $sortOrder = CRM_Utils_Request::retrieveValue('sSortDir_0', 'String', 'asc');

    $params = $_POST;

    if ($sort && $sortOrder) {
      $params['sortBy'] = $sort . ' ' . $sortOrder;
    }

    $params['page'] = ($offset / $rowCount) + 1;
    $params['rp'] = $rowCount;

    $params['context'] = $context;
    $params['offset'] = $offset;
    $params['rowCount'] = $params['rp'];
    $params['sort'] = CRM_Utils_Array::value('sortBy', $params);
    $params['total'] = 0;
    $params['entityTable'] = $entityTable ?: 'civicrm_value_dd_mandate';

    $batchTransaction = new CRM_ManualDirectDebit_Batch_Transaction($entityID, $params, [], [], $notPresent);
    $batchTransaction->addReturnValues(['id' => $params['entityTable'] . '.id as id']);

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

    if ($batch->getBatchType() == BatchHandler::BATCH_TYPE_PAYMENTS) {
      $selectorElements[] = 'receive_date';
    }
    $selectorElements[] = 'action';

    if ($return) {
      return CRM_Utils_JSON::encodeDataTableSelector($mandateItems, $sEcho, $iTotal, $iFilteredTotal, $selectorElements);
    }
    CRM_Utils_System::setHttpHeader('Content-Type', 'application/json');
    echo self::encodeDataTableSelector($mandateItems, $sEcho, $iTotal, $iFilteredTotal, $selectorElements);
    CRM_Utils_System::civiExit();
  }

  /**
   * Generates string for DataTable.js library
   *
   * @param array $params
   *   Associated array of row elements.
   * @param int $sEcho
   *   Datatable needs this to make it more secure.
   * @param int $iTotal
   *   Total records.
   * @param int $iFilteredTotal
   *   Total records on a page.
   * @param array $selectorElements
   *   Selector elements.
   *
   * @return string
   */
  private static function encodeDataTableSelector($params, $sEcho, $iTotal, $iFilteredTotal, $selectorElements) {
    $sOutput = '{';
    $sOutput .= '"sEcho": ' . intval($sEcho) . ', ';
    $sOutput .= '"iTotalRecords": ' . $iTotal . ', ';
    $sOutput .= '"iTotalDisplayRecords": ' . $iFilteredTotal . ', ';
    $sOutput .= '"aaData": [ ';
    foreach ((array) $params as $key => $value) {
      $addcomma = FALSE;
      $sOutput .= "{";
      foreach ($selectorElements as $element) {
        if ($addcomma) {
          $sOutput .= ",";
        }
        // CRM-7130 --lets addslashes to only double quotes,
        // since we are using it to quote the field value.
        // str_replace helps to provide a break for new-line
        $sOutput .= '"' . $element . '" :"' . addcslashes(str_replace([
          "\r\n",
          "\n",
          "\r",
        ], '<br />', $value[$element]), '"\\') . '"';

        // remove extra spaces and tab character that breaks dataTable CRM-12551
        $sOutput = preg_replace("/\s+/", " ", $sOutput);
        $addcomma = TRUE;
      }
      $sOutput .= "},";
    }
    $sOutput = substr_replace($sOutput, "", -1);
    $sOutput .= '] }';

    return $sOutput;
  }

  /**
   * Exports batch items to csv.
   */
  public static function export() {
    $id = CRM_Utils_Request::retrieveValue('id', 'String');
    $batch = new BatchHandler($id);
    $batch->createExportFile();
  }

}
