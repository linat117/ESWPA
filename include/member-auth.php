<?php
session_start();
include 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
        if (empty($email) || empty($password)) {
        header("Location: member-login.php?error=Please fill in all fields");
        exit();
    }
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("Location: member-login.php?error=Invalid email format");
        exit();
    }
    
    // Check if member exists in member_access table
    $query = "SELECT ma.*, r.approval_status, r.status as reg_status, r.expiry_date, r.membership_id, r.fullname, r.id as registration_id
              FROM member_access ma
              INNER JOIN registrations r ON ma.member_id = r.id
              WHERE ma.email = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $member = $result->fetch_assoc();
        
        // Verify password
        if (password_verify($password, $member['password'])) {
            // Check if member is approved
            if ($member['approval_status'] !== 'approved') {
                header("Location: member-login.php?error=not_approved");
                exit();
            }
            
            // Check if membership is expired
            if (!empty($member['expiry_date'])) {
                $expiryDate = new DateTime($member['expiry_date']);
                $today = new DateTime();
                
                if ($expiryDate < $today) {
                    // Update status to expired
                    $updateStatusQuery = "UPDATE registrations SET status = 'expired' WHERE id = ?";
                    $updateStmt = $conn->prepare($updateStatusQuery);
                    $updateStmt->bind_param("i", $member['registration_id']);
                    $updateStmt->execute();
                    $updateStmt->close();
                    
                    header("Location: member-login.php?error=expired");
                    exit();
                }
            }
            
            // Check if account is suspended
            if ($member['status'] === 'suspended') {
                header("Location: member-login.php?error=suspended");
                exit();
            }
            
            // Update last login
            $updateLoginQuery = "UPDATE member_access SET last_login = NOW() WHERE id = ?";
            $updateLoginStmt = $conn->prepare($updateLoginQuery);
            $updateLoginStmt->bind_param("i", $member['id']);
            $updateLoginStmt->execute();
            $updateLoginStmt->close();
            
            // Set session variables
            $_SESSION['member_id'] = $member['member_id'];
            $_SESSION['membership_id'] = $member['membership_id'];
            $_SESSION['member_email'] = $member['email'];
            $_SESSION['member_name'] = $member['fullname'];
            $_SESSION['member_access_id'] = $member['id'];
            
            // Redirect to member dashboard
            header("Location: member-dashboard.php");
            exit();
            
        } else {
            header("Location: member-login.php?error=invalid");
            exit();
        }
    } else {
        // Member not found in member_access - might need to set password first
        // Check if they exist in registrations and are approved
        $regQuery = "SELECT * FROM registrations WHERE email = ? AND approval_status = 'approved'";
        $regStmt = $conn->prepare($regQuery);
        $regStmt->bind_param("s", $email);
        $regStmt->execute();
        $regResult = $regStmt->get_result();
        
        if ($regResult->num_rows == 1) {
            // Member is approved but hasn't set password yet
            header("Location: member-set-password.php?email=" . urlencode($email));
            exit();
        } else {
            header("Location: member-login.php?error=invalid");
            exit();
        }
    }
    
    $stmt->close();
} else {
    header("Location: member-login.php");
    exit();
}
?>

