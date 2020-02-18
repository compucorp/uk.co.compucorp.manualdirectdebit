<table class="no-border">
  <tbody>
  <tr>
    <td id="direct_debit_information__" class="section-shown form-item">
      <div class="crm-accordion-wrapper  ">
        <div class="crm-accordion-header">
          {ts}Direct Debit Information{/ts}
        </div>
        <div class="crm-accordion-body">
          <table class="crm-info-panel">
            <tbody>
            <tr>
              <td class="label">{ts}Mandate ID{/ts}</td>
              <td class="html-adjust positionRelative">
                <span id="directDebitMandate"></span>
                <span class="newMandateButton"><a href="#" id="newDirectDebitMandate">{ts}Use a new mandate{/ts}</a></span>
            </tr>
            </tbody>
          </table>
        </div>
        <div class="clear"></div>
      </div>
    </td>
  </tr>
  </tbody>
</table>

<script type="text/javascript">
  {literal}
  CRM.$(function ($) {
    var urlData = CRM.urlData || {};
    CRM.$('#directDebitMandate').text( urlData.mandateId );

    var url = CRM.url(
      'civicrm/contact/view/cd',
      {
        reset: '1',
        gid: urlData.gid,
        cid: urlData.cid,
        recId: urlData.recId,
        multiRecordDisplay: 'single',
        mode: 'view'
      }
    );
    CRM.$('#directDebitMandate').click(function () {
      CRM.loadPage(url);
    });

    var newUrl = CRM.url(
      'civicrm/contact/view/cd/edit',
      {
        reset: '1',
        type: CRM.vars['uk.co.compucorp.manualdirectdebit'].contactType,
        groupID: urlData.gid,
        entityID: urlData.cid,
        cgcount: urlData.cgcount,
        multiRecordDisplay: 'single',
        mode: 'add',
        updatedRecId: urlData.recurringContribution,
      }
    );
    CRM.$('#newDirectDebitMandate').attr('href', newUrl);
  });
  {/literal}
</script>
