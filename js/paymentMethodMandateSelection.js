CRM.$(function ($) {
  CRM.$('#contact_id').change(function () {
    CRM.vars.coreForm.contact_id = CRM.$(this).val();
    CRM.$('#payment_instrument_id').trigger('change.paymentBlock');
  });
});
