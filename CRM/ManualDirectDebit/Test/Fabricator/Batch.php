<?php
use CRM_ManualDirectDebit_Test_Fabricator_Base as BaseFabricator;

/**
 * Class CRM_ManualDirectDebit_Test_Fabricator_Batch.
 */
class CRM_ManualDirectDebit_Test_Fabricator_Batch extends BaseFabricator {

  /**
   * Entity's name.
   *
   * @var string
   */
  protected static $entityName = 'Batch';

  /**
   * Array if default parameters to be used to create a batch.
   *
   * @var array
   */
  protected static $defaultParams = [
    'name' => 'Direct_Debit_Batch_1',
    'title'   => 'Direct Debit Batch - 1',
    'status_id' => 1,
  ];

  /**
   * Fabricates a batch with the given parameters.
   *
   * @param array $params
   *
   * @return array
   * @throws \CiviCRM_API3_Exception
   */
  public static function fabricate(array $params = []) {
    $params = array_merge(static::$defaultParams, $params);

    return parent::fabricate($params);
  }

}
