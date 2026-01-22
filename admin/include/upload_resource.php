<?php
// Start output buffering to prevent header issues
ob_start();

// Enable error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 1); // Don't display errors to users, but log them
ini_set('log_errors', 1);

session_start();

// Check if session is started properly
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
if (!file_exists(__DIR__ . '/conn.php')) {
    error_log("Error: conn.php not found in " . __DIR__);
    die("Database configuration file not found. Please contact administrator.");
}

include 'conn.php';

// Check database connection
if (!$conn || $conn->connect_error) {
    error_log("Database connection error: " . ($conn ? $conn->connect_error : "Connection object is null"));
    header("Location: ../add_resource.php?error=Database connection failed. Please try again later.");
    exit();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth-login.php");
    exit();
}

// Helper function to convert PHP size strings to bytes
function return_bytes($val) {
    $val = trim($val);
    $last = strtolower($val[strlen($val)-1]);
    $val = (int)$val;
    switch($last) {
        case 'g': $val *= 1024;
        case 'm': $val *= 1024;
        case 'k': $val *= 1024;
    }
    return $val;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check for PHP upload errors first
    if (isset($_FILES['pdf_file']['error']) && $_FILES['pdf_file']['error'] !== UPLOAD_ERR_OK) {
        $upload_errors = [
            UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize directive in php.ini',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE directive in HTML form',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'File upload stopped by extension'
        ];
        $error_msg = $upload_errors[$_FILES['pdf_file']['error']] ?? 'Unknown upload error';
        error_log("File upload error: " . $error_msg . " (Error code: " . $_FILES['pdf_file']['error'] . ")");
        header("Location: ../add_resource.php?error=" . urlencode($error_msg));
        exit();
    }
    
    $section = trim($_POST['section'] ?? '');
    $title = trim($_POST['title'] ?? '');
    $publication_date = $_POST['publication_date'] ?? '';
    $author = trim($_POST['author'] ?? '');
    $description = trim($_POST['description'] ?? '');
    
    // Validate status - must be one of the allowed ENUM values
    $status = $_POST['status'] ?? 'active';
    $allowed_statuses = ['active', 'inactive', 'archived'];
    if (!in_array($status, $allowed_statuses)) {
        $status = 'active'; // Default to active if invalid
    }
    
    // Validate access_level - must be one of the allowed ENUM values
    $access_level = $_POST['access_level'] ?? 'member';
    $allowed_access_levels = ['public', 'member', 'premium', 'restricted'];
    if (!in_array($access_level, $allowed_access_levels)) {
        $access_level = 'member'; // Default to member if invalid
    }
    
    $tags = trim($_POST['tags'] ?? '');
    $featured = isset($_POST['featured']) && $_POST['featured'] == '1' ? 1 : 0;

    if (empty($section) || empty($title) || empty($publication_date) || empty($author)) {
        header("Location: ../add_resource.php?error=Please fill in all required fields");
        exit();
    }

    // Handle PDF upload
    if (empty($_FILES['pdf_file']['name'])) {
        header("Location: ../add_resource.php?error=Please upload a PDF file");
        exit();
    }
    
    // Check if file was actually uploaded
    if (!isset($_FILES['pdf_file']) || !is_uploaded_file($_FILES['pdf_file']['tmp_name'])) {
        error_log("File upload validation failed: File not uploaded properly");
        header("Location: ../add_resource.php?error=File upload failed. Please try again.");
        exit();
    }

    $pdfFile = $_FILES['pdf_file'];
    $allowedMimeTypes = ['application/pdf'];
    $allowedExtensions = ['pdf'];
    
    // Get PHP upload limits
    $phpUploadMax = ini_get('upload_max_filesize');
    $phpPostMax = ini_get('post_max_size');
    // Convert to bytes
    $phpUploadMaxBytes = return_bytes($phpUploadMax);
    $phpPostMaxBytes = return_bytes($phpPostMax);
    // Use the smaller of the two limits, or 10MB as default
    $maxSize = min($phpUploadMaxBytes, $phpPostMaxBytes, 10 * 1024 * 1024);

    // Get file extension
    $pdfExtension = strtolower(pathinfo($pdfFile['name'], PATHINFO_EXTENSION));
    
    // Validate file extension
    if (!in_array($pdfExtension, $allowedExtensions)) {
        header("Location: ../add_resource.php?error=Invalid file type. Only PDF files are allowed.");
        exit();
    }
    
    // Validate MIME type
    if (!in_array($pdfFile['type'], $allowedMimeTypes)) {
        header("Location: ../add_resource.php?error=Invalid PDF file type.");
        exit();
    }
    
    // Validate file size
    if ($pdfFile['size'] > $maxSize) {
        $maxSizeMB = round($maxSize / (1024 * 1024), 2);
        $errorMsg = "File size exceeds the maximum allowed size of {$maxSizeMB}MB. ";
        $errorMsg .= "Your PHP configuration allows: upload_max_filesize={$phpUploadMax}, post_max_size={$phpPostMax}. ";
        $errorMsg .= "Please increase these values in php.ini or upload a smaller file.";
        header("Location: ../add_resource.php?error=" . urlencode($errorMsg));
        exit();
    }
    
    // Additional validation: Verify actual file content using finfo if available
    if (function_exists('finfo_open') && file_exists($pdfFile['tmp_name'])) {
        try {
            $finfo = @finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo !== false) {
                $actualMimeType = @finfo_file($finfo, $pdfFile['tmp_name']);
                finfo_close($finfo);
                
                if ($actualMimeType !== false && $actualMimeType !== 'application/pdf') {
                    header("Location: ../add_resource.php?error=File content does not match PDF format.");
                    exit();
                }
            }
        } catch (Exception $e) {
            error_log("finfo validation error: " . $e->getMessage());
            // Continue with upload even if finfo fails
        }
    }

    // Create uploads/resources directory if it doesn't exist
    $uploadDir = '../../uploads/resources/';
    if (!file_exists($uploadDir)) {
        if (!mkdir($uploadDir, 0777, true)) {
            error_log("Failed to create upload directory: " . $uploadDir);
            header("Location: ../add_resource.php?error=Failed to create upload directory. Please check server permissions.");
            exit();
        }
    }
    
    // Check if directory is writable
    if (!is_writable($uploadDir)) {
        error_log("Upload directory is not writable: " . $uploadDir);
        header("Location: ../add_resource.php?error=Upload directory is not writable. Please contact administrator.");
        exit();
    }

    // Generate unique filename (sanitize original filename)
    $sanitizedFilename = preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($pdfFile['name']));
    $pdfName = time() . '_' . uniqid() . '_' . $sanitizedFilename;
    $pdfPath = $uploadDir . $pdfName;

    if (move_uploaded_file($pdfFile['tmp_name'], $pdfPath)) {
        $pdfFilePath = 'uploads/resources/' . $pdfName;

        // Insert into database - check if new columns exist
        try {
            $checkStatus = $conn->query("SHOW COLUMNS FROM resources LIKE 'status'");
            if ($checkStatus === false) {
                error_log("Error checking database columns: " . $conn->error);
                throw new Exception("Database query failed: " . $conn->error);
            }
            
            $hasNewFields = $checkStatus && $checkStatus->num_rows > 0;
            
            if ($hasNewFields) {
                $query = "INSERT INTO resources (section, title, publication_date, author, pdf_file, description, status, access_level, tags, featured) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($query);
                if (!$stmt) {
                    error_log("Prepare failed: " . $conn->error);
                    throw new Exception("Database prepare failed: " . $conn->error);
                }
                $stmt->bind_param("sssssssssi", $section, $title, $publication_date, $author, $pdfFilePath, $description, $status, $access_level, $tags, $featured);
            } else {
                // Fallback for old table structure
                $query = "INSERT INTO resources (section, title, publication_date, author, pdf_file, description) 
                          VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($query);
                if (!$stmt) {
                    error_log("Prepare failed: " . $conn->error);
                    throw new Exception("Database prepare failed: " . $conn->error);
                }
                $stmt->bind_param("ssssss", $section, $title, $publication_date, $author, $pdfFilePath, $description);
            }
        } catch (Exception $e) {
            error_log("Database error in upload_resource.php: " . $e->getMessage());
            // Delete uploaded file if database insert fails
            if (file_exists($pdfPath)) {
                unlink($pdfPath);
            }
            header("Location: ../add_resource.php?error=Database error occurred. Please try again.");
            exit();
        }

        if ($stmt->execute()) {
            $resource_id = $stmt->insert_id;
            $stmt->close();
            
            // Trigger email automation (with error handling) - temporarily disabled for debugging
            /*
            try {
                if (file_exists(__DIR__ . '/email_automation.php')) {
                    require_once __DIR__ . '/email_automation.php';
                    
                    // Generate content link
                    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
                    $host = $_SERVER['HTTP_HOST'];
                    $content_link = $protocol . "://" . $host . "/resources.php";
                    
                    // Send automated email
                    if (function_exists('sendAutomatedEmail')) {
                        sendAutomatedEmail('resource', $resource_id, [
                            'title' => $title,
                            'content' => $description,
                            'author' => $author,
                            'date' => $publication_date,
                            'link' => $content_link,
                            'file_url' => $pdfFilePath
                        ]);
                    }
                }
            } catch (Exception $e) {
                // Log error but don't stop the resource creation
                error_log("Email automation error: " . $e->getMessage());
            } catch (Error $e) {
                // Catch fatal errors too
                error_log("Email automation fatal error: " . $e->getMessage());
            }
            */
            
            header("Location: ../resources_list.php?success=Resource uploaded successfully");
            exit();
        } else {
            // Delete uploaded file if database insert fails
            if (file_exists($pdfPath)) {
                unlink($pdfPath);
            }
            $error_msg = $stmt->error ? $stmt->error : ($conn->error ? $conn->error : "Unknown database error");
            error_log("Database execute error: " . $error_msg);
            header("Location: ../add_resource.php?error=Failed to save resource: " . urlencode($error_msg));
            exit();
        }
    } else {
        error_log("Failed to move uploaded file. Source: " . $pdfFile['tmp_name'] . ", Destination: " . $pdfPath);
        error_log("Upload error code: " . ($pdfFile['error'] ?? 'unknown'));
        header("Location: ../add_resource.php?error=Failed to upload PDF file. Please check file permissions or try again.");
        exit();
    }
} else {
    header("Location: ../add_resource.php");
    exit();
}

// Close database connection
if (isset($conn) && $conn) {
    $conn->close();
}

// Clean output buffer
ob_end_flush();
?>

