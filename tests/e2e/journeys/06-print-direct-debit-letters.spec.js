'use strict';

const fs = require('fs');
const os = require('os');
const path = require('path');
const { execFileSync } = require('child_process');
const { test, expect } = require('@playwright/test');
const civi = require('../helpers/civi');

const seed = civi.readSeed();

/**
 * Reads the text out of a PDF, if poppler is installed.
 *
 * @param {string} file
 *   Path to the PDF.
 *
 * @return {string|null}
 *   The text, or null if it could not be extracted.
 */
function extractPdfText (file) {
  try {
    return execFileSync('pdftotext', [file, '-'], { encoding: 'utf8', maxBuffer: 32 * 1024 * 1024 });
  }
  catch (error) {
    return null;
  }
}

test.describe('Print Direct Debit Letters', () => {
  // This is the journey that CIVIMM-19 broke: the task fataled for every
  // template because it called a core class that had been removed. It is also
  // the only route to a Direct Debit communication for a member with no email
  // address, which is why the seeded member here deliberately has none.
  test('produces a letter for a member with no email address', async ({ page }) => {
    const member = seed.members.letters;
    const templateTitle = seed.templateTitles.signUp;
    const serverErrors = civi.watchForServerErrors(page);

    await page.goto('/civicrm/member/search?reset=1', { waitUntil: 'domcontentloaded' });
    await civi.waitForCiviJs(page);

    await page.fill('input[name="sort_name"]', member.lastName);
    await civi.runSearch(page);

    await civi.selectResultRow(page, member.lastName);
    await civi.runTask(page, 'Print Direct Debit Letters');
    await civi.useTemplate(page, templateTitle);

    const downloadStarted = page.waitForEvent('download', { timeout: 120000 });
    await civi.submitTaskForm(page, /download document|make pdf|download/i);
    const download = await downloadStarted;

    const file = path.join(os.tmpdir(), `mdd-e2e-letter-${member.membershipId}.pdf`);
    await download.saveAs(file);
    const pdf = fs.readFileSync(file);

    expect(pdf.subarray(0, 5).toString(), 'the download should be a PDF').toBe('%PDF-');
    expect(pdf.length, 'the PDF should have content in it').toBeGreaterThan(2000);

    const text = extractPdfText(file);
    if (text === null) {
      console.log('pdftotext is not installed — not checking the letter content.');
    }
    else {
      expect(text, 'the letter should show the mandate bank name').toContain(seed.mandate.bankName);
      expect(text, 'the letter should show the account holder').toContain(seed.mandate.accountHolderName);
      expect(text, 'the letter should include the guarantee').toContain('The Direct Debit Guarantee');

      for (const markup of ['{$', '{ts}', '{if ', '{foreach ']) {
        expect(text, `the letter should not contain unresolved "${markup}"`).not.toContain(markup);
      }
    }

    // The task records the letter against the member, same as core's does.
    await civi.expectActivityOfType(page, member.contactId, 'Print PDF Letter');
    civi.expectNoServerErrors(serverErrors);
  });

  // A member with no contribution has nothing to bill, so no letter can be
  // built for them. The run must still produce the other letters, and must not
  // record a letter against the member who did not get one.
  test('records the letter only against the members it could generate one for', async ({ page }) => {
    const posted = seed.members.letters;
    const skipped = seed.members.noContribution;
    const templateTitle = seed.templateTitles.signUp;
    const serverErrors = civi.watchForServerErrors(page);

    await page.goto('/civicrm/member/search?reset=1', { waitUntil: 'domcontentloaded' });
    await civi.waitForCiviJs(page);

    // Every seeded member shares this first name, so one search returns both.
    await page.fill('input[name="sort_name"]', 'Ddtest');
    await civi.runSearch(page);

    await civi.selectResultRow(page, posted.lastName);
    await civi.selectResultRow(page, skipped.lastName);
    await civi.runTask(page, 'Print Direct Debit Letters');
    await civi.useTemplate(page, templateTitle);

    const downloadStarted = page.waitForEvent('download', { timeout: 120000 });
    await civi.submitTaskForm(page, /download document|make pdf|download/i);
    const download = await downloadStarted;

    const file = path.join(os.tmpdir(), 'mdd-e2e-letter-mixed.pdf');
    await download.saveAs(file);
    const pdf = fs.readFileSync(file);

    expect(pdf.subarray(0, 5).toString(), 'the other letters should still be produced').toBe('%PDF-');

    await civi.expectActivityOfType(page, posted.contactId, 'Print PDF Letter');
    await civi.expectNoActivityOfType(page, skipped.contactId, 'Print PDF Letter');
    civi.expectNoServerErrors(serverErrors);
  });

  // When no letter at all can be built, the user needs telling. An empty
  // document would otherwise download as a blank one-page PDF.
  test('says so when it cannot generate any letter at all', async ({ page }) => {
    const skipped = seed.members.noContribution;
    const templateTitle = seed.templateTitles.signUp;
    const serverErrors = civi.watchForServerErrors(page);

    await page.goto('/civicrm/member/search?reset=1', { waitUntil: 'domcontentloaded' });
    await civi.waitForCiviJs(page);

    await page.fill('input[name="sort_name"]', skipped.lastName);
    await civi.runSearch(page);

    await civi.selectResultRow(page, skipped.lastName);
    await civi.runTask(page, 'Print Direct Debit Letters');
    await civi.useTemplate(page, templateTitle);

    let downloaded = false;
    page.on('download', () => { downloaded = true; });
    await civi.submitTaskForm(page, /download document|make pdf|download/i);
    await page.waitForLoadState('domcontentloaded');

    await expect(
      page.locator('body'),
      'the user should be told no letters could be generated'
    ).toContainText(/No Direct Debit letters could be generated/i);

    expect(downloaded, 'nothing should be downloaded when there is no letter').toBe(false);
    await civi.expectNoActivityOfType(page, skipped.contactId, 'Print PDF Letter');
    civi.expectNoServerErrors(serverErrors);
  });
});
