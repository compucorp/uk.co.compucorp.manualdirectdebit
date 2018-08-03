CRM.$('document').ready(function () {
  var customGroupTitle = CRM.$('.section-shown.form-item .crm-accordion-header').first().text().trim();
  if (customGroupTitle == "Direct Debit Mandate") {

    CRM.$('.form-item a.button').parent().hide();

    CRM.$('.crm-hover-button.crm-custom-value-del').each(function () {
      var mandateTitle = CRM.$(this).attr('title').split(' ');
      var cgCount = mandateTitle[mandateTitle.length - 1];

      var isAlreadyEditButtonAdd = CRM.$(this).next('#edit_direct_debit_mandate_' + cgCount).length != 1;
      if (isAlreadyEditButtonAdd) {
        var mandateData = JSON.parse(CRM.$(this).attr('data-post'));

        CRM.$('<a href=\"#\" class=\"button edit\" id="edit_direct_debit_mandate_' + cgCount + '" title=\"Edit Direct Debit Mandate\" onclick=\"CRM.loadPage(\'' + getUrlForUpdatingCurrentMandate(cgCount, mandateData.groupID, mandateData.contactId, mandateData.valueID) + '\')\"><span><i class="crm-i fa-pencil"></i> Edit </span></a>').insertAfter(CRM.$(this));
        CRM.$(this).hide();
      }
    });
  }

  function getUrlForUpdatingCurrentMandate(cgCount, groupId, contactId, mandateId) {
    var url = CRM.url(
      'civicrm/contact/view/cd/edit',
      {
        reset: '1',
        type: 'Individual',
        groupID: groupId,
        entityID: contactId,
        cgcount: cgCount,
        multiRecordDisplay: 'single',
        mode: 'edit',
        mandateId: mandateId
      }
    );

    return url;
  }

});
