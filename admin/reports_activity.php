<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: auth-login.php");
    exit();
}

include 'include/conn.php';
include 'header.php';

// Check if audit_logs table exists
$audit_table_exists = false;
$access_logs_exists = false;
$member_access_exists = false;

$result = $conn->query("SHOW TABLES LIKE 'audit_logs'");
if ($result && $result->num_rows > 0) {
    $audit_table_exists = true;
}
$result = $conn->query("SHOW TABLES LIKE 'access_logs'");
if ($result && $result->num_rows > 0) {
    $access_logs_exists = true;
}
$result = $conn->query("SHOW TABLES LIKE 'member_access'");
if ($result && $result->num_rows > 0) {
    $member_access_exists = true;
}

// Handle CSV export
if (isset($_GET['export']) && $_GET['export'] == 'csv' && $audit_table_exists) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=activity_reports_' . date('Y-m-d') . '.csv');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'User ID', 'User Type', 'Action', 'Table Name', 'Record ID', 'IP Address', 'Date']);
    
    $start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
    $end_date = $_GET['end_date'] ?? date('Y-m-d');
    $start_datetime = $start_date . ' 00:00:00';
    $end_datetime = $end_date . ' 23:59:59';
    
    $stmt = $conn->prepare("SELECT id, user_id, user_type, action, table_name, record_id, ip_address, created_at 
                           FROM audit_logs 
                           WHERE created_at BETWEEN ? AND ? 
                           ORDER BY created_at DESC 
                           LIMIT 1000");
    $stmt->bind_param("ss", $start_datetime, $end_datetime);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, [
            $row['id'],
            $row['user_id'] ?? '',
            $row['user_type'],
            $row['action'],
            $row['table_name'] ?? '',
            $row['record_id'] ?? '',
            $row['ip_address'] ?? '',
            $row['created_at']
        ]);
    }
    
    fclose($output);
    exit();
}

// Get date range filter
$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$end_date = $_GET['end_date'] ?? date('Y-m-d');

// Sanitize dates
$start_date = date('Y-m-d', strtotime($start_date));
$end_date = date('Y-m-d', strtotime($end_date));

// Validate dates
if (!$start_date || !$end_date || $start_date === '1970-01-01' || $end_date === '1970-01-01') {
    $start_date = date('Y-m-d', strtotime('-30 days'));
    $end_date = date('Y-m-d');
}

$start_datetime = $start_date . ' 00:00:00';
$end_datetime = $end_date . ' 23:59:59';

// Statistics
$stats = [];

if ($audit_table_exists) {
    // Total actions
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM audit_logs WHERE created_at BETWEEN ? AND ?");
    $stmt->bind_param("ss", $start_datetime, $end_datetime);
    $stmt->execute();
    $result = $stmt->get_result();
    $stats['total_actions'] = $result->fetch_assoc()['total'] ?? 0;
    $stmt->close();

    // Actions by type
    $action_breakdown = [];
    $stmt = $conn->prepare("SELECT action, COUNT(*) as count FROM audit_logs WHERE created_at BETWEEN ? AND ? GROUP BY action ORDER BY count DESC LIMIT 20");
    $stmt->bind_param("ss", $start_datetime, $end_datetime);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $action_breakdown[] = $row;
    }
    $stmt->close();

    // Actions by user type
    $user_type_breakdown = [];
    $stmt = $conn->prepare("SELECT user_type, COUNT(*) as count FROM audit_logs WHERE created_at BETWEEN ? AND ? GROUP BY user_type ORDER BY count DESC");
    $stmt->bind_param("ss", $start_datetime, $end_datetime);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $user_type_breakdown[] = $row;
    }
    $stmt->close();

    // Actions by table
    $table_breakdown = [];
    $stmt = $conn->prepare("SELECT table_name, COUNT(*) as count FROM audit_logs WHERE created_at BETWEEN ? AND ? AND table_name IS NOT NULL GROUP BY table_name ORDER BY count DESC LIMIT 15");
    $stmt->bind_param("ss", $start_datetime, $end_datetime);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $table_breakdown[] = $row;
    }
    $stmt->close();

    // Top users
    $top_users = [];
    $stmt = $conn->prepare("SELECT user_id, user_type, COUNT(*) as count FROM audit_logs WHERE created_at BETWEEN ? AND ? AND user_id IS NOT NULL GROUP BY user_id, user_type ORDER BY count DESC LIMIT 10");
    $stmt->bind_param("ss", $start_datetime, $end_datetime);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $top_users[] = $row;
    }
    $stmt->close();

    // Monthly trend (last 12 months)
    $monthly_trend = [];
    $months = [];
    for ($i = 11; $i >= 0; $i--) {
        $month = date('Y-m', strtotime("-$i months"));
        $months[] = date('M Y', strtotime("-$i months"));
        $monthly_trend[$month] = 0;
    }

    $monthly_query = "SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(id) as count 
                      FROM audit_logs 
                      WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH) 
                      GROUP BY month ORDER BY month ASC";
    $monthly_result = $conn->query($monthly_query);
    while ($row = $monthly_result->fetch_assoc()) {
        if (isset($monthly_trend[$row['month']])) {
            $monthly_trend[$row['month']] = $row['count'];
        }
    }

    // Recent activities (user table uses 'username', not 'user_name')
    $recent_activities = [];
    $stmt = $conn->prepare("SELECT al.id, al.user_id, al.user_type, al.action, al.table_name, al.record_id, al.ip_address, al.created_at,
                           u.username as admin_name
                           FROM audit_logs al
                           LEFT JOIN user u ON al.user_id = u.id AND al.user_type = 'admin'
                           WHERE al.created_at BETWEEN ? AND ?
                           ORDER BY al.created_at DESC 
                           LIMIT 100");
    if ($stmt) {
        $stmt->bind_param("ss", $start_datetime, $end_datetime);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $recent_activities[] = $row;
            }
        }
        $stmt->close();
    }
} else {
    $stats['total_actions'] = 0;
    $action_breakdown = [];
    $user_type_breakdown = [];
    $table_breakdown = [];
    $top_users = [];
    $monthly_trend = [];
    $months = [];
    $recent_activities = [];
}

