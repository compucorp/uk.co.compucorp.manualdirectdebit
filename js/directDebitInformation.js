CRM.$(function ($) {

  renderMandateCustomData();

  function renderMandateCustomData() {
    var urlData = CRM.urlData || {};

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

    var block = CRM.$('.crm-block.crm-content-block.crm-recurcontrib-view-block');
    block.append('' +
      '<table class="no-border">\n' +
      '   <tbody>' +
      '     <tr>\n' +
'             <td id="direct_debit_information__" class="section-shown form-item">\n' +
'               <div class="crm-accordion-wrapper  ">\n' +
'                 <div class="crm-accordion-header">\n' +
'                   Direct Debit Information\n' +
'                 </div>\n' +
'                 <div class="crm-accordion-body">\n' +
'                   <table class="crm-info-panel">\n' +
'                     <tbody>' +
      '                 <tr>\n' +
'                         <td class="label">Mandate ID</td>\n' +
'                         <td class="html-adjust">' + urlData.mandateId + '</td>\n' +
'                       </tr>\n' +
'                     </tbody>' +
      '             </table>\n' +
'                 </div>\n' +
'                 <div class="clear"></div>\n' +
'               </div>\n' +
'             </td>\n' +
      '     </tr>\n' +
      '   </tbody>' +
      '</table>');

    CRM.$('.crm-accordion-body').click(function () {
      CRM.loadPage(url);
    });
  }
});
