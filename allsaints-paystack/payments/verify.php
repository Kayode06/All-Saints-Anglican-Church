<?php
/**
 * payments/verify.php
 * ---------------------------------------------------------------------
 * This is the MOST IMPORTANT security step in the whole system.
 *
 * The browser popup (Paystack Inline) tells us "payment succeeded",
 * but a browser can be manipulated. We NEVER trust that message alone.
 * Instead, once the popup finishes, the browser is redirected here with
 * just the transaction reference, and THIS server independently asks
 * Paystack's servers "did this reference actually get paid?" using our
 * secret key. Only if Paystack's own API confirms success do we record
 * the donation as successful.
 * ---------------------------------------------------------------------
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

$reference = isset($_GET['reference']) ? clean_input($_GET['reference']) : '';

if ($reference === '') {
    http_response_code(400);
    die('Missing transaction reference.');
}

/**
 * Call Paystack's "Verify Transaction" endpoint using cURL.
 * Docs: https://paystack.com/docs/api/transaction/#verify
 */
function paystack_verify_transaction($reference)
{
    $url = 'https://api.paystack.co/transaction/verify/' . rawurlencode($reference);

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . PAYSTACK_SECRET_KEY,
            'Cache-Control: no-cache',
        ],
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);

    $response = curl_exec($ch);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
        error_log('Paystack verify cURL error: ' . $curlError);
        return null;
    }

    return json_decode($response, true);
}

$result = paystack_verify_transaction($reference);

if (!$result || empty($result['status'])) {
    // Could not reach Paystack, or the API returned an error envelope.
    $errorMessage = 'We could not verify your payment. If you were charged, please contact the church office with reference: ' . e($reference);
    require __DIR__ . '/../includes/header.php';
    echo '<main class="container"><div class="card"><div class="alert alert-error">' . $errorMessage . '</div></div></main>';
    require __DIR__ . '/../includes/footer.php';
    exit;
}

$data = $result['data'];
$paystackStatus = $data['status'] ?? 'failed'; // "success", "failed", "abandoned", etc.
$amountKobo     = $data['amount'] ?? 0;
$amountNaira    = $amountKobo / 100;
$channel        = $data['channel'] ?? null;
$paidAt         = !empty($data['paid_at']) ? date('Y-m-d H:i:s', strtotime($data['paid_at'])) : null;
$customerEmail  = $data['customer']['email'] ?? '';
$metadata       = $data['metadata'] ?? [];

$fullName      = clean_input($metadata['full_name'] ?? 'Donor');
$phone         = clean_input($metadata['phone'] ?? '');
$category      = clean_input($metadata['category'] ?? 'Other Donation');
$prayerRequest = clean_input($metadata['prayer_request'] ?? '');

if (!is_valid_category($category)) {
    $category = 'Other Donation';
}

$dbStatus = ($paystackStatus === 'success') ? 'success' : 'failed';

// ---------------------------------------------------------------------
// Save (or update) the donation record.
// We use "INSERT ... ON DUPLICATE KEY UPDATE" keyed on the unique
// `reference` column, so refreshing this page never creates duplicate
// rows for the same transaction.
// ---------------------------------------------------------------------
$stmt = $pdo->prepare('
    INSERT INTO donations
        (full_name, email, phone, category, amount, prayer_request, reference, status, channel, paid_at)
    VALUES
        (:full_name, :email, :phone, :category, :amount, :prayer_request, :reference, :status, :channel, :paid_at)
    ON DUPLICATE KEY UPDATE
        status = VALUES(status),
        channel = VALUES(channel),
        paid_at = VALUES(paid_at)
');

$stmt->execute([
    ':full_name'      => $fullName,
    ':email'          => $customerEmail,
    ':phone'          => $phone,
    ':category'       => $category,
    ':amount'         => $amountNaira,
    ':prayer_request' => $prayerRequest,
    ':reference'      => $reference,
    ':status'         => $dbStatus,
    ':channel'        => $channel,
    ':paid_at'        => $paidAt,
]);

// Send the email receipt only when the payment actually succeeded.
if ($dbStatus === 'success') {
    send_receipt_email($customerEmail, $fullName, [
        'reference' => $reference,
        'category'  => $category,
        'amount'    => $amountNaira,
    ]);
}

// Send the donor on to the printable receipt page.
header('Location: /payments/receipt.php?reference=' . urlencode($reference));
exit;
