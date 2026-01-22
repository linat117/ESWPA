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
    header('Content-Disposition: attachment; filename=notifications_' . date('Y-m-d') . '.csv');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'Member', 'Type', 'Title', 'Message', 'Status', 'Created', 'Read At']);
    
    $table_exists = false;
    $result = $conn->query("SHOW TABLES LIKE 'notifications'");
    if ($result && $result->num_rows > 0) {
        $table_exists = true;
    }
    
    if ($table_exists) {
        $query = "SELECT n.*, r.fullname, r.email 
                  FROM notifications n 
                  LEFT JOIN registrations r ON n.member_id = r.id 
                  ORDER BY n.created_at DESC";
        $result = $conn->query($query);
        
        while ($row = $result->fetch_assoc()) {
            fputcsv($output, [
                $row['id'],
                $row['fullname'] ?? 'Unknown',
                $row['type'],
                $row['title'],
                strip_tags($row['message']),
                $row['is_read'] ? 'Read' : 'Unread',
                $row['created_at'],
                $row['read_at'] ?? ''
            ]);
        }
    }
    
    fclose($output);
    exit();
}

// Check if table exists
$table_exists = false;
$result = $conn->query("SHOW TABLES LIKE 'notifications'");
if ($result && $result->num_rows > 0) {
    $table_exists = true;
}

// Quick stats
$total = 0;
$unread = 0;
$read = 0;

$notifications = [];

if ($table_exists) {
    $totalQuery = "SELECT COUNT(*) as total FROM notifications";
    $unreadQuery = "SELECT COUNT(*) as total FROM notifications WHERE is_read = 0";
    $readQuery = "SELECT COUNT(*) as total FROM notifications WHERE is_read = 1";
    
    $total = mysqli_fetch_assoc(mysqli_query($conn, $totalQuery))['total'] ?? 0;
    $unread = mysqli_fetch_assoc(mysqli_query($conn, $unreadQuery))['total'] ?? 0;
    $read = mysqli_fetch_assoc(mysqli_query($conn, $readQuery))['total'] ?? 0;
    
    // Build query with filters
    $where = [];
    $params = [];
    $types = '';
    
    $statusFilter = $_GET['filter'] ?? '';
    $typeFilter = $_GET['type'] ?? '';
    $memberFilter = $_GET['member'] ?? '';
    $search = $_GET['search'] ?? '';
    $date_from = $_GET['date_from'] ?? '';
    $date_to = $_GET['date_to'] ?? '';
    
    if ($statusFilter == 'unread') {
        $where[] = "n.is_read = 0";
    } elseif ($statusFilter == 'read') {
        $where[] = "n.is_read = 1";
    }
    
    if ($typeFilter) {
        $where[] = "n.type = ?";
        $params[] = $typeFilter;
        $types .= 's';
    }
    
    if ($memberFilter) {
        $where[] = "n.member_id = ?";
        $params[] = $memberFilter;
        $types .= 'i';
    }
    
    if ($search) {
        $where[] = "(n.title LIKE ? OR n.message LIKE ? OR r.fullname LIKE ? OR r.email LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $types .= 'ssss';
    }
    
    if ($date_from) {
        $where[] = "DATE(n.created_at) >= ?";
        $params[] = $date_from;
        $types .= 's';
    }
    
    if ($date_to) {
        $where[] = "DATE(n.created_at) <= ?";
        $params[] = $date_to;
        $types .= 's';
    }
    
    $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
    
    $query = "SELECT n.*, r.fullname, r.email 
             FROM notifications n 
             LEFT JOIN registrations r ON n.member_id = r.id 
             $whereClause
             ORDER BY n.created_at DESC";
    
    $stmt = $conn->prepare($query);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $notifications[] = $row;
    }
    $stmt->close();
    
    // Get notification types for filter
    $types_result = $conn->query("SELECT DISTINCT type FROM notifications ORDER BY type");
    $notification_types = [];
    while ($row = $types_result->fetch_assoc()) {
        $notification_types[] = $row['type'];
    }
}

