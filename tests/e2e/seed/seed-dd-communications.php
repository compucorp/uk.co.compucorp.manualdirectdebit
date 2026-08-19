<?php

/**
 * Seeds the fixtures the Direct Debit communication journeys need.
 *
 * Creates one Direct Debit member per journey, each with a payment plan, a
 * mandate and the Direct Debit activity that journey's documented search looks
 * for. Also redirects outgoing mail to the database so the tests can read what
 * was actually sent.
 *
 * Run it with cv, from inside the site:
 *   SEED_OUTPUT=/tmp/seed.json cv scr seed-dd-communications.php
 *
 * See ../README.md. Intended for local test sites only.
 */

if (!class_exists('CRM_ManualDirectDebit_Common_MandateStorageManager')) {
  throw new CRM_Core_Exception('The Manual Direct Debit extension is not enabled on this site.');
}

define('SEED_TAG', 'MDD E2E');

// Seeding twice on the same site would otherwise leave two members answering to
// the same name and email address, and the journeys pick their member out of
// the search results by name. Suffixing keeps each run's members its own.
define('SEED_RUN', strtoupper(substr(dechex(crc32(uniqid('', TRUE))), 0, 4)));

/**
 * Applies the Direct Debit settings a mandate needs to be generated.
 */
function seed_settings() {
  Civi::settings()->set('manualdirectdebit_default_reference_prefix', 'DDE2E');
  Civi::settings()->set('manualdirectdebit_minimum_reference_prefix_length', 10);
  Civi::settings()->set('manualdirectdebit_new_instruction_run_dates', [1, 15]);
  Civi::settings()->set('manualdirectdebit_payment_collection_run_dates', [1, 15]);
  Civi::settings()->set('manualdirectdebit_minimum_days_to_first_payment', 5);
  Civi::settings()->set('manualdirectdebit_days_in_advance_notice_period', 10);
}

/**
 * Sends outgoing mail to the database so the tests can read it back.
 */
function seed_redirect_mail_to_database() {
  $backend = Civi::settings()->get('mailing_backend');
  $backend = is_array($backend) ? $backend : [];
  $backend['outBound_option'] = CRM_Mailing_Config::OUTBOUND_OPTION_REDIRECT_TO_DB;
  Civi::settings()->set('mailing_backend', $backend);
}

/**
 * Returns the value of a Direct Debit code, by its label.
 *
 * @param string $label
 *   For example "01".
 *
 * @return string
 *   The option value.
 */
function seed_dd_code($label) {
  return civicrm_api3('OptionValue', 'getvalue', [
    'option_group_id' => 'direct_debit_codes',
    'label' => $label,
    'return' => 'value',
  ]);
}

/**
 * Returns an originator number to put the mandates under, creating one if none.
 *
 * @return string
 *   The option value.
 */
function seed_originator_number() {
  $existing = civicrm_api3('OptionValue', 'get', [
    'option_group_id' => 'direct_debit_originator_number',
    'options' => ['limit' => 1],
    'sequential' => 1,
  ]);

  if ($existing['count']) {
    return $existing['values'][0]['value'];
  }

  return civicrm_api3('OptionValue', 'create', [
    'option_group_id' => 'direct_debit_originator_number',
    'label' => '123456',
    'value' => '123456',
    'name' => 'e2e_originator_number',
  ])['values'][0]['value'];
}

/**
 * Returns the Direct Debit payment processor to put the payment plans under.
 *
 * Other extensions read the processor off a recurring contribution, so a plan
 * seeded without one is not realistic enough to work with.
 *
 * @return int
 *   ID of the live Direct Debit payment processor.
 */
function seed_payment_processor_id() {
  return (int) civicrm_api3('PaymentProcessor', 'getvalue', [
    'name' => 'Direct Debit',
    'is_test' => 0,
    'return' => 'id',
    'options' => ['limit' => 1],
  ]);
}

/**
 * Returns the membership type the seeded members are given.
 *
 * @return array
 *   The membership type.
 */
function seed_membership_type() {
  $name = SEED_TAG . ' Membership';
  $existing = civicrm_api3('MembershipType', 'get', [
    'name' => $name,
    'sequential' => 1,
  ]);

  if ($existing['count']) {
    return $existing['values'][0];
  }

  return civicrm_api3('MembershipType', 'create', [
    'sequential' => 1,
    'name' => $name,
    'member_of_contact_id' => CRM_Core_BAO_Domain::getDomain()->contact_id,
    'financial_type_id' => 'Member Dues',
    'duration_unit' => 'year',
    'duration_interval' => 1,
    'period_type' => 'rolling',
    'minimum_fee' => 120,
    'auto_renew' => 1,
    'is_active' => 1,
  ])['values'][0];
}

