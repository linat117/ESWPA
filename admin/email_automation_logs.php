<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: auth-login.php");
    exit();
}

include 'include/conn.php';
include 'header.php';

// Get filter parameters
$filter_type = $_GET['type'] ?? '';
$filter_status = $_GET['status'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// Build query
$where = [];
$params = [];
$types = '';

if ($filter_type) {
    $where[] = "content_type = ?";
    $params[] = $filter_type;
    $types .= 's';
}

if ($filter_status) {
    $where[] = "status = ?";
    $params[] = $filter_status;
    $types .= 's';
}

if ($date_from) {
    $where[] = "DATE(sent_at) >= ?";
    $params[] = $date_from;
    $types .= 's';
}

if ($date_to) {
    $where[] = "DATE(sent_at) <= ?";
    $params[] = $date_to;
    $types .= 's';
}

$whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

// Get logs
$logsQuery = "SELECT * FROM email_automation_logs $whereClause ORDER BY sent_at DESC LIMIT 100";
$logsStmt = $conn->prepare($logsQuery);
if (!empty($params)) {
    $logsStmt->bind_param($types, ...$params);
}
$logsStmt->execute();
$logsResult = $logsStmt->get_result();
$logs = [];
while ($row = $logsResult->fetch_assoc()) {
    $logs[] = $row;
}
$logsStmt->close();
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
                                <div class="page-title-right">
                                    <ol class="breadcrumb m-0">
                                        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                                        <li class="breadcrumb-item active">Email Automation Logs</li>
                                    </ol>
                                </div>
                                <h4 class="page-title">Email Automation Logs</h4>
                            </div>
                        </div>
                    </div>

                    <!-- Filters -->
                    <div class="row mb-3">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <form method="GET" class="row g-3">
                                        <div class="col-md-3">
                                            <label class="form-label">Content Type</label>
                                            <select class="form-control" name="type">
                                                <option value="">All Types</option>
                                                <option value="news" <?php echo $filter_type == 'news' ? 'selected' : ''; ?>>News</option>
                                                <option value="blog" <?php echo $filter_type == 'blog' ? 'selected' : ''; ?>>Blog</option>
                                                <option value="report" <?php echo $filter_type == 'report' ? 'selected' : ''; ?>>Report</option>
                                                <option value="event" <?php echo $filter_type == 'event' ? 'selected' : ''; ?>>Event</option>
                                                <option value="resource" <?php echo $filter_type == 'resource' ? 'selected' : ''; ?>>Resource</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Status</label>
                                            <select class="form-control" name="status">
                                                <option value="">All Status</option>
                                                <option value="success" <?php echo $filter_status == 'success' ? 'selected' : ''; ?>>Success</option>
                                                <option value="failed" <?php echo $filter_status == 'failed' ? 'selected' : ''; ?>>Failed</option>
                                                <option value="partial" <?php echo $filter_status == 'partial' ? 'selected' : ''; ?>>Partial</option>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label">From Date</label>
                                            <input type="date" class="form-control" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>">
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label">To Date</label>
                                            <input type="date" class="form-control" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>">
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label">&nbsp;</label>
                                            <button type="submit" class="btn btn-primary d-block w-100">Filter</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Logs Table -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped table-bordered" id="logs-datatable">
                                            <thead>
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Content Type</th>
                                                    <th>Title</th>
                                                    <th>Recipients</th>
                                                    <th>Sent</th>
                                                    <th>Failed</th>
                                                    <th>Status</th>
                                                    <th>Sent At</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($logs as $log): ?>
                                                <tr>
                                                    <td><?php echo $log['id']; ?></td>
                                                    <td><span class="badge bg-info"><?php echo ucfirst($log['content_type']); ?></span></td>
                                                    <td><?php echo htmlspecialchars(substr($log['content_title'], 0, 40)) . '...'; ?></td>
                                                    <td><?php echo number_format($log['recipients_count']); ?></td>
                                                    <td><span class="text-success"><?php echo number_format($log['sent_count']); ?></span></td>
                                                    <td><span class="text-danger"><?php echo number_format($log['failed_count']); ?></span></td>
                                                    <td>
                                                        <?php
                                                        $statusClass = $log['status'] == 'success' ? 'success' : ($log['status'] == 'failed' ? 'danger' : 'warning');
                                                        ?>
                                                        <span class="badge bg-<?php echo $statusClass; ?>"><?php echo ucfirst($log['status']); ?></span>
                                                    </td>
                                                    <td><?php echo date('Y-m-d H:i', strtotime($log['sent_at'])); ?></td>
                                                    <td>
                                                        <?php if ($log['error_message']): ?>
                                                        <button class="btn btn-sm btn-info view-error" data-error="<?php echo htmlspecialchars($log['error_message']); ?>">
                                                            <i class="ri-error-warning-line"></i> View Error
                                                        </button>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
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

    <!-- Error Modal -->
    <div class="modal fade" id="errorModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Error Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <pre id="error-content"></pre>
                </div>
            </div>
        </div>
    </div>

    <!-- Vendor js -->
    <script src="assets/js/vendor.min.js"></script>
    
    <!-- Datatables js -->
    <script src="assets/js/jquery.dataTables.min.js"></script>
    <script src="assets/js/dataTables.bootstrap5.min.js"></script>
    
    <!-- App js -->
    <script src="assets/js/app.min.js"></script>

    <script>
        $(document).ready(function() {
            $('#logs-datatable').DataTable({
                scrollX: true,
                order: [[7, 'desc']],
                language: {
                    paginate: {
                        previous: "<i class='mdi mdi-chevron-left'>",
                        next: "<i class='mdi mdi-chevron-right'>"
                    }
                },
                drawCallback: function() {
                    $(".dataTables_paginate > .pagination").addClass("pagination-rounded");
                }
            });

            $('.view-error').on('click', function() {
                const error = $(this).data('error');
                $('#error-content').text(error);
                $('#errorModal').modal('show');
            });
        });
    </script>
</body>
</html>

