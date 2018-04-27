<?php

/**
 * This class is used for batch handling
 *
 */
class CRM_ManualDirectDebit_Batch_BatchHandler {

  /**
   * Batch id
   *
   * @var integer
   */
  private $batchID;

  /**
   * Batch status
   *
   * @var string
   */
  private $batchStatus;

  /**
   * Batch status
   *
   * @var string
   */
  private $batchStatusId;

  public function __construct($batchID) {
    $this->batchID = $batchID;
  }

  /**
   * Gets batch status ID
   *
   * @return int
   */
  public function getBatchStatusId() {
    if (!$this->batchStatusId) {
      $this->batchStatusId = CRM_Core_DAO::getFieldValue('CRM_Batch_DAO_Batch', $this->batchID, 'status_id');
    }

    return $this->batchStatusId;
  }

  /**
   * Gets batch status
   *
   * @return string
   */
  public function getBatchStatus() {
    if (!$this->batchStatus) {
      $statusID = $this->getBatchStatusId();
      $batchStatuses = CRM_Core_PseudoConstant::get('CRM_Batch_DAO_Batch', 'status_id', [
        'labelColumn' => 'name',
        'status' => " v.value={$statusID}",
      ]);
      $this->batchStatus = $batchStatuses[$statusID];
    }

    return $this->batchStatus;
  }

  /**
   * Checks whether the batch is open
   *
   * @return bool
   */
  public function validBatchStatus() {
    return in_array($this->getBatchStatus(), ['Open', 'Reopened']);
  }

  /**
   * Creates Export File
   *
   * @param array $params
   */
  public function createExportFile($params) {
    $returnValues = [
      'contact_id' => CRM_ManualDirectDebit_Batch_Transaction::DD_MANDATE_TABLE . '.entity_id as contact_id',
      'name' => CRM_ManualDirectDebit_Batch_Transaction::DD_MANDATE_TABLE . '.account_holder_name as name',
      'sort_code' => CRM_ManualDirectDebit_Batch_Transaction::DD_MANDATE_TABLE . '.sort_code as sort_code',
      'account_number' => CRM_ManualDirectDebit_Batch_Transaction::DD_MANDATE_TABLE . '.ac_number as account_number',
      'amount' => 'IF(civicrm_contribution.net_amount IS NOT NULL, civicrm_contribution.net_amount , 0) as amount',
      'reference_number' => CRM_ManualDirectDebit_Batch_Transaction::DD_MANDATE_TABLE . '.dd_ref as reference_number',
      'transaction_type' => 'civicrm_option_value.label as transaction_type',
    ];

    $batchTransaction = new CRM_ManualDirectDebit_Batch_Transaction($params['batch_id'], $params, [], $returnValues);

    $mandateItems = $batchTransaction->getDDMandateInstructions();

    $headers = [
      ts('Contact ID'),
      ts('Account Holder Name'),
      ts('Sort code'),
      ts('Account Number'),
      ts('Amount'),
      ts('Reference Number'),
      ts('Transaction Type'),
    ];

    $contents = $this->generateCSVFile($headers, $mandateItems);

    $fileName = 'Batch_' . $this->batchID . '_' . date('YmdHis') . '.csv';
    CRM_Utils_System::setHttpHeader('Content-Type', 'text/plain');
    CRM_Utils_System::setHttpHeader('Content-Disposition', 'attachment; filename=' . CRM_Utils_File::cleanFileName(basename($fileName)));
    ob_clean();
    flush();
    echo $contents;
    CRM_Utils_System::civiExit();
  }

  /**
   * Creates csv File
   *
   * @param array $headers
   *
   * @param \CRM_Core_DAO $export
   *
   * @return string
   */
  private function generateCSVFile($headers, $export) {
    $out = fopen('php://temp/maxmemory:'. (12*1024*1024), 'r+');
    fputcsv($out, $headers);

    while ($export->fetch()) {
      fputcsv($out, $export->toArray());
    }
    rewind($out);
    $contents = stream_get_contents($out);
    fclose($out);

    return $contents;
  }

  /**
   * Returns max batch id
   *
   * @return null|string
   */
  public static function getMaxBatchId() {
    $sql = "SELECT max(id) FROM civicrm_batch";
    return CRM_Core_DAO::singleValueQuery($sql);
  }

}
