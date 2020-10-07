<?php
use CRM_ManualDirectDebit_Test_Fabricator_Base as BaseFabricator;

/**
 * Class CRM_ManualDirectDebit_Test_Fabricator_Contribution.
 */
class CRM_ManualDirectDebit_Test_Fabricator_Contribution extends BaseFabricator {

  /**
   * Entity name.
   *
   * @var string
   */
  protected static $entityName = 'Contribution';

  /**
   * Fabricates a contribution with given parameters.
   *
   * @param array $params
   *
   * @return mixed
   * @throws \CiviCRM_API3_Exception
   */
  public static function fabricate(array $params = []) {
    $params = array_merge(static::getDefaultParams(), $params);
    $contribution = parent::fabricate($params);
    if (!isset($params['contact_id'])) {
      $contact = CRM_ManualDirectDebit_Test_Fabricator_Contact::fabricate();
      $params['contact_id'] = $contact['id'];
    }

    $contributionSoftParams = CRM_Utils_Array::value('soft_credit', $params);
    if (!empty($contributionSoftParams)) {
      $contributionSoftParams['contribution_id'] = $contribution['id'];
      $contributionSoftParams['currency'] = $contribution['currency'];
      $contributionSoftParams['amount'] = $contribution['total_amount'];

      CRM_Contribute_BAO_ContributionSoft::add($contributionSoftParams);
    }

    return $contribution;
  }

  private static function getDefaultParams() {
    $now = new DateTime();
    return [
      'financial_type_id' => "Member Dues",
      'total_amount' => 100,
      'receive_date' => $now->format('Y-m-d H:i:s'),
      'payment_instrument' => self::getDirectDebitPaymentInstrumentID(),
      'contribution_status_id' => self::getPendingStatusID(),
    ];
  }

  private static function getDirectDebitPaymentInstrumentID() {
    return civicrm_api3('OptionValue', 'get', [
      'sequential' => 1,
      'option_group_id' => "payment_instrument",
      'name' => "direct_debit",
    ])['values'][0]['value'];

  }

  private static function getPendingStatusID() {
    return civicrm_api3('OptionValue', 'get', [
      'sequential' => 1,
      'option_group_id' => "contribution_status",
      'name' => "Pending",
    ])['values'][0]['value'];
  }

}
