<?php

use CRM_ManualDirectDebit_Test_Fabricator_Mandate as MandateFabricator;
use CRM_ManualDirectDebit_Test_Fabricator_Setting as SettingFabricator;
use CRM_ManualDirectDebit_Test_Fabricator_Contact as ContactFabricator;
use CRM_ManualDirectDebit_Test_Fabricator_Batch as BatchFabricator;
use CRM_ManualDirectDebit_Test_Fabricator_OriginatorNumber as OriginatorNumberFabricator;

require_once __DIR__ . '/../../../BaseHeadlessTest.php';

/**
 * Runs tests on RecurrMandateRef.
 *
 * @group headless
 */
class CRM_ManualDirectDebit_Batch_TransactionTest extends BaseHeadlessTest {

  protected $recurringContribution;
  protected $mandateStorage;
  protected $testRollingMembershipType;
  protected $testRollingMembershipTypePriceFieldValue;
  protected $batch;
  protected $tag1;
  protected $tag2;
  protected $group1;
  protected $group2;
  protected $contact1;
  protected $contact2;
  protected $mandate1;
  protected $mandate2;
  protected $batchTypeId;
  protected $recurringContributionDDCode;
  protected $directDebitPaymentInstrumentId;
  protected $memberDuesFinancialTypeId;
  protected $contributionPendingStatusValue;

  public function setUp() {
    SettingFabricator::fabricate();
    $this->mandateStorage = new CRM_ManualDirectDebit_Common_MandateStorageManager();
    $this->paymentBatchTypeId = $this->getPaymentBatchTypeId();
    $this->batch = BatchFabricator::fabricate(['type_id' => $this->paymentBatchTypeId]);
    $this->originatorNumber = OriginatorNumberFabricator::fabricate()['values'][0]['value'];
    $this->recurringContributionDDCode = $this->getRecurringContributionDDCode();
    $this->setUpMembershipType();

    $this->tag1 = $this->createTag(['name' => 'tag1']);
    $this->tag2 = $this->createTag(['name' => 'tag2']);

    $this->group1 = $this->createGroup(['name' => 'group1']);
    $this->group2 = $this->createGroup(['name' => 'group2']);

    $this->clearMandateContributionConnectorProperties();
  }

  /**
   * Clear MandateContributionConnector properties
   *
   * Sometimes the object has properties from previous test because its class follow the singlton pattern
   * we need to clear those properties between tests by accessing its private method
   */
  public function clearMandateContributionConnectorProperties() {
    $mandateContributionConnector = CRM_ManualDirectDebit_Hook_MandateContributionConnector::getInstance();
    $class = new \ReflectionClass($mandateContributionConnector);
    $method = $class->getMethod("refreshProperties");
    $method->setAccessible(TRUE);
    $method->invokeArgs($mandateContributionConnector, []);
  }

  /**
   * Prepare Membership Type
   *
   * @throws \CiviCRM_API3_Exception
   */
  private function setUpMembershipType() {
    $this->directDebitPaymentInstrumentId = $this->getDirectDebitPaymentInstrumentId();
    $this->memberDuesFinancialTypeId = $this->getMemberDuesFinancialTypeId();
    $this->contributionPendingStatusValue = $this->getPendingContributionStatusValue();

    $this->testRollingMembershipType = CRM_MembershipExtras_Test_Fabricator_MembershipType::fabricate(
      [
        'name' => 'Test Rolling Membership',
        'period_type' => 'rolling',
        'minimum_fee' => 120,
        'duration_interval' => 1,
        'duration_unit' => 'year',
      ]);

    $this->testRollingMembershipTypePriceFieldValue = civicrm_api3('PriceFieldValue', 'get', [
      'sequential' => 1,
      'membership_type_id' => $this->testRollingMembershipType['id'],
      'options' => ['limit' => 1],
    ])['values'][0];
  }

  /**
   * Obtains value for the 'Payment' batch type option value.
   *
   * @return array
   * @throws \CiviCRM_API3_Exception
   */
  private function getPaymentBatchTypeId() {
    return civicrm_api3('OptionValue', 'getvalue', [
      'return' => 'value',
      'option_group_id' => 'batch_type',
      'name' => 'dd_payments',
    ]);
  }

