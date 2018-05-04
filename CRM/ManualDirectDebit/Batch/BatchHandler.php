<?php

/**
 * This class is used for batch handling
 *
 */
class CRM_ManualDirectDebit_Batch_BatchHandler {

  /**
   * Batch
   *
   * @var \CRM_Batch_DAO_Batch
   */
  private $batch;

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
   * Batch type
   *
   * @var string
   */
  private $batchType;

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
   * Gets batch object
   *
   * @return \CRM_Batch_DAO_Batch|object
   */
  public function getBatch() {
    if (!$this->batch) {
      $this->batch = CRM_Batch_DAO_Batch::findById($this->batchID);
    }

    return $this->batch;
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
   * Gets batch type
   *
   * @return string
   */
  public function getBatchType() {
    if (!$this->batchType) {
      $this->getBatch();
      $batchTypes = CRM_Core_PseudoConstant::get('CRM_Batch_DAO_Batch', 'type_id', ['labelColumn' => 'name']);
      $this->batchType = $batchTypes[$this->batch->type_id];
    }

    return $this->batchType;
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
   */
  public function createExportFile() {
    $returnValues = [
      'contact_id' => CRM_ManualDirectDebit_Batch_Transaction::DD_MANDATE_TABLE . '.entity_id as contact_id',
      'name' => CRM_ManualDirectDebit_Batch_Transaction::DD_MANDATE_TABLE . '.account_holder_name as name',
      'sort_code' => CRM_ManualDirectDebit_Batch_Transaction::DD_MANDATE_TABLE . '.sort_code as sort_code',
      'account_number' => CRM_ManualDirectDebit_Batch_Transaction::DD_MANDATE_TABLE . '.ac_number as account_number',
      'amount' => 'IF(civicrm_contribution.net_amount IS NOT NULL, civicrm_contribution.net_amount , 0) as amount',
      'reference_number' => CRM_ManualDirectDebit_Batch_Transaction::DD_MANDATE_TABLE . '.dd_ref as reference_number',
      'transaction_type' => 'civicrm_option_value.label as transaction_type',
    ];

    if ($this->getBatchType() == 'instructions_batch') {
      $entityTable = 'civicrm_value_dd_mandate';
    }
    if ($this->getBatchType() == 'dd_payments') {
      $entityTable = 'civicrm_contribution';
    }
    $batchTransaction = new CRM_ManualDirectDebit_Batch_Transaction($this->batchID, ['entityTable' => $entityTable], [], $returnValues);
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

    $fileName = 'Batch_' . $this->batchID . '_' . date('YmdHis') . '.csv';
    CRM_Utils_System::setHttpHeader('Content-Type', 'text/plain');
    CRM_Utils_System::setHttpHeader('Content-Disposition', 'attachment; filename=' . CRM_Utils_File::cleanFileName(basename($fileName)));
    CRM_Utils_File::cleanFileName(basename($fileName));
    ob_clean();
    flush();
    $this->outputCSVFile($headers, $mandateItems);
    CRM_Utils_System::civiExit();
  }

  /**
   * Output CSV File
   *
   * @param array $headers
   *
   * @param \CRM_Core_DAO $export
   */
  private function outputCSVFile($headers, $export) {
    $out = fopen('php://temp/maxmemory:'. (12*1024*1024), 'r+');
    fputcsv($out, $headers);

    while ($export->fetch()) {
      fputcsv($out, $export->toArray());
    }
    rewind($out);
    fpassthru($out);
    fclose($out);
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

  /**
   * Returns max batch id
   *
   * @param integer $typeId
   *
   * @return null|string
   */
  public static function getSubmitAlertMessage($typeId) {
    $batchTypes = CRM_Core_OptionGroup::values('batch_type', FALSE, FALSE, FALSE, NULL, 'name');

    if ($batchTypes[$typeId] == 'instructions_batch') {
      $submittedMessage = '<p>' . ts('You are submitting the batch results:') . '</p>';
      $submittedMessage .= '<p>' . ts('-All mandates in the batch with code %1 will be transite to code %2', [
          1 => '0N',
          2 => '01',
        ]) . '</p>';
      $submittedMessage .= '<p>' . ts('-The batch will be updated with \'Submitted\' status') . '</p>';
      $submittedMessage .= '<p>' . ts('This process is not revertable.') . '</p>';
    }
    elseif ($batchTypes[$typeId] == 'dd_payments') {
      $submittedMessage = '<p>' . ts('You are submitting the batch results:') . '</p>';
      $submittedMessage .= '<p>' . ts('-All mandates in the batch with code %1 will be transite to code %2', [
          1 => '01',
          2 => '07',
        ]) . '</p>';
      $submittedMessage .= '<p>' . ts('-All contributions in the batch with status \'Pending\' or \'Cancelled\' will be marked as \'Completed\'') . '</p>';
      $submittedMessage .= '<p>' . ts('-The batch will be updated with \'Submitted\' status') . '</p>';
      $submittedMessage .= '<p>' . ts('This process is not revertable.') . '</p>';
    }
    else {
      $submittedMessage = '<p>' . ts('Are you sure you want to submit this batch?') . '</p>';
      $submittedMessage .= '<p>' . ts('This process is not revertable.') . '</p>';
    }

    return $submittedMessage;
  }

  /**
   * Submits batches with types "New Direct Debit Instructions" and "Direct Debit Payments".
   * Updates Direct Debits Mandate code and Contribute status.
   *
   * @return bool
   */
  public function submitBatch() {
    if (!$this->validBatchStatus()) {
      return FALSE;
    }

    if ($this->getBatchType() == 'instructions_batch') {
      $submitted = $this->submitInstructionsBatch();
    }
    if ($this->getBatchType() == 'dd_payments') {
      $submitted = $this->submitDDPayments();
    }

    return !empty($submitted);
  }

  /**
   * Submits a batch with type "Direct Debit Payments".
   *
   * @return bool
   */
  private function submitInstructionsBatch() {
    $batchTransaction = new CRM_ManualDirectDebit_Batch_Transaction($this->batchID, ['entityTable' => 'civicrm_value_dd_mandate'], ['mandate_id' => 1], ['mandate_id' => 'GROUP_CONCAT(civicrm_value_dd_mandate.id SEPARATOR \',\') as mandate_id']);
    $rows = $batchTransaction->getRows();
    $row = array_shift($rows);
    $ddMandateIDs = $row['mandate_id'];
    if ($ddMandateIDs) {
      $this->updateDDMandate('first_time_payment', $ddMandateIDs);
    }

    return TRUE;
  }

  /**
   * Updates Direct Debits Mandates code.
   *
   * @param string $codeName
   * @param string $IDs list of IDs separated by comma
   */
  private function updateDDMandate($codeName, $IDs) {
    $ddCodes = CRM_Core_OptionGroup::values('direct_debit_codes', FALSE, FALSE, FALSE, NULL, 'name');
    $query = 'UPDATE civicrm_value_dd_mandate SET dd_code = "' .  array_search($codeName, $ddCodes) . '" WHERE id IN (' . $IDs . ')';
    CRM_Core_DAO::executeQuery($query);
  }

  /**
   * Submits a batch with type "Direct Debit Payments".
   *
   * @return bool
   */
  private function submitDDPayments() {
    $batchTransaction = new CRM_ManualDirectDebit_Batch_Transaction($this->batchID, ['entityTable' => 'civicrm_value_dd_mandate'], [
      'mandate_id' => 1,
      'contribute_id' => 1,
    ], [
      'mandate_id' => 'GROUP_CONCAT(civicrm_value_dd_mandate.id SEPARATOR \',\') as mandate_id',
      'contribute_id' => 'GROUP_CONCAT(civicrm_contribution.id SEPARATOR \',\') as contribute_id',
    ]);
    $rows = $batchTransaction->getRows();
    $row = array_shift($rows);
    $ddMandateIDs = $row['mandate_id'];
    $contributeIDs = $row['contribute_id'];

    if (!empty($ddMandateIDs)) {
      $this->updateDDMandate('recurring_contribution', $ddMandateIDs);
    }
    if (!empty($contributeIDs)) {
      $this->updateContribute('Completed', $contributeIDs);
    }

    return TRUE;
  }

  /**
   * Updates Contributes status.
   *
   * @param string $status
   * @param string $IDs list of IDs separated by comma
   */
  private function updateContribute($status, $IDs) {
    $ContributeStatuses = CRM_Core_PseudoConstant::get('CRM_Contribute_DAO_Contribution', 'contribution_status_id', ['labelColumn' => 'name']);
    $query = 'UPDATE civicrm_contribution SET contribution_status_id = ' . array_search($status, $ContributeStatuses) . ' WHERE id IN (' . $IDs . ')';
    CRM_Core_DAO::executeQuery($query);
  }

  /**
   * Discard batch.
   * Remove all transaction items from "Entity Batch".
   *
   * @return bool
   */
  public function discardBatch() {
    if (!$this->validBatchStatus()) {
      return FALSE;
    }
    $params = ['batch_id' => $this->batchID];
    $entityBatch = CRM_Batch_BAO_EntityBatch::del($params);

    return !empty($entityBatch);
  }

}