/**
 * Creates a Direct Debit member with a payment plan and a mandate.
 *
 * @param string $lastName
 *   Last name, used to find the contact in the search results.
 * @param bool $withEmail
 *   Whether to give the contact an email address.
 * @param string $collectionDate
 *   Date the pending instalment is expected to be collected.
 *
 * @return array
 *   IDs of everything created.
 */
function seed_member($lastName, $withEmail, $collectionDate) {
  $lastName .= SEED_RUN;
  $membershipType = seed_membership_type();
  $mandateStorage = new CRM_ManualDirectDebit_Common_MandateStorageManager();

  $contactParams = [
    'contact_type' => 'Individual',
    'first_name' => 'Ddtest',
    'last_name' => $lastName,
    'source' => SEED_TAG,
  ];
  if ($withEmail) {
    $contactParams['email'] = strtolower($lastName) . '@example.org';
  }
  $contactId = civicrm_api3('Contact', 'create', $contactParams)['id'];

  $recurringContributionId = civicrm_api3('ContributionRecur', 'create', [
    'contact_id' => $contactId,
    'amount' => 10,
    'currency' => 'GBP',
    'frequency_unit' => 'month',
    'frequency_interval' => 1,
    'installments' => 12,
    'start_date' => date('Y-m-d'),
    'contribution_status_id' => 'Pending',
    'payment_instrument_id' => 'direct_debit',
    'payment_processor_id' => seed_payment_processor_id(),
    'financial_type_id' => 'Member Dues',
    'auto_renew' => 1,
  ])['id'];

  $membershipId = civicrm_api3('Membership', 'create', [
    'contact_id' => $contactId,
    'membership_type_id' => $membershipType['id'],
    'join_date' => date('Y-m-d'),
    'start_date' => date('Y-m-d'),
    'contribution_recur_id' => $recurringContributionId,
    'source' => SEED_TAG,
  ])['id'];

  $contributionId = civicrm_api3('Contribution', 'create', [
    'contact_id' => $contactId,
    'financial_type_id' => 'Member Dues',
    'total_amount' => 10,
    'currency' => 'GBP',
    'receive_date' => $collectionDate,
    'contribution_status_id' => 'Pending',
    'payment_instrument_id' => 'direct_debit',
    'contribution_recur_id' => $recurringContributionId,
    'is_pay_later' => 1,
    'source' => SEED_TAG,
  ])['id'];

  civicrm_api3('MembershipPayment', 'create', [
    'membership_id' => $membershipId,
    'contribution_id' => $contributionId,
  ]);

  $mandateId = seed_mandate($contactId);
  $mandateStorage->assignRecurringContributionMandate($recurringContributionId, $mandateId);
  $mandateStorage->assignContributionMandate($contributionId, $mandateId);

  return [
    'contactId' => (int) $contactId,
    'lastName' => $lastName,
    'email' => $withEmail ? $contactParams['email'] : NULL,
    'membershipId' => (int) $membershipId,
    'recurringContributionId' => (int) $recurringContributionId,
    'contributionId' => (int) $contributionId,
    'mandateId' => (int) $mandateId,
  ];
}

/**
 * Creates a mandate for a contact.
 *
 * @param int $contactId
 *   Contact the mandate belongs to.
 *
 * @return int
 *   ID of the created mandate.
 */
function seed_mandate($contactId) {
  $mandateStorage = new CRM_ManualDirectDebit_Common_MandateStorageManager();
  $now = new DateTime();

  $mandate = (array) $mandateStorage->saveDirectDebitMandate($contactId, [
    'entity_id' => $contactId,
    'bank_name' => 'Lloyds Bank',
    'bank_street_address' => '25 Gresham Street',
    'bank_city' => 'London',
    'bank_county' => 'Greater London',
    'bank_postcode' => 'EC2V 7HN',
    'account_holder_name' => 'John Doe',
    'ac_number' => '12345678',
    'sort_code' => '12-34-56',
    'dd_code' => seed_dd_code('01'),
    'dd_ref' => 'DD Ref',
    'start_date' => $now->format('Y-m-d H:i:s'),
    'authorisation_date' => $now->format('Y-m-d H:i:s'),
    'originator_number' => seed_originator_number(),
  ]);

  return (int) $mandate['id'];
}

/**
 * Creates a Direct Debit member with a mandate but nothing to bill.
 *
 * The letter for this member cannot be generated, which is what makes it
 * useful: it proves the run continues and that nothing is recorded against a
 * member who was not written to.
 *
 * @param string $lastName
 *   Last name, used to find the contact in the search results.
 *
 * @return array
 *   IDs of everything created.
 */
function seed_member_without_a_contribution($lastName) {
  $lastName .= SEED_RUN;
  $membershipType = seed_membership_type();

  $contactId = civicrm_api3('Contact', 'create', [
    'contact_type' => 'Individual',
    'first_name' => 'Ddtest',
    'last_name' => $lastName,
    'source' => SEED_TAG,
  ])['id'];

  $membershipId = civicrm_api3('Membership', 'create', [
    'contact_id' => $contactId,
    'membership_type_id' => $membershipType['id'],
    'join_date' => date('Y-m-d'),
    'start_date' => date('Y-m-d'),
    'source' => SEED_TAG,
  ])['id'];

  return [
    'contactId' => (int) $contactId,
    'lastName' => $lastName,
    'email' => NULL,
    'membershipId' => (int) $membershipId,
    'mandateId' => seed_mandate($contactId),
  ];
}

