<?php
/**
 * admin/dashboard.php
 * ---------------------------------------------------------------------
 * Landing page after admin login. Shows quick summary stats:
 * total successful donations, total amount raised, today's total, and
 * a breakdown by category. All queries use prepared statements even
 * though there's no user input here, to keep the pattern consistent.
 * ---------------------------------------------------------------------
 */
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

$activePage = 'dashboard';

$totalAmount = $pdo->query("SELECT COALESCE(SUM(amount),0) AS total FROM donations WHERE status = 'success'")->fetch()['total'];
$totalCount  = $pdo->query("SELECT COUNT(*) AS c FROM donations WHERE status = 'success'")->fetch()['c'];
$todayAmount = $pdo->query("SELECT COALESCE(SUM(amount),0) AS total FROM donations WHERE status = 'success' AND DATE(paid_at) = CURDATE()")->fetch()['total'];
$pendingCount = $pdo->query("SELECT COUNT(*) AS c FROM donations WHERE status = 'pending'")->fetch()['c'];

$byCategory = $pdo->query("
    SELECT category, COUNT(*) AS count, COALESCE(SUM(amount),0) AS total
    FROM donations
    WHERE status = 'success'
    GROUP BY category
    ORDER BY total DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard — <?php echo e(CHURCH_NAME); ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="admin-body">
<div class="admin-shell">
    <?php require __DIR__ . '/includes/sidebar.php'; ?>

    <main class="admin-main">
        <div class="admin-topbar">
            <h2 style="color:var(--church-blue);margin:0;">Dashboard</h2>
            <span>Welcome, <?php echo e($_SESSION['admin_name']); ?></span>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Total Raised</div>
                <div class="stat-value">&#8358;<?php echo number_format($totalAmount, 2); ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Successful Donations</div>
                <div class="stat-value"><?php echo (int) $totalCount; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Raised Today</div>
                <div class="stat-value">&#8358;<?php echo number_format($todayAmount, 2); ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Pending Transactions</div>
                <div class="stat-value"><?php echo (int) $pendingCount; ?></div>
            </div>
        </div>

        <div class="table-card">
            <h3 style="color:var(--church-blue);margin-top:0;">Breakdown by Category</h3>
            <table class="data-table">
                <thead>
                    <tr><th>Category</th><th># Donations</th><th>Total Amount</th></tr>
                </thead>
                <tbody>
                <?php foreach ($byCategory as $row): ?>
                    <tr>
                        <td><?php echo e($row['category']); ?></td>
                        <td><?php echo (int) $row['count']; ?></td>
                        <td>&#8358;<?php echo number_format($row['total'], 2); ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$byCategory): ?>
                    <tr><td colspan="3">No successful donations yet.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>
</body>
</html>
