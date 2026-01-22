<?php
session_start();
include 'conn.php';

// Set content type to JSON
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] != "POST") {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$action = $_POST['action'] ?? '';
$resource_ids = $_POST['resource_ids'] ?? [];

if (empty($action)) {
    echo json_encode(['success' => false, 'message' => 'No action specified']);
    exit();
}

if (empty($resource_ids) || !is_array($resource_ids)) {
    echo json_encode(['success' => false, 'message' => 'No resources selected']);
    exit();
}

// Sanitize resource IDs
$resource_ids = array_map('intval', $resource_ids);
$resource_ids = array_filter($resource_ids, function($id) {
    return $id > 0;
});

if (empty($resource_ids)) {
    echo json_encode(['success' => false, 'message' => 'Invalid resource IDs']);
    exit();
}

// Create placeholders for IN clause
$placeholders = str_repeat('?,', count($resource_ids) - 1) . '?';
$types = str_repeat('i', count($resource_ids));

try {
    $conn->begin_transaction();

    switch ($action) {
        case 'activate':
            $query = "UPDATE resources SET status = 'active' WHERE id IN ($placeholders)";
            $stmt = $conn->prepare($query);
            $stmt->bind_param($types, ...$resource_ids);
            $stmt->execute();
            $affected = $stmt->affected_rows;
            $stmt->close();
            $conn->commit();
            echo json_encode([
                'success' => true,
                'message' => "$affected resource(s) activated successfully"
            ]);
            break;

        case 'deactivate':
            $query = "UPDATE resources SET status = 'inactive' WHERE id IN ($placeholders)";
            $stmt = $conn->prepare($query);
            $stmt->bind_param($types, ...$resource_ids);
            $stmt->execute();
            $affected = $stmt->affected_rows;
            $stmt->close();
            $conn->commit();
            echo json_encode([
                'success' => true,
                'message' => "$affected resource(s) deactivated successfully"
            ]);
            break;

        case 'archive':
            $query = "UPDATE resources SET status = 'archived' WHERE id IN ($placeholders)";
            $stmt = $conn->prepare($query);
            $stmt->bind_param($types, ...$resource_ids);
            $stmt->execute();
            $affected = $stmt->affected_rows;
            $stmt->close();
            $conn->commit();
            echo json_encode([
                'success' => true,
                'message' => "$affected resource(s) archived successfully"
            ]);
            break;

        case 'delete':
            // First, get file paths for deletion
            $getFilesQuery = "SELECT pdf_file FROM resources WHERE id IN ($placeholders)";
            $getFilesStmt = $conn->prepare($getFilesQuery);
            $getFilesStmt->bind_param($types, ...$resource_ids);
            $getFilesStmt->execute();
            $result = $getFilesStmt->get_result();
            
            $filesToDelete = [];
            while ($row = $result->fetch_assoc()) {
                if (!empty($row['pdf_file'])) {
                    $filesToDelete[] = '../' . $row['pdf_file'];
                }
            }
            $getFilesStmt->close();

            // Delete from database
            $query = "DELETE FROM resources WHERE id IN ($placeholders)";
            $stmt = $conn->prepare($query);
            $stmt->bind_param($types, ...$resource_ids);
            $stmt->execute();
            $affected = $stmt->affected_rows;
            $stmt->close();
            
            // Delete files
            foreach ($filesToDelete as $filePath) {
                if (file_exists($filePath)) {
                    @unlink($filePath);
                }
            }
            
            $conn->commit();
            echo json_encode([
                'success' => true,
                'message' => "$affected resource(s) deleted successfully"
            ]);
            break;

        default:
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => 'Invalid action specified']);
            break;
    }

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}

$conn->close();
?>

