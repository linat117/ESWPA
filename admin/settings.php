<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: auth-login.php");
    exit();
}

include 'include/conn.php';
include 'header.php';

// Get current tab
$tab = $_GET['tab'] ?? 'general';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_settings'])) {
    $category = $_POST['category'] ?? 'general';
    
    foreach ($_POST as $key => $value) {
        if ($key != 'save_settings' && $key != 'category') {
            $query = "INSERT INTO settings (setting_key, setting_value, category) 
                      VALUES (?, ?, ?) 
                      ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = NOW()";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ssss", $key, $value, $category, $value);
            $stmt->execute();
            $stmt->close();
        }
    }
    
    header("Location: settings.php?tab=$category&success=Settings saved successfully");
    exit();
}

// Get settings by category
function getSettings($conn, $category) {
    $query = "SELECT setting_key, setting_value, description FROM settings WHERE category = ? ORDER BY setting_key";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $category);
    $stmt->execute();
    $result = $stmt->get_result();
    $settings = [];
    while ($row = $result->fetch_assoc()) {
        $settings[$row['setting_key']] = $row;
    }
    $stmt->close();
    return $settings;
}
?>

<body>
    <div class="wrapper">
        <?php include 'sidebar.php'; ?>
        <div class="content-page">
            <div class="content">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-12">
                            <div class="page-title-box">
                                <h4 class="page-title">System Settings</h4>
                            </div>
                        </div>
                    </div>

                    <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($_GET['success']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>

                    <!-- Settings Tabs -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <ul class="nav nav-tabs nav-bordered" role="tablist">
                                        <li class="nav-item">
                                            <a href="?tab=general" class="nav-link <?php echo $tab == 'general' ? 'active' : ''; ?>">
                                                <i class="ri-settings-3-line"></i> General
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a href="?tab=email" class="nav-link <?php echo $tab == 'email' ? 'active' : ''; ?>">
                                                <i class="ri-mail-line"></i> Email
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a href="?tab=telegram" class="nav-link <?php echo $tab == 'telegram' ? 'active' : ''; ?>">
                                                <i class="ri-telegram-line"></i> Telegram
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a href="?tab=backup" class="nav-link <?php echo $tab == 'backup' ? 'active' : ''; ?>">
                                                <i class="ri-database-2-line"></i> Backup
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a href="?tab=sync" class="nav-link <?php echo $tab == 'sync' ? 'active' : ''; ?>">
                                                <i class="ri-refresh-line"></i> Data Sync
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a href="settings_users.php" class="nav-link">
                                                <i class="ri-user-settings-line"></i> User Management
                                            </a>
                                        </li>
                                    </ul>

                                    <div class="tab-content mt-3">
                                        <?php
                                        $settings = getSettings($conn, $tab);
                                        ?>
                                        <form method="POST" action="settings.php">
                                            <input type="hidden" name="category" value="<?php echo $tab; ?>">
                                            
                                            <?php if ($tab == 'general'): ?>
                                                <div class="mb-3">
                                                    <label for="site_name" class="form-label">Site Name</label>
                                                    <input type="text" class="form-control" id="site_name" name="site_name" 
                                                           value="<?php echo htmlspecialchars($settings['site_name']['setting_value'] ?? ''); ?>">
                                                </div>
                                                <div class="mb-3">
                                                    <label for="site_email" class="form-label">Site Email</label>
                                                    <input type="email" class="form-control" id="site_email" name="site_email" 
                                                           value="<?php echo htmlspecialchars($settings['site_email']['setting_value'] ?? ''); ?>">
                                                </div>
                                            
                                            <?php elseif ($tab == 'email'): ?>
                                                <div class="mb-3">
                                                    <label for="smtp_host" class="form-label">SMTP Host</label>
                                                    <input type="text" class="form-control" id="smtp_host" name="smtp_host" 
                                                           value="<?php echo htmlspecialchars($settings['smtp_host']['setting_value'] ?? ''); ?>">
                                                </div>
                                                <div class="mb-3">
                                                    <label for="smtp_port" class="form-label">SMTP Port</label>
                                                    <input type="number" class="form-control" id="smtp_port" name="smtp_port" 
                                                           value="<?php echo htmlspecialchars($settings['smtp_port']['setting_value'] ?? '587'); ?>">
                                                </div>
                                                <div class="mb-3">
                                                    <label for="smtp_username" class="form-label">SMTP Username</label>
                                                    <input type="text" class="form-control" id="smtp_username" name="smtp_username" 
                                                           value="<?php echo htmlspecialchars($settings['smtp_username']['setting_value'] ?? ''); ?>">
                                                </div>
                                                <div class="mb-3">
                                                    <label for="smtp_password" class="form-label">SMTP Password</label>
                                                    <input type="password" class="form-control" id="smtp_password" name="smtp_password" 
                                                           value="<?php echo htmlspecialchars($settings['smtp_password']['setting_value'] ?? ''); ?>">
                                                </div>
                                                <div class="mb-3">
                                                    <label for="smtp_encryption" class="form-label">Encryption</label>
                                                    <select class="form-control" id="smtp_encryption" name="smtp_encryption">
                                                        <option value="tls" <?php echo ($settings['smtp_encryption']['setting_value'] ?? 'tls') == 'tls' ? 'selected' : ''; ?>>TLS</option>
                                                        <option value="ssl" <?php echo ($settings['smtp_encryption']['setting_value'] ?? '') == 'ssl' ? 'selected' : ''; ?>>SSL</option>
                                                    </select>
                                                </div>
                                            
                                            <?php elseif ($tab == 'telegram'): ?>
                                                <div class="mb-3">
                                                    <label for="telegram_bot_token" class="form-label">Telegram Bot Token</label>
                                                    <input type="text" class="form-control" id="telegram_bot_token" name="telegram_bot_token" 
                                                           value="<?php echo htmlspecialchars($settings['telegram_bot_token']['setting_value'] ?? ''); ?>">
                                                    <small class="text-muted">Get your bot token from @BotFather on Telegram</small>
                                                </div>
                                                <div class="mb-3">
                                                    <label for="telegram_chat_id" class="form-label">Telegram Chat ID</label>
                                                    <input type="text" class="form-control" id="telegram_chat_id" name="telegram_chat_id" 
                                                           value="<?php echo htmlspecialchars($settings['telegram_chat_id']['setting_value'] ?? ''); ?>">
                                                    <small class="text-muted">Your Telegram chat ID for receiving notifications</small>
                                                </div>
                                                <div class="mb-3">
                                                    <button type="button" class="btn btn-info" id="testTelegramBtn">
                                                        <i class="ri-telegram-line"></i> Test Telegram Bot
                                                    </button>
                                                    <div id="telegramTestResult" class="mt-2" style="display: none;"></div>
                                                </div>
                                            
                                            <?php elseif ($tab == 'backup'): ?>
                                                <div class="mb-3">
                                                    <label class="form-label">Automatic Backups</label>
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox" id="backup_auto_enabled" name="backup_auto_enabled" value="1"
                                                               <?php echo ($settings['backup_auto_enabled']['setting_value'] ?? '0') == '1' ? 'checked' : ''; ?>>
                                                        <label class="form-check-label" for="backup_auto_enabled">Enable automatic backups</label>
                                                    </div>
                                                </div>
                                                <div class="mb-3">
                                                    <label for="backup_auto_frequency" class="form-label">Backup Frequency</label>
                                                    <select class="form-control" id="backup_auto_frequency" name="backup_auto_frequency">
                                                        <option value="daily" <?php echo ($settings['backup_auto_frequency']['setting_value'] ?? 'daily') == 'daily' ? 'selected' : ''; ?>>Daily</option>
                                                        <option value="weekly" <?php echo ($settings['backup_auto_frequency']['setting_value'] ?? '') == 'weekly' ? 'selected' : ''; ?>>Weekly</option>
                                                        <option value="monthly" <?php echo ($settings['backup_auto_frequency']['setting_value'] ?? '') == 'monthly' ? 'selected' : ''; ?>>Monthly</option>
                                                    </select>
                                                </div>
                                                <div class="mb-3">
                                                    <a href="settings_backup.php" class="btn btn-primary">
                                                        <i class="ri-database-2-line"></i> Manage Backups
                                                    </a>
                                                </div>
                                            
                                            <?php elseif ($tab == 'sync'): ?>
                                                <div class="mb-3">
                                                    <label class="form-label">Data Synchronization</label>
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox" id="sync_enabled" name="sync_enabled" value="1"
                                                               <?php echo ($settings['sync_enabled']['setting_value'] ?? '0') == '1' ? 'checked' : ''; ?>>
                                                        <label class="form-check-label" for="sync_enabled">Enable data synchronization</label>
                                                    </div>
                                                </div>
                                                <div class="mb-3">
                                                    <label for="sync_remote_host" class="form-label">Remote Server Host</label>
                                                    <input type="text" class="form-control" id="sync_remote_host" name="sync_remote_host" 
                                                           value="<?php echo htmlspecialchars($settings['sync_remote_host']['setting_value'] ?? ''); ?>"
                                                           placeholder="localhost or IP address">
                                                    <small class="text-muted">For remote server, use the server IP or hostname. For same server, use 'localhost'</small>
                                                </div>
                                                <div class="mb-3">
                                                    <label for="sync_remote_db" class="form-label">Remote Database Name</label>
                                                    <input type="text" class="form-control" id="sync_remote_db" name="sync_remote_db" 
                                                           value="<?php echo htmlspecialchars($settings['sync_remote_db']['setting_value'] ?? ''); ?>"
                                                           placeholder="ethiosdt_new_db">
                                                </div>
                                                <div class="mb-3">
                                                    <label for="sync_remote_user" class="form-label">Remote Database User</label>
                                                    <input type="text" class="form-control" id="sync_remote_user" name="sync_remote_user" 
                                                           value="<?php echo htmlspecialchars($settings['sync_remote_user']['setting_value'] ?? ''); ?>"
                                                           placeholder="ethiosdt_new_user">
                                                </div>
                                                <div class="mb-3">
                                                    <label for="sync_remote_password" class="form-label">Remote Database Password</label>
                                                    <input type="password" class="form-control" id="sync_remote_password" name="sync_remote_password" 
                                                           value="<?php echo htmlspecialchars($settings['sync_remote_password']['setting_value'] ?? ''); ?>"
                                                           placeholder="Enter database password">
                                                    <small class="text-muted">Password is stored securely in settings</small>
                                                </div>
                                                <div class="mb-3">
                                                    <a href="settings_sync.php" class="btn btn-primary">
                                                        <i class="ri-refresh-line"></i> Go to Sync Page
                                                    </a>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <div class="mt-3">
                                                <button type="submit" name="save_settings" class="btn btn-primary">
                                                    <i class="ri-save-line"></i> Save Settings
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php include 'footer.php'; ?>
        </div>
    </div>

    <script src="assets/js/vendor.min.js"></script>
    <script src="assets/js/app.min.js"></script>
    
    <script>
    // Telegram Test Button
    document.addEventListener('DOMContentLoaded', function() {
        const testBtn = document.getElementById('testTelegramBtn');
        const resultDiv = document.getElementById('telegramTestResult');
        
        if (testBtn) {
            testBtn.addEventListener('click', function() {
                testBtn.disabled = true;
                testBtn.innerHTML = '<i class="ri-loader-4-line ri-spin"></i> Testing...';
                resultDiv.style.display = 'none';
                
                fetch('../include/test_telegram.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    }
                })
                .then(response => response.json())
                .then(data => {
                    resultDiv.style.display = 'block';
                    if (data.success) {
                        resultDiv.className = 'alert alert-success mt-2';
                        resultDiv.innerHTML = '<i class="ri-check-line"></i> ' + data.message;
                    } else {
                        resultDiv.className = 'alert alert-danger mt-2';
                        resultDiv.innerHTML = '<i class="ri-error-warning-line"></i> ' + data.message;
                    }
                })
                .catch(error => {
                    resultDiv.style.display = 'block';
                    resultDiv.className = 'alert alert-danger mt-2';
                    resultDiv.innerHTML = '<i class="ri-error-warning-line"></i> Error: Could not test Telegram bot.';
                })
                .finally(() => {
                    testBtn.disabled = false;
                    testBtn.innerHTML = '<i class="ri-telegram-line"></i> Test Telegram Bot';
                });
            });
        }
    });
    </script>
</body>
</html>

