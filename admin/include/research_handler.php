<?php
session_start();
include 'conn.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth-login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create') {
        // Create new research project
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $abstract = trim($_POST['abstract'] ?? '');
        $status = $_POST['status'] ?? 'draft';
        $category = trim($_POST['category'] ?? '');
        $research_type = $_POST['research_type'] ?? null;
        $start_date = $_POST['start_date'] ?? null;
        $end_date = $_POST['end_date'] ?? null;
        $publication_date = $_POST['publication_date'] ?? null;
        $doi = trim($_POST['doi'] ?? '');
        $keywords = trim($_POST['keywords'] ?? '');
        $created_by = intval($_POST['created_by'] ?? 0);
        
        if (empty($title) || empty($description) || $created_by <= 0) {
            header("Location: ../add_research.php?error=Please fill in all required fields");
            exit();
        }
        
        // Insert research project
        $query = "INSERT INTO research_projects 
                  (title, description, abstract, status, category, research_type, start_date, end_date, publication_date, doi, keywords, created_by) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sssssssssssi", 
            $title, $description, $abstract, $status, $category, $research_type,
            $start_date, $end_date, $publication_date, $doi, $keywords, $created_by
        );
        
        if ($stmt->execute()) {
            $research_id = $stmt->insert_id;
            $stmt->close();
            
            // Handle file uploads
            if (!empty($_FILES['research_files']['name'][0])) {
                $uploadDir = '../../uploads/research/';
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                
                $fileCount = count($_FILES['research_files']['name']);
                for ($i = 0; $i < $fileCount; $i++) {
                    if ($_FILES['research_files']['error'][$i] === UPLOAD_ERR_OK) {
                        $fileName = $_FILES['research_files']['name'][$i];
                        $fileTmp = $_FILES['research_files']['tmp_name'][$i];
                        $fileSize = $_FILES['research_files']['size'][$i];
                        $fileType = $_FILES['research_files']['type'][$i];
                        
                        // Validate file
                        $allowedMimeTypes = ['application/pdf', 'application/msword', 
                                           'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                                           'text/plain'];
                        $allowedExtensions = ['pdf', 'doc', 'docx', 'txt'];
                        $maxSize = 10 * 1024 * 1024; // 10MB
                        
                        // Get file extension
                        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                        
                        // Validate extension
                        if (!in_array($fileExtension, $allowedExtensions)) {
                            continue; // Skip invalid files
                        }
                        
                        // Validate MIME type
                        if (!in_array($fileType, $allowedMimeTypes)) {
                            continue; // Skip invalid files
                        }
                        
                        // Validate file size
                        if ($fileSize > $maxSize) {
                            continue; // Skip oversized files
                        }
                        
                        // Additional validation: Verify actual file content using finfo if available
                        if (function_exists('finfo_open')) {
                            $finfo = finfo_open(FILEINFO_MIME_TYPE);
                            $actualMimeType = finfo_file($finfo, $fileTmp);
                            finfo_close($finfo);
                            
                            if (!in_array($actualMimeType, $allowedMimeTypes)) {
                                continue; // Skip files with mismatched content
                            }
                        }
                        
                        // Sanitize filename and generate unique name
                        $sanitizedFilename = preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($fileName));
                        $uniqueFileName = time() . '_' . uniqid() . '_' . $sanitizedFilename;
                        $filePath = $uploadDir . $uniqueFileName;
                        
                        if (move_uploaded_file($fileTmp, $filePath)) {
                            $dbFilePath = 'uploads/research/' . $uniqueFileName;
                            
                            // Insert file record
                            $fileQuery = "INSERT INTO research_files 
                                         (research_id, file_name, file_path, file_type, file_size, uploaded_by) 
                                         VALUES (?, ?, ?, ?, ?, ?)";
                            $fileStmt = $conn->prepare($fileQuery);
                            $fileStmt->bind_param("isssii", $research_id, $fileName, $dbFilePath, $fileType, $fileSize, $created_by);
                            $fileStmt->execute();
                            $fileStmt->close();
                        }
                    }
                }
            }
            
            // Create initial version record
            $versionQuery = "INSERT INTO research_versions 
                            (research_id, version_number, title, description, changed_by) 
                            VALUES (?, '1.0', ?, ?, ?)";
            $versionStmt = $conn->prepare($versionQuery);
            $versionStmt->bind_param("issi", $research_id, $title, $description, $created_by);
            $versionStmt->execute();
            $versionStmt->close();

            // Insert collaborators (from Add Research form)
            $collaborators = isset($_POST['collaborators']) && is_array($_POST['collaborators']) ? $_POST['collaborators'] : [];
            $validRoles = ['lead', 'co_author', 'contributor', 'advisor', 'reviewer'];
            $collabStmt = $conn->prepare("INSERT INTO research_collaborators (research_id, member_id, role, contribution_percentage) VALUES (?, ?, ?, ?)");
            foreach ($collaborators as $c) {
                $member_id = isset($c['member_id']) ? (int) $c['member_id'] : 0;
                if ($member_id <= 0 || $member_id === $created_by) continue;
                $role = isset($c['role']) && in_array($c['role'], $validRoles) ? $c['role'] : 'contributor';
                $pct = isset($c['contribution_percentage']) && $c['contribution_percentage'] !== '' ? (float) $c['contribution_percentage'] : null;
                $collabStmt->bind_param("iisd", $research_id, $member_id, $role, $pct);
                $collabStmt->execute();
            }
            $collabStmt->close();
            
            // Email Automation Integration - Send email if published
            if ($status === 'published') {
                require_once __DIR__ . '/email_automation.php';
                
                // Get creator name
                $creatorQuery = "SELECT fullname FROM registrations WHERE id = ?";
                $creatorStmt = $conn->prepare($creatorQuery);
                $creatorStmt->bind_param("i", $created_by);
                $creatorStmt->execute();
                $creatorResult = $creatorStmt->get_result();
                $creatorData = $creatorResult->fetch_assoc();
                $author = $creatorData['fullname'] ?? 'Unknown';
                $creatorStmt->close();
                
                // Generate content link
                $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
                $host = $_SERVER['HTTP_HOST'];
                $content_link = $protocol . "://" . $host . "/member-research-detail.php?id=" . $research_id;
                
                // Prepare content data
                $content_data = [
                    'title' => $title,
                    'content' => $description,
                    'excerpt' => !empty($abstract) ? substr($abstract, 0, 300) : substr($description, 0, 300),
                    'author' => $author,
                    'date' => date('F d, Y'),
                    'link' => $content_link,
                    'type' => 'Research Project',
                    'status' => $status
                ];
                
                // Send automated email (using 'resource' content type as per documentation)
                sendAutomatedEmail('resource', $research_id, $content_data);
            }
            
            header("Location: ../research_list.php?success=Research project created successfully");
            exit();
        } else {
            $stmt->close();
            header("Location: ../add_research.php?error=Failed to create research project: " . $conn->error);
            exit();
        }
        
    } elseif ($action === 'update') {
        // Update existing research project
        $research_id = intval($_POST['research_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $abstract = trim($_POST['abstract'] ?? '');
        $status = $_POST['status'] ?? 'draft';
        $category = trim($_POST['category'] ?? '');
        $research_type = $_POST['research_type'] ?? null;
        $start_date = $_POST['start_date'] ?? null;
        $end_date = $_POST['end_date'] ?? null;
        $publication_date = $_POST['publication_date'] ?? null;
        $doi = trim($_POST['doi'] ?? '');
        $keywords = trim($_POST['keywords'] ?? '');
        
        if ($research_id <= 0 || empty($title) || empty($description)) {
            header("Location: ../edit_research.php?id=" . $research_id . "&error=Please fill in all required fields");
            exit();
        }
        
        // Update research project
        $query = "UPDATE research_projects SET 
                  title = ?, description = ?, abstract = ?, status = ?, category = ?, 
                  research_type = ?, start_date = ?, end_date = ?, publication_date = ?, 
                  doi = ?, keywords = ? 
                  WHERE id = ?";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sssssssssssi", 
            $title, $description, $abstract, $status, $category, $research_type,
            $start_date, $end_date, $publication_date, $doi, $keywords, $research_id
        );
        
        if ($stmt->execute()) {
            $stmt->close();
            
            // Handle new file uploads
            if (!empty($_FILES['research_files']['name'][0])) {
                $uploadDir = '../../uploads/research/';
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                
                // Get current highest version
                $versionQuery = "SELECT MAX(CAST(version_number AS DECIMAL(10,2))) as max_version FROM research_versions WHERE research_id = ?";
                $versionStmt = $conn->prepare($versionQuery);
                $versionStmt->bind_param("i", $research_id);
                $versionStmt->execute();
                $versionResult = $versionStmt->get_result();
                $versionRow = $versionResult->fetch_assoc();
                $nextVersion = $versionRow['max_version'] ? number_format($versionRow['max_version'] + 0.1, 1) : '1.1';
                $versionStmt->close();
                
                $fileCount = count($_FILES['research_files']['name']);
                for ($i = 0; $i < $fileCount; $i++) {
                    if ($_FILES['research_files']['error'][$i] === UPLOAD_ERR_OK) {
                        $fileName = $_FILES['research_files']['name'][$i];
                        $fileTmp = $_FILES['research_files']['tmp_name'][$i];
                        $fileSize = $_FILES['research_files']['size'][$i];
                        $fileType = $_FILES['research_files']['type'][$i];
                        
                        $allowedMimeTypes = ['application/pdf', 'application/msword', 
                                           'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                                           'text/plain'];
                        $allowedExtensions = ['pdf', 'doc', 'docx', 'txt'];
                        $maxSize = 10 * 1024 * 1024;
                        
                        // Get file extension
                        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                        
                        // Validate extension
                        if (!in_array($fileExtension, $allowedExtensions)) {
                            continue; // Skip invalid files
                        }
                        
                        // Validate MIME type
                        if (!in_array($fileType, $allowedMimeTypes)) {
                            continue; // Skip invalid files
                        }
                        
                        // Validate file size
                        if ($fileSize > $maxSize) {
                            continue; // Skip oversized files
                        }
                        
                        // Additional validation: Verify actual file content using finfo if available
                        if (function_exists('finfo_open')) {
                            $finfo = finfo_open(FILEINFO_MIME_TYPE);
                            $actualMimeType = finfo_file($finfo, $fileTmp);
                            finfo_close($finfo);
                            
                            if (!in_array($actualMimeType, $allowedMimeTypes)) {
                                continue; // Skip files with mismatched content
                            }
                        }
                        
                        // Sanitize filename and generate unique name
                        $sanitizedFilename = preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($fileName));
                        $uniqueFileName = time() . '_' . uniqid() . '_' . $sanitizedFilename;
                        $filePath = $uploadDir . $uniqueFileName;
                        
                        if (move_uploaded_file($fileTmp, $filePath)) {
                            $dbFilePath = 'uploads/research/' . $uniqueFileName;
                            
                            // Get created_by from research project
                            $creatorQuery = "SELECT created_by FROM research_projects WHERE id = ?";
                            $creatorStmt = $conn->prepare($creatorQuery);
                            $creatorStmt->bind_param("i", $research_id);
                            $creatorStmt->execute();
                            $creatorResult = $creatorStmt->get_result();
                            $creatorRow = $creatorResult->fetch_assoc();
                            $uploaded_by = $creatorRow['created_by'];
                            $creatorStmt->close();
                            
                            $fileQuery = "INSERT INTO research_files 
                                         (research_id, file_name, file_path, file_type, file_size, version, uploaded_by) 
                                         VALUES (?, ?, ?, ?, ?, ?, ?)";
                            $fileStmt = $conn->prepare($fileQuery);
                            $fileStmt->bind_param("isssisi", $research_id, $fileName, $dbFilePath, $fileType, $fileSize, $nextVersion, $uploaded_by);
                            $fileStmt->execute();
                            $fileStmt->close();
                        }
                    }
                }
                
                // Create new version record for the update
                $getCreatorQuery = "SELECT created_by FROM research_projects WHERE id = ?";
                $getCreatorStmt = $conn->prepare($getCreatorQuery);
                $getCreatorStmt->bind_param("i", $research_id);
                $getCreatorStmt->execute();
                $getCreatorResult = $getCreatorStmt->get_result();
                $creatorData = $getCreatorResult->fetch_assoc();
                $changedBy = $creatorData['created_by'] ?? $_SESSION['user_id'] ?? 1;
                $getCreatorStmt->close();
                
                $versionQuery = "INSERT INTO research_versions 
                                (research_id, version_number, title, description, changes_summary, changed_by) 
                                VALUES (?, ?, ?, ?, ?, ?)";
                $versionStmt = $conn->prepare($versionQuery);
                $changesSummary = "Updated research project details";
                $versionStmt->bind_param("issssi", $research_id, $nextVersion, $title, $description, $changesSummary, $changedBy);
                $versionStmt->execute();
                $versionStmt->close();
            } else {
                // Still create version record even without new files
                $getCreatorQuery = "SELECT created_by FROM research_projects WHERE id = ?";
                $getCreatorStmt = $conn->prepare($getCreatorQuery);
                $getCreatorStmt->bind_param("i", $research_id);
                $getCreatorStmt->execute();
                $getCreatorResult = $getCreatorStmt->get_result();
                $creatorData = $getCreatorResult->fetch_assoc();
                $changedBy = $creatorData['created_by'] ?? $_SESSION['user_id'] ?? 1;
                $getCreatorStmt->close();
                
                // Get current highest version
                $versionQuery = "SELECT MAX(CAST(version_number AS DECIMAL(10,2))) as max_version FROM research_versions WHERE research_id = ?";
                $versionStmt = $conn->prepare($versionQuery);
                $versionStmt->bind_param("i", $research_id);
                $versionStmt->execute();
                $versionResult = $versionStmt->get_result();
                $versionRow = $versionResult->fetch_assoc();
                $nextVersion = $versionRow['max_version'] ? number_format($versionRow['max_version'] + 0.1, 1) : '1.1';
                $versionStmt->close();
                
                $versionQuery = "INSERT INTO research_versions 
                                (research_id, version_number, title, description, changes_summary, changed_by) 
                                VALUES (?, ?, ?, ?, ?, ?)";
                $versionStmt = $conn->prepare($versionQuery);
                $changesSummary = "Updated research project details";
                $versionStmt->bind_param("issssi", $research_id, $nextVersion, $title, $description, $changesSummary, $changedBy);
                $versionStmt->execute();
                $versionStmt->close();
            }
            
            // Email Automation Integration - Send email if status changed to published
            if ($status === 'published') {
                require_once __DIR__ . '/email_automation.php';
                
                // Get creator name
                $getCreatorQuery = "SELECT created_by FROM research_projects WHERE id = ?";
                $getCreatorStmt = $conn->prepare($getCreatorQuery);
                $getCreatorStmt->bind_param("i", $research_id);
                $getCreatorStmt->execute();
                $getCreatorResult = $getCreatorStmt->get_result();
                $creatorData = $getCreatorResult->fetch_assoc();
                $creatorId = $creatorData['created_by'] ?? 1;
                $getCreatorStmt->close();
                
                $creatorQuery = "SELECT fullname FROM registrations WHERE id = ?";
                $creatorStmt = $conn->prepare($creatorQuery);
                $creatorStmt->bind_param("i", $creatorId);
                $creatorStmt->execute();
                $creatorResult = $creatorStmt->get_result();
                $creatorRow = $creatorResult->fetch_assoc();
                $author = $creatorRow['fullname'] ?? 'Unknown';
                $creatorStmt->close();
                
                // Generate content link
                $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
                $host = $_SERVER['HTTP_HOST'];
                $content_link = $protocol . "://" . $host . "/member-research-detail.php?id=" . $research_id;
                
                // Prepare content data
                $content_data = [
                    'title' => $title,
                    'content' => $description,
                    'excerpt' => !empty($abstract) ? substr($abstract, 0, 300) : substr($description, 0, 300),
                    'author' => $author,
                    'date' => date('F d, Y'),
                    'link' => $content_link,
                    'type' => 'Research Project',
                    'status' => $status
                ];
                
                // Send automated email (using 'resource' content type as per documentation)
                sendAutomatedEmail('resource', $research_id, $content_data);
            }
            
            header("Location: ../research_list.php?success=Research project updated successfully");
            exit();
        } else {
            $stmt->close();
            header("Location: ../edit_research.php?id=" . $research_id . "&error=Failed to update research project: " . $conn->error);
            exit();
        }
    }
} else {
    header("Location: ../research_list.php");
    exit();
}

$conn->close();
?>

