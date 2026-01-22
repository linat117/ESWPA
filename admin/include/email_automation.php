<?php
/**
 * Email Automation Handler
 * Handles automated email sending when content is created/published
 * 
 * Agent: agent_dev_1
 * Date: 2025-12-22
 */

// Include database connection
require_once __DIR__ . '/conn.php';

// Include email handler
require_once __DIR__ . '/email_handler.php';

/**
 * Get automation settings for a content type
 * 
 * @param string $content_type Content type (news, blog, report, event, resource)
 * @return array|null Settings array or null if not found
 */
function getAutomationSettings($content_type) {
    global $conn;
    
    $query = "SELECT * FROM email_automation_settings WHERE content_type = ? LIMIT 1";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $content_type);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $settings = $result->fetch_assoc();
        $stmt->close();
        return $settings;
    }
    
    $stmt->close();
    return null;
}

/**
 * Get recipients based on automation settings
 * 
 * @param string $content_type Content type
 * @param array $settings Automation settings
 * @return array Array of email addresses with names
 */
function getRecipients($content_type, $settings) {
    global $conn;
    
    $recipients = [];
    
    // Get email subscribers if enabled
    if ($settings['send_to_subscribers'] == 1) {
        $subQuery = "SELECT email, name FROM email_subscribers WHERE status = 'active'";
        $subResult = mysqli_query($conn, $subQuery);
        if ($subResult) {
            while ($row = mysqli_fetch_assoc($subResult)) {
                $recipients[] = [
                    'email' => $row['email'],
                    'name' => $row['name'] ?? ''
                ];
            }
        }
    }
    
    // Get members if enabled
    if ($settings['send_to_members'] == 1) {
        // Check if registrations table has status and expiry_date fields
        $checkQuery = "SHOW COLUMNS FROM registrations LIKE 'status'";
        $checkResult = mysqli_query($conn, $checkQuery);
        $hasStatus = mysqli_num_rows($checkResult) > 0;
        
        $checkQuery2 = "SHOW COLUMNS FROM registrations LIKE 'expiry_date'";
        $checkResult2 = mysqli_query($conn, $checkQuery2);
        $hasExpiry = mysqli_num_rows($checkResult2) > 0;
        
        if ($hasStatus && $hasExpiry) {
            $memberQuery = "SELECT DISTINCT email, fullname as name FROM registrations 
                           WHERE email IS NOT NULL AND email != '' 
                           AND status = 'active' AND expiry_date >= CURDATE()";
        } else {
            // Fallback if fields don't exist
            $memberQuery = "SELECT DISTINCT email, fullname as name FROM registrations 
                           WHERE email IS NOT NULL AND email != ''";
        }
        
        $memberResult = mysqli_query($conn, $memberQuery);
        if ($memberResult) {
            while ($row = mysqli_fetch_assoc($memberResult)) {
                $recipients[] = [
                    'email' => $row['email'],
                    'name' => $row['name'] ?? ''
                ];
            }
        }
    }
    
    // Get custom emails if enabled
    if ($settings['send_to_custom'] == 1 && !empty($settings['custom_emails'])) {
        $customEmails = explode(',', $settings['custom_emails']);
        foreach ($customEmails as $email) {
            $email = trim($email);
            if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $recipients[] = [
                    'email' => $email,
                    'name' => ''
                ];
            }
        }
    }
    
    // Remove duplicates based on email
    $uniqueRecipients = [];
    $seenEmails = [];
    foreach ($recipients as $recipient) {
        $email = strtolower(trim($recipient['email']));
        if (!in_array($email, $seenEmails)) {
            $seenEmails[] = $email;
            $uniqueRecipients[] = $recipient;
        }
    }
    
    return $uniqueRecipients;
}

/**
 * Generate email content from template
 * 
 * @param string $content_type Content type
 * @param array $content_data Content data (title, content, author, date, link, images, etc.)
 * @param int|null $template_id Template ID (optional, will use default if not provided)
 * @return array Array with 'subject' and 'body'
 */
