<?php
/**
 * admin/includes/auth_check.php
 * ---------------------------------------------------------------------
 * Include this at the very top of every protected admin page. Redirects
 * to the login page if the visitor isn't authenticated as an admin.
 * ---------------------------------------------------------------------
 */
require_once __DIR__ . '/../../includes/config.php';

if (empty($_SESSION['admin_id'])) {
    header('Location: /admin/login.php');
    exit;
}
