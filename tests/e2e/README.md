# Direct Debit communication journeys (end-to-end)

These tests drive a **real CiviCRM site** through the manual routes documented in
the product guide, *8.3 Manual Direct Debit Communications*: for each of the five
Direct Debit notifications, find the right members with a search, then send the
notification from the search actions.

They are **not part of the extension's CI**, which has no browser and no web
server — run them locally against a test site, the same way as the
`io.compuco.financeextras` end-to-end tests.

## What each journey covers

| Spec | Route under test |
| --- | --- |
| `01-payment-sign-up-notification` | Advanced Search on the *New Direct Debit Recurring Payment* activity → Display Results As **Memberships** → **Send Direct Debit Notifications** |
| `02-payment-update-notification` | as above, on *Update Direct Debit Recurring Payment* — **expected to fail**, see below |
| `03-payment-collection-reminder` | **Find Contributions** filtered to Direct Debit / Pending in the collection window → **Send Direct Debit Notifications** |
| `04-auto-renew-notification` | Advanced Search on *Offline Direct Debit Auto-renewal* |
| `05-mandate-update-notification` | Advanced Search on *Direct Debit Mandate Update* |
| `06-print-direct-debit-letters` | **Find Memberships** → **Print Direct Debit Letters** for a member with no email address |

### The one that is expected to fail

`02-payment-update-notification` is marked `test.fail()`. Changing the mandate a
payment plan uses never records an *Update Direct Debit Recurring Payment*
activity, so neither this search nor a scheduled reminder can find anything to
send: `MandateStorageManager::assignRecurringContributionMandate()` reports the
operation as `update`, while
`Hook_Post_RecurContribution_Activity::createActivity()` only acts on `create`
and `edit`. When that is fixed, Playwright will report this journey as
unexpectedly passing — remove the `test.fail()` then.

Every journey asserts that the search finds the seeded member, that the Direct
Debit action is offered on those results, that the send or download completes
without a server error, and that the Direct Debit content in what was sent is
actually resolved — bank details present, no leftover `{$…}` markup.

Spec 06 is the regression test for **CIVIMM-19**: before that fix the task
returned HTTP 500 for every template.

## Prerequisites

* A local CiviCRM site with **Manual Direct Debit** and **Membership Extras**
  enabled, and a Drupal user who can search contacts and send email.
* Node 18 or newer.
* Optional: `pdftotext` (poppler) so spec 06 can check the letter's text.

## Setup

```bash
cd tests/e2e
npm install
npx playwright install chromium
```

## Seeding

The journeys need a Direct Debit member per notification, each with the activity
that journey's search looks for. The seed script creates them, and points
outgoing mail at the database so the tests can read back what was sent.

Run it through `cv` **inside the site**, writing the fixture IDs somewhere the
tests can read:

```bash
docker exec -w /var/www/default/htdocs/httpdocs my_php_container \
  env SEED_OUTPUT=/tmp/mdd-seed.json cv scr \
  /var/www/default/htdocs/httpdocs/path/to/tests/e2e/seed/seed-dd-communications.php
```

Then copy the JSON out to `tests/e2e/.seed/seed.json`, or point `SEED_FILE` at
wherever it landed. Re-running the script simply seeds another set of members.

> The script creates contacts and memberships, and asks CiviCRM to spool mail to
> the database. Only run it on a local or test site.

Two things to know about seeding these sites:

* **Disable the extensions that hook contribution creation first.** On the
  CiviPlus sites, CiviGiftAid and Stripe both throw when a contribution is
  created through the API with test data (`cv dis uk.co.compucorp.civicrm.giftaid`
  and `cv dis uk.co.compucorp.stripe`, re-enabling them afterwards).
* **Mail may be pinned by the site.** `sites/default/smtp.civicrm.settings.php`
  forces the mailer on the CiviPlus images, so the seed script's request to
  spool to the database is ignored and mail goes to the site's **MailHog**
  instead. Make sure that container is running — it is part of the site's own
  compose stack.

## Running

```bash
CIVI_BASE_URL=http://civiplus-golden.localhost:8090 \
CIVI_LOGIN_URL="$(docker exec -w /var/www/default/htdocs/httpdocs my_php_container drush uli --uid=1 | tail -1)" \
SEED_FILE=$PWD/.seed/seed.json \
CIVI_CV_CMD='docker exec -w /var/www/default/htdocs/httpdocs my_php_container cv' \
npm test
```

| Variable | Purpose |
| --- | --- |
| `CIVI_BASE_URL` | Base URL of the site under test. Use the host the site knows itself by, or its own redirects will drop the session. |
| `CIVI_LOGIN_URL` | A one-time login link from `drush uli`. Preferred: no password goes near the test run. |
| `CIVI_USER` / `CIVI_PASS` | Alternative to the above — the normal login form. |
| `SEED_FILE` | JSON written by the seed script. Defaults to `.seed/seed.json`. |
| `CIVI_CV_CMD` | Optional. A command that reaches `cv` on the site, used to read the delivered mail. |
| `CIVI_MAILHOG_URL` | Optional. Defaults to `http://mailhog:8025`, resolved from inside the site. |

Without `CIVI_CV_CMD` the journeys still run and still assert the send happened,
but the **content** of the sent emails is not checked — the tests say so in
their output. Spec 06 checks its content from the downloaded PDF and does not
need `cv`.

Mail is read from MailHog first and from CiviCRM's `civicrm_mailing_spool` table
second, both reached through the site. It has to come from one of those: the
Email activity CiviCRM records keeps the template **as written**, tokens and
all, so it cannot tell you whether the Direct Debit content resolved.

Run one journey with `npx playwright test journeys/06-print-direct-debit-letters.spec.js`,
and watch it with `--headed`.

Verify against both CiviCRM lines in use — a compuclient 4.x site (CiviCRM 5.75)
and a compuclient 7.x site (CiviCRM 6.4.x) — since the letter path behaves
differently on each.

## Notes

* Videos of each run land in `test-results/`. Keep the ones worth attaching to a
  ticket; the directory itself is ignored by git.
* The specs run one at a time: they share a single login, and CiviPlus sites cap
  concurrent sessions per user.
* Search fields are set through CiviCRM's own jQuery rather than Playwright's
  form helpers, because these are select2 widgets whose change handlers are what
  CiviCRM listens to. When a field or option cannot be found the error lists
  what *was* available, which is usually enough to spot a version difference.
* The contribution date range in spec 03 is best effort: if that CiviCRM version
  names those fields differently the journey still runs, filtered by payment
  method and status, and says so in the output.
* The Advanced Search criteria panes are `<details>` elements whose contents
  CiviCRM fetches the first time they are opened, so the activity fields do not
  exist until the pane has been expanded.
* In CiviCRM 5.75 "Display Results As" is a select, and the option for
  memberships is labelled **Memberships**.

Verified against CiviCRM 5.75.0 with Manual Direct Debit on 19 August 2026: all
six journeys, with spec 02 failing as described above.
