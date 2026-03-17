<?php
/**
 * Update Member Handler
 * Handles member information updates from admin panel
 * 
 * Last Updated: December 24, 2025
 */

function updateMemberDetails($member_id, $post_data, $files) {
    global $conn;
    
    $errors = [];
    $updates = [];
    
    // Validate and prepare updates
    // Full Name
    if (isset($post_data['fullname'])) {
        $fullname = trim($post_data['fullname']);
        if (empty($fullname)) {
            $errors[] = "Full name is required";
        } elseif (strlen($fullname) > 255) {
            $errors[] = "Full name must be less than 255 characters";
        } else {
            $updates['fullname'] = $fullname;
        }
    }
    
    // Phone
    if (isset($post_data['phone'])) {
        $phone = trim($post_data['phone']);
        if (empty($phone)) {
            $errors[] = "Phone number is required";
        } elseif (strlen($phone) > 20) {
            $errors[] = "Phone number must be less than 20 characters";
        } else {
            $updates['phone'] = $phone;
        }
    }
    
    // Sex
    if (isset($post_data['sex'])) {
        $sex = $post_data['sex'];
        if (!in_array($sex, ['Male', 'Female'])) {
            $errors[] = "Invalid sex selection";
        } else {
            $updates['sex'] = $sex;
        }
    }
    
    // Address
    if (isset($post_data['address'])) {
        $address = trim($post_data['address']);
        if (empty($address)) {
            $errors[] = "Address is required";
        } else {
            $updates['address'] = $address;
        }
    }
    
    // Qualification
    if (isset($post_data['qualification'])) {
        $qualification = trim($post_data['qualification']);
        if (empty($qualification)) {
            $errors[] = "Qualification is required";
        } elseif (strlen($qualification) > 255) {
            $errors[] = "Qualification must be less than 255 characters";
        } else {
            $updates['qualification'] = $qualification;
        }
    }
    
    // Graduation Date (optional)
    if (isset($post_data['graduation_date']) && !empty($post_data['graduation_date'])) {
        $graduation_date = $post_data['graduation_date'];
        $date = DateTime::createFromFormat('Y-m-d', $graduation_date);
        if ($date && $date->format('Y-m-d') === $graduation_date) {
            $updates['graduation_date'] = $graduation_date;
        }
    }
    
    // Approval Status
    if (isset($post_data['approval_status'])) {
        $approval_status = $post_data['approval_status'];
        if (in_array($approval_status, ['pending', 'approved', 'rejected'])) {
            $updates['approval_status'] = $approval_status;
            
            // If approving, set approved_by and approved_at
            if ($approval_status == 'approved' && !isset($updates['approved_at'])) {
                $updates['approved_by'] = $_SESSION['user_id'];
                $updates['approved_at'] = date('Y-m-d H:i:s');
            }
        }
    }
    
    // Membership Status
    if (isset($post_data['status'])) {
        $status = $post_data['status'];
        if (in_array($status, ['pending', 'active', 'expired'])) {
            $updates['status'] = $status;
        }
    }
    
    // Expiry Date (for manual renewal)
    if (isset($post_data['expiry_date']) && !empty($post_data['expiry_date'])) {
        $expiry_date = $post_data['expiry_date'];
        $date = DateTime::createFromFormat('Y-m-d', $expiry_date);
        if ($date && $date->format('Y-m-d') === $expiry_date) {
            $updates['expiry_date'] = $expiry_date;
            // If expiry date is in future, set status to active
            $today = new DateTime();
            if ($date > $today) {
                $updates['status'] = 'active';
            }
        }
    }
    
    // Email - Check if trying to change (verify uniqueness)
    if (isset($post_data['email'])) {
        $email = trim($post_data['email']);
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Valid email is required";
        } else {
            // Check if email is already taken by another member
            $checkQuery = "SELECT id FROM registrations WHERE email = ? AND id != ?";
            $checkStmt = $conn->prepare($checkQuery);
            $checkStmt->bind_param("si", $email, $member_id);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();
            $checkStmt->close();
            
            if ($checkResult->num_rows > 0) {
                $errors[] = "Email is already registered to another member";
            } else {
                $updates['email'] = $email;
            }
        }
    }
    
    // Handle photo upload
    if (isset($files['photo']) && $files['photo']['error'] === UPLOAD_ERR_OK) {
        $photoResult = handleMemberPhotoUpload($files['photo'], $member_id);
        if ($photoResult['success']) {
            $updates['photo'] = $photoResult['path'];
        } else {
            $errors[] = $photoResult['message'];
        }
    }
    
    // If there are errors, return them
    if (!empty($errors)) {
        return [
            'success' => false,
            'message' => implode('<br>', $errors)
        ];
    }
    
    // If no updates, return success
    if (empty($updates)) {
        return [
            'success' => true,
            'message' => 'No changes to save'
        ];
    }
    
    // Build update query
    $setParts = [];
    $types = '';
    $values = [];
    
    foreach ($updates as $field => $value) {
        $setParts[] = "$field = ?";
        $types .= 's';
        $values[] = $value;
    }
    
    // Add updated_at if field exists
    $checkUpdatedAt = "SHOW COLUMNS FROM registrations LIKE 'updated_at'";
    $updatedAtResult = $conn->query($checkUpdatedAt);
    if ($updatedAtResult && $updatedAtResult->num_rows > 0) {
        $setParts[] = "updated_at = NOW()";
    }
    
    $query = "UPDATE registrations SET " . implode(', ', $setParts) . " WHERE id = ?";
    $types .= 'i';
    $values[] = $member_id;
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        return [
            'success' => false,
            'message' => 'Database error: ' . $conn->error
        ];
    }
    
    $stmt->bind_param($types, ...$values);
    
    if ($stmt->execute()) {
        $stmt->close();
        
        // Log activity
        logAdminActivity($_SESSION['user_id'], 'member_updated', "Updated member ID: $member_id");
        
        // If member was approved, create notification (only if handler file exists)
        if (isset($updates['approval_status']) && $updates['approval_status'] == 'approved') {
            $notificationsPath = __DIR__ . '/../../include/notifications_handler.php';
            if (file_exists($notificationsPath)) {
                require_once $notificationsPath;
                if (function_exists('notifyMemberApproved')) {
                    notifyMemberApproved($member_id);
                }
            }
        }
        
        return [
            'success' => true,
            'message' => 'Member updated successfully!'
        ];
    } else {
        $error = $stmt->error;
        $stmt->close();
        
        return [
            'success' => false,
            'message' => 'Failed to update member: ' . $error
        ];
    }
}

