<?php
/**
 * Email Subscription Handler
 * Handles email subscription processing, validation, and confirmation emails
 * 
 * Agent: agent_dev_1
 * Date: 2025-12-16
 */

// Include database connection
if (!isset($conn)) {
    include __DIR__ . '/config.php';
}

// Include PHPMailer - handle different include paths
$vendorPath = __DIR__ . '/../vendor/autoload.php';
if (!file_exists($vendorPath)) {
    // Try alternative path
    $vendorPath = dirname(dirname(__DIR__)) . '/vendor/autoload.php';
}
if (file_exists($vendorPath)) {
    require_once $vendorPath;
} else {
    error_log("PHPMailer autoload not found. Tried: " . __DIR__ . '/../vendor/autoload.php');
    die("Email system error: PHPMailer not found");
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Generate a unique unsubscribe token
 */
function generateUnsubscribeToken($email) {
    return bin2hex(random_bytes(32)) . '_' . md5($email . time());
}

/**
 * Process email subscription
 * 
 * @param string $email Email address
 * @param string $name Optional name
 * @param string $source Subscription source (popup, footer, manual)
 * @param string $ip_address IP address
 * @param string $user_agent User agent string
 * @return array Result array with success status and message
 */
function processSubscription($email, $name = null, $source = 'popup', $ip_address = null, $user_agent = null) {
    global $conn;
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return [
            'success' => false,
            'message' => 'Please enter a valid email address.'
        ];
    }
    
    // Sanitize inputs
    $email = filter_var($email, FILTER_SANITIZE_EMAIL);
    $name = $name ? filter_var($name, FILTER_SANITIZE_STRING) : null;
    $source = filter_var($source, FILTER_SANITIZE_STRING);
    
    // Get IP address if not provided
    if (!$ip_address) {
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
    }
    
    // Get user agent if not provided
    if (!$user_agent) {
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
    }
    
    // Check if email already exists
    $checkQuery = "SELECT id, status FROM email_subscribers WHERE email = ? LIMIT 1";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->bind_param("s", $email);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows > 0) {
        $existing = $checkResult->fetch_assoc();
        $checkStmt->close();
        
        // If already subscribed and active, return success message
        if ($existing['status'] === 'active') {
            return [
                'success' => true,
                'message' => 'You are already subscribed to our newsletter!'
            ];
        }
        
        // If unsubscribed, reactivate
        if ($existing['status'] === 'unsubscribed') {
            $unsubscribe_token = generateUnsubscribeToken($email);
            $updateQuery = "UPDATE email_subscribers SET 
                            status = 'active', 
                            name = ?, 
                            source = ?, 
                            subscribed_at = NOW(), 
                            unsubscribed_at = NULL,
                            unsubscribe_token = ?,
                            ip_address = ?,
                            user_agent = ?
                            WHERE id = ?";
            $updateStmt = $conn->prepare($updateQuery);
            $updateStmt->bind_param("sssssi", $name, $source, $unsubscribe_token, $ip_address, $user_agent, $existing['id']);
            $updateStmt->execute();
            $updateStmt->close();
            
            // Send confirmation email
            $emailSent = sendSubscriptionConfirmation($email, $name, $unsubscribe_token);
            
            if ($emailSent) {
                return [
                    'success' => true,
                    'message' => 'Thank you for resubscribing! A confirmation email has been sent.'
                ];
            } else {
                error_log("Warning: Resubscription saved for {$email} but confirmation email failed to send");
                return [
                    'success' => true,
                    'message' => 'Thank you for resubscribing! However, there was an issue sending the confirmation email.'
                ];
            }
        }
    }
    
    $checkStmt->close();
    
    // Generate unsubscribe token
    $unsubscribe_token = generateUnsubscribeToken($email);
    
    // Insert new subscriber
    $insertQuery = "INSERT INTO email_subscribers 
                    (email, name, status, source, ip_address, user_agent, unsubscribe_token) 
                    VALUES (?, ?, 'active', ?, ?, ?, ?)";
    $insertStmt = $conn->prepare($insertQuery);
    $insertStmt->bind_param("ssssss", $email, $name, $source, $ip_address, $user_agent, $unsubscribe_token);
    
    if ($insertStmt->execute()) {
        $insertStmt->close();
        
        // Send confirmation email
        $emailSent = sendSubscriptionConfirmation($email, $name, $unsubscribe_token);
        
        if ($emailSent) {
            return [
                'success' => true,
                'message' => 'Thank you for subscribing! A confirmation email has been sent.'
            ];
        } else {
            // Subscription saved but email failed - still return success but log the issue
            error_log("Warning: Subscription saved for {$email} but confirmation email failed to send");
            return [
                'success' => true,
                'message' => 'Thank you for subscribing! However, there was an issue sending the confirmation email. Please contact us if you don\'t receive it.'
            ];
        }
    } else {
        $error = $insertStmt->error;
        $insertStmt->close();
        
        error_log("Subscription error: " . $error);
        return [
            'success' => false,
            'message' => 'Sorry, there was an error processing your subscription. Please try again later.'
        ];
    }
}

