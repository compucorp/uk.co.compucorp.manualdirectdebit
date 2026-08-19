'use strict';

const path = require('path');
const { defineConfig } = require('@playwright/test');

/**
 * These tests drive a real CiviCRM instance, so they are configured entirely
 * from environment variables and are NOT wired into the extension's default
 * CI (which has no browser/CiviCRM-web environment). See README.md.
 *
 *   CIVI_BASE_URL   e.g. http://civiplus-golden.localhost:8090
 *   CIVI_USER       Drupal admin username
 *   CIVI_PASS       Drupal admin password
 *   SEED_FILE       JSON written by seed/seed-dd-communications.php
 *   CIVI_CV_CMD     optional, a cv command used to read the mail spool
 */
module.exports = defineConfig({
  testDir: './journeys',
  timeout: 240000,
  expect: { timeout: 30000 },
  retries: 0,
  // CiviPlus sites limit concurrent sessions, and these journeys share one
  // logged-in session, so they have to run one at a time.
  workers: 1,
  fullyParallel: false,
  reporter: 'list',
  globalSetup: require.resolve('./global-setup.js'),
  use: {
    baseURL: process.env.CIVI_BASE_URL,
    storageState: path.join(__dirname, '.auth', 'state.json'),
    headless: true,
    video: 'on',
    screenshot: 'only-on-failure',
    ignoreHTTPSErrors: true,
    actionTimeout: 45000,
    navigationTimeout: 60000,
  },
});
