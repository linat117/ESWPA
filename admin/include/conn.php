<?php
/**
 * Admin Panel Database Configuration
 * Auto-detects environment and connects to appropriate database
 * 
 * Localhost: Uses XAMPP MySQL with root user
 * Production: Uses production database credentials
 * 
 * Last Updated: December 23, 2025
 */

if ($_SERVER['HTTP_HOST'] == 'localhost' || $_SERVER['HTTP_HOST'] == '127.0.0.1' || strpos($_SERVER['HTTP_HOST'], 'localhost') !== false) {
    // For localhost/development
    $db_host = "localhost";
    $db_user = "root";
    $db_pass = "";
    $db_name = "ethiosocialworks";
} else {
    // For production server (ethiosocialworker.org)
    $db_host = "localhost";
    $db_user = "ethiosdt_new_user";
    $db_pass = "Ol9xN*dS7B=jX%}o";
    $db_name = "ethiosdt_new_db";
}

// Create database connection
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to UTF-8
$conn->set_charset("utf8mb4");
