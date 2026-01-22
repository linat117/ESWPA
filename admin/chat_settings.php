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
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_chat_settings'])) {
    $settings_to_save = [
        'chat_widget_enabled' => $_POST['chat_widget_enabled'] ?? '0',
        'chat_widget_position' => $_POST['chat_widget_position'] ?? 'bottom-right',
        'chat_widget_text' => $_POST['chat_widget_text'] ?? 'Chat with Us',
        'chat_widget_button_color' => $_POST['chat_widget_button_color'] ?? '#0088cc',
        'chat_auto_reply_enabled' => $_POST['chat_auto_reply_enabled'] ?? '0',
        'chat_auto_reply_message' => $_POST['chat_auto_reply_message'] ?? '',
        'chat_email_notification' => $_POST['chat_email_notification'] ?? '0',
        'chat_email_recipient' => $_POST['chat_email_recipient'] ?? ''
    ];
    
    foreach ($settings_to_save as $key => $value) {
        $query = "INSERT INTO settings (setting_key, setting_value, category, description) 
                  VALUES (?, ?, 'chat', ?) 
                  ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = NOW()";
        $stmt = $conn->prepare($query);
        $description = "Chat widget setting: " . str_replace('_', ' ', $key);
        $stmt->bind_param("ssss", $key, $value, $description, $value);
        $stmt->execute();
        $stmt->close();
    }
    
    $success_message = "Chat settings saved successfully";
}

