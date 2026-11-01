<?php
/**
 * admin/view_donations.php
 * ---------------------------------------------------------------------
 * Searchable, filterable table of all donations.
 * - Search: matches full_name, email, phone, or reference.
 * - Filter: by category and by date range.
 * - All filter values are bound as parameters in a prepared statement
 *   — never concatenated directly into SQL — to prevent SQL injection.
 * - Export to CSV/Excel and PDF-friendly print reuse the SAME filters
 *   (passed through as query-string parameters).
 * ---------------------------------------------------------------------
 */
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

$activePage = 'donations';

// ---- Read & sanitize filters from the query string ----
$search   = clean_input($_GET['search'] ?? '');
$category = clean_input($_GET['category'] ?? '');
$status   = clean_input($_GET['status'] ?? '');
$dateFrom = clean_input($_GET['date_from'] ?? '');
$dateTo   = clean_input($_GET['date_to'] ?? '');

// ---- Build the WHERE clause dynamically but SAFELY using bound params ----
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

$sql = "SELECT * FROM donations $whereSql ORDER BY created_at DESC LIMIT 500";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$donations = $stmt->fetchAll();

// Build a query string (without the page path) to hand to the export links
// so exports respect the same filters currently applied on screen.
$exportQuery = http_build_query([
    'search' => $search, 'category' => $category, 'status' => $status,
    'date_from' => $dateFrom, 'date_to' => $dateTo,
]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Donations — <?php echo e(CHURCH_NAME); ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="admin-body">
<div class="admin-shell">
    <?php require __DIR__ . '/includes/sidebar.php'; ?>

    <main class="admin-main">
        <div class="admin-topbar">
            <h2 style="color:var(--church-blue);margin:0;">All Donations</h2>
            <div class="export-buttons no-print">
                <a href="/admin/export_excel.php?<?php echo $exportQuery; ?>">Export Excel (CSV)</a>
                <a href="/admin/export_pdf.php?<?php echo $exportQuery; ?>">Export / Print PDF</a>
            </div>
        </div>

        <div class="table-card">
            <form method="get" class="filters-bar no-print">
                <div class="field">
                    <label for="search">Search</label>
                    <input type="text" id="search" name="search" placeholder="Name, email, phone, reference" value="<?php echo e($search); ?>">
                </div>
                <div class="field">
                    <label for="category">Category</label>
                    <select id="category" name="category">
                        <option value="">All Categories</option>
                        <?php foreach (DONATION_CATEGORIES as $cat): ?>
                            <option value="<?php echo e($cat); ?>" <?php echo $category === $cat ? 'selected' : ''; ?>><?php echo e($cat); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        <option value="">All Statuses</option>
                        <option value="success" <?php echo $status === 'success' ? 'selected' : ''; ?>>Success</option>
                        <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="failed" <?php echo $status === 'failed' ? 'selected' : ''; ?>>Failed</option>
                    </select>
                </div>
                <div class="field">
                    <label for="date_from">From</label>
                    <input type="date" id="date_from" name="date_from" value="<?php echo e($dateFrom); ?>">
                </div>
                <div class="field">
                    <label for="date_to">To</label>
                    <input type="date" id="date_to" name="date_to" value="<?php echo e($dateTo); ?>">
                </div>
                <div class="field">
                    <button type="submit" class="btn btn-secondary" style="width:auto;padding:11px 20px;">Filter</button>
                </div>
            </form>

            <table class="data-table">
                <thead>
                    <tr>
                        <th>Date</th><th>Donor</th><th>Email</th><th>Phone</th>
                        <th>Category</th><th>Amount</th><th>Status</th><th>Reference</th>
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
                        <td>&#8358;<?php echo number_format($d['amount'], 2); ?></td>
                        <td><span class="badge badge-<?php echo e($d['status']); ?>"><?php echo e(ucfirst($d['status'])); ?></span></td>
                        <td><?php echo e($d['reference']); ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$donations): ?>
                    <tr><td colspan="8">No donations match your filters.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>
</body>
</html>
