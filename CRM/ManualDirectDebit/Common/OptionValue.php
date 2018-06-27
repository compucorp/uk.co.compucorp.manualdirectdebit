<?php

/**
 * Class provide methods with 'OptionValue'
 */
class CRM_ManualDirectDebit_Common_OptionValue {

  /**
   * Gets the specified option value ID (value)
   * for the specified option group.
   *
   * @param string $optionGroupName
   * @param string $optionValueName
   *
   * @return string
   */
  public static function getValueForOptionValue($optionGroupName, $optionValueName) {
    $optionValue = civicrm_api3('OptionValue', 'getSingle', [
      'sequential' => 1,
      'options' => ['limit' => 1],
      'return' => ["value"],
      'option_group_id' => $optionGroupName,
      'name' => $optionValueName,
    ]);

    return $optionValue['value'];
  }

}
