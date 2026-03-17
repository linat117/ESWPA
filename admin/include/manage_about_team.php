<?php
session_start();
include 'conn.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth-login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    // Ensure table exists (safe if run multiple times)
    $createSql = "
        CREATE TABLE IF NOT EXISTS about_team_members (
            id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            role VARCHAR(255) NULL,
            bio TEXT NULL,
            photo VARCHAR(255) NULL,
            sort_order INT(11) DEFAULT 0,
            status ENUM('active','inactive') NOT NULL DEFAULT 'active',
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";
    $conn->query($createSql);

    if ($action === 'create') {
        $name  = trim($_POST['name'] ?? '');
        $role  = trim($_POST['role'] ?? '');
        $bio   = trim($_POST['bio'] ?? '');
        $order = intval($_POST['sort_order'] ?? 0);
        $status = $_POST['status'] === 'inactive' ? 'inactive' : 'active';

        if ($name === '') {
            header('Location: ../about_team.php?error=Please provide a name');
            exit();
        }

        $photoPath = null;
        if (!empty($_FILES['photo']['name'])) {
            $uploadDir = '../../uploads/team/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
            $maxSize = 2 * 1024 * 1024; // 2MB

            if ($_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                $fileType = $_FILES['photo']['type'];
                $fileSize = $_FILES['photo']['size'];

                if (in_array($fileType, $allowedTypes) && $fileSize <= $maxSize) {
                    $fileName = time() . '_' . uniqid() . '_' . basename($_FILES['photo']['name']);
                    $filePath = $uploadDir . $fileName;

                    if (move_uploaded_file($_FILES['photo']['tmp_name'], $filePath)) {
                        $photoPath = 'uploads/team/' . $fileName;
                    }
                }
            }
        }

        $stmt = $conn->prepare("
            INSERT INTO about_team_members (name, role, bio, photo, sort_order, status)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param('ssssis', $name, $role, $bio, $photoPath, $order, $status);

        if ($stmt->execute()) {
            $stmt->close();
            header('Location: ../about_team.php?success=Team member added');
            exit();
        } else {
            $error = 'Failed to add team member: ' . $conn->error;
            $stmt->close();
            header('Location: ../about_team.php?error=' . urlencode($error));
            exit();
        }
    }

    if ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        if ($id <= 0) {
            header('Location: ../about_team.php?error=Invalid ID');
            exit();
        }

        $stmt = $conn->prepare('DELETE FROM about_team_members WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();

        header('Location: ../about_team.php?success=Team member deleted');
        exit();
    }

    if ($action === 'update_order') {
        if (!empty($_POST['order']) && is_array($_POST['order'])) {
            foreach ($_POST['order'] as $id => $order) {
                $id = intval($id);
                $order = intval($order);
                $stmt = $conn->prepare('UPDATE about_team_members SET sort_order = ? WHERE id = ?');
                $stmt->bind_param('ii', $order, $id);
                $stmt->execute();
                $stmt->close();
            }
        }

        header('Location: ../about_team.php?success=Order updated');
        exit();
    }
}

header('Location: ../about_team.php');
exit();

