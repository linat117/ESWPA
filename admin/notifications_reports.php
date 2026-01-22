<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: auth-login.php");
    exit();
}

include 'include/conn.php';
include 'header.php';

// Handle export
if (isset($_GET['export']) && $_GET['export'] == 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=notification_reports_' . date('Y-m-d') . '.csv');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Date', 'Total', 'Unread', 'Read', 'Read Rate']);
    
    $table_exists = false;
    $result = $conn->query("SHOW TABLES LIKE 'notifications'");
    if ($result && $result->num_rows > 0) {
        $table_exists = true;
    }
    
    if ($table_exists) {
        // Daily statistics for last 30 days
        for ($i = 29; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $date_start = $date . ' 00:00:00';
            $date_end = $date . ' 23:59:59';
            
            $total = $conn->query("SELECT COUNT(*) as total FROM notifications WHERE created_at BETWEEN '$date_start' AND '$date_end'")->fetch_assoc()['total'] ?? 0;
            $unread = $conn->query("SELECT COUNT(*) as total FROM notifications WHERE is_read = 0 AND created_at BETWEEN '$date_start' AND '$date_end'")->fetch_assoc()['total'] ?? 0;
            $read = $conn->query("SELECT COUNT(*) as total FROM notifications WHERE is_read = 1 AND created_at BETWEEN '$date_start' AND '$date_end'")->fetch_assoc()['total'] ?? 0;
            $read_rate = $total > 0 ? round(($read / $total) * 100, 2) : 0;
            
            fputcsv($output, [$date, $total, $unread, $read, $read_rate . '%']);
        }
    }
    
    fclose($output);
    exit();
}

// Get date range filter
$date_range = $_GET['range'] ?? '30';
$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime("-{$date_range} days"));
$end_date = $_GET['end_date'] ?? date('Y-m-d');

$start_date = date('Y-m-d', strtotime($start_date));
$end_date = date('Y-m-d', strtotime($end_date));
$start_datetime = $start_date . ' 00:00:00';
$end_datetime = $end_date . ' 23:59:59';

// Check if table exists
$table_exists = false;
$result = $conn->query("SHOW TABLES LIKE 'notifications'");
if ($result && $result->num_rows > 0) {
    $table_exists = true;
}

$stats = [];
$daily_data = [];
$type_stats = [];
$member_stats = [];