  /**
   * Obtains value for the 'Payment' batch type option value.
   *
   * @return array
   * @throws \CiviCRM_API3_Exception
   */
  private function getRecurringContributionDDCode() {
    return civicrm_api3('OptionValue', 'getvalue', [
      'return' => 'value',
      'option_group_id' => 'direct_debit_codes',
      'name' => 'recurring_contribution',
    ]);
  }

  /**
   * Obtains value for the 'Payment' batch type option value.
   *
   * @return array
   * @throws \CiviCRM_API3_Exception
   */
  private function getActiveDDCodes() {
    $result = civicrm_api3('OptionValue', 'get', [
      'return' => 'value',
      'option_group_id' => 'direct_debit_codes',
      'name' => ['IN' => ['first_time_payment', 'recurring_contribution']],
    ]);

    $activeDDCodes = array_map(function ($item) {
      return $item['value'];
    }, $result['values']);

    return implode(',', $activeDDCodes);
  }

  /**
   * Obtains value for the 'Pending' contribution status option value.
   *
   * @return array
   * @throws \CiviCRM_API3_Exception
   */
  private function getPendingContributionStatusValue() {
    return civicrm_api3('OptionValue', 'getvalue', [
      'return' => 'value',
      'option_group_id' => 'contribution_status',
      'name' => 'Pending',
    ]);
  }

  /**
   * Obtains value for direct debit payment instrument option value.
   *
   * @return array
   * @throws \CiviCRM_API3_Exception
   */
  private function getDirectDebitPaymentInstrumentId() {
    return civicrm_api3('OptionValue', 'getvalue', [
      'return' => 'value',
      'option_group_id' => 'payment_instrument',
      'name' => 'direct_debit',
    ]);
  }

  /**
   * Obtains value for direct debit payment instrument option value.
   *
   * @return array
   * @throws \CiviCRM_API3_Exception
   */
  private function getMemberDuesFinancialTypeId() {
    return civicrm_api3('FinancialType', 'getvalue', [
      'return' => 'id',
      'name' => 'Member Dues',
    ]);
  }

  /**
   * create tag
   *
   * @return array
   * @throws \CiviCRM_API3_Exception
   */
  private function createTag($params) {
    return civicrm_api3('Tag', 'create', [
      'name' => $params['name'],
    ]);
  }

  /**
   * Add tag to a contact
   *
   * @return array
   * @throws \CiviCRM_API3_Exception
   */
  private function addTagToContact($params) {
    return civicrm_api3('EntityTag', 'create', [
      'contact_id' => $params['contact_id'],
      'tag_id' => $params['tag_id'],
    ]);
  }

  /**
   * create group
   *
   * @return array
   * @throws \CiviCRM_API3_Exception
   */
  private function createGroup($params) {
    return civicrm_api3('Group', 'create', [
      'name' => $params['name'],
      'title' => $params['name'],
    ]);
  }

  /**
   * Add Group to a contact
   *
   * @return array
   * @throws \CiviCRM_API3_Exception
   */
  private function addContactToGroup($params) {
    return civicrm_api3('GroupContact', 'create', [
      'contact_id' => $params['contact_id'],
      'group_id' => $params['group_id'],
    ]);
  }

  /**
   * Create payment plan order
   *
   * @param array $params
   *
   * @return array
   * @throws \CiviCRM_API3_Exception
   */
  private function createPaymentPlanOrder($params) {
    $paymentPlanMembershipOrder = new CRM_MembershipExtras_Test_Entity_PaymentPlanMembershipOrder();
    $paymentPlanMembershipOrder->contactId = $params['contact_id'];
    $paymentPlanMembershipOrder->membershipStartDate = date('Y-m-01', strtotime('first day of january this year'));
    $paymentPlanMembershipOrder->paymentPlanFrequency = 'Monthly';
    $paymentPlanMembershipOrder->paymentPlanStatus = 'Pending';
    $paymentPlanMembershipOrder->paymentMethod = 'direct_debit';
    $paymentPlanMembershipOrder->lineItems[] = [
      'entity_table' => 'civicrm_membership',
      'price_field_id' => $this->testRollingMembershipTypePriceFieldValue['price_field_id'],
      'price_field_value_id' => $this->testRollingMembershipTypePriceFieldValue['id'],
      'label' => $this->testRollingMembershipType['name'],
      'qty' => 1,
      'unit_price' => $this->testRollingMembershipTypePriceFieldValue['amount'],
      'line_total' => $this->testRollingMembershipTypePriceFieldValue['amount'],
      'financial_type_id' => 'Member Dues',
      'non_deductible_amount' => 0,
    ];
    $paymentPlan = CRM_MembershipExtras_Test_Fabricator_PaymentPlanOrder::fabricate($paymentPlanMembershipOrder);
    $this->relateMandateToRecurringContributions($params);
    return $paymentPlan;
  }

  /**
   * Relates the given mandate to all the recurring contributions under the given contact
   * workaround to add mandate to contributions
   *
   * @param array $params
   */
  private function relateMandateToRecurringContributions($params) {
    $recurringContributions = civicrm_api3('ContributionRecur', 'get', [
      'return' => 'id',
      'sequential' => 1,
      'contact_id' => $params['contact_id'],
      'options' => ['limit' => 0],
    ]);

    if ($recurringContributions['count'] < 1) {
      return;
    }

    foreach ($recurringContributions['values'] as $recurringContribution) {
      $this->relateMandateToExistingContributions($recurringContribution['id'], $params['mandate_id']);
    }
  }

  /**
   * Relates the given mandate to all the contributions under the given
   * recurring contribution Id. (copied from CRM_ManualDirectDebit_Hook_PostProcess_Contribution_DirectDebitMandate)
   *
   * @param int $recurringContributionId
   * @param int $mandateId
   */
  private function relateMandateToExistingContributions($recurringContributionId, $mandateId) {
    $contributions = civicrm_api3('Contribution', 'get', [
      'sequential' => 1,
      'contribution_recur_id' => $recurringContributionId,
      'options' => ['limit' => 0],
    ]);

    if ($contributions['count'] < 1) {
      return;
    }

    foreach ($contributions['values'] as $payment) {
      $this->mandateStorage->assignContributionMandate($payment['id'], $mandateId);
    }
  }

  /**
   * Create payment plan order
   *
   * @return array
   * @throws \CiviCRM_API3_Exception
   */
  private function createContactAndMandate($params = []) {
    $contact = ContactFabricator::fabricate($params);
    $mandate = MandateFabricator::fabricate([
      'entity_id' => $contact['id'],
      'originator_number' => $this->originatorNumber,
      'dd_code' => $this->recurringContributionDDCode,
    ]);

    return [$contact, $mandate];
  }

  /**
   * Obtains search params
   *
   * @param array $params
   *
   * @return array
   */
  private function getSearchParams($params = []) {
    $offset = 0;
    $rowCount = 25;
    $defaultParams = [
      'page' => ($offset / $rowCount) + 1,
      'rp' => $rowCount,
      'context' => 'instructionBatch',
      'offset' => $offset,
      'rowCount' => $rowCount,
      'sort' => NULL,
      'total' => 0,
      'entityTable' => 'civicrm_contribution',
      'originator_number' => $this->originatorNumber,
      'dd_code' => $this->getActiveDDCodes(),
      'recur_status' => '1,2,4,5,6,7,8,9,10,11',
      'contribution_payment_instrument_id' => $this->directDebitPaymentInstrumentId,
    ];

    return array_merge($defaultParams, $params);
  }

  /**
   * Tests create recurrMandateRef entity
   *
   * I used one function to contain all the tests to finish faster by running setUp with creatimg the payment plans only once
   * instead of multiple times
   */
  public function testTransactionSearch() {
    list($this->contact1, $this->mandate1) = $this->createContactAndMandate([
      'first_name' => 'John',
      'last_name' => 'Doe',
    ]);
    list($this->contact2, $this->mandate2) = $this->createContactAndMandate([
      'first_name' => 'Bot',
      'last_name' => 'Compucorp',
    ]);
    $this->createPaymentPlanOrder(['contact_id' => $this->contact1['id'], 'mandate_id' => $this->mandate1['id']]);
    $this->createPaymentPlanOrder(['contact_id' => $this->contact2['id'], 'mandate_id' => $this->mandate2['id']]);

    $this->searchByDefault();
    $this->searchByContactTags();
    $this->searchByGroups();
    $this->searchByFinancialType();
    $this->searchByContributionAmount();
    $this->searchByReceiveDate();
    $this->searchBySortName();
  }

  /**
   * Search by default parameters
   */
  private function searchByDefault() {
    $notPresent = TRUE;
    $params = $this->getSearchParams();
    $batchTransaction = new CRM_ManualDirectDebit_Batch_Transaction($this->batch['id'], $params, [], [], $notPresent);
    $total = $batchTransaction->getTotalNumber();
    $this->assertEquals(24, $total);
  }

