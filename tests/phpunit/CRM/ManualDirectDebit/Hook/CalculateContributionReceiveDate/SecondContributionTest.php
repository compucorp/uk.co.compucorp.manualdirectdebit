<?php

use CRM_ManualDirectDebit_Common_SettingsManager as SettingsManager;
use CRM_ManualDirectDebit_Hook_CalculateContributionReceiveDate_SecondContribution as SecondContributionReceiveDateCalculator;

/**
 * Class CRM_ManualDirectDebit_Hook_CalculateContributionReceiveDate_SecondContributionTest.
 *
 * @group headless
 */
class CRM_ManualDirectDebit_Hook_CalculateContributionReceiveDate_SecondContributionTest extends BaseHeadlessTest {

  use CRM_ManualDirectDebit_Test_Helper_PaymentPlanTrait;
  use CRM_ManualDirectDebit_Test_Helper_SettingsTrait;

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
    'payment_schedule' => 'monthly',
    'payment_instrument_id' => 'direct_debit',
  ];

  public function setUp() {
    $this->mockSettings($this->defaultDDSettings);
  }

  public function testSecondContributionOneMonthAfterFirstWhenSettingIsSet() {
    $membershipStartDate = '2020-01-01';
    $firstInstalmentReceiveDate = '2020-02-05 00:00:00';
    $programmedSecondInstalmentReceiveDate = $receiveDate = $this->defaultContributionParams['receive_date'] = '2020-03-05 00:00:00';

    $recurringContribution = $this->setupPlan($membershipStartDate, $firstInstalmentReceiveDate);

    $this->defaultContributionParams['membership_id'] = $recurringContribution['membership_id'];
    $this->defaultContributionParams['previous_instalment_date'] = $firstInstalmentReceiveDate;
    $this->defaultContributionParams['contribution_recur_id'] = $recurringContribution['id'];
    $this->defaultContributionParams['membership_start_date'] = $membershipStartDate;
    $this->defaultContributionParams['frequency_interval'] = $recurringContribution['frequency_interval'];
    $this->defaultContributionParams['frequency_unit'] = $recurringContribution['frequency_unit'];

    $settings = [
      'second_instalment_date_behaviour' => SettingsManager::SECOND_INSTALMENT_BEHAVIOUR_ONE_MONTH_AFTER,
    ];
    $settingsManager = $this->buildSettingsManagerMock($settings);

    $receiveDateCalculator = new SecondContributionReceiveDateCalculator(
      $receiveDate,
      $this->defaultContributionParams,
      $settingsManager
    );
    $receiveDateCalculator->process();

    $this->assertEquals($programmedSecondInstalmentReceiveDate, $receiveDate);
  }

  public function testSecondContributionIsNotBeforeFirst() {
    $membershipStartDate = '2020-10-05 00:00:00';
    $firstInstalmentReceiveDate = $membershipStartDate;

    $recurringContribution = $this->setupPlan($membershipStartDate, $firstInstalmentReceiveDate, [
      'amount' => 120,
      'frequency_unit' => 'month',
      'frequency_interval' => 1,
      'installments' => 12,
      'cycle_day' => 5,
    ]);

    $this->defaultContributionParams['membership_id'] = $recurringContribution['membership_id'];
    $this->defaultContributionParams['previous_instalment_date'] = $firstInstalmentReceiveDate;
    $this->defaultContributionParams['contribution_recur_id'] = $recurringContribution['id'];
    $this->defaultContributionParams['membership_start_date'] = $membershipStartDate;
    $this->defaultContributionParams['frequency_interval'] = $recurringContribution['frequency_interval'];
    $this->defaultContributionParams['frequency_unit'] = $recurringContribution['frequency_unit'];

    $settings = [
      'new_instruction_run_dates' => [4],
      'payment_collection_run_dates' => [5],
      'minimum_days_to_first_payment' => 3,
      'second_instalment_date_behaviour' => SettingsManager::SECOND_INSTALMENT_BEHAVIOUR_FORCE_SECOND_MONTH,
    ];
    $settingsManager = $this->buildSettingsManagerMock($settings);

    $receiveDateCalculator = new SecondContributionReceiveDateCalculator(
      $receiveDate,
      $this->defaultContributionParams,
      $settingsManager
    );
    $receiveDateCalculator->process();

    $firstInstalmentDateTime = new DateTime($firstInstalmentReceiveDate);
    $calculatedSecondInstalmentReceiveDateTime = new DateTime($receiveDate);
    $this->assertFalse(
      $firstInstalmentDateTime > $calculatedSecondInstalmentReceiveDateTime,
      "First instalment ($firstInstalmentReceiveDate) is after second instalment ($receiveDate)!"
    );
    $this->assertEquals('2020-11-05 00:00:00', $receiveDate);
  }

  public function testSecondContributionOnRenewalsIsNotChanged() {
    $membershipStartDate = '2019-09-21';
    $firstPreviousInstalmentReceiveDate = $membershipStartDate;
    $previousPeriod = $this->setupPlan($membershipStartDate, $firstPreviousInstalmentReceiveDate, [
      'amount' => 120,
      'frequency_unit' => 'month',
      'frequency_interval' => 1,
      'installments' => 12,
      'cycle_day' => 5,
    ]);

    $membershipId = $previousPeriod['membership_id'];

    $previousContributions = civicrm_api3('Contribution', 'get', [
      'sequential' => 1,
      'contribution_recur_id' => $previousPeriod['id'],
    ])['values'];

    foreach ($previousContributions as $contribution) {
      CRM_Member_BAO_MembershipPayment::create([
        'membership_id' => $membershipId,
        'contribution_id' => $contribution['id'],
      ]);
    }

    $firstRenewalInstalmentReceiveDate = '2020-08-21 00:00:00';
    $recurringContribution = $this->setupPlan($membershipStartDate, $firstRenewalInstalmentReceiveDate, [
      'amount' => 120,
      'frequency_unit' => 'month',
      'frequency_interval' => 1,
      'installments' => 12,
      'cycle_day' => 5,
    ]);

    $currentContributions = civicrm_api3('Contribution', 'get', [
      'sequential' => 1,
      'contribution_recur_id' => $recurringContribution['id'],
    ])['values'];

    foreach ($currentContributions as $contribution) {
      CRM_Member_BAO_MembershipPayment::create([
        'membership_id' => $membershipId,
        'contribution_id' => $contribution['id'],
      ]);
    }

    $settings = [
      'new_instruction_run_dates' => [4],
      'payment_collection_run_dates' => [5, 21],
      'minimum_days_to_first_payment' => 3,
      'second_instalment_date_behaviour' => SettingsManager::SECOND_INSTALMENT_BEHAVIOUR_FORCE_SECOND_MONTH,
    ];
    $settingsManager = $this->buildSettingsManagerMock($settings);

    $this->defaultContributionParams['membership_id'] = $membershipId;
    $this->defaultContributionParams['previous_instalment_date'] = $firstRenewalInstalmentReceiveDate;
    $this->defaultContributionParams['contribution_recur_id'] = $recurringContribution['id'];
    $this->defaultContributionParams['membership_start_date'] = $membershipStartDate;
    $this->defaultContributionParams['frequency_interval'] = $recurringContribution['frequency_interval'];
    $this->defaultContributionParams['frequency_unit'] = $recurringContribution['frequency_unit'];

    $preprocessReceiveDate = '2020-09-21 00:00:00';
    $receiveDate = $preprocessReceiveDate;

    $receiveDateCalculator = new SecondContributionReceiveDateCalculator(
      $receiveDate,
      $this->defaultContributionParams,
      $settingsManager
    );
    $receiveDateCalculator->process();
    $this->assertEquals($preprocessReceiveDate, $receiveDate);

    $settings['second_instalment_date_behaviour'] = SettingsManager::SECOND_INSTALMENT_BEHAVIOUR_ONE_MONTH_AFTER;
    $settingsManager = $this->buildSettingsManagerMock($settings);
    $receiveDateCalculator = new SecondContributionReceiveDateCalculator(
      $receiveDate,
      $this->defaultContributionParams,
      $settingsManager
    );
    $receiveDateCalculator->process();
    $this->assertEquals($preprocessReceiveDate, $receiveDate);
  }

}
