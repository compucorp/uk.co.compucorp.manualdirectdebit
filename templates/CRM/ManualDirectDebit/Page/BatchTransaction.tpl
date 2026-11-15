<div id="enableDisableStatusMsg" class="crm-container" style="display:none;"></div>
{if $batchInfo}
  <div class="batch-transaction crm-results-block">
    <table class="batchPaginator selector row-highlight">
      <thead class="sticky">
      <tr>
        <th class="crm-batch-name">{ts}Batch Name{/ts}</th>
        <th class="crm-batch-item_count">{ts}{$type} Count{/ts}</th>
        <th class="crm-batch-status">{ts}Status{/ts}</th>
        <th class="crm-batch-created_date">{ts}Created Date{/ts}</th>
        <th class="crm-batch-created_by">{ts}Created by{/ts}</th>
      </tr>
      </thead>
      <tbody>
      <tr class="crm-entity" data-entity="batch" data-id="{$batch.id}">
        <td class="crm-batch-name">
            {$batchInfo.name}
        </td>
        <td class="crm-batch-item_count">
            {$batchInfo.transaction_count}
        </td>
        <td class="crm-batch-status">
            {$batchInfo.batch_status}
        </td>
        <td class="crm-batch-created_date">
            {$batchInfo.created_date}
        </td>
        <td class="crm-batch-created_by">
            {$batchInfo.created_by}
        </td>
      </tr>
      </tbody>
    </table>
  </div>
{/if}
<div class="crm-submit-buttons">
  {if $batchInfo}
    <div class="float-left">
      {$form.export_batch.html}
    </div>
  {else}
    <div class="float-left">
      {$form.save_batch.html}
    </div>
    <div class="float-left">
      {$form.save_and_export_batch.html}
    </div>
  {/if}

    {if in_array($batchStatus, array('Open', 'Reopened'))  && $action eq 4}
        <div class="float-right" style="width:200px;">
            {$form.discard.html}
            {$form.submitted.html}
        </div>
    {/if}
</div>

{include file="CRM/ManualDirectDebit/Form/BatchTransaction.tpl"}

{literal}
<script type="text/javascript">

CRM.$(function($) {
  var entityID = {/literal}{$entityID}{literal};
  CRM.$('#discard').click( function() {
    assignRemove(entityID, 'discard');
    return false;
  });
  CRM.$('#submitted').click( function() {
      submitBatch(entityID);
    return false;
  });
});

function submitBatch(batchId) {
  CRM.$("#enableDisableStatusMsg").dialog({
    title: {/literal}'{ts escape="js"}Submit Batch{/ts}'{literal},
    modal: true,
    open: function () {
      var msg = {/literal}{if $submittedMessage}"{$submittedMessage}"{else}"{ts escape="js"}Are you sure you want to submit this batch? This process is not revertable.{/ts}"{/if}{literal};
      CRM.$('#enableDisableStatusMsg').show().html(msg);
    },
    buttons: {
      {/literal}"{ts escape='js'}Cancel{/ts}"{literal}: function () {
        CRM.$(this).dialog("close");
      },
      {/literal}"{ts escape='js'}Submit{/ts}"{literal}: function () {
        CRM.$(this).dialog("close");
        window.location.href = CRM.url('civicrm/direct_debit/batch/submit', {batchId: batchId});
      }
    }
  });
}

function assignRemove(recordID, op) {
  if (op == 'discard') {
    CRM.$("#enableDisableStatusMsg").dialog({
      title: {/literal}'{ts escape="js"}Discard Batch{/ts}'{literal},
      modal: true,
      open: function () {
        var msg = {/literal}'{ts escape="js"}Are you sure you want to discard this batch?{/ts}'{literal};
        CRM.$('#enableDisableStatusMsg').show().html(msg);
      },
      buttons: {
        {/literal}"{ts escape='js'}Cancel{/ts}"{literal}: function () {
          CRM.$(this).dialog("close");
        },
        {/literal}"{ts escape='js'}Discard{/ts}"{literal}: function () {
          CRM.$(this).dialog("close");
          CRM.api4('Batch', 'update', {
            where: [['id', '=', recordID]],
            values: {'status_id:name': 'Discarded'}
          }).then(function () {
            window.location.href = CRM.url('civicrm/direct_debit/batch-list', 'reset=1&type_id=' + {/literal}"{$batchType_id}"{literal});
          });
        }
      }
    });
  } else if (op == 'export') {
    window.location.href = CRM.url('civicrm/direct_debit/batch/export', {reset: 1, id: recordID, status: 1});
  } else {
    var entityID = {/literal}"{$entityID}"{literal};
    var entityTable = {/literal}"{$entityTable}"{literal};
    CRM.$('#mark_x_' + recordID).closest('tr').block({message: {/literal}'{ts escape="js"}Updating{/ts}'{literal}});

    var apiCall;
    if (op == 'assign') {
      apiCall = CRM.api4('EntityBatch', 'save', {
        records: [{
          batch_id: entityID,
          entity_table: entityTable,
          entity_id: recordID
        }]
      });
    }
    else if (op == 'remove') {
      apiCall = CRM.api4('EntityBatch', 'delete', {
        where: [
          ['batch_id', '=', entityID],
          ['entity_table', '=', entityTable],
          ['entity_id', '=', recordID]
        ]
      });
    }

    if (apiCall) {
      apiCall.then(function () {
        buildTransactionSelectorAssign(true);
        buildTransactionSelectorRemove();
      });
    }
  }
}
</script>
{/literal}

<style>
  {literal}
  .crm-button.crm-button-type-submitted.crm-buttonsubmitted.crm-form-xbutton,
  .crm-button.crm-button-type-discard.crm-buttondiscard.crm-form-xbutton {
    float: left !important;
  }
  {/literal}
</style>
