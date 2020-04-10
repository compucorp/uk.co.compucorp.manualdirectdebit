<script type="text/javascript">
  {literal}
  CRM.$(function($) {
    var selectedPaymentMethod = $('#payment_instrument_id option:selected').text();
    if (selectedPaymentMethod == 'Direct Debit') {
      $('#payment_instrument_id_field').hide();
    } else {
      $('#payment_instrument_id_field option:contains(Direct Debit)').hide();
    }
  });
  {/literal}
</script>
