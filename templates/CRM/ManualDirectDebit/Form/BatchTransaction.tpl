
<h3>{ts}Added to batch{/ts}:</h3>
{if in_array($batchStatus, array('Open', 'Reopened'))} {* Add / remove transactions only allowed for Open/Reopened batches *}
  <br /><div class="form-layout-compressed">{$form.trans_remove.html}&nbsp;{$form.rSubmit.html}</div><br/>
{/if}
<div id="ltype">
  <div class="form-item">
      {strip}
        <table id="crm-transaction-selector-remove-{$entityID}" cellpadding="0" cellspacing="0" border="0">
          <thead>
          <tr>
            <th class="crm-transaction-checkbox">{if in_array($batchStatus, array('Open', 'Reopened'))}{$form.toggleSelects.html}{/if}</th>
            <th class="crm-contact-id">{ts}Contact ID{/ts}</th>
            <th class="crm-name">{ts}Account Holder Name{/ts}</th>
            <th class="crm-sort-code">{ts}Sort code{/ts}</th>
            <th class="crm-account-number">{ts}Account Number{/ts}</th>
            <th class="crm-amount">{ts}Amount{/ts}</th>
            <th class="crm-reference-number">{ts}Reference Number{/ts}</th>
            <th class="crm-transaction-type">{ts}Transaction Type{/ts}</th>
          </tr>
          </thead>
        </table>
      {/strip}
  </div>
</div>
<br/>

{if in_array($batchStatus, array('Open', 'Reopened'))}
  <h3>{ts}Available instructions{/ts}:</h3>
  <div class="form-layout-compressed">{$form.trans_assign.html}&nbsp;{$form.submit.html}</div>
  <div id="ltype">
    <div class="form-item">
    {strip}
      <table id="crm-transaction-selector-assign-{$entityID}" cellpadding="0" cellspacing="0" border="0">
        <thead>
        <tr>
          <th class="crm-transaction-checkbox">{if in_array($batchStatus, array('Open', 'Reopened'))}{$form.toggleSelect.html}{/if}</th>
          <th class="crm-contact-id">{ts}Contact ID{/ts}</th>
          <th class="crm-name">{ts}Account Holder Name{/ts}</th>
          <th class="crm-sort-code">{ts}Sort code{/ts}</th>
          <th class="crm-account-number">{ts}Account Number{/ts}</th>
          <th class="crm-amount">{ts}Amount{/ts}</th>
          <th class="crm-reference-number">{ts}Reference Number{/ts}</th>
          <th class="crm-transaction-type">{ts}Transaction Type{/ts}</th>
        </tr>
        </thead>
      </table>
    {/strip}
    </div>
</div>
{/if}

{literal}
<script type="text/javascript">
CRM.$(function($) {
  CRM.$('#_qf_BatchTransaction_submit-top, #_qf_BatchTransaction_submit-bottom').click(function() {
    CRM.$('.crm-batch_transaction_search-accordion:not(.collapsed)').crmAccordionToggle();
  });
  var batchStatus = {/literal}{$statusID}{literal};
  {/literal}{if $validStatus}{literal}
    // build transaction listing only for open/reopened batches
    buildTransactionSelectorAssign();
    buildTransactionSelectorRemove();

    CRM.$("#trans_assign").prop('disabled',true);
    CRM.$("#trans_remove").prop('disabled',true);
    CRM.$('#crm-transaction-selector-assign-{/literal}{$entityID}{literal} #toggleSelect').click( function() {
      enableActions('x');
    });
    CRM.$('#crm-transaction-selector-remove-{/literal}{$entityID}{literal} #toggleSelects').click( function() {
      enableActions('y');
    });
    CRM.$('#Go').click( function() {
      return selectAction("trans_assign","toggleSelect", "crm-transaction-selector-assign-{/literal}{$entityID}{literal} input[id^='mark_x_']");
    });
    CRM.$('#GoRemove').click( function() {
      return selectAction("trans_remove","toggleSelects", "crm-transaction-selector-remove-{/literal}{$entityID}{literal} input[id^='mark_y_']");
    });
    CRM.$('#Go').click( function() {
      if (CRM.$("#trans_assign" ).val() != "" && CRM.$("input[id^='mark_x_']").is(':checked')) {
        bulkAssignRemove('Assign');
      }
      return false;
    });
    CRM.$('#GoRemove').click( function() {
      if (CRM.$("#trans_remove" ).val() != "" && CRM.$("input[id^='mark_y_']").is(':checked')) {
        bulkAssignRemove('Remove');
      }
      return false;
    });
    CRM.$("#crm-transaction-selector-assign-{/literal}{$entityID}{literal} input[id^='mark_x_']").click( function() {
      enableActions('x');
    });
    CRM.$("#crm-transaction-selector-remove-{/literal}{$entityID}{literal} input[id^='mark_y_']").click( function() {
      enableActions('y');
    });

    CRM.$("#crm-transaction-selector-assign-{/literal}{$entityID}{literal} #toggleSelect").click( function() {
      toggleFinancialSelections('#toggleSelect', 'assign');
    });
    CRM.$("#crm-transaction-selector-remove-{/literal}{$entityID}{literal} #toggleSelects").click( function() {
      toggleFinancialSelections('#toggleSelects', 'remove');
    });
  {/literal}{else}{literal}
    buildTransactionSelectorRemove();
  {/literal}{/if}{literal}
});

