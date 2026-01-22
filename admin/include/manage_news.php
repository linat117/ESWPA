<?php
session_start();
include 'conn.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth-login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    $action = $_POST['action'];
    $type = $_POST['type'] ?? 'news';
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $author = trim($_POST['author'] ?? $_SESSION['username']);
    $published_date = $_POST['published_date'] ?? date('Y-m-d');
    $status = $_POST['status'] ?? 'draft';

    if (empty($title) || empty($content)) {
        header("Location: ../add_news.php?error=Please fill in title and content");
        exit();
    }

    // Handle image uploads
    $uploadedImages = [];
    if (!empty($_FILES['images']['name'][0])) {
        $uploadDir = '../../uploads/news/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
        $maxSize = 2 * 1024 * 1024; // 2MB

        foreach ($_FILES['images']['tmp_name'] as $key => $tmpName) {
            if ($_FILES['images']['error'][$key] === UPLOAD_ERR_OK) {
                $fileType = $_FILES['images']['type'][$key];
                $fileSize = $_FILES['images']['size'][$key];

                if (in_array($fileType, $allowedTypes) && $fileSize <= $maxSize) {
                    $fileName = time() . '_' . uniqid() . '_' . basename($_FILES['images']['name'][$key]);
                    $filePath = $uploadDir . $fileName;

                    if (move_uploaded_file($tmpName, $filePath)) {
                        $uploadedImages[] = 'uploads/news/' . $fileName;
                    }
                }
            }
        }
    }

    $imagesJson = !empty($uploadedImages) ? json_encode($uploadedImages) : null;

    if ($action === 'create') {
        // Insert new post
        $query = "INSERT INTO news_media (type, title, content, images, author, published_date, status) 
                  VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sssssss", $type, $title, $content, $imagesJson, $author, $published_date, $status);

        if ($stmt->execute()) {
            $new_post_id = $stmt->insert_id;
            $stmt->close();
            
            // Trigger email automation if status is published
            if ($status === 'published') {
                require_once __DIR__ . '/email_automation.php';
                
                // Generate content link
                $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
                $host = $_SERVER['HTTP_HOST'];
                $content_link = $protocol . "://" . $host . "/news-detail.php?id=" . $new_post_id;
                
                // Parse images
                $images = [];
                if ($imagesJson) {
                    $images = json_decode($imagesJson, true) ?? [];
                }
                
                // Send automated email
                sendAutomatedEmail($type, $new_post_id, [
                    'title' => $title,
                    'content' => $content,
                    'author' => $author,
                    'date' => $published_date,
                    'status' => $status,
                    'images' => $images,
                    'link' => $content_link
                ]);
            }
            
            header("Location: ../news_list.php?success=Post created successfully");
            exit();
        } else {
            header("Location: ../add_news.php?error=Failed to create post: " . $conn->error);
            exit();
        }
    } elseif ($action === 'update') {
        $id = intval($_POST['id'] ?? 0);
        if ($id <= 0) {
            header("Location: ../news_list.php?error=Invalid post ID");
            exit();
        }

        // Update existing post
        if ($imagesJson) {
            // Get existing images
            $getQuery = "SELECT images FROM news_media WHERE id = ?";
            $getStmt = $conn->prepare($getQuery);
            $getStmt->bind_param("i", $id);
            $getStmt->execute();
            $getResult = $getStmt->get_result();
            $existing = $getResult->fetch_assoc();
            $getStmt->close();

            // Merge with existing images
            if (!empty($existing['images'])) {
                $existingImages = json_decode($existing['images'], true);
                $uploadedImages = array_merge($existingImages ?? [], $uploadedImages);
                $imagesJson = json_encode($uploadedImages);
            }
        }

        $query = "UPDATE news_media SET type = ?, title = ?, content = ?, images = ?, 
                  author = ?, published_date = ?, status = ? WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sssssssi", $type, $title, $content, $imagesJson, $author, $published_date, $status, $id);

        if ($stmt->execute()) {
            header("Location: ../news_list.php?success=Post updated successfully");
            exit();
        } else {
            header("Location: ../news_list.php?error=Failed to update post: " . $conn->error);
            exit();
        }

        $stmt->close();
    }

    $conn->close();
} else {
    header("Location: ../add_news.php");
    exit();
}
?>

