<?php
session_start();
include 'conn.php';

if (!isset($_SESSION['user_id'])) {
    // Determine redirect path based on calling context
    $redirectPath = (strpos($_SERVER['PHP_SELF'], '/admin/') !== false || strpos($_SERVER['REQUEST_URI'], '/admin/') !== false) 
        ? 'auth-login.php' 
        : '../auth-login.php';
    header("Location: " . $redirectPath);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES['media_files'])) {
    $category = $_POST['category'] ?? 'general';
    
    // Define upload directories based on category
    $uploadDirs = [
        'news' => '../../uploads/news/',
        'members' => '../../uploads/members/',
        'resources' => '../../uploads/resources/',
        'company' => '../../uploads/company/',
        'general' => '../../uploads/'
    ];
    
    $uploadDir = $uploadDirs[$category] ?? $uploadDirs['general'];
    
    // Create directory if it doesn't exist
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Allowed file types
    $allowedTypes = [
        'image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp',
        'application/pdf',
        'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
    ];
    
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'doc', 'docx', 'xls', 'xlsx'];
    $maxSize = 5 * 1024 * 1024; // 5MB
    
    $uploadedFiles = [];
    $errors = [];
    
    // Handle multiple file uploads
    $files = $_FILES['media_files'];
    $fileCount = count($files['name']);
    
    for ($i = 0; $i < $fileCount; $i++) {
        if ($files['error'][$i] === UPLOAD_ERR_OK) {
            $fileName = $files['name'][$i];
            $fileTmp = $files['tmp_name'][$i];
            $fileSize = $files['size'][$i];
            $fileType = $files['type'][$i];
            
            // Get file extension
            $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            
            // Validate file type
            if (!in_array($extension, $allowedExtensions)) {
                $errors[] = "File '$fileName' has an invalid extension. Allowed: " . implode(', ', $allowedExtensions);
                continue;
            }
            
            // Validate file size
            if ($fileSize > $maxSize) {
                $errors[] = "File '$fileName' is too large. Maximum size: 5MB";
                continue;
            }
            
            // Generate unique filename
            $newFileName = time() . '_' . uniqid() . '_' . basename($fileName);
            $targetPath = $uploadDir . $newFileName;
            
            // Move uploaded file
            if (move_uploaded_file($fileTmp, $targetPath)) {
                $uploadedFiles[] = $fileName;
            } else {
                $errors[] = "Failed to upload file '$fileName'";
            }
        } elseif ($files['error'][$i] !== UPLOAD_ERR_NO_FILE) {
            $errors[] = "Error uploading file: " . $files['name'][$i] . " (Error code: " . $files['error'][$i] . ")";
        }
    }
    
    // Redirect with results
    if (!empty($uploadedFiles)) {
        $successMsg = count($uploadedFiles) . " file(s) uploaded successfully: " . implode(', ', $uploadedFiles);
        if (!empty($errors)) {
            $successMsg .= ". Errors: " . implode(', ', $errors);
        }
        // Redirect to media library (path is relative to calling file location)
        header("Location: media_library.php?success=" . urlencode($successMsg) . "&category=" . urlencode($category));
    } else {
        $errorMsg = !empty($errors) ? implode(', ', $errors) : "No files were uploaded.";
        header("Location: media_library.php?error=" . urlencode($errorMsg) . "&category=" . urlencode($category));
    }
    exit();
} else {
    header("Location: media_library.php?error=Invalid request");
    exit();
}
?>

