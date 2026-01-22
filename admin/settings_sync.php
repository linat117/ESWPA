<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: auth-login.php");
    exit();
}

include 'include/conn.php';
include 'include/sync_handler.php';
include 'header.php';

// Get sync settings
$sync_enabled = false;
$sync_host = '';
$sync_db = '';
$sync_user = '';
$sync_password = '';

$settings_query = "SELECT setting_key, setting_value FROM settings WHERE category = 'sync'";
$settings_result = $conn->query($settings_query);
while ($setting = $settings_result->fetch_assoc()) {
    if ($setting['setting_key'] == 'sync_enabled') {
        $sync_enabled = $setting['setting_value'] == '1';
    } elseif ($setting['setting_key'] == 'sync_remote_host') {
        $sync_host = $setting['setting_value'];
    } elseif ($setting['setting_key'] == 'sync_remote_db') {
        $sync_db = $setting['setting_value'];
    } elseif ($setting['setting_key'] == 'sync_remote_user') {
        $sync_user = $setting['setting_value'];
    } elseif ($setting['setting_key'] == 'sync_remote_password') {
        $sync_password = $setting['setting_value'];
    }
}

// Handle sync action
$sync_result = null;
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['sync_now'])) {
    $sync_direction = $_POST['sync_direction'] ?? 'pull';
    
    if ($sync_enabled && !empty($sync_host) && !empty($sync_db) && !empty($sync_user) && !empty($sync_password)) {
        $sync_settings = [
            'remote_host' => $sync_host,
            'remote_database' => $sync_db,
            'remote_user' => $sync_user,
            'remote_password' => $sync_password
        ];
        
        $sync_result = performSync($sync_direction, $sync_settings, $_SESSION['user_id']);
    } else {
        $sync_result = [
            'success' => false,
            'message' => 'Sync is not enabled or not fully configured. Please configure all sync settings first (host, database, user, password).'
        ];
    }
}