function generateEmailContent($content_type, $content_data, $template_id = null) {
    global $conn;
    
    // Get template
    if ($template_id) {
        $templateQuery = "SELECT * FROM email_templates WHERE id = ? AND is_active = 1 LIMIT 1";
        $templateStmt = $conn->prepare($templateQuery);
        $templateStmt->bind_param("i", $template_id);
        $templateStmt->execute();
        $templateResult = $templateStmt->get_result();
        $template = $templateResult->fetch_assoc();
        $templateStmt->close();
    }
    
    // If no template found or no template_id, get default template for content type
    if (empty($template)) {
        $defaultQuery = "SELECT * FROM email_templates WHERE content_type = ? AND is_active = 1 LIMIT 1";
        $defaultStmt = $conn->prepare($defaultQuery);
        $defaultStmt->bind_param("s", $content_type);
        $defaultStmt->execute();
        $defaultResult = $defaultStmt->get_result();
        $template = $defaultResult->fetch_assoc();
        $defaultStmt->close();
    }
    
    // If still no template, use basic default
    if (empty($template)) {
        $subject = "New " . ucfirst($content_type) . ": " . ($content_data['title'] ?? '');
        $body = "<h1>" . htmlspecialchars($content_data['title'] ?? '') . "</h1>";
        $body .= "<p>" . nl2br(htmlspecialchars($content_data['content'] ?? '')) . "</p>";
        if (!empty($content_data['link'])) {
            $body .= "<p><a href=\"" . htmlspecialchars($content_data['link']) . "\">Read more</a></p>";
        }
        return ['subject' => $subject, 'body' => $body];
    }
    
    // Prepare variables
    $title = htmlspecialchars($content_data['title'] ?? '');
    $content = $content_data['content'] ?? '';
    $excerpt = strip_tags($content);
    if (strlen($excerpt) > 300) {
        $excerpt = substr($excerpt, 0, 300) . '...';
    }
    $author = htmlspecialchars($content_data['author'] ?? 'Admin');
    $date = htmlspecialchars($content_data['date'] ?? date('Y-m-d'));
    $link = htmlspecialchars($content_data['link'] ?? '');
    $type = ucfirst($content_type);
    
    // Generate image HTML
    $imageHtml = '';
    if (!empty($content_data['images']) && is_array($content_data['images']) && !empty($content_data['images'][0])) {
        $firstImage = $content_data['images'][0];
        $imageUrl = (strpos($firstImage, 'http') === 0) ? $firstImage : 
                    ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . 
                     "://" . $_SERVER['HTTP_HOST'] . "/" . ltrim($firstImage, '/'));
        $imageHtml = '<img src="' . htmlspecialchars($imageUrl) . '" alt="' . htmlspecialchars($title) . '" style="max-width: 100%; height: auto; margin: 20px 0;">';
    }
    
    // Generate unsubscribe link (for subscribers)
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
    $host = $_SERVER['HTTP_HOST'];
    $unsubscribeLink = $protocol . "://" . $host . "/unsubscribe.php";
    
    // Replace template variables
    $subject = str_replace(
        ['{TITLE}', '{AUTHOR}', '{DATE}', '{TYPE}'],
        [$title, $author, $date, $type],
        $template['subject']
    );
    
    $body = str_replace(
        ['{TITLE}', '{CONTENT}', '{EXCERPT}', '{AUTHOR}', '{DATE}', '{LINK}', '{TYPE}', '{IMAGE}', '{UNSUBSCRIBE_LINK}'],
        [$title, nl2br($content), $excerpt, $author, $date, $link, $type, $imageHtml, $unsubscribeLink],
        $template['body']
    );
    
    return ['subject' => $subject, 'body' => $body];
}

