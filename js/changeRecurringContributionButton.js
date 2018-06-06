CRM.$(function ($) {
  var recurringContributions = CRM.recurringContributions || {};
  var listOfRecurrContributionIds = recurringContributions.listOfRecurrContributions;

  listOfRecurrContributionIds.forEach(function (recurContributionId) {
    if (CRM.$('#contribution_recur-' + recurContributionId).length) {
      changeContribution('#contribution_recur-' + recurContributionId, recurContributionId);
    }
  });

  function changeContribution(contributionSelector, recurContributionId) {
    var cancelButtonSelector = '.action-item.crm-hover-button[title ="Cancel"]';
    var isCancelButtonExistForCurrentRecurringContribution = CRM.$(contributionSelector + ' ' + cancelButtonSelector).length;
    if (isCancelButtonExistForCurrentRecurringContribution) {
      if (!CRM.$(cancelButtonSelector).hasClass('movedBlock')) {
        CRM.$(cancelButtonSelector).parent().append('<span class="btn-slide crm-hover-button">more<ul class="panel moreButtonWrap"><li class="cancelButton"></li><li class="newMandate"></li></ul></span>');
        CRM.$(cancelButtonSelector).appendTo(contributionSelector + ' .moreButtonWrap .cancelButton');

        var newMandate = '<a href="' + getNewUrl(recurContributionId) + '" class="action-item crm-hover-button" title="Use a new mandate">Use a new mandate</a>';
        CRM.$(newMandate).appendTo(contributionSelector + ' .moreButtonWrap .newMandate');

        CRM.$(cancelButtonSelector).addClass('movedBlock')
      }
    }
  }

  function getNewUrl(recurContributionId) {
    var urlData = CRM.urlData || {};
    var newUrl = CRM.url(
      'civicrm/contact/view/cd/edit',
      {
        reset: '1',
        type: 'Individual',
        groupID: urlData.groupID,
        entityID: urlData.cid,
        cgcount: urlData.cgcount,
        multiRecordDisplay: 'single',
        mode: 'add',
        updatedRecId: recurContributionId,
      }
    );

    return newUrl;
  }

});
