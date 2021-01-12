<tr>
    {include file="CRM/Core/DatePickerRangeWrapper.tpl" fieldName="receive_date" colspan="2"}
</tr>
<tr>
  <td>
    <label>{ts}Contribution Amounts{/ts}</label> <br/>
      {$form.contribution_amount_low.label}
      {$form.contribution_amount_low.html} &nbsp;&nbsp;
      {$form.contribution_amount_high.label}
      {$form.contribution_amount_high.html} </td>
  <td>
    <label>{$form.contribution_status_id.label}</label> <br/>
      {$form.contribution_status_id.html} </td>
</tr>
<tr>
  <td>
    <label>{ts}Currency{/ts}</label> <br/>
      {$form.contribution_currency_type.html|crmAddClass:twenty}
  </td>
    {if $form.contribution_batch_id.html }
      <td>
          {$form.contribution_batch_id.label}<br/>
          {$form.contribution_batch_id.html}
      </td>
    {/if}
</tr>
<tr>
  <td>
    <div class="float-left">
      <label>{$form.contribution_payment_instrument_id.label}</label> <br/>
        {$form.contribution_payment_instrument_id.html|crmAddClass:twenty}
    </div>
  </td>
</tr>
<tr>
  <td>
    <label>{ts}Financial Type{/ts}</label> <br/>
      {$form.financial_type_id.html|crmAddClass:twenty}
  </td>
</tr>
<tr>
  <td>
      {$form.contribution_product_id.label} <br/>
      {$form.contribution_product_id.html|crmAddClass:twenty}
  </td>
</tr>