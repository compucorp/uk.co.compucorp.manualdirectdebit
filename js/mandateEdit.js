CRM.$('document').ready(function () {
  var customGroupTitle = CRM.$('.section-shown.form-item .crm-accordion-header').first().text().trim();

  if (customGroupTitle == "Direct Debit Mandate") {
    CRM.$('.form-item a.button').parent().hide();
    CRM.$('.crm-hover-button.crm-custom-value-del').each(function () {
      var that = this;
      var mandateTitle = CRM.$(this).attr('title').split(' ');
      var cgCount = mandateTitle[mandateTitle.length - 1];

      var isAlreadyEditButtonAdd = CRM.$(this).next('#edit_direct_debit_mandate_' + cgCount).length != 1;
      if (isAlreadyEditButtonAdd) {
        var mandateData = JSON.parse(CRM.$(this).attr('data-post'));
        var editMandateURL = getUrlForUpdatingCurrentMandate(cgCount, mandateData.groupID, mandateData.contactId, mandateData.valueID);
        var editButtonHTML =
          '<a href="'+ editMandateURL +'" class="button edit" id="edit_direct_debit_mandate_' + cgCount + '" title="Edit Direct Debit Mandate">' +
          '  <span><i class="crm-i fa-pencil"></i> Edit </span>' +
          '</a>';

        CRM.$(editButtonHTML).insertBefore(CRM.$(this));
        CRM.$(this).addClass('button delete')
          .removeClass('crm-hover-button')
          .removeClass('crm-custom-value-del')
        ;
        CRM.$(this).click(function () {
          CRM.confirm({
            title: ts('Delete Mandate?'),
            message: ts('Are you sure you want to delete this mandate? This action cannot be undone.'),
            options: {
              no: ts('Cancel'),
              yes: ts('Apply')
            }
          }).on('crmConfirm:yes', function() {
            var mandateData = JSON.parse(CRM.$(that).attr('data-post'));

            CRM.api3('ManualDirectDebit', 'deletemandate', {
              mandate_id: mandateData.valueID,
            }).done(function(result) {
              if (result.is_error) {
                CRM.alert(result.error_message, null, 'error');
              } else {
                CRM.alert(ts('Mandate has been deleted.'), null, 'success');
              }

              CRM.refreshParent(that);
            });
          }).on('crmConfirm:no', function() {
            return;
          });

          return false;
        });
      }
    });
  }

  function getUrlForUpdatingCurrentMandate(cgCount, groupId, contactId, mandateId) {
    var url = CRM.url(
      'civicrm/contact/view/cd/edit',
      {
        reset: '1',
        type: CRM.vars['uk.co.compucorp.manualdirectdebit'].contactType,
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

  CRM.$('#mandate_delete_btn').each(function () {
    var that = this;

    CRM.$(this).click(function () {
      CRM.confirm({
        title: ts('Delete Mandate?'),
        message: ts('Are you sure you want to delete this mandate? This action cannot be undone.'),
        options: {
          no: ts('Cancel'),
          yes: ts('Apply')
        }
      }).on('crmConfirm:yes', function() {
        var mandateData = JSON.parse(CRM.$(that).attr('data-post'));

        CRM.api3('ManualDirectDebit', 'deletemandate', {
          mandate_id: mandateData.valueID,
        }).done(function(result) {
          if (result.is_error) {
            CRM.alert(result.error_message, null, 'error');
          } else {
            var mandateDiealog = CRM.$('div.ui-dialog-content.ui-widget-content.modal-dialog.crm-ajax-container');
            mandateDiealog.dialog('destroy');
            CRM.alert(ts('Mandate has been deleted.'), null, 'success');
            CRM.refreshParent('#crm-main-content-wrapper');
          }
        });
      }).on('crmConfirm:no', function() {
        return;
      });

      return false;
    });
  });
});
