<?php

/**
 * Collect data for message template
 */
abstract class CRM_ManualDirectDebit_Mail_DataCollector_Base {

  /**
   * Contribution id
   *
   * @var int
   */
  protected $contributionId;

  /**
   * Membership id
   *
   * @var int
   */
  protected $membershipId;

  /**
   * Template params
   *
   * @var array
   */
  private $tplParams = [];

  /**
   * Email address
   *
   * @var int
   */
  private $email = FALSE;

  /**
   * Recurring contribution id
   *
   * @var int
   */
  protected $recurringContributionId;

  /**
   * Retrieves tpl params for template
   *
   * @return array
   */
  public function retrieve() {
    $this->setContributionId();
    $this->setEmail();
    $this->setRecurringContributionId();
    $this->setMembershipId();

    $this->collectRecurringContributionData();
    $this->collectMandateData();
    $this->collectMembershipData();
    $this->collectImageSrc();

    return $this->tplParams;
  }

  /**
   * Retrieves email
   *
   * @return int
   */
  public function retrieveEmail() {
    return $this->email;
  }

  /**
   * Sets contribution id
   */
  abstract protected function setContributionId();

  /**
   * Sets email by contribution id
   */
  private function setEmail() {
    $query = "
      SELECT
        (
          SELECT email.email
          FROM civicrm_email AS email
          WHERE email.contact_id = contribution.contact_id
          LIMIT 1
        ) AS email
      FROM civicrm_contribution AS contribution
      WHERE contribution.id = %1
      LIMIT 1
    ";

    $dao = CRM_Core_DAO::executeQuery($query, [
      1 => [$this->contributionId, 'Integer']
    ]);

    while ($dao->fetch()) {
      $this->email = $dao->email;
    }
  }

  /**
   * Sets recurring contribution id
   */
  private function setRecurringContributionId() {
    $query = "
      SELECT 
        contribution.contribution_recur_id AS contribution_recur_id
      FROM civicrm_contribution AS contribution
      WHERE contribution.id = %1
    ";

    $dao = CRM_Core_DAO::executeQuery($query, [
      1 => [$this->contributionId, 'Integer']
    ]);

    while ($dao->fetch()) {
      $this->recurringContributionId = $dao->contribution_recur_id;
      return;
    }

    $this->recurringContributionId = FALSE;
  }

  /**
   * Sets membership id
   */
  private function setMembershipId() {
    $query = "
      SELECT 
        membership_payment.membership_id AS membership_id
      FROM civicrm_membership_payment AS membership_payment
      WHERE membership_payment.contribution_id = %1
    ";

    $dao = CRM_Core_DAO::executeQuery($query, [
      1 => [$this->contributionId, 'Integer']
    ]);

    while ($dao->fetch()) {
      $this->membershipId = $dao->membership_id;
      return;
    }

    $this->membershipId = FALSE;
  }

  /**
   * Collects mandate data
   */
  private function collectMandateData() {
    $query = "
      SELECT
        mandate.bank_name AS bank_name,
        mandate.bank_street_address AS bank_street_address,
        mandate.bank_city AS bank_city,
        mandate.bank_county AS bank_county,
        mandate.bank_postcode AS bank_postcode,
        mandate.account_holder_name AS account_holder_name,
        mandate.ac_number AS ac_number,
        mandate.sort_code AS sort_code,
        mandate.dd_code AS dd_code,
        mandate.dd_ref AS dd_ref,
        mandate.start_date AS start_date,
        mandate.authorisation_date AS authorisation_date
      FROM civicrm_value_dd_information AS dd_information 
      LEFT JOIN civicrm_value_dd_mandate AS mandate
        ON dd_information.mandate_id = mandate.id
      WHERE dd_information.entity_id = %1
      LIMIT 1
    ";

    $dao = CRM_Core_DAO::executeQuery($query, [
      1 => [$this->contributionId, 'Integer']
    ]);

    while ($dao->fetch()) {
      $this->tplParams['mandateData'] = [
        'bank_name' => $dao->bank_name,
        'bank_street_address' => $dao->bank_street_address,
        'bank_city' => $dao->bank_city,
        'bank_county' => $dao->bank_county,
        'bank_postcode' => $dao->bank_postcode,
        'account_holder_name' => $dao->account_holder_name,
        'ac_number' => $dao->ac_number,
        'sort_code' => $dao->sort_code,
        'dd_ref' => $dao->dd_ref,
        'dd_code' => $dao->dd_code,
        'start_date' => $dao->start_date,
        'authorisation_date' => $dao->authorisation_date,
      ];
    }
  }

