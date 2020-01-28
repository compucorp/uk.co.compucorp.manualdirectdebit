var CRM = CRM || {};
CRM.coreForm = CRM.coreForm || {};

CRM.$(function ($) {
  /**
   * Loads contact ID into CiviCRM form globals.
   *
   * CRM.vars.coreForm.contact_id is a global variable CiviCRM uses to send the
   * contact_id into the ajax call that obtains fields required for each
   * particular payment instrument. However, we lose this value after a failed
   * validation, and on modal dialogs. Thus, we need to load it if it isn't set,
   * as we need the contact ID to be able to load its existing mandates.
   */
  function loadContactIDIntoGlobalFormVariables() {
    let contactField = $("#contact_id");

    if (typeof contactField.val() !== "undefined" && contactField.val() !== "") {
      CRM.vars.coreForm.contact_id = contactField.val();
    }
  }

  /**
   * Sets up trigger to reload payment instrument fields on contact change.
   *
   * A change in contact needs to trigger the payment instrument block fields to
   * be refreshed, so it loads the mandates specific for that contact. Also, if
   * the user chooses direct debit the payment instrument, leaving the contact
   * empty, we show an error message to the user asking him to select the
   * contact. Doing so, should also trigger the payment instrument fields to be
   * refreshed.
   */
  function setupContactChangeEventTriggeringPaymentBlockRefresh() {
    let contactField = $("#contact_id");

    contactField.change(function () {
      CRM.vars.coreForm.contact_id = $(this).val();
      $("#payment_instrument_id").trigger("change.paymentBlock");
    });
  }

  /**
   * Triggers loading of fields related to payment instrument.
   *
   * Checks if a payment instrument has been selected and if so, triggers the
   * event to load fields specific to that instrument.
   */
  function triggerPaymentBlockRefreshingIfPaymentInstrumentIsNotNull() {
    let paymentInstrumentField = $("#payment_instrument_id");

    if (paymentInstrumentField.val() !== "") {
      paymentInstrumentField.trigger("change.paymentBlock");
    }
  }

  /**
   * Checks if there has been a validation error for the mandate field.
   *
   * If the mandate field has is found empty after validation, it is marked as
   * an error by adding the appropriate CSS classes.
   */
  function handleEmptyMandateFieldValidationError() {
    $("#billing-payment-block").on("crmLoad", function() {
      if (typeof CRM.vars.coreForm.empty_mandate_id === "undefined" || !CRM.vars.coreForm.empty_mandate_id) {
        return;
      }

      let mandateField = $("#mandate_id");
      let mandateFieldLabel = $("label[for='mandate_id']");
      if (typeof mandateField.val() !== "undefined" && mandateField.val() === "-") {
        mandateField.addClass("error crm-error");
        mandateFieldLabel.addClass("error crm-error");
      }
    });
  }

  /**
   * Handle validation errors on forms opened as modal dialogs.
   *
   * Forms opened on modal dialogs don't actually refresh the page, so we need
   * to capture CiviCRM load events to refresh payment instrument fields and
   * handle errors of mandate field.
   */
  function handleEmptyMandateFieldOnFailedValidationForMembershipDialogs() {
    let paymentInstrumentField = $("#payment_instrument_id");

    paymentInstrumentField.closest('.ui-dialog').on('crmFormLoad.crmForm', function () {
      $("#payment_instrument_id").trigger("change.paymentBlock");
      handleEmptyMandateFieldValidationError();
    });
  }

  loadContactIDIntoGlobalFormVariables();
  setupContactChangeEventTriggeringPaymentBlockRefresh();
  triggerPaymentBlockRefreshingIfPaymentInstrumentIsNotNull();
  handleEmptyMandateFieldValidationError();
  handleEmptyMandateFieldOnFailedValidationForMembershipDialogs();
});
