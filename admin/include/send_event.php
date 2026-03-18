<?php
include 'conn.php';
include 'email_handler.php'; // Include the new email handler

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit'])) {
    $event_date = $_POST['event_date'];
    $event_header = $_POST['event_header'];
    $event_description = $_POST['event_description'];

    // Remove a single outer <p>...</p> wrapper added by the editor
    $event_description = trim($event_description);
    if (preg_match('/^<p[^>]*>(.*)<\/p>$/is', $event_description, $matches)) {
        $event_description = $matches[1];
    }
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
                // Create professional email content
                $subject = "New Event: " . $event_header;
                
                // Email header with logo and branding
                $body = '<div style="max-width: 600px; margin: 0 auto; background-color: #ffffff; font-family: Arial, sans-serif;">';
                
                // Header section
                $body .= '<div style="background: linear-gradient(135deg, #007bff 0%, #0056b3 100%); padding: 40px 30px; text-align: center; color: white;">';
                $body .= '<h1 style="margin: 0; font-size: 28px; font-weight: 700; text-shadow: 0 2px 4px rgba(0,0,0,0.3);">' . htmlspecialchars($event_header) . '</h1>';
                $body .= '<div style="margin-top: 15px; font-size: 16px; opacity: 0.9;">';
                $body .= '<i class="fas fa-calendar-alt" style="margin-right: 8px;"></i>' . date("F j, Y", strtotime($event_date));
                $body .= '</div>';
                $body .= '</div>';
                
                // Content section
                $body .= '<div style="padding: 40px 30px;">';
                
                // Add images to email if any were uploaded
                if (!empty($uploaded_images)) {
                    foreach ($uploaded_images as $image) {
                        // Convert relative path to full URL
                        if (strpos($image, '../../') === 0) {
                            $cleanPath = str_replace('../../', '', $image);
                            $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
                            $host = $_SERVER['HTTP_HOST'];
                            $imageUrl = $protocol . "://" . $host . "/" . $cleanPath;
                        } elseif (strpos($image, '../') === 0) {
                            $cleanPath = str_replace('../', '', $image);
                            $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
                            $host = $_SERVER['HTTP_HOST'];
                            $imageUrl = $protocol . "://" . $host . "/" . $cleanPath;
                        } else {
                            $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
                            $host = $_SERVER['HTTP_HOST'];
                            $imageUrl = $protocol . "://" . $host . "/" . ltrim($image, '/');
                        }
                        
                        error_log("Email image URL: " . $imageUrl);
                        
                        $body .= '<div style="text-align: center; margin: 30px 0;">';
                        $body .= '<img src="' . htmlspecialchars($imageUrl) . '" alt="' . htmlspecialchars($event_header) . '" style="max-width: 100%; height: auto; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.1);">';
                        $body .= '</div>';
                    }
                }
                
                // Event description
                $body .= '<div style="font-size: 16px; line-height: 1.8; color: #333; margin-bottom: 30px;">';
                $body .= $event_description;
                $body .= '</div>';
                
                // Call to action button
                $body .= '<div style="text-align: center; margin: 40px 0;">';
                $body .= '<a href="http://' . $_SERVER['HTTP_HOST'] . '/events.php" style="background-color: #007bff; color: white; padding: 15px 35px; text-decoration: none; border-radius: 25px; font-weight: 600; font-size: 16px; display: inline-block; transition: all 0.3s ease;">';
                $body .= 'View All Events <i class="fas fa-arrow-right" style="margin-left: 8px;"></i>';
                $body .= '</a>';
                $body .= '</div>';
                
                $body .= '</div>';
                
                // Professional footer
                $body .= '<div style="background-color: #0084c7; padding: 30px; text-align: center; color: #ffffff; font-size: 12px;">';
                $body .= '<p style="margin: 0 0 10px 0; font-weight: 600;">This email is sent for you because you are member of ESWPA</p>';
                $body .= '<p style="margin: 0; opacity: 0.8;">Powered by Lebawi net trading</p>';
                $body .= '</div>';
                
                $body .= '</div>';
                
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
