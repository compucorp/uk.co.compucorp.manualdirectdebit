<?php

use CRM_ManualDirectDebit_Common_SettingsManager as SettingsManager;
use CRM_ManualDirectDebit_Hook_CalculateContributionReceiveDate_FirstContribution as FirstContributionReceiveDateCalculator;

/**
 * Class CRM_ManualDirectDebit_Hook_CalculateContributionReceiveDate_FirstContributionTest
 *
 * @group headless
 */
class CRM_ManualDirectDebit_Hook_CalculateContributionReceiveDate_FirstContributionTest extends BaseHeadlessTest {

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
  ];

  /**
   * Default parameters used to create the contribution of a payment plan.
   *
   * @var array
   */
  private $defaultContributionParams = [
    'payment_instrument_id' => 'direct_debit',
  ];

  /**
   * Sets up a mock settings manager.
   *
   * Builds a mock settings manager object, making it return the given settings
   * array merged with the default settings, when getManualDirectDebitSettings
   * method is called.
   *
   * @param array $settings
   *
   * @return \CRM_ManualDirectDebit_Common_SettingsManager
   */
  private function setUpMockSettingsManager(array $settings) {
    $settingsManager = $this->createMock(SettingsManager::class);
    $settingsManager
      ->method('getManualDirectDebitSettings')
      ->willReturn(array_merge($this->defaultDDSettings, $settings));

    return $settingsManager;
  }

  public function testCalculateReceiveDateOnFirstRunDateWithMinDaysOverFirstPayDateUsesSecondPayDate() {
    $receiveDate = '2020-01-01';
    $settings = [
      'new_instruction_run_dates' => [10, 20],
      'minimum_days_to_first_payment' => 4,
      'payment_collection_run_dates' => [1, 15],
    ];
    $settingsManager = $this->setUpMockSettingsManager($settings);

    $receiveDateCalculator = new FirstContributionReceiveDateCalculator(
      $receiveDate,
      $this->defaultContributionParams,
      $settingsManager
    );
    $receiveDateCalculator->process();

    $this->assertEquals('2020-01-15 00:00:00', $receiveDate);
  }

  public function testCalculateReceiveDateOnSecondRunDateWithMinDaysOverSecondPayDatePushesForNextMonth() {
    $receiveDate = '2020-01-15';
    $settings = [
      'new_instruction_run_dates' => [10, 20],
      'minimum_days_to_first_payment' => 5,
      'payment_collection_run_dates' => [1, 15],
    ];
    $settingsManager = $this->setUpMockSettingsManager($settings);

    $receiveDateCalculator = new FirstContributionReceiveDateCalculator(
      $receiveDate,
      $this->defaultContributionParams,
      $settingsManager
    );
    $receiveDateCalculator->process();

    $this->assertEquals('2020-02-01 00:00:00', $receiveDate);
  }

  public function testCalculateReceiveDateOnSecondRunDateAtEndOfYearIsPushedForNextYear() {
    $receiveDate = '2020-12-15';
    $settings = [
      'new_instruction_run_dates' => [10, 20],
      'minimum_days_to_first_payment' => 5,
      'payment_collection_run_dates' => [1, 15],
    ];
    $settingsManager = $this->setUpMockSettingsManager($settings);

    $receiveDateCalculator = new FirstContributionReceiveDateCalculator(
      $receiveDate,
      $this->defaultContributionParams,
      $settingsManager
    );
    $receiveDateCalculator->process();

    $this->assertEquals('2021-01-01 00:00:00', $receiveDate);
  }

  public function testPaymentPlansNotPaidWithDirectDebitAreNotChanged() {
    $receiveDate = '2020-01-15 00:00:00';
    $settings = [
      'new_instruction_run_dates' => [10, 20],
      'minimum_days_to_first_payment' => 5,
      'payment_collection_run_dates' => [1, 15],
    ];
    $settingsManager = $this->setUpMockSettingsManager($settings);

    $this->defaultContributionParams['payment_instrument_id'] = 'EFT';
    $receiveDateCalculator = new FirstContributionReceiveDateCalculator(
      $receiveDate,
      $this->defaultContributionParams,
      $settingsManager
    );
    $receiveDateCalculator->process();

    $this->assertEquals('2020-01-15 00:00:00', $receiveDate);
  }

  public function testReceiveDateCalculationScenarios() {
    $scenarios = [
      'IO131TestScenario' => ['2020-12-18 00:00:00', 3, [4], [5, 21], '2021-01-21 00:00:00'],
      'StartDateBeforeNIRD' => ['2020-08-02 00:00:00', 3, [4], [5, 21], '2020-08-21 00:00:00'],
      'SignUpAfterNIRD' => ['2020-08-04 00:00:00', 3, [4], [5, 21], '2020-09-21 00:00:00'],
      'OnPaymentRunDate' => ['2020-08-04 00:00:00', 3, [4], [5, 21], '2020-09-21 00:00:00'],
      'Scenario5' => ['2020-09-09 00:00:00', 10, [5, 10], [5, 25], '2020-09-25 00:00:00'],
      'Scenario6' => ['2020-09-11 00:00:00', 15, [5], [25], '2020-10-25 00:00:00'],
      'Sheela#1' => ['2020-09-10 00:00:00', 10, [5, 10], [5, 25], '2020-10-25 00:00:00'],
      'Sheela#2' => ['2020-08-01 00:00:00', 3, [4], [5, 21], '2020-08-21 00:00:00'],
      'Sheela#3' => ['2020-08-01 00:00:00', 3, [4], [7, 21], '2020-08-21 00:00:00'],
      'Sheela#4' => ['2020-08-05 00:00:00', 3, [4, 18], [7, 21], '2020-09-07 00:00:00'],
      'Sheela#5' => ['2020-08-20 00:00:00', 3, [4, 18], [5, 21], '2020-09-21 00:00:00'],
      'Sheela#6' => ['2020-02-04 00:00:00', 3, [4], [5, 21], '2020-03-21 00:00:00'],
    ];

    foreach ($scenarios as $scenarioName => $testData) {
      $receiveDate = $testData[0];
      $settings = [
        'minimum_days_to_first_payment' => $testData[1],
        'new_instruction_run_dates' => $testData[2],
        'payment_collection_run_dates' => $testData[3],
      ];
      $expectedReceiveDateForFirstContribution = $testData[4];

      $settingsManager = $this->setUpMockSettingsManager($settings);
      $receiveDateCalculator = new FirstContributionReceiveDateCalculator(
        $receiveDate,
        $this->defaultContributionParams,
        $settingsManager
      );
      $receiveDateCalculator->process();

      $this->assertEquals(
        $expectedReceiveDateForFirstContribution,
        $receiveDate,
        "Scenario $scenarioName failed!"
      );
    }
  }

}
