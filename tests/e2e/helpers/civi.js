'use strict';

const fs = require('fs');
const path = require('path');
const { expect } = require('@playwright/test');

const SEED_FILE = process.env.SEED_FILE || path.join(__dirname, '..', '.seed', 'seed.json');

/**
 * Reads the fixtures created by seed/seed-dd-communications.php.
 *
 * @return {object}
 *   The seeded IDs and labels.
 */
function readSeed () {
  if (!fs.existsSync(SEED_FILE)) {
    throw new Error(
      `No seed data at ${SEED_FILE}. Run seed/seed-dd-communications.php first — see tests/e2e/README.md.`
    );
  }

  return JSON.parse(fs.readFileSync(SEED_FILE, 'utf8'));
}

/**
 * Starts collecting server errors, so a fatal cannot pass as a soft failure.
 *
 * @param {import('@playwright/test').Page} page
 *   The page to watch.
 *
 * @return {string[]}
 *   Collected errors, filled in as the test runs.
 */
function watchForServerErrors (page) {
  const errors = [];

  page.on('response', (response) => {
    if (response.status() >= 500) {
      errors.push(`${response.status()} ${response.request().method()} ${response.url()}`);
    }
  });

  return errors;
}

/**
 * Waits until CiviCRM's JavaScript is usable on the current page.
 *
 * @param {import('@playwright/test').Page} page
 *   The page to wait on.
 */
async function waitForCiviJs (page) {
  await page.waitForFunction(() => window.CRM && window.CRM.$ && window.CRM.api3, null, { timeout: 60000 });
}

/**
 * Opens the Advanced Search form.
 *
 * @param {import('@playwright/test').Page} page
 *   The page to drive.
 */
async function openAdvancedSearch (page) {
  await page.goto('/civicrm/contact/search/advanced?reset=1', { waitUntil: 'domcontentloaded' });
  await waitForCiviJs(page);
}

/**
 * Opens one of the Advanced Search criteria panes and waits for its fields.
 *
 * The panes are <details> elements whose contents CiviCRM fetches the first
 * time they are opened, so the fields inside genuinely do not exist until the
 * pane has been expanded.
 *
 * @param {import('@playwright/test').Page} page
 *   The page to drive.
 * @param {string} pane
 *   ID of the pane's summary element, for example "activity".
 * @param {string} fieldSelector
 *   A field inside the pane, waited for once it is open.
 */
async function openSearchCriteriaPane (page, pane, fieldSelector) {
  if (await page.locator(fieldSelector).count() > 0) {
    return;
  }

  const summary = page.locator(`summary#${pane}`).first();
  if (await summary.count()) {
    await summary.click();
  }
  else {
    // Older markup used accordion headers matched on their title.
    const header = page.locator('.crm-accordion-header').filter({ hasText: pane }).first();
    if (await header.count()) {
      await header.click().catch(() => { /* Already open. */ });
    }
  }

  await page.waitForFunction(
    (selector) => window.CRM.$(selector).length > 0,
    fieldSelector,
    { timeout: 30000 }
  );
}

/**
 * Picks an option on a select by its visible label, through CiviCRM's jQuery.
 *
 * Going through jQuery rather than Playwright's selectOption is deliberate:
 * these are select2 widgets whose change handlers are what CiviCRM reacts to.
 *
 * @param {import('@playwright/test').Page} page
 *   The page to drive.
 * @param {string} selector
 *   Selector for the select element.
 * @param {string} label
 *   The visible option text to pick.
 * @param {string} description
 *   What the field is, used in the error message.
 *
 * @return {string}
 *   The value that was selected.
 */
async function selectByLabel (page, selector, label, description) {
  const result = await page.evaluate(([fieldSelector, wanted]) => {
    const $ = window.CRM.$;
    const select = $(fieldSelector).first();

    if (!select.length) {
      return { ok: false, reason: 'the field is not on this form' };
    }

    let value = null;
    const available = [];
    select.find('option').each(function () {
      const text = $(this).text().trim();
      available.push(text);
      if (text === wanted) {
        value = $(this).val();
      }
    });

    if (value === null) {
      return { ok: false, reason: 'no option with that label', available };
    }

    select.val(select.prop('multiple') ? [value] : value).trigger('change');

    return { ok: true, value };
  }, [selector, label]);

  if (!result.ok) {
    const available = result.available ? ` Available: ${result.available.join(' | ')}` : '';
    throw new Error(`Could not choose ${description} "${label}" — ${result.reason}.${available}`);
  }

  return result.value;
}

