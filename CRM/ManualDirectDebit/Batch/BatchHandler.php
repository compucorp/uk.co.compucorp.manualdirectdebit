<?php

/**
 * This class is used for batch handling.
 */
class CRM_ManualDirectDebit_Batch_BatchHandler {
  const BATCH_TYPE_INSTRUCTIONS = 'instructions_batch';
  const BATCH_TYPE_PAYMENTS = 'dd_payments';
  const BATCH_TYPE_CANCELLATIONS = 'cancellations_batch';

  /**
   * Batch
   *
   * @var \CRM_Batch_DAO_Batch
   */
  private $batch;

  /**
   * Batch id
   *
   * @var int
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
    $dataForSaving = $this->getDataForSaving();

    $headers = [
      ts('Contact ID'),
      ts('Account Holder Name'),
      ts('Sort code'),
      ts('Account Number'),
      ts('Amount'),
      ts('Reference Number'),
      ts('Transaction Type'),
    ];

    if ($this->getBatchType() == self::BATCH_TYPE_PAYMENTS) {
      $headers[] = ts('Received Date');
    }

    $fileName = 'Batch_' . $this->batchID . '_' . date('YmdHis') . '.csv';
    CRM_Utils_System::setHttpHeader('Content-Type', 'text/plain');
    CRM_Utils_System::setHttpHeader('Content-Disposition', 'attachment; filename=' . CRM_Utils_File::cleanFileName(basename($fileName)));
    CRM_Utils_File::cleanFileName(basename($fileName));
    ob_clean();
    flush();
    $this->outputCSVFile($headers, $dataForSaving);
    CRM_Utils_System::civiExit();
  }

  /**
   * Output CSV File
   *
   * @param array $headers
   * @param array $export
   */
  private function outputCSVFile($headers, $export) {
    $out = fopen('php://temp/maxmemory:' . (12 * 1024 * 1024), 'r+');
    fputcsv($out, $headers);

    foreach ($export as $value) {
      fputcsv($out, $value);
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
   * Returns message which describe submit action
   *
   * @param integer $typeId
   *
   * @return string
   */
  public static function getSubmitAlertMessage($typeId) {
    $batchTypes = CRM_Core_OptionGroup::values('batch_type', FALSE, FALSE, FALSE, NULL, 'name');
    $batchTypeName = CRM_Utils_Array::value($typeId, $batchTypes, '');

    if ($batchTypeName == self::BATCH_TYPE_INSTRUCTIONS) {
      $submittedMessage = '<p>' . ts('You are submitting all items within this batch:') . '</p>';
      $submittedMessage .= '<p>' . ts('- All mandates in the batch that currently have instruction code %1 will be transitioned to instruction code %2', [
        1 => '0N',
        2 => '01',
      ]) . '</p>';
      $submittedMessage .= '<p>' . ts('- The status of this batch will be updated to \'Submitted\'') . '</p>';
      $submittedMessage .= '<p>' . ts('Please note that this process is not reversible.') . '</p>';
    }
    elseif ($batchTypeName == self::BATCH_TYPE_PAYMENTS) {
      $submittedMessage = '<p>' . ts('You are submitting all items within this batch:') . '</p>';
      $submittedMessage .= '<p>' . ts('- All mandates in the batch that currently have the code %1 will be transitioned to the code %2', [
        1 => '01',
        2 => '17',
      ]) . '</p>';

      $submittedMessage .= '<p>' . ts('- All contributions in the batch with status \'Pending\' will be marked as \'Completed\'') . '</p>';
      $submittedMessage .= '<p>' . ts('- The status of this batch will be updated to \'Submitted\'') . '</p>';
      $submittedMessage .= '<p>' . ts('Please note that this process is not reversible.') . '</p>';
    }
    else {
      $submittedMessage = '<p>' . ts('You are submitting all items within this batch:') . '</p>';
      $submittedMessage .= '<p>' . ts('Please note that this process is not reversible.') . '</p>';
    }

    return $submittedMessage;
  }

  /**
   * Gets data for saving
   *
   * @return array
   */
  private function getDataForSaving() {
    $returnValues = [
      'contact_id' => CRM_ManualDirectDebit_Batch_Transaction::DD_MANDATE_TABLE . '.entity_id as contact_id',
      'name' => CRM_ManualDirectDebit_Batch_Transaction::DD_MANDATE_TABLE . '.account_holder_name as name',
      'sort_code' => CRM_ManualDirectDebit_Batch_Transaction::DD_MANDATE_TABLE . '.sort_code as sort_code',
      'account_number' => 'CONCAT("\t",' . CRM_ManualDirectDebit_Batch_Transaction::DD_MANDATE_TABLE . '.ac_number) as account_number',
      'amount' => 'IF(civicrm_contribution.net_amount IS NOT NULL, civicrm_contribution.net_amount , 0.00) as amount',
      'reference_number' => CRM_ManualDirectDebit_Batch_Transaction::DD_MANDATE_TABLE . '.dd_ref as reference_number',
      'transaction_type' => 'CONCAT("\t",civicrm_option_value.label) as transaction_type',
    ];

    if ($this->getBatchType() == self::BATCH_TYPE_PAYMENTS) {
      $returnValues['contribute_id'] = 'civicrm_contribution.id as contribute_id';
      $returnValues['receive_date'] = 'DATE_FORMAT(civicrm_contribution.receive_date, "%d-%m-%Y") as receive_date';
    }
    else {
      $returnValues['mandate_id'] = 'civicrm_value_dd_mandate.id as mandate_id';
    }

    return $this->getMandateCurrentState($returnValues);
  }

  /**
   * Gets snapshoot of current state about mandates in batch
   *
   * @param $returnValues
   *
   * @return array
   */
  public function getMandateCurrentState($returnValues) {
    $dataForExport = [];
    switch ($this->getBatchType()) {
      case self::BATCH_TYPE_INSTRUCTIONS:
      case self::BATCH_TYPE_CANCELLATIONS:
        $entityTable = 'civicrm_value_dd_mandate';
        break;

      case self::BATCH_TYPE_PAYMENTS:
        $entityTable = 'civicrm_contribution';
        break;
    }

    $batchTransaction = new CRM_ManualDirectDebit_Batch_Transaction(
      $this->batchID,
      ['entityTable' => $entityTable],
      [],
      $returnValues
    );

    if ($this->getBatchType() === self::BATCH_TYPE_PAYMENTS) {
      $mandateItems = $batchTransaction->getDDPayments();
    }
    else {
      $mandateItems = $batchTransaction->getDDMandateInstructions();
    }

    foreach ($mandateItems as $mandateItem) {
      switch ($this->getBatchType()) {
        case self::BATCH_TYPE_INSTRUCTIONS:
        case self::BATCH_TYPE_CANCELLATIONS:
          $entityId = $mandateItem['mandate_id'];
          unset($mandateItem['mandate_id']);
          $dataForExport[$entityId] = $mandateItem;
          break;

        case self::BATCH_TYPE_PAYMENTS:
          $entityId = $mandateItem['contribute_id'];
          unset($mandateItem['contribute_id']);
          $dataForExport[$entityId] = $mandateItem;
          break;
      }
    }

    return $dataForExport;
  }

}