  /**
   * Collects recurring contribution data
   */
  private function collectRecurringContributionData() {
    if ($this->recurringContributionId === FALSE) {
      return;
    }

    $recurringContributionBao = CRM_Contribute_BAO_ContributionRecur::findById($this->recurringContributionId);
    $recurringContributionRows = $this->collectRecurringContributionRows();
    $total = 0;
    foreach ($recurringContributionRows as $recurringContributionRow ) {
      $total += $recurringContributionRow['amount'];
    }
    $total = round($total, 2);

    $this->tplParams['recurringContributionData'] = [
      'recurringContributionRows' => $recurringContributionRows,
      'total' => $total,
      'installments' => $recurringContributionBao->installments,
      'installments_paid' => $recurringContributionBao->amount
    ];

    $this->tplParams['currency'] = $this->getCurrencySymbol($recurringContributionBao->currency);
  }

  /**
   * Gets currency symbol
   *
   * @param $currencyString
   *
   * @return mixed
   */
  private function getCurrencySymbol($currencyString) {
    $query = "
      SELECT currency.symbol AS symbol
      FROM civicrm_currency AS currency
      WHERE currency.name = %1
    ";

    $dao = CRM_Core_DAO::executeQuery($query, [
      1 => [$currencyString, 'String']
    ]);

    while ($dao->fetch()) {
      return $dao->symbol;
    }

    return '(' . $currencyString . ')';
  }

  /**
   * Collect recurring contribution table rows
   *
   * @return array
   */
  private function collectRecurringContributionRows() {
    $query = "
      SELECT 
        contribution.total_amount AS amount,
        financial_type.name AS financial_type_name
      FROM civicrm_contribution AS contribution
      LEFT JOIN civicrm_financial_type AS financial_type
        ON contribution.financial_type_id = financial_type.id
      WHERE contribution.contribution_recur_id = %1
    ";

    $dao = CRM_Core_DAO::executeQuery($query, [
      1 => [$this->recurringContributionId, 'Integer']
    ]);

    $rows = [];
    while ($dao->fetch()) {
      $rows[] = [
        'type' => $dao->financial_type_name,
        'amount' => $dao->amount
      ];
    }

    return $rows;
  }

  /**
   * Collects membership data
   */
  private function collectMembershipData() {
    if ($this->membershipId === FALSE) {
      return;
    }

    $query = "
      SELECT
        membership_type.duration_unit AS duration_unit,
        membership_type.name AS membership_name,
        membership_type.minimum_fee AS amount_per_unit
      FROM civicrm_membership AS membership 
      LEFT JOIN civicrm_membership_type AS membership_type
        ON membership.membership_type_id = membership_type.id
      WHERE membership.id = %1
      LIMIT 1
    ";

    $dao = CRM_Core_DAO::executeQuery($query, [
      1 => [$this->membershipId, 'Integer']
    ]);

    while ($dao->fetch()) {
      $this->tplParams['membershipData'] = [
        'durationUnit' => $dao->duration_unit,
        'amountPerUnit' => round($dao->amount_per_unit, 2),
        'membershipName' => $dao->membership_name
      ];
    }

    $this->collectNextMembershipPayment();
  }

  /**
   * Gets next payment date
   */
  private function collectNextMembershipPayment() {
    $cancelledStatus = CRM_ManualDirectDebit_Common_OptionValue::getOptionValueID('contribution_status', 'Cancelled');
    $completedStatus = CRM_ManualDirectDebit_Common_OptionValue::getOptionValueID('contribution_status', 'Completed');

    $query = "
      SELECT 
        contribution.receive_date AS next_payment_date, 
        contribution.total_amount AS next_payment_amount
      FROM civicrm_membership_payment AS membership_payment
      LEFT JOIN civicrm_contribution AS contribution
        ON membership_payment.contribution_id = contribution.id
      WHERE membership_payment.membership_id = %1
        AND contribution.receive_date >= NOW()
        AND contribution.contribution_status_id NOT IN (%2, %3)
      ORDER BY contribution.receive_date ASC
      LIMIT 1
    ";

    $dao = CRM_Core_DAO::executeQuery($query, [
      1 => [$this->membershipId, 'Integer'],
      2 => [$cancelledStatus, 'Integer'],
      3 => [$completedStatus, 'Integer'],
    ]);

    while ($dao->fetch()) {
      $this->tplParams['nextMembershipPayment'] = [
        'date' => $dao->next_payment_date,
        'amount' => round($dao->next_payment_amount, 2)
      ];
    }
  }

  /**
   * Gets direct debit image src
   */
  private function collectImageSrc() {
    $this->tplParams['directDebitImageSrc'] = CRM_ManualDirectDebit_ExtensionUtil::url() . '/Images/debit.ico';
  }

}
