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
   *
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
        'status' => " v.value={$statusID }",
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

    $config = CRM_Core_Config::singleton();

    $columnHeader = [
      'contact_id' => ts('Contact ID'),
      'name' => ts('Account Holder Name'),
      'sort_code' => ts('Sort code'),
      'account_number' => ts('Account Number'),
      'amount' => ts('Amount'),
      'reference_number' => ts('Reference Number'),
      'transaction_type' => ts('Transaction Type'),
    ];

    $returnValues = [
      'id' => 'civicrm_value_dd_mandate.id as id',
      'contact_id' => 'civicrm_value_dd_mandate.entity_id as contact_id',
      'name' => 'civicrm_value_dd_mandate.account_holder_name as name',
      'sort_code' => 'civicrm_value_dd_mandate.sort_code as sort_code',
      'account_number' => 'civicrm_value_dd_mandate.ac_number as account_number',
      'amount' => '0 as amount',
      'reference_number' => 'civicrm_value_dd_mandate.dd_ref as reference_number',
      'transaction_type' => 'civicrm_value_dd_mandate.dd_code as transaction_type',
    ];

    $batchTransaction = new CRM_ManualDirectDebit_Batch_Transaction($params['batch_id'], $params, $columnHeader, $returnValues);

    $mandateItems = $batchTransaction->getRows();
    foreach ($mandateItems as &$mandateItem) {
      unset($mandateItem['check']);
    }

    $mandateItems['headers'] = $columnHeader;

    $fileName = $this->generateCSVFile($mandateItems);

    $this->createActivityExport($fileName);

    CRM_Utils_System::setHttpHeader('Content-Type', 'text/plain');
    CRM_Utils_System::setHttpHeader('Content-Disposition', 'attachment; filename=' . CRM_Utils_File::cleanFileName(basename($fileName)));
    CRM_Utils_System::setHttpHeader('Content-Length', '' . filesize($fileName));
    ob_clean();
    flush();
    readfile($config->customFileUploadDir . CRM_Utils_File::cleanFileName(basename($fileName)));
    CRM_Utils_System::civiExit();
  }

  /**
   * Creates csv File
   *
   * @param array $export
   *
   * @return string
   *
   */
  private function generateCSVFile($export) {
    $config = CRM_Core_Config::singleton();
    $fileName = $config->uploadDir . 'Instructions_Batch' . $this->batchID . '_' . date('YmdHis') . '.csv';
    $out = fopen($fileName, 'w');
    if (!empty($export['headers'])) {
      fputcsv($out, $export['headers']);
    }
    unset($export['headers']);
    if (!empty($export)) {
      foreach ($export as $fields) {
        fputcsv($out, $fields);
      }
      fclose($out);
    }

    return $fileName;
  }

  /**
   * Creates activity with type "Export Accounting Batch".
   *
   * @param string $fileName
   *
   */
  private function createActivityExport($fileName) {
    $session = CRM_Core_Session::singleton();
    $values = [];
    $params = ['id' => $this->batchID];
    CRM_Batch_BAO_Batch::retrieve($params, $values);
    $createdBy = CRM_Contact_BAO_Contact::displayName($values['created_id']);
    $modifiedBy = CRM_Contact_BAO_Contact::displayName($values['modified_id']);

    $details = '<p>' . ts('Record:') . ' ' . $values['title'] . '</p><p>' . ts('Description:') . '</p><p>' . ts('Created By:') . " $createdBy" . '</p><p>' . ts('Created Date:') . ' ' . $values['created_date'] . '</p><p>' . ts('Last Modified By:') . ' ' . $modifiedBy . '</p>';
    $subject = '';
    if (!empty($values['total'])) {
      $subject .= ts('Total') . '[' . CRM_Utils_Money::format($values['total']) . '],';
    }
    if (!empty($values['item_count'])) {
      $subject .= ' ' . ts('Count') . '[' . $values['item_count'] . '],';
    }

    // create activity.
    $subject .= ' ' . ts('Batch') . '[' . $values['title'] . ']';
    $activityTypes = CRM_Core_PseudoConstant::get('CRM_Activity_DAO_Activity', 'activity_type_id');
    $activityParams = [
      'activity_type_id' => array_search('Export Accounting Batch', $activityTypes),
      'subject' => $subject,
      'status_id' => 2,
      'activity_date_time' => date('YmdHis'),
      'source_contact_id' => $session->get('userID'),
      'source_record_id' => $values['id'],
      'target_contact_id' => $session->get('userID'),
      'details' => $details,
      'attachFile_1' => [
        'uri' => $fileName,
        'type' => 'text/csv',
        'location' => $fileName,
        'upload_date' => date('YmdHis'),
      ],
    ];

    CRM_Activity_BAO_Activity::create($activityParams);
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
