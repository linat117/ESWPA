<?php
/**
 * Telegram Message Handler
 * Handles processing of chat messages from website to Telegram
 * 
 * Agent: agent_dev_1
 * Date: 2025-12-16
 */

// Include Telegram bot handler
require_once __DIR__ . '/telegram_bot.php';

/**
 * Process chat message from website
 * 
 * @param string $name User name
 * @param string $email User email
 * @param string $phone User phone
 * @param string $message User message
 * @param string $ip_address IP address
 * @param string $user_agent User agent
 * @return array Result array with success status and message
 */
function processChatMessage($name, $email, $phone, $message, $ip_address = null, $user_agent = null) {
    // Validate required fields
    if (empty($message)) {
        return [
            'success' => false,
            'message' => 'Message is required.'
        ];
    }
    
    // Sanitize inputs
    $name = $name ? trim(filter_var($name, FILTER_SANITIZE_STRING)) : '';
    $email = $email ? trim(filter_var($email, FILTER_SANITIZE_EMAIL)) : '';
    $phone = $phone ? trim(filter_var($phone, FILTER_SANITIZE_STRING)) : '';
    $message = trim(filter_var($message, FILTER_SANITIZE_STRING));
    
    // Validate email if provided
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return [
            'success' => false,
            'message' => 'Please enter a valid email address.'
        ];
    }
    
    // Get IP address if not provided
    if (!$ip_address) {
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
    }
    
    // Get user agent if not provided
    if (!$user_agent) {
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
    }
    
    // Check if Telegram is configured
    $settings = getTelegramSettings();
    if (!$settings) {
        return [
            'success' => false,
            'message' => 'Telegram bot is not configured. Please contact us via email instead.'
        ];
    }
    
    // Format message for Telegram
    $telegramMessage = formatWebsiteMessage($name, $email, $phone, $message);
    
    // Send to Telegram
    $result = sendTelegramMessage($telegramMessage, $settings['chat_id'], $settings['bot_token']);
    
    // Log to database if table exists (optional)
    if ($result['success']) {
        logTelegramMessage($name, $email, $phone, $message, $ip_address, $user_agent, $result['message_id'] ?? null);
    }
    
    return $result;
}

/**
 * Log message to database (optional)
 * 
 * @param string $name User name
 * @param string $email User email
 * @param string $phone User phone
 * @param string $message User message
 * @param string $ip_address IP address
 * @param string $user_agent User agent
 * @param string $telegram_message_id Telegram message ID
 * @return bool Success status
 */
function logTelegramMessage($name, $email, $phone, $message, $ip_address, $user_agent, $telegram_message_id = null) {
    global $conn;
    
    // Check if table exists
    $tableCheck = "SHOW TABLES LIKE 'telegram_messages'";
    $tableResult = mysqli_query($conn, $tableCheck);
    
    if (!$tableResult || mysqli_num_rows($tableResult) === 0) {
        // Table doesn't exist, skip logging
        return false;
    }
    
    // Insert message log
    $insertQuery = "INSERT INTO telegram_messages 
                    (user_name, user_email, user_phone, message, telegram_message_id, status, ip_address, user_agent) 
                    VALUES (?, ?, ?, ?, ?, 'sent', ?, ?)";
    
    $stmt = $conn->prepare($insertQuery);
    if (!$stmt) {
        error_log("Failed to prepare statement for telegram_messages: " . $conn->error);
        return false;
    }
    
    $stmt->bind_param("sssssss", $name, $email, $phone, $message, $telegram_message_id, $ip_address, $user_agent);
    
    if ($stmt->execute()) {
        $stmt->close();
        return true;
    } else {
        $error = $stmt->error;
        $stmt->close();
        error_log("Failed to log telegram message: " . $error);
        return false;
    }
}

