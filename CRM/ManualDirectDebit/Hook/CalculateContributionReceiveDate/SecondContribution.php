<?php
use CRM_ManualDirectDebit_Common_SettingsManager as SettingsManager;
use CRM_MembershipExtras_Service_InstallmentReceiveDateCalculator as ReceiveDateCalculator;

/**
 * Class CRM_ManualDirectDebit_Hook_CalculateContributionReceiveDate_SecondContribution.
 */
class CRM_ManualDirectDebit_Hook_CalculateContributionReceiveDate_SecondContribution extends CRM_ManualDirectDebit_Hook_CalculateContributionReceiveDate_Base {

  /**
   * Helper object used to calculate receive dates.
   *
   * @var \CRM_MembershipExtras_Service_InstallmentReceiveDateCalculator
   */
  protected $receiveDateCalculator;

  /**
   * CRM_ManualDirectDebit_Hook_CalculateContributionReceiveDate_SecondContribution constructor.
   *
   * @param string $receiveDate
   * @param array $params
   * @param \CRM_ManualDirectDebit_Common_SettingsManager $settingsManager
   * @param \CRM_MembershipExtras_Service_InstallmentReceiveDateCalculator $calculator
   *
   * @throws \CRM_Extension_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function __construct(&$receiveDate, array $params, SettingsManager $settingsManager, ReceiveDateCalculator $calculator) {
    parent::__construct($receiveDate, $params, $settingsManager);

    $this->receiveDateCalculator = $calculator;
  }

  /**
   * @inheritDoc
   */
  public function process() {
    if (!$this->isDirectDebit()) {
      return;
    }

    if (!$this->isForceOnSecondMonth()) {
      return;
    }

    $this->forceSecondInstalmentOnSecondPeriod();
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
    $recurringContribution = $this->getRecurringContribution();
    $firstContribution = $this->getFirstContribution();
    $membershipsStartDate = $this->getMembershipsStartDate($firstContribution);

    $firstMembershipCycleDate = new DateTime(
      $membershipsStartDate->format('Y-m-' . $recurringContribution['cycle_day'])
    );

    $secondInstalmentReceiveDate = $this->calculateNextInstalmentReceiveDate(
      $firstMembershipCycleDate,
      $recurringContribution['frequency_interval'],
      $recurringContribution['frequency_unit']
    );
    $this->receiveDate = $secondInstalmentReceiveDate;

    $firstInstalmentDateTime = new DateTime($firstContribution['receive_date']);
    $secondInstalmentDateTime = new DateTime($secondInstalmentReceiveDate);
    if ($firstInstalmentDateTime > $secondInstalmentDateTime) {
      $this->receiveDate = $firstInstalmentDateTime->format('Y-m-d H:i:s');
    }
  }

  /**
   * Obteins the first contribution in the payment plan.
   *
   * @return array
   * @throws \CiviCRM_API3_Exception
   */
  private function getFirstContribution() {
    $result = civicrm_api3('Contribution', 'get', [
      'sequential' => 1,
      'contribution_recur_id' => $this->params['contribution_recur_id'],
      'options' => [
        'limit' => 0,
        'sort' => 'id',
      ],
    ]);

    if ($result['count'] > 0) {
      return array_shift($result['values']);
    }

    return [];
  }

  /**
   * Obtains a membership's start date from those related to the payment plan.
   *
   * @param array $firstContribution
   *
   * @return \DateTime|null
   * @throws \CiviCRM_API3_Exception
   */
  private function getMembershipsStartDate($firstContribution) {
    $lineItems = $this->getContributionLineItems($firstContribution);

    foreach ($lineItems as $line) {
      if ($line['entity_table'] != 'civicrm_membership') {
        continue;
      }

      $membership = $this->getMembership($line['entity_id']);

      return new DateTime($membership['start_date']);
    }

    return NULL;
  }

  /**
   * Obtains the list of line items for the given contribution.
   *
   * @param $contribution
   *
   * @return array|mixed
   * @throws \CiviCRM_API3_Exception
   */
  private function getContributionLineItems($contribution) {
    $result = civicrm_api3('LineItem', 'get', [
      'sequential' => 1,
      'contribution_id' => $contribution['id'],
      'options' => ['limit' => 0],
    ]);

    if ($result['count'] > 0) {
      return $result['values'];
    }

    return [];
  }

  /**
   * Obtains the given membership's data.
   *
   * @param int $membershipID
   *
   * @return array
   * @throws \CiviCRM_API3_Exception
   */
  private function getMembership($membershipID) {
    $result = civicrm_api3('Membership', 'get', [
      'sequential' => 1,
      'id' => $membershipID,
      'options' => ['limit' => 0],
    ]);

    if ($result['count'] > 0) {
      return array_shift($result['values']);
    }

    return [];
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

}
