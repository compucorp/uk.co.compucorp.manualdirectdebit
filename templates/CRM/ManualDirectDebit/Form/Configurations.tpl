<div class="crm-block crm-form-block crm-direct-debit-configuration-form-block">
  <div class="crm-submit-buttons">
    {include file="CRM/common/formButtons.tpl" location="bottom"}
  </div>
  <h3>{ts}Mandate Config{/ts}</h3>
  <table class="form-layout-compressed">
    <tbody>
      {foreach from=$mandateConfigSection item=elementName}
        <tr>
          <td class="label">{$form.$elementName.label}</td>
          <td>{$form.$elementName.html}
            {if $fieldsWithHelp.$elementName}{help id=$form.$elementName.name}{/if}
          </td>
        </tr>
      {/foreach}
    </tbody>
  </table>
  <h3>{ts}Payment Config{/ts}</h3>
  <table class="form-layout-compressed">
    <tbody>
      {foreach from=$paymentConfigSection item=elementName}
        <tr>
          <td class="label">{$form.$elementName.label}</td>
          <td>{$form.$elementName.html}
            {if $fieldsWithHelp.$elementName}{help id=$form.$elementName.name}{/if}
          </td>
        </tr>
      {/foreach}
    </tbody>
  </table>
  <h3>Instalment Config</h3>
  <table class="form-layout-compressed">
    <tbody>
    {foreach from=$instalmentConfigSection item=elementName}
      <tr>
        <td class="label">{$form.$elementName.label}</td>
        <td>{$form.$elementName.html}
          {if $fieldsWithHelp.$elementName}{help id=$form.$elementName.name}{/if}
        </td>
      </tr>
    {/foreach}
    </tbody>
  </table>
  <h3>{ts}Reminder Config{/ts}</h3>
  <table class="form-layout-compressed">
    <tbody>
      {foreach from=$reminderConfigSection item=elementName}
        <tr>
          <td class="label">{$form.$elementName.label}</td>
          <td>{$form.$elementName.html}
            {if $fieldsWithHelp.$elementName}{help id=$form.$elementName.name}{/if}
          </td>
        </tr>
      {/foreach}
    </tbody>
  </table>
  <h3>{ts}Batch Config{/ts}</h3>
  <table class="form-layout-compressed">
    <tbody>
    {foreach from=$batchConfigSection item=elementName}
      <tr>
        <td class="label">{$form.$elementName.label}</td>
        <td>{$form.$elementName.html}
            {if $fieldsWithHelp.$elementName}{help id=$form.$elementName.name}{/if}
        </td>
      </tr>
    {/foreach}
    </tbody>
  </table>
  <h3 class="title">{ts}Batch and code transition{/ts}</h3>
  <table style="width:100%" class="row-highlight">
    <tr class="crm-admin-options crm-entity odd-row">
      <th></th>
      <th>{ts}Code to Batch{/ts}</th>
      <th></th>
      <th>{ts}Code to transit to after batched{/ts}</th>
    </tr>
    <tr class="crm-admin-options crm-entity even-row">
      <td>{ts}New instruction batched processing{/ts}</td>
      <td>0N</td>
      <td>-></td>
      <td>01</td>
    </tr>
    <tr class="crm-admin-options crm-entity odd-row">
      <td>{ts}Payment collection batched processing{/ts}</td>
      <td>01,17</td>
      <td>-></td>
      <td>17</td>
    </tr>
    <tr class="crm-admin-options crm-entity even-row">
      <td>{ts}Codes for inactivity{/ts} {help id="inactivity_code"}</td>
      <td>0C</td>
      <td></td>
      <td></td>
    </tr>
  </table>
  <div class="crm-submit-buttons">
    {include file="CRM/common/formButtons.tpl" location="bottom"}
  </div>
</div>
