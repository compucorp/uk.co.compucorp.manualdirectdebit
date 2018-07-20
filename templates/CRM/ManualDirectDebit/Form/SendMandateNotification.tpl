<script type="text/javascript">
  {literal}
  CRM.$(function($) {
    CRM.$('#send_mandate_update_notification_to_the_contact').insertAfter(CRM.$('.custom-group.custom-group-direct_debit_mandate .spacer'));
  });
  {/literal}
</script>

<table>
  <tr id="send_mandate_update_notification_to_the_contact">
    <td class="label">
      {$form.send_mandate_update_notification_to_the_contact.label}
      {$form.send_mandate_update_notification_to_the_contact.html}
    </td>
  </tr>
</table>