/**
 * Records a Direct Debit activity against a member.
 *
 * @param string $activityTypeName
 *   Machine name of the Direct Debit activity type.
 * @param int $contactId
 *   The member the activity is about.
 * @param int $sourceRecordId
 *   The record the activity was raised from.
 *
 * @return int
 *   ID of the created activity.
 */
function seed_activity($activityTypeName, $contactId, $sourceRecordId) {
  $label = civicrm_api3('OptionValue', 'getvalue', [
    'option_group_id' => 'activity_type',
    'name' => $activityTypeName,
    'return' => 'label',
  ]);

  return (int) civicrm_api3('Activity', 'create', [
    'activity_type_id' => $activityTypeName,
    'subject' => $label,
    'activity_date_time' => date('YmdHis'),
    'status_id' => 'Completed',
    'source_contact_id' => $contactId,
    'target_id' => $contactId,
    'source_record_id' => $sourceRecordId,
  ])['id'];
}

/**
 * Returns the label of a Direct Debit activity type.
 *
 * @param string $name
 *   Machine name of the activity type.
 *
 * @return string
 *   The label shown in the search form.
 */
function seed_activity_type_label($name) {
  return civicrm_api3('OptionValue', 'getvalue', [
    'option_group_id' => 'activity_type',
    'name' => $name,
    'return' => 'label',
  ]);
}

seed_settings();
seed_redirect_mail_to_database();

$collectionDate = date('Y-m-d', strtotime('+7 days'));

// One member per journey, so a journey cannot be satisfied by another's data.
$signUp = seed_member('Signup', TRUE, $collectionDate);
$paymentUpdate = seed_member('Paymentupdate', TRUE, $collectionDate);
$collectionReminder = seed_member('Reminder', TRUE, $collectionDate);
$autoRenew = seed_member('Autorenew', TRUE, $collectionDate);
$mandateUpdate = seed_member('Mandateupdate', TRUE, $collectionDate);
$letters = seed_member('Letters', FALSE, $collectionDate);
$noContribution = seed_member_without_a_contribution('Nocontribution');

// Creating the payment plan raises the sign-up activity by itself. Re-pointing
// a plan at a second mandate is what raises the payment-update activity.
$mandateStorage = new CRM_ManualDirectDebit_Common_MandateStorageManager();
$mandateStorage->assignRecurringContributionMandate(
  $paymentUpdate['recurringContributionId'],
  seed_mandate($paymentUpdate['contactId'])
);

seed_activity('direct_debit_payment_reminder', $collectionReminder['contactId'], $collectionReminder['contributionId']);
seed_activity('offline_direct_debit_auto_renewal', $autoRenew['contactId'], $autoRenew['recurringContributionId']);
seed_activity('direct_debit_mandate_update', $mandateUpdate['contactId'], $mandateUpdate['mandateId']);

$seed = [
  'seededOn' => date('c'),
  'run' => SEED_RUN,
  'collectionDate' => $collectionDate,
  'members' => [
    'signUp' => $signUp,
    'paymentUpdate' => $paymentUpdate,
    'collectionReminder' => $collectionReminder,
    'autoRenew' => $autoRenew,
    'mandateUpdate' => $mandateUpdate,
    'letters' => $letters,
    'noContribution' => $noContribution,
  ],
  'activityTypeLabels' => [
    'signUp' => seed_activity_type_label('new_direct_debit_recurring_payment'),
    'paymentUpdate' => seed_activity_type_label('update_direct_debit_recurring_payment'),
    'collectionReminder' => seed_activity_type_label('direct_debit_payment_reminder'),
    'autoRenew' => seed_activity_type_label('offline_direct_debit_auto_renewal'),
    'mandateUpdate' => seed_activity_type_label('direct_debit_mandate_update'),
  ],
  'templateTitles' => [
    'signUp' => 'Direct Debit Payment Sign Up Notification',
    'paymentUpdate' => 'Direct Debit Payment Update Notification',
    'collectionReminder' => 'Direct Debit Payment Collection Reminder',
    'autoRenew' => 'Direct Debit Auto-renew Notification',
    'mandateUpdate' => 'Direct Debit Mandate Update Notification',
  ],
  'mandate' => [
    'bankName' => 'Lloyds Bank',
    'accountHolderName' => 'John Doe',
  ],
];

$json = json_encode($seed, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

if (getenv('SEED_OUTPUT')) {
  file_put_contents(getenv('SEED_OUTPUT'), $json);
}

echo $json . "\n";
