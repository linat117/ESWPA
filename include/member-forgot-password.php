<?php
session_start();
include 'config.php';

// Load PHPMailer
require_once __DIR__ . '/../vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email'] ?? '');
    
    if (empty($email)) {
        header("Location: ../member-forgot-password.php?error=Please enter your email address");
        exit();
    }
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("Location: ../member-forgot-password.php?error=Invalid email format");
        exit();
    }
    
    // Check if member exists - check both registrations and member_access tables
    $query = "SELECT r.id, r.fullname, r.approval_status, r.status, r.email as reg_email, 
                     ma.id as access_id, ma.email as access_email
              FROM registrations r
              LEFT JOIN member_access ma ON r.id = ma.member_id
              WHERE r.email = ? OR ma.email = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $email, $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        header("Location: ../member-forgot-password.php?error=email_not_found");
        exit();
    }
    
    $member = $result->fetch_assoc();
    
    // Check if member is approved
    if ($member['approval_status'] !== 'approved') {
        header("Location: ../member-forgot-password.php?error=not_approved");
        exit();
    }
    
    // Check if member_access exists
    if (empty($member['access_id'])) {
        header("Location: ../member-forgot-password.php?error=Account not activated. Please contact support.");
        exit();
    }
    
    // Use the email from member_access if available, otherwise use registrations email
    $memberEmail = !empty($member['access_email']) ? $member['access_email'] : $member['reg_email'];
    
    // Generate reset token
    $token = bin2hex(random_bytes(32)); // 64 character token
    $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour')); // Token expires in 1 hour
    
    // Delete any existing unused tokens for this member
    $deleteQuery = "DELETE FROM password_reset_tokens WHERE member_id = ? AND used = 0";
    $deleteStmt = $conn->prepare($deleteQuery);
    $deleteStmt->bind_param("i", $member['id']);
    $deleteStmt->execute();
    $deleteStmt->close();
    
    // Insert new reset token - use the email from member_access or registrations
    $insertQuery = "INSERT INTO password_reset_tokens (member_id, email, token, expires_at) 
                    VALUES (?, ?, ?, ?)";
    $insertStmt = $conn->prepare($insertQuery);
    $insertStmt->bind_param("isss", $member['id'], $memberEmail, $token, $expires_at);
    
    if ($insertStmt->execute()) {
        // Send reset email
        $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
        $host = $_SERVER['HTTP_HOST'];
        $scriptPath = dirname(dirname($_SERVER['PHP_SELF']));
        $resetLink = $protocol . "://" . $host . $scriptPath . "/member-reset-password.php?token=" . $token;
        
        // Send email using PHPMailer
        $mail = new PHPMailer(true);
        
        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host       = 'mail.ethiosocialworker.org';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'noreplay@ethiosocialworker.org';
            $mail->Password   = 'o%-4Y*-Zmpm*P9?x';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port       = 465;
            
            // Sender
            $mail->setFrom('noreplay@ethiosocialworker.org', 'Ethio Social Works');
            
            // Recipient - use the email from member_access or registrations
            $mail->addAddress($memberEmail, $member['fullname']);
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Password Reset Request - Ethio Social Works';
            
            $mail->Body = "
                <html>
                <head>
                    <style>
                        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                        .header { background-color: #1273c3; color: white; padding: 20px; text-align: center; }
                        .content { padding: 20px; background-color: #f9f9f9; }
                        .button { display: inline-block; padding: 12px 30px; background-color: #1273c3; color: white; text-decoration: none; border-radius: 5px; margin: 20px 0; }
                        .footer { padding: 20px; text-align: center; font-size: 12px; color: #666; }
                        .warning { color: #d9534f; font-weight: bold; }
                    </style>
                </head>
                <body>
                    <div class='container'>
                        <div class='header'>
                            <h2>Password Reset Request</h2>
                        </div>
                        <div class='content'>
                            <p>Hello " . htmlspecialchars($member['fullname']) . ",</p>
                            <p>We received a request to reset your password for your Ethio Social Works member account.</p>
                            <p>Click the button below to reset your password:</p>
                            <p style='text-align: center;'>
                                <a href='" . $resetLink . "' class='button'>Reset Password</a>
                            </p>
                            <p>Or copy and paste this link into your browser:</p>
                            <p style='word-break: break-all; color: #1273c3;'>" . $resetLink . "</p>
                            <p class='warning'>This link will expire in 1 hour.</p>
                            <p>If you did not request a password reset, please ignore this email or contact support if you have concerns.</p>
                            <p>Best regards,<br>Ethio Social Works Team</p>
                        </div>
                        <div class='footer'>
                            <p>This is an automated email. Please do not reply to this message.</p>
                        </div>
                    </div>
                </body>
                </html>
            ";
            
            $mail->AltBody = "Hello " . $member['fullname'] . ",\n\n" .
                           "We received a request to reset your password.\n\n" .
                           "Click this link to reset your password: " . $resetLink . "\n\n" .
                           "This link will expire in 1 hour.\n\n" .
                           "If you did not request this, please ignore this email.\n\n" .
                           "Best regards,\nEthio Social Works Team";
            
            $mail->send();
            
            header("Location: ../member-forgot-password.php?success=1");
            exit();
            
        } catch (Exception $e) {
            error_log("Password reset email failed: " . $mail->ErrorInfo);
            header("Location: ../member-forgot-password.php?error=email_failed");
            exit();
        }
        
    } else {
        header("Location: ../member-forgot-password.php?error=Failed to generate reset token. Please try again.");
        exit();
    }
    
    $insertStmt->close();
    $stmt->close();
    $conn->close();
} else {
    header("Location: ../member-forgot-password.php");
    exit();
}
?>

