<?php
use CRM_ManualDirectDebit_Common_SettingsManager as SettingsManager;

/**
 * Class OtherContribution.
 *
 * Calculates receive date for contributions beyond the second instalment.
 */
class CRM_ManualDirectDebit_Hook_CalculateContributionReceiveDate_OtherContribution extends CRM_ManualDirectDebit_Hook_CalculateContributionReceiveDate_SecondContribution {

  /**
   * CRM_ManualDirectDebit_Hook_CalculateContributionReceiveDate_OtherContribution constructor.
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
  }

  /**
   * @inheritDoc
   *
   * @throws \CiviCRM_API3_Exception
   * @throws \Exception
   */
  public function process() {
    if (!$this->shouldProcess()) {
      return;
    }
    $receiveDate = new DateTime($this->params['previous_instalment_date']);

    $numberOfIntervals = $this->params['frequency_interval'];
    $frequencyUnit = $this->params['frequency_unit'];

    $this->receiveDate = $this->calculateNextInstalmentReceiveDate($receiveDate, $numberOfIntervals, $frequencyUnit);
  }

}
