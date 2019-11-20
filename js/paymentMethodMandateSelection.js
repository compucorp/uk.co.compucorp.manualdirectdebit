CRM.$(function ($) {
  let contactField = $("#contact_id");
  let paymentInstrumentField = $("#payment_instrument_id");

  if (contactField.val() !== "" && typeof CRM.vars.coreForm !== "undefined") {
    CRM.vars.coreForm.contact_id = contactField.val();
  }

  contactField.change(function () {
    CRM.vars.coreForm.contact_id = $(this).val();
    CRM.$("#payment_instrument_id").trigger("change.paymentBlock");
  });

  if (paymentInstrumentField.val() !== "") {
    paymentInstrumentField.trigger("change.paymentBlock");
  }
});
