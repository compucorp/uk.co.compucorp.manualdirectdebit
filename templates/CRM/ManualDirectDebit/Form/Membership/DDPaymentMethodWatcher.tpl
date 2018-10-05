<script type="text/javascript">
  var directDebitID = '{$directDebitPaymentInstrumentId}';
  var pendingPaymentStatusID = '{$pendingPaymentStatusID}';

  {literal}
  CRM.$('#payment_instrument_id').change(function () {
    changePaymentStatusOptionToPendingWhenDDPaymentMethodIsSelected();
  });

  function changePaymentStatusOptionToPendingWhenDDPaymentMethodIsSelected() {
    if (CRM.$('#payment_instrument_id option:selected').val() == directDebitID) {
      CRM.$('#contribution_status_id').val(pendingPaymentStatusID);
    }
  }
  {/literal}
</script>

