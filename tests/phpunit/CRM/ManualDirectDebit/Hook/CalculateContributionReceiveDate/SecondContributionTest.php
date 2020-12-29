<?php
use CRM_ManualDirectDebit_Test_Fabricator_Contact as ContactFabricator;
use CRM_ManualDirectDebit_Test_Fabricator_RecurringContribution as RecurringContributionFabricator;
use CRM_MembershipExtras_Test_Fabricator_MembershipType as MembershipTypeFabricator;
use CRM_MembershipExtras_Test_Fabricator_Membership as MembershipFabricator;
use CRM_ManualDirectDebit_Test_Fabricator_Contribution as ContributionFabricator;
use CRM_MembershipExtras_Test_Fabricator_LineItem as LineItemFabricator;
use CRM_ManualDirectDebit_Common_SettingsManager as SettingsManager;
use CRM_MembershipExtras_Service_InstallmentReceiveDateCalculator as ReceiveDateCalculator;
use CRM_ManualDirectDebit_Hook_CalculateContributionReceiveDate_SecondContribution as SecondContributionReceiveDateCalculator;

/**
 * Class CRM_ManualDirectDebit_Hook_CalculateContributionReceiveDate_SecondContributionTest.
 *
 * @group headless
 */
class CRM_ManualDirectDebit_Hook_CalculateContributionReceiveDate_SecondContributionTest extends BaseHeadlessTest {
  /**
   * Default direct debit settings that will be used for tests.
   *
   * @var array
   */
  private $defaultDDSettings = [
    'default_reference_prefix' => 'PRE-',
    'minimum_reference_prefix_length' => 4,
    'new_instruction_run_dates' => [1],
    'payment_collection_run_dates' => [5],
    'minimum_days_to_first_payment' => 1,
    'second_instalment_date_behaviour' => SettingsManager::SECOND_INSTALMENT_BEHAVIOUR_ONE_MONTH_AFTER,
  ];

  /**
   * Default parameters used to create the contribution of a payment plan.
   *
   * @var array
   */
  private $defaultContributionParams = [
    'is_pay_later' => TRUE,
    'skipLineItem' => 1,
    'skipCleanMoney' => TRUE,
    'fee_amount' => 0,
    'payment_instrument_id' => 'direct_debit',
  ];

  /**
   * Helper function to create memberships and its default price field value.
   *
   * @param array $params
   *
   * @return \stdClass
   * @throws \CiviCRM_API3_Exception
   */
  private function createMembershipType($params) {
    $membershipType = MembershipTypeFabricator::fabricate($params);
    $priceFieldValue = civicrm_api3('PriceFieldValue', 'get', [
      'sequential' => 1,
      'membership_type_id' => $membershipType['id'],
      'options' => ['limit' => 1],
    ])['values'][0];

    $result = new stdClass();
    $result->membershipType = $membershipType;
    $result->priceFieldValue = $priceFieldValue;

    return $result;
  }

  /**
   * Builds a mock class to manage DD settings.
   *
   * @return mixed
   */
  private function buildSettingsManagerMock($settings) {
    $settingsManager = $this->createMock(SettingsManager::class);
    $settingsManager
      ->method('getManualDirectDebitSettings')
      ->willReturn(array_merge($this->defaultDDSettings, $settings));

    return $settingsManager;
  }

  /**
   * Builds mock receive date calculator object.
   *
   * @param string $dayNextMonth
   *
   * @return \CRM_MembershipExtras_Service_InstallmentReceiveDateCalculator
   * @throws \Exception
   */
  private function buildReceiveDateCalculatorMock($dayNextMonth) {
    $calculator = $this->createMock(ReceiveDateCalculator::class);
    $calculator
      ->method('getSameDayNextMonth')
      ->willReturn(new DateTime($dayNextMonth));

    return $calculator;
  }

  public function testForceSecondContributionOnSecondMonthWhenStartDateToFirstPaymentIsMoreThan30Days() {
    $membershipStartDate = '2020-01-01';
    $firstInstalmentReceiveDate = '2020-02-05 00:00:00';
    $receiveDate = $this->defaultContributionParams['receive_date'] = '2020-03-05';

    $recurringContribution = $this->setupPlan($membershipStartDate, $firstInstalmentReceiveDate, [
      'amount' => 1200,
      'frequency_unit' => 'month',
      'frequency_interval' => 1,
      'installments' => 12,
      'cycle_day' => 5,
    ]);
    $this->defaultContributionParams['contribution_recur_id'] = $recurringContribution['id'];

    $settings = [
      'second_instalment_date_behaviour' => SettingsManager::SECOND_INSTALMENT_BEHAVIOUR_FORCE_SECOND_MONTH,
    ];
    $settingsManager = $this->buildSettingsManagerMock($settings);
    $receiveDateCalculatorHelper = $this->buildReceiveDateCalculatorMock('2020-02-05');

    $receiveDateCalculator = new SecondContributionReceiveDateCalculator(
      $receiveDate,
      $this->defaultContributionParams,
      $settingsManager,
      $receiveDateCalculatorHelper
    );
    $receiveDateCalculator->process();

    $this->assertEquals($firstInstalmentReceiveDate, $receiveDate);
  }

