<?php
session_start();
include 'conn.php';
include 'email_handler.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth-login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit'])) {
    $recipients = $_POST['recipients'];
    $subject = $_POST['subject'];
    $body = $_POST['email_body'];
    $attachment = isset($_FILES['attachment']) ? $_FILES['attachment'] : null;

    if (empty($recipients) || empty($subject) || empty($body)) {
        $_SESSION['toast_message'] = "Please fill in all required fields.";
        header("Location: ../send_email.php");
        exit();
    }

    if (sendBulkEmail($subject, $body, $recipients, $attachment)) {
        // Store the email in the database
        $recipients_str = implode(', ', $recipients);
        $attachment_name = $attachment ? $attachment['name'] : null;

        $stmt = $conn->prepare("INSERT INTO sent_emails (recipients, subject, body, attachment) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $recipients_str, $subject, $body, $attachment_name);
        $stmt->execute();
        
        $_SESSION['toast_message'] = "Email sent successfully!";
    } else {
        $_SESSION['toast_message'] = "Failed to send email.";
    }

    header("Location: ../send_email.php");
    exit();
} else {
    header("Location: ../send_email.php");
    exit();
}
?> 