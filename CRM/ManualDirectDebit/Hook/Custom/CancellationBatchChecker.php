<?php
use CRM_ManualDirectDebit_Common_MandateStorageManager as MandateStorageManager;

/**
 * Class CRM_ManualDirectDebit_Hook_Custom_CancellationBatchChecker.
 *
 * Checks if the mandate's status has changed vs active cancellations, to remove
 * it from the batch if need be. MAndates that change their status from '0C' to
 * anything else should be removed from pending (unsubmitted) batches.
 */
class CRM_ManualDirectDebit_Hook_Custom_CancellationBatchChecker {

  private $contactID;
  private $params;
  private $mandateStorageManager;
  private $ddCodes;

  /**
   * CRM_ManualDirectDebit_Hook_Custom_CancellationBatchChecker constructor.
   *
   * @param $contactID
   * @param $params
   * @param \CRM_ManualDirectDebit_Common_MandateStorageManager $mandateStorageManager
   */
  public function __construct($contactID, $params, MandateStorageManager $mandateStorageManager) {
    $this->contactID = $contactID;
    $this->params = $params;
    $this->mandateStorageManager = $mandateStorageManager;
    $this->ddCodes = $this->getDDCodes();
  }

  /**
   * Obtains list of dd codes, mapping their name to their value.
   *
   * @return array
   */
  private function getDDCodes() {
    return CRM_Core_OptionGroup::values('direct_debit_codes', FALSE, FALSE, FALSE, NULL, 'name');
  }

  /**
   * Runs the check for invalid mandates in cancellations.
   *
   * Processes the contact to check for mandates in cancellation batches with
   * stuats different to '0C'.
   *
   * @throws \CiviCRM_API3_Exception
   */
  public function process() {
    $mandates = $this->getMandates();

    foreach ($mandates as $checkedMandate) {
      $this->checkMandate($checkedMandate);
    }
  }

  /**
   * Returns array of mandates associated to contact.
   *
   * @return array
   */
  private function getMandates() {
    return $this->mandateStorageManager->getMandatesForContact($this->contactID);
  }

  /**
   * Checks if the mandate needs to be removed from a cancellations batch.
   *
   * @param array $mandate
   *
   * @throws \CiviCRM_API3_Exception
   */
  private function checkMandate($mandate) {
    if ($this->isCancelledMandate($mandate)) {
      return;
    }

    $pendingCancellationBatches = $this->getPendingCancellationBatches();
    foreach ($pendingCancellationBatches as $batch) {
      $batchItemID = $this->getPendingCancellationBatchItemID($mandate, $batch);
      if ($batchItemID) {
        $this->removeBatchItem($batchItemID);
      }
    }
  }

  /**
   * Checks if the given mandate has been cancelled.
   *
   * @param $mandate
   *
   * @return bool
   */
  private function isCancelledMandate($mandate) {
    if ($this->ddCodes[$mandate['dd_code']] === MandateStorageManager::DD_CODE_NAME_CANCELDIRECTDEBIT) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Obtains list of open cancellation batches.
   *
   * @return array|mixed
   * @throws \CiviCRM_API3_Exception
   */
  private function getPendingCancellationBatches() {
    $result = civicrm_api3('Batch', 'get', [
      'sequential' => 1,
      'type_id' => 'cancellations_batch',
      'status_id' => 'Open',
    ]);

    if (!empty($result['values'])) {
      return $result['values'];
    }

    return [];
  }

  /**
   * Obteins batch item ofr the mandate in the given batch.
   *
   * @param array $mandate
   * @param array $batch
   *
   * @return int
   * @throws \CiviCRM_API3_Exception
   */
  private function getPendingCancellationBatchItemID($mandate, $batch) {
    $result = civicrm_api3('EntityBatch', 'get', [
      'sequential' => 1,
      'batch_id' => $batch['id'],
      'entity_id' => $mandate['id'],
      'entity_table' => MandateStorageManager::DIRECT_DEBIT_TABLE_NAME,
    ]);

    if ($result['count'] > 0) {
      return $result['values'][0]['id'];
    }

    return 0;
  }

  /**
   * Deletes the given batch item.
   *
   * @param int $batchItemID
   *
   * @throws \CiviCRM_API3_Exception
   */
  private function removeBatchItem($batchItemID) {
    civicrm_api3('EntityBatch', 'delete', [
      'sequential' => 1,
      'id' => $batchItemID,
      'options' => ['limit' => 0],
    ]);
  }

}
