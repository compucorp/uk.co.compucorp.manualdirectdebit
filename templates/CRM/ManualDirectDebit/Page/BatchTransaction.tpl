<div id="enableDisableStatusMsg" class="crm-container" style="display:none;"></div>
<div class="crm-submit-buttons">{$form.export_batch.html}</div>

{include file="CRM/ManualDirectDebit/Form/BatchTransaction.tpl"}

{literal}
<script type="text/javascript">


function assignRemove(recordID, op) {
  var recordBAO = 'CRM_Batch_BAO_Batch';
  if (op == 'assign' || op == 'remove') {
    recordBAO = 'CRM_Batch_BAO_EntityBatch';   
  }
  var entityID = {/literal}"{$entityID}"{literal};
  CRM.$('#mark_x_' + recordID).closest('tr').block({message: {/literal}'{ts escape="js"}Updating{/ts}'{literal}});

  saveRecord(recordID, op, recordBAO, entityID);
}

function noServerResponse() {
  CRM.alert({/literal}'{ts escape="js"}No response from the server. Check your internet connection and try reloading the page.{/ts}', '{ts escape="js"}Network Error{/ts}'{literal}, 'error');
}

function saveRecord(recordID, op, recordBAO, entityID) {

  var postUrl = {/literal}"{crmURL p='civicrm/ajax/rest' h=0 q='className=CRM_ManualDirectDebit_Page_AJAX&fnName=assignRemove'}"{literal};
  //post request and get response
  CRM.$.post( postUrl, { records: [recordID], recordBAO: recordBAO, op:op, entityID:entityID, key: {/literal}"{crmKey name='civicrm/ajax/ar'}"{literal},  originator_number: {/literal}"{$originator_number}"{literal} }, function( html ){
    //this is custom status set when record update success.
    if (html.status == 'record-updated-success') {
      if (op == 'close') {
        window.location.href = CRM.url('civicrm/financial/financialbatches', 'reset=1&batchStatus=2');
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
