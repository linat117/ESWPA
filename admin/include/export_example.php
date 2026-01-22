<?php
/**
 * Example usage of export_handler.php
 * This file demonstrates how to use the export functionality
 * 
 * This is a reference example - delete or modify as needed
 */

session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: auth-login.php");
    exit();
}

include 'include/conn.php';
include 'include/export_handler.php';

// Example: Export members data
if (isset($_GET['export'])) {
    // Fetch data from database
    $query = "SELECT id, fullname, email, phone, qualification, approval_status, created_at 
              FROM registrations 
              ORDER BY created_at DESC";
    $result = $conn->query($query);
    
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    
    // Define headers
    $headers = ['ID', 'Full Name', 'Email', 'Phone', 'Qualification', 'Approval Status', 'Created At'];
    
    // Process export
    $format = $_GET['export'];
    processExport($data, $headers, 'members', $format, 'Members Report');
}

// Regular page content would go here...
?>

