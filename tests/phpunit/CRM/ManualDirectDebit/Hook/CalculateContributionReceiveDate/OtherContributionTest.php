<?php
use CRM_ManualDirectDebit_Common_SettingsManager as SettingsManager;
use CRM_MembershipExtras_Service_InstalmentReceiveDateCalculator as InstalmentReceiveDateCalculator;
use CRM_ManualDirectDebit_Hook_CalculateContributionReceiveDate_OtherContribution as OtherContributionReceiveDateCalculator;

/**
 * Class CRM_ManualDirectDebit_Hook_CalculateContributionReceiveDate_OtherContributionTest.
 *
 * @group headless
 */
class CRM_ManualDirectDebit_Hook_CalculateContributionReceiveDate_OtherContributionTest extends BaseHeadlessTest {

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

  public function testReceiveDateCalculationOfPaymentsFollowSecondInstalment() {
    $membershipStartDate = '2020-01-15';
    $firstInstalmentReceiveDate = '2020-01-15 00:00:00';
    $this->mockSettings($this->defaultDDSettings);
    $recurringContribution = $this->setupPlan($membershipStartDate, $firstInstalmentReceiveDate);

    $secondInstalmentReceiveDate = '2020-02-15 00:00:00';

    $this->defaultContributionParams['membership_id'] = $recurringContribution['membership_id'];
    $this->defaultContributionParams['contribution_recur_id'] = $recurringContribution['id'];
    $this->defaultContributionParams['previous_instalment_date'] = $secondInstalmentReceiveDate;
    $this->defaultContributionParams['membership_start_date'] = $membershipStartDate;
    $this->defaultContributionParams['frequency_interval'] = $recurringContribution['frequency_interval'];
    $this->defaultContributionParams['frequency_unit'] = $recurringContribution['frequency_unit'];

    $settingsManager = $this->buildSettingsManagerMock([]);

    $receiveDateCalculator = new OtherContributionReceiveDateCalculator(
      $receiveDate,
      $this->defaultContributionParams,
      $settingsManager
    );
    $receiveDateCalculator->process();

    $this->assertEquals('2020-03-15 00:00:00', $receiveDate);
  }

  public function  testReceiveDateOfNonDirectDebitPaymentsIsNotAlteredInHook() {
    $membershipStartDate = '2020-01-31';
    $firstInstalmentReceiveDate = '2020-01-31 00:00:00';
    $this->mockSettings($this->defaultDDSettings);
    $recurringContribution = $this->setupPlan($membershipStartDate, $firstInstalmentReceiveDate);

    $instalmentReceiveDateCalculator = new InstalmentReceiveDateCalculator($recurringContribution);
    $instalmentReceiveDateCalculator->setStartDate($membershipStartDate);

    $secondInstalmentReceiveDate = $instalmentReceiveDateCalculator->calculate(2);
    $thirdInstallmentReceiveDate = $instalmentReceiveDateCalculator->calculate(3);

    $this->defaultContributionParams['payment_instrument_id'] = 'test_payment';
    $this->defaultContributionParams['membership_id'] = $recurringContribution['membership_id'];;
    $this->defaultContributionParams['contribution_recur_id'] = $recurringContribution['id'];
    $this->defaultContributionParams['previous_instalment_date'] = $secondInstalmentReceiveDate;
    $this->defaultContributionParams['membership_start_date'] = $membershipStartDate;
    $this->defaultContributionParams['frequency_interval'] = $recurringContribution['frequency_interval'];
    $this->defaultContributionParams['frequency_unit'] = $recurringContribution['frequency_unit'];

    $settingsManager = $this->buildSettingsManagerMock([]);

    $receiveDateCalculator = new OtherContributionReceiveDateCalculator(
      $thirdInstallmentReceiveDate,
      $this->defaultContributionParams,
      $settingsManager
    );
    $receiveDateCalculator->process();

    $this->assertEquals($thirdInstallmentReceiveDate, $instalmentReceiveDateCalculator->calculate(3));
  }

}