/**
 * Chooses what the Advanced Search returns, via "Display Results As".
 *
 * @param {import('@playwright/test').Page} page
 *   The page to drive.
 * @param {string} label
 *   For example "Members" or "Contributions".
 */
async function displayResultsAs (page, label) {
  const kind = await page.evaluate(() => {
    const field = window.CRM.$('[name="component_mode"]');
    return field.length ? field[0].tagName : null;
  });

  if (kind === null) {
    throw new Error('Could not set "Display Results As" — it is not on this form.');
  }

  if (kind === 'SELECT') {
    await selectByLabel(page, 'select[name="component_mode"]', label, '"Display Results As"');
    return;
  }

  const result = await page.evaluate((wanted) => {
    const $ = window.CRM.$;
    const options = $('input[name="component_mode"]');

    if (!options.length) {
      return { ok: false, reason: '"Display Results As" is not on this form' };
    }

    let matched = null;
    const available = [];
    options.each(function () {
      const text = ($('label[for="' + this.id + '"]').text() || '').trim();
      available.push(text);
      if (text === wanted) {
        matched = this;
      }
    });

    if (!matched) {
      return { ok: false, reason: 'no option with that label', available };
    }

    $(matched).prop('checked', true).trigger('change');

    return { ok: true, value: $(matched).val() };
  }, label);

  if (!result.ok) {
    const available = result.available ? ` Available: ${result.available.join(' | ')}` : '';
    throw new Error(`Could not set "Display Results As" to "${label}" — ${result.reason}.${available}`);
  }
}

/**
 * Submits a search form and waits for the results.
 *
 * @param {import('@playwright/test').Page} page
 *   The page to drive.
 */
async function runSearch (page) {
  // Matched by role so it works whether the theme renders the submit as an
  // <input> or a <button>, and so the "Search" item in the menu bar (a link)
  // is not picked up by mistake.
  const search = page.getByRole('button', { name: /^\s*search\s*$/i }).first();

  await search.waitFor({ state: 'visible' });
  await search.click();

  await page
    .locator('table.selector, .crm-search-results, .crm-results-block, .messages')
    .first()
    .waitFor({ state: 'visible' });

  await waitForCiviJs(page);
}

/**
 * Ticks the search result row for a seeded record.
 *
 * Doubles as the check that the documented search actually finds it.
 *
 * @param {import('@playwright/test').Page} page
 *   The page to drive.
 * @param {string} text
 *   Text identifying the row, normally the contact's last name.
 */
async function selectResultRow (page, text) {
  const row = page.locator('table.selector tbody tr').filter({ hasText: text }).first();

  await expect(row, `the search should have found "${text}"`).toBeVisible();
  await row.locator('input[type="checkbox"]').first().check();
}

/**
 * Runs one of the search-result actions.
 *
 * @param {import('@playwright/test').Page} page
 *   The page to drive.
 * @param {string} taskLabel
 *   The action's label in the Actions menu.
 */
async function runTask (page, taskLabel) {
  const task = await page.evaluate((wanted) => {
    const $ = window.CRM.$;
    const select = $('select#task').first();

    if (!select.length) {
      return { ok: false, reason: 'there is no Actions menu on the results' };
    }

    let value = null;
    const available = [];
    select.find('option').each(function () {
      const text = $(this).text().trim();
      if (text) {
        available.push(text);
      }
      if (text === wanted) {
        value = $(this).val();
      }
    });

    if (value === null) {
      return { ok: false, reason: 'the action is not offered here', available };
    }

    return { ok: true, value };
  }, taskLabel);

  if (!task.ok) {
    const available = task.available ? ` Offered: ${task.available.join(' | ')}` : '';
    throw new Error(`Could not run the "${taskLabel}" action — ${task.reason}.${available}`);
  }

  const urlBefore = page.url();

  await page.evaluate((value) => {
    window.CRM.$('select#task').val(value).trigger('change');
  }, task.value);

  // Choosing an action submits the results form by itself. Pressing Go as well
  // would interrupt that submission, so it is only a fallback for versions
  // where the menu does not submit.
  const submitted = await page
    .waitForURL((url) => url.toString() !== urlBefore, { timeout: 30000 })
    .then(() => true)
    .catch(() => false);

  if (!submitted) {
    const go = page.getByRole('button', { name: /^\s*go\s*$/i }).first();
    if (await go.count()) {
      await go.click();
    }
  }

  await page.waitForLoadState('domcontentloaded');
  await waitForCiviJs(page);
}

