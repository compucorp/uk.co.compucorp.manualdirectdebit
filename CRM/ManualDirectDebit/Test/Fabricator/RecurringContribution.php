<?php
use CRM_ManualDirectDebit_Test_Fabricator_Base as BaseFabricator;

/**
 * Class CRM_ManualDirectDebit_Test_Fabricator_RecurringContribution.
 *
 */
class CRM_ManualDirectDebit_Test_Fabricator_RecurringContribution extends BaseFabricator {

  /**
   * Entity name.
   *
   * @var string
   */
  protected static $entityName = 'ContributionRecur';

  /**
   * Fabricates a recurring contribution with given parameters.
   *
   * @param array $params
   *
   * @return mixed
   * @throws \CiviCRM_API3_Exception
   */
  public static function fabricate(array $params = []) {
    $params = array_merge(static::getDefaultParams(), $params);
    if (!isset($params['contact_id'])) {
      $contact = CRM_ManualDirectDebit_Test_Fabricator_Contact::fabricate();
      $params['contact_id'] = $contact['id'];
    }
    return parent::fabricate($params);
  }

  /**
   * @return array
   */
  private static function getDefaultParams() {
    return [
      'amount' => 100,
      'frequency_interval' => 1,
    ];
  }

}
