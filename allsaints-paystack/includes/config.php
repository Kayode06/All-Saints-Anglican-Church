<?php
/**
 * config.php
 * ---------------------------------------------------------------------
 * Central configuration file. Every other file includes this one.
 *
 * SECURITY NOTE:
 * - Never commit real secret keys to a public Git repository.
 * - In production, prefer loading these from environment variables
 *   (e.g. via getenv()) instead of hardcoding them here.
 * - Your Paystack SECRET key must never appear in any JavaScript /
 *   frontend file. Only the PUBLIC key goes into the browser.
 * ---------------------------------------------------------------------
 */

// ---- Database credentials ----
define('DB_HOST', 'localhost');
define('DB_NAME', 'allsaints_donations');
define('DB_USER', 'your_db_username');
define('DB_PASS', 'your_db_password');

// ---- Paystack API keys ----
// Get these from https://dashboard.paystack.com/#/settings/developers
// Use the TEST keys while developing, then switch to LIVE keys when the
// site goes live.
define('PAYSTACK_PUBLIC_KEY', 'pk_test_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx');
define('PAYSTACK_SECRET_KEY', 'sk_test_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx');

// ---- Church / site details ----
define('CHURCH_NAME', "All Saints' Anglican Church, Yaba");
define('CHURCH_EMAIL', 'no-reply@allsaintsyaba.org'); // used as the "From" address for receipts
define('SITE_URL', 'https://allsaintsyaba.org'); // change to your live domain, no trailing slash

// ---- Donation categories ----
// Used to populate the dropdown on the donation form AND to validate
// submissions server-side, so a donor can't submit an arbitrary string.
define('DONATION_CATEGORIES', [
    'Tithe',
    'Offering',
    'Thanksgiving',
    'First Fruits',
    'Building Project',
    'Mission Support',
    'Harvest Donation',
    'Welfare Fund',
    'Special Thanksgiving',
    'Other Donation',
]);

// ---- Session ----
// Started here once so both the public site and admin area can rely on
// it (needed for CSRF tokens and admin login state).
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ---- Error display ----
// Turn OFF display_errors in production; log instead. Left visible here
// only to make local development easier.
ini_set('display_errors', 0);
error_reporting(E_ALL);
