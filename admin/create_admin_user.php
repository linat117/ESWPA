<?php
/**
 * Create Admin User Script
 * 
 * This is a standalone utility script to create an admin user directly in the database.
 * 
 * USAGE:
 * 1. Access this file via browser: http://your-domain/admin/create_admin_user.php
 * 2. Fill in the form with username and password
 * 3. Submit to create the admin user
 * 4. DELETE THIS FILE after use for security
 * 
 * SECURITY NOTE:
 * - This script should be deleted immediately after creating the admin user
 * - It uses the same password hashing as the main auth system
 * - It checks for duplicate usernames before creating
 * 
 * Last Created: December 2025
 */

// Include database connection
include 'include/conn.php';

$message = '';
$message_type = ''; // 'success' or 'error'

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');
    
    // Validation
    if (empty($username) || empty($password)) {
        $message = 'Username and password are required.';
        $message_type = 'error';
    } elseif (strlen($username) < 3) {
        $message = 'Username must be at least 3 characters long.';
        $message_type = 'error';
    } elseif (strlen($password) < 6) {
        $message = 'Password must be at least 6 characters long.';
        $message_type = 'error';
    } elseif ($password !== $confirm_password) {
        $message = 'Passwords do not match.';
        $message_type = 'error';
    } else {
        // Check if username already exists
        $checkQuery = "SELECT id FROM user WHERE username = ?";
        $checkStmt = $conn->prepare($checkQuery);
        $checkStmt->bind_param("s", $username);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        
        if ($checkResult->num_rows > 0) {
            $message = 'Username already exists. Please choose another username.';
            $message_type = 'error';
            $checkStmt->close();
        } else {
            // Hash the password using the same method as auth.php
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert new admin user
            $insertQuery = "INSERT INTO user (username, password) VALUES (?, ?)";
            $insertStmt = $conn->prepare($insertQuery);
            $insertStmt->bind_param("ss", $username, $hashed_password);
            
            if ($insertStmt->execute()) {
                $new_user_id = $insertStmt->insert_id;
                $message = "Admin user created successfully!<br>Username: <strong>{$username}</strong><br>User ID: {$new_user_id}<br><br><strong style='color: red;'>IMPORTANT: Please delete this file (create_admin_user.php) immediately for security!</strong>";
                $message_type = 'success';
            } else {
                $message = 'Error creating user: ' . $conn->error;
                $message_type = 'error';
            }
            
            $insertStmt->close();
            $checkStmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Admin User</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            max-width: 450px;
            width: 100%;
            padding: 40px;
        }
        h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 24px;
        }
        .subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 14px;
        }
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 14px;
            line-height: 1.5;
        }
        .alert-success {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        .alert-error {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
            font-size: 14px;
        }
        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        input[type="text"]:focus,
        input[type="password"]:focus {
            outline: none;
            border-color: #667eea;
        }
        .btn {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .btn:active {
            transform: translateY(0);
        }
        .warning {
            background: #fff3cd;
            border: 1px solid #ffc107;
            color: #856404;
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
            font-size: 13px;
            line-height: 1.6;
        }
        .warning strong {
            display: block;
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Create Admin User</h1>
        <p class="subtitle">Add a new administrator to the system</p>
        
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required 
                       placeholder="Enter username" 
                       value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                       minlength="3">
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required 
                       placeholder="Enter password" 
                       minlength="6">
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required 
                       placeholder="Confirm password" 
                       minlength="6">
            </div>
            
            <button type="submit" class="btn">Create Admin User</button>
        </form>
        
        <div class="warning">
            <strong>⚠️ Security Warning:</strong>
            This is a temporary utility script. Please delete this file (create_admin_user.php) immediately after creating your admin user to prevent unauthorized access.
        </div>
    </div>
</body>
</html>
