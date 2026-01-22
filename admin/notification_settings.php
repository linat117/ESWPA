<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: auth-login.php");
    exit();
}

include 'include/conn.php';
include 'header.php';

$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_notification_settings'])) {
    $settings_to_save = [
        'notification_email_enabled' => $_POST['notification_email_enabled'] ?? '0',
        'notification_sms_enabled' => $_POST['notification_sms_enabled'] ?? '0',
        'notification_push_enabled' => $_POST['notification_push_enabled'] ?? '0',
        'notification_default_types' => $_POST['notification_default_types'] ?? '',
        'notification_auto_mark_read' => $_POST['notification_auto_mark_read'] ?? '0',
        'notification_retention_days' => $_POST['notification_retention_days'] ?? '90',
        'notification_batch_size' => $_POST['notification_batch_size'] ?? '100'
    ];
    
    foreach ($settings_to_save as $key => $value) {
        $query = "INSERT INTO settings (setting_key, setting_value, category, description) 
                  VALUES (?, ?, 'notification', ?) 
                  ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = NOW()";
        $stmt = $conn->prepare($query);
        $description = "Notification setting: " . str_replace('_', ' ', $key);
        $stmt->bind_param("ssss", $key, $value, $description, $value);
        $stmt->execute();
        $stmt->close();
    }
    
    $success_message = "Notification settings saved successfully";
}

// Get notification settings
function getNotificationSetting($conn, $key, $default = '') {
    $query = "SELECT setting_value FROM settings WHERE setting_key = ? AND category = 'notification' LIMIT 1";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $key);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $value = $result->fetch_assoc()['setting_value'];
        $stmt->close();
        return $value;
    }
    $stmt->close();
    return $default;
}

$notification_email_enabled = getNotificationSetting($conn, 'notification_email_enabled', '0');
$notification_sms_enabled = getNotificationSetting($conn, 'notification_sms_enabled', '0');
$notification_push_enabled = getNotificationSetting($conn, 'notification_push_enabled', '0');
$notification_default_types = getNotificationSetting($conn, 'notification_default_types', '');
$notification_auto_mark_read = getNotificationSetting($conn, 'notification_auto_mark_read', '0');
$notification_retention_days = getNotificationSetting($conn, 'notification_retention_days', '90');
$notification_batch_size = getNotificationSetting($conn, 'notification_batch_size', '100');
?>

<!DOCTYPE html>
<html lang="en">

