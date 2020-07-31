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
   * Template params
   *
   * @var array
   */
  private $tplParams = [
    'directDebitImageSrc' => FALSE,
    'mandateData' => FALSE,
    'recurringContributionData' => FALSE,
    'currency' => FALSE,
    'paymentPlanMemberships' => FALSE,
    'activeMemberships' => FALSE,
    'nextMembershipPayment' => FALSE,
    'orderLineItems' => FALSE,
    'orderSummaryTable' => FALSE,
    'activeMembershipsTable' => FALSE,
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
   *
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function retrieve() {
    $this->setShortDateFormat();
    $this->setContributionId();
    $this->loadContributionData();
    $this->setContactEmailData();
    $this->setRecurringContributionId();

    $this->collectRecurringContributionData();
    $this->collectMandateData();
    $this->collectOrderLineItems();
    $this->collectPaymentPlanMembershipsData();
    $this->collectActiveMembershipsData();
    $this->collectNextMembershipPayment();
    $this->collectImageSrc();
    $this->collectCurrency();

    $this->generateOrderSummaryTable();
    $this->generateActiveMembershipsTable();

    return $this->tplParams;
  }

  /**
   * Loads short date format as configured in CiviCRM.
   */
  private function setShortDateFormat() {
    $this->tplParams['shortDateFormat'] = Civi::Settings()->get('dateformatshortdate');
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
    catch (CiviCRM_API3_Exception $e) {
      return;
    }
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
      1 => [$this->contributionId, 'Integer'],
    ]);

    while ($dao->fetch()) {
      $this->tplParams['mandateData'] = [
        'bank_name' => $dao->bank_name,
        'bank_street_address' => $dao->bank_street_address,
        'bank_city' => $dao->bank_city,
        'bank_county' => $dao->bank_county,
        'bank_postcode' => $dao->bank_postcode,
        'account_holder_name' => $dao->account_holder_name,
        'ac_number' => $this->obfuscateAccountNumber($dao->ac_number),
        'sort_code' => $dao->sort_code,
        'dd_ref' => $dao->dd_ref,
        'dd_code' => $this->getDdCode($dao->dd_code),
        'start_date' => $dao->start_date,
        'authorisation_date' => $dao->authorisation_date,
      ];
    }
  }

  /**
   * Replaces all but the last four charcters of the given number for '*'
   * characters.
   *
   * @param string $accountNumber
   *
   * @return string
   */
  private function obfuscateAccountNumber($accountNumber) {
    if (strlen($accountNumber) > 4) {
      $accountNumber = str_repeat('*', strlen($accountNumber) - 4) . substr($accountNumber, -4);
    }
    else {
      $accountNumber = '****';
    }

    return $accountNumber;
  }

  /**
   * Collects recurring contribution data
   */
  private function collectRecurringContributionData() {
    if ($this->recurringContributionId === FALSE) {
      return;
    }

    $recurringContributionBao = CRM_Contribute_BAO_ContributionRecur::findById($this->recurringContributionId);
    $installmentsCount = empty($recurringContributionBao->installments) ? 1 : $recurringContributionBao->installments;
    $recurringContributionRows = $this->collectRecurringContributionRows($installmentsCount);
    $total = 0;
    $totalTax = 0;
    $recurringContributionPlan = array();
    foreach ($recurringContributionRows as $index => $recurringContributionRow) {
      $total += $recurringContributionRow['amount'];
      $totalTax += $recurringContributionRow['tax'];
      $dueDate = DateTime::createFromFormat('Y-m-d H:i:s', $recurringContributionRow['receive_date']);
      $recurringContributionPlan[$index]['index'] = $index + 1;
      $recurringContributionPlan[$index]['amount'] = $this->formatAmount($recurringContributionRow['amount']);
      $recurringContributionPlan[$index]['tax'] = $this->formatAmount($recurringContributionRow['tax']);
      $recurringContributionPlan[$index]['sub_total'] = $this->formatAmount($recurringContributionRow['amount'] - $recurringContributionRow['tax']);
      $recurringContributionPlan[$index]['due_date'] = $dueDate->format('Y-m-d');
    }
    $recurringContributionRows['recurringInstallmentsTable'] = $this->buildRecuringContributionTable($recurringContributionPlan, $totalTax);
    $total = $this->formatAmount($total);
    $totalTax = $totalTax ? $this->formatAmount($totalTax) : NULL;

    $this->tplParams['recurringContributionData'] = [
      'recurringContributionRows' => $recurringContributionRows,
      'total' => $total,
      'tax_total' => $totalTax,
      'installments' => $installmentsCount,
      'installments_paid' => $this->formatAmount($recurringContributionBao->amount),
    ];
  }

  /**
   * Builds a HTML table for recurring contribution installments
   * Note: If we build this table in mail template, there is an issue
   * with using loops within tables because of the WYSIWYG editor
   * to which the mail templates are loaded into
   *
   * @param $recurringContributionPlan
   * @param $totalTax
   *
   * @return string
   */
  private function buildRecuringContributionTable($recurringContributionPlan, $totalTax) {
    $smarty = CRM_Core_Smarty::singleton();
    $smarty->assign('totalTax', $totalTax);
    $smarty->assign('installments', $recurringContributionPlan);
    $smarty->assign('shortDateFormat', $this->tplParams['shortDateFormat']);

    return $smarty->fetch('CRM/ManualDirectDebit/MessageTemplate/Snippets/InstallmentList.tpl');
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
      1 => [$currencyString, 'String'],
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
  private function collectRecurringContributionRows($installmentsCount) {
    $query = "
      SELECT
        contribution.total_amount AS amount,
        contribution.tax_amount AS tax_amount,
        contribution.receive_date AS receive_date,
        financial_type.name AS financial_type_name,
        contribution_recur.amount AS recur_amount,
        contribution_recur.currency AS recur_currency,
        contribution_recur.frequency_unit AS recur_frequency_unit,
        contribution_recur.frequency_interval AS recur_interval,
        contribution_recur.installments AS recur_installments,
        contribution_recur.start_date AS recur_start_date
      FROM civicrm_contribution AS contribution
      LEFT JOIN civicrm_financial_type AS financial_type
        ON contribution.financial_type_id = financial_type.id
      LEFT JOIN civicrm_contribution_recur AS contribution_recur
        ON contribution.contribution_recur_id = contribution_recur.id
      WHERE contribution.contribution_recur_id = %1
    ";

    if ($installmentsCount == 1) {
      $query .= ' ORDER BY contribution.id DESC LIMIT 1 ';
    }

    $dao = CRM_Core_DAO::executeQuery($query, [
      1 => [$this->recurringContributionId, 'Integer'],
    ]);

    $rows = [];
    while ($dao->fetch()) {
      $rows[] = [
        'type' => $dao->financial_type_name,
        'amount' => $dao->amount,
        'tax' => $dao->tax_amount,
        'receive_date' => $dao->receive_date,
        'recur_amount' => $dao->recur_amount,
        'recur_currency' => $dao->recur_currency,
        'recur_frequency_unit' => $dao->recur_frequency_unit,
        'recur_interval' => $dao->recur_interval,
        'recur_installments' => $dao->recur_installments,
        'recur_start_date' => $dao->recur_start_date,
      ];
    }

    return $rows;
  }

  private function collectOrderLineItems() {
    $lineItems = civicrm_api3('LineItem', 'get', [
      'sequential' => 1,
      'contribution_id' => $this->contributionId,
      'options' => ['limit' => 0],
    ]);

    if (empty($lineItems['count'])) {
      return;
    }

    $installmentsCount = $this->tplParams['recurringContributionData']['installments'];
    $orderLineItems = [];
    $total = 0;
    foreach ($lineItems['values'] as $lineItem) {
      $price = $lineItem['line_total'] * $installmentsCount;
      $tax = $lineItem['tax_amount'] * $installmentsCount;
      $orderLineItems[] = [
        'label' => $lineItem['label'],
        'price' => $this->formatAmount($price),
        'entityTable' => $lineItem['entity_table'],
        'entityId' => $lineItem['entity_id'],
        'tax' => $tax ? $this->formatAmount($tax) : NULL,
      ];
      $total += $price;
    }

    $this->tplParams['orderLineItems'] = $orderLineItems;
    $this->tplParams['recurringContributionData']['total'] = $this->formatAmount($total);
  }

  /**
   * Obtains data for the membership payment plan.
   *
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  private function collectPaymentPlanMembershipsData() {
    if (empty($this->tplParams['orderLineItems'])) {
      return;
    }

    $paymentPlanMemberships = [];
    $membershipIds = [];
    foreach ($this->tplParams['orderLineItems'] as $lineItem) {
      if ($lineItem['entityTable'] == 'civicrm_membership') {
        $membershipIds[] = $lineItem['entityId'];
        $paymentPlanMemberships[$lineItem['entityId']]['label'] = $lineItem['label'];
        $paymentPlanMemberships[$lineItem['entityId']]['price'] = $lineItem['price'];
        $paymentPlanMemberships[$lineItem['entityId']]['tax'] = $lineItem['tax'];
      }
    }

    if (empty($membershipIds)) {
      return;
    }

    $membershipsResponse = civicrm_api3('Membership', 'get', [
      'sequential' => 1,
      'id' => ['IN' => $membershipIds],
      'options' => ['limit' => 0],
      'api.MembershipType.get' => ['id' => '$value.membership_type_id'],
    ]);

    if ($membershipsResponse['count'] < 1) {
      $membershipIds = implode(', ', $membershipIds);
      throw new CRM_Core_Exception("Memberships with the following IDs: $membershipIds, do no longer exist!");
    }

    foreach ($membershipsResponse['values'] as $membership) {
      $paymentPlanMemberships[$membership['id']]['id'] = $membership['id'];
      $paymentPlanMemberships[$membership['id']]['startDate'] = $membership['start_date'];
      $paymentPlanMemberships[$membership['id']]['endDate'] = $membership['end_date'];
      $paymentPlanMemberships[$membership['id']]['durationUnit'] = $membership['api.MembershipType.get']['values'][0]['duration_unit'];
    }

    $this->tplParams['paymentPlanMemberships'] = array_values($paymentPlanMemberships);
  }

  private function collectActiveMembershipsData() {
    $activeMembershipsResponse = civicrm_api3('Membership', 'get', [
      'sequential' => 1,
      'contact_id' => $this->contributionData['contact_id'],
      'active_only' => 1,
    ]);

    if (empty($activeMembershipsResponse['count'])) {
      return;
    }

    $activeMemberships = [];
    $i = 0;
    foreach ($activeMembershipsResponse['values'] as $membership) {
      $activeMemberships[] = [
        'name' => $membership['membership_name'],
        'startDate' => $membership['start_date'],
        'endDate' => $membership['end_date'],
      ];
    }

    $this->tplParams['activeMemberships'] = $activeMemberships;
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

    if (empty($this->tplParams['paymentPlanMemberships'])) {
      return;
    }

    $dao = CRM_Core_DAO::executeQuery($query, [
      1 => [$this->tplParams['paymentPlanMemberships'][0]['id'], 'Integer'],
      2 => [$cancelledStatus, 'Integer'],
      3 => [$completedStatus, 'Integer'],
    ]);

    while ($dao->fetch()) {
      $this->tplParams['nextMembershipPayment'] = [
        'date' => $dao->next_payment_date,
        'amount' => round($dao->next_payment_amount, 2),
      ];
    }
  }

  /**
   * Gets direct debit image src
   */
  private function collectImageSrc() {
    $this->tplParams['directDebitImageSrc'] = CIVICRM_UF_BASEURL . CRM_ManualDirectDebit_ExtensionUtil::url('Images/debit.png');
  }

  /**
   * Collects currency symbol
   */
  private function collectCurrency() {
    if ($this->recurringContributionId !== FALSE) {
      $recurringContributionBao = CRM_Contribute_BAO_ContributionRecur::findById($this->recurringContributionId);
      $this->tplParams['currency'] = $this->getCurrencySymbol($recurringContributionBao->currency);
    }
    else {
      $contributionBao = CRM_Contribute_BAO_Contribution::findById($this->contributionId);
      $this->tplParams['currency'] = $this->getCurrencySymbol($contributionBao->currency);
    }
  }

  private function generateOrderSummaryTable() {
    $smarty = CRM_Core_Smarty::singleton();
    $paramsToAssign = ['orderLineItems', 'recurringContributionData', 'currency', 'shortDateFormat'];
    foreach ($paramsToAssign as $param) {
      $smarty->assign($param, $this->tplParams[$param]);
    }

    $this->tplParams['orderSummaryTable'] = $smarty->fetch('CRM/ManualDirectDebit/MessageTemplate/Snippets/OrderSummary.tpl');
  }

  private function generateActiveMembershipsTable() {
    $smarty = CRM_Core_Smarty::singleton();
    $smarty->assign('activeMemberships', $this->tplParams['activeMemberships']);
    $smarty->assign('shortDateFormat', $this->tplParams['shortDateFormat']);

    $this->tplParams['activeMembershipsTable'] = $smarty->fetch('CRM/ManualDirectDebit/MessageTemplate/Snippets/ActiveMemberships.tpl');
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

  private function formatAmount($amount) {
    $roundedAmount = round($amount, 2);
    return number_format($roundedAmount, 2);
  }

}
