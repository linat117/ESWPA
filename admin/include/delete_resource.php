<?php
session_start();
include 'conn.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth-login.php");
    exit();
}

$resource_id = intval($_GET['id'] ?? 0);

if ($resource_id <= 0) {
    header("Location: ../resources_list.php?error=Invalid resource ID");
    exit();
}

// Get resource details to delete the file
$query = "SELECT pdf_file FROM resources WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $resource_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: ../resources_list.php?error=Resource not found");
    exit();
}

$resource = $result->fetch_assoc();
$pdfFile = '../' . $resource['pdf_file'];

// Delete from database
$deleteQuery = "DELETE FROM resources WHERE id = ?";
$deleteStmt = $conn->prepare($deleteQuery);
$deleteStmt->bind_param("i", $resource_id);

if ($deleteStmt->execute()) {
    // Delete the file if it exists
    if (file_exists($pdfFile)) {
        unlink($pdfFile);
    }
    header("Location: ../resources_list.php?success=Resource deleted successfully");
} else {
    header("Location: ../resources_list.php?error=Failed to delete resource");
}

$deleteStmt->close();
$stmt->close();
$conn->close();
?>

