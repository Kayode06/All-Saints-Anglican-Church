<?php
/**
 * admin/login.php
 * ---------------------------------------------------------------------
 * Simple username/password login for church office staff.
 * - Passwords are checked with password_verify() against the bcrypt
 *   hash stored in admin_users.password_hash.
 * - A CSRF token protects the login form itself.
 * - Failed login attempts show a generic message (we don't reveal
 *   whether the username or the password was wrong).
 * ---------------------------------------------------------------------
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Already logged in? Go straight to the dashboard.
if (!empty($_SESSION['admin_id'])) {
    header('Location: /admin/dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        $error = 'Your session expired. Please try again.';
    } else {
        $username = clean_input($_POST['username'] ?? '');
        $password = $_POST['password'] ?? ''; // not htmlspecialchars'd — passwords may contain special chars

        $stmt = $pdo->prepare('SELECT * FROM admin_users WHERE username = :username LIMIT 1');
        $stmt->execute([':username' => $username]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password_hash'])) {
            // Regenerate the session ID on login to prevent session fixation.
            session_regenerate_id(true);
            $_SESSION['admin_id']   = $admin['id'];
            $_SESSION['admin_name'] = $admin['full_name'];
            header('Location: /admin/dashboard.php');
            exit;
        }

        $error = 'Invalid username or password.';
    }
}

$token = csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login — <?php echo e(CHURCH_NAME); ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<div class="login-wrap">
    <div class="login-card">
        <h2>Admin Login</h2>
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo e($error); ?></div>
        <?php endif; ?>
        <form method="post" novalidate>
            <input type="hidden" name="csrf_token" value="<?php echo e($token); ?>">
            <div class="field">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required autofocus>
            </div>
            <div class="field">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="btn">Log In</button>
        </form>
    </div>
</div>
</body>
</html>