function enableActions( type ) {
  if (type == 'x') {
    CRM.$("#trans_assign").prop('disabled',false);
  }
  else {
    CRM.$("#trans_remove").prop('disabled',false);
  }
}

function toggleFinancialSelections(toggleID, toggleClass) {
  var mark = 'x';
  if (toggleClass == 'remove') {
    mark = 'y';
  }
  if (CRM.$("#crm-transaction-selector-" + toggleClass + "-{/literal}{$entityID}{literal} " +	toggleID).is(':checked')) {
    CRM.$("#crm-transaction-selector-" + toggleClass + "-{/literal}{$entityID}{literal} input[id^='mark_" + mark + "_']").prop('checked',true);
  }
  else {
    CRM.$("#crm-transaction-selector-" + toggleClass + "-{/literal}{$entityID}{literal} input[id^='mark_" + mark + "_']").prop('checked',false);
  }
}

function buildTransactionSelectorAssign() {
  var sourceUrl = {/literal}'{crmURL p="civicrm/ajax/rest" h=0 q="className=CRM_ManualDirectDebit_Page_AJAX&fnName=getInstructionTransactionsList&snippet=4&context=instructionBatch&entityID=$entityID&notPresent=1&statusID=$statusID"}'{literal};
  var ZeroRecordText = '<div class="status messages">{/literal}{ts escape="js"}None found.{/ts}{literal}</li></ul></div>';

  crmBatchSelector1 = CRM.$('#crm-transaction-selector-assign-{/literal}{$entityID}{literal}').dataTable({
  "bDestroy"   : true,
  "bFilter"    : false,
  "bAutoWidth" : false,
  "lengthMenu": [ 10, 25, 50, 100, 250, 500, 1000, 2000 ],
  "aaSorting"  : [],
  "aoColumns"  : [
    {sClass:'crm-transaction-checkbox', bSortable:false},
    {sClass:'crm-contact-id', bSortable:false},
    {sClass:'crm-name'},
    {sClass:'crm-sort-code'},
    {sClass:'crm-account-number'},
    {sClass:'crm-amount'},
    {sClass:'crm-reference-number'},
    {sClass:'crm-transaction-type'}
  ],
  "bProcessing": true,
  "asStripClasses" : [ "odd-row", "even-row" ],
  "sPaginationType": "full_numbers",
  "sDom"       : '<"crm-datatable-pager-top"lfp>rt<"crm-datatable-pager-bottom"ip>',
  "bServerSide": true,
  "bJQueryUI": true,
  "sAjaxSource": sourceUrl,
  "iDisplayLength": 25,
  "oLanguage": {
    "sZeroRecords":  ZeroRecordText,
    "sProcessing":    {/literal}"{ts escape='js'}Processing...{/ts}"{literal},
    "sLengthMenu":    {/literal}"{ts escape='js'}Show _MENU_ entries{/ts}"{literal},
    "sInfo":          {/literal}"{ts escape='js'}Showing _START_ to _END_ of _TOTAL_ entries{/ts}"{literal},
    "sInfoEmpty":     {/literal}"{ts escape='js'}Showing 0 to 0 of 0 entries{/ts}"{literal},
    "sInfoFiltered":  {/literal}"{ts escape='js'}(filtered from _MAX_ total entries){/ts}"{literal},
    "sSearch":        {/literal}"{ts escape='js'}Search:{/ts}"{literal},
    "oPaginate": {
      "sFirst":    {/literal}"{ts escape='js'}First{/ts}"{literal},
      "sPrevious": {/literal}"{ts escape='js'}Previous{/ts}"{literal},
      "sNext":     {/literal}"{ts escape='js'}Next{/ts}"{literal},
      "sLast":     {/literal}"{ts escape='js'}Last{/ts}"{literal}
    }
  },
  "fnServerData": function ( sSource, aoData, fnCallback ) {

    aoData.push( { name: 'originator_number', value: "{/literal}{$originator_number}{literal}" } );
    aoData.push( { name: 'start_date', value: "{/literal}{$start_date}{literal}" } );
    aoData.push( { name: 'dd_code', value: "{/literal}{$dd_code}{literal}" } );

    CRM.$.ajax({
    "dataType": 'json',
    "type": "POST",
    "url": sSource,
    "data": aoData,
    "success": function(b) {
      fnCallback(b);
      toggleFinancialSelections('#toggleSelect', 'assign');
    }
    });
  }
});
	
}

