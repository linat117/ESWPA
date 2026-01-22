<?php
session_start();
include 'conn.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth-login.php");
    exit();
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $version = $_POST['version'];
    $change_date = $_POST['change_date'];
    $type = $_POST['type'];
    $title = $_POST['title'];
    $description = $_POST['description'];

    if (empty($version) || empty($change_date) || empty($type) || empty($title) || empty($description)) {
        $error = "All fields are required.";
    } else {
        $stmt = $conn->prepare("INSERT INTO changelogs (version, change_date, type, title, description) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $version, $change_date, $type, $title, $description);

        if ($stmt->execute()) {
            $message = "Changelog entry added successfully!";
        } else {
            $error = "Error: " . $stmt->error;
        }
        $stmt->close();
    }
    $conn->close();
}
// This is a backend script, so we redirect back to the form page with a status message.
if (!empty($message)) {
    header("Location: ../add_changelog.php?status=success&msg=" . urlencode($message));
} else {
    header("Location: ../add_changelog.php?status=error&msg=" . urlencode($error));
}
exit();
?> 