
<h3 style="margin-top: 30px;">{ts}Added to batch{/ts}:</h3>
{if in_array($batchStatus, array('Open', 'Reopened')) && $action eq 2} {* Add / remove transactions only allowed for Open/Reopened batches *}
  <div class="form-layout-compressed">{$form.trans_remove.html}&nbsp;{$form.rSubmit.html}</div>
{/if}
<div id="ltype">
  <div class="form-item">
      {strip}
        <table id="crm-transaction-selector-remove-{$entityID}" cellpadding="0" cellspacing="0" border="0">
          <thead>
          <tr>
            {if in_array($batchStatus, array('Open', 'Reopened'))  && $action eq 2}<th class="crm-transaction-checkbox">{$form.toggleSelects.html}</th>{/if}
            <th class="crm-contact-id">{ts}Contact ID{/ts}</th>
            <th class="crm-name">{ts}Account Holder Name{/ts}</th>
            <th class="crm-sort-code">{ts}Sort code{/ts}</th>
            <th class="crm-account-number">{ts}Account Number{/ts}</th>
            <th class="crm-amount">{ts}Amount{/ts}</th>
            <th class="crm-reference-number">{ts}Reference Number{/ts}</th>
            <th class="crm-transaction-type">{ts}Transaction Type{/ts}</th>
            {if $showReceiveDateColumn}
            <th class="crm-receive-date">{ts}Received Date{/ts}</th>
            {/if}
            <th class="crm-action">{ts}Action{/ts}</th>
          </tr>
          </thead>
        </table>
      {/strip}
  </div>
</div>
<br/>

{if in_array($batchStatus, array('Open', 'Reopened')) && $action eq 2}
  {if $showFilters == TRUE}
    <div class="crm-form-block crm-search-form-block">
      <div class="crm-accordion-wrapper crm-batch_transaction_search-accordion collapsed">
        <div class="crm-accordion-header crm-master-accordion-header">
          {ts}Edit Search Criteria{/ts}
        </div>
        <div class="crm-accordion-body">
          <div id="manualDirectDebitSearchForm" class="crm-block crm-form-block crm-manual-direct-debit-search-form-block">
            <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>
            <table class="form-layout">
              <tr>
                <td class="font-size12pt" colspan="2">
                  {$form.sort_name.label}<br>
                  {$form.sort_name.html|crmAddClass:'twenty'}
                </td>
              </tr>
              <tr>
              {if $form.contact_tags}
                <td>
                  <label>{ts}Contributor Tag(s){/ts}</label><br>
                  {$form.contact_tags.html}
                </td>
                {else}
                <td>&nbsp;</td>
              {/if}
              {if $form.group}
                <td><label>{ts}Contributor Group(s){/ts}</label><br>
                  {$form.group.html}
                </td>
                {else}
                <td>&nbsp;</td>
              {/if}
              </tr>
              {include file="CRM/ManualDirectDebit/Form/Search/Common.tpl"}
            </table>
      <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
          </div>
        </div>
      </div>
    </div>
  {/if}
  <h3>{$tableTitle}:</h3>
  <div class="form-layout-compressed">{$form.trans_assign.html}&nbsp;{$form.submit.html}</div>
  <div id="ltype">
    <div class="form-item">
    {strip}
      <table id="crm-transaction-selector-assign-{$entityID}" cellpadding="0" cellspacing="0" border="0">
        <thead>
          <tr>
            <th class="crm-transaction-checkbox">{$form.toggleSelect.html}</th>
            <th class="crm-contact-id">{ts}Contact ID{/ts}</th>
            <th class="crm-name">{ts}Account Holder Name{/ts}</th>
            <th class="crm-sort-code">{ts}Sort code{/ts}</th>
            <th class="crm-account-number">{ts}Account Number{/ts}</th>
            <th class="crm-amount">{ts}Amount{/ts}</th>
            <th class="crm-reference-number">{ts}Reference Number{/ts}</th>
            <th class="crm-transaction-type">{ts}Transaction Type{/ts}</th>
            {if $showReceiveDateColumn}
            <th class="crm-receive-date">{ts}Received Date{/ts}</th>
            {/if}
            <th class="crm-action">{ts}Action{/ts}</th>
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

  hideTimeFieldFromDatePicker();
  setDefaultFilterValues();

  var batchStatus = {/literal}{$statusID}{literal};
  {/literal}{if $validStatus}{literal}
    buildTransactionSelectorAssign();
    buildTransactionSelectorRemove();

    CRM.$('#_qf_BatchTransaction_submit-bottom, #_qf_BatchTransaction_submit-top').click( function() {
      buildTransactionSelectorAssign();
      return false;
    });

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

  hideSearchFields();
});

function hideTimeFieldFromDatePicker() {
  CRM.$('input.crm-form-time').hide();
}

function hideSearchFields() {
  var fieldsToHide  = [
    '#s2id_contribution_batch_id',
    '#s2id_contribution_currency_type',
  ];

  CRM.$.each(fieldsToHide, function (index, field) {
    CRM.$(field).parent('td').hide();
  });
}

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
  var sourceUrl = {/literal}'{crmURL p="civicrm/ajax/rest" h=0 q="className=CRM_ManualDirectDebit_Page_AJAX&fnName=getInstructionTransactionsList&snippet=4&context=instructionBatch&entityID=$entityID&entityTable=$entityTable&notPresent=1&statusID=$statusID&search=1"}'{literal};

  var ZeroRecordText = '<div class="status messages">{/literal}{ts escape="js"}None found.{/ts}{literal}</li></ul></div>';

  var columns = [
    {sClass:'crm-transaction-checkbox', bSortable:false, mData: "check"},
    {sClass:'crm-contact-id', bSortable:false, mData: "contact_id"},
    {sClass:'crm-name', mData: "name"},
    {sClass:'crm-sort-code', mData: "sort_code"},
    {sClass:'crm-account-number', mData: "account_number"},
    {sClass:'crm-amount', mData: "amount"},
    {sClass:'crm-reference-number', mData: "reference_number"},
    {sClass:'crm-transaction-type', mData: "transaction_type"},
    ('{/literal}{$showReceiveDateColumn}{literal}' ? {sClass:'crm-receive-date', mData: "receive_date"} : undefined),
    {sClass:'crm-action', mData: "action"},
  ].filter(Boolean);

  var crmBatchSelector = CRM.$('#crm-transaction-selector-assign-{/literal}{$entityID}{literal}').dataTable({
    "bDestroy"   : true,
    "bFilter"    : false,
    "bAutoWidth" : false,
    "lengthMenu": [ 10, 25, 50, 100, 250, 500, 1000, 2000 ],
    "aaSorting"  : [],
    "aoColumns"  : columns,
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

      var searchData = {/literal}{$searchData}{literal};
      aoData = aoData.concat(searchData);


      CRM.$('#manualDirectDebitSearchForm :input').each(function() {
        if (CRM.$(this).val()) {
          aoData.push(
            {name:CRM.$(this).attr('id'), value: CRM.$(this).val()}
          );
          CRM.$(':radio, :checkbox').each(function() {
            if (CRM.$(this).is(':checked')) {
              aoData.push( { name: CRM.$(this).attr('name'), value: CRM.$(this).val() } );
            }
          });
        }
      });

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
  var sourceUrl = {/literal}'{crmURL p="civicrm/ajax/rest" h=0 q="className=CRM_ManualDirectDebit_Page_AJAX&fnName=getInstructionTransactionsList&snippet=4&context=financialBatch&entityID=$entityID&entityTable=$entityTable&statusID=$statusID"}'{literal};

  var columns = [
    {/literal} {if in_array($batchStatus, array('Open', 'Reopened')) && $action eq 2}{literal} {sClass:'crm-transaction-checkbox', bSortable:false, mData: "check"}, {/literal}{/if}{literal}
    {sClass:'crm-contact-id', bSortable:false, mData: "contact_id"},
    {sClass:'crm-name', mData: "name"},
    {sClass:'crm-sort-code', mData: "sort_code"},
    {sClass:'crm-account-number', mData: "account_number"},
    {sClass:'crm-amount', mData: "amount"},
    {sClass:'crm-reference-number', mData: "reference_number"},
    {sClass:'crm-transaction-type', mData: "transaction_type"},
    ('{/literal}{$showReceiveDateColumn}{literal}' ? {sClass:'crm-receive-date', mData: "receive_date"} : undefined),
    {sClass:'action', mData: "action"}
  ].filter(Boolean);

  var crmBatchSelector = CRM.$('#crm-transaction-selector-remove-{/literal}{$entityID}{literal}').dataTable({
    "bDestroy"   : true,
    "bFilter"    : false,
    "bAutoWidth" : false,
    "aaSorting"  : [],
    "aoColumns"  : columns,
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
  var postUrl = {/literal}"{crmURL p='civicrm/ajax/rest' h=0 q="className=CRM_ManualDirectDebit_Page_AJAX&fnName=bulkAssignRemove&entityID=$entityID&entityTable=$entityTable" }"{literal};
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
      buildTransactionSelectorAssign();
      buildTransactionSelectorRemove();
      batchSummary({/literal}{$entityID}{literal});
    }
    else {
      CRM.alert(data.status);
    }
  }, 'json');
}

function contactRecurContribution(recId, cid) {
  var url = CRM.url(
    'civicrm/contact/view/contribution',
    {
      reset: '1',
      id: recId,
      cid: cid,
      context: 'contribution',
      action: 'view',
      selectedChild: 'contribute'
    }
  );
  CRM.loadPage(url);
  return false;
}

function setDefaultFilterValues() {
  // Payment method
  // Allow 'Direct debit' option only.
  CRM.api3('OptionValue', 'getsingle', {
    "return": ["value"],
    "name": "direct_debit",
    "option_group_id": "payment_instrument"
  }).done(function(result) {
    cj('#contribution_payment_instrument_id').select2('val', [result.value]);
    cj('#contribution_payment_instrument_id').select2().enable(false);
  });

  // Contribution Status
  // Allow 'Pending' contributions only.
  cj('#contribution_status_id').select2('val', [2]);
  cj('#contribution_status_id').select2().enable(false);

  // Date received
  cj('#receive_date_relative').select2('val', 0).trigger('change');
  cj('#receive_date_high').next().datepicker('setDate', new Date()).trigger('change');

  cj('#receive_date_relative').on('change.select2', function(e){
    if(e.val == "0") {
      cj('#receive_date_high').next().datepicker('setDate', new Date()).trigger('change');
    }
  });

  // Contribution Recur Status
  // Set all options except 'Cancelled'.
  cj('#contribution_recur_contribution_status_id').select2('val', [1, 2, 4, 5, 6, 7, 8, 9, 10]);
  cj('#contribution_recur_contribution_status_id').select2().enable(false);
}

</script>
{/literal}