/**
 * Picks a message template on a Send Email or Print Letter form.
 *
 * @param {import('@playwright/test').Page} page
 *   The page to drive.
 * @param {string} title
 *   Title of the message template.
 */
async function useTemplate (page, title) {
  await page.locator('select#template').first().waitFor({ state: 'attached' });
  await selectByLabel(page, 'select#template', title, 'message template');

  // Picking a template loads its subject and body over ajax.
  await page
    .waitForFunction(() => (window.CRM.$('#subject').val() || '').trim().length > 0, null, { timeout: 30000 })
    .catch(() => { /* Not every form has a subject field. */ });
}

/**
 * Submits a task form by the label on its button.
 *
 * @param {import('@playwright/test').Page} page
 *   The page to drive.
 * @param {RegExp} buttonLabel
 *   Pattern matching the button's value.
 *
 * @return {import('@playwright/test').Locator}
 *   The button that was clicked.
 */
async function submitTaskForm (page, buttonLabel) {
  // By role first: CiviCRM's themes render these as <button value="1">Label</button>
  // as often as <input value="Label">, so the accessible name is the reliable
  // way in.
  const byRole = page.getByRole('button', { name: buttonLabel }).first();
  if (await byRole.count()) {
    await byRole.click();
    return byRole;
  }

  const buttons = page.locator('input[type="submit"], button[type="submit"], button');
  const count = await buttons.count();
  const seen = [];

  for (let index = 0; index < count; index++) {
    const button = buttons.nth(index);
    const labels = [
      await button.getAttribute('value'),
      await button.getAttribute('aria-label'),
      await button.getAttribute('name'),
      await button.textContent(),
    ].filter(Boolean).map((label) => label.trim()).filter((label) => label !== '');

    if (labels.length) {
      seen.push(labels.join(' / '));
    }

    if (labels.some((label) => buttonLabel.test(label)) && await button.isVisible()) {
      await button.click();
      return button;
    }
  }

  throw new Error(`No submit button matching ${buttonLabel} was found. Buttons: ${seen.join(' | ')}`);
}

/**
 * Waits for the Email activity CiviCRM records when a message is sent.
 *
 * This is the deterministic proof that the send went through: the status
 * notification on screen fades on its own.
 *
 * @param {import('@playwright/test').Page} page
 *   The page to query from.
 * @param {number} contactId
 *   Contact the message was sent to.
 * @param {string} subject
 *   Expected subject of the message.
 */
async function expectEmailActivity (page, contactId, subject) {
  // Let the send finish redirecting first: polling across a navigation loses
  // the execution context the query runs in.
  await page.waitForLoadState('domcontentloaded');
  await waitForCiviJs(page);

  const found = await page.waitForFunction(async ([cid, expectedSubject]) => {
    const result = await window.CRM.api3('Activity', 'get', {
      target_contact_id: cid,
      activity_type_id: 'Email',
      subject: expectedSubject,
      options: { limit: 1 },
      sequential: 1,
    });

    return result.count > 0 ? result.values[0] : false;
  }, [contactId, subject], { timeout: 60000 })
    .then((handle) => handle.jsonValue())
    .catch((error) => ({ error: error.message }));

  expect(
    found && !found.error,
    `an Email activity subjected "${subject}" should have been recorded for contact ${contactId}` +
    (found && found.error ? ` (lookup failed: ${found.error})` : '')
  ).toBeTruthy();
}

/**
 * Narrows a contribution search to a collection window.
 *
 * Best effort: the essential filters for this journey are the payment method
 * and the status, and the row assertion proves what was found, so a CiviCRM
 * version that names these fields differently does not fail the journey.
 *
 * @param {import('@playwright/test').Page} page
 *   The page to drive.
 * @param {string} from
 *   Start of the window, as YYYY-MM-DD.
 * @param {string} to
 *   End of the window, as YYYY-MM-DD.
 *
 * @return {boolean}
 *   Whether the range could be applied.
 */
