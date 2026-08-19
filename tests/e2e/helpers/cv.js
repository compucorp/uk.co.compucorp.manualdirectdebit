'use strict';

const { execFileSync } = require('child_process');

/**
 * Runs PHP inside the site under test, through cv.
 *
 * Used for the few things a browser cannot see — mainly reading back the mail
 * that was actually sent. CIVI_CV_CMD is the command that reaches cv on the
 * site, for example:
 *
 *   docker exec -w /var/www/default/htdocs/httpdocs my_php_container cv
 */

/**
 * @return {boolean}
 *   Whether a cv command was configured.
 */
function hasCv () {
  return Boolean(process.env.CIVI_CV_CMD && process.env.CIVI_CV_CMD.trim());
}

/**
 * Evaluates a PHP expression on the site and returns the decoded result.
 *
 * The command is run without a shell, so PHP variables in the expression are
 * not at risk of being expanded by the shell.
 *
 * @param {string} php
 *   PHP to run, which must return a JSON-encodable value.
 *
 * @return {*}
 *   The returned value.
 */
function cvEval (php) {
  if (!hasCv()) {
    throw new Error('CIVI_CV_CMD is not set — see tests/e2e/README.md.');
  }

  const parts = process.env.CIVI_CV_CMD.trim().split(/\s+/);
  const output = execFileSync(parts[0], [...parts.slice(1), 'ev', php], {
    encoding: 'utf8',
    maxBuffer: 64 * 1024 * 1024,
    // Sites with noisy extensions write deprecation notices to stderr, which
    // would otherwise be mixed into the test output.
    stdio: ['ignore', 'pipe', 'ignore'],
  });

  // Sites with noisy extensions print PHP notices ahead of cv's own output.
  const json = output
    .split('\n')
    .filter((line) => !/^\s*\[PHP (Notice|Warning|Deprecated|Error|Fatal)/.test(line))
    .join('\n')
    .trim();

  if (json === '') {
    return null;
  }

  try {
    return JSON.parse(json);
  }
  catch (error) {
    throw new Error(`Could not read cv's output as JSON:\n${output}`);
  }
}

/**
 * Undoes the quoted-printable encoding of a spooled message.
 *
 * @param {string} body
 *   The raw message body.
 *
 * @return {string}
 *   The decoded body.
 */
function decodeQuotedPrintable (body) {
  return String(body)
    .replace(/=\r?\n/g, '')
    .replace(/=([0-9A-Fa-f]{2})/g, (match, hex) => String.fromCharCode(parseInt(hex, 16)));
}

/**
 * Reads the most recent message sent to an address.
 *
 * Relies on the seed script having pointed the mailer at the database.
 *
 * @param {string} recipientEmail
 *   Address the message was sent to.
 *
 * @return {string|null}
 *   The decoded message, or null if nothing was spooled.
 */
function latestSpooledEmail (recipientEmail) {
  if (!/^[\w.+-]+@[\w.-]+$/.test(recipientEmail)) {
    throw new Error(`Refusing to query the spool for "${recipientEmail}".`);
  }

  const body = cvEval(
    'return CRM_Core_DAO::singleValueQuery("SELECT body FROM civicrm_mailing_spool ' +
    `WHERE recipient_email = '${recipientEmail}' ORDER BY id DESC LIMIT 1");`
  );

  return body === null ? null : decodeQuotedPrintable(body);
}

module.exports = { hasCv, cvEval, decodeQuotedPrintable, latestSpooledEmail };
