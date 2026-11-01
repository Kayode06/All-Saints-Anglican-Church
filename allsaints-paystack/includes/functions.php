<?php
/**
 * functions.php
 * ---------------------------------------------------------------------
 * Reusable helper functions shared across the public site and the
 * admin dashboard: input sanitization, CSRF protection, reference
 * generation, and email sending.
 * ---------------------------------------------------------------------
 */

require_once __DIR__ . '/config.php';

/**
 * Clean a piece of user input for safe display / storage.
 * - trims whitespace
 * - strips tags
 * - converts special characters to HTML entities (defends against XSS
 *   when the value is later echoed into a page)
 */
function clean_input($value)
{
    $value = trim($value ?? '');
    $value = strip_tags($value);
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

/**
 * Escape a value for safe output in HTML (used when printing values
 * that were stored earlier, e.g. from the database, back into a page).
 */
function e($value)
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Generate and store a CSRF token in the session if one doesn't exist,
 * and return it. Include this as a hidden field in every form.
 */
function csrf_token()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify a submitted CSRF token against the one stored in the session.
 * Uses hash_equals() for a timing-safe comparison.
 */
function csrf_verify($submittedToken)
{
    return isset($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $submittedToken ?? '');
}

/**
 * Build a unique, hard-to-guess payment reference to hand to Paystack.
 * Prefixed with "ASC" (All Saints' Church) so references are
 * recognizable in the Paystack dashboard.
 */
function generate_reference()
{
    return 'ASC-' . date('Ymd-His') . '-' . bin2hex(random_bytes(4));
}

/**
 * Validate that a submitted donation category is one of the categories
 * we actually defined — prevents a tampered request from injecting an
 * arbitrary category string.
 */
function is_valid_category($category)
{
    return in_array($category, DONATION_CATEGORIES, true);
}

/**
 * Send a simple HTML email confirmation to the donor using PHP's
 * built-in mail() function.
 *
 * PRODUCTION NOTE: mail() is unreliable on most hosts and often lands
 * in spam. For real deployments, swap this out for PHPMailer or a
 * transactional email API (SendGrid, Mailgun, Postmark, Zeptomail, etc.)
 * using SMTP authentication. The function signature below can stay the
 * same, so the rest of the app doesn't need to change.
 */
function send_receipt_email($toEmail, $toName, $donation)
{
    $subject = CHURCH_NAME . ' — Donation Receipt';

    $amount = number_format($donation['amount'], 2);

    $body = "
    <html>
    <body style='font-family: Arial, sans-serif; color:#1a1a1a;'>
        <div style='max-width:600px;margin:auto;border:1px solid #d4af37;padding:20px;'>
            <h2 style='color:#0a2472;'>" . e(CHURCH_NAME) . "</h2>
            <p>Dear " . e($toName) . ",</p>
            <p>Thank you for your generous <strong>" . e($donation['category']) . "</strong> donation.
            Below is a summary of your transaction:</p>
            <table style='width:100%;border-collapse:collapse;'>
                <tr><td style='padding:6px 0;'><strong>Reference:</strong></td><td>" . e($donation['reference']) . "</td></tr>
                <tr><td style='padding:6px 0;'><strong>Category:</strong></td><td>" . e($donation['category']) . "</td></tr>
                <tr><td style='padding:6px 0;'><strong>Amount:</strong></td><td>&#8358;" . $amount . "</td></tr>
                <tr><td style='padding:6px 0;'><strong>Date:</strong></td><td>" . e(date('d M Y, h:i A')) . "</td></tr>
            </table>
            <p style='margin-top:20px;'>May God bless you abundantly for your giving.</p>
            <p style='color:#0a2472;'><strong>" . e(CHURCH_NAME) . "</strong></p>
        </div>
    </body>
    </html>
    ";

    $headers  = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8" . "\r\n";
    $headers .= 'From: ' . CHURCH_NAME . ' <' . CHURCH_EMAIL . '>' . "\r\n";

    // @ suppresses warnings if mail() isn't configured on this server;
    // the donation is still saved and the receipt page still works even
    // if the email fails to send.
    return @mail($toEmail, $subject, $body, $headers);
}
