<?php
/**
 * Admin Unsubscribe/Resubscribe Handler
 * Handles admin actions to unsubscribe or resubscribe subscribers
 * 
 * Agent: agent_dev_1
 * Date: 2025-12-16
 */

session_start();

// Check if admin is logged in
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access.'
    ]);
    exit;
}

header('Content-Type: application/json');

// Include database connection
require_once __DIR__ . '/../include/conn.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method.'
    ]);
    exit;
}

$id = intval($_POST['id'] ?? 0);
$action = trim($_POST['action'] ?? '');

if ($id <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid subscriber ID.'
    ]);
    exit;
}

if (!in_array($action, ['unsubscribe', 'resubscribe'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid action.'
    ]);
    exit;
}

// Check if subscriber exists
$checkQuery = "SELECT id, status FROM email_subscribers WHERE id = ?";
$checkStmt = $conn->prepare($checkQuery);
$checkStmt->bind_param("i", $id);
$checkStmt->execute();
$result = $checkStmt->get_result();

if ($result->num_rows === 0) {
    $checkStmt->close();
    echo json_encode([
        'success' => false,
        'message' => 'Subscriber not found.'
    ]);
    exit;
}

$checkStmt->close();

// Perform action
if ($action === 'unsubscribe') {
    $updateQuery = "UPDATE email_subscribers SET 
                    status = 'unsubscribed', 
                    unsubscribed_at = NOW() 
                    WHERE id = ?";
    $updateStmt = $conn->prepare($updateQuery);
    $updateStmt->bind_param("i", $id);
    
    if ($updateStmt->execute()) {
        $updateStmt->close();
        echo json_encode([
            'success' => true,
            'message' => 'Subscriber unsubscribed successfully.'
        ]);
    } else {
        $error = $updateStmt->error;
        $updateStmt->close();
        error_log("Unsubscribe error: " . $error);
        echo json_encode([
            'success' => false,
            'message' => 'Error updating subscriber status.'
        ]);
    }
} else { // resubscribe
    $updateQuery = "UPDATE email_subscribers SET 
                    status = 'active', 
                    unsubscribed_at = NULL 
                    WHERE id = ?";
    $updateStmt = $conn->prepare($updateQuery);
    $updateStmt->bind_param("i", $id);
    
    if ($updateStmt->execute()) {
        $updateStmt->close();
        echo json_encode([
            'success' => true,
            'message' => 'Subscriber resubscribed successfully.'
        ]);
    } else {
        $error = $updateStmt->error;
        $updateStmt->close();
        error_log("Resubscribe error: " . $error);
        echo json_encode([
            'success' => false,
            'message' => 'Error updating subscriber status.'
        ]);
    }
}

