<?php
include 'conn.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['id']) && isset($_POST['type'])) {
    $event_id = intval($_POST['id']);
    $event_type = $_POST['type']; // "events" or "upcoming"

    // Ensure only allowed table names are used
    if ($event_type !== "events" && $event_type !== "upcoming") {
        echo "Invalid event type!";
        exit;
    }

    // Step 1: Fetch event images to delete from the server
    $query = "SELECT event_images FROM $event_type WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $event_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($row = mysqli_fetch_assoc($result)) {
        $event_images = json_decode($row['event_images'], true);

        // Step 2: Delete images from the server
        if (!empty($event_images)) {
            foreach ($event_images as $image) {
                $image_path = "../../uploads/" . basename($image);
                if (file_exists($image_path)) {
                    unlink($image_path); // Delete image file
                }
            }
        }
    }

    // Step 3: Delete event record from database
    $delete_query = "DELETE FROM $event_type WHERE id = ?";
    $delete_stmt = mysqli_prepare($conn, $delete_query);
    mysqli_stmt_bind_param($delete_stmt, "i", $event_id);

    if (mysqli_stmt_execute($delete_stmt)) {
        echo ucfirst($event_type) . " event deleted successfully!";
    } else {
        echo "Error deleting " . $event_type . " event: " . mysqli_error($conn);
    }
} else {
    echo "Invalid request!";
}
?>
