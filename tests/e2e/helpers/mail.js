'use strict';

const { hasCv, cvEval, decodeQuotedPrintable, latestSpooledEmail } = require('./cv');

const MAILHOG_URL = process.env.CIVI_MAILHOG_URL || 'http://mailhog:8025';

/**
 * Reads the message a site delivered to an address.
 *
 * Only the delivered message contains the rendered Direct Debit content: the
 * Email activity CiviCRM records keeps the template as written, tokens and
 * all. Two sources are tried, both reached through the site itself:
 *
 *  1. MailHog, which is what the local CiviPlus sites send to.
 *  2. CiviCRM's own mail spool, for sites configured to spool to the database.
 *
 * @param {string} recipientEmail
 *   Address the message was sent to.
 *
 * @return {string|null}
 *   The decoded message body, or null if nothing was delivered.
 */
function latestMessageFor (recipientEmail) {
  if (!/^[\w.+-]+@[\w.-]+$/.test(recipientEmail)) {
    throw new Error(`Refusing to look up mail for "${recipientEmail}".`);
  }

  const fromMailhog = cvEval(
    '$json = @file_get_contents(' +
    `"${MAILHOG_URL}/api/v2/search?kind=to&limit=1&query=" . urlencode("${recipientEmail}")` +
    '); $data = $json ? json_decode($json, TRUE) : NULL; ' +
    'return empty($data["items"]) ? NULL : $data["items"][0]["Content"]["Body"];'
  );

  if (fromMailhog) {
    return decodeQuotedPrintable(fromMailhog);
  }

  return latestSpooledEmail(recipientEmail);
}

/**
 * @return {boolean}
 *   Whether delivered mail can be read at all.
 */
function canReadMail () {
  return hasCv();
}

module.exports = { latestMessageFor, canReadMail };
