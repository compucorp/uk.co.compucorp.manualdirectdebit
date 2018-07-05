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
  private $tplParams = [
    'directDebitImageSrc' => FALSE,
    'mandateData' => FALSE,
    'recurringContributionData' => FALSE,
    'currency' => FALSE,
    'membershipData' => FALSE,
    'nextMembershipPayment' => FALSE,
  ];

  /**
   * Email address
   *
   * @var int
   */
  private $contactEmailData = FALSE;

  /**
   * Recurring contribution id
   *
   * @var int
   */
  protected $recurringContributionId = FALSE;

  /**
   * Contribution data
   *
   * @var array
   */
  private $contributionData = FALSE;

  /**
   * Retrieves tpl params for template
   *
   * @return array
   */
  public function retrieve() {
    $this->setContributionId();
    $this->loadContributionData();
    $this->setContactEmailData();
    $this->setRecurringContributionId();
    $this->setMembershipId();

    $this->collectRecurringContributionData();
    $this->collectMandateData();
    $this->collectMembershipData();
    $this->collectImageSrc();
    $this->collectCurrency();

    return $this->tplParams;
  }

  /**
   * Retrieves email
   *
   * @return int
   */
  public function retrieveContactEmailData() {
    return $this->contactEmailData;
  }

  /**
   * Sets contribution id
   */
  abstract protected function setContributionId();

  /**
   *  Loads Contribution Data
   */
  private function loadContributionData() {
    $this->contributionData = civicrm_api3('Contribution', 'getsingle', [
      'id' => $this->contributionId,
    ]);
  }

  /**
   * Sets email by contribution id
   */
  private function setContactEmailData() {
    try {
      $this->contactEmailData = civicrm_api3('Contact', 'getsingle', [
        'return' => ["do_not_email", "is_opt_out", "email"],
        'id' => $this->contributionData['contact_id'],
      ]);
    }
    catch (CiviCRM_API3_Exception $e) {}
  }

  /**
   * Sets recurring contribution id
   */
  private function setRecurringContributionId() {
    if (!empty($this->contributionData['contribution_recur_id'])) {
      $this->recurringContributionId = $this->contributionData['contribution_recur_id'];
    }
  }

  /**
   * Sets membership id
   */
  private function setMembershipId() {
    $result = civicrm_api3('MembershipPayment', 'get', [
      'sequential' => 1,
      'contribution_id' => $this->contributionId,
    ]);

    $this->membershipId = $result['count'] == 1 ? $result['values'][0]['membership_id'] : FALSE;
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
        'dd_code' => $this->getDdCode($dao->dd_code),
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
    $cancelledStatus = CRM_ManualDirectDebit_Common_OptionValue::getValueForOptionValue('contribution_status', 'Cancelled');
    $completedStatus = CRM_ManualDirectDebit_Common_OptionValue::getValueForOptionValue('contribution_status', 'Completed');

    $query = "
      SELECT 
        contribution.receive_date AS next_payment_date, 
        contribution.total_amount AS next_payment_amount
      FROM civicrm_membership_payment AS membership_payment
      LEFT JOIN civicrm_contribution AS contribution
        ON membership_payment.contribution_id = contribution.id
      WHERE membership_payment.membership_id = %1
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
        'date' => $this->convertDateFormat($dao->next_payment_date),
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

  /**
   * Collects currency symbol
   */
  private function collectCurrency() {
    if ($this->recurringContributionId !== FALSE) {
      $recurringContributionBao = CRM_Contribute_BAO_ContributionRecur::findById($this->recurringContributionId);
      $this->tplParams['currency'] = $this->getCurrencySymbol($recurringContributionBao->currency);
    } else {
      $contributionBao = CRM_Contribute_BAO_Contribution::findById($this->contributionId);
      $this->tplParams['currency'] = $this->getCurrencySymbol($contributionBao->currency);
    }
  }

  /**
   * Gets direct debit code label
   *
   * @param $dd_code
   *
   * @return string
   */
  private function getDdCode($dd_code) {
    $result = civicrm_api3('OptionGroup', 'get', [
      'return' => 'label',
      'name' => 'direct_debit_codes',
      'sequential' => 1,
      'api.OptionValue.getValue' => [
        'return' => 'label',
        'option_group_id' => '$value.id',
        'sequential' => 1,
        'value' => $dd_code,
      ],
    ]);

    return $result['values'][0]['api.OptionValue.getValue'];
  }

  /**
   * Converts date time format
   *
   * @param $date
   *
   * @return string
   */
  private function convertDateFormat($date) {
    $myDateTime = DateTime::createFromFormat('Y-m-d H:i:s', $date);
    return $myDateTime->format('d/m/Y');
  }

}
