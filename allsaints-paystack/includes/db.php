<?php
/**
 * db.php
 * ---------------------------------------------------------------------
 * Creates a single shared PDO connection to MySQL.
 *
 * We use PDO with prepared statements throughout the project instead of
 * the old mysqli/mysql_* functions. Prepared statements are the primary
 * defense against SQL Injection: user input is always sent separately
 * from the SQL command, never concatenated into the query string.
 * ---------------------------------------------------------------------
 */

require_once __DIR__ . '/config.php';

try {
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';

    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        // Throw exceptions on error instead of failing silently.
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        // Return associative arrays by default (['column' => value]).
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        // Use real prepared statements (not emulated) for stronger
        // protection against SQL injection.
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    // Never leak raw DB error details (host, credentials, schema) to
    // the end user — log it instead and show a generic message.
    error_log('Database connection failed: ' . $e->getMessage());
    die('Sorry, we are unable to connect right now. Please try again later.');
}
