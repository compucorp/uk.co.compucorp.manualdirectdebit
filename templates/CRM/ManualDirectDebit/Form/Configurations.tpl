<h3>{ts}Mandate config{/ts}</h3>
{foreach from=$mandateConfigSection item=elementName}
    <div class="crm-section">
        <div class="label">{$form.$elementName.label}</div>
        <div class="content">{$form.$elementName.html}
            {if $fieldsWithHelp.$elementName}{help id=$form.$elementName.name}{/if}
        </div>
        <div class="clear"></div>
    </div>
{/foreach}

<h3>{ts}Payment config{/ts}</h3>
{foreach from=$paymentConfigSection item=elementName}
    <div class="crm-section">
        <div class="label">{$form.$elementName.label}</div>
        <div class="content">{$form.$elementName.html}
            {if $fieldsWithHelp.$elementName}{help id=$form.$elementName.name}{/if}
        </div>
        <div class="clear"></div>
    </div>
{/foreach}

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
