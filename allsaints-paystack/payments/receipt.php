<?php
/**
 * payments/receipt.php
 * ---------------------------------------------------------------------
 * Looks up a donation by its reference and displays a printable
 * receipt. This page is READ-ONLY — it never writes to the database,
 * it only reads the record that verify.php already saved.
 * ---------------------------------------------------------------------
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

$reference = isset($_GET['reference']) ? clean_input($_GET['reference']) : '';

$stmt = $pdo->prepare('SELECT * FROM donations WHERE reference = :reference LIMIT 1');
$stmt->execute([':reference' => $reference]);
$donation = $stmt->fetch();

$pageTitle = 'Donation Receipt';
require __DIR__ . '/../includes/header.php';

if (!$donation) {
    echo '<main class="container"><div class="card"><div class="alert alert-error">No donation found for that reference.</div></div></main>';
    require __DIR__ . '/../includes/footer.php';
    exit;
}

$statusClass = $donation['status'] === 'success' ? 'receipt-status-success' : 'receipt-status-failed';
?>

<main class="container">
    <div class="receipt">
        <h2><?php echo e(CHURCH_NAME); ?></h2>
        <p style="text-align:center;color:var(--text-muted);margin-top:-10px;">Official Donation Receipt</p>

        <div class="receipt-row"><span>Status</span><span class="<?php echo $statusClass; ?>"><?php echo e(ucfirst($donation['status'])); ?></span></div>
        <div class="receipt-row"><span>Reference</span><span><?php echo e($donation['reference']); ?></span></div>
        <div class="receipt-row"><span>Donor Name</span><span><?php echo e($donation['full_name']); ?></span></div>
        <div class="receipt-row"><span>Email</span><span><?php echo e($donation['email']); ?></span></div>
        <div class="receipt-row"><span>Phone</span><span><?php echo e($donation['phone']); ?></span></div>
        <div class="receipt-row"><span>Category</span><span><?php echo e($donation['category']); ?></span></div>
        <div class="receipt-row"><span>Amount</span><span>&#8358;<?php echo number_format($donation['amount'], 2); ?></span></div>
        <div class="receipt-row"><span>Date</span><span><?php echo e($donation['paid_at'] ? date('d M Y, h:i A', strtotime($donation['paid_at'])) : date('d M Y, h:i A', strtotime($donation['created_at']))); ?></span></div>

        <?php if (!empty($donation['prayer_request'])): ?>
            <div class="receipt-row" style="flex-direction:column;align-items:flex-start;">
                <span>Prayer Request</span>
                <span style="margin-top:4px;"><?php echo nl2br(e($donation['prayer_request'])); ?></span>
            </div>
        <?php endif; ?>

        <?php if ($donation['status'] !== 'success'): ?>
            <div class="alert alert-error" style="margin-top:16px;">
                This transaction was not successful. No funds were received. If you believe this is an error, please contact the church office with the reference above.
            </div>
        <?php endif; ?>

        <div class="receipt-actions no-print">
            <button class="btn btn-secondary" onclick="window.print()">Print Receipt</button>
            <a class="btn" href="/payments/donate.php" style="display:inline-block;width:auto;">Give Again</a>
        </div>
    </div>
</main>

<?php require __DIR__ . '/../includes/footer.php'; ?>
