<?php
use CRM_ManualDirectDebit_Batch_BatchHandler as BatchHandler;

/**
 * Class CRM_ManualDirectDebit_Hook_Alter_ContactDetailReport.
 */
class CRM_ManualDirectDebit_Hook_Alter_ContactDetailReport {

  /**
   * Batches
   *
   * @var array
   */
  private $_batches = [];

  /**
   * Handle the hook.
   *
   * @param string $varType
   * @param CRM_Report_Form_Contact_Detail|array $var
   * @param CRM_Report_Form_Contact_Detail $reportForm
   */
  public function handle($varType, &$var, &$reportForm) {
    if (!$this->shouldHandle(get_class($reportForm))) {
      return;
    }

    $methodName = 'update' . ucfirst($varType);
    call_user_func_array([$this, $methodName], array(&$var));
  }

  /**
   * Checks if the hook should be handled.
   *
   * @param class $reportFormClass
   *
   * @return bool
   */
  private function shouldHandle($reportFormClass) {
    if ($reportFormClass === CRM_Report_Form_Contact_Detail::class) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Checks if the SQL should be updated.
   *
   * @param CRM_Report_Form_Contact_Detail $reportForm
   *
   * @return bool
   */
  private function shouldUpdate($reportForm) {
    if (
      isset($reportForm->getVar('_params')['fields']['dd_instruction_batch_id'])
      || isset($reportForm->getVar('_params')['dd_instruction_batch_id_value'])
      || isset($reportForm->getVar('_params')['dd_instruction_batch_id_op'])
    ) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Update column list
   *
   * @param array $columns
   */
  private function updateColumns(&$columns) {
    $batches = $this->getBatches();
    $columns['civicrm_value_dd_mandate']['fields']['dd_instruction_batch_id'] = [
      'title' => ts('Instruction Batch'),
      'dbAlias' => "instruction_batches.batch_id",
    ];

    $columns['civicrm_value_dd_mandate']['filters']['dd_instruction_batch_id'] = [
      'title' => ts('Instruction Batch'),
      'dbAlias' => 'instruction_batches.batch_id',
      'type' => CRM_Utils_Type::T_INT,
      'operatorType' => CRM_Report_Form::OP_MULTISELECT,
      'options' => $batches,
    ];
  }

  /**
   * update the SQL Query
   *
   * @param CRM_Report_Form_Contact_Detail $reportForm
   */
  private function updateSql(&$reportForm) {
    if (!$this->shouldUpdate($reportForm)) {
      return;
    }

    $from = $reportForm->getVar('_from');

    // prevent double left join with civicrm_value_dd_mandate
    if (strpos($from, 'civicrm_value_dd_mandate') === FALSE) {
      $from .= "
          LEFT JOIN civicrm_value_dd_mandate value_dd_mandate_civireport
          ON (value_dd_mandate_civireport.entity_id = contact_civireport.id)
        ";
    }

    $from .= "
        LEFT JOIN civicrm_entity_batch instruction_batches
        ON (instruction_batches.entity_table = 'civicrm_value_dd_mandate'
        AND instruction_batches.entity_id = value_dd_mandate_civireport.id)
      ";

    $reportForm->setVar('_from', $from);
  }

  /**
   * Update rows for display
   *
   * @param array $rows
   */
  private function updateRows(&$rows) {
    $batches = $this->getBatches();
    foreach ($rows as $rowNum => $row) {
      if (isset($rows[$rowNum]['civicrm_value_dd_mandate_dd_instruction_batch_id'])) {
        $rows[$rowNum]['civicrm_value_dd_mandate_dd_instruction_batch_id'] = $batches[$row['civicrm_value_dd_mandate_dd_instruction_batch_id']] ?? NULL;
      }
    }
  }

  /**
   * Get Batches
   *
   * @return array $batches
   */
  private function getBatches() {
    if (count($this->_batches)) {
      return $this->_batches;
    }

    $condition = " AND (
      v.name = '" . BatchHandler::BATCH_TYPE_INSTRUCTIONS . "'
      OR v.name = '" . BatchHandler::BATCH_TYPE_PAYMENTS . "'
      OR v.name = '" . BatchHandler::BATCH_TYPE_CANCELLATIONS . "'
    )";
    $batchTypeIds = CRM_Core_OptionGroup::values('batch_type', FALSE, FALSE, FALSE, $condition, 'value');

    $params = [
      'type_id' => ['IN' => $batchTypeIds],
      'options' => ['limit' => 0],
      'return' => ['id', 'title'],
    ];
    $result = civicrm_api3('Batch', 'get', $params);

    foreach ($result['values'] as $batch) {
      $this->_batches[$batch['id']] = $batch['title'];
    }
    return $this->_batches;
  }

}
