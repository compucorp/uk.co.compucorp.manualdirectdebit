'use strict';

const fs = require('fs');
const path = require('path');
const { chromium } = require('@playwright/test');

/**
 * Logs in once and saves the session for every journey to share.
 *
 * The journeys all run as the same staff user, and the CiviPlus sites these
 * tests are pointed at allow only one session per user, so logging in per test
 * would knock the previous one out.
 *
 * Either set CIVI_LOGIN_URL to a one-time login link (`drush uli`), which is
 * the tidier option because no password goes near the test run, or set
 * CIVI_USER and CIVI_PASS to use the normal login form.
 */
module.exports = async () => {
  if (!process.env.CIVI_BASE_URL) {
    throw new Error('CIVI_BASE_URL is not set. See tests/e2e/README.md.');
  }

  const loginUrl = process.env.CIVI_LOGIN_URL;
  if (!loginUrl && !(process.env.CIVI_USER && process.env.CIVI_PASS)) {
    throw new Error(
      'Set CIVI_LOGIN_URL to a one-time login link, or CIVI_USER and CIVI_PASS. See tests/e2e/README.md.'
    );
  }

  const authDir = path.join(__dirname, '.auth');
  fs.mkdirSync(authDir, { recursive: true });

  const browser = await chromium.launch();
  const page = await browser.newPage({
    baseURL: process.env.CIVI_BASE_URL,
    ignoreHTTPSErrors: true,
  });

  if (loginUrl) {
    await page.goto(loginUrl, { waitUntil: 'domcontentloaded' });

    // Drupal 7 lands on the password-reset page with a confirmation button.
    const confirm = page.locator('#user-pass-reset input[type="submit"], input#edit-submit').first();
    if (await confirm.count()) {
      await confirm.click().catch(() => { /* Already logged straight in. */ });
      await page.waitForLoadState('domcontentloaded');
    }
  }
  else {
    await page.goto('/user/login', { waitUntil: 'domcontentloaded' });
    await page.fill('#edit-name', process.env.CIVI_USER);
    await page.fill('#edit-pass', process.env.CIVI_PASS);
    await Promise.all([
      page.waitForURL('**/*', { waitUntil: 'domcontentloaded' }),
      page.click('#edit-submit'),
    ]);
  }

  // Fail here rather than in every journey if the login did not take. The
  // contact search is used because it is the page the journeys start from —
  // the CiviCRM dashboard depends on dashlets that some sites cannot render.
  const response = await page.goto('/civicrm/contact/search?reset=1', { waitUntil: 'domcontentloaded' });

  if (await page.locator('#crm-container, .crm-container').count() === 0) {
    throw new Error(
      'Logged in but CiviCRM did not load. ' +
      `Got HTTP ${response ? response.status() : '?'} for ${page.url()} ` +
      `with title "${await page.title()}". Check the login and CiviCRM access.`
    );
  }

  await page.context().storageState({ path: path.join(authDir, 'state.json') });
  await browser.close();
};