function buildTransactionSelectorRemove( ) {
  var sourceUrl = {/literal}'{crmURL p="civicrm/ajax/rest" h=0 q="className=CRM_ManualDirectDebit_Page_AJAX&fnName=getInstructionTransactionsList&snippet=4&context=financialBatch&entityID=$entityID&statusID=$statusID"}'{literal};

  crmBatchSelector = CRM.$('#crm-transaction-selector-remove-{/literal}{$entityID}{literal}').dataTable({
  "bDestroy"   : true,
  "bFilter"    : false,
  "bAutoWidth" : false,
  "aaSorting"  : [],
  "aoColumns"  : [
    {sClass:'crm-transaction-checkbox', bSortable:false},
    {sClass:'crm-contact-id', bSortable:false},
    {sClass:'crm-name'},
    {sClass:'crm-sort-code'},
    {sClass:'crm-account-number'},
    {sClass:'crm-amount'},
    {sClass:'crm-reference-number'},
    {sClass:'crm-transaction-type'}
  ],
  "bProcessing": true,
  "asStripClasses" : [ "odd-row", "even-row" ],
  "sPaginationType": "full_numbers",
  "sDom"       : '<"crm-datatable-pager-top"lfp>rt<"crm-datatable-pager-bottom"ip>',
  "bServerSide": true,
  "bJQueryUI": true,
  "sAjaxSource": sourceUrl,
  "iDisplayLength": 25,
  "oLanguage": {
    "sProcessing":    {/literal}"{ts escape='js'}Processing...{/ts}"{literal},
    "sLengthMenu":    {/literal}"{ts escape='js'}Show _MENU_ entries{/ts}"{literal},
    "sInfo":          {/literal}"{ts escape='js'}Showing _START_ to _END_ of _TOTAL_ entries{/ts}"{literal},
    "sInfoEmpty":     {/literal}"{ts escape='js'}Showing 0 to 0 of 0 entries{/ts}"{literal},
    "sInfoFiltered":  {/literal}"{ts escape='js'}(filtered from _MAX_ total entries){/ts}"{literal},
    "sSearch":        {/literal}"{ts escape='js'}Search:{/ts}"{literal},
    "oPaginate": {
      "sFirst":    {/literal}"{ts escape='js'}First{/ts}"{literal},
      "sPrevious": {/literal}"{ts escape='js'}Previous{/ts}"{literal},
      "sNext":     {/literal}"{ts escape='js'}Next{/ts}"{literal},
      "sLast":     {/literal}"{ts escape='js'}Last{/ts}"{literal}
    }
  },
  "fnServerData": function (sSource, aoData, fnCallback) {
    CRM.$.ajax({
      "dataType": 'json',
      "type": "POST",
      "url": sSource,
      "data": aoData,
      "success": function(b) {
        fnCallback(b);
        toggleFinancialSelections('#toggleSelects', 'remove');
      }
    });
  }
});
}

function selectAction( id, toggleSelectId, checkId ) {
  if (CRM.$("#"+ id ).is(':disabled')) {
    return false;
  }
  else if (!CRM.$("#" + toggleSelectId).is(':checked') && !CRM.$("#" + checkId).is(':checked') && CRM.$("#" + id).val() != "") {
    CRM.alert ({/literal}'{ts escape="js"}Please select one or more contributions for this action.{/ts}'{literal});
    return false;
  }
  else if (CRM.$("#" + id).val() == "") {
    CRM.alert ({/literal}'{ts escape="js"}Please select an action from the drop-down menu.{/ts}'{literal});
    return false;
  }
}

function bulkAssignRemove( action ) {
  var postUrl = {/literal}"{crmURL p='civicrm/ajax/rest' h=0 q="className=CRM_ManualDirectDebit_Page_AJAX&fnName=bulkAssignRemove&entityID=$entityID" }"{literal};
  var fids = [];
  if (action == 'Assign') {
    CRM.$("input[id^='mark_x_']:checked").each( function () {
      var a = CRM.$(this).attr('id');
      fids.push(a);
    });
  }
  if (action == 'Remove') {
    CRM.$("input[id^='mark_y_']:checked").each( function () {
      var a = CRM.$(this).attr('id');
      fids.push(a);
    });
  }
  CRM.$.post(postUrl, { ID: fids, actions:action }, function(data) {
    //this is custom status set when record update success.
    if (data.status == 'record-updated-success') {
      buildTransactionSelectorAssign( true );
      buildTransactionSelectorRemove();
      batchSummary({/literal}{$entityID}{literal});
    }
    else {
      CRM.alert(data.status);
    }
  }, 'json');
}
</script>
{/literal}
