<?php
/**
 * admin/logout.php
 * ---------------------------------------------------------------------
 * Destroys the admin session and returns to the login page.
 * ---------------------------------------------------------------------
 */
require_once __DIR__ . '/../includes/config.php';

$_SESSION = [];
session_destroy();

header('Location: /admin/login.php');
exit;
