<script type="text/javascript">
  {literal}
  CRM.$(function ($) {
    CRM.$('#mandate_id').change(function () {
      if (typeof CRM.vars.coreForm.contact_id == 'undefined') {
        CRM.vars.coreForm.contact_id = CRM.$('#contact_id').val();
      }

      if ($(this).val() == 0) {
        var formURL = CRM.url('civicrm/direct_debit/mandate/create', {
          reset: 1,
          contact_id: CRM.vars.coreForm.contact_id,
        });
        CRM.loadForm(formURL, {
          dialog: {width: 600, height: 0}
        }).on('crmFormSuccess', function () {
          CRM.refreshParent('#mandate_id');
        });
      }
    });

    if (typeof CRM.vars.coreForm != 'undefined') {
      if (typeof CRM.vars.coreForm.selected_mandate_id != 'undefined') {
        var optionSelector = '#mandate_id option[value=' + CRM.vars.coreForm.selected_mandate_id + ']';
        CRM.$(optionSelector).attr('selected', 'selected');
      }
    }
  });
  {/literal}
</script>
