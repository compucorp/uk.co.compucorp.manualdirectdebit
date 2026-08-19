'use strict';

const { test } = require('@playwright/test');
const civi = require('../helpers/civi');
const { expectSentEmailIsResolved, SEND_NOTIFICATIONS_TASK } = require('../helpers/journeys');

const seed = civi.readSeed();

test.describe('Direct Debit Payment Collection Reminder', () => {
  // The documented manual route for this one starts from Find Contributions
  // rather than an activity search, because the reminder is about a payment
  // that is due rather than something that has already happened.
  test('can be found and sent manually from Find Contributions', async ({ page }) => {
    const member = seed.members.collectionReminder;
    const templateTitle = seed.templateTitles.collectionReminder;
    const serverErrors = civi.watchForServerErrors(page);

    await page.goto('/civicrm/contribute/search?reset=1', { waitUntil: 'domcontentloaded' });
    await civi.waitForCiviJs(page);

    // The contribution search prefixes its own fields; older versions did not.
    await civi.selectByLabel(
      page,
      '#contribution_payment_instrument_id, #payment_instrument_id',
      'Direct Debit',
      'payment method'
    );
    await civi.selectByLabel(page, '#contribution_status_id, #status_id', 'Pending', 'contribution status');

    const windowApplied = await civi.setReceiveDateRange(page, seed.collectionDate, seed.collectionDate);
    if (!windowApplied) {
      console.log('Could not find the contribution date range fields — searching without a collection window.');
    }

    await civi.runSearch(page);

    await civi.selectResultRow(page, member.lastName);
    await civi.runTask(page, SEND_NOTIFICATIONS_TASK);
    await civi.useTemplate(page, templateTitle);
    await civi.submitTaskForm(page, /send email/i);

    await civi.expectEmailActivity(page, member.contactId, templateTitle);
    civi.expectNoServerErrors(serverErrors);

    await expectSentEmailIsResolved(member, seed, templateTitle);
  });
});