/**
 * Send subscription confirmation email
 * 
 * @param string $email Subscriber email
 * @param string $name Subscriber name
 * @param string $unsubscribe_token Unsubscribe token
 * @return bool Success status
 */
function sendSubscriptionConfirmation($email, $name = null, $unsubscribe_token) {
    $mail = new PHPMailer(true);
    
    try {
        // Enable verbose debug output (for troubleshooting - uncomment if needed)
        // $mail->SMTPDebug = 2; // Uncomment for detailed debugging
        // $mail->Debugoutput = 'error_log'; // Log debug output to error_log
        
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'mail.ethiosocialworker.org';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'noreplay@ethiosocialworker.org';
        $mail->Password   = 'o%-4Y*-Zmpm*P9?x';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;
        $mail->CharSet    = 'UTF-8';
        
        // Additional SMTP options
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );
        
        // Sender
        $mail->setFrom('noreplay@ethiosocialworker.org', 'Ethio Social Works');
        
        // Recipient
        $mail->addAddress($email, $name);
        
        // Generate unsubscribe URL
        $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
        $host = $_SERVER['HTTP_HOST'];
        $unsubscribe_url = $protocol . "://" . $host . "/unsubscribe.php?token=" . urlencode($unsubscribe_token);
        
        // Email content
        $mail->isHTML(true);
        $mail->Subject = 'Welcome to Ethio Social Works Newsletter!';
        
        $greeting = $name ? "Hello {$name}," : "Hello,";
        
        $mail->Body = "
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
                .button { display: inline-block; padding: 12px 30px; background: #667eea; color: white; text-decoration: none; border-radius: 5px; margin: 20px 0; }
                .footer { text-align: center; margin-top: 20px; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Welcome to Ethio Social Works!</h1>
                </div>
                <div class='content'>
                    <p>{$greeting}</p>
                    <p>Thank you for subscribing to our newsletter! You'll now receive updates about:</p>
                    <ul>
                        <li>Upcoming events and workshops</li>
                        <li>Latest news and announcements</li>
                        <li>Professional development opportunities</li>
                        <li>Community initiatives and resources</li>
                    </ul>
                    <p>We're excited to have you as part of our community!</p>
                    <p>If you no longer wish to receive our emails, you can <a href='{$unsubscribe_url}'>unsubscribe here</a>.</p>
                </div>
                <div class='footer'>
                    <p>Ethiopian Social Workers Professional Association</p>
                    <p>© " . date('Y') . " All rights reserved</p>
                </div>
            </div>
        </body>
        </html>";
        
        $mail->AltBody = "{$greeting}\n\nThank you for subscribing to our newsletter! You'll now receive updates about upcoming events, news, and professional development opportunities.\n\nIf you no longer wish to receive our emails, you can unsubscribe here: {$unsubscribe_url}";
        
        $mail->send();
        
        // Log successful email send
        error_log("Subscription confirmation email sent successfully to: {$email}");
        return true;
    } catch (Exception $e) {
        // Log detailed error information
        $errorMsg = "Subscription confirmation email error for {$email}: {$mail->ErrorInfo}";
        error_log($errorMsg);
        
        // Also log to a specific email error log file
        $logFile = __DIR__ . '/../error_log';
        file_put_contents($logFile, date('Y-m-d H:i:s') . " - " . $errorMsg . "\n", FILE_APPEND);
        
        return false;
    }
}

/**
 * Unsubscribe from newsletter
 * 
 * @param string $token Unsubscribe token
 * @return array Result array with success status and message
 */
function unsubscribe($token) {
    global $conn;
    
    if (empty($token)) {
        return [
            'success' => false,
            'message' => 'Invalid unsubscribe link.'
        ];
    }
    
    // Find subscriber by token
    $query = "SELECT id, email, status FROM email_subscribers WHERE unsubscribe_token = ? LIMIT 1";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $stmt->close();
        return [
            'success' => false,
            'message' => 'Invalid unsubscribe link.'
        ];
    }
    
    $subscriber = $result->fetch_assoc();
    $stmt->close();
    
    // If already unsubscribed
    if ($subscriber['status'] === 'unsubscribed') {
        return [
            'success' => true,
            'message' => 'You are already unsubscribed from our newsletter.'
        ];
    }
    
    // Update status to unsubscribed
    $updateQuery = "UPDATE email_subscribers SET 
                    status = 'unsubscribed', 
                    unsubscribed_at = NOW() 
                    WHERE id = ?";
    $updateStmt = $conn->prepare($updateQuery);
    $updateStmt->bind_param("i", $subscriber['id']);
    
    if ($updateStmt->execute()) {
        $updateStmt->close();
        return [
            'success' => true,
            'message' => 'You have been successfully unsubscribed from our newsletter.'
        ];
    } else {
        $error = $updateStmt->error;
        $updateStmt->close();
        error_log("Unsubscribe error: " . $error);
        return [
            'success' => false,
            'message' => 'Sorry, there was an error processing your unsubscribe request. Please try again later.'
        ];
    }
}

