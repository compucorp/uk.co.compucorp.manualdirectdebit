<script type="text/javascript">
  {literal}
  CRM.$(function ($) {
    CRM.$('#mandate_id').change(function () {
      if ($(this).val() == 0) {
        var formURL = CRM.url('civicrm/direct_debit/mandate/create', {
          reset: 1,
          contact_id: CRM.$('#contact_id').val(),
        });
        CRM.loadForm(formURL, {
          dialog: {width: 600, height: 0}
        }).on('crmFormSuccess', function () {
          CRM.vars.coreForm.contact_id = CRM.$('#contact_id').val();
          CRM.refreshParent('#mandate_id');
        });
      }
    });
  });
  {/literal}
</script>
