<?php
/**
 * Test Telegram Bot Endpoint
 * Tests Telegram bot configuration and sends a test message
 * 
 * Agent: agent_dev_1
 * Date: 2025-12-16
 */

session_start();

// Check if admin is logged in
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access.'
    ]);
    exit;
}

header('Content-Type: application/json');

// Include Telegram bot handler
require_once __DIR__ . '/telegram_bot.php';

// Test Telegram bot
$result = testTelegramBot();

// Return JSON response
echo json_encode($result);

