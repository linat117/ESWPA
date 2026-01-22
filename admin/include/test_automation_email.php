<?php
/**
 * Test Email Automation
 * Sends a test email using the automation system
 */

session_start();
include 'conn.php';

if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

require_once __DIR__ . '/email_automation.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['content_type']) && isset($_POST['test_email'])) {
    $content_type = $_POST['content_type'];
    $test_email = trim($_POST['test_email']);
    
    // Validate email
    if (!filter_var($test_email, FILTER_VALIDATE_EMAIL)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid email address']);
        exit();
    }
    
    // Get automation settings
    $settings = getAutomationSettings($content_type);
    
    if (!$settings) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Automation settings not found for this content type']);
        exit();
    }
    
    // Create test content data
    $test_content = [
        'title' => 'Test ' . ucfirst($content_type) . ' - ' . date('Y-m-d H:i:s'),
        'content' => 'This is a test email from the Email Automation System. If you received this email, the automation is working correctly!<br><br>Content Type: ' . ucfirst($content_type) . '<br>Test Date: ' . date('Y-m-d H:i:s'),
        'author' => $_SESSION['username'] ?? 'Admin',
        'date' => date('Y-m-d'),
        'status' => 'published',
        'images' => [],
        'link' => (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . "/"
    ];
    
    // Generate email content using the template
    $emailContent = generateEmailContent($content_type, $test_content, $settings['template_id']);
    
    // Send test email directly to the specified email
    require_once __DIR__ . '/email_handler.php';
    
    $success = sendNewsletter($emailContent['subject'], $emailContent['body'], [$test_email]);
    
    if ($success) {
        // Log the test send
        logAutomation(
            $content_type,
            0, // Test content ID
            $test_content['title'],
            1, // 1 recipient
            1, // 1 sent
            0, // 0 failed
            'success',
            $_SESSION['user_id'],
            null
        );
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true, 
            'message' => 'Test email sent successfully to ' . $test_email
        ]);
    } else {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false, 
            'message' => 'Failed to send test email. Please check your email configuration.'
        ]);
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
exit();

