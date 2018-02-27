<?php

/*
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
  'manualdirectdebit_new_instruction_run_dates' => [
    'group_name' => 'Manual Direct Debit',
    'group' => 'manualdirectdebit',
    'name' => 'manualdirectdebit_new_instruction_run_dates',
    'title' => 'New instruction run dates',
    'type' => 'Integer',
    'html_type' => 'select',
    'quick_form_type' => 'Element',
    'default' => 0,
    'is_help' => FALSE,
    'is_required' => TRUE,
    'html_attributes' => getSequenceOfDays(30),
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
    'title' => 'Payment collection run dates ',
    'type' => 'Integer',
    'html_type' => 'select',
    'quick_form_type' => 'Element',
    'default' => 1,
    'is_required' => TRUE,
    'is_help' => FALSE,
    'html_attributes' => getSequenceOfDays(31),
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
    'title' => 'Minimum days from new instruction to first payment',
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
];

/**
 * function generate int arrays from 1 till $amountOfDays
 *
 * @param int $amountOfDays
 *
 * @return  array sequence
 *
 */
function getSequenceOfDays($amountOfDays) {
  $sequenceOfDays = [];
  for ($i = 1; $i <= $amountOfDays; $i++) {
    $sequenceOfDays[] = $i;
  }
  return $sequenceOfDays;
}
