<?php
session_start();
include 'conn.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth-login.php");
    exit();
}

$news_id = intval($_GET['id'] ?? 0);

if ($news_id <= 0) {
    header("Location: ../news_list.php?error=Invalid post ID");
    exit();
}

// Get post details to delete images
$query = "SELECT images FROM news_media WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $news_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: ../news_list.php?error=Post not found");
    exit();
}

$post = $result->fetch_assoc();

// Delete images if they exist
if (!empty($post['images'])) {
    $images = json_decode($post['images'], true);
    if (is_array($images)) {
        foreach ($images as $image) {
            $imagePath = '../' . $image;
            if (file_exists($imagePath)) {
                unlink($imagePath);
            }
        }
    }
}

// Delete from database
$deleteQuery = "DELETE FROM news_media WHERE id = ?";
$deleteStmt = $conn->prepare($deleteQuery);
$deleteStmt->bind_param("i", $news_id);

if ($deleteStmt->execute()) {
    header("Location: ../news_list.php?success=Post deleted successfully");
} else {
    header("Location: ../news_list.php?error=Failed to delete post");
}

$deleteStmt->close();
$stmt->close();
$conn->close();
?>

