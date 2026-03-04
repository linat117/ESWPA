<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth-login.php");
    exit();
}

$user_id = intval($_GET['id'] ?? 0);
if ($user_id <= 0) {
    header("Location: ../settings_users.php?error=" . urlencode('Invalid user ID'));
    exit();
}

if ($user_id == $_SESSION['user_id']) {
    header("Location: ../settings_users.php?error=" . urlencode('You cannot delete your own account'));
    exit();
}

require_once __DIR__ . '/conn.php';

$delRole = $conn->prepare("DELETE FROM user_roles WHERE user_id = ?");
$delRole->bind_param("i", $user_id);
$delRole->execute();
$delRole->close();

$delUser = $conn->prepare("DELETE FROM user WHERE id = ?");
$delUser->bind_param("i", $user_id);
$delUser->execute();
if ($delUser->affected_rows === 0) {
    header("Location: ../settings_users.php?error=" . urlencode('User not found or could not be deleted'));
    exit();
}
$delUser->close();

header("Location: ../settings_users.php?success=User deleted successfully");
exit();
