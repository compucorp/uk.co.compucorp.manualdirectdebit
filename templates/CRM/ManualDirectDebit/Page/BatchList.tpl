<div id="enableDisableStatusMsg" class="crm-container" style="display:none;"></div>

<div class="crm-form-block crm-search-form-block">
  <div class="page-civicrm-group">
    <form id="searchForm">
      <div class="crm-section">
        <div class="content float-left">
          Batch Type: {html_options name="type_id" id="type_id" class="crm-select2 crm-form-select" options=$batchTypes selected=$type_id}
        </div>
        <div class="content float-left">
          From: <input data-crm-datepicker="{ldelim}&quot;time&quot;:false, &quot;allowClear&quot;:false{rdelim}" aria-label="From" name="created_date_from" type="text" value="{$created_date_from}" id="created_date_from" class="crm-form-text crm-hidden-date" />
          To: <input data-crm-datepicker="{ldelim}&quot;time&quot;:false, &quot;allowClear&quot;:false{rdelim}" aria-label="To" name="created_date_to" type="text" value="{$created_date_from}" id="created_date_to" class="crm-form-text crm-hidden-date" />
        </div>
        <div class="float-left">&nbsp;</div>
        <div>
          <span class="crm-button crm-button-type-refresh crm-button_qf_Basic_refresh crm-i-button">
            <i class="crm-i fa-check" aria-hidden="true"></i>
            <input class="crm-form-submit default validate" crm-icon="fa-check" name="_qf_Basic_refresh" value="Search" type="submit" id="_qf_Basic_refresh">
          </span>
        </div>
        <div class="clear"></div>
      </div>
    </form>
  </div>
</div>
<div class="batch-list crm-results-block">
  {include file="CRM/common/pager.tpl" location="top"}
    {strip}
  <table class="batchPaginator selector row-highlight" id="crm-transaction-selector-assign-{$entityID}" cellpadding="0" cellspacing="0" border="0">
    <thead class="sticky">
    <tr>
      <th class="crm-batch-name">{ts}Batch Name{/ts}</th>
      <th class="crm-batch-name">{ts}Batch Type{/ts}</th>
      <th class="crm-batch-item_count">{ts}{$type} Count{/ts}</th>
      <th class="crm-batch-status">{ts}Status{/ts}</th>
      <th class="crm-batch-created_date">{ts}Created Date{/ts}</th>
      <th class="crm-batch-created_by">{ts}Created by{/ts}</th>
      <th class="crm-batch-transaction-links">{ts}Action{/ts}</th>
    </tr>
    </thead>
    <tbody>
    {foreach from=$batches item=batch}
      <tr class="crm-entity" data-entity="batch" data-id="{$batch.id}">
        <td class="crm-batch-name">
          {$batch.name}
        </td>
        <td class="crm-batch-item_count">
          {$batch.batch_type_name}
        </td>
        <td class="crm-batch-item_count">
          {$batch.transaction_count}
        </td>
        <td class="crm-batch-status">
          {$batch.batch_status}
        </td>
        <td class="crm-batch-created_date">
          {$batch.created_date}
        </td>
        <td class="crm-batch-created_by">
          {$batch.created_by}
        </td>
        <td class="crm-batch-links">
          {$batch.action}
        </td>
      </tr>
    {/foreach}
    </tbody>
  </table>
    {/strip}
  {include file="CRM/common/pager.tpl" location="bottom"}
</div>

{literal}
<script type="text/javascript">

  CRM.$('.batchPaginator').dataTable({
    destroy: true,
    bFilter: false,
    bAutoWidth: false,
    bProcessing: false,
    bLengthChange: true,
    paging: false,
    bJQueryUI: true,
    order: []
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
            saveRecord(recordID, op);
          }
        }
      });
    }
    else {
      saveRecord(recordID, op);
    }
  }

  function noServerResponse() {
    CRM.alert({/literal}'{ts escape="js"}No response from the server. Check your internet connection and try reloading the page.{/ts}', '{ts escape="js"}Network Error{/ts}'{literal}, 'error');
  }

  function saveRecord(recordID, op) {
    if (op == 'export') {
      window.location.href = CRM.url('civicrm/direct_debit/batch/export', {reset: 1, id: recordID, status: 1});
      return;
    }
    var postUrl = {/literal}"{crmURL p='civicrm/ajax/rest' h=0 q='className=CRM_ManualDirectDebit_Page_AJAX&fnName=assignRemove'}"{literal};
    //post request and get response
    CRM.$.post(postUrl, {
        records: [recordID],
        recordBAO: 'CRM_Batch_BAO_Batch',
        op: op,
        key: {/literal}"{crmKey name='civicrm/ajax/ar'}"{literal}
      }, function (html) {
        //this is custom status set when record update success.
        if (html.status == 'record-updated-success') {
          location.reload();
        }
        else {
            CRM.alert(html.status);
        }
      },
      'json').error(noServerResponse);
  }

  function batchSummary(entityID) {
    var postUrl = {/literal}"{crmURL p='civicrm/ajax/rest' h=0 q='className=CRM_Financial_Page_AJAX&fnName=getBatchSummary'}"{literal};
    //post request and get response
    CRM.$.post(postUrl, {batchID: entityID}, function (html) {
        CRM.$.each(html, function (i, val) {
          CRM.$("#row_" + i).html(val);
        });
      },
    'json');
  }
</script>
{/literal}