  /**
   * Search by contact tags
   */
  private function searchByContactTags() {
    $notPresent = TRUE;
    $params = $this->getSearchParams();

    // Search by an empty tag
    $params['contact_tags'] = $this->tag1['id'];
    $batchTransaction = new CRM_ManualDirectDebit_Batch_Transaction($this->batch['id'], $params, [], [], $notPresent);
    $total = $batchTransaction->getTotalNumber();
    $this->assertEquals(0, $total);

    // Search by one tag
    $params['contact_tags'] = $this->tag1['id'];
    $this->addTagToContact(['contact_id' => $this->contact1['id'], 'tag_id' => $this->tag1['id']]);
    $batchTransaction = new CRM_ManualDirectDebit_Batch_Transaction($this->batch['id'], $params, [], [], $notPresent);
    $total = $batchTransaction->getTotalNumber();
    $this->assertEquals(12, $total);

    // Search by two tags
    $params['contact_tags'] = implode(',', [$this->tag1['id'], $this->tag2['id']]);
    $this->addTagToContact(['contact_id' => $this->contact2['id'], 'tag_id' => $this->tag2['id']]);
    $batchTransaction = new CRM_ManualDirectDebit_Batch_Transaction($this->batch['id'], $params, [], [], $notPresent);
    $total = $batchTransaction->getTotalNumber();
    $this->assertEquals(24, $total);
  }

  /**
   * Search by groups
   */
  private function searchByGroups() {
    $notPresent = TRUE;
    $params = $this->getSearchParams();

    // Search by an empty group
    $params['group'] = $this->group1['id'];
    $batchTransaction = new CRM_ManualDirectDebit_Batch_Transaction($this->batch['id'], $params, [], [], $notPresent);
    $total = $batchTransaction->getTotalNumber();
    $this->assertEquals(0, $total);

    // Search by one group
    $this->addContactToGroup(['contact_id' => $this->contact1['id'], 'group_id' => $this->group1['id']]);
    $batchTransaction = new CRM_ManualDirectDebit_Batch_Transaction($this->batch['id'], $params, [], [], $notPresent);
    $total = $batchTransaction->getTotalNumber();
    $this->assertEquals(12, $total);

    // Search by two groups (one of them is empty)
    $params['group'] = implode(',', [$this->group1['id'], $this->group2['id']]);
    $batchTransaction = new CRM_ManualDirectDebit_Batch_Transaction($this->batch['id'], $params, [], [], $notPresent);
    $total = $batchTransaction->getTotalNumber();
    $this->assertEquals(12, $total);

    // Search by two groups
    $this->addContactToGroup(['contact_id' => $this->contact2['id'], 'group_id' => $this->group2['id']]);
    $batchTransaction = new CRM_ManualDirectDebit_Batch_Transaction($this->batch['id'], $params, [], [], $notPresent);
    $total = $batchTransaction->getTotalNumber();
    $this->assertEquals(24, $total);

    // Search by two groups with a contact belong to both of them
    $this->addContactToGroup(['contact_id' => $this->contact2['id'], 'group_id' => $this->group1['id']]);
    $batchTransaction = new CRM_ManualDirectDebit_Batch_Transaction($this->batch['id'], $params, [], [], $notPresent);
    $total = $batchTransaction->getTotalNumber();
    $this->assertEquals(24, $total);
  }

  /**
   * Search by financial type
   */
  private function searchByFinancialType() {
    $notPresent = TRUE;
    $params = $this->getSearchParams();

    // Search by financial type
    $params['financial_type_id'] = $this->memberDuesFinancialTypeId;
    $batchTransaction = new CRM_ManualDirectDebit_Batch_Transaction($this->batch['id'], $params, [], [], $notPresent);
    $total = $batchTransaction->getTotalNumber();
    $this->assertEquals(24, $total);

    // Search by a non existatant financial type
    $params['financial_type_id'] = '9999';
    $batchTransaction = new CRM_ManualDirectDebit_Batch_Transaction($this->batch['id'], $params, [], [], $notPresent);
    $total = $batchTransaction->getTotalNumber();
    $this->assertEquals(0, $total);
  }

