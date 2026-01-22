<?php
/**
 * Email Subscription AJAX Endpoint
 * Handles AJAX requests for email subscription
 * 
 * Agent: agent_dev_1
 * Date: 2025-12-16
 */

header('Content-Type: application/json');

// Include subscription handler
require_once 'subscribe_handler.php';

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

// Get and validate email
$email = trim($input['email'] ?? '');
$name = trim($input['name'] ?? '');
$source = trim($input['source'] ?? 'popup');

// Honeypot field (spam protection)
$website = trim($input['website'] ?? '');
if (!empty($website)) {
    // This is likely a bot, silently fail
    echo json_encode([
        'success' => true,
        'message' => 'Thank you for subscribing!'
    ]);
    exit;
}

// Validate required fields
if (empty($email)) {
    echo json_encode([
        'success' => false,
        'message' => 'Email address is required.'
    ]);
    exit;
}

// Get IP address and user agent
$ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;

// Process subscription
$result = processSubscription($email, $name, $source, $ip_address, $user_agent);

// Return JSON response
echo json_encode($result);

