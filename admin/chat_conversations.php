<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: auth-login.php");
    exit();
}

include 'header.php';
include 'include/conn.php';

// Handle export
if (isset($_GET['export']) && $_GET['export'] == 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=chat_messages_' . date('Y-m-d') . '.csv');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'User Name', 'Email', 'Phone', 'Message', 'Status', 'Created']);
    
    $table_exists = false;
    $result = $conn->query("SHOW TABLES LIKE 'telegram_messages'");
    if ($result && $result->num_rows > 0) {
        $table_exists = true;
    }
    
    if ($table_exists) {
        $query = "SELECT * FROM telegram_messages ORDER BY created_at DESC";
        $result = $conn->query($query);
        
        while ($row = $result->fetch_assoc()) {
            fputcsv($output, [
                $row['id'],
                $row['user_name'] ?? '',
                $row['user_email'] ?? '',
                $row['user_phone'] ?? '',
                $row['message'],
                $row['status'],
                $row['created_at']
            ]);
        }
    }
    
    fclose($output);
    exit();
}

// Check if table exists
$table_exists = false;
$result = $conn->query("SHOW TABLES LIKE 'telegram_messages'");
if ($result && $result->num_rows > 0) {
    $table_exists = true;
}

// Quick stats
$total = 0;
$sent = 0;
$failed = 0;
$pending = 0;

$messages = [];

if ($table_exists) {
    $totalQuery = "SELECT COUNT(*) as total FROM telegram_messages";
    $sentQuery = "SELECT COUNT(*) as total FROM telegram_messages WHERE status = 'sent'";
    $failedQuery = "SELECT COUNT(*) as total FROM telegram_messages WHERE status = 'failed'";
    $pendingQuery = "SELECT COUNT(*) as total FROM telegram_messages WHERE status = 'pending'";
    
    $total = mysqli_fetch_assoc(mysqli_query($conn, $totalQuery))['total'] ?? 0;
    $sent = mysqli_fetch_assoc(mysqli_query($conn, $sentQuery))['total'] ?? 0;
    $failed = mysqli_fetch_assoc(mysqli_query($conn, $failedQuery))['total'] ?? 0;
    $pending = mysqli_fetch_assoc(mysqli_query($conn, $pendingQuery))['total'] ?? 0;
    
    // Build query with filters
    $where = [];
    $params = [];
    $types = '';
    
    $statusFilter = $_GET['status'] ?? '';
    $search = $_GET['search'] ?? '';
    $date_from = $_GET['date_from'] ?? '';
    $date_to = $_GET['date_to'] ?? '';
    
    if ($statusFilter) {
        $where[] = "status = ?";
        $params[] = $statusFilter;
        $types .= 's';
    }
    
    if ($search) {
        $where[] = "(user_name LIKE ? OR user_email LIKE ? OR message LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $types .= 'sss';
    }
    
    if ($date_from) {
        $where[] = "DATE(created_at) >= ?";
        $params[] = $date_from;
        $types .= 's';
    }
    
    if ($date_to) {
        $where[] = "DATE(created_at) <= ?";
        $params[] = $date_to;
        $types .= 's';
    }
    
    $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
    
    $query = "SELECT * FROM telegram_messages $whereClause ORDER BY created_at DESC";
    
    $stmt = $conn->prepare($query);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $messages[] = $row;
    }
    $stmt->close();
}

