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
    header('Content-Disposition: attachment; filename=support_reports_' . date('Y-m-d') . '.csv');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Ticket #', 'Subject', 'Member', 'Category', 'Priority', 'Status', 'Assigned To', 'Created', 'Resolved', 'Resolution Time (hours)']);
    
    $table_exists = false;
    $result = $conn->query("SHOW TABLES LIKE 'support_tickets'");
    if ($result && $result->num_rows > 0) {
        $table_exists = true;
    }
    
    if ($table_exists) {
        $query = "SELECT st.*, r.fullname, r.email, u.username as assigned_to_name 
                  FROM support_tickets st 
                  LEFT JOIN registrations r ON st.member_id = r.id 
                  LEFT JOIN user u ON st.assigned_to = u.id
                  ORDER BY st.created_at DESC";
        $result = $conn->query($query);
        
        while ($row = $result->fetch_assoc()) {
            $resolution_time = '';
            if ($row['resolved_at']) {
                $created = strtotime($row['created_at']);
                $resolved = strtotime($row['resolved_at']);
                $hours = round(($resolved - $created) / 3600, 2);
                $resolution_time = $hours;
            }
            
            fputcsv($output, [
                $row['ticket_number'],
                $row['subject'],
                $row['fullname'] ?? 'Guest',
                $row['category'] ?? 'N/A',
                $row['priority'],
                $row['status'],
                $row['assigned_to_name'] ?? 'Unassigned',
                $row['created_at'],
                $row['resolved_at'] ?? '',
                $resolution_time
            ]);
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
$result = $conn->query("SHOW TABLES LIKE 'support_tickets'");
if ($result && $result->num_rows > 0) {
    $table_exists = true;
}

$stats = [];
$monthly_data = [];
$status_distribution = [];
$priority_distribution = [];
$category_stats = [];
$admin_stats = [];

if ($table_exists) {
    // Statistics
    $stats['total'] = $conn->query("SELECT COUNT(*) as total FROM support_tickets")->fetch_assoc()['total'];
    $stats['open'] = $conn->query("SELECT COUNT(*) as total FROM support_tickets WHERE status = 'open'")->fetch_assoc()['total'];
    $stats['resolved'] = $conn->query("SELECT COUNT(*) as total FROM support_tickets WHERE status IN ('resolved', 'closed')")->fetch_assoc()['total'];
    $stats['in_range'] = $conn->query("SELECT COUNT(*) as total FROM support_tickets WHERE created_at BETWEEN '{$start_datetime}' AND '{$end_datetime}'")->fetch_assoc()['total'];
    
    // Average resolution time
    $avg_resolution = $conn->query("SELECT AVG(TIMESTAMPDIFF(HOUR, created_at, resolved_at)) as avg_hours FROM support_tickets WHERE resolved_at IS NOT NULL")->fetch_assoc()['avg_hours'] ?? 0;
    $stats['avg_resolution'] = round($avg_resolution, 1);
    
    // Monthly ticket trend
    $months = [];
    for ($i = 11; $i >= 0; $i--) {
        $month = date('Y-m', strtotime("-$i months"));
        $months[] = date('M Y', strtotime("-$i months"));
        $monthly_data[$month] = 0;
    }
    $monthly_query = "SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(id) as count 
                      FROM support_tickets 
                      WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH) 
                      GROUP BY month ORDER BY month ASC";
    $monthly_result = $conn->query($monthly_query);
    while ($row = $monthly_result->fetch_assoc()) {
        if (isset($monthly_data[$row['month']])) {
            $monthly_data[$row['month']] = $row['count'];
        }
    }
    
    // Status distribution
    $status_query = "SELECT status, COUNT(*) as count FROM support_tickets GROUP BY status";
    $status_result = $conn->query($status_query);
    while ($row = $status_result->fetch_assoc()) {
        $status_distribution[$row['status']] = $row['count'];
    }
    
    // Priority distribution
    $priority_query = "SELECT priority, COUNT(*) as count FROM support_tickets GROUP BY priority";
    $priority_result = $conn->query($priority_query);
    while ($row = $priority_result->fetch_assoc()) {
        $priority_distribution[$row['priority']] = $row['count'];
    }
    
    // Category statistics
    $category_query = "SELECT category, COUNT(*) as count FROM support_tickets WHERE category IS NOT NULL GROUP BY category ORDER BY count DESC LIMIT 10";
    $category_result = $conn->query($category_query);
    while ($row = $category_result->fetch_assoc()) {
        $category_stats[] = $row;
    }
    
    // Admin performance
    $admin_query = "SELECT u.id, u.username, 
                    COUNT(st.id) as total_tickets,
                    SUM(CASE WHEN st.status IN ('resolved', 'closed') THEN 1 ELSE 0 END) as resolved_tickets,
                    AVG(CASE WHEN st.resolved_at IS NOT NULL THEN TIMESTAMPDIFF(HOUR, st.created_at, st.resolved_at) ELSE NULL END) as avg_resolution_hours
                    FROM user u
                    LEFT JOIN support_tickets st ON u.id = st.assigned_to
                    WHERE st.assigned_to IS NOT NULL
                    GROUP BY u.id, u.username
                    ORDER BY total_tickets DESC";
    $admin_result = $conn->query($admin_query);
    while ($row = $admin_result->fetch_assoc()) {
        $admin_stats[] = $row;
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
                                <h4 class="page-title">Support Reports</h4>
                                <div>
                                    <a href="support_dashboard.php" class="btn btn-secondary me-2">
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
                                                <option value="custom" <?php echo $date_range == 'custom' ? 'selected' : ''; ?>>Custom Range</option>
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
                            <h5 class="alert-heading"><i class="ri-alert-line"></i> Support Tickets Table Not Found</h5>
                            <p>The <code>support_tickets</code> table does not exist in the database.</p>
                            <p class="mb-0">See <a href="support_dashboard.php">Support Dashboard</a> for the required table structure.</p>
                        </div>
                    <?php else: ?>

                    <!-- Summary Statistics -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-3">
                            <div class="card bg-primary bg-opacity-10 border-primary">
                                <div class="card-body">
                                    <h6 class="text-muted mb-1">Total Tickets</h6>
                                    <h3 class="mb-0"><?php echo number_format($stats['total']); ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-warning bg-opacity-10 border-warning">
                                <div class="card-body">
                                    <h6 class="text-muted mb-1">Open Tickets</h6>
                                    <h3 class="mb-0"><?php echo number_format($stats['open']); ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-success bg-opacity-10 border-success">
                                <div class="card-body">
                                    <h6 class="text-muted mb-1">Resolved</h6>
                                    <h3 class="mb-0"><?php echo number_format($stats['resolved']); ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-info bg-opacity-10 border-info">
                                <div class="card-body">
                                    <h6 class="text-muted mb-1">Avg Resolution Time</h6>
                                    <h3 class="mb-0"><?php echo $stats['avg_resolution']; ?> hrs</h3>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Charts Row -->
                    <div class="row mb-3">
                        <div class="col-lg-8">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Ticket Trend (12 Months)</h4>
                                </div>
                                <div class="card-body">
                                    <div id="ticket-trend-chart" style="min-height: 350px;"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Status Distribution</h4>
                                </div>
                                <div class="card-body">
                                    <div id="status-distribution-chart" style="min-height: 350px;"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Category & Priority Stats -->
                    <div class="row mb-3">
                        <div class="col-lg-6">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Top Categories</h4>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-sm mb-0">
                                            <thead>
                                                <tr>
                                                    <th>Category</th>
                                                    <th>Count</th>
                                                    <th>Percentage</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($category_stats as $cat): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($cat['category']); ?></td>
                                                        <td><span class="badge bg-primary"><?php echo number_format($cat['count']); ?></span></td>
                                                        <td><?php echo $stats['total'] > 0 ? number_format(($cat['count'] / $stats['total']) * 100, 1) : 0; ?>%</td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Priority Distribution</h4>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-sm mb-0">
                                            <thead>
                                                <tr>
                                                    <th>Priority</th>
                                                    <th>Count</th>
                                                    <th>Percentage</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($priority_distribution as $priority => $count): ?>
                                                    <tr>
                                                        <td><span class="badge bg-<?php 
                                                            echo $priority == 'urgent' ? 'danger' : 
                                                                ($priority == 'high' ? 'warning' : 
                                                                ($priority == 'medium' ? 'info' : 'secondary')); 
                                                        ?>"><?php echo ucfirst($priority); ?></span></td>
                                                        <td><?php echo number_format($count); ?></td>
                                                        <td><?php echo $stats['total'] > 0 ? number_format(($count / $stats['total']) * 100, 1) : 0; ?>%</td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Admin Performance -->
                    <?php if (!empty($admin_stats)): ?>
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Admin Performance</h4>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-hover mb-0">
                                            <thead>
                                                <tr>
                                                    <th>Admin</th>
                                                    <th>Total Tickets</th>
                                                    <th>Resolved</th>
                                                    <th>Resolution Rate</th>
                                                    <th>Avg Resolution Time</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($admin_stats as $admin): ?>
                                                    <tr>
                                                        <td><strong><?php echo htmlspecialchars($admin['username']); ?></strong></td>
                                                        <td><?php echo number_format($admin['total_tickets']); ?></td>
                                                        <td><?php echo number_format($admin['resolved_tickets']); ?></td>
                                                        <td><?php echo $admin['total_tickets'] > 0 ? number_format(($admin['resolved_tickets'] / $admin['total_tickets']) * 100, 1) : 0; ?>%</td>
                                                        <td><?php echo $admin['avg_resolution_hours'] ? number_format($admin['avg_resolution_hours'], 1) . ' hrs' : 'N/A'; ?></td>
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
        // Ticket Trend Chart
        var ticketTrendOptions = {
            chart: {
                type: 'line',
                height: 350,
                toolbar: { show: false }
            },
            series: [{
                name: 'Tickets',
                data: <?php echo json_encode(array_values($monthly_data)); ?>
            }],
            xaxis: {
                categories: <?php echo json_encode($months); ?>
            },
            colors: ['#3e60d5'],
            stroke: { width: 3 },
            markers: { size: 5 }
        };
        new ApexCharts(document.querySelector("#ticket-trend-chart"), ticketTrendOptions).render();

        // Status Distribution Chart
        var statusChartOptions = {
            chart: {
                type: 'donut',
                height: 350
            },
            series: <?php echo json_encode(array_values($status_distribution)); ?>,
            labels: <?php echo json_encode(array_map(function($s) { return ucfirst(str_replace('_', ' ', $s)); }, array_keys($status_distribution))); ?>,
            colors: ['#f7b84b', '#0acf97', '#727cf5', '#fa5c7c', '#6c757d'],
            legend: { position: 'bottom' }
        };
        new ApexCharts(document.querySelector("#status-distribution-chart"), statusChartOptions).render();
    </script>
    <?php endif; ?>

</body>
</html>

