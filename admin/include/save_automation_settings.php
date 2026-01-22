<?php
/**
 * Save Email Automation Settings
 * Handles saving automation settings for each content type
 */

session_start();
include 'conn.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth-login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_settings'])) {
    $content_type = $_POST['content_type'] ?? '';
    
    if (empty($content_type)) {
        header("Location: ../email_automation_settings.php?error=Invalid content type");
        exit();
    }
    
    $enabled = isset($_POST['enabled']) ? 1 : 0;
    $send_to_subscribers = isset($_POST['send_to_subscribers']) ? 1 : 0;
    $send_to_members = isset($_POST['send_to_members']) ? 1 : 0;
    $send_to_custom = isset($_POST['send_to_custom']) ? 1 : 0;
    $custom_emails = trim($_POST['custom_emails'] ?? '');
    $template_id = !empty($_POST['template_id']) ? intval($_POST['template_id']) : null;
    $send_immediately = isset($_POST['send_immediately']) ? 1 : 0;
    $send_only_published = isset($_POST['send_only_published']) ? 1 : 0;
    $include_images = isset($_POST['include_images']) ? 1 : 0;
    
    // Validate custom emails if enabled
    if ($send_to_custom && !empty($custom_emails)) {
        $emails = explode(',', $custom_emails);
        $validEmails = [];
        foreach ($emails as $email) {
            $email = trim($email);
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $validEmails[] = $email;
            }
        }
        $custom_emails = implode(', ', $validEmails);
    }
    
    // Update or insert settings
    $query = "INSERT INTO email_automation_settings 
              (content_type, enabled, send_to_subscribers, send_to_members, send_to_custom, 
               custom_emails, template_id, send_immediately, send_only_published, include_images) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
              ON DUPLICATE KEY UPDATE 
              enabled = VALUES(enabled),
              send_to_subscribers = VALUES(send_to_subscribers),
              send_to_members = VALUES(send_to_members),
              send_to_custom = VALUES(send_to_custom),
              custom_emails = VALUES(custom_emails),
              template_id = VALUES(template_id),
              send_immediately = VALUES(send_immediately),
              send_only_published = VALUES(send_only_published),
              include_images = VALUES(include_images),
              updated_at = NOW()";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("siiisiiiii", $content_type, $enabled, $send_to_subscribers, $send_to_members, 
                      $send_to_custom, $custom_emails, $template_id, $send_immediately, 
                      $send_only_published, $include_images);
    
    if ($stmt->execute()) {
        header("Location: ../email_automation_settings.php?success=Settings saved successfully&type=" . $content_type);
    } else {
        header("Location: ../email_automation_settings.php?error=Failed to save settings: " . $conn->error);
    }
    
    $stmt->close();
    $conn->close();
} else {
    header("Location: ../email_automation_settings.php");
}
exit();

