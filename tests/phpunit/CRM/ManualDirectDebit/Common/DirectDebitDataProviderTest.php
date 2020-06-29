<?php

require_once __DIR__ . '/../../../BaseHeadlessTest.php';

/**
 * Runs tests on DirectDebitDataProvider.
 *
 * @group headless
 */
class CRM_ManualDirectDebit_Common_DirectDebitDataProviderTest extends BaseHeadlessTest {

  /**
   * Tests getCustomFieldIdbyName function
   */
  public function testGetCustomFieldIdByName() {
    $customFieldId =
      CRM_ManualDirectDebit_Common_DirectDebitDataProvider::getCustomFieldIdByName('mandate_id');
    $this->assertNotNull($customFieldId);
  }

  /**
   * Tests getGroupIDByName function
   */
  public function testGetGroupIDByName() {
    $groupId = CRM_ManualDirectDebit_Common_DirectDebitDataProvider::
      getGroupIDByName('direct_debit_message_template');

    $this->assertNotNull($groupId);
  }

  /**
   * Tests getDirectDebitPaymentInstrumentId
   */
  public function testGetDirectDebitPaymentInstrumentId() {
    $paymentInstrumentId = CRM_ManualDirectDebit_Common_DirectDebitDataProvider::
    getDirectDebitPaymentInstrumentId('direct_debit_message_template');

    $this->assertNotNull($paymentInstrumentId);
  }

}
