<?php
use CRM_ManualDirectDebit_Common_SettingsManager as SettingsManager;

/**
 * Metadata for Manual Direct Debit Settings
 */
return [
  'manualdirectdebit_default_reference_prefix' => [
    'group_name' => 'Manual Direct Debit',
    'group' => 'manualdirectdebit',
    'name' => 'manualdirectdebit_default_reference_prefix',
    'title' => 'Default Reference Prefix',
    'type' => 'String',
    'html_type' => 'text',
    'quick_form_type' => 'Element',
    'default' => '',
    'is_help' => FALSE,
    'is_required' => TRUE,
    'html_attributes' => '',
    'extra_data' => '',
    'section' => 'mandate_config',
  ],
  'manualdirectdebit_minimum_reference_prefix_length' => [
    'group_name' => 'Manual Direct Debit',
    'group' => 'manualdirectdebit',
    'name' => 'manualdirectdebit_minimum_reference_prefix_length',
    'title' => 'Minimum Mandate Reference Length',
    'type' => 'Integer',
    'html_type' => 'number',
    'quick_form_type' => 'Element',
    'default' => 6,
    'is_required' => TRUE,
    'is_help' => TRUE,
    'html_attributes' => ['min' => 0],
    'extra_data' => '',
    'section' => 'mandate_config',
  ],
  'manualdirectdebit_new_instruction_run_dates' => [
    'group_name' => 'Manual Direct Debit',
    'group' => 'manualdirectdebit',
    'name' => 'manualdirectdebit_new_instruction_run_dates',
    'title' => 'New Instruction Run Dates',
    'type' => 'Integer',
    'html_type' => 'select',
    'quick_form_type' => 'Element',
    'default' => 0,
    'is_help' => FALSE,
    'is_required' => TRUE,
    'html_attributes' => generateSequenceNumbers(31),
    'extra_data' => [
      'class' => 'crm-select2',
      'multiple' => 'multiple',
      'placeholder' => ts('- select -'),
    ],
    'section' => 'payment_config',
  ],
  'manualdirectdebit_payment_collection_run_dates' => [
    'group_name' => 'Manual Direct Debit',
    'group' => 'manualdirectdebit',
    'name' => 'manualdirectdebit_payment_collection_run_dates',
    'title' => 'Payment Collection Run Dates ',
    'type' => 'Integer',
    'html_type' => 'select',
    'quick_form_type' => 'Element',
    'default' => 1,
    'is_required' => TRUE,
    'is_help' => FALSE,
    'html_attributes' => generateSequenceNumbers(28),
    'extra_data' => [
      'class' => 'crm-select2',
      'multiple' => 'multiple',
      'placeholder' => ts('- select -'),
    ],
    'section' => 'payment_config',
  ],
  'manualdirectdebit_minimum_days_to_first_payment' => [
    'group_name' => 'Manual Direct Debit',
    'group' => 'manualdirectdebit',
    'name' => 'manualdirectdebit_minimum_days_to_first_payment',
    'title' => 'Minimum Days from New Instruction to First Payment',
    'type' => 'Integer',
    'html_type' => 'number',
    'quick_form_type' => 'Element',
    'default' => 1,
    'is_required' => TRUE,
    'is_help' => TRUE,
    'html_attributes' => ['min' => 0, 'max' => 30],
    'extra_data' => '',
    'section' => 'payment_config',
  ],
  'manualdirectdebit_second_instalment_date_behaviour' => [
    'group_name' => 'Manual Direct Debit',
    'group' => 'manualdirectdebit',
    'name' => 'manualdirectdebit_second_instalment_date_behaviour',
    'title' => 'Second Instalment Date Behaviour',
    'type' => 'String',
    'html_type' => 'select',
    'quick_form_type' => 'Element',
    'default' => SettingsManager::SECOND_INSTALMENT_BEHAVIOUR_ONE_MONTH_AFTER,
    'is_required' => TRUE,
    'is_help' => TRUE,
    'html_attributes' => [
      SettingsManager::SECOND_INSTALMENT_BEHAVIOUR_ONE_MONTH_AFTER => ts('Take second instalment 1 month after the first instalment'),
      SettingsManager::SECOND_INSTALMENT_BEHAVIOUR_FORCE_SECOND_MONTH => ts('Take second instalment in the second month of membership'),
    ],
    'extra_data' => [
      'class' => 'crm-select2',
      'placeholder' => ts('- select -'),
    ],
    'section' => 'instalment_config',
  ],
  'manualdirectdebit_days_in_advance_for_collection_reminder' => [
    'group_name' => 'Manual Direct Debit',
    'group' => 'manualdirectdebit',
    'name' => 'manualdirectdebit_days_in_advance_for_collection_reminder',
    'title' => 'Days in Advance for Collection Reminder',
    'type' => 'Integer',
    'html_type' => 'number',
    'quick_form_type' => 'Element',
    'default' => '',
    'is_required' => TRUE,
    'is_help' => TRUE,
    'html_attributes' => '',
    'extra_data' => '',
    'section' => 'reminder_config',
  ],
  'manualdirectdebit_batch_submission_queue_limit' => [
    'group_name' => 'Manual Direct Debit',
    'group' => 'manualdirectdebit',
    'name' => 'manualdirectdebit_batch_submission_queue_limit',
    'title' => 'Number of Records to be Processed per Batch Submission Queue Task',
    'type' => 'Integer',
    'html_type' => 'number',
    'quick_form_type' => 'Element',
    'default' => 50,
    'is_required' => TRUE,
    'is_help' => FALSE,
    'html_attributes' => '',
    'extra_data' => '',
    'section' => 'batch_config',
  ],
];

/**
 * Generates a list of sequence numbers starting from 1 to the specified limit.
 *
 * @param int $limit
 *
 * @return  array
 */
function generateSequenceNumbers($limit) {
  $sequence = [];
  for ($i = 1; $i <= $limit; $i++) {
    $sequence[] = $i;
  }
  return $sequence;
}
