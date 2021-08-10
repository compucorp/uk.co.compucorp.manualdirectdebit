<div class="crm-submit-buttons">
  <a href="/civicrm/contact/view/cd/edit?reset=1&amp;type=Individual&amp;groupID={$groupId}&amp;entityID={$contactId}&amp;cgcount={$cgcount}&amp;multiRecordDisplay=single&amp;mode=edit&amp;mandateId={$mandate_id}" class="button edit" id="edit_direct_debit_mandate_1" title="Edit Direct Debit Mandate">
    <span><i class="crm-i fa-pencil"></i> {ts}Edit{/ts} </span>
  </a>
  <a href="#" id="mandate_delete_btn" class="button delete" data-post="{ldelim}&quot;valueID&quot;: &quot;{$mandate_id}&quot;, &quot;groupID&quot;: &quot;{$groupId}&quot;, &quot;contactId&quot;: &quot;{$contactId}&quot;, &quot;key&quot;: &quot;ad6ba0055f16b015e7228c2eba077526&quot;{rdelim}" title="Delete Direct Debit Mandate">
    <i class="crm-i fa-trash" aria-hidden="true"></i>
    {ts}Delete{/ts}
  </a>
</div>
