<?php

/**
 * Class CRM_ManualDirectDebit_Hook_CalculateContributionReceiveDate_FirstContribution.
 *
 * Implements hook to calculate the receive date of the first contribution of a
 * payment plan.
 */
class CRM_ManualDirectDebit_Hook_CalculateContributionReceiveDate_FirstContribution extends CRM_ManualDirectDebit_Hook_CalculateContributionReceiveDate_Base {

  /**
   * Calculates receive date for payment plan if payment method is DD.
   *
   * @throws \Exception
   */
  public function process() {
    if (!$this->isDirectDebit()) {
      return;
    }

    $receiveDateTime = new DateTime($this->receiveDate);
    $nextInstructionRunDate = $this->getNextValidDateAfter($receiveDateTime, $this->ddSettings['new_instruction_run_dates']);

    $minimumDaysToFirstPayment = $this->ddSettings['minimum_days_to_first_payment'];
    if ($minimumDaysToFirstPayment) {
      $nextInstructionRunDate->add(new DateInterval("P{$minimumDaysToFirstPayment}D"));
    }

    $nextPaymentCollectionDate = $this->getNextValidDateAfter($nextInstructionRunDate, $this->ddSettings['payment_collection_run_dates']);
    $this->receiveDate = $nextPaymentCollectionDate->format('Y-m-d H:i:s');
  }

  /**
   * Returns first date in collection of days that is after given dates.
   *
   * @param \DateTime $referenceDate
   * @param array $validDaysArray
   *
   * @return \Date|\DateTime
   */
  private function getNextValidDateAfter(\DateTime $referenceDate, array $validDaysArray) {
    $referenceYear = intval($referenceDate->format('Y'));

    for ($year = $referenceYear; $year < $referenceYear + 2; $year++) {
      for ($month = 1; $month < 13; $month++) {
        foreach ($validDaysArray as $paymentCollectionDay) {
          $paymentCollectionDay = ($paymentCollectionDay < 10 ? '0' . $paymentCollectionDay : $paymentCollectionDay);
          $paymentCollectionMonth = ($month < 10 ? '0' . $month : $month);
          $nextAvailableDate = new DateTime("{$year}-{$paymentCollectionMonth}-{$paymentCollectionDay}");

          if ($nextAvailableDate > $referenceDate) {
            return $nextAvailableDate;
          }
        }
      }
    }

    return $referenceDate;
  }

}
