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
    header('Content-Disposition: attachment; filename=chat_analytics_' . date('Y-m-d') . '.csv');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Date', 'Total Messages', 'Sent', 'Failed', 'Pending', 'Success Rate']);
    
    $table_exists = false;
    $result = $conn->query("SHOW TABLES LIKE 'telegram_messages'");
    if ($result && $result->num_rows > 0) {
        $table_exists = true;
    }
    
    if ($table_exists) {
        // Daily statistics for last 30 days
        for ($i = 29; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $date_start = $date . ' 00:00:00';
            $date_end = $date . ' 23:59:59';
            
            $total = $conn->query("SELECT COUNT(*) as total FROM telegram_messages WHERE created_at BETWEEN '$date_start' AND '$date_end'")->fetch_assoc()['total'] ?? 0;
            $sent = $conn->query("SELECT COUNT(*) as total FROM telegram_messages WHERE status = 'sent' AND created_at BETWEEN '$date_start' AND '$date_end'")->fetch_assoc()['total'] ?? 0;
            $failed = $conn->query("SELECT COUNT(*) as total FROM telegram_messages WHERE status = 'failed' AND created_at BETWEEN '$date_start' AND '$date_end'")->fetch_assoc()['total'] ?? 0;
            $pending = $conn->query("SELECT COUNT(*) as total FROM telegram_messages WHERE status = 'pending' AND created_at BETWEEN '$date_start' AND '$date_end'")->fetch_assoc()['total'] ?? 0;
            $success_rate = $total > 0 ? round(($sent / $total) * 100, 2) : 0;
            
            fputcsv($output, [$date, $total, $sent, $failed, $pending, $success_rate . '%']);
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
$result = $conn->query("SHOW TABLES LIKE 'telegram_messages'");
if ($result && $result->num_rows > 0) {
    $table_exists = true;
}

$stats = [];
$daily_data = [];
$hourly_data = [];
$status_trend = [];
$top_users = [];

if ($table_exists) {
    // Statistics
    $stats['total'] = $conn->query("SELECT COUNT(*) as total FROM telegram_messages")->fetch_assoc()['total'];
    $stats['sent'] = $conn->query("SELECT COUNT(*) as total FROM telegram_messages WHERE status = 'sent'")->fetch_assoc()['total'];
    $stats['failed'] = $conn->query("SELECT COUNT(*) as total FROM telegram_messages WHERE status = 'failed'")->fetch_assoc()['total'];
    $stats['in_range'] = $conn->query("SELECT COUNT(*) as total FROM telegram_messages WHERE created_at BETWEEN '{$start_datetime}' AND '{$end_datetime}'")->fetch_assoc()['total'];
    
    // Success rate
    $stats['success_rate'] = $stats['total'] > 0 ? round(($stats['sent'] / $stats['total']) * 100, 1) : 0;
    
    // Daily message trend (last 30 days)
    $days = [];
    for ($i = 29; $i >= 0; $i--) {
        $day = date('Y-m-d', strtotime("-$i days"));
        $days[] = date('M d', strtotime("-$i days"));
        $daily_data[$day] = 0;
    }
    $daily_query = "SELECT DATE(created_at) as date, COUNT(id) as count 
                    FROM telegram_messages 
                    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) 
                    GROUP BY date ORDER BY date ASC";
    $daily_result = $conn->query($daily_query);
    while ($row = $daily_result->fetch_assoc()) {
        if (isset($daily_data[$row['date']])) {
            $daily_data[$row['date']] = $row['count'];
        }
    }
    
    // Hourly distribution (24 hours)
    for ($i = 0; $i < 24; $i++) {
        $hourly_data[$i] = 0;
    }
    $hourly_query = "SELECT HOUR(created_at) as hour, COUNT(id) as count 
                     FROM telegram_messages 
                     WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                     GROUP BY hour ORDER BY hour ASC";
    $hourly_result = $conn->query($hourly_query);
    while ($row = $hourly_result->fetch_assoc()) {
        $hourly_data[$row['hour']] = $row['count'];
    }
    
    // Status trend (daily breakdown)
    $status_trend_query = "SELECT DATE(created_at) as date, status, COUNT(*) as count 
                          FROM telegram_messages 
                          WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                          GROUP BY date, status 
                          ORDER BY date ASC, status ASC";
    $status_trend_result = $conn->query($status_trend_query);
    $status_trend_data = ['sent' => [], 'failed' => [], 'pending' => []];
    while ($row = $status_trend_result->fetch_assoc()) {
        if (!isset($status_trend_data[$row['status']][$row['date']])) {
            $status_trend_data[$row['status']][$row['date']] = 0;
        }
        $status_trend_data[$row['status']][$row['date']] = $row['count'];
    }
    
    // Fill in missing dates
    foreach (array_keys($daily_data) as $date) {
        foreach (['sent', 'failed', 'pending'] as $status) {
            if (!isset($status_trend_data[$status][$date])) {
                $status_trend_data[$status][$date] = 0;
            }
        }
    }
    
    // Sort by date
    foreach ($status_trend_data as $status => $data) {
        ksort($data);
        $status_trend[$status] = array_values($data);
    }
    
    // Top users (by message count)
    $top_users_query = "SELECT user_email, user_name, COUNT(*) as message_count 
                       FROM telegram_messages 
                       WHERE user_email IS NOT NULL AND user_email != ''
                       GROUP BY user_email, user_name 
                       ORDER BY message_count DESC 
                       LIMIT 10";
    $top_users_result = $conn->query($top_users_query);
    while ($row = $top_users_result->fetch_assoc()) {
        $top_users[] = $row;
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
                                <h4 class="page-title">Chat Analytics</h4>
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
                        <div class="alert alert-info" role="alert">
                            <h5 class="alert-heading"><i class="ri-information-line"></i> Telegram Messages Table Not Found</h5>
                            <p>The <code>telegram_messages</code> table does not exist. This is optional - the chat system works without it.</p>
                            <p class="mb-0">See <a href="chat_dashboard.php">Chat Dashboard</a> for the optional table structure.</p>
                        </div>
                    <?php else: ?>

                    <!-- Summary Statistics -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-3">
                            <div class="card bg-primary bg-opacity-10 border-primary">
                                <div class="card-body">
                                    <h6 class="text-muted mb-1">Total Messages</h6>
                                    <h3 class="mb-0"><?php echo number_format($stats['total']); ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-success bg-opacity-10 border-success">
                                <div class="card-body">
                                    <h6 class="text-muted mb-1">Sent</h6>
                                    <h3 class="mb-0"><?php echo number_format($stats['sent']); ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-danger bg-opacity-10 border-danger">
                                <div class="card-body">
                                    <h6 class="text-muted mb-1">Failed</h6>
                                    <h3 class="mb-0"><?php echo number_format($stats['failed']); ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-info bg-opacity-10 border-info">
                                <div class="card-body">
                                    <h6 class="text-muted mb-1">Success Rate</h6>
                                    <h3 class="mb-0"><?php echo $stats['success_rate']; ?>%</h3>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Charts Row -->
                    <div class="row mb-3">
                        <div class="col-lg-8">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Daily Message Trend (30 Days)</h4>
                                </div>
                                <div class="card-body">
                                    <div id="daily-trend-chart" style="min-height: 350px;"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Hourly Distribution</h4>
                                </div>
                                <div class="card-body">
                                    <div id="hourly-chart" style="min-height: 350px;"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Status Trend Chart -->
                    <div class="row mb-3">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Status Trend (30 Days)</h4>
                                </div>
                                <div class="card-body">
                                    <div id="status-trend-chart" style="min-height: 350px;"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Top Users -->
                    <?php if (!empty($top_users)): ?>
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Top Users (Most Messages)</h4>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-hover mb-0">
                                            <thead>
                                                <tr>
                                                    <th>User</th>
                                                    <th>Email</th>
                                                    <th>Message Count</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($top_users as $user): ?>
                                                    <tr>
                                                        <td><strong><?php echo htmlspecialchars($user['user_name'] ?? 'Unknown'); ?></strong></td>
                                                        <td><?php echo htmlspecialchars($user['user_email']); ?></td>
                                                        <td><span class="badge bg-primary"><?php echo number_format($user['message_count']); ?></span></td>
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
                name: 'Messages',
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

        // Hourly Chart
        var hourlyChartOptions = {
            chart: {
                type: 'bar',
                height: 350,
                toolbar: { show: false }
            },
            series: [{
                name: 'Messages',
                data: <?php echo json_encode(array_values($hourly_data)); ?>
            }],
            xaxis: {
                categories: <?php echo json_encode(array_map(function($h) { return sprintf('%02d:00', $h); }, array_keys($hourly_data))); ?>
            },
            colors: ['#0acf97']
        };
        new ApexCharts(document.querySelector("#hourly-chart"), hourlyChartOptions).render();

        // Status Trend Chart
        var statusTrendOptions = {
            chart: {
                type: 'line',
                height: 350,
                toolbar: { show: false }
            },
            series: [
                {
                    name: 'Sent',
                    data: <?php echo json_encode($status_trend['sent'] ?? []); ?>
                },
                {
                    name: 'Failed',
                    data: <?php echo json_encode($status_trend['failed'] ?? []); ?>
                },
                {
                    name: 'Pending',
                    data: <?php echo json_encode($status_trend['pending'] ?? []); ?>
                }
            ],
            xaxis: {
                categories: <?php echo json_encode($days); ?>
            },
            colors: ['#0acf97', '#fa5c7c', '#f7b84b'],
            stroke: { width: 3 },
            markers: { size: 4 }
        };
        new ApexCharts(document.querySelector("#status-trend-chart"), statusTrendOptions).render();
    </script>
    <?php endif; ?>

</body>
</html>

