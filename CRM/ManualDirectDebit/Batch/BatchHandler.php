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

    $batchTransaction = new CRM_ManualDirectDebit_Batch_Transaction($params['batch_id'], $params);

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

    $fileName = $this->generateCSVFile($headers, $mandateItems);

    $this->createActivityExport($fileName);

    CRM_Utils_System::setHttpHeader('Content-Type', 'text/plain');
    CRM_Utils_System::setHttpHeader('Content-Disposition', 'attachment; filename=' . CRM_Utils_File::cleanFileName(basename($fileName)));
    CRM_Utils_System::setHttpHeader('Content-Length', '' . filesize($config->customFileUploadDir . CRM_Utils_File::cleanFileName(basename($fileName))));
    ob_clean();
    flush();
    readfile($config->customFileUploadDir . CRM_Utils_File::cleanFileName(basename($fileName)));
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
    $config = CRM_Core_Config::singleton();
    $fileName = $config->uploadDir . 'Instructions_Batch' . $this->batchID . '_' . date('YmdHis') . '.csv';
    $out = fopen($fileName, 'w');

    fputcsv($out, $headers);

    while ($export->fetch()) {
      fputcsv($out, $export->toArray());
    }
    fclose($out);

    return $fileName;
  }

  /**
   * Creates activity with type "Export Accounting Batch".
   *
   * @param string $fileName
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