// Member login activity (from member_access table)
$member_login_stats = ['unique_users' => 0, 'total_logins' => 0];
if ($member_access_exists) {
    $stmt = @$conn->prepare("SELECT COUNT(DISTINCT member_id) as unique_users, COUNT(*) as total_logins 
                             FROM member_access 
                             WHERE last_login BETWEEN ? AND ?");
    if ($stmt) {
        $stmt->bind_param("ss", $start_datetime, $end_datetime);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result) {
            $member_login_stats = $result->fetch_assoc() ?: $member_login_stats;
        }
        $stmt->close();
    }
}

// Access logs statistics
$access_logs_stats = ['total' => 0, 'granted' => 0, 'denied' => 0];
if ($access_logs_exists) {
    $stmt = @$conn->prepare("SELECT COUNT(*) as total, SUM(CASE WHEN access_granted = 1 THEN 1 ELSE 0 END) as granted, SUM(CASE WHEN access_granted = 0 THEN 1 ELSE 0 END) as denied 
                             FROM access_logs 
                             WHERE accessed_at BETWEEN ? AND ?");
    if ($stmt) {
        $stmt->bind_param("ss", $start_datetime, $end_datetime);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result) {
            $row = $result->fetch_assoc();
            if ($row) {
                $access_logs_stats = ['total' => (int)($row['total'] ?? 0), 'granted' => (int)($row['granted'] ?? 0), 'denied' => (int)($row['denied'] ?? 0)];
            }
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<!-- ApexCharts css -->
<link href="assets/vendor/apexcharts/apexcharts.css" rel="stylesheet" type="text/css" />
<!-- Datatables css -->
<link href="assets/vendor/datatables.net-bs5/css/dataTables.bootstrap5.min.css" rel="stylesheet" type="text/css" />
<link href="assets/vendor/datatables.net-responsive-bs5/css/responsive.bootstrap5.min.css" rel="stylesheet" type="text/css" />

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
                                <h4 class="page-title">Activity Reports</h4>
                                <div>
                                    <a href="reports_dashboard.php" class="btn btn-secondary me-2">
                                        <i class="ri-dashboard-line"></i> Reports Dashboard
                                    </a>
                                    <?php if ($audit_table_exists): ?>
                                        <a href="?export=csv&start_date=<?php echo urlencode($start_date); ?>&end_date=<?php echo urlencode($end_date); ?>" class="btn btn-success">
                                            <i class="ri-download-line"></i> Export CSV
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if (!$audit_table_exists && !$member_access_exists && !$access_logs_exists): ?>
                        <!-- Missing Tables Alert -->
                        <div class="row">
                            <div class="col-12">
                                <div class="alert alert-warning">
                                    <h5 class="alert-heading"><i class="ri-alert-line"></i> Activity Tables Not Found</h5>
                                    <p>The activity tracking tables (<code>audit_logs</code>, <code>member_access</code>, <code>access_logs</code>) do not exist in the database.</p>
                                    <hr>
                                    <p class="mb-0">You can find migration SQL files in the <code>Sql/</code> directory to create these tables.</p>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>

                    <!-- Date Range Filter -->
                    <div class="row mb-3">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <form method="GET" class="row gy-2 gx-2 align-items-end">
                                        <div class="col-auto">
                                            <label class="form-label">Start Date</label>
                                            <input type="date" class="form-control" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>">
                                        </div>
                                        <div class="col-auto">
                                            <label class="form-label">End Date</label>
                                            <input type="date" class="form-control" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>">
                                        </div>
                                        <div class="col-auto">
                                            <button type="submit" class="btn btn-primary">Apply Filter</button>
                                            <a href="reports_activity.php" class="btn btn-secondary">Reset</a>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Statistics Cards -->
                    <div class="row g-3 mb-4">
                        <?php if ($audit_table_exists): ?>
                        <div class="col-xl-3 col-md-6">
                            <div class="card widget-flat text-bg-primary">
                                <div class="card-body">
                                    <div class="float-end">
                                        <i class="ri-file-list-3-line widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0">Total Actions</h6>
                                    <h2 class="my-2"><?php echo number_format($stats['total_actions']); ?></h2>
                                    <p class="mb-0">
                                        <span class="text-white-50">Audit log entries</span>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        <?php if ($member_access_exists): ?>
                        <div class="col-xl-3 col-md-6">
                            <div class="card widget-flat text-bg-success">
                                <div class="card-body">
                                    <div class="float-end">
                                        <i class="ri-user-line widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0">Member Logins</h6>
                                    <h2 class="my-2"><?php echo number_format($member_login_stats['total_logins']); ?></h2>
                                    <p class="mb-0">
                                        <span class="text-white-50"><?php echo number_format($member_login_stats['unique_users']); ?> unique users</span>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        <?php if ($access_logs_exists): ?>
                        <div class="col-xl-3 col-md-6">
                            <div class="card widget-flat text-bg-info">
                                <div class="card-body">
                                    <div class="float-end">
                                        <i class="ri-shield-check-line widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0">Access Requests</h6>
                                    <h2 class="my-2"><?php echo number_format($access_logs_stats['total']); ?></h2>
                                    <p class="mb-0">
                                        <span class="text-white-50"><?php echo number_format($access_logs_stats['granted']); ?> granted, <?php echo number_format($access_logs_stats['denied']); ?> denied</span>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        <div class="col-xl-3 col-md-6">
                            <div class="card widget-flat text-bg-warning">
                                <div class="card-body">
                                    <div class="float-end">
                                        <i class="ri-time-line widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0">Date Range</h6>
                                    <h2 class="my-2"><?php echo date('M d', strtotime($start_date)); ?></h2>
                                    <p class="mb-0">
                                        <span class="text-white-50">to <?php echo date('M d, Y', strtotime($end_date)); ?></span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if ($audit_table_exists && !empty($monthly_trend)): ?>
                    <!-- Charts Row -->
                    <div class="row mb-4">
                        <div class="col-xl-6">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Monthly Activity Trend</h4>
                                </div>
                                <div class="card-body">
                                    <div id="monthly-trend-chart" style="height: 300px;"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-6">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Actions by User Type</h4>
                                </div>
                                <div class="card-body">
                                    <div id="user-type-chart" style="height: 300px;"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Breakdown Cards -->
                    <?php if ($audit_table_exists): ?>
                    <div class="row mb-4">
                        <div class="col-xl-6">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Top Actions</h4>
                                </div>
                                <div class="card-body">
                                    <?php if (!empty($action_breakdown)): ?>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-hover mb-0">
                                                <thead>
                                                    <tr>
                                                        <th>Action</th>
                                                        <th class="text-end">Count</th>
                                                        <th class="text-end">Percentage</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($action_breakdown as $item): ?>
                                                        <tr>
                                                            <td><?php echo htmlspecialchars($item['action']); ?></td>
                                                            <td class="text-end"><?php echo number_format($item['count']); ?></td>
                                                            <td class="text-end"><?php echo $stats['total_actions'] > 0 ? number_format(($item['count'] / $stats['total_actions']) * 100, 1) : 0; ?>%</td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php else: ?>
                                        <p class="text-muted mb-0">No action data available.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-6">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Top Tables</h4>
                                </div>
                                <div class="card-body">
                                    <?php if (!empty($table_breakdown)): ?>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-hover mb-0">
                                                <thead>
                                                    <tr>
                                                        <th>Table</th>
                                                        <th class="text-end">Count</th>
                                                        <th class="text-end">Percentage</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($table_breakdown as $item): ?>
                                                        <tr>
                                                            <td><?php echo htmlspecialchars($item['table_name']); ?></td>
                                                            <td class="text-end"><?php echo number_format($item['count']); ?></td>
                                                            <td class="text-end"><?php echo $stats['total_actions'] > 0 ? number_format(($item['count'] / $stats['total_actions']) * 100, 1) : 0; ?>%</td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php else: ?>
                                        <p class="text-muted mb-0">No table data available.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Recent Activities Table -->
                    <?php if ($audit_table_exists): ?>
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Recent Activities</h4>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped table-bordered dt-responsive nowrap datatable" style="width:100%">
                                            <thead>
                                                <tr>
                                                    <th>ID</th>
                                                    <th>User</th>
                                                    <th>User Type</th>
                                                    <th>Action</th>
                                                    <th>Table</th>
                                                    <th>Record ID</th>
                                                    <th>IP Address</th>
                                                    <th>Date</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($recent_activities as $activity): ?>
                                                    <tr>
                                                        <td><?php echo $activity['id']; ?></td>
                                                        <td>
                                                            <?php if ($activity['user_type'] == 'admin' && $activity['admin_name']): ?>
                                                                <?php echo htmlspecialchars($activity['admin_name']); ?>
                                                            <?php elseif ($activity['user_id']): ?>
                                                                User #<?php echo $activity['user_id']; ?>
                                                            <?php else: ?>
                                                                N/A
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <span class="badge bg-<?php echo $activity['user_type'] == 'admin' ? 'primary' : 'success'; ?>">
                                                                <?php echo ucfirst($activity['user_type']); ?>
                                                            </span>
                                                        </td>
                                                        <td><?php echo htmlspecialchars($activity['action']); ?></td>
                                                        <td><?php echo htmlspecialchars($activity['table_name'] ?? '-'); ?></td>
                                                        <td><?php echo $activity['record_id'] ?? '-'; ?></td>
                                                        <td><?php echo htmlspecialchars($activity['ip_address'] ?? '-'); ?></td>
                                                        <td><?php echo date('M d, Y H:i', strtotime($activity['created_at'])); ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php endif; ?>

                </div>
            </div>

            <?php include 'footer.php'; ?>
        </div>
    </div>

    <!-- Vendor js -->
    <script src="assets/js/vendor.min.js"></script>
    <!-- ApexCharts js -->
    <script src="assets/vendor/apexcharts/apexcharts.min.js"></script>
    <!-- Datatables js -->
    <script src="assets/vendor/datatables.net/js/jquery.dataTables.min.js"></script>
    <script src="assets/vendor/datatables.net-bs5/js/dataTables.bootstrap5.min.js"></script>
    <script src="assets/vendor/datatables.net-responsive/js/dataTables.responsive.min.js"></script>
    <script src="assets/vendor/datatables.net-responsive-bs5/js/responsive.bootstrap5.min.js"></script>
    <!-- App js -->
    <script src="assets/js/app.min.js"></script>

    <?php if ($audit_table_exists && !empty($monthly_trend)): ?>
    <script>
        $(document).ready(function() {
            // Initialize DataTable
            $('.datatable').DataTable({
                responsive: true,
                order: [[7, 'desc']],
                pageLength: 25
            });

            // Monthly Trend Chart
            var monthlyOptions = {
                series: [{
                    name: 'Actions',
                    data: [<?php echo implode(',', array_values($monthly_trend)); ?>]
                }],
                chart: {
                    type: 'line',
                    height: 300,
                    toolbar: { show: false }
                },
                colors: ['#727cf5'],
                stroke: {
                    width: 3,
                    curve: 'smooth'
                },
                xaxis: {
                    categories: [<?php echo "'" . implode("','", $months) . "'"; ?>],
                    labels: { rotate: -45 }
                },
                yaxis: {
                    title: { text: 'Number of Actions' }
                },
                tooltip: {
                    y: { formatter: function(val) { return val + ' actions'; } }
                }
            };
            var monthlyChart = new ApexCharts(document.querySelector("#monthly-trend-chart"), monthlyOptions);
            monthlyChart.render();

            // User Type Chart
            var userTypeData = <?php echo json_encode(array_column($user_type_breakdown, 'count')); ?>;
            var userTypeLabels = <?php echo json_encode(array_map(function($s) { return ucfirst($s['user_type']); }, $user_type_breakdown)); ?>;
            
            var userTypeOptions = {
                series: userTypeData,
                chart: {
                    type: 'donut',
                    height: 300
                },
                labels: userTypeLabels,
                colors: ['#727cf5', '#0acf97'],
                legend: {
                    position: 'bottom'
                }
            };
            var userTypeChart = new ApexCharts(document.querySelector("#user-type-chart"), userTypeOptions);
            userTypeChart.render();
        });
    </script>
    <?php endif; ?>
</body>
</html>

