<?php
session_start();
include 'conn.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth-login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $name = trim($_POST['name'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $body = trim($_POST['body'] ?? '');
    $content_type = $_POST['content_type'] ?? 'general';
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    if (empty($name) || empty($subject) || empty($body)) {
        header("Location: ../email_templates.php?error=Please fill in all required fields");
        exit();
    }
    
    if ($action === 'create') {
        $query = "INSERT INTO email_templates (name, subject, body, content_type, is_active) 
                  VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssssi", $name, $subject, $body, $content_type, $is_active);
        
        if ($stmt->execute()) {
            header("Location: ../email_templates.php?success=Template created successfully");
        } else {
            header("Location: ../add_email_template.php?error=Failed to create template: " . $conn->error);
        }
        $stmt->close();
    } elseif ($action === 'update') {
        $id = intval($_POST['id'] ?? 0);
        if ($id <= 0) {
            header("Location: ../email_templates.php?error=Invalid template ID");
            exit();
        }
        
        $query = "UPDATE email_templates SET name = ?, subject = ?, body = ?, content_type = ?, is_active = ? WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssssii", $name, $subject, $body, $content_type, $is_active, $id);
        
        if ($stmt->execute()) {
            header("Location: ../email_templates.php?success=Template updated successfully");
        } else {
            header("Location: ../edit_email_template.php?id=$id&error=Failed to update template: " . $conn->error);
        }
        $stmt->close();
    }
    
    $conn->close();
} else {
    header("Location: ../email_templates.php");
}
exit();