// Get sync history
$sync_history = getSyncHistory(20);
$sync_stats = getSyncStatistics();
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
                                <h4 class="page-title">Data Synchronization</h4>
                                <a href="settings.php" class="btn btn-secondary">
                                    <i class="ri-arrow-left-line"></i> Back to Settings
                                </a>
                            </div>
                        </div>
                    </div>

                    <?php if ($sync_result): ?>
                    <div class="alert alert-<?php echo $sync_result['success'] ? 'success' : 'warning'; ?> alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($sync_result['message']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Sync Configuration</h4>
                                </div>
                                <div class="card-body">
                                    <div class="alert alert-info">
                                        <strong>Note:</strong> Data synchronization requires proper server configuration and access credentials.
                                        Please configure sync settings in <a href="settings.php?tab=sync">Settings > Data Sync</a> before attempting to sync.
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Sync Status</label>
                                        <div>
                                            <span class="badge bg-<?php echo $sync_enabled ? 'success' : 'danger'; ?>">
                                                <?php echo $sync_enabled ? 'Enabled' : 'Disabled'; ?>
                                            </span>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Remote Host</label>
                                        <div>
                                            <?php echo !empty($sync_host) ? htmlspecialchars($sync_host) : '<span class="text-muted">Not configured</span>'; ?>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Remote Database</label>
                                        <div>
                                            <?php echo !empty($sync_db) ? htmlspecialchars($sync_db) : '<span class="text-muted">Not configured</span>'; ?>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Remote User</label>
                                        <div>
                                            <?php echo !empty($sync_user) ? htmlspecialchars($sync_user) : '<span class="text-muted">Not configured</span>'; ?>
                                        </div>
                                    </div>

                                    <form method="POST" class="mt-4" onsubmit="return confirm('Are you sure you want to sync? This may overwrite existing data. Make sure you have a backup!');">
                                        <div class="mb-3">
                                            <label for="sync_direction" class="form-label">Sync Direction</label>
                                            <select class="form-control" id="sync_direction" name="sync_direction" required>
                                                <option value="pull">Pull from Remote (Remote → Local)</option>
                                                <option value="push">Push to Remote (Local → Remote)</option>
                                            </select>
                                        </div>

                                        <button type="submit" name="sync_now" class="btn btn-primary" <?php echo !$sync_enabled ? 'disabled' : ''; ?>>
                                            <i class="ri-refresh-line"></i> Sync Now
                                        </button>
                                        <a href="settings.php?tab=sync" class="btn btn-secondary">
                                            <i class="ri-settings-3-line"></i> Configure Settings
                                        </a>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sync Statistics -->
                    <div class="row mt-3">
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-body text-center">
                                    <h3 class="text-primary"><?php echo $sync_stats['total_syncs']; ?></h3>
                                    <p class="text-muted mb-0">Total Syncs</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-body text-center">
                                    <h3 class="text-success"><?php echo $sync_stats['successful_syncs']; ?></h3>
                                    <p class="text-muted mb-0">Successful</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-body text-center">
                                    <h3 class="text-danger"><?php echo $sync_stats['failed_syncs']; ?></h3>
                                    <p class="text-muted mb-0">Failed</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sync History -->
                    <div class="row mt-3">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Sync History</h4>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($sync_history)): ?>
                                        <p class="text-muted">No sync history available yet.</p>
                                    <?php else: ?>
                                        <div class="table-responsive">
                                            <table class="table table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>Date</th>
                                                        <th>Direction</th>
                                                        <th>Status</th>
                                                        <th>Tables</th>
                                                        <th>Records</th>
                                                        <th>Duration</th>
                                                        <th>Initiated By</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($sync_history as $log): ?>
                                                        <tr>
                                                            <td><?php echo date('Y-m-d H:i:s', strtotime($log['created_at'])); ?></td>
                                                            <td>
                                                                <span class="badge bg-<?php echo $log['sync_direction'] == 'pull' ? 'info' : 'primary'; ?>">
                                                                    <?php echo strtoupper($log['sync_direction']); ?>
                                                                </span>
                                                            </td>
                                                            <td>
                                                                <?php
                                                                $status_colors = [
                                                                    'completed' => 'success',
                                                                    'failed' => 'danger',
                                                                    'partial' => 'warning',
                                                                    'in_progress' => 'info',
                                                                    'pending' => 'secondary'
                                                                ];
                                                                $color = $status_colors[$log['status']] ?? 'secondary';
                                                                ?>
                                                                <span class="badge bg-<?php echo $color; ?>">
                                                                    <?php echo ucfirst($log['status']); ?>
                                                                </span>
                                                            </td>
                                                            <td><?php echo !empty($log['tables_synced']) ? count(explode(', ', $log['tables_synced'])) : '0'; ?></td>
                                                            <td><?php echo number_format($log['records_synced']); ?></td>
                                                            <td><?php echo $log['duration_seconds'] ? number_format($log['duration_seconds'], 2) . 's' : '-'; ?></td>
                                                            <td><?php echo htmlspecialchars($log['initiated_by_name'] ?? 'System'); ?></td>
                                                            <td>
                                                                <?php if (!empty($log['error_message'])): ?>
                                                                    <button type="button" class="btn btn-sm btn-outline-danger" 
                                                                            data-bs-toggle="tooltip" 
                                                                            title="<?php echo htmlspecialchars($log['error_message']); ?>">
                                                                        <i class="ri-error-warning-line"></i> Error
                                                                    </button>
                                                                <?php endif; ?>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Sync Information</h4>
                                </div>
                                <div class="card-body">
                                    <h5>What gets synced:</h5>
                                    <ul>
                                        <li>Database tables (registrations, events, members, resources, research, etc.)</li>
                                        <li>All data records in synced tables</li>
                                        <li>System settings (if configured)</li>
                                    </ul>

                                    <h5 class="mt-3">Sync Directions:</h5>
                                    <ul>
                                        <li><strong>Pull:</strong> Downloads data from remote server to local (Remote → Local)</li>
                                        <li><strong>Push:</strong> Uploads local data to remote server (Local → Remote)</li>
                                    </ul>

                                    <div class="alert alert-warning mt-3">
                                        <strong>Warning:</strong> Sync operations may overwrite existing data. Always create a backup before syncing.
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
</body>
</html>

