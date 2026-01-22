<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: auth-login.php");
    exit();
}

include 'header.php';
include 'include/conn.php';

// Handle actions
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    $queueId = intval($_GET['id'] ?? 0);
    
    if ($action === 'retry' && $queueId > 0) {
        $query = "UPDATE ai_processing_queue SET status = 'pending', error_message = NULL, attempts = 0 WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $queueId);
        $stmt->execute();
        $stmt->close();
        $success = "Queue item reset for retry";
    } elseif ($action === 'cancel' && $queueId > 0) {
        $query = "UPDATE ai_processing_queue SET status = 'cancelled' WHERE id = ? AND status IN ('pending', 'processing')";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $queueId);
        $stmt->execute();
        $stmt->close();
        $success = "Queue item cancelled";
    } elseif ($action === 'clear_completed') {
        $query = "DELETE FROM ai_processing_queue WHERE status = 'completed'";
        mysqli_query($conn, $query);
        $success = "Completed items cleared";
    } elseif ($action === 'clear_failed') {
        $query = "DELETE FROM ai_processing_queue WHERE status = 'failed'";
        mysqli_query($conn, $query);
        $success = "Failed items cleared";
    }
}

// Get filter
$filterStatus = $_GET['status'] ?? '';

$where = [];
if ($filterStatus) {
    $where[] = "status = '$filterStatus'";
}
$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

$query = "SELECT q.*, p.plugin_name 
          FROM ai_processing_queue q
          LEFT JOIN ai_plugins p ON q.plugin_id = p.id
          $whereClause
          ORDER BY q.created_at DESC
          LIMIT 100";
$result = mysqli_query($conn, $query);
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
                                <h4 class="page-title">AI Processing Queue</h4>
                                <div>
                                    <a href="ai_settings.php" class="btn btn-secondary">
                                        <i class="ri-settings-line"></i> AI Settings
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if (isset($success)): ?>
                        <div class="alert alert-success alert-dismissible fade show">
                            <?php echo htmlspecialchars($success); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Filters -->
                    <div class="row mb-3">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <div class="d-flex gap-2 align-items-center">
                                        <label class="form-label mb-0">Filter by Status:</label>
                                        <a href="?status=" class="btn btn-sm <?php echo $filterStatus === '' ? 'btn-primary' : 'btn-outline-primary'; ?>">All</a>
                                        <a href="?status=pending" class="btn btn-sm <?php echo $filterStatus === 'pending' ? 'btn-warning' : 'btn-outline-warning'; ?>">Pending</a>
                                        <a href="?status=processing" class="btn btn-sm <?php echo $filterStatus === 'processing' ? 'btn-info' : 'btn-outline-info'; ?>">Processing</a>
                                        <a href="?status=completed" class="btn btn-sm <?php echo $filterStatus === 'completed' ? 'btn-success' : 'btn-outline-success'; ?>">Completed</a>
                                        <a href="?status=failed" class="btn btn-sm <?php echo $filterStatus === 'failed' ? 'btn-danger' : 'btn-outline-danger'; ?>">Failed</a>
                                        <div class="ms-auto">
                                            <a href="?action=clear_completed" class="btn btn-sm btn-outline-success" onclick="return confirm('Clear all completed items?')">
                                                Clear Completed
                                            </a>
                                            <a href="?action=clear_failed" class="btn btn-sm btn-outline-danger" onclick="return confirm('Clear all failed items?')">
                                                Clear Failed
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Queue Table -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Processing Queue</h4>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Content</th>
                                                    <th>Process Type</th>
                                                    <th>Plugin</th>
                                                    <th>Priority</th>
                                                    <th>Status</th>
                                                    <th>Attempts</th>
                                                    <th>Error</th>
                                                    <th>Created</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (mysqli_num_rows($result) > 0): ?>
                                                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                                        <tr>
                                                            <td><?php echo $row['id']; ?></td>
                                                            <td>
                                                                <strong><?php echo ucfirst($row['content_type']); ?></strong><br>
                                                                <small>ID: <?php echo $row['content_id']; ?></small>
                                                            </td>
                                                            <td><?php echo ucfirst(str_replace('_', ' ', $row['process_type'])); ?></td>
                                                            <td><?php echo htmlspecialchars($row['plugin_name'] ?? 'Default'); ?></td>
                                                            <td><?php echo $row['priority']; ?></td>
                                                            <td>
                                                                <?php
                                                                $statusColors = [
                                                                    'pending' => 'warning',
                                                                    'processing' => 'info',
                                                                    'completed' => 'success',
                                                                    'failed' => 'danger',
                                                                    'cancelled' => 'secondary'
                                                                ];
                                                                $color = $statusColors[$row['status']] ?? 'secondary';
                                                                ?>
                                                                <span class="badge bg-<?php echo $color; ?>"><?php echo ucfirst($row['status']); ?></span>
                                                            </td>
                                                            <td><?php echo $row['attempts']; ?>/<?php echo $row['max_attempts']; ?></td>
                                                            <td>
                                                                <?php if (!empty($row['error_message'])): ?>
                                                                    <small class="text-danger"><?php echo htmlspecialchars(substr($row['error_message'], 0, 50)); ?>...</small>
                                                                <?php else: ?>
                                                                    <span class="text-muted">-</span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td><?php echo date('M d, Y H:i', strtotime($row['created_at'])); ?></td>
                                                            <td>
                                                                <?php if ($row['status'] === 'failed'): ?>
                                                                    <a href="?action=retry&id=<?php echo $row['id']; ?>" class="btn btn-sm btn-warning" title="Retry">
                                                                        <i class="ri-refresh-line"></i>
                                                                    </a>
                                                                <?php endif; ?>
                                                                <?php if (in_array($row['status'], ['pending', 'processing'])): ?>
                                                                    <a href="?action=cancel&id=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger" title="Cancel" onclick="return confirm('Cancel this item?')">
                                                                        <i class="ri-close-line"></i>
                                                                    </a>
                                                                <?php endif; ?>
                                                            </td>
                                                        </tr>
                                                    <?php endwhile; ?>
                                                <?php else: ?>
                                                    <tr>
                                                        <td colspan="10" class="text-center">No items in queue</td>
                                                    </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
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

<?php
mysqli_close($conn);
?>