// Get chat settings
function getChatSetting($conn, $key, $default = '') {
    $query = "SELECT setting_value FROM settings WHERE setting_key = ? AND category = 'chat' LIMIT 1";
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

// Get Telegram settings (from telegram category)
function getTelegramSetting($conn, $key, $default = '') {
    $query = "SELECT setting_value FROM settings WHERE setting_key = ? AND category = 'telegram' LIMIT 1";
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

$chat_widget_enabled = getChatSetting($conn, 'chat_widget_enabled', '1');
$chat_widget_position = getChatSetting($conn, 'chat_widget_position', 'bottom-right');
$chat_widget_text = getChatSetting($conn, 'chat_widget_text', 'Chat with Us');
$chat_widget_button_color = getChatSetting($conn, 'chat_widget_button_color', '#0088cc');
$chat_auto_reply_enabled = getChatSetting($conn, 'chat_auto_reply_enabled', '0');
$chat_auto_reply_message = getChatSetting($conn, 'chat_auto_reply_message', 'Thank you for your message! We will get back to you soon.');
$chat_email_notification = getChatSetting($conn, 'chat_email_notification', '0');
$chat_email_recipient = getChatSetting($conn, 'chat_email_recipient', '');

// Telegram settings
$telegram_bot_token = getTelegramSetting($conn, 'telegram_bot_token', '');
$telegram_chat_id = getTelegramSetting($conn, 'telegram_chat_id', '');
$telegram_configured = !empty($telegram_bot_token) && !empty($telegram_chat_id);
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
                                <h4 class="page-title">Chat Settings</h4>
                                <div>
                                    <a href="chat_dashboard.php" class="btn btn-secondary me-2">
                                        <i class="ri-dashboard-line"></i> Dashboard
                                    </a>
                                    <a href="settings.php?tab=telegram" class="btn btn-info">
                                        <i class="ri-telegram-line"></i> Telegram Settings
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

                    <!-- Telegram Configuration Status -->
                    <?php if (!$telegram_configured): ?>
                    <div class="alert alert-warning" role="alert">
                        <h5 class="alert-heading"><i class="ri-alert-line"></i> Telegram Not Configured</h5>
                        <p>Telegram bot is not configured. Please configure the bot token and chat ID to enable chat functionality.</p>
                        <p class="mb-0">
                            <a href="settings.php?tab=telegram" class="btn btn-sm btn-primary">
                                <i class="ri-settings-line"></i> Configure Telegram Settings
                            </a>
                        </p>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-success" role="alert">
                        <i class="ri-checkbox-circle-line"></i> Telegram bot is configured and ready.
                        <a href="settings.php?tab=telegram" class="alert-link">Edit settings</a>
                    </div>
                    <?php endif; ?>

                    <!-- Chat Settings Form -->
                    <div class="row">
                        <div class="col-lg-8">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Chat Widget Settings</h4>
                                </div>
                                <div class="card-body">
                                    <form method="POST" action="">
                                        <!-- Widget Enable/Disable -->
                                        <div class="mb-4">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="chat_widget_enabled" id="chat_widget_enabled" value="1" 
                                                       <?php echo $chat_widget_enabled == '1' ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="chat_widget_enabled">
                                                    <strong>Enable Chat Widget</strong>
                                                </label>
                                                <p class="text-muted small mb-0">Show the chat widget on the website</p>
                                            </div>
                                        </div>

                                        <hr>

                                        <!-- Widget Position -->
                                        <div class="mb-3">
                                            <label class="form-label">Widget Position</label>
                                            <select name="chat_widget_position" class="form-select">
                                                <option value="bottom-right" <?php echo $chat_widget_position == 'bottom-right' ? 'selected' : ''; ?>>Bottom Right</option>
                                                <option value="bottom-left" <?php echo $chat_widget_position == 'bottom-left' ? 'selected' : ''; ?>>Bottom Left</option>
                                                <option value="top-right" <?php echo $chat_widget_position == 'top-right' ? 'selected' : ''; ?>>Top Right</option>
                                                <option value="top-left" <?php echo $chat_widget_position == 'top-left' ? 'selected' : ''; ?>>Top Left</option>
                                            </select>
                                            <small class="text-muted">Where the chat button appears on the page</small>
                                        </div>

                                        <!-- Widget Text -->
                                        <div class="mb-3">
                                            <label class="form-label">Widget Button Text</label>
                                            <input type="text" name="chat_widget_text" class="form-control" value="<?php echo htmlspecialchars($chat_widget_text); ?>">
                                            <small class="text-muted">Text displayed on the chat button</small>
                                        </div>

                                        <!-- Button Color -->
                                        <div class="mb-3">
                                            <label class="form-label">Button Color</label>
                                            <div class="input-group">
                                                <input type="color" name="chat_widget_button_color" class="form-control form-control-color" value="<?php echo htmlspecialchars($chat_widget_button_color); ?>">
                                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($chat_widget_button_color); ?>" readonly>
                                            </div>
                                            <small class="text-muted">Chat button background color</small>
                                        </div>

                                        <hr>

                                        <!-- Auto Reply -->
                                        <div class="mb-4">
                                            <div class="form-check form-switch mb-3">
                                                <input class="form-check-input" type="checkbox" name="chat_auto_reply_enabled" id="chat_auto_reply_enabled" value="1"
                                                       <?php echo $chat_auto_reply_enabled == '1' ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="chat_auto_reply_enabled">
                                                    <strong>Enable Auto Reply</strong>
                                                </label>
                                                <p class="text-muted small mb-0">Send automatic confirmation message to users</p>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label class="form-label">Auto Reply Message</label>
                                                <textarea name="chat_auto_reply_message" class="form-control" rows="3"><?php echo htmlspecialchars($chat_auto_reply_message); ?></textarea>
                                                <small class="text-muted">Message sent automatically after user submits chat form</small>
                                            </div>
                                        </div>

                                        <hr>

                                        <!-- Email Notification -->
                                        <div class="mb-4">
                                            <div class="form-check form-switch mb-3">
                                                <input class="form-check-input" type="checkbox" name="chat_email_notification" id="chat_email_notification" value="1"
                                                       <?php echo $chat_email_notification == '1' ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="chat_email_notification">
                                                    <strong>Email Notifications</strong>
                                                </label>
                                                <p class="text-muted small mb-0">Send email notification when new chat message is received</p>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label class="form-label">Notification Email</label>
                                                <input type="email" name="chat_email_recipient" class="form-control" value="<?php echo htmlspecialchars($chat_email_recipient); ?>" placeholder="admin@example.com">
                                                <small class="text-muted">Email address to receive chat notifications</small>
                                            </div>
                                        </div>

                                        <div class="d-grid gap-2">
                                            <button type="submit" name="save_chat_settings" class="btn btn-primary">
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
                                    <h6>Chat Widget</h6>
                                    <p class="text-muted small">The chat widget allows visitors to send messages directly to your Telegram account. Messages are forwarded via Telegram Bot API.</p>
                                    
                                    <h6 class="mt-3">Requirements</h6>
                                    <ul class="text-muted small">
                                        <li>Telegram Bot Token (from @BotFather)</li>
                                        <li>Telegram Chat ID (your chat ID)</li>
                                        <li>Configure in System Settings → Telegram</li>
                                    </ul>
                                    
                                    <h6 class="mt-3">Message Logging</h6>
                                    <p class="text-muted small">Messages are optionally logged to the <code>telegram_messages</code> table for tracking and analytics.</p>
                                    
                                    <a href="chat_dashboard.php" class="btn btn-sm btn-info w-100 mt-3">
                                        <i class="ri-dashboard-line"></i> View Dashboard
                                    </a>
                                    <a href="chat_conversations.php" class="btn btn-sm btn-secondary w-100 mt-2">
                                        <i class="ri-message-3-line"></i> View Messages
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

