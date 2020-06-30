<?php

/**
 * Class CRM_ManualDirectDebit_Test_Fabricator_OriginatorNumber
 */
class CRM_ManualDirectDebit_Test_Fabricator_OriginatorNumber {

  /**
   * @param array $params
   * @return array
   * @throws CiviCRM_API3_Exception
   */
  public static function fabricate($params = []) {
    $params = array_merge(static::getDefaultParams(), $params);
    return civicrm_api3('OptionValue', 'create', $params);
  }

  /**
   * @return array
   */
  private static function getDefaultParams() {
    return [
      "sequential" => 1,
      'option_group_id' => "direct_debit_originator_number",
      'label' => "01",
      'value' => 1,
    ];
  }

}
