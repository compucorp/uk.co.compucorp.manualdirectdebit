<?php

/**
 * Generates `Start Date` field if it wasn't filed by user
 */
class CRM_ManualDirectDebit_Hook_Custom_MandateStartDateGenerator {

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
   * Collection day
   *
   * @var int
   */
  private $collectionDay;

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

  public function __construct($collectionDay) {
    $this->year = (new DateTime())->format('Y');
    $this->month = (new DateTime())->format('m');
    $this->day = (new DateTime())->format('d');

    $this->collectionDay = $collectionDay;
  }

  /**
   * Calculate start date
   *
   * @return string
   */
  public function generate() {
    if ($this->day > $this->collectionDay) {
      $this->setMonth($this->month + 1);
    }
    $this->day = $this->collectionDay;

    $this->generateClosestAppropriateDate();

    $date = new DateTime();
    $date->setDate($this->year, $this->month, $this->day);

    return $date->format('Y-m-d H:i:s');
  }

  /**
   * Generates closest appropriate valid date
   *
   * @return bool
   */
  private function generateClosestAppropriateDate() {
    if (checkdate($this->month, $this->day, $this->year)) {
      return TRUE;
    }
    else {
      $this->day--;
      return $this->generateClosestAppropriateDate();
    }
  }

}
