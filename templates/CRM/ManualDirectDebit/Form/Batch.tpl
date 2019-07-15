<div class="crm-block crm-form-block crm-create-direct-debit-form-block">
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
  <table class="form-layout-compressed">
    <tbody>
      <tr>
        <td class="label">
          <label>{ts}Batch ID{/ts}:</label>
        </td>
        <td>{$batch_id}</td>
      </tr>
      <tr>
        <td class="label">
          <label>{$form.title.label}</label>
        </td>
        <td>{$form.title.html}</td>
      </tr>
      <tr>
        <td class="label">
          <label>{ts}Batch Type{/ts}</label>
        </td>
        <td>{$batch_type.label}</td>
      </tr>
      <tr>
        <td class="label">
          <label>{$form.originator_number.label}</label>
        </td>
        <td>{$form.originator_number.html}</td>
      </tr>
    </tbody>
  </table>
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>
