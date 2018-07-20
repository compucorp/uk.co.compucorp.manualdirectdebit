CRM.$(function ($) {
  var openContributionElement = CRM.$('#crm-main-content-wrapper input[name|="optionContributionId"]');
  var openContribution = openContributionElement.val();
  if(openContribution){
    openContributionElement.val(false);
    CRM.$('#rowid' + openContribution +" a[title|='View Contribution']").click();
  }
});