/**
 * Send automated email
 * 
 * @param string $content_type Content type
 * @param int $content_id Content ID
 * @param array $content_data Content data
 * @return array Result array with success status and counts
 */
function sendAutomatedEmail($content_type, $content_id, $content_data) {
    global $conn;
    
    // Get automation settings
    $settings = getAutomationSettings($content_type);
    
    if (!$settings || $settings['enabled'] != 1) {
        return [
            'success' => false,
            'message' => 'Automation is not enabled for this content type.'
        ];
    }
    
    // Check if should only send for published content
    if ($settings['send_only_published'] == 1) {
        if (isset($content_data['status']) && $content_data['status'] !== 'published') {
            return [
                'success' => false,
                'message' => 'Content is not published. Automation only sends for published content.'
            ];
        }
    }
    
    // Get recipients
    $recipients = getRecipients($content_type, $settings);
    
    if (empty($recipients)) {
        return [
            'success' => false,
            'message' => 'No recipients found.'
        ];
    }
    
    // Generate email content
    $emailContent = generateEmailContent($content_type, $content_data, $settings['template_id']);
    
    // Get current user ID (if in session)
    $sent_by = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    
    // Send emails
    $sentCount = 0;
    $failedCount = 0;
    $errors = [];
    
    // Extract email addresses for sending
    $emailAddresses = array_column($recipients, 'email');
    
    // Send using existing newsletter function
    $success = sendNewsletter($emailContent['subject'], $emailContent['body'], $emailAddresses);
    
    if ($success) {
        $sentCount = count($emailAddresses);
        $status = 'success';
    } else {
        $failedCount = count($emailAddresses);
        $status = 'failed';
        $errors[] = 'Failed to send newsletter';
    }
    
    // Log automation
    logAutomation(
        $content_type,
        $content_id,
        $content_data['title'] ?? 'Untitled',
        count($recipients),
        $sentCount,
        $failedCount,
        $status,
        $sent_by,
        !empty($errors) ? implode('; ', $errors) : null
    );
    
    return [
        'success' => $success,
        'sent_count' => $sentCount,
        'failed_count' => $failedCount,
        'recipients_count' => count($recipients),
        'message' => $success ? "Email sent to {$sentCount} recipients." : "Failed to send email."
    ];
}

/**
 * Log automation attempt
 * 
 * @param string $content_type Content type
 * @param int $content_id Content ID
 * @param string $title Content title
 * @param int $recipients_count Number of recipients
 * @param int $sent_count Number of emails sent
 * @param int $failed_count Number of emails failed
 * @param string $status Status (success, failed, partial)
 * @param int|null $sent_by User ID who triggered
 * @param string|null $error_message Error message if any
 * @return bool Success status
 */
function logAutomation($content_type, $content_id, $title, $recipients_count, $sent_count, $failed_count, $status, $sent_by = null, $error_message = null) {
    global $conn;
    
    $query = "INSERT INTO email_automation_logs 
              (content_type, content_id, content_title, recipients_count, sent_count, failed_count, status, sent_by, error_message) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        error_log("Failed to prepare automation log statement: " . $conn->error);
        return false;
    }
    
    $stmt->bind_param("sisiiisss", $content_type, $content_id, $title, $recipients_count, $sent_count, $failed_count, $status, $sent_by, $error_message);
    
    if ($stmt->execute()) {
        $stmt->close();
        return true;
    } else {
        $error = $stmt->error;
        $stmt->close();
        error_log("Failed to log automation: " . $error);
        return false;
    }
}

/**
 * Format email template with variables
 * 
 * @param string $template Template string with variables
 * @param array $variables Array of variable => value pairs
 * @return string Formatted template
 */
function formatEmailTemplate($template, $variables) {
    $formatted = $template;
    foreach ($variables as $key => $value) {
        $formatted = str_replace('{' . strtoupper($key) . '}', $value, $formatted);
    }
    return $formatted;
}

