<?php
session_start();
include 'conn.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth-login.php");
    exit();
}

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    header("Location: ../email_templates.php?error=Invalid template ID");
    exit();
}

// Check if template is being used
$checkQuery = "SELECT COUNT(*) as count FROM email_automation_settings WHERE template_id = ?";
$checkStmt = $conn->prepare($checkQuery);
$checkStmt->bind_param("i", $id);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();
$check = $checkResult->fetch_assoc();
$checkStmt->close();

if ($check['count'] > 0) {
    header("Location: ../email_templates.php?error=Cannot delete template. It is being used by automation settings.");
    exit();
}

// Delete template
$query = "DELETE FROM email_templates WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    header("Location: ../email_templates.php?success=Template deleted successfully");
} else {
    header("Location: ../email_templates.php?error=Failed to delete template: " . $conn->error);
}

$stmt->close();
$conn->close();
exit();