  public function testSecondContributionOneMonthAfterFirstWhenStartDateToFirstPaymentIsLessThan30Days() {
    $membershipStartDate = '2020-01-01';
    $firstInstalmentReceiveDate = '2020-01-15 00:00:00';
    $programmedSecondInstalmentReceiveDate = $receiveDate = $this->defaultContributionParams['receive_date'] = '2020-02-15 00:00:00';

    $recurringContribution = $this->setupPlan($membershipStartDate, $firstInstalmentReceiveDate, [
      'amount' => 120,
      'frequency_unit' => 'month',
      'frequency_interval' => 1,
      'installments' => 12,
      'cycle_day' => 5,
    ]);
    $this->defaultContributionParams['contribution_recur_id'] = $recurringContribution['id'];

    $settings = [
      'second_instalment_date_behaviour' => SettingsManager::SECOND_INSTALMENT_BEHAVIOUR_FORCE_SECOND_MONTH,
    ];
    $settingsManager = $this->buildSettingsManagerMock($settings);
    $receiveDateCalculatorHelper = $this->buildReceiveDateCalculatorMock('2020-02-15');

    $receiveDateCalculator = new SecondContributionReceiveDateCalculator(
      $receiveDate,
      $this->defaultContributionParams,
      $settingsManager,
      $receiveDateCalculatorHelper
    );
    $receiveDateCalculator->process();

    $this->assertEquals($programmedSecondInstalmentReceiveDate, $receiveDate);
  }

  public function testSecondContributionOneMonthAfterFirstWhenSettingIsSet() {
    $membershipStartDate = '2020-01-01';
    $firstInstalmentReceiveDate = '2020-02-05 00:00:00';
    $programmedSecondInstalmentReceiveDate = $receiveDate = $this->defaultContributionParams['receive_date'] = '2020-03-05 00:00:00';

    $recurringContribution = $this->setupPlan($membershipStartDate, $firstInstalmentReceiveDate, [
      'amount' => 1200,
      'frequency_unit' => 'month',
      'frequency_interval' => 1,
      'installments' => 12,
      'cycle_day' => 5,
    ]);
    $this->defaultContributionParams['contribution_recur_id'] = $recurringContribution['id'];

    $settings = [
      'second_instalment_date_behaviour' => SettingsManager::SECOND_INSTALMENT_BEHAVIOUR_ONE_MONTH_AFTER,
    ];
    $settingsManager = $this->buildSettingsManagerMock($settings);
    $receiveDateCalculatorHelper = $this->buildReceiveDateCalculatorMock('2020-03-05');

    $receiveDateCalculator = new SecondContributionReceiveDateCalculator(
      $receiveDate,
      $this->defaultContributionParams,
      $settingsManager,
      $receiveDateCalculatorHelper
    );
    $receiveDateCalculator->process();

    $this->assertEquals($programmedSecondInstalmentReceiveDate, $receiveDate);
  }

  /**
   * Configures a payment plan to be used on tests.
   *
   * @param string $membershipStartDate
   * @param string $firstInstalmentReceiveDate
   * @param array $params
   *
   * @return mixed
   * @throws \CiviCRM_API3_Exception
   */
  private function setupPlan($membershipStartDate, $firstInstalmentReceiveDate, array $params) {
    $mainMembershipType = $this->createMembershipType([
      'name' => 'Main Rolling Membership',
      'period_type' => 'rolling',
      'minimum_fee' => $params['amount'],
      'duration_interval' => $params['installments'],
      'duration_unit' => 'month',
    ]);

    $contact = ContactFabricator::fabricate();
    $recurringContribution = RecurringContributionFabricator::fabricate([
      'contact_id' => $contact['id'],
      'amount' => $params['amount'],
      'currency' => NULL,
      'frequency_unit' => $params['frequency_unit'],
      'frequency_interval' => $params['frequency_interval'],
      'installments' => $params['installments'],
      'start_date' => $firstInstalmentReceiveDate,
      'contribution_status_id' => 'Pending',
      'is_test' => 0,
      'cycle_day' => $params['cycle_day'],
      'payment_processor_id' => 'Offline Recurring Contribution',
      'financial_type_id' => 'Member Dues',
      'payment_instrument_id' => 'direct_debit',
      'campaign_id' => NULL,
    ]);
    $contribution = ContributionFabricator::fabricate([
      'currency' => NULL,
      'source' => NULL,
      'contact_id' => $contact['id'],
      'fee_amount' => 0,
      'net_amount' => $params['amount'] / $params['installments'],
      'total_amount' => $params['amount'] / $params['installments'],
      'receive_date' => $firstInstalmentReceiveDate,
      'payment_instrument_id' => 'direct_debit',
      'financial_type_id' => 'Member Dues',
      'is_test' => 0,
      'contribution_status_id' => 'Pending',
      'is_pay_later' => TRUE,
      'skipLineItem' => 1,
      'skipCleanMoney' => TRUE,
      'contribution_recur_id' => $recurringContribution['id'],
    ]);
    $membership = MembershipFabricator::fabricate([
      'contact_id' => $contact['id'],
      'membership_type_id' => $mainMembershipType->membershipType['id'],
      'join_date' => $membershipStartDate,
      'start_date' => $membershipStartDate,
      'end_date' => NULL,
      'contribution_recur_id' => $recurringContribution['id'],
      'financial_type_id' => 'Member Dues',
      'skipLineItem' => 1,
    ]);
    LineItemFabricator::fabricate([
      'entity_table' => 'civicrm_membership',
      'entity_id' => $membership['id'],
      'contribution_id' => $contribution['id'],
      'price_field_id' => $mainMembershipType->priceFieldValue['price_field_id'],
      'price_field_value_id' => $mainMembershipType->priceFieldValue['id'],
      'label' => $mainMembershipType->membershipType['name'],
      'qty' => 1,
      'unit_price' => $contribution['total_amount'],
      'line_total' => $contribution['total_amount'],
      'financial_type_id' => 'Member Dues',
      'non_deductible_amount' => 0,
      'auto_renew' => 0,
    ]);

    return $recurringContribution;
  }

}
