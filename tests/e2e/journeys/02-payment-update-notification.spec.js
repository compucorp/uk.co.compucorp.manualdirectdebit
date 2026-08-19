'use strict';

const { test } = require('@playwright/test');
const { readSeed } = require('../helpers/civi');
const { sendNotificationViaActivitySearch } = require('../helpers/journeys');

const seed = readSeed();

test.describe('Direct Debit Payment Update Notification', () => {
  // Expected to fail: changing the mandate a payment plan uses never records an
  // "Update Direct Debit Recurring Payment" activity, so there is nothing for
  // this journey's search — or for a scheduled reminder — to find.
  //
  // CRM_ManualDirectDebit_Common_MandateStorageManager::assignRecurringContributionMandate()
  // passes the operation as 'update', while
  // CRM_ManualDirectDebit_Hook_Post_RecurContribution_Activity::createActivity()
  // only acts on 'create' and 'edit'. That is the only place the class is
  // constructed, so the activity is never created for an updated plan.
  //
  // Remove this test.fail() once that is fixed — Playwright then reports the
  // journey as unexpectedly passing, which is the signal we want.
  test.fail();

  test('can be found and sent manually from a search', async ({ page }) => {
    await sendNotificationViaActivitySearch(page, seed, 'paymentUpdate');
  });
});
