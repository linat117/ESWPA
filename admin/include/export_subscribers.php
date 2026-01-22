<?php
/**
 * Export Subscribers to CSV
 * Exports email subscribers list to CSV file
 * 
 * Agent: agent_dev_1
 * Date: 2025-12-16
 */

session_start();

// Check if admin is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth-login.php");
    exit;
}

// Include database connection
require_once __DIR__ . '/../include/conn.php';

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="subscribers_' . date('Y-m-d') . '.csv"');

// Create output stream
$output = fopen('php://output', 'w');

// Add BOM for UTF-8 (Excel compatibility)
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Add CSV headers
fputcsv($output, [
    'ID',
    'Email',
    'Name',
    'Status',
    'Source',
    'Subscribed Date',
    'Unsubscribed Date',
    'IP Address'
]);

// Fetch subscribers
$query = "SELECT * FROM email_subscribers ORDER BY subscribed_at DESC";
$result = mysqli_query($conn, $query);

// Add data rows
if (mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        fputcsv($output, [
            $row['id'],
            $row['email'],
            $row['name'] ?? '',
            $row['status'],
            $row['source'],
            $row['subscribed_at'],
            $row['unsubscribed_at'] ?? '',
            $row['ip_address'] ?? ''
        ]);
    }
}

fclose($output);
exit;