function handleMemberPhotoUpload($file, $member_id) {
    // Allowed file types
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
    $maxSize = 2 * 1024 * 1024; // 2MB
    
    // Check file type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, $allowedTypes)) {
        return [
            'success' => false,
            'message' => 'Invalid file type. Only JPG, PNG, and WEBP are allowed.'
        ];
    }
    
    // Check file size
    if ($file['size'] > $maxSize) {
        return [
            'success' => false,
            'message' => 'File size must be less than 2MB.'
        ];
    }
    
    // Create upload directory if it doesn't exist
    $uploadDir = '../uploads/members/photos/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'member_' . $member_id . '_' . time() . '.' . $extension;
    $filepath = $uploadDir . $filename;
    $relativePath = 'uploads/members/photos/' . $filename;
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        // Delete old photo if exists
        global $conn;
        $oldPhotoQuery = "SELECT photo FROM registrations WHERE id = ?";
        $oldPhotoStmt = $conn->prepare($oldPhotoQuery);
        $oldPhotoStmt->bind_param("i", $member_id);
        $oldPhotoStmt->execute();
        $oldPhotoResult = $oldPhotoStmt->get_result();
        $oldMember = $oldPhotoResult->fetch_assoc();
        $oldPhotoStmt->close();
        
        if (!empty($oldMember['photo']) && file_exists('../' . $oldMember['photo'])) {
            @unlink('../' . $oldMember['photo']);
        }
        
        return [
            'success' => true,
            'path' => $relativePath,
            'message' => 'Photo uploaded successfully'
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Failed to upload photo. Please try again.'
        ];
    }
}

function logAdminActivity($admin_id, $activity_type, $description) {
    global $conn;
    
    // Check if audit_logs table exists
    $checkTable = "SHOW TABLES LIKE 'audit_logs'";
    $result = $conn->query($checkTable);
    
    if ($result && $result->num_rows > 0) {
        // Align with actual audit_logs schema:
        // user_id, user_type, action, table_name, record_id, old_value, new_value, ip_address, user_agent
        $query = "INSERT INTO audit_logs (user_id, user_type, action, table_name, record_id, old_value, new_value, ip_address, user_agent)
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        if ($stmt) {
            $ip_address  = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $user_agent  = $_SERVER['HTTP_USER_AGENT'] ?? '';
            $user_type   = 'admin';
            $table_name  = null;
            $record_id   = null;
            $old_value   = null;
            $new_value   = $description;
            $stmt->bind_param(
                "isssissss",
                $admin_id,
                $user_type,
                $activity_type,
                $table_name,
                $record_id,
                $old_value,
                $new_value,
                $ip_address,
                $user_agent
            );
            $stmt->execute();
            $stmt->close();
        }
    }
}

?>

