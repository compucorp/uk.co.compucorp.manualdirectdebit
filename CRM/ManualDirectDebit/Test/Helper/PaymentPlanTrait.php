<?php

use CRM_MembershipExtras_Test_Fabricator_MembershipType as MembershipTypeFabricator;

trait CRM_ManualDirectDebit_Test_Helper_PaymentPlanTrait {

  /**
   * A helper funcitons that configures a payment plan to be used on tests.
   *
   * @param string $membershipStartDate
   * @param string $firstInstalmentReceiveDate
   *
   * @return mixed
   * @throws \CiviCRM_API3_Exception
   */
  private function setupPlan($membershipStartDate, $firstInstalmentReceiveDate) {
    $testMembershipType = MembershipTypeFabricator::fabricate(
      [
        'name' => 'Test Rolling Membership',
        'period_type' => 'rolling',
        'minimum_fee' => 120,
        'duration_interval' => 1,
        'duration_unit' => 'year',
      ]);

    $testMembershipTypePriceFieldValue = civicrm_api3('PriceFieldValue', 'get', [
      'sequential' => 1,
      'membership_type_id' => $testMembershipType['id'],
      'options' => ['limit' => 1],
    ])['values'][0];

    $paymentPlanEntity = new CRM_MembershipExtras_Test_Entity_PaymentPlanMembershipOrder();
    $paymentPlanEntity->membershipStartDate = $membershipStartDate;
    $paymentPlanEntity->paymentPlanStartDate = $firstInstalmentReceiveDate;
    $paymentPlanEntity->paymentMethod = 'direct_debit';
    $paymentPlanEntity->paymentPlanFrequency = 'Monthly';
    $paymentPlanEntity->lineItems[] = [
      'entity_table' => 'civicrm_membership',
      'price_field_id' => $testMembershipTypePriceFieldValue['price_field_id'],
      'price_field_value_id' => $testMembershipTypePriceFieldValue['id'],
      'label' => $testMembershipType['name'],
      'qty' => 1,
      'unit_price' => $testMembershipTypePriceFieldValue['amount'],
      'line_total' => $testMembershipTypePriceFieldValue['amount'],
      'financial_type_id' => 'Member Dues',
      'non_deductible_amount' => 0,
    ];;

    $recurringContribution = CRM_MembershipExtras_Test_Fabricator_PaymentPlanOrder::fabricate($paymentPlanEntity);

    $membership = civicrm_api3('Membership', 'get', [
      'sequential'   => 1,
      'membership_type_id' => $testMembershipType['id'],
      'contact_id' => $recurringContribution['contact_id'],
    ]);

    //Passing membership ID along with recurring recurring contribution for testing.
    $recurringContribution['membership_id'] = $membership['id'];

    return $recurringContribution;
  }

}
