<?php
use CRM_ManualDirectDebit_Common_SettingsManager as SettingsManager;
use CRM_MembershipExtras_Service_InstalmentReceiveDateCalculator as ReceiveDateCalculator;

/**
 * Class OtherContribution.
 *
 * Calculates receive date for contributions beyond the second instalment.
 */
class CRM_ManualDirectDebit_Hook_CalculateContributionReceiveDate_OtherContribution extends CRM_ManualDirectDebit_Hook_CalculateContributionReceiveDate_SecondContribution {

  /**
   * Number of instalment in payment plan.
   *
   * @var int
   */
  private $contributionNumber;

  /**
   * CRM_ManualDirectDebit_Hook_CalculateContributionReceiveDate_OtherContribution constructor.
   *
   * @param int $contributionNumber
   * @param string $receiveDate
   * @param array $params
   * @param \CRM_ManualDirectDebit_Common_SettingsManager $settingsManager
   * @param \CRM_MembershipExtras_Service_InstalmentReceiveDateCalculator $calculator
   *
   * @throws \CRM_Extension_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function __construct($contributionNumber, &$receiveDate, array $params, SettingsManager $settingsManager, ReceiveDateCalculator $calculator) {
    $this->contributionNumber = $contributionNumber;

    parent::__construct($receiveDate, $params, $settingsManager, $calculator);
  }

  /**
   * @inheritDoc
   *
   * @throws \CiviCRM_API3_Exception
   * @throws \Exception
   */
  public function process() {
    $previousInstalmentDate = $this->getPreviousContributionReceiveDate();
    $receiveDate = new DateTime($previousInstalmentDate);

    $recurringContribution = $this->getRecurringContribution();
    $numberOfIntervals = $recurringContribution['frequency_interval'];
    $frequencyUnit = $recurringContribution['frequency_unit'];

    $this->receiveDate = $this->calculateNextInstalmentReceiveDate($receiveDate, $numberOfIntervals, $frequencyUnit);
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

}
