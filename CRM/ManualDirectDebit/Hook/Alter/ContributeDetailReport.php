<?php
use CRM_ManualDirectDebit_Batch_BatchHandler as BatchHandler;

/**
 * Class CRM_ManualDirectDebit_Hook_Alter_ContributeDetailReport.
 */
class CRM_ManualDirectDebit_Hook_Alter_ContributeDetailReport {

  /**
   * Batches
   *
   * @var array
   */
  protected $_batches = [];

  /**
   * Handle the hook.
   *
   * @param string $varType
   * @param CRM_Report_Form_Contribute_Detail|array $var
   * @param CRM_Report_Form_Contribute_Detail $reportForm
   */
  public function handle($varType, &$var, &$reportForm) {
    if (!$this->shouldHandle(get_class($reportForm))) {
      return;
    }

    $methodName = 'update' . ucfirst($varType);
    call_user_func_array([$this, $methodName], array(&$var));

    // @note Bug1: Updating columns and sql will not work the same way like in ContactDetailReport.php
    // because the changes that have done by sql hook will be lost in
    // https://github.com/civicrm/civicrm-core/blob/3662d5a75d79d6c259b632df748b1beb66db6faf/CRM/Report/Form/Contribute/Detail.php#L956

    // @note Bug2: The sql query wil not work if the user used this column or filter
    // so the column will not be availbe in the form and in the POST request
    if ($varType === 'columns') {
      // we have to use the request because reportForm->_params variable is null
      $fields = CRM_Utils_Request::retrieveValue('fields', 'String', []);
      if (isset($fields['dd_payment_batch_id'])) {
        unset($var['civicrm_value_dd_information']['fields']['dd_payment_batch_id']);
      }
    }
  }

  /**
   * Checks if the hook should be handled.
   *
   * @param class $reportFormClass
   *
   * @return bool
   */
  protected function shouldHandle($reportFormClass) {
    if ($reportFormClass === CRM_Report_Form_Contribute_Detail::class) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Checks if the SQL should be updated.
   *
   * @param CRM_Report_Form_Contribute_Detail $reportForm
   *
   * @return bool
   */
  protected function shouldUpdate($reportForm) {
    // @note related to Bug2
    $fields = CRM_Utils_Request::retrieveValue('fields', 'String', []);
    if (isset($fields['dd_payment_batch_id'])) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Update column list
   *
   * @param array $columns
   */
  protected function updateColumns(&$columns) {
    $batches = $this->getBatches();

    $columns['civicrm_value_dd_information']['fields']['dd_payment_batch_id'] = [
      'title' => ts('Payment Batch'),
      'dbAlias' => "payment_batches.batch_id",
    ];
  }

  /**
   * update the SQL Query
   *
   * @param CRM_Report_Form_Contact_Detail $reportForm
   */
  protected function updateSql(&$reportForm) {
    if (!$this->shouldUpdate($reportForm)) {
      return;
    }

    // @note related to Bug2
    $reportForm->_columnHeaders['civicrm_value_dd_information_dd_payment_batch_id'] = array(
      'title' => ts('Payment Batch'),
    );

    $reportForm->_select .= ' , GROUP_CONCAT(DISTINCT payment_batches.batch_id) as civicrm_value_dd_information_dd_payment_batch_id ';
    $from = $reportForm->getVar('_from');
    $from .= "
        LEFT JOIN civicrm_entity_batch payment_batches
        ON (payment_batches.entity_table = 'civicrm_contribution'
        AND payment_batches.entity_id = contribution_civireport.id)
      ";
    $reportForm->setVar('_from', $from);
  }

  /**
   * Update rows for display
   *
   * @param array $rows
   */
  protected function updateRows(&$rows) {
    $batches = $this->getBatches();
    foreach ($rows as $rowNum => $row) {
      if (isset($rows[$rowNum]['civicrm_value_dd_information_dd_payment_batch_id'])) {
        $rows[$rowNum]['civicrm_value_dd_information_dd_payment_batch_id'] = $batches[$row['civicrm_value_dd_information_dd_payment_batch_id']] ?? NULL;
      }
    }
  }

  /**
   * Get Batches
   *
   * @return array $batches
   */
  protected function getBatches() {
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
      'return' => ['id', 'title'],
    ];
    $result = civicrm_api3('Batch', 'get', $params);

    foreach ($result['values'] as $batch) {
      $this->_batches[$batch['id']] = $batch['title'];
    }
    return $this->_batches;
  }

}
