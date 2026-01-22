<?php
session_start();
include 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $token = trim($_POST['token'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');
    
    if (empty($token) || empty($password) || empty($confirm_password)) {
        header("Location: ../member-reset-password.php?token=" . urlencode($token) . "&error=Please fill in all fields");
        exit();
    }
    
    // Validate password length
    if (strlen($password) < 8) {
        header("Location: ../member-reset-password.php?token=" . urlencode($token) . "&error=password_weak");
        exit();
    }
    
    // Check if passwords match
    if ($password !== $confirm_password) {
        header("Location: ../member-reset-password.php?token=" . urlencode($token) . "&error=password_mismatch");
        exit();
    }
    
    // Validate token
    $query = "SELECT prt.*, r.id as member_id, r.email 
              FROM password_reset_tokens prt
              INNER JOIN registrations r ON prt.member_id = r.id
              WHERE prt.token = ? AND prt.used = 0 AND prt.expires_at > NOW()";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $stmt->close();
        header("Location: ../member-forgot-password.php?error=Invalid or expired reset link. Please request a new one.");
        exit();
    }
    
    $tokenData = $result->fetch_assoc();
    $member_id = $tokenData['member_id'];
    
    // Hash new password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Update password in member_access
    $updateQuery = "UPDATE member_access SET password = ? WHERE member_id = ?";
    $updateStmt = $conn->prepare($updateQuery);
    $updateStmt->bind_param("si", $hashedPassword, $member_id);
    
    if ($updateStmt->execute()) {
        // Mark token as used
        $markUsedQuery = "UPDATE password_reset_tokens SET used = 1 WHERE token = ?";
        $markStmt = $conn->prepare($markUsedQuery);
        $markStmt->bind_param("s", $token);
        $markStmt->execute();
        $markStmt->close();
        
        header("Location: ../member-login.php?success=password_reset");
        exit();
    } else {
        header("Location: ../member-reset-password.php?token=" . urlencode($token) . "&error=Failed to reset password. Please try again.");
        exit();
    }
    
    $updateStmt->close();
    $stmt->close();
    $conn->close();
} else {
    header("Location: ../member-forgot-password.php");
    exit();
}
?>

