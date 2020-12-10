<?php
use CRM_ManualDirectDebit_Common_SettingsManager as SettingsManager;

/**
 * Class CRM_ManualDirectDebit_Hook_CalculateContributionReceiveDate_SecondContribution.
 */
class CRM_ManualDirectDebit_Hook_CalculateContributionReceiveDate_SecondContribution extends CRM_ManualDirectDebit_Hook_CalculateContributionReceiveDate_ContributionBase {

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

    $this->forceSecondInstalmentOnSecondMonth();
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
  private function forceSecondInstalmentOnSecondMonth() {
    $firstContribution = $this->getFirstContribution();
    $firstContributionReceiveDate = new DateTime($firstContribution['receive_date']);
    $membershipsStartDate = $this->getMembershipsStartDate($firstContribution);

    $interval = $membershipsStartDate->diff($firstContributionReceiveDate);
    $dias = $interval->format('%a');

    if ($dias > 30) {
      $this->receiveDate = $firstContributionReceiveDate->format('Y-m-d H:i:s');
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

}
