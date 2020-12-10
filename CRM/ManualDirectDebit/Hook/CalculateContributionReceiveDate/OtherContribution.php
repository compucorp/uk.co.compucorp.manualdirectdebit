<?php
use CRM_ManualDirectDebit_Common_SettingsManager as SettingsManager;
use CRM_MembershipExtras_Service_InstallmentReceiveDateCalculator as ReceiveDateCalculator;

/**
 * Class OtherContribution.
 *
 * Calculates receive date for contributions beyond the second instalment.
 */
class CRM_ManualDirectDebit_Hook_CalculateContributionReceiveDate_OtherContribution extends CRM_ManualDirectDebit_Hook_CalculateContributionReceiveDate_ContributionBase {

  /**
   * Number of instalment in payment plan.
   *
   * @var int
   */
  private $contributionNumber;

  /**
   * Helper object used to calculate receive dates.
   *
   * @var \CRM_MembershipExtras_Service_InstallmentReceiveDateCalculator
   */
  private $receiveDateCalculator;

  /**
   * CRM_ManualDirectDebit_Hook_CalculateContributionReceiveDate_OtherContribution constructor.
   *
   * @param int $contributionNumber
   * @param string $receiveDate
   * @param array $params
   * @param \CRM_ManualDirectDebit_Common_SettingsManager $settingsManager
   * @param \CRM_MembershipExtras_Service_InstallmentReceiveDateCalculator $calculator
   *
   * @throws \CRM_Extension_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function __construct($contributionNumber, &$receiveDate, array $params, SettingsManager $settingsManager, ReceiveDateCalculator $calculator) {
    $this->contributionNumber = $contributionNumber;
    $this->receiveDateCalculator = $calculator;

    parent::__construct($receiveDate, $params, $settingsManager);
  }

  /**
   * @inheritDoc
   *
   * @throws \CiviCRM_API3_Exception
   */
  public function process() {
    $previousInstalmentDate = $this->getPreviousContributionReceiveDate();
    $receiveDate = new DateTime($previousInstalmentDate);

    $recurringContribution = $this->getRecurringContribution();
    $numberOfIntervals = $recurringContribution['frequency_interval'];
    $frequencyUnit = $recurringContribution['frequency_unit'];

    switch ($frequencyUnit) {
      case 'day':
        $interval = "P{$numberOfIntervals}D";
        $receiveDate->add(new DateInterval($interval));
        break;

      case 'week':
        $interval = "P{$numberOfIntervals}W";
        $receiveDate->add(new DateInterval($interval));
        break;

      case 'month':
        $receiveDate = $this->receiveDateCalculator->getSameDayNextMonth($receiveDate, $numberOfIntervals);
        break;

      case 'year':
        $interval = "P{$numberOfIntervals}Y";
        $receiveDate->add(new DateInterval($interval));
        break;
    }

    $this->receiveDate = $receiveDate->format('Y-m-d H:i:s');
  }

  /**
   * Obtains the receive date of the last contribution in the payment plan.
   *
   * @return mixed|string
   * @throws \CiviCRM_API3_Exception
   */
  private function getPreviousContributionReceiveDate() {
    $result = civicrm_api3('Contribution', 'get', [
      'sequential' => 1,
      'contribution_recur_id' => $this->params['contribution_recur_id'],
      'options' => [
        'limit' => 0,
        'sort' => 'id ASC',
      ],
    ]);

    return $result['values'][$this->contributionNumber - 2]['receive_date'];
  }

  /**
   * Obtains recurrring contribution used for the payment plan.
   *
   * @return array
   * @throws \CiviCRM_API3_Exception
   */
  private function getRecurringContribution() {
    $result = civicrm_api3('ContributionRecur', 'get', [
      'sequential' => 1,
      'id' => $this->params['contribution_recur_id'],
      'options' => ['limit' => 0],
    ]);

    if ($result['count'] > 0) {
      return $result['values'][0];
    }

    return [];
  }

}