async function setReceiveDateRange (page, from, to) {
  return page.evaluate(([low, high]) => {
    const $ = window.CRM.$;
    const candidates = [
      ['contribution_date_low', 'contribution_date_high'],
      ['receive_date_low', 'receive_date_high'],
    ];

    for (const [lowName, highName] of candidates) {
      const lowField = $('[name="' + lowName + '"]');
      const highField = $('[name="' + highName + '"]');

      if (lowField.length && highField.length) {
        // A relative date preset would override the explicit range.
        $('[name="contribution_date_relative"], [name="receive_date_relative"]').val('0').trigger('change');
        lowField.val(low).trigger('change');
        highField.val(high).trigger('change');

        return true;
      }
    }

    return false;
  }, [from, to]);
}

/**
 * Waits for an activity of a given type to be recorded against a contact.
 *
 * @param {import('@playwright/test').Page} page
 *   The page to query from.
 * @param {number} contactId
 *   The contact the activity should be on.
 * @param {string} activityType
 *   Name of the activity type, for example "Print PDF Letter".
 */
async function expectActivityOfType (page, contactId, activityType) {
  await page.waitForLoadState('domcontentloaded');
  await waitForCiviJs(page);

  const found = await page.waitForFunction(async ([cid, type]) => {
    const result = await window.CRM.api3('Activity', 'get', {
      target_contact_id: cid,
      activity_type_id: type,
      options: { limit: 1 },
      sequential: 1,
    });

    return result.count > 0 ? result.values[0] : false;
  }, [contactId, activityType], { timeout: 60000 }).catch(() => null);

  expect(
    found,
    `a "${activityType}" activity should have been recorded for contact ${contactId}`
  ).toBeTruthy();
}

/**
 * Asserts no activity of a given type is recorded against a contact.
 *
 * @param {import('@playwright/test').Page} page
 *   The page to query from.
 * @param {number} contactId
 *   The contact that should have no such activity.
 * @param {string} activityType
 *   Name of the activity type, for example "Print PDF Letter".
 */
async function expectNoActivityOfType (page, contactId, activityType) {
  await page.waitForLoadState('domcontentloaded');
  await waitForCiviJs(page);

  const count = await page.evaluate(async ([cid, type]) => {
    const result = await window.CRM.api3('Activity', 'getcount', {
      target_contact_id: cid,
      activity_type_id: type,
    });

    return typeof result === 'number' ? result : result.result;
  }, [contactId, activityType]);

  expect(
    count,
    `no "${activityType}" activity should be recorded for contact ${contactId}`
  ).toBe(0);
}

/**
 * Asserts a rendered message has its Direct Debit content filled in.
 *
 * @param {string} content
 *   The rendered message or letter.
 * @param {object} seed
 *   The seeded fixtures, for the expected mandate details.
 * @param {string} description
 *   What is being checked, used in failure messages.
 */
function expectDirectDebitContentResolved (content, seed, description) {
  expect(content, `${description} should contain the mandate's bank name`)
    .toContain(seed.mandate.bankName);
  expect(content, `${description} should contain the Direct Debit logo`)
    .toContain('debit.png');

  for (const markup of ['{$', '{ts}', '{if ', '{foreach ']) {
    expect(content, `${description} should not contain unresolved "${markup}"`)
      .not.toContain(markup);
  }
}

/**
 * Asserts nothing 500'd during the journey.
 *
 * @param {string[]} errors
 *   Errors collected by watchForServerErrors().
 */
function expectNoServerErrors (errors) {
  expect(errors, 'no request should have failed with a server error').toEqual([]);
}

module.exports = {
  readSeed,
  watchForServerErrors,
  waitForCiviJs,
  openAdvancedSearch,
  openSearchCriteriaPane,
  selectByLabel,
  displayResultsAs,
  runSearch,
  selectResultRow,
  runTask,
  useTemplate,
  submitTaskForm,
  setReceiveDateRange,
  expectActivityOfType,
  expectNoActivityOfType,
  expectEmailActivity,
  expectDirectDebitContentResolved,
  expectNoServerErrors,
};
