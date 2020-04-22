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
        <div class="float-right">
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
          var recordBAO = 'CRM_Batch_BAO_Batch';
          saveRecord(recordID, op, recordBAO, null);
        }
      }
    });
  } else {
    var recordBAO = 'CRM_Batch_BAO_Batch';
    if (op == 'assign' || op == 'remove') {
      recordBAO = 'CRM_Batch_BAO_EntityBatch';
    }
    var entityID = {/literal}"{$entityID}"{literal};
    CRM.$('#mark_x_' + recordID).closest('tr').block({message: {/literal}'{ts escape="js"}Updating{/ts}'{literal}});

    saveRecord(recordID, op, recordBAO, entityID);
  }
}

function noServerResponse() {
  CRM.alert({/literal}'{ts escape="js"}No response from the server. Check your internet connection and try reloading the page.{/ts}', '{ts escape="js"}Network Error{/ts}'{literal}, 'error');
}

function saveRecord(recordID, op, recordBAO, entityID) {

  if (op == 'export') {
    window.location.href = CRM.url('civicrm/direct_debit/batch/export', {reset: 1, id: recordID, status: 1});
    return;
  }

  var postUrl = {/literal}"{crmURL p='civicrm/ajax/rest' h=0 q='className=CRM_ManualDirectDebit_Page_AJAX&fnName=assignRemove'}"{literal};
  //post request and get response

  CRM.$.post( postUrl, { records: [recordID], recordBAO: recordBAO, op:op, entityID:entityID, key: {/literal}"{crmKey name='civicrm/ajax/ar'}"{literal},  originator_number: {/literal}"{$originator_number}"{literal} }, function( html ){
    //this is custom status set when record update success.
    if (html.status == 'record-updated-success') {
      if (op == 'discard') {
        window.location.href = CRM.url('civicrm/direct_debit/batch-list', 'reset=1&type_id=' + {/literal}"{$batchType_id}"{literal});
      }
      else {
        buildTransactionSelectorAssign( true );
        buildTransactionSelectorRemove();
      }
    }
    else {
      CRM.alert(html.status);
    }
  },
  'json').error(noServerResponse);
}
</script>
{/literal}
