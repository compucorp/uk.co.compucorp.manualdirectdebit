'use strict';

const { test } = require('@playwright/test');
const { readSeed } = require('../helpers/civi');
const { sendNotificationViaActivitySearch } = require('../helpers/journeys');

const seed = readSeed();

test.describe('Direct Debit Auto-renew Notification', () => {
  test('can be found and sent manually from a search', async ({ page }) => {
    await sendNotificationViaActivitySearch(page, seed, 'autoRenew');
  });
});
