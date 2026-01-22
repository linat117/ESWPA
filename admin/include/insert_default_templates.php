<?php
/**
 * Insert Default Email Templates
 * Run this script once to insert default templates
 */

require_once __DIR__ . '/conn.php';

$templates = [
    [
        'name' => 'News Template',
        'subject' => 'New News: {TITLE}',
        'body' => '<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"></head><body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;"><div style="max-width: 600px; margin: 0 auto; padding: 20px;"><h1 style="color: #667eea;">{TITLE}</h1><p style="color: #666; font-size: 14px;">Published: {DATE} | By: {AUTHOR}</p><div style="margin: 20px 0;">{IMAGE}{CONTENT}</div><a href="{LINK}" style="display: inline-block; background: #667eea; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">Read More</a><hr style="margin: 30px 0; border: none; border-top: 1px solid #eee;"><p style="font-size: 12px; color: #999; text-align: center;">You received this email because you subscribed to our newsletter.<br><a href="{UNSUBSCRIBE_LINK}">Unsubscribe</a></p></div></body></html>',
        'content_type' => 'news'
    ],
    [
        'name' => 'Blog Template',
        'subject' => 'New Blog Post: {TITLE}',
        'body' => '<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"></head><body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;"><div style="max-width: 600px; margin: 0 auto; padding: 20px;"><h1 style="color: #667eea;">{TITLE}</h1><p style="color: #666; font-size: 14px;">Published: {DATE} | By: {AUTHOR}</p><div style="margin: 20px 0;">{IMAGE}{CONTENT}</div><a href="{LINK}" style="display: inline-block; background: #667eea; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">Read More</a><hr style="margin: 30px 0; border: none; border-top: 1px solid #eee;"><p style="font-size: 12px; color: #999; text-align: center;">You received this email because you subscribed to our newsletter.<br><a href="{UNSUBSCRIBE_LINK}">Unsubscribe</a></p></div></body></html>',
        'content_type' => 'blog'
    ],
    [
        'name' => 'Report Template',
        'subject' => 'New Report: {TITLE}',
        'body' => '<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"></head><body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;"><div style="max-width: 600px; margin: 0 auto; padding: 20px;"><h1 style="color: #667eea;">{TITLE}</h1><p style="color: #666; font-size: 14px;">Published: {DATE} | By: {AUTHOR}</p><div style="margin: 20px 0;">{IMAGE}{CONTENT}</div><a href="{LINK}" style="display: inline-block; background: #667eea; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">Read More</a><hr style="margin: 30px 0; border: none; border-top: 1px solid #eee;"><p style="font-size: 12px; color: #999; text-align: center;">You received this email because you subscribed to our newsletter.<br><a href="{UNSUBSCRIBE_LINK}">Unsubscribe</a></p></div></body></html>',
        'content_type' => 'report'
    ],
    [
        'name' => 'Event Template',
        'subject' => 'New Event: {TITLE}',
        'body' => '<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"></head><body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;"><div style="max-width: 600px; margin: 0 auto; padding: 20px;"><h1 style="color: #667eea;">{TITLE}</h1><p style="color: #666; font-size: 14px;">Event Date: {DATE}</p><div style="margin: 20px 0;">{IMAGE}{CONTENT}</div><a href="{LINK}" style="display: inline-block; background: #667eea; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">View Details</a><hr style="margin: 30px 0; border: none; border-top: 1px solid #eee;"><p style="font-size: 12px; color: #999; text-align: center;">You received this email because you subscribed to our newsletter.<br><a href="{UNSUBSCRIBE_LINK}">Unsubscribe</a></p></div></body></html>',
        'content_type' => 'event'
    ],
    [
        'name' => 'Resource Template',
        'subject' => 'New Resource Available: {TITLE}',
        'body' => '<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"></head><body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;"><div style="max-width: 600px; margin: 0 auto; padding: 20px;"><h1 style="color: #667eea;">{TITLE}</h1><p style="color: #666; font-size: 14px;">Published: {DATE} | By: {AUTHOR}</p><div style="margin: 20px 0;">{CONTENT}</div><a href="{LINK}" style="display: inline-block; background: #667eea; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">Download</a><hr style="margin: 30px 0; border: none; border-top: 1px solid #eee;"><p style="font-size: 12px; color: #999; text-align: center;">You received this email because you subscribed to our newsletter.<br><a href="{UNSUBSCRIBE_LINK}">Unsubscribe</a></p></div></body></html>',
        'content_type' => 'resource'
    ]
];

$inserted = 0;
$skipped = 0;

foreach ($templates as $template) {
    // Check if template already exists
    $checkQuery = "SELECT id FROM email_templates WHERE name = ? AND content_type = ? LIMIT 1";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->bind_param("ss", $template['name'], $template['content_type']);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows > 0) {
        $skipped++;
        $checkStmt->close();
        continue;
    }
    $checkStmt->close();
    
    // Insert template
    $insertQuery = "INSERT INTO email_templates (name, subject, body, content_type, is_active) VALUES (?, ?, ?, ?, 1)";
    $insertStmt = $conn->prepare($insertQuery);
    $insertStmt->bind_param("ssss", $template['name'], $template['subject'], $template['body'], $template['content_type']);
    
    if ($insertStmt->execute()) {
        $inserted++;
    }
    $insertStmt->close();
}

echo "Default templates inserted: $inserted\n";
echo "Templates skipped (already exist): $skipped\n";

$conn->close();

