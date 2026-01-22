<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: auth-login.php");
    exit();
}

include 'header.php';
include 'include/conn.php';

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $settings = $_POST['settings'] ?? [];
    
    foreach ($settings as $key => $value) {
        $query = "UPDATE ai_settings SET setting_value = ? WHERE setting_key = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ss", $value, $key);
        $stmt->execute();
        $stmt->close();
    }
    
    $success = "AI settings updated successfully!";
}

// Get all settings
$settingsQuery = "SELECT * FROM ai_settings ORDER BY category, setting_key";
$settingsResult = mysqli_query($conn, $settingsQuery);

// Get queue statistics
require_once '../include/ai_queue_processor.php';
$queueStats = getQueueStatistics();

// Get active plugins
$pluginsQuery = "SELECT * FROM ai_plugins WHERE is_active = 1 ORDER BY plugin_type, plugin_name";
$pluginsResult = mysqli_query($conn, $pluginsQuery);
?>

<body>
    <div class="wrapper">
        <?php include 'sidebar.php'; ?>

        <div class="content-page">
            <div class="content">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-12">
                            <div class="page-title-box d-flex justify-content-between align-items-center">
                                <h4 class="page-title">AI Settings</h4>
                            </div>
                        </div>
                    </div>

                    <?php if (isset($success)): ?>
                        <div class="alert alert-success alert-dismissible fade show">
                            <?php echo htmlspecialchars($success); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Statistics Cards -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title">Queue Statistics</h5>
                                    <p class="mb-0"><strong>Total:</strong> <?php echo $queueStats['total']; ?></p>
                                    <p class="mb-0"><strong>Pending:</strong> <span class="badge bg-warning"><?php echo $queueStats['pending']; ?></span></p>
                                    <p class="mb-0"><strong>Processing:</strong> <span class="badge bg-info"><?php echo $queueStats['processing']; ?></span></p>
                                    <p class="mb-0"><strong>Completed:</strong> <span class="badge bg-success"><?php echo $queueStats['completed']; ?></span></p>
                                    <p class="mb-0"><strong>Failed:</strong> <span class="badge bg-danger"><?php echo $queueStats['failed']; ?></span></p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title">Active Plugins</h5>
                                    <p class="mb-0"><strong><?php echo mysqli_num_rows($pluginsResult); ?></strong> active plugins</p>
                                    <a href="ai_plugins.php" class="btn btn-sm btn-primary mt-2">Manage Plugins</a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Settings Form -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">AI Configuration</h4>
                                </div>
                                <div class="card-body">
                                    <form method="POST">
                                        <?php
                                        $currentCategory = '';
                                        while ($setting = mysqli_fetch_assoc($settingsResult)):
                                            if ($currentCategory !== $setting['category']):
                                                if ($currentCategory !== ''):
                                                    echo '</div></div>';
                                                endif;
                                                $currentCategory = $setting['category'];
                                                echo '<div class="mb-4">';
                                                echo '<h5 class="mb-3">' . ucfirst($currentCategory) . ' Settings</h5>';
                                                echo '<div class="row g-3">';
                                            endif;
                                        ?>
                                            <div class="col-md-6">
                                                <label class="form-label">
                                                    <?php echo htmlspecialchars($setting['setting_key']); ?>
                                                    <?php if (!empty($setting['description'])): ?>
                                                        <small class="text-muted d-block"><?php echo htmlspecialchars($setting['description']); ?></small>
                                                    <?php endif; ?>
                                                </label>
                                                
                                                <?php if ($setting['setting_type'] === 'boolean'): ?>
                                                    <select name="settings[<?php echo htmlspecialchars($setting['setting_key']); ?>]" class="form-select">
                                                        <option value="1" <?php echo $setting['setting_value'] == '1' ? 'selected' : ''; ?>>Enabled</option>
                                                        <option value="0" <?php echo $setting['setting_value'] == '0' ? 'selected' : ''; ?>>Disabled</option>
                                                    </select>
                                                <?php elseif ($setting['setting_type'] === 'integer'): ?>
                                                    <input type="number" name="settings[<?php echo htmlspecialchars($setting['setting_key']); ?>]" 
                                                           class="form-control" value="<?php echo htmlspecialchars($setting['setting_value']); ?>">
                                                <?php else: ?>
                                                    <input type="text" name="settings[<?php echo htmlspecialchars($setting['setting_key']); ?>]" 
                                                           class="form-control" value="<?php echo htmlspecialchars($setting['setting_value']); ?>">
                                                <?php endif; ?>
                                            </div>
                                        <?php
                                        endwhile;
                                        if ($currentCategory !== ''):
                                            echo '</div></div>';
                                        endif;
                                        ?>
                                        
                                        <div class="mt-4">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="ri-save-line"></i> Save Settings
                                            </button>
                                            <a href="ai_queue.php" class="btn btn-info">
                                                <i class="ri-list-check"></i> View Processing Queue
                                            </a>
                                        </div>
                                    </form>
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
</body>
</html>

<?php
mysqli_close($conn);
?>

