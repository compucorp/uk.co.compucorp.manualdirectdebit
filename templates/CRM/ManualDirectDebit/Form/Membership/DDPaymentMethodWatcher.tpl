<script type="text/javascript">
  var directDebitID = '{$directDebitPaymentInstrumentId}';
  var pendingPaymentStatusID = '{$pendingPaymentStatusID}';
  var completedPaymentStatusID = '{$completedPaymentStatusID}';

  {literal}
  CRM.$('#payment_instrument_id').change(function () {
    changePaymentStatusOptionToPendingWhenDDPaymentMethodIsSelected();
  });

  function changePaymentStatusOptionToPendingWhenDDPaymentMethodIsSelected() {
    if (CRM.$('#payment_instrument_id option:selected').val() == directDebitID) {
      CRM.$('#contribution_status_id').val(pendingPaymentStatusID);
    } else if (CRM.$('input[name=fe_record_payment_check]').length && CRM.$('input[name=fe_record_payment_check]').is(':checked')) {
      CRM.$('#contribution_status_id').val(completedPaymentStatusID);
    }
  }
  {/literal}
</script>

