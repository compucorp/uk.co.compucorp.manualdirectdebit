<?php

use CRM_ManualDirectDebit_Test_Fabricator_OriginatorNumber as OriginatorNumberFabricator;

/**
 * Class CRM_ManualDirectDebit_Test_Fabricator_Mandate
 */
class CRM_ManualDirectDebit_Test_Fabricator_Mandate {

  /**
   * @param array $params
   * @return array
   * @throws CiviCRM_API3_Exception
   */
  public static function fabricate($params = []) {
    $params = array_merge(static::getDefaultParams(), $params);
    if (!isset($params['entity_id'])) {
      $contact = CRM_ManualDirectDebit_Test_Fabricator_Contact::fabricate();
      $params['entity_id'] = $contact['id'];
    }

    if (!isset($params['originator_number'])) {
      $originatorNumber = OriginatorNumberFabricator::fabricate();
      $params['originator_number'] = $originatorNumber['values'][0]['value'];
    }

    $storageManager = new CRM_ManualDirectDebit_Common_MandateStorageManager();
    return (array) $storageManager->saveDirectDebitMandate($params['entity_id'], $params);
  }

  /**
   * @return array
   */
  private static function getDefaultParams() {
    $now = new DateTime();
    return [
      'bank_name' => 'Lloyds Bank',
      'account_holder_name' => 'John Doe',
      'ac_number' => '12345678',
      'sort_code' => '12-34-56',
      'dd_code' => 1,
      'dd_ref' => 'DD Ref',
      'start_date' => $now->format('Y-m-d H:i:s'),
      'authorisation_date' => $now->format('Y-m-d H:i:s'),
    ];
  }

}
