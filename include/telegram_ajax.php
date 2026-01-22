<?php
/**
 * Telegram Chat AJAX Endpoint
 * Handles AJAX requests for sending messages via Telegram
 * 
 * Agent: agent_dev_1
 * Date: 2025-12-16
 */

header('Content-Type: application/json');

// Include Telegram handler
require_once __DIR__ . '/telegram_handler.php';

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method.'
    ]);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// If JSON decode failed, try POST data
if (!$input) {
    $input = $_POST;
}

// Get and validate inputs
$name = trim($input['name'] ?? '');
$email = trim($input['email'] ?? '');
$phone = trim($input['phone'] ?? '');
$message = trim($input['message'] ?? '');

// Honeypot field (spam protection)
$website = trim($input['website'] ?? '');
if (!empty($website)) {
    // This is likely a bot, silently fail
    echo json_encode([
        'success' => true,
        'message' => 'Message sent successfully!'
    ]);
    exit;
}

// Validate required fields
if (empty($message)) {
    echo json_encode([
        'success' => false,
        'message' => 'Message is required.'
    ]);
    exit;
}

// Get IP address and user agent
$ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;

// Process chat message
$result = processChatMessage($name, $email, $phone, $message, $ip_address, $user_agent);

// Return JSON response
echo json_encode($result);

