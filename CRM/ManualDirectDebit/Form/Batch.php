<?php

use CRM_ManualDirectDebit_ExtensionUtil as E;
use CRM_ManualDirectDebit_Batch_BatchHandler as BatchHandler;

/**
 * This class generates form components for Create Instructions Batch
 */
class CRM_ManualDirectDebit_Form_Batch extends CRM_Admin_Form {

  private $batchType;

  /**
   * PreProcess function.
   */
  public function preProcess() {
    parent::preProcess();

    $batchTypeID = CRM_Utils_Request::retrieveValue('type_id', 'String', NULL);
    $this->batchType = CRM_Core_OptionGroup::getRowValues('batch_type', $batchTypeID, 'value', 'String', FALSE);

    // Set the user context.
    $session = CRM_Core_Session::singleton();
    $session->replaceUserContext(CRM_Utils_System::url('civicrm/direct_debit/batch', "reset=1&action=add&type_id=" . $this->batchType['value']));

    CRM_Utils_System::setTitle(E::ts('Create %1', [1 => $this->batchType['label']]));
    if ($this->batchType['name'] === BatchHandler::BATCH_TYPE_CANCELLATIONS) {
      CRM_Utils_System::setTitle(E::ts('Create Cancelled Instructions Batch'));
      $this->batchType['label'] .= ' Batch';
    }

    $newBatchID = BatchHandler::getMaxBatchId() + 1;
    $this->assign('batch_id', $newBatchID);
    $this->add('hidden', 'batch_id', $newBatchID);
    $this->add('hidden', 'type_id', $this->batchType['value']);
    $this->assign('batch_type', $this->batchType);
  }

  /**
   * Builds the form object.
   */
  public function buildQuickForm() {
    parent::buildQuickForm();
    $this->addFormRule([get_class($this), 'validateBatchID'], $this);

    $attributes = CRM_Core_DAO::getAttribute('CRM_Batch_DAO_Batch');
    $this->add('text', 'title', ts('Batch Name'), $attributes['name'], TRUE);
    $this->add('select', 'originator_number', ts('Originator number'),
      ['' => ts('- select -')] + CRM_Core_OptionGroup::values('direct_debit_originator_number'),
      TRUE
    );

    $cancelURL = CRM_Utils_System::url('civicrm/');
    $this->addButtons([
      [
        'type' => 'cancel',
        'name' => ts('Cancel'),
        'js' => ['onclick' => "location.href='{$cancelURL}'; return false;"],
      ],
      [
        'type' => 'next',
        'name' => ts('Next'),
        'isDefault' => TRUE,
      ],
    ]);
  }

  /**
   * BatchID form rule.
   *
   * @param array $fields
   *   The input form values.
   * @param array $files
   *   The uploaded files if any.
   * @param object $self
   *
   * @return bool|array
   *   true if no errors, else array of errors
   */
  public static function validateBatchID($fields, $files, $self) {

    if ($fields['batch_id'] <= BatchHandler::getMaxBatchId()) {
      $errors['batch_id'] = ts(
        'Batch ID %1 already exists. It is likely another batch has just been created a moment ago. Please %2 to reload the page and try again.',
        [
          1 => $fields['batch_id'],
          2 => '<a href="' . CRM_Utils_System::url('civicrm/direct_debit/batch', "reset=1&action=add&type_id=" . $fields['type_id']) . '">' . ts('click here') . '</a>',
        ]
      );
      $self->assign('batch_id', $fields['batch_id']);
    }

    return empty($errors) ? TRUE : $errors;
  }

  /**
   * Sets defaults for form.
   *
   * @see CRM_Core_Form::setDefaultValues()
   */
  public function setDefaultValues() {
    $defaults = parent::setDefaultValues();

    $sql = "SELECT max(id) FROM civicrm_batch";
    $batchNo = CRM_Core_DAO::singleValueQuery($sql) + 1;

    if ($this->batchType['name'] == BatchHandler::BATCH_TYPE_INSTRUCTIONS) {
      $defaults['title'] = ts('New Instruction Batch - %1', [1 => $batchNo]);
    }

    if ($this->batchType['name'] == BatchHandler::BATCH_TYPE_PAYMENTS) {
      $defaults['title'] = ts('Direct Debit Batch - %1', [1 => $batchNo]);
      $defaults['end_date_filter'] = (new DateTime())->format('Y-m-d');
    }

    if ($this->batchType['name'] == BatchHandler::BATCH_TYPE_CANCELLATIONS) {
      $defaults['title'] = ts('Cancelled Instruction Batch - %1', [1 => $batchNo]);
    }

    return $defaults;
  }

  /**
   * postProcess function.
   */
  public function postProcess() {
    $session = CRM_Core_Session::singleton();
    $batchStatus = CRM_Core_PseudoConstant::get('CRM_Batch_DAO_Batch', 'status_id');
    $params = $this->controller->exportValues($this->_name);

    $params['data'] = json_encode([
      'values' => ['originator_number' => $params['originator_number']],
    ]);

    $params['modified_date'] = date('YmdHis');
    $params['modified_id'] = $session->get('userID');
    $batchMode = CRM_Core_PseudoConstant::get('CRM_Batch_DAO_Batch', 'mode_id', ['labelColumn' => 'name']);
    $params['mode_id'] = CRM_Utils_Array::key('Manual Batch', $batchMode);
    $params['status_id'] = CRM_Utils_Array::key('Open', $batchStatus);
    $params['created_date'] = date('YmdHis');
    $params['created_id'] = $session->get('userID');

    $batch = CRM_Batch_BAO_Batch::create($params);

    $this->_id = $batch->id;

    $this->createBatchActivity($batch->title);

    if ($batch->title) {
      CRM_Core_Session::setStatus(ts("'%1' batch has been saved.", [1 => $batch->title]), ts('Saved'), 'success');
    }

    $session->replaceUserContext(CRM_Utils_System::url('civicrm/direct_debit/batch-transaction',
      "reset=1&bid={$batch->id}&action=update"));
  }

  /**
   * Creates activity for new batch
   *
   * @param object $batchTitle
   *
   */
  private function createBatchActivity($batchTitle) {
    $activityTypes = CRM_Core_PseudoConstant::get('CRM_Activity_DAO_Activity', 'activity_type_id');
    $details = ts('%1 batch has been created by this contact.', [1 => $batchTitle]);
    $activityParams = [
      'activity_type_id' => array_search('Create Batch', $activityTypes),
      'subject' => $batchTitle,
      'status_id' => 2,
      'priority_id' => 2,
      'activity_date_time' => date('YmdHis'),
      'source_contact_id' => CRM_Core_Session::singleton()->get('userID'),
      'source_contact_qid' => CRM_Core_Session::singleton()->get('userID'),
      'details' => $details,
    ];

    CRM_Activity_BAO_Activity::create($activityParams);
  }

}
