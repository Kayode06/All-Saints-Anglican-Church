<?php
/**
 * admin/includes/sidebar.php
 * Shared sidebar navigation for the admin dashboard.
 * Expects $activePage to be set by the including file (e.g. 'dashboard').
 */
$activePage = $activePage ?? '';
?>
<aside class="admin-sidebar">
    <h3><?php echo e(CHURCH_NAME); ?></h3>
    <a href="/admin/dashboard.php" class="<?php echo $activePage === 'dashboard' ? 'active' : ''; ?>">Dashboard</a>
    <a href="/admin/view_donations.php" class="<?php echo $activePage === 'donations' ? 'active' : ''; ?>">All Donations</a>
    <a href="/admin/logout.php">Log Out</a>
</aside>
