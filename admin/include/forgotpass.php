<?php
session_start();
include 'conn.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == "reset_password") {
    $username = trim($_POST['username']);
    $new_password = trim($_POST['new_password']);
    $confirm_password = trim($_POST['confirm_password']);

    if (empty($username) || empty($new_password) || empty($confirm_password)) {
        echo "<script>alert('All fields are required!'); window.location.href = '../auth-forgotpw.php';</script>";
        exit();
    }

    if ($new_password !== $confirm_password) {
        echo "<script>alert('Passwords do not match!'); window.location.href = '../auth-forgotpw.php';</script>";
        exit();
    }

    // Hash the new password
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

    // Check if the user exists
    $stmt = $conn->prepare("SELECT * FROM user WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Update the password
        $update_stmt = $conn->prepare("UPDATE user SET password = ? WHERE username = ?");
        $update_stmt->bind_param("ss", $hashed_password, $username);
        if ($update_stmt->execute()) {
            echo "<script>alert('Password changed successfully!'); window.location.href = '../auth-login.php';</script>";
        } else {
            echo "<script>alert('Error updating password. Please try again.'); window.location.href = '../auth-forgotpw.php';</script>";
        }
    } else {
        echo "<script>alert('Username not found!'); window.location.href = '../auth-forgotpw.php';</script>";
    }

    $stmt->close();
    $update_stmt->close();
    $conn->close();
} else {
    header("Location: ../auth-forgotpw.php");
    exit();
}
?>
