<?php
/**
 * Member Profile Handler
 * Handles member profile updates
 * 
 * Last Updated: December 24, 2025
 */

function updateMemberProfile($member_id, $post_data, $files) {
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
        // Validate date format
        $date = DateTime::createFromFormat('Y-m-d', $graduation_date);
        if ($date && $date->format('Y-m-d') === $graduation_date) {
            $updates['graduation_date'] = $graduation_date;
        }
    }
    
    // Email - Check if trying to change (should not be allowed)
    if (isset($post_data['email'])) {
        // Verify email belongs to this member
        $checkQuery = "SELECT email FROM registrations WHERE id = ?";
        $checkStmt = $conn->prepare($checkQuery);
        $checkStmt->bind_param("i", $member_id);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        $currentMember = $checkResult->fetch_assoc();
        $checkStmt->close();
        
        if ($currentMember && $currentMember['email'] !== $post_data['email']) {
            $errors[] = "Email cannot be changed. Please contact admin if you need to change your email.";
        }
    }
    
    // Handle photo upload
    if (isset($files['photo']) && $files['photo']['error'] === UPLOAD_ERR_OK) {
        $photoResult = handlePhotoUpload($files['photo'], $member_id);
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
    
    $setParts[] = "updated_at = NOW()";
    
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
        logMemberActivity($member_id, 'profile_updated', 'Updated profile information');
        
        return [
            'success' => true,
            'message' => 'Profile updated successfully!'
        ];
    } else {
        $error = $stmt->error;
        $stmt->close();
        
        return [
            'success' => false,
            'message' => 'Failed to update profile: ' . $error
        ];
    }
}

function handlePhotoUpload($file, $member_id) {
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
    $uploadDir = 'uploads/members/photos/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'member_' . $member_id . '_' . time() . '.' . $extension;
    $filepath = $uploadDir . $filename;
    
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
        
        if (!empty($oldMember['photo']) && file_exists($oldMember['photo'])) {
            @unlink($oldMember['photo']);
        }
        
        return [
            'success' => true,
            'path' => $filepath,
            'message' => 'Photo uploaded successfully'
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Failed to upload photo. Please try again.'
        ];
    }
}

function logMemberActivity($member_id, $activity_type, $description) {
    global $conn;
    
    // Check if member_activities table exists, if not, skip logging
    $checkTable = "SHOW TABLES LIKE 'member_activities'";
    $result = $conn->query($checkTable);
    
    if ($result && $result->num_rows > 0) {
        $query = "INSERT INTO member_activities (member_id, activity_type, activity_description) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($query);
        if ($stmt) {
            $stmt->bind_param("iss", $member_id, $activity_type, $description);
            $stmt->execute();
            $stmt->close();
        }
    }
}

?>

