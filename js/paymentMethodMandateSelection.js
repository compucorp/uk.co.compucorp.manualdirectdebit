CRM.$(function ($) {
  let contactField = $("#contact_id");
  let paymentInstrumentField = $("#payment_instrument_id");

  if (typeof contactField.val() !== "undefined" && contactField.val() !== "" && typeof CRM.vars.coreForm !== "undefined") {
    CRM.vars.coreForm.contact_id = contactField.val();
  }

  if (paymentInstrumentField.val() !== "") {
    paymentInstrumentField.trigger("change.paymentBlock");
  }

  contactField.change(function () {
    CRM.vars.coreForm.contact_id = $(this).val();
    $("#payment_instrument_id").trigger("change.paymentBlock");
  });

  paymentInstrumentField.closest('.ui-dialog').on('crmFormLoad.crmForm', function () {
    $("#payment_instrument_id").trigger("change.paymentBlock");
  });

  paymentInstrumentField.closest('.ui-dialog').on('crmFormError.crmForm', function () {
    $("#billing-payment-block").on('crmLoad', function() {
      let mandateField = $("#mandate_id");
      let mandateFieldLabel = $("label[for='mandate_id']");
      if (typeof mandateField.val() !== "undefined" && mandateField.val() === "-") {
        mandateField.addClass("error crm-error");
        mandateFieldLabel.addClass("error crm-error");
      }
    });
  });
});
