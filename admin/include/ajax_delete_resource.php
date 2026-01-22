<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

include 'conn.php';

$resource_id = intval($_GET['id'] ?? $_POST['id'] ?? 0);

if ($resource_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid resource ID']);
    exit();
}

// Get resource details to delete the file
$query = "SELECT pdf_file FROM resources WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $resource_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Resource not found']);
    $stmt->close();
    $conn->close();
    exit();
}

$resource = $result->fetch_assoc();
$pdfFile = '../' . $resource['pdf_file'];
$stmt->close();

// Delete from database
$deleteQuery = "DELETE FROM resources WHERE id = ?";
$deleteStmt = $conn->prepare($deleteQuery);
$deleteStmt->bind_param("i", $resource_id);

if ($deleteStmt->execute()) {
    // Delete the file if it exists
    if (file_exists($pdfFile)) {
        @unlink($pdfFile);
    }
    $deleteStmt->close();
    $conn->close();
    echo json_encode(['success' => true, 'message' => 'Resource deleted successfully']);
} else {
    $error = $deleteStmt->error;
    $deleteStmt->close();
    $conn->close();
    echo json_encode(['success' => false, 'message' => 'Failed to delete resource: ' . $error]);
}
?>

