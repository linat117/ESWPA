<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: auth-login.php");
    exit();
}

include 'include/conn.php';
include 'header.php';

// Handle backup creation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_backup'])) {
    $backup_type = $_POST['backup_type'];
    $backup_file = createBackup($conn, $backup_type);
    
    if ($backup_file) {
        $file_size = filesize($backup_file);
        $query = "INSERT INTO backups (backup_type, file_path, file_size, status, created_by) 
                  VALUES (?, ?, ?, 'completed', ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssii", $backup_type, $backup_file, $file_size, $_SESSION['user_id']);
        $stmt->execute();
        $stmt->close();
        
        header("Location: settings_backup.php?success=Backup created successfully");
        exit();
    } else {
        header("Location: settings_backup.php?error=Failed to create backup");
        exit();
    }
}

// Handle restore
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['restore_backup'])) {
    $backup_id = intval($_POST['backup_id']);
    $backup_query = "SELECT file_path, backup_type FROM backups WHERE id = ?";
    $stmt = $conn->prepare($backup_query);
    $stmt->bind_param("i", $backup_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $backup = $result->fetch_assoc();
    $stmt->close();
    
    if ($backup && file_exists($backup['file_path'])) {
        $restored = restoreBackup($conn, $backup['file_path'], $backup['backup_type']);
        if ($restored) {
            header("Location: settings_backup.php?success=Backup restored successfully");
            exit();
        }
    }
    header("Location: settings_backup.php?error=Failed to restore backup");
    exit();
}

function createBackup($conn, $type) {
    $backup_dir = '../../backups/';
    if (!file_exists($backup_dir)) {
        mkdir($backup_dir, 0777, true);
    }
    
    $filename = $backup_dir . 'backup_' . $type . '_' . date('Y-m-d_H-i-s') . '.sql';
    
    if ($type == 'database' || $type == 'full') {
        // Database backup
        $tables = [];
        $result = $conn->query("SHOW TABLES");
        while ($row = $result->fetch_array()) {
            $tables[] = $row[0];
        }
        
        $sql = "-- Database Backup\n";
        $sql .= "-- Generated: " . date('Y-m-d H:i:s') . "\n\n";
        
        foreach ($tables as $table) {
            $sql .= "DROP TABLE IF EXISTS `$table`;\n";
            $create = $conn->query("SHOW CREATE TABLE `$table`");
            $create_row = $create->fetch_array();
            $sql .= $create_row[1] . ";\n\n";
            
            $result = $conn->query("SELECT * FROM `$table`");
            while ($row = $result->fetch_assoc()) {
                $sql .= "INSERT INTO `$table` VALUES (";
                $values = [];
                foreach ($row as $value) {
                    $values[] = "'" . $conn->real_escape_string($value) . "'";
                }
                $sql .= implode(',', $values) . ");\n";
            }
            $sql .= "\n";
        }
        
        file_put_contents($filename, $sql);
        return $filename;
    }
    
    return false;
}

function restoreBackup($conn, $file_path, $type) {
    if (!file_exists($file_path)) {
        return false;
    }
    
    $sql = file_get_contents($file_path);
    $queries = explode(';', $sql);
    
    foreach ($queries as $query) {
        $query = trim($query);
        if (!empty($query)) {
            $conn->query($query);
        }
    }
    
    return true;
}

// Get all backups
$backups_query = "SELECT b.*, u.username as created_by_name 
                  FROM backups b 
                  LEFT JOIN user u ON b.created_by = u.id 
                  ORDER BY b.created_at DESC";
$backups_result = $conn->query($backups_query);
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
                                <h4 class="page-title">Backup & Restore</h4>
                                <a href="settings.php" class="btn btn-secondary">
                                    <i class="ri-arrow-left-line"></i> Back to Settings
                                </a>
                            </div>
                        </div>
                    </div>

                    <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($_GET['success']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>

                    <?php if (isset($_GET['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($_GET['error']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>

                    <!-- Create Backup -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Create Backup</h4>
                                </div>
                                <div class="card-body">
                                    <form method="POST">
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="backup_type" class="form-label">Backup Type</label>
                                                <select class="form-control" id="backup_type" name="backup_type" required>
                                                    <option value="database">Database Only</option>
                                                    <option value="full">Full Backup</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6 mb-3 d-flex align-items-end">
                                                <button type="submit" name="create_backup" class="btn btn-primary">
                                                    <i class="ri-database-2-line"></i> Create Backup
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Backups List -->
                    <div class="row mt-3">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Backup History</h4>
                                </div>
                                <div class="card-body">
                                    <table class="table table-striped datatable">
                                        <thead>
                                            <tr>
                                                <th>Type</th>
                                                <th>File</th>
                                                <th>Size</th>
                                                <th>Status</th>
                                                <th>Created</th>
                                                <th>Created By</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($backup = $backups_result->fetch_assoc()): ?>
                                            <tr>
                                                <td><span class="badge bg-info"><?php echo ucfirst($backup['backup_type']); ?></span></td>
                                                <td><?php echo htmlspecialchars(basename($backup['file_path'])); ?></td>
                                                <td><?php echo $backup['file_size'] ? number_format($backup['file_size'] / 1024, 2) . ' KB' : 'N/A'; ?></td>
                                                <td>
                                                    <span class="badge bg-<?php echo $backup['status'] == 'completed' ? 'success' : ($backup['status'] == 'failed' ? 'danger' : 'warning'); ?>">
                                                        <?php echo ucfirst($backup['status']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo date('M d, Y H:i', strtotime($backup['created_at'])); ?></td>
                                                <td><?php echo htmlspecialchars($backup['created_by_name'] ?? 'System'); ?></td>
                                                <td>
                                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to restore this backup? This will overwrite current data.');">
                                                        <input type="hidden" name="backup_id" value="<?php echo $backup['id']; ?>">
                                                        <button type="submit" name="restore_backup" class="btn btn-sm btn-warning">
                                                            <i class="ri-refresh-line"></i> Restore
                                                        </button>
                                                    </form>
                                                    <a href="<?php echo $backup['file_path']; ?>" class="btn btn-sm btn-primary" download>
                                                        <i class="ri-download-line"></i> Download
                                                    </a>
                                                </td>
                                            </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
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
    <script src="assets/vendor/datatables.net/js/jquery.dataTables.min.js"></script>
    <script src="assets/vendor/datatables.net-bs5/js/dataTables.bootstrap5.min.js"></script>
    <script src="assets/js/app.min.js"></script>

    <script>
        $(document).ready(function() {
            $('.datatable').DataTable({
                responsive: true,
                order: [[4, 'desc']]
            });
        });
    </script>
</body>
</html>

