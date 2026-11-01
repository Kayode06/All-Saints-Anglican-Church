<?php
/**
 * admin/export_excel.php
 * ---------------------------------------------------------------------
 * Exports the currently filtered donation list as a CSV file, which
 * opens natively in Microsoft Excel / Google Sheets. This keeps the
 * project dependency-free (no external libraries needed).
 *
 * For a native .xlsx file with styling instead of CSV, install
 * PhpSpreadsheet via Composer and swap the output block below for a
 * Spreadsheet/Xlsx writer — the filtering logic above it stays the same.
 * ---------------------------------------------------------------------
 */
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

// ---- Re-apply the same filters used in view_donations.php ----
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

// ---- Stream the CSV directly to the browser as a download ----
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="donations_' . date('Ymd_His') . '.csv"');

$out = fopen('php://output', 'w');

// Add a UTF-8 BOM so Excel renders the ₦ symbol and names correctly.
fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));

fputcsv($out, ['Date', 'Full Name', 'Email', 'Phone', 'Category', 'Amount (NGN)', 'Status', 'Channel', 'Reference']);

foreach ($donations as $d) {
    fputcsv($out, [
        $d['created_at'],
        $d['full_name'],
        $d['email'],
        $d['phone'],
        $d['category'],
        number_format($d['amount'], 2, '.', ''),
        ucfirst($d['status']),
        $d['channel'],
        $d['reference'],
    ]);
}

fclose($out);
exit;
