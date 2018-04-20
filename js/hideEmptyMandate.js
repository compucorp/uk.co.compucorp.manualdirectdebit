CRM.$(function ($) {
  // hides `direct_debit_information` custom group from contribution detail
  $('#direct_debit_information__').hide();

  // hides `direct_debit_information` custom group from creating and editing contribution
  CRM.$('.crm-block.crm-form-block.crm-contribution-form-block #customData').hide();

  // hides `direct_debit_information` custom group if change financial type
  $('#financial_type_id').change(function () {
    CRM.$('.crm-block.crm-form-block.crm-contribution-form-block #customData').hide();
  });
});