  /**
   * Search by contribution amount
   */
  private function searchByContributionAmount() {
    $notPresent = TRUE;
    $params = $this->getSearchParams();

    // Search by contribution amount low
    $params['contribution_amount_low'] = '100';
    $batchTransaction = new CRM_ManualDirectDebit_Batch_Transaction($this->batch['id'], $params, [], [], $notPresent);
    $total = $batchTransaction->getTotalNumber();
    $this->assertEquals(24, $total);

    // Search by contribution amount low
    $params['contribution_amount_low'] = '130';
    $batchTransaction = new CRM_ManualDirectDebit_Batch_Transaction($this->batch['id'], $params, [], [], $notPresent);
    $total = $batchTransaction->getTotalNumber();
    $this->assertEquals(0, $total);

    // Search by contribution amount high
    unset($params['contribution_amount_low']);
    $params['contribution_amount_high'] = '1000';
    $batchTransaction = new CRM_ManualDirectDebit_Batch_Transaction($this->batch['id'], $params, [], [], $notPresent);
    $total = $batchTransaction->getTotalNumber();
    $this->assertEquals(24, $total);

    // Search by contribution amount high
    $params['contribution_amount_high'] = '10';
    $batchTransaction = new CRM_ManualDirectDebit_Batch_Transaction($this->batch['id'], $params, [], [], $notPresent);
    $total = $batchTransaction->getTotalNumber();
    $this->assertEquals(0, $total);

    // Search by contribution amount low and contribution amount high
    $params['contribution_amount_low'] = '10';
    $params['contribution_amount_high'] = '130';
    $batchTransaction = new CRM_ManualDirectDebit_Batch_Transaction($this->batch['id'], $params, [], [], $notPresent);
    $total = $batchTransaction->getTotalNumber();
    $this->assertEquals(24, $total);

    // Search by contribution amount low and contribution amount high
    $params['contribution_amount_low'] = '130';
    $params['contribution_amount_high'] = '160';
    $batchTransaction = new CRM_ManualDirectDebit_Batch_Transaction($this->batch['id'], $params, [], [], $notPresent);
    $total = $batchTransaction->getTotalNumber();
    $this->assertEquals(0, $total);
  }

  /**
   * Search by receive date
   */
  private function searchByReceiveDate() {
    $notPresent = TRUE;
    $params = $this->getSearchParams();

    // Search for this month records
    $params['receive_date_relative'] = 'this.month';
    $batchTransaction = new CRM_ManualDirectDebit_Batch_Transaction($this->batch['id'], $params, [], [], $notPresent);
    $total = $batchTransaction->getTotalNumber();
    $this->assertEquals(2, $total);

    // Search for this year records
    $params['receive_date_relative'] = 'this.year';
    $batchTransaction = new CRM_ManualDirectDebit_Batch_Transaction($this->batch['id'], $params, [], [], $notPresent);
    $total = $batchTransaction->getTotalNumber();
    $this->assertEquals(24, $total);
  }

  /**
   * Search by sort name
   */
  private function searchBySortName() {
    $notPresent = TRUE;
    $params = $this->getSearchParams();

    // Search by a non existatant name
    $params['sort_name'] = 'a_name_shouldn_t_exist';
    $batchTransaction = new CRM_ManualDirectDebit_Batch_Transaction($this->batch['id'], $params, [], [], $notPresent);
    $total = $batchTransaction->getTotalNumber();
    $this->assertEquals(0, $total);

    // Search by full name
    $params['sort_name'] = 'Compucorp, Bot';
    $batchTransaction = new CRM_ManualDirectDebit_Batch_Transaction($this->batch['id'], $params, [], [], $notPresent);
    $total = $batchTransaction->getTotalNumber();
    $this->assertEquals(12, $total);

    // Search by a name using wildcard implicitly
    $params['sort_name'] = 'Comp';
    $batchTransaction = new CRM_ManualDirectDebit_Batch_Transaction($this->batch['id'], $params, [], [], $notPresent);
    $total = $batchTransaction->getTotalNumber();
    $this->assertEquals(12, $total);

    // Search by a name using wildcard explicitly
    $params['sort_name'] = 'Comp%';
    $batchTransaction = new CRM_ManualDirectDebit_Batch_Transaction($this->batch['id'], $params, [], [], $notPresent);
    $total = $batchTransaction->getTotalNumber();
    $this->assertEquals(12, $total);
  }

}
