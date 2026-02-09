<?php
/**
 * Admin Panel Database Configuration
 * Auto-detects environment and connects to appropriate database
 *
 * Localhost: Uses WAMP/XAMPP MySQL. Set $db_pass if your root user has a password.
 * Optional: create conn.local.php in this folder with $db_host, $db_user, $db_pass, $db_name to override.
 *
 * Last Updated: December 23, 2025
 */

if ($_SERVER['HTTP_HOST'] == 'localhost' || $_SERVER['HTTP_HOST'] == '127.0.0.1' || strpos($_SERVER['HTTP_HOST'], 'localhost') !== false) {
    // For localhost/development (WAMP/XAMPP)
    $db_host = "localhost";
    $db_user = "root";
    $db_pass = "";  // Set your MySQL root password here if WAMP requires it
    $db_name = "ethiosocialworks";
} else {
    // For production server (ethiosocialworker.org)
    $db_host = "localhost";
    $db_user = "ethiosdt_new_user";
    $db_pass = "Ol9xN*dS7B=jX%}o";
    $db_name = "ethiosdt_new_db";
}

// Optional local override (e.g. conn.local.php with your password) – not in version control
if (file_exists(__DIR__ . '/conn.local.php')) {
    include __DIR__ . '/conn.local.php';
}

// Create database connection (catch exception to avoid 500 and show clear message)
try {
    $conn = new mysqli($db_host, $db_user, $db_pass ?? '', $db_name);
} catch (mysqli_sql_exception $e) {
    $msg = $e->getMessage();
    if (strpos($msg, 'Access denied') !== false && (isset($db_pass) ? $db_pass : '') === '') {
        $msg = 'Database connection failed: MySQL root user requires a password on this system. Set $db_pass in admin/include/conn.php or create admin/include/conn.local.php with your local credentials.';
    }
    if (php_sapi_name() === 'cli') {
        die($msg . "\n");
    }
    header('Content-Type: text/html; charset=utf-8');
    http_response_code(503);
    die('<h1>Database configuration error</h1><p>' . htmlspecialchars($msg) . '</p><p><a href="auth-login.php">Back to login</a></p>');
}

// Check connection
if ($conn->connect_error) {
    $err = $conn->connect_error;
    if (php_sapi_name() === 'cli') {
        die("Connection failed: " . $err . "\n");
    }
    header('Content-Type: text/html; charset=utf-8');
    http_response_code(503);
    die('<h1>Database connection failed</h1><p>' . htmlspecialchars($err) . '</p><p><a href="auth-login.php">Back to login</a></p>');
}

// Set charset to UTF-8
$conn->set_charset("utf8mb4");
