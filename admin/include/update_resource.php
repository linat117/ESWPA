<?php
session_start();
include 'conn.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth-login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $resource_id = intval($_POST['resource_id'] ?? 0);
    $section = trim($_POST['section'] ?? '');
    $title = trim($_POST['title'] ?? '');
    $publication_date = $_POST['publication_date'] ?? '';
    $author = trim($_POST['author'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $status = $_POST['status'] ?? 'active';
    $access_level = $_POST['access_level'] ?? 'member';
    $tags = trim($_POST['tags'] ?? '');
    $featured = isset($_POST['featured']) && $_POST['featured'] == '1' ? 1 : 0;

    if ($resource_id <= 0) {
        header("Location: ../resources_list.php?error=Invalid resource ID");
        exit();
    }

    if (empty($section) || empty($title) || empty($publication_date) || empty($author)) {
        header("Location: ../edit_resource.php?id=" . $resource_id . "&error=Please fill in all required fields");
        exit();
    }

    // Get current resource data
    $getQuery = "SELECT pdf_file FROM resources WHERE id = ?";
    $getStmt = $conn->prepare($getQuery);
    $getStmt->bind_param("i", $resource_id);
    $getStmt->execute();
    $result = $getStmt->get_result();
    
    if ($result->num_rows === 0) {
        header("Location: ../resources_list.php?error=Resource not found");
        exit();
    }
    
    $currentResource = $result->fetch_assoc();
    $getStmt->close();
    
    $pdfFilePath = $currentResource['pdf_file'];
    $oldPdfPath = '../' . $currentResource['pdf_file'];
    
    // Handle PDF file replacement (optional)
    if (!empty($_FILES['pdf_file']['name'])) {
        $pdfFile = $_FILES['pdf_file'];
        $allowedMimeTypes = ['application/pdf'];
        $allowedExtensions = ['pdf'];
        $maxSize = 10 * 1024 * 1024; // 10MB

        // Get file extension
        $pdfExtension = strtolower(pathinfo($pdfFile['name'], PATHINFO_EXTENSION));
        
        // Validate file extension
        if (!in_array($pdfExtension, $allowedExtensions)) {
            header("Location: ../edit_resource.php?id=" . $resource_id . "&error=Invalid file type. Only PDF files are allowed.");
            exit();
        }
        
        // Validate MIME type
        if (!in_array($pdfFile['type'], $allowedMimeTypes)) {
            header("Location: ../edit_resource.php?id=" . $resource_id . "&error=Invalid PDF file type.");
            exit();
        }
        
        // Validate file size
        if ($pdfFile['size'] > $maxSize) {
            header("Location: ../edit_resource.php?id=" . $resource_id . "&error=File size exceeds 10MB limit.");
            exit();
        }
        
        // Additional validation: Verify actual file content using finfo if available
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $actualMimeType = finfo_file($finfo, $pdfFile['tmp_name']);
            finfo_close($finfo);
            
            if ($actualMimeType !== 'application/pdf') {
                header("Location: ../edit_resource.php?id=" . $resource_id . "&error=File content does not match PDF format.");
                exit();
            }
        }

        // Create uploads/resources directory if it doesn't exist
        $uploadDir = '../../uploads/resources/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        // Generate unique filename (sanitize original filename)
        $sanitizedFilename = preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($pdfFile['name']));
        $pdfName = time() . '_' . uniqid() . '_' . $sanitizedFilename;
        $pdfPath = $uploadDir . $pdfName;

        if (move_uploaded_file($pdfFile['tmp_name'], $pdfPath)) {
            // Delete old file if it exists
            if (!empty($currentResource['pdf_file']) && file_exists($oldPdfPath)) {
                unlink($oldPdfPath);
            }
            
            $pdfFilePath = 'uploads/resources/' . $pdfName;
        } else {
            header("Location: ../edit_resource.php?id=" . $resource_id . "&error=Failed to upload PDF file");
            exit();
        }
    }

    // Update database - check if new columns exist
    $checkStatus = $conn->query("SHOW COLUMNS FROM resources LIKE 'status'");
    $hasNewFields = $checkStatus && $checkStatus->num_rows > 0;
    
    if ($hasNewFields) {
        $query = "UPDATE resources SET section = ?, title = ?, publication_date = ?, author = ?, description = ?, pdf_file = ?, status = ?, access_level = ?, tags = ?, featured = ? WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sssssssssii", $section, $title, $publication_date, $author, $description, $pdfFilePath, $status, $access_level, $tags, $featured, $resource_id);
    } else {
        // Fallback for old table structure
        $query = "UPDATE resources SET section = ?, title = ?, publication_date = ?, author = ?, description = ?, pdf_file = ? WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssssssi", $section, $title, $publication_date, $author, $description, $pdfFilePath, $resource_id);
    }

    if ($stmt->execute()) {
        $stmt->close();
        header("Location: ../resources_list.php?success=Resource updated successfully");
        exit();
    } else {
        $stmt->close();
        header("Location: ../edit_resource.php?id=" . $resource_id . "&error=Failed to update resource: " . $conn->error);
        exit();
    }

    $conn->close();
} else {
    header("Location: ../resources_list.php");
    exit();
}
?>

