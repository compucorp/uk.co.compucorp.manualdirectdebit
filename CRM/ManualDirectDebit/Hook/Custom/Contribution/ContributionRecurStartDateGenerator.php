<?php

/**
 * Generates `Start Date` field if it wasn't filed by user
 */
class CRM_ManualDirectDebit_Hook_Custom_Contribution_ContributionRecurStartDateGenerator {

  /**
   * Current year
   *
   * @var int
   */
  private $year;

  /**
   * Current month
   *
   * @var int
   */
  private $month;

  /**
   * Current day
   *
   * @var int
   */
  private $day;

  /**
   * Cycle day
   *
   * @var int
   */
  private $cycleDay;

  /**
   * Protects setting month bigger then 12, and in that case set it to 1
   *
   * @param $month
   */
  private function setMonth($month) {
    if ($month > 12) {
      $this->month = 1;
      $this->year++;
    }
    else {
      $this->month = $month;
    }
  }

  public function __construct($cycleDay, $mandateStartDate) {
    $this->year = DateTime::createFromFormat('Y-m-d H:i:s', $mandateStartDate)->format('Y');
    $this->month = DateTime::createFromFormat('Y-m-d H:i:s', $mandateStartDate)->format('m');
    $this->day = DateTime::createFromFormat('Y-m-d H:i:s', $mandateStartDate)->format('d');

    $this->cycleDay = $cycleDay;
  }

  /**
   * Calculate start date
   *
   * @return string
   */
  public function generate() {
    if ($this->day > $this->cycleDay) {
      $this->setMonth($this->month + 1);
    }
    $this->day = $this->cycleDay;

    $this->generateClosestDate();

    $date = new DateTime();
    $date->setDate($this->year, $this->month, $this->day);

    return $date->format('Y-m-d H:i:s');
  }

  /**
   * Generates closest valid date
   *
   * @return bool
   */
  private function generateClosestDate() {
    if (checkdate($this->month, $this->day, $this->year)) {
      return TRUE;
    }
    else {
      $this->day--;
      return $this->generateClosestDate();
    }
  }

}