<body>
    <div class="wrapper">
        <?php include 'sidebar.php'; ?>

        <div class="content-page">
            <div class="content">
                <div class="container-fluid">
                    <!-- Page Title -->
                    <div class="row">
                        <div class="col-12">
                            <div class="page-title-box d-flex justify-content-between align-items-center">
                                <h4 class="page-title">Notification Settings</h4>
                                <div>
                                    <a href="notifications_center.php" class="btn btn-secondary me-2">
                                        <i class="ri-dashboard-line"></i> Dashboard
                                    </a>
                                    <a href="notification_templates.php" class="btn btn-info">
                                        <i class="ri-file-text-line"></i> Templates
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php
                    if ($success_message) {
                        echo '<div class="alert alert-success alert-dismissible fade show" role="alert">';
                        echo htmlspecialchars($success_message);
                        echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
                        echo '</div>';
                    }
                    if ($error_message) {
                        echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">';
                        echo htmlspecialchars($error_message);
                        echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
                        echo '</div>';
                    }
                    ?>

                    <div class="row">
                        <div class="col-lg-8">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Notification Preferences</h4>
                                </div>
                                <div class="card-body">
                                    <form method="POST" action="">
                                        
                                        <!-- Delivery Methods -->
                                        <h5 class="mb-3">Delivery Methods</h5>
                                        
                                        <div class="mb-4">
                                            <div class="form-check form-switch mb-3">
                                                <input class="form-check-input" type="checkbox" name="notification_email_enabled" id="notification_email_enabled" value="1" 
                                                       <?php echo $notification_email_enabled == '1' ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="notification_email_enabled">
                                                    <strong>Enable Email Notifications</strong>
                                                </label>
                                                <p class="text-muted small mb-0">Send notifications via email</p>
                                            </div>
                                            
                                            <div class="form-check form-switch mb-3">
                                                <input class="form-check-input" type="checkbox" name="notification_sms_enabled" id="notification_sms_enabled" value="1"
                                                       <?php echo $notification_sms_enabled == '1' ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="notification_sms_enabled">
                                                    <strong>Enable SMS Notifications</strong>
                                                </label>
                                                <p class="text-muted small mb-0">Send notifications via SMS (requires SMS gateway configuration)</p>
                                            </div>
                                            
                                            <div class="form-check form-switch mb-3">
                                                <input class="form-check-input" type="checkbox" name="notification_push_enabled" id="notification_push_enabled" value="1"
                                                       <?php echo $notification_push_enabled == '1' ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="notification_push_enabled">
                                                    <strong>Enable Push Notifications</strong>
                                                </label>
                                                <p class="text-muted small mb-0">Send browser push notifications (requires service worker setup)</p>
                                            </div>
                                        </div>

                                        <hr>

                                        <!-- Notification Behavior -->
                                        <h5 class="mb-3">Notification Behavior</h5>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Default Notification Types</label>
                                            <input type="text" name="notification_default_types" class="form-control" 
                                                   value="<?php echo htmlspecialchars($notification_default_types); ?>" 
                                                   placeholder="e.g., membership, event, research, resource">
                                            <small class="text-muted">Comma-separated list of default notification types</small>
                                        </div>
                                        
                                        <div class="mb-4">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="notification_auto_mark_read" id="notification_auto_mark_read" value="1"
                                                       <?php echo $notification_auto_mark_read == '1' ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="notification_auto_mark_read">
                                                    <strong>Auto Mark as Read</strong>
                                                </label>
                                                <p class="text-muted small mb-0">Automatically mark notifications as read after a certain period</p>
                                            </div>
                                        </div>

                                        <hr>

                                        <!-- Data Management -->
                                        <h5 class="mb-3">Data Management</h5>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Notification Retention (Days)</label>
                                            <input type="number" name="notification_retention_days" class="form-control" 
                                                   value="<?php echo htmlspecialchars($notification_retention_days); ?>" 
                                                   min="1" max="3650">
                                            <small class="text-muted">Number of days to keep notifications before automatic deletion (1-3650 days)</small>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Batch Size</label>
                                            <input type="number" name="notification_batch_size" class="form-control" 
                                                   value="<?php echo htmlspecialchars($notification_batch_size); ?>" 
                                                   min="10" max="1000">
                                            <small class="text-muted">Number of notifications to process per batch when sending bulk notifications (10-1000)</small>
                                        </div>

                                        <div class="d-grid gap-2">
                                            <button type="submit" name="save_notification_settings" class="btn btn-primary">
                                                <i class="ri-save-line"></i> Save Settings
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Sidebar Info -->
                        <div class="col-lg-4">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Information</h4>
                                </div>
                                <div class="card-body">
                                    <h6>Notification System</h6>
                                    <p class="text-muted small">The notification system allows you to send notifications to members about various events and updates in the system.</p>
                                    
                                    <h6 class="mt-3">Notification Types</h6>
                                    <ul class="text-muted small">
                                        <li>Membership updates</li>
                                        <li>Event notifications</li>
                                        <li>Research updates</li>
                                        <li>Resource availability</li>
                                        <li>System announcements</li>
                                        <li>Custom notifications</li>
                                    </ul>
                                    
                                    <h6 class="mt-3">Delivery Methods</h6>
                                    <p class="text-muted small">Notifications can be delivered via in-app notifications (always enabled), email, SMS, and push notifications based on your configuration.</p>
                                    
                                    <a href="notifications_center.php" class="btn btn-sm btn-info w-100 mt-3">
                                        <i class="ri-dashboard-line"></i> View Dashboard
                                    </a>
                                    <a href="notifications_list.php" class="btn btn-sm btn-secondary w-100 mt-2">
                                        <i class="ri-notification-line"></i> View Notifications
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <?php include 'footer.php'; ?>
        </div>
    </div>

    <!-- Vendor js -->
    <script src="assets/js/vendor.min.js"></script>
    
    <!-- App js -->
    <script src="assets/js/app.min.js"></script>

</body>
</html>

