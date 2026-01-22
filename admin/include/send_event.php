<?php
include 'conn.php';
include 'email_handler.php'; // Include the new email handler

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit'])) {
    $event_date = $_POST['event_date'];
    $event_header = $_POST['event_header'];
    $event_description = $_POST['event_description'];
    $event_type = $_POST['event_type']; 
    $upload_dir = "../../uploads/";
    $allowed_types = ['image/png', 'image/jpeg', 'image/jpg', 'image/webp'];
    $uploaded_images = [];

    // Handle file uploads
    if (!empty($_FILES['event_images']['name'][0])) {
        foreach ($_FILES['event_images']['tmp_name'] as $key => $tmp_name) {
            $file_name = $_FILES['event_images']['name'][$key];
            $file_type = $_FILES['event_images']['type'][$key];
            $file_tmp = $_FILES['event_images']['tmp_name'][$key];

            if (in_array($file_type, $allowed_types)) {
                $new_file_name = time() . "_" . basename($file_name);
                $target_file = $upload_dir . $new_file_name;

                if (move_uploaded_file($file_tmp, $target_file)) {
                    $uploaded_images[] = "../../uploads/" . $new_file_name; // Store relative path
                }
            }
        }
    }

    // Convert image paths to JSON format for database storage
    $event_images_json = json_encode($uploaded_images);

    // Determine which table to insert into
    $table_name = ($event_type === "upcoming") ? "upcoming" : "events";

    // Insert data into the correct table
    $query = "INSERT INTO $table_name (event_date, event_header, event_description, event_images) VALUES (?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "ssss", $event_date, $event_header, $event_description, $event_images_json);

    if (mysqli_stmt_execute($stmt)) {
        $event_id = mysqli_insert_id($conn);
        
        // Check if newsletter should be sent (manual checkbox)
        if (isset($_POST['send_newsletter']) && $_POST['send_newsletter'] == '1') {
            // Fetch subscriber emails from 'registrations' table
            $sql = "SELECT email FROM registrations WHERE email IS NOT NULL AND email != ''";
            $result = $conn->query($sql);
            $subscribers = [];
            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    $subscribers[] = $row['email'];
                }
            }

            if (!empty($subscribers)) {
                // Create email content
                $subject = "New Event: " . $event_header;
                $body = "<h1>" . $event_header . "</h1>";
                $body .= "<h4>Event Date: " . $event_date . "</h4>";
                $body .= "<p>" . nl2br($event_description) . "</p>";
                $body .= "<p>For more details, please visit our website.</p>";
                
                // Send the newsletter
                sendNewsletter($subject, $body, $subscribers);
            }
        }
        
        // Also check for automated email sending (if automation is enabled)
        require_once __DIR__ . '/email_automation.php';
        
        // Generate content link
        $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
        $host = $_SERVER['HTTP_HOST'];
        $content_link = $protocol . "://" . $host . "/events.php";
        
        // Parse images
        $images = [];
        if (!empty($uploaded_images)) {
            $images = $uploaded_images;
        }
        
        // Send automated email (will check if automation is enabled)
        sendAutomatedEmail('event', $event_id, [
            'title' => $event_header,
            'content' => $event_description,
            'author' => 'Admin',
            'date' => $event_date,
            'images' => $images,
            'link' => $content_link
        ]);
        
        echo "<script>alert('Event added successfully!'); window.location.href='../add_event.php';</script>";
    } else {
        echo "<script>alert('Error adding event: " . mysqli_error($conn) . "'); window.history.back();</script>";
    }
} else {
    echo "<script>alert('Invalid request!'); window.history.back();</script>";
}
?>
