<?php
/**
 * admin/export_pdf.php
 * ---------------------------------------------------------------------
 * Renders the filtered donation list as a clean, print-optimized HTML
 * report. The admin clicks "Print" (or "Save as PDF" in the browser's
 * print dialog) to get a PDF — this avoids adding a heavy PDF library
 * dependency for a basic report.
 *
 * For a one-click, no-dialog PDF download instead, install a library
 * such as dompdf or TCPDF via Composer and render this same HTML
 * through it — the query/filter logic below does not need to change.
 * ---------------------------------------------------------------------
 */
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

$search   = clean_input($_GET['search'] ?? '');
$category = clean_input($_GET['category'] ?? '');
$status   = clean_input($_GET['status'] ?? '');
$dateFrom = clean_input($_GET['date_from'] ?? '');
$dateTo   = clean_input($_GET['date_to'] ?? '');

$conditions = [];
$params = [];

if ($search !== '') {
    $conditions[] = '(full_name LIKE :search OR email LIKE :search OR phone LIKE :search OR reference LIKE :search)';
    $params[':search'] = '%' . $search . '%';
}
if ($category !== '' && is_valid_category($category)) {
    $conditions[] = 'category = :category';
    $params[':category'] = $category;
}
if ($status !== '' && in_array($status, ['pending', 'success', 'failed'], true)) {
    $conditions[] = 'status = :status';
    $params[':status'] = $status;
}
if ($dateFrom !== '') {
    $conditions[] = 'DATE(created_at) >= :date_from';
    $params[':date_from'] = $dateFrom;
}
if ($dateTo !== '') {
    $conditions[] = 'DATE(created_at) <= :date_to';
    $params[':date_to'] = $dateTo;
}

$whereSql = $conditions ? ('WHERE ' . implode(' AND ', $conditions)) : '';
$stmt = $pdo->prepare("SELECT * FROM donations $whereSql ORDER BY created_at DESC");
$stmt->execute($params);
$donations = $stmt->fetchAll();

$grandTotal = array_sum(array_column(array_filter($donations, fn($d) => $d['status'] === 'success'), 'amount'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Donations Report — <?php echo e(CHURCH_NAME); ?></title>
    <style>
        body { font-family: Arial, sans-serif; color: #1a1a1a; padding: 20px; }
        h1 { color: #0a2472; margin-bottom: 0; }
        .subtitle { color: #666; margin-top: 4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 0.85rem; }
        th, td { border: 1px solid #ccc; padding: 6px 8px; text-align: left; }
        th { background: #0a2472; color: #fff; }
        tfoot td { font-weight: bold; background: #f3e4b3; }
        .print-btn { margin-bottom: 16px; background:#d4af37; border:none; padding:10px 18px; border-radius:6px; cursor:pointer; font-weight:bold; }
        @media print { .print-btn { display: none; } }
    </style>
</head>
<body>
    <button class="print-btn" onclick="window.print()">Print / Save as PDF</button>

    <h1><?php echo e(CHURCH_NAME); ?></h1>
    <p class="subtitle">Donations Report — generated <?php echo e(date('d M Y, h:i A')); ?></p>

    <table>
        <thead>
            <tr>
                <th>Date</th><th>Full Name</th><th>Email</th><th>Phone</th>
                <th>Category</th><th>Amount (₦)</th><th>Status</th><th>Reference</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($donations as $d): ?>
            <tr>
                <td><?php echo e(date('d M Y, h:i A', strtotime($d['created_at']))); ?></td>
                <td><?php echo e($d['full_name']); ?></td>
                <td><?php echo e($d['email']); ?></td>
                <td><?php echo e($d['phone']); ?></td>
                <td><?php echo e($d['category']); ?></td>
                <td><?php echo number_format($d['amount'], 2); ?></td>
                <td><?php echo e(ucfirst($d['status'])); ?></td>
                <td><?php echo e($d['reference']); ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$donations): ?>
            <tr><td colspan="8">No donations match the selected filters.</td></tr>
        <?php endif; ?>
        </tbody>
        <tfoot>
            <tr><td colspan="5">Total (Successful Donations)</td><td colspan="3">&#8358;<?php echo number_format($grandTotal, 2); ?></td></tr>
        </tfoot>
    </table>
</body>
</html>
