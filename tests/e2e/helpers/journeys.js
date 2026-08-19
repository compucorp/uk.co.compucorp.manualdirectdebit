'use strict';

const { expect } = require('@playwright/test');
const { canReadMail, latestMessageFor } = require('./mail');
const civi = require('./civi');

const SEND_NOTIFICATIONS_TASK = 'Send Direct Debit Notifications';

/**
 * Walks the documented "manual via search actions" route for a notification.
 *
 * This is the journey the product guide describes: find the members the Direct
 * Debit event happened to by searching for its activity, switch the results to
 * Members so the Direct Debit action is offered, then send the notification.
 *
 * @param {import('@playwright/test').Page} page
 *   The page to drive.
 * @param {object} seed
 *   The seeded fixtures.
 * @param {string} journey
 *   Key of the journey in the seed data, for example "signUp".
 */
async function sendNotificationViaActivitySearch (page, seed, journey) {
  const member = seed.members[journey];
  const activityTypeLabel = seed.activityTypeLabels[journey];
  const templateTitle = seed.templateTitles[journey];
  const serverErrors = civi.watchForServerErrors(page);

  await civi.openAdvancedSearch(page);
  await civi.openSearchCriteriaPane(page, 'activity', '#activity_type_id');
  await civi.selectByLabel(page, '#activity_type_id', activityTypeLabel, 'activity type');

  // The guide's key trick: the Direct Debit actions are only offered on
  // membership and contribution result sets, never on an activity result set.
  await civi.displayResultsAs(page, 'Memberships');
  await civi.runSearch(page);

  await civi.selectResultRow(page, member.lastName);
  await civi.runTask(page, SEND_NOTIFICATIONS_TASK);
  await civi.useTemplate(page, templateTitle);
  await civi.submitTaskForm(page, /send email/i);

  await civi.expectEmailActivity(page, member.contactId, templateTitle);
  civi.expectNoServerErrors(serverErrors);

  await expectSentEmailIsResolved(member, seed, templateTitle);
}

/**
 * Checks the message that was actually sent, if the mail spool is reachable.
 *
 * @param {object} member
 *   The seeded member the message went to.
 * @param {object} seed
 *   The seeded fixtures.
 * @param {string} templateTitle
 *   Title of the template that was sent.
 */
async function expectSentEmailIsResolved (member, seed, templateTitle) {
  if (!canReadMail()) {
    console.log(`CIVI_CV_CMD is not set — not checking the content of the "${templateTitle}" email.`);
    return;
  }

  const sent = latestMessageFor(member.email);

  expect(sent, `a message should have been delivered to ${member.email}`).toBeTruthy();
  civi.expectDirectDebitContentResolved(sent, seed, `the "${templateTitle}" email`);
}

module.exports = { sendNotificationViaActivitySearch, expectSentEmailIsResolved, SEND_NOTIFICATIONS_TASK };
