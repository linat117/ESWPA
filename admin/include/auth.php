<?php
session_start();
include 'conn.php'; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    if (!empty($username) && !empty($password)) {
        
        if (isset($_POST['action']) && $_POST['action'] == 'signup') {
            // SIGN-UP LOGIC
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Check if the username exists
            $query = "SELECT * FROM user WHERE username = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows == 0) {
                // Insert new user
                $insertQuery = "INSERT INTO user (username, password) VALUES (?, ?)";
                $stmt = $conn->prepare($insertQuery);
                $stmt->bind_param("ss", $username, $hashed_password);

                if ($stmt->execute()) {
                    $_SESSION['user_id'] = $stmt->insert_id;
                    $_SESSION['username'] = $username;
                    header("Location: auth-login.php");
                    exit();
                } else {
                    echo "<script>alert('Error: Unable to register.'); window.location='auth-register.php';</script>";
                    exit();
                }
            } else {
                echo "<script>alert('Username already exists. Choose another.'); window.location='auth-register.php';</script>";
                exit();
            }

        } elseif (isset($_POST['action']) && $_POST['action'] == 'login') {
            // LOGIN LOGIC
            $query = "SELECT id, username, password FROM user WHERE username = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows == 1) {
                $user = $result->fetch_assoc();

                if (password_verify($password, $user['password'])) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    header("Location: index.php");
                    exit();
                } else {
                    echo "<script>alert('Incorrect password.'); window.location='auth-login.php';</script>";
                    exit();
                }
            } else {
                echo "<script>alert('User not found. Please sign up.'); window.location='auth-register.php';</script>";
                exit();
            }
        }
    } else {
        echo "<script>alert('All fields are required.'); window.location='auth-register.php';</script>";
        exit();
    }
} else {
    header("Location: auth-login.php");
    exit();
}
?>
