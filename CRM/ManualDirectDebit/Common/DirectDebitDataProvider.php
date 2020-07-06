<?php

/**
 * Class provide important information about 'Direct Debit Mandate' data structure
 */
class CRM_ManualDirectDebit_Common_DirectDebitDataProvider {

  /**
   * Prefix which added to all custom group field names
   *
   * @var string
   */
  const PREFIX = "directDebitMandate_";

  /**
   * Direct Debit Mandate custom group field
   *
   * @var array
   */
  private $directDebitMandateCustomGroupFields;

  /**
   * CRM_ManualDirectDebit_Common_DirectDebitDataProvider constructor.
   */
  public function __construct() {
    $this->directDebitMandateCustomGroupFields = $this->getDirectDebitMandateFields();
  }

  /**
   * Gets mandate custom group fields data
   *
   * @return array
   */
  private function getDirectDebitMandateFields() {
    return civicrm_api3('CustomField', 'get', [
      'sequential' => 1,
      'custom_group_id' => "direct_debit_mandate",
    ]);
  }

  /**
   * Gets converted information about custom group fields for building form
   *
   * @return array
   */
  public function getMandateCustomFieldDataForBuildingForm() {
    $mandateCustomGroupFieldData = [];
    foreach ($this->directDebitMandateCustomGroupFields['values'] as $value) {
      $params = [];
      $optionGroupId = '';

      if ($value['html_type'] == 'Select Date') {
        $value['html_type'] = 'datepicker';

        $params = [
          'time' => FALSE,
          'date' => 'dd/mm/yy',
        ];
      }

      if ($value['html_type'] == 'Select' && $value['option_group_id'] != 0) {
        $optionGroupId = $this->getOptionList($value['option_group_id']);
      }

      if ($value['name'] == 'dd_ref') {
        $value['html_type'] = 'hidden';
      }

      $mandateCustomGroupFieldData[] = [
        'name' => self::PREFIX . $value['name'],
        'label' => $value['label'],
        'data_type' => $value['data_type'],
        'html_type' => lcfirst($value['html_type']),
        'is_required' => $value['is_required'],
        'option_group_id' => $optionGroupId,
        'params' => $params,
      ];
    }

    return $mandateCustomGroupFieldData;
  }

  /**
   * Gets list of option values
   *
   * @param $optionGroupId
   *
   * @return array
   */
  private function getOptionList($optionGroupId) {
    $optionValue = civicrm_api3('OptionValue', 'get', [
      'sequential' => 1,
      'option_group_id' => "$optionGroupId",
    ]);

    $list = [];
    foreach ($optionValue['values'] as $value) {
      $list[$value['value']] = $value['label'];
    }

    return $list;
  }

  /**
   * Gets direct Debit Mandate Custom Field names
   *
   * @return array
   */
  public function getMandateCustomFieldNames() {
    $mandateCustomGroupFieldNames = [];
    foreach ($this->directDebitMandateCustomGroupFields['values'] as $value) {
      $mandateCustomGroupFieldNames[] = self::PREFIX . $value['name'];
    }

    return $mandateCustomGroupFieldNames;
  }

  /**
   * Checks if current payment method Id is Direct Debit Mandate
   *
   * @param $currentMethodId
   *
   * @return bool
   */
  public static function isPaymentMethodDirectDebit($currentMethodId) {
    $directDebitPaymentMethod = civicrm_api3('OptionValue', 'getvalue', [
      'return' => "value",
      'name' => "direct_debit",
      'option_group_id' => "payment_instrument",
    ]);

    return $currentMethodId == $directDebitPaymentMethod;
  }

  /**
   * Checks if current Payment Processor Id is Direct Debit
   *
   * @param $currentPaymentProcessor
   *
   * @return bool
   */
  public static function isDirectDebitPaymentProcessor($currentPaymentProcessor) {

    $directDebitPaymentProcessorId = civicrm_api3('PaymentProcessor', 'getvalue', [
      'return' => "id",
      'name' => "Direct Debit",
      'is_test' => 0,
    ]);

    if ($directDebitPaymentProcessorId != $currentPaymentProcessor) {
      $directDebitPaymentProcessorId = civicrm_api3('PaymentProcessor', 'getvalue', [
        'return' => "id",
        'name' => "Direct Debit",
        'is_test' => 1,
      ]);
    }
    return $directDebitPaymentProcessorId == $currentPaymentProcessor;
  }

  /**
   * Checks if current group Id is Direct Debit Mandate
   *
   * @param $currentGroupId
   *
   * @return bool
   */
  public static function isDirectDebitCustomGroup($currentGroupId) {
    $directDebitMandateId = civicrm_api3('CustomGroup', 'getvalue', [
      'sequential' => 1,
      'return' => "id",
      'name' => "direct_debit_mandate",
    ]);

    return $currentGroupId == $directDebitMandateId;
  }

  /**
   * Gets id od direct debit payment instrument
   *
   * @return int
   */
  public static function getDirectDebitPaymentInstrumentId() {
    $directDebitPaymentInstrumentId = civicrm_api3('OptionValue', 'getvalue', [
      'return' => "value",
      'name' => "direct_debit",
      'option_group_id' => "payment_instrument",
    ]);

    return $directDebitPaymentInstrumentId;
  }

  /**
   * Gets id of custom group by name
   *
   * @param $customGroupName
   *
   * @return int
   */
  public static function getGroupIDByName($customGroupName) {
    return civicrm_api3('CustomGroup', 'getvalue', [
      'return' => "id",
      'name' => $customGroupName,
    ]);
  }

  /**
   * Gets id of custom field by name
   *
   * @param $customFieldName
   *
   * @return int
   */
  public static function getCustomFieldIdByName($customFieldName) {
    return civicrm_api3('CustomField', 'getvalue', [
      'return' => "id",
      'name' => $customFieldName,
    ]);
  }

  /**
   * Gets Id of payment processor for current recurring contribution
   *
   * @param $contributionRecurId
   *
   * @return int
   */
  public static function getCurrentPaymentProcessorId($contributionRecurId) {
    return civicrm_api3('ContributionRecur', 'getvalue', [
      'return' => "payment_processor_id",
      'id' => $contributionRecurId,
    ]);
  }

  /**
   * Gets id of current payment instrument
   *
   * @param $currentRecurringContributionId
   *
   * @return int
   */
  public static function getPaymentInstrumentIdOfRecurrContribution($currentRecurringContributionId) {
    return civicrm_api3('ContributionRecur', 'getvalue', [
      'return' => "payment_instrument_id",
      'id' => $currentRecurringContributionId,
    ]);
  }

  /**
   * Gets max id of 'direct debit mandate'
   *
   * @return int
   */
  public static function getMaxMandateId() {
    $sqlSelectDebitMandateID = "SELECT MAX(`id`) as id FROM `civicrm_value_dd_mandate`";
    $queryResult = CRM_Core_DAO::executeQuery($sqlSelectDebitMandateID);
    $queryResult->fetch();

    return $queryResult->id;
  }

}