// Get message for viewing
$view_message = null;
if (isset($_GET['view']) && $table_exists) {
    $view_id = intval($_GET['view']);
    $stmt = $conn->prepare("SELECT * FROM telegram_messages WHERE id = ?");
    $stmt->bind_param("i", $view_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $view_message = $result->fetch_assoc();
    }
    $stmt->close();
}
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
                            <div class="page-title-box d-flex justify-content-between align-items-center mb-3">
                                <h4 class="page-title">Chat Conversations</h4>
                                <div>
                                    <a href="chat_dashboard.php" class="btn btn-secondary me-2">
                                        <i class="ri-dashboard-line"></i> Dashboard
                                    </a>
                                    <?php if ($table_exists): ?>
                                        <a href="?export=csv" class="btn btn-success">
                                            <i class="ri-download-line"></i> Export CSV
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if (!$table_exists): ?>
                        <!-- Table Not Found Alert -->
                        <div class="alert alert-info" role="alert">
                            <h5 class="alert-heading"><i class="ri-information-line"></i> Telegram Messages Table Not Found</h5>
                            <p>The <code>telegram_messages</code> table does not exist. This is optional - the chat system works without it.</p>
                            <p class="mb-0">See <a href="chat_dashboard.php">Chat Dashboard</a> for the optional table structure.</p>
                        </div>
                    <?php else: ?>

                    <!-- Quick Stats -->
                    <div class="row g-2 mb-3">
                        <div class="col-md-3">
                            <div class="card bg-primary bg-opacity-10 border-primary">
                                <div class="card-body p-2">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-grow-1">
                                            <h6 class="mb-0 text-muted small">Total Messages</h6>
                                            <h4 class="mb-0"><?php echo number_format($total); ?></h4>
                                        </div>
                                        <i class="ri-message-3-line fs-1 text-primary opacity-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-success bg-opacity-10 border-success">
                                <div class="card-body p-2">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-grow-1">
                                            <h6 class="mb-0 text-muted small">Sent</h6>
                                            <h4 class="mb-0"><?php echo number_format($sent); ?></h4>
                                        </div>
                                        <i class="ri-checkbox-circle-line fs-1 text-success opacity-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-danger bg-opacity-10 border-danger">
                                <div class="card-body p-2">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-grow-1">
                                            <h6 class="mb-0 text-muted small">Failed</h6>
                                            <h4 class="mb-0"><?php echo number_format($failed); ?></h4>
                                        </div>
                                        <i class="ri-close-circle-line fs-1 text-danger opacity-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-warning bg-opacity-10 border-warning">
                                <div class="card-body p-2">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-grow-1">
                                            <h6 class="mb-0 text-muted small">Pending</h6>
                                            <h4 class="mb-0"><?php echo number_format($pending); ?></h4>
                                        </div>
                                        <i class="ri-time-line fs-1 text-warning opacity-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Filters -->
                    <div class="row mb-3">
                        <div class="col-12">
                            <div class="card border">
                                <div class="card-body">
                                    <h6 class="card-title mb-3 d-flex justify-content-between align-items-center">
                                        <span><i class="ri-filter-line"></i> Filters & Search</span>
                                        <button class="btn btn-sm btn-link" type="button" data-bs-toggle="collapse" data-bs-target="#filterCollapse">
                                            <i class="ri-arrow-down-s-line"></i> Toggle
                                        </button>
                                    </h6>
                                    <div class="collapse show" id="filterCollapse">
                                        <form method="GET" class="row g-3">
                                            <div class="col-md-4">
                                                <label class="form-label small">Search (Name/Email/Message)</label>
                                                <input type="text" name="search" class="form-control form-control-sm" 
                                                       value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>" 
                                                       placeholder="Search messages...">
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label small">Status</label>
                                                <select name="status" class="form-select form-select-sm">
                                                    <option value="">All Status</option>
                                                    <option value="sent" <?php echo ($_GET['status'] ?? '') == 'sent' ? 'selected' : ''; ?>>Sent</option>
                                                    <option value="failed" <?php echo ($_GET['status'] ?? '') == 'failed' ? 'selected' : ''; ?>>Failed</option>
                                                    <option value="pending" <?php echo ($_GET['status'] ?? '') == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                </select>
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label small">Date From</label>
                                                <input type="date" name="date_from" class="form-control form-control-sm" 
                                                       value="<?php echo htmlspecialchars($_GET['date_from'] ?? ''); ?>">
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label small">Date To</label>
                                                <input type="date" name="date_to" class="form-control form-control-sm" 
                                                       value="<?php echo htmlspecialchars($_GET['date_to'] ?? ''); ?>">
                                            </div>
                                            <div class="col-md-2 d-flex align-items-end gap-2">
                                                <button type="submit" class="btn btn-sm btn-primary w-100">
                                                    <i class="ri-filter-line"></i> Filter
                                                </button>
                                            </div>
                                        </form>
                                        <div class="row mt-2">
                                            <div class="col-md-12">
                                                <a href="chat_conversations.php" class="btn btn-sm btn-secondary">
                                                    <i class="ri-refresh-line"></i> Reset Filters
                                                </a>
                                                <a href="?export=csv" class="btn btn-sm btn-success">
                                                    <i class="ri-download-line"></i> Export CSV
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Messages Table -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table id="messagesTable" class="table table-striped table-bordered dt-responsive nowrap w-100">
                                            <thead>
                                                <tr>
                                                    <th>ID</th>
                                                    <th>User Name</th>
                                                    <th>Email</th>
                                                    <th>Phone</th>
                                                    <th>Message</th>
                                                    <th>Status</th>
                                                    <th>Created</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($messages as $msg): ?>
                                                    <tr>
                                                        <td><?php echo $msg['id']; ?></td>
                                                        <td><strong><?php echo htmlspecialchars($msg['user_name'] ?? 'Anonymous'); ?></strong></td>
                                                        <td><?php echo htmlspecialchars($msg['user_email'] ?? '-'); ?></td>
                                                        <td><?php echo htmlspecialchars($msg['user_phone'] ?? '-'); ?></td>
                                                        <td><?php echo htmlspecialchars(substr($msg['message'], 0, 80)) . (strlen($msg['message']) > 80 ? '...' : ''); ?></td>
                                                        <td>
                                                            <span class="badge bg-<?php 
                                                                echo $msg['status'] == 'sent' ? 'success' : 
                                                                    ($msg['status'] == 'failed' ? 'danger' : 'warning'); 
                                                            ?>">
                                                                <?php echo ucfirst($msg['status']); ?>
                                                            </span>
                                                        </td>
                                                        <td><?php echo date('M d, Y H:i', strtotime($msg['created_at'])); ?></td>
                                                        <td>
                                                            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#viewMessageModal<?php echo $msg['id']; ?>">
                                                                <i class="ri-eye-line"></i> View
                                                            </button>
                                                        </td>
                                                    </tr>

                                                    <!-- View Message Modal -->
                                                    <div class="modal fade" id="viewMessageModal<?php echo $msg['id']; ?>" tabindex="-1">
                                                        <div class="modal-dialog modal-lg">
                                                            <div class="modal-content">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title">Message Details</h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    <div class="mb-3">
                                                                        <strong>User Name:</strong> <?php echo htmlspecialchars($msg['user_name'] ?? 'Anonymous'); ?>
                                                                    </div>
                                                                    <?php if ($msg['user_email']): ?>
                                                                    <div class="mb-3">
                                                                        <strong>Email:</strong> <?php echo htmlspecialchars($msg['user_email']); ?>
                                                                    </div>
                                                                    <?php endif; ?>
                                                                    <?php if ($msg['user_phone']): ?>
                                                                    <div class="mb-3">
                                                                        <strong>Phone:</strong> <?php echo htmlspecialchars($msg['user_phone']); ?>
                                                                    </div>
                                                                    <?php endif; ?>
                                                                    <div class="mb-3">
                                                                        <strong>Status:</strong> 
                                                                        <span class="badge bg-<?php 
                                                                            echo $msg['status'] == 'sent' ? 'success' : 
                                                                                ($msg['status'] == 'failed' ? 'danger' : 'warning'); 
                                                                        ?>">
                                                                            <?php echo ucfirst($msg['status']); ?>
                                                                        </span>
                                                                    </div>
                                                                    <div class="mb-3">
                                                                        <strong>Message:</strong>
                                                                        <div class="mt-2 p-3 bg-light rounded">
                                                                            <?php echo nl2br(htmlspecialchars($msg['message'])); ?>
                                                                        </div>
                                                                    </div>
                                                                    <div class="mb-3">
                                                                        <strong>Created:</strong> <?php echo date('M d, Y H:i:s', strtotime($msg['created_at'])); ?>
                                                                    </div>
                                                                    <?php if ($msg['telegram_message_id']): ?>
                                                                    <div class="mb-3">
                                                                        <strong>Telegram Message ID:</strong> <?php echo htmlspecialchars($msg['telegram_message_id']); ?>
                                                                    </div>
                                                                    <?php endif; ?>
                                                                    <?php if ($msg['ip_address']): ?>
                                                                    <div class="mb-3">
                                                                        <strong>IP Address:</strong> <?php echo htmlspecialchars($msg['ip_address']); ?>
                                                                    </div>
                                                                    <?php endif; ?>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php endif; ?>

                </div>
            </div>

            <?php include 'footer.php'; ?>
        </div>
    </div>

    <!-- Vendor js -->
    <script src="assets/js/vendor.min.js"></script>
    
    <!-- DataTables js -->
    <script src="assets/vendor/datatables.net/js/jquery.dataTables.min.js"></script>
    <script src="assets/vendor/datatables.net-bs5/js/dataTables.bootstrap5.min.js"></script>
    <script src="assets/vendor/datatables.net-responsive/js/dataTables.responsive.min.js"></script>
    <script src="assets/vendor/datatables.net-responsive-bs5/js/responsive.bootstrap5.min.js"></script>
    
    <!-- App js -->
    <script src="assets/js/app.min.js"></script>

    <script>
        $(document).ready(function() {
            $('#messagesTable').DataTable({
                responsive: true,
                order: [[6, 'desc']],
                pageLength: 25,
                language: {
                    search: "_INPUT_",
                    searchPlaceholder: "Search messages..."
                }
            });
        });
    </script>

</body>
</html>

