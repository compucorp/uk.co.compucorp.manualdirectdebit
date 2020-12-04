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
    'is_pay_later' => TRUE,
    'skipLineItem' => 1,
    'skipCleanMoney' => TRUE,
    'fee_amount' => 0,
    'payment_instrument_id' => 'direct_debit',
  ];

  public function testCalculateReceiveDateOnFirstRunDateWithMinDaysOverFirstPayDateUsesSecondPayDate() {
    $receiveDate = '2020-01-01';
    $settings = [
      'new_instruction_run_dates' => [10, 20],
      'minimum_days_to_first_payment' => 5,
      'payment_collection_run_dates' => [1, 15],
    ];
    $settingsManager = $this->createMock(SettingsManager::class);
    $settingsManager
      ->method('getManualDirectDebitSettings')
      ->willReturn(array_merge($this->defaultDDSettings, $settings));

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
    $settingsManager = $this->createMock(SettingsManager::class);
    $settingsManager
      ->method('getManualDirectDebitSettings')
      ->willReturn(array_merge($this->defaultDDSettings, $settings));

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
    $settingsManager = $this->createMock(SettingsManager::class);
    $settingsManager
      ->method('getManualDirectDebitSettings')
      ->willReturn(array_merge($this->defaultDDSettings, $settings));

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
    $settingsManager = $this->createMock(SettingsManager::class);
    $settingsManager
      ->method('getManualDirectDebitSettings')
      ->willReturn(array_merge($this->defaultDDSettings, $settings));

    $this->defaultContributionParams['payment_instrument_id'] = 'EFT';
    $receiveDateCalculator = new FirstContributionReceiveDateCalculator(
      $receiveDate,
      $this->defaultContributionParams,
      $settingsManager
    );
    $receiveDateCalculator->process();

    $this->assertEquals('2020-01-15 00:00:00', $receiveDate);
  }

}
