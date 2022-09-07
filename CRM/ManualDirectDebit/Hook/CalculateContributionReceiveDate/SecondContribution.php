<?php
use CRM_ManualDirectDebit_Common_SettingsManager as SettingsManager;

/**
 * Class CRM_ManualDirectDebit_Hook_CalculateContributionReceiveDate_SecondContribution.
 */
class CRM_ManualDirectDebit_Hook_CalculateContributionReceiveDate_SecondContribution extends CRM_ManualDirectDebit_Hook_CalculateContributionReceiveDate_Base {

  /**
   * Helper object used to calculate receive dates.
   *
   * @var \CRM_MembershipExtras_Service_InstalmentReceiveDateCalculator
   */
  protected $receiveDateCalculator;

  /**
   * CRM_ManualDirectDebit_Hook_CalculateContributionReceiveDate_SecondContribution constructor.
   *
   * @param string $receiveDate
   * @param array $params
   * @param \CRM_ManualDirectDebit_Common_SettingsManager $settingsManager
   *
   * @throws \CRM_Extension_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function __construct(&$receiveDate, array $params, SettingsManager $settingsManager) {

    parent::__construct($receiveDate, $params, $settingsManager);

    $this->receiveDateCalculator = new CRM_MembershipExtras_Service_InstalmentReceiveDateCalculator();
  }

  /**
   * @inheritDoc
   */
  public function process() {
    if (!$this->shouldProcess()) {
      return;
    }

    if (!$this->isForceOnSecondMonth()) {
      return;
    }

    $this->forceSecondInstalmentOnSecondPeriod();
  }

  /**
   * Determines if the payment plan is a renewal or if it's being created.
   *
   * To detect whether the payment plan is being renewed, we check if membership
   * has more than one recurring contributions attached to the membership or not
   * if the membership has more than one recurring contribution, this means that
   * the payment plan is being renewed.
   *
   * @return bool
   */
  private function isRenewal() {
    if (is_null($this->params['contribution_recur_id']) && is_null($this->params['membership_id'])) {
      return FALSE;
    }

    $query = "
        SELECT DISTINCT civicrm_contribution.contribution_recur_id
        FROM civicrm_contribution
        INNER JOIN civicrm_membership_payment
            ON civicrm_contribution.id = civicrm_membership_payment.contribution_id
        WHERE  civicrm_membership_payment.membership_id = %1
        GROUP BY civicrm_contribution.contribution_recur_id";

    $result = CRM_Core_DAO::executeQuery($query, [
      1 => [$this->params['membership_id'], 'Integer'],
    ]);

    $paymentPlanIds = [];
    while ($result->fetch()) {
      $paymentPlanIds[] = $result->contribution_recur_id;
    }

    if (count($paymentPlanIds) > 1) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Checks if setting to force second payment on second month is active.
   *
   * Checks if DD settings are configured to force second instalment to be on
   * second month of membership.
   *
   * @return bool
   */
  private function isForceOnSecondMonth() {
    if ($this->ddSettings['second_instalment_date_behaviour'] === SettingsManager::SECOND_INSTALMENT_BEHAVIOUR_FORCE_SECOND_MONTH) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Forces second instalment to have the first instalment's receive date.
   *
   * @throws \CiviCRM_API3_Exception
   * @throws \Exception
   */
  private function forceSecondInstalmentOnSecondPeriod() {
    $membershipsStartDate = new DateTime($this->params['membership_start_date']);
    $firstInstalmentDateTime = new DateTime($this->params['previous_instalment_date']);
    $cycleDay = $firstInstalmentDateTime->format('d');
    $firstMembershipCycleDate = new DateTime($membershipsStartDate->format('Y-m-' . $cycleDay));
    $secondInstalmentReceiveDate = $this->calculateNextInstalmentReceiveDate(
      $firstMembershipCycleDate,
      $this->params['frequency_interval'],
      $this->params['frequency_unit']
    );
    $this->receiveDate = $secondInstalmentReceiveDate;

    $secondInstalmentDateTime = new DateTime($secondInstalmentReceiveDate);
    if ($firstInstalmentDateTime > $secondInstalmentDateTime) {
      $this->receiveDate = $firstInstalmentDateTime->format('Y-m-d H:i:s');
    }
  }

  /**
   * Calculates the date for the next instalment, given a date and a frequency.
   *
   * @param \DateTime $referenceInstalmentDate
   * @param $numberOfIntervals
   * @param $frequencyUnit
   *
   * @return string
   * @throws \Exception
   */
  protected function calculateNextInstalmentReceiveDate(\DateTime $referenceInstalmentDate, $numberOfIntervals, $frequencyUnit) {
    switch ($frequencyUnit) {
      case 'day':
        $interval = "P{$numberOfIntervals}D";
        $referenceInstalmentDate->add(new DateInterval($interval));
        break;

      case 'week':
        $interval = "P{$numberOfIntervals}W";
        $referenceInstalmentDate->add(new DateInterval($interval));
        break;

      case 'month':
        $referenceInstalmentDate = $this->receiveDateCalculator->getSameDayNextMonth($referenceInstalmentDate, $numberOfIntervals);
        break;

      case 'year':
        $interval = "P{$numberOfIntervals}Y";
        $referenceInstalmentDate->add(new DateInterval($interval));
        break;
    }

    return $referenceInstalmentDate->format('Y-m-d H:i:s');
  }

  /**
   * Checks if we should process the hook
   *
   * Only proceses if payment schedule is MONTHLY
   */
  protected function shouldProcess() {
    if ($this->params['payment_schedule'] != CRM_MembershipExtras_Service_MembershipInstalmentsSchedule::MONTHLY) {
      return FALSE;
    }

    if (!$this->isDirectDebit() || $this->isRenewal()) {
      return FALSE;
    }

    return TRUE;
  }

}
