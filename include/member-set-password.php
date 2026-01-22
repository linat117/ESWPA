<?php
session_start();
include 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');
    
    if (empty($email) || empty($password) || empty($confirm_password)) {
        header("Location: ../member-set-password.php?email=" . urlencode($email) . "&error=Please fill in all fields");
        exit();
    }
    
    // Validate password length
    if (strlen($password) < 8) {
        header("Location: ../member-set-password.php?email=" . urlencode($email) . "&error=password_weak");
        exit();
    }
    
    // Check if passwords match
    if ($password !== $confirm_password) {
        header("Location: ../member-set-password.php?email=" . urlencode($email) . "&error=password_mismatch");
        exit();
    }
    
    // Check if member exists and is approved
    $checkQuery = "SELECT r.id, r.approval_status, r.membership_id 
                   FROM registrations r 
                   WHERE r.email = ? AND r.approval_status = 'approved'";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->bind_param("s", $email);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows == 0) {
        header("Location: ../member-set-password.php?email=" . urlencode($email) . "&error=Member not found or not approved");
        exit();
    }
    
    $memberData = $checkResult->fetch_assoc();
    $member_id = $memberData['id'];
    $membership_id = $memberData['membership_id'];
    
    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Check if member_access record exists
    $accessQuery = "SELECT id FROM member_access WHERE member_id = ?";
    $accessStmt = $conn->prepare($accessQuery);
    $accessStmt->bind_param("i", $member_id);
    $accessStmt->execute();
    $accessResult = $accessStmt->get_result();
    
    if ($accessResult->num_rows > 0) {
        // Update existing password
        $updateQuery = "UPDATE member_access SET password = ?, status = 'active' WHERE member_id = ?";
        $updateStmt = $conn->prepare($updateQuery);
        $updateStmt->bind_param("si", $hashedPassword, $member_id);
        
        if ($updateStmt->execute()) {
            // Set session and redirect to dashboard
            $_SESSION['member_id'] = $member_id;
            $_SESSION['membership_id'] = $membership_id;
            $_SESSION['member_email'] = $email;
            $_SESSION['member_access_id'] = $accessResult->fetch_assoc()['id'];
            
            // Get member name
            $nameQuery = "SELECT fullname FROM registrations WHERE id = ?";
            $nameStmt = $conn->prepare($nameQuery);
            $nameStmt->bind_param("i", $member_id);
            $nameStmt->execute();
            $nameResult = $nameStmt->get_result();
            if ($nameResult->num_rows > 0) {
                $_SESSION['member_name'] = $nameResult->fetch_assoc()['fullname'];
            }
            $nameStmt->close();
            
            header("Location: ../member-dashboard.php?success=password_set");
            exit();
        } else {
            header("Location: ../member-set-password.php?email=" . urlencode($email) . "&error=Failed to update password");
            exit();
        }
        
        $updateStmt->close();
    } else {
        // Create new member_access record
        $insertQuery = "INSERT INTO member_access (member_id, email, password, membership_id, status) 
                        VALUES (?, ?, ?, ?, 'active')";
        $insertStmt = $conn->prepare($insertQuery);
        $insertStmt->bind_param("isss", $member_id, $email, $hashedPassword, $membership_id);
        
        if ($insertStmt->execute()) {
            $access_id = $insertStmt->insert_id;
            
            // Set session
            $_SESSION['member_id'] = $member_id;
            $_SESSION['membership_id'] = $membership_id;
            $_SESSION['member_email'] = $email;
            $_SESSION['member_access_id'] = $access_id;
            
            // Get member name
            $nameQuery = "SELECT fullname FROM registrations WHERE id = ?";
            $nameStmt = $conn->prepare($nameQuery);
            $nameStmt->bind_param("i", $member_id);
            $nameStmt->execute();
            $nameResult = $nameStmt->get_result();
            if ($nameResult->num_rows > 0) {
                $_SESSION['member_name'] = $nameResult->fetch_assoc()['fullname'];
            }
            $nameStmt->close();
            
            header("Location: ../member-dashboard.php?success=password_set");
            exit();
        } else {
            header("Location: ../member-set-password.php?email=" . urlencode($email) . "&error=Failed to create account");
            exit();
        }
        
        $insertStmt->close();
    }
    
    $accessStmt->close();
    $checkStmt->close();
    $conn->close();
} else {
    header("Location: ../member-login.php");
    exit();
}
?>

