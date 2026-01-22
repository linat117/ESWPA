<?php
/**
 * Telegram Bot Handler
 * Handles Telegram Bot API integration for sending messages
 * 
 * Agent: agent_dev_1
 * Date: 2025-12-16
 */

// Include database connection
if (!isset($conn)) {
    include __DIR__ . '/config.php';
}

/**
 * Get Telegram settings from database
 * 
 * @return array Array with bot_token and chat_id, or null if not configured
 */
function getTelegramSettings() {
    global $conn;
    
    $settings = [];
    
    // Get bot token
    $tokenQuery = "SELECT setting_value FROM settings WHERE setting_key = 'telegram_bot_token' LIMIT 1";
    $tokenResult = mysqli_query($conn, $tokenQuery);
    if ($tokenResult && mysqli_num_rows($tokenResult) > 0) {
        $settings['bot_token'] = mysqli_fetch_assoc($tokenResult)['setting_value'];
    } else {
        $settings['bot_token'] = '';
    }
    
    // Get chat ID
    $chatQuery = "SELECT setting_value FROM settings WHERE setting_key = 'telegram_chat_id' LIMIT 1";
    $chatResult = mysqli_query($conn, $chatQuery);
    if ($chatResult && mysqli_num_rows($chatResult) > 0) {
        $settings['chat_id'] = mysqli_fetch_assoc($chatResult)['setting_value'];
    } else {
        $settings['chat_id'] = '';
    }
    
    // Check if both are configured
    if (empty($settings['bot_token']) || empty($settings['chat_id'])) {
        return null;
    }
    
    return $settings;
}

/**
 * Format website message for Telegram
 * 
 * @param string $name User name
 * @param string $email User email
 * @param string $phone User phone
 * @param string $message User message
 * @return string Formatted message
 */
function formatWebsiteMessage($name, $email, $phone, $message) {
    $timestamp = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    
    $formatted = "📩 <b>New Message from Website</b>\n\n";
    
    if (!empty($name)) {
        $formatted .= "👤 <b>Name:</b> " . htmlspecialchars($name) . "\n";
    }
    
    if (!empty($email)) {
        $formatted .= "📧 <b>Email:</b> " . htmlspecialchars($email) . "\n";
    }
    
    if (!empty($phone)) {
        $formatted .= "📱 <b>Phone:</b> " . htmlspecialchars($phone) . "\n";
    }
    
    $formatted .= "\n💬 <b>Message:</b>\n" . htmlspecialchars($message) . "\n\n";
    $formatted .= "🕐 <i>Time:</i> {$timestamp}\n";
    $formatted .= "🌐 <i>IP:</i> {$ip}";
    
    return $formatted;
}

/**
 * Send message to Telegram
 * 
 * @param string $message Message to send
 * @param string $chat_id Telegram chat ID (optional, will use settings if not provided)
 * @param string $bot_token Telegram bot token (optional, will use settings if not provided)
 * @return array Result array with success status and message
 */
function sendTelegramMessage($message, $chat_id = null, $bot_token = null) {
    // Get settings if not provided
    if (empty($chat_id) || empty($bot_token)) {
        $settings = getTelegramSettings();
        if (!$settings) {
            return [
                'success' => false,
                'message' => 'Telegram bot is not configured. Please configure bot token and chat ID in admin settings.'
            ];
        }
        $bot_token = $settings['bot_token'];
        $chat_id = $settings['chat_id'];
    }
    
    // Validate inputs
    if (empty($message)) {
        return [
            'success' => false,
            'message' => 'Message cannot be empty.'
        ];
    }
    
    // Telegram Bot API endpoint
    $url = "https://api.telegram.org/bot{$bot_token}/sendMessage";
    
    // Prepare data
    $data = [
        'chat_id' => $chat_id,
        'text' => $message,
        'parse_mode' => 'HTML',
        'disable_web_page_preview' => true
    ];
    
    // Initialize cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    // Execute request
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    // Check for cURL errors
    if (!empty($curlError)) {
        error_log("Telegram API cURL error: {$curlError}");
        return [
            'success' => false,
            'message' => 'Failed to connect to Telegram API. Please try again later.'
        ];
    }
    
    // Check HTTP response code
    if ($httpCode !== 200) {
        error_log("Telegram API HTTP error: {$httpCode} - Response: {$response}");
        return [
            'success' => false,
            'message' => 'Telegram API returned an error. Please check bot token and chat ID.'
        ];
    }
    
    // Parse response
    $result = json_decode($response, true);
    
    if (!$result || !isset($result['ok'])) {
        error_log("Telegram API invalid response: {$response}");
        return [
            'success' => false,
            'message' => 'Invalid response from Telegram API.'
        ];
    }
    
    if ($result['ok'] === true) {
        // Log successful send
        error_log("Telegram message sent successfully to chat ID: {$chat_id}");
        return [
            'success' => true,
            'message' => 'Message sent successfully!',
            'message_id' => $result['result']['message_id'] ?? null
        ];
    } else {
        $errorDescription = $result['description'] ?? 'Unknown error';
        error_log("Telegram API error: {$errorDescription}");
        return [
            'success' => false,
            'message' => 'Failed to send message: ' . $errorDescription
        ];
    }
}

/**
 * Test Telegram bot connection
 * 
 * @return array Result array with success status and message
 */
function testTelegramBot() {
    $settings = getTelegramSettings();
    
    if (!$settings) {
        return [
            'success' => false,
            'message' => 'Telegram bot is not configured. Please configure bot token and chat ID.'
        ];
    }
    
    $testMessage = "🧪 <b>Test Message</b>\n\nThis is a test message from your website. If you receive this, your Telegram bot is configured correctly! ✅\n\nTime: " . date('Y-m-d H:i:s');
    
    return sendTelegramMessage($testMessage, $settings['chat_id'], $settings['bot_token']);
}

