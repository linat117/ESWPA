<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: auth-login.php");
    exit();
}

include 'include/conn.php';
include 'header.php';

// Check if tables exist
$user_table_exists = false;
$user_roles_exists = false;
$audit_logs_exists = false;

$result = $conn->query("SHOW TABLES LIKE 'user'");
if ($result && $result->num_rows > 0) {
    $user_table_exists = true;
}
$result = $conn->query("SHOW TABLES LIKE 'user_roles'");
if ($result && $result->num_rows > 0) {
    $user_roles_exists = true;
}
$result = $conn->query("SHOW TABLES LIKE 'audit_logs'");
if ($result && $result->num_rows > 0) {
    $audit_logs_exists = true;
}

// Handle CSV export
if (isset($_GET['export']) && $_GET['export'] == 'csv' && $user_table_exists) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=users_reports_' . date('Y-m-d') . '.csv');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'Username', 'Role', 'Registration Date', 'Total Actions', 'Last Activity']);
    
    // Get user statistics
    $users = [];
    $stmt = $conn->prepare("SELECT u.id, u.username, u.registration_date, 
                           COALESCE(ur.role, 'N/A') as role
                           FROM user u
                           LEFT JOIN user_roles ur ON u.id = ur.user_id
                           ORDER BY u.registration_date DESC");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $user_id = $row['id'];
        $total_actions = 0;
        $last_activity = null;
        
        if ($audit_logs_exists) {
            $stmt2 = $conn->prepare("SELECT COUNT(*) as total, MAX(created_at) as last_activity 
                                    FROM audit_logs 
                                    WHERE user_id = ? AND user_type = 'admin'");
            $stmt2->bind_param("i", $user_id);
            $stmt2->execute();
            $result2 = $stmt2->get_result();
            if ($activity = $result2->fetch_assoc()) {
                $total_actions = $activity['total'];
                $last_activity = $activity['last_activity'];
            }
            $stmt2->close();
        }
        
        $row['total_actions'] = $total_actions;
        $row['last_activity'] = $last_activity;
        $users[] = $row;
    }
    $stmt->close();
    
    foreach ($users as $user) {
        fputcsv($output, [
            $user['id'],
            $user['username'],
            $user['role'],
            $user['registration_date'],
            $user['total_actions'],
            $user['last_activity'] ?? 'N/A'
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

if ($user_table_exists) {
    // Total users
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM user");
    $stmt->execute();
    $result = $stmt->get_result();
    $stats['total_users'] = $result->fetch_assoc()['total'] ?? 0;
    $stmt->close();

    // Users by role
    $users_by_role = [];
    if ($user_roles_exists) {
        $stmt = $conn->prepare("SELECT role, COUNT(*) as count FROM user_roles GROUP BY role ORDER BY count DESC");
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $users_by_role[] = $row;
        }
        $stmt->close();
    }

    // Recent registrations
    $recent_registrations = [];
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM user WHERE registration_date BETWEEN ? AND ?");
    $stmt->bind_param("ss", $start_datetime, $end_datetime);
    $stmt->execute();
    $result = $stmt->get_result();
    $stats['recent_registrations'] = $result->fetch_assoc()['total'] ?? 0;
    $stmt->close();
} else {
    $stats['total_users'] = 0;
    $stats['recent_registrations'] = 0;
    $users_by_role = [];
}

// Admin activity statistics
$admin_activity = [];
if ($audit_logs_exists && $user_table_exists) {
    // Top active admins
    $stmt = $conn->prepare("SELECT u.id, u.username, COUNT(al.id) as action_count, MAX(al.created_at) as last_activity
                           FROM user u
                           LEFT JOIN audit_logs al ON u.id = al.user_id AND al.user_type = 'admin'
                           WHERE al.created_at BETWEEN ? AND ? OR al.created_at IS NULL
                           GROUP BY u.id, u.username
                           HAVING action_count > 0
                           ORDER BY action_count DESC
                           LIMIT 20");
    $stmt->bind_param("ss", $start_datetime, $end_datetime);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $admin_activity[] = $row;
    }
    $stmt->close();

    // Total admin actions in period
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM audit_logs WHERE user_type = 'admin' AND created_at BETWEEN ? AND ?");
    $stmt->bind_param("ss", $start_datetime, $end_datetime);
    $stmt->execute();
    $result = $stmt->get_result();
    $stats['total_actions'] = $result->fetch_assoc()['total'] ?? 0;
    $stmt->close();
} else {
    $stats['total_actions'] = 0;
}

// Monthly user registration trend
$monthly_trend = [];
$months = [];
for ($i = 11; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $months[] = date('M Y', strtotime("-$i months"));
    $monthly_trend[$month] = 0;
}

if ($user_table_exists) {
    $monthly_query = "SELECT DATE_FORMAT(registration_date, '%Y-%m') as month, COUNT(id) as count 
                      FROM user 
                      WHERE registration_date >= DATE_SUB(NOW(), INTERVAL 12 MONTH) 
                      GROUP BY month ORDER BY month ASC";
    $monthly_result = $conn->query($monthly_query);
    while ($row = $monthly_result->fetch_assoc()) {
        if (isset($monthly_trend[$row['month']])) {
            $monthly_trend[$row['month']] = $row['count'];
        }
    }
}

// All users with details
$all_users = [];
if ($user_table_exists) {
    $stmt = $conn->prepare("SELECT u.id, u.username, u.registration_date, 
                           COALESCE(ur.role, 'N/A') as role,
                           (SELECT COUNT(*) FROM audit_logs WHERE user_id = u.id AND user_type = 'admin') as total_actions,
                           (SELECT MAX(created_at) FROM audit_logs WHERE user_id = u.id AND user_type = 'admin') as last_activity
                           FROM user u
                           LEFT JOIN user_roles ur ON u.id = ur.user_id
                           ORDER BY u.registration_date DESC");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $all_users[] = $row;
    }
    $stmt->close();
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
                                <h4 class="page-title">Users/Admin Reports</h4>
                                <div>
                                    <a href="reports_dashboard.php" class="btn btn-secondary me-2">
                                        <i class="ri-dashboard-line"></i> Reports Dashboard
                                    </a>
                                    <?php if ($user_table_exists): ?>
                                        <a href="?export=csv&start_date=<?php echo urlencode($start_date); ?>&end_date=<?php echo urlencode($end_date); ?>" class="btn btn-success">
                                            <i class="ri-download-line"></i> Export CSV
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if (!$user_table_exists): ?>
                        <!-- Missing Table Alert -->
                        <div class="row">
                            <div class="col-12">
                                <div class="alert alert-warning">
                                    <h5 class="alert-heading"><i class="ri-alert-line"></i> User Table Not Found</h5>
                                    <p>The <code>user</code> table does not exist in the database.</p>
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
                                            <a href="reports_users.php" class="btn btn-secondary">Reset</a>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Statistics Cards -->
                    <div class="row g-3 mb-4">
                        <div class="col-xl-3 col-md-6">
                            <div class="card widget-flat text-bg-primary">
                                <div class="card-body">
                                    <div class="float-end">
                                        <i class="ri-user-line widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0">Total Users</h6>
                                    <h2 class="my-2"><?php echo number_format($stats['total_users']); ?></h2>
                                    <p class="mb-0">
                                        <span class="text-white-50">Admin users</span>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6">
                            <div class="card widget-flat text-bg-success">
                                <div class="card-body">
                                    <div class="float-end">
                                        <i class="ri-user-add-line widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0">Recent Registrations</h6>
                                    <h2 class="my-2"><?php echo number_format($stats['recent_registrations']); ?></h2>
                                    <p class="mb-0">
                                        <span class="text-white-50">In selected period</span>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6">
                            <div class="card widget-flat text-bg-info">
                                <div class="card-body">
                                    <div class="float-end">
                                        <i class="ri-file-list-3-line widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0">Admin Actions</h6>
                                    <h2 class="my-2"><?php echo number_format($stats['total_actions']); ?></h2>
                                    <p class="mb-0">
                                        <span class="text-white-50">In selected period</span>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6">
                            <div class="card widget-flat text-bg-warning">
                                <div class="card-body">
                                    <div class="float-end">
                                        <i class="ri-shield-user-line widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0">User Roles</h6>
                                    <h2 class="my-2"><?php echo count($users_by_role); ?></h2>
                                    <p class="mb-0">
                                        <span class="text-white-50">Different roles</span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Charts Row -->
                    <?php if (!empty($monthly_trend)): ?>
                    <div class="row mb-4">
                        <div class="col-xl-6">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Monthly User Registration Trend</h4>
                                </div>
                                <div class="card-body">
                                    <div id="monthly-trend-chart" style="height: 300px;"></div>
                                </div>
                            </div>
                        </div>
                        <?php if (!empty($users_by_role)): ?>
                        <div class="col-xl-6">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Users by Role</h4>
                                </div>
                                <div class="card-body">
                                    <div id="role-chart" style="height: 300px;"></div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <!-- Breakdown Cards -->
                    <?php if (!empty($users_by_role) || !empty($admin_activity)): ?>
                    <div class="row mb-4">
                        <?php if (!empty($users_by_role)): ?>
                        <div class="col-xl-6">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Users by Role</h4>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-sm table-hover mb-0">
                                            <thead>
                                                <tr>
                                                    <th>Role</th>
                                                    <th class="text-end">Count</th>
                                                    <th class="text-end">Percentage</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($users_by_role as $item): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $item['role']))); ?></td>
                                                        <td class="text-end"><?php echo number_format($item['count']); ?></td>
                                                        <td class="text-end"><?php echo $stats['total_users'] > 0 ? number_format(($item['count'] / $stats['total_users']) * 100, 1) : 0; ?>%</td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($admin_activity)): ?>
                        <div class="col-xl-6">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Top Active Admins</h4>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-sm table-hover mb-0">
                                            <thead>
                                                <tr>
                                                    <th>Username</th>
                                                    <th class="text-end">Actions</th>
                                                    <th>Last Activity</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($admin_activity as $item): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($item['username']); ?></td>
                                                        <td class="text-end"><?php echo number_format($item['action_count']); ?></td>
                                                        <td><?php echo $item['last_activity'] ? date('M d, Y H:i', strtotime($item['last_activity'])) : 'N/A'; ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <!-- All Users Table -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">All Admin Users</h4>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped table-bordered dt-responsive nowrap datatable" style="width:100%">
                                            <thead>
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Username</th>
                                                    <th>Role</th>
                                                    <th>Registration Date</th>
                                                    <th>Total Actions</th>
                                                    <th>Last Activity</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($all_users as $user): ?>
                                                    <tr>
                                                        <td><?php echo $user['id']; ?></td>
                                                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                                                        <td>
                                                            <?php
                                                            $role_colors = [
                                                                'super_admin' => 'danger',
                                                                'admin' => 'primary',
                                                                'editor' => 'success',
                                                                'viewer' => 'secondary'
                                                            ];
                                                            $role_color = $role_colors[$user['role']] ?? 'secondary';
                                                            ?>
                                                            <span class="badge bg-<?php echo $role_color; ?>"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $user['role']))); ?></span>
                                                        </td>
                                                        <td><?php echo date('M d, Y', strtotime($user['registration_date'])); ?></td>
                                                        <td>
                                                            <span class="badge bg-info"><?php echo number_format($user['total_actions'] ?? 0); ?></span>
                                                        </td>
                                                        <td><?php echo $user['last_activity'] ? date('M d, Y H:i', strtotime($user['last_activity'])) : 'Never'; ?></td>
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

    <?php if (!empty($monthly_trend)): ?>
    <script>
        $(document).ready(function() {
            // Initialize DataTable
            $('.datatable').DataTable({
                responsive: true,
                order: [[3, 'desc']],
                pageLength: 25
            });

            // Monthly Trend Chart
            var monthlyOptions = {
                series: [{
                    name: 'Registrations',
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
                    title: { text: 'Number of Users' }
                },
                tooltip: {
                    y: { formatter: function(val) { return val + ' users'; } }
                }
            };
            var monthlyChart = new ApexCharts(document.querySelector("#monthly-trend-chart"), monthlyOptions);
            monthlyChart.render();

            <?php if (!empty($users_by_role)): ?>
            // Role Distribution Chart
            var roleData = <?php echo json_encode(array_column($users_by_role, 'count')); ?>;
            var roleLabels = <?php echo json_encode(array_map(function($r) { return ucfirst(str_replace('_', ' ', $r['role'])); }, $users_by_role)); ?>;
            
            var roleOptions = {
                series: roleData,
                chart: {
                    type: 'donut',
                    height: 300
                },
                labels: roleLabels,
                colors: ['#727cf5', '#0acf97', '#fa5c7c', '#ffbc00'],
                legend: {
                    position: 'bottom'
                }
            };
            var roleChart = new ApexCharts(document.querySelector("#role-chart"), roleOptions);
            roleChart.render();
            <?php endif; ?>
        });
    </script>
    <?php endif; ?>
</body>
</html>