// Get notification for viewing
$view_notification = null;
if (isset($_GET['view']) && $table_exists) {
    $view_id = intval($_GET['view']);
    $stmt = $conn->prepare("SELECT n.*, r.fullname, r.email 
                           FROM notifications n 
                           LEFT JOIN registrations r ON n.member_id = r.id 
                           WHERE n.id = ?");
    $stmt->bind_param("i", $view_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $view_notification = $result->fetch_assoc();
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
                                <h4 class="page-title">Notifications</h4>
                                <div>
                                    <a href="notifications_center.php" class="btn btn-secondary me-2">
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
                        <div class="alert alert-warning" role="alert">
                            <h5 class="alert-heading"><i class="ri-alert-line"></i> Notifications Table Not Found</h5>
                            <p>The <code>notifications</code> table does not exist in the database.</p>
                            <p class="mb-0">See <a href="notifications_center.php">Notifications Center</a> for the required table structure.</p>
                        </div>
                    <?php else: ?>

                    <!-- Quick Stats -->
                    <div class="row g-2 mb-3">
                        <div class="col-md-4">
                            <div class="card bg-primary bg-opacity-10 border-primary">
                                <div class="card-body p-2">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-grow-1">
                                            <h6 class="mb-0 text-muted small">Total</h6>
                                            <h4 class="mb-0"><?php echo number_format($total); ?></h4>
                                        </div>
                                        <i class="ri-notification-line fs-1 text-primary opacity-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-warning bg-opacity-10 border-warning">
                                <div class="card-body p-2">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-grow-1">
                                            <h6 class="mb-0 text-muted small">Unread</h6>
                                            <h4 class="mb-0"><?php echo number_format($unread); ?></h4>
                                        </div>
                                        <i class="ri-mail-unread-line fs-1 text-warning opacity-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-success bg-opacity-10 border-success">
                                <div class="card-body p-2">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-grow-1">
                                            <h6 class="mb-0 text-muted small">Read</h6>
                                            <h4 class="mb-0"><?php echo number_format($read); ?></h4>
                                        </div>
                                        <i class="ri-mail-open-line fs-1 text-success opacity-50"></i>
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
                                            <div class="col-md-3">
                                                <label class="form-label small">Search</label>
                                                <input type="text" name="search" class="form-control form-control-sm" 
                                                       value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>" 
                                                       placeholder="Search notifications...">
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label small">Status</label>
                                                <select name="filter" class="form-select form-select-sm">
                                                    <option value="">All</option>
                                                    <option value="unread" <?php echo ($_GET['filter'] ?? '') == 'unread' ? 'selected' : ''; ?>>Unread</option>
                                                    <option value="read" <?php echo ($_GET['filter'] ?? '') == 'read' ? 'selected' : ''; ?>>Read</option>
                                                </select>
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label small">Type</label>
                                                <select name="type" class="form-select form-select-sm">
                                                    <option value="">All Types</option>
                                                    <?php foreach ($notification_types as $type): ?>
                                                        <option value="<?php echo htmlspecialchars($type); ?>" <?php echo ($_GET['type'] ?? '') == $type ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $type))); ?>
                                                        </option>
                                                    <?php endforeach; ?>
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
                                            <div class="col-md-1 d-flex align-items-end gap-2">
                                                <button type="submit" class="btn btn-sm btn-primary w-100">
                                                    <i class="ri-filter-line"></i> Filter
                                                </button>
                                            </div>
                                        </form>
                                        <div class="row mt-2">
                                            <div class="col-md-12">
                                                <a href="notifications_list.php" class="btn btn-sm btn-secondary">
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

                    <!-- Notifications Table -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table id="notificationsTable" class="table table-striped table-bordered dt-responsive nowrap w-100">
                                            <thead>
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Member</th>
                                                    <th>Type</th>
                                                    <th>Title</th>
                                                    <th>Status</th>
                                                    <th>Created</th>
                                                    <th>Read At</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($notifications as $notif): ?>
                                                    <tr class="<?php echo !$notif['is_read'] ? 'table-warning' : ''; ?>">
                                                        <td><?php echo $notif['id']; ?></td>
                                                        <td><strong><?php echo htmlspecialchars($notif['fullname'] ?? 'Unknown'); ?></strong></td>
                                                        <td>
                                                            <span class="badge bg-info"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $notif['type']))); ?></span>
                                                        </td>
                                                        <td><?php echo htmlspecialchars($notif['title']); ?></td>
                                                        <td>
                                                            <span class="badge bg-<?php echo $notif['is_read'] ? 'success' : 'warning'; ?>">
                                                                <?php echo $notif['is_read'] ? 'Read' : 'Unread'; ?>
                                                            </span>
                                                        </td>
                                                        <td><?php echo date('M d, Y H:i', strtotime($notif['created_at'])); ?></td>
                                                        <td><?php echo $notif['read_at'] ? date('M d, Y H:i', strtotime($notif['read_at'])) : '-'; ?></td>
                                                        <td>
                                                            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#viewNotificationModal<?php echo $notif['id']; ?>">
                                                                <i class="ri-eye-line"></i> View
                                                            </button>
                                                        </td>
                                                    </tr>

                                                    <!-- View Notification Modal -->
                                                    <div class="modal fade" id="viewNotificationModal<?php echo $notif['id']; ?>" tabindex="-1">
                                                        <div class="modal-dialog modal-lg">
                                                            <div class="modal-content">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title">Notification Details</h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    <div class="mb-3">
                                                                        <strong>Member:</strong> <?php echo htmlspecialchars($notif['fullname'] ?? 'Unknown'); ?>
                                                                        <?php if ($notif['email']): ?>
                                                                            <br><small class="text-muted"><?php echo htmlspecialchars($notif['email']); ?></small>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                    <div class="mb-3">
                                                                        <strong>Type:</strong> 
                                                                        <span class="badge bg-info"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $notif['type']))); ?></span>
                                                                    </div>
                                                                    <div class="mb-3">
                                                                        <strong>Status:</strong> 
                                                                        <span class="badge bg-<?php echo $notif['is_read'] ? 'success' : 'warning'; ?>">
                                                                            <?php echo $notif['is_read'] ? 'Read' : 'Unread'; ?>
                                                                        </span>
                                                                    </div>
                                                                    <div class="mb-3">
                                                                        <strong>Title:</strong>
                                                                        <p><?php echo htmlspecialchars($notif['title']); ?></p>
                                                                    </div>
                                                                    <div class="mb-3">
                                                                        <strong>Message:</strong>
                                                                        <div class="mt-2 p-3 bg-light rounded">
                                                                            <?php echo nl2br(htmlspecialchars($notif['message'])); ?>
                                                                        </div>
                                                                    </div>
                                                                    <div class="mb-3">
                                                                        <strong>Created:</strong> <?php echo date('M d, Y H:i:s', strtotime($notif['created_at'])); ?>
                                                                    </div>
                                                                    <?php if ($notif['read_at']): ?>
                                                                    <div class="mb-3">
                                                                        <strong>Read At:</strong> <?php echo date('M d, Y H:i:s', strtotime($notif['read_at'])); ?>
                                                                    </div>
                                                                    <?php endif; ?>
                                                                    <?php if ($notif['related_type'] && $notif['related_id']): ?>
                                                                    <div class="mb-3">
                                                                        <strong>Related:</strong> <?php echo htmlspecialchars($notif['related_type']); ?> #<?php echo $notif['related_id']; ?>
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
            $('#notificationsTable').DataTable({
                responsive: true,
                order: [[5, 'desc']],
                pageLength: 25,
                language: {
                    search: "_INPUT_",
                    searchPlaceholder: "Search notifications..."
                }
            });
        });
    </script>

</body>
</html>