if ($table_exists) {
    // Statistics
    $stats['total'] = $conn->query("SELECT COUNT(*) as total FROM notifications")->fetch_assoc()['total'];
    $stats['unread'] = $conn->query("SELECT COUNT(*) as total FROM notifications WHERE is_read = 0")->fetch_assoc()['total'];
    $stats['read'] = $conn->query("SELECT COUNT(*) as total FROM notifications WHERE is_read = 1")->fetch_assoc()['total'];
    $stats['in_range'] = $conn->query("SELECT COUNT(*) as total FROM notifications WHERE created_at BETWEEN '{$start_datetime}' AND '{$end_datetime}'")->fetch_assoc()['total'];
    
    // Read rate
    $stats['read_rate'] = $stats['total'] > 0 ? round(($stats['read'] / $stats['total']) * 100, 1) : 0;
    
    // Daily notification trend (last 30 days)
    $days = [];
    for ($i = 29; $i >= 0; $i--) {
        $day = date('Y-m-d', strtotime("-$i days"));
        $days[] = date('M d', strtotime("-$i days"));
        $daily_data[$day] = 0;
    }
    $daily_query = "SELECT DATE(created_at) as date, COUNT(id) as count 
                    FROM notifications 
                    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) 
                    GROUP BY date ORDER BY date ASC";
    $daily_result = $conn->query($daily_query);
    while ($row = $daily_result->fetch_assoc()) {
        if (isset($daily_data[$row['date']])) {
            $daily_data[$row['date']] = $row['count'];
        }
    }
    
    // Type statistics
    $type_query = "SELECT type, COUNT(*) as total, 
                   SUM(CASE WHEN is_read = 1 THEN 1 ELSE 0 END) as read_count,
                   SUM(CASE WHEN is_read = 0 THEN 1 ELSE 0 END) as unread_count
                   FROM notifications 
                   GROUP BY type 
                   ORDER BY total DESC";
    $type_result = $conn->query($type_query);
    while ($row = $type_result->fetch_assoc()) {
        $type_stats[] = $row;
    }
    
    // Member statistics (members with most notifications)
    $member_query = "SELECT n.member_id, r.fullname, r.email,
                     COUNT(n.id) as total_notifications,
                     SUM(CASE WHEN n.is_read = 1 THEN 1 ELSE 0 END) as read_count
                     FROM notifications n
                     LEFT JOIN registrations r ON n.member_id = r.id
                     GROUP BY n.member_id, r.fullname, r.email
                     ORDER BY total_notifications DESC
                     LIMIT 20";
    $member_result = $conn->query($member_query);
    while ($row = $member_result->fetch_assoc()) {
        $member_stats[] = $row;
    }
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
                            <div class="page-title-box d-flex justify-content-between align-items-center">
                                <h4 class="page-title">Notification Reports</h4>
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

                    <!-- Date Range Filter -->
                    <div class="row mb-3">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <form method="GET" class="row g-3">
                                        <div class="col-md-3">
                                            <label class="form-label">Date Range</label>
                                            <select name="range" class="form-select" onchange="this.form.submit()">
                                                <option value="7" <?php echo $date_range == '7' ? 'selected' : ''; ?>>Last 7 days</option>
                                                <option value="30" <?php echo $date_range == '30' ? 'selected' : ''; ?>>Last 30 days</option>
                                                <option value="90" <?php echo $date_range == '90' ? 'selected' : ''; ?>>Last 90 days</option>
                                                <option value="365" <?php echo $date_range == '365' ? 'selected' : ''; ?>>Last year</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Start Date</label>
                                            <input type="date" name="start_date" class="form-control" value="<?php echo $start_date; ?>">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">End Date</label>
                                            <input type="date" name="end_date" class="form-control" value="<?php echo $end_date; ?>">
                                        </div>
                                        <div class="col-md-3 d-flex align-items-end">
                                            <button type="submit" class="btn btn-primary w-100">
                                                <i class="ri-filter-line"></i> Apply Filter
                                            </button>
                                        </div>
                                    </form>
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

                    <!-- Summary Statistics -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-3">
                            <div class="card bg-primary bg-opacity-10 border-primary">
                                <div class="card-body">
                                    <h6 class="text-muted mb-1">Total Notifications</h6>
                                    <h3 class="mb-0"><?php echo number_format($stats['total']); ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-warning bg-opacity-10 border-warning">
                                <div class="card-body">
                                    <h6 class="text-muted mb-1">Unread</h6>
                                    <h3 class="mb-0"><?php echo number_format($stats['unread']); ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-success bg-opacity-10 border-success">
                                <div class="card-body">
                                    <h6 class="text-muted mb-1">Read</h6>
                                    <h3 class="mb-0"><?php echo number_format($stats['read']); ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-info bg-opacity-10 border-info">
                                <div class="card-body">
                                    <h6 class="text-muted mb-1">Read Rate</h6>
                                    <h3 class="mb-0"><?php echo $stats['read_rate']; ?>%</h3>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Charts Row -->
                    <div class="row mb-3">
                        <div class="col-lg-8">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Daily Notification Trend (30 Days)</h4>
                                </div>
                                <div class="card-body">
                                    <div id="daily-trend-chart" style="min-height: 350px;"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Statistics by Type</h4>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-sm mb-0">
                                            <thead>
                                                <tr>
                                                    <th>Type</th>
                                                    <th>Total</th>
                                                    <th>Read</th>
                                                    <th>Rate</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($type_stats as $type): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $type['type']))); ?></td>
                                                        <td><?php echo number_format($type['total']); ?></td>
                                                        <td><?php echo number_format($type['read_count']); ?></td>
                                                        <td><?php echo $type['total'] > 0 ? number_format(($type['read_count'] / $type['total']) * 100, 1) : 0; ?>%</td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Member Statistics -->
                    <?php if (!empty($member_stats)): ?>
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Top Members by Notification Count</h4>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-hover mb-0">
                                            <thead>
                                                <tr>
                                                    <th>Member</th>
                                                    <th>Email</th>
                                                    <th>Total</th>
                                                    <th>Read</th>
                                                    <th>Unread</th>
                                                    <th>Read Rate</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($member_stats as $member): ?>
                                                    <tr>
                                                        <td><strong><?php echo htmlspecialchars($member['fullname'] ?? 'Unknown'); ?></strong></td>
                                                        <td><?php echo htmlspecialchars($member['email'] ?? '-'); ?></td>
                                                        <td><?php echo number_format($member['total_notifications']); ?></td>
                                                        <td><?php echo number_format($member['read_count']); ?></td>
                                                        <td><?php echo number_format($member['total_notifications'] - $member['read_count']); ?></td>
                                                        <td><?php echo $member['total_notifications'] > 0 ? number_format(($member['read_count'] / $member['total_notifications']) * 100, 1) : 0; ?>%</td>
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
    
    <!-- App js -->
    <script src="assets/js/app.min.js"></script>

    <?php if ($table_exists): ?>
    <script>
        // Daily Trend Chart
        var dailyTrendOptions = {
            chart: {
                type: 'area',
                height: 350,
                toolbar: { show: false }
            },
            series: [{
                name: 'Notifications',
                data: <?php echo json_encode(array_values($daily_data)); ?>
            }],
            xaxis: {
                categories: <?php echo json_encode($days); ?>
            },
            colors: ['#3e60d5'],
            fill: {
                type: 'gradient',
                gradient: {
                    shadeIntensity: 1,
                    opacityFrom: 0.7,
                    opacityTo: 0.3
                }
            },
            stroke: { width: 2 }
        };
        new ApexCharts(document.querySelector("#daily-trend-chart"), dailyTrendOptions).render();
    </script>
    <?php endif; ?>

</body>
</html>

