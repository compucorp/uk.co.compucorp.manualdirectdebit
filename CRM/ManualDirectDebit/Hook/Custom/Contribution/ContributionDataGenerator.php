<?php

/**
 * This class automatically generates all required fields for contribution
 */
class CRM_ManualDirectDebit_Hook_Custom_Contribution_ContributionDataGenerator {

  /**
   * Recurring contribution cycle day
   *
   * @var int
   */
  private $cycleDay;

  /**
   * Contribution start date
   *
   * @var object
   */
  private $start_date;

  /**
   * Next contribution date
   *
   * @var object
   */
  private $nextContributionDate;

  /**
   * Array of extension settings
   *
   * @var array
   */
  private $settings;

  /**
   * Contact entity ID
   *
   * @var int
   */
  private $entityID;

  /**
   * Mandate Start Date
   *
   * @var object
   */
  private $mandateStartDate;

  public function __construct($entityID, $settings) {
    $this->entityID = $entityID;
    $this->settings = $settings;
  }

  /**
   * Sets mandate start date
   *
   * @param $mandateStartDate
   */
  public function setMandateStartDate($mandateStartDate) {
    $this->mandateStartDate = $mandateStartDate;
  }

  /**
   * Launches generation of required contribution fields
   */
  public function generateContributionFieldsValues() {
    $this->generateCycleDate();
    $this->generateRecurringContributionStartDate();
    $this->generateNextContributionDate();
  }

  /**
   * Generates cycle date
   */
  private function generateCycleDate() {
    $mandateStartDateDayNumber = DateTime::createFromFormat('Y-m-d H:i:s', $this->mandateStartDate)->format('d');
    $closestNewInstructionRunDate = $this->findClosestDate($this->settings['new_instruction_run_dates'], $mandateStartDateDayNumber);
    $closestNewInstructionRunDateWithOffset = $closestNewInstructionRunDate + $this->settings['minimum_days_to_first_payment'];

    $this->cycleDay = $this->findClosestDate($this->settings['payment_collection_run_dates'], $closestNewInstructionRunDateWithOffset);
  }

  /**
   * Gets closest date to 'selectedDate' in array 'possibleRunDates'. If it`s
   * not exists it gets lowest value in 'possibleRunDates'
   *
   * @param $possibleRunDates
   * @param $selectedDate
   *
   * @return int|mixed
   */
  private function findClosestDate($possibleRunDates, $selectedDate) {
    $closestDay = 0;
    foreach ($possibleRunDates as $possibleDate) {
      if ($possibleDate > $selectedDate) {
        $closestDay = $possibleDate;
        break;
      }
    }

    if ($closestDay === 0) {
      $closestDay = min($possibleRunDates);
    }

    return $closestDay;
  }

  /**
   * Generates Recurring Contribution `Start date`
   */
  private function generateRecurringContributionStartDate() {
    $startDateGenerator = new CRM_ManualDirectDebit_Hook_Custom_Contribution_ContributionRecurStartDateGenerator($this->cycleDay, $this->mandateStartDate);
    $this->start_date = $startDateGenerator->generate();
  }

  private function generateNextContributionDate() {
    $contributionRecur = civicrm_api3('ContributionRecur', 'get', [
      'sequential' => 1,
      'return' => ['id', 'start_date', 'frequency_interval', 'frequency_unit'],
      'contact_id' => $this->entityID,
      'options' => ['limit' => 1, 'sort' => 'contribution_recur_id DESC'],
    ])['values'][0];

    $receiveDateCalculator = new CRM_MembershipExtras_Service_InstallmentReceiveDateCalculator($contributionRecur);
    $nextContributionIndex = 2;
    $this->nextContributionDate = $receiveDateCalculator->calculate($nextContributionIndex);
  }

  /**
   * Saves all generated values
   */
  public function saveGeneratedContributionValues() {
    civicrm_api3('ContributionRecur', 'get', [
      'return' => "id",
      'contact_id' => $this->entityID,
      'options' => ['limit' => 1, 'sort' => "contribution_recur_id DESC"],
      'api.ContributionRecur.create' => [
        'id' => '$value.id',
        'cycle_day' => $this->cycleDay,
        'start_date' => $this->start_date,
        'next_sched_contribution_date' => $this->nextContributionDate,
      ],
    ]);

    civicrm_api3('Contribution', 'get', [
      'return' => "id",
      'contact_id' => $this->entityID,
      'options' => ['limit' => 1, 'sort' => "contribution_id DESC"],
      'api.Contribution.create' => [
        'id' => '$value.id',
        'receive_date' => $this->start_date,
      ],
    ]);
  }

}
