<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: auth-login.php");
    exit();
}

include 'include/conn.php';
include 'header.php';

// Handle CSV export
if (isset($_GET['export']) && $_GET['export'] == 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=finance_reports_' . date('Y-m-d') . '.csv');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Month', 'Total Registrations', 'Payment Duration', 'Estimated Revenue']);
    
    $start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-12 months'));
    $end_date = $_GET['end_date'] ?? date('Y-m-d');
    
    // Monthly revenue breakdown
    $monthly_query = "SELECT DATE_FORMAT(created_at, '%Y-%m') as month, 
                     COUNT(*) as total_registrations,
                     payment_duration,
                     COUNT(*) * CASE 
                         WHEN payment_duration = '1_year' THEN 1
                         WHEN payment_duration = '2_years' THEN 2
                         WHEN payment_duration = '3_years' THEN 3
                         ELSE 1
                     END as estimated_revenue
                     FROM registrations 
                     WHERE created_at BETWEEN ? AND ?
                     GROUP BY month, payment_duration
                     ORDER BY month ASC";
    
    $stmt = $conn->prepare($monthly_query);
    $start_datetime = $start_date . ' 00:00:00';
    $end_datetime = $end_date . ' 23:59:59';
    $stmt->bind_param("ss", $start_datetime, $end_datetime);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, [
            $row['month'],
            $row['total_registrations'],
            $row['payment_duration'],
            $row['estimated_revenue']
        ]);
    }
    
    fclose($output);
    exit();
}

// Get date range filter
$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-12 months'));
$end_date = $_GET['end_date'] ?? date('Y-m-d');

// Sanitize dates
$start_date = date('Y-m-d', strtotime($start_date));
$end_date = date('Y-m-d', strtotime($end_date));

// Validate dates
if (!$start_date || !$end_date || $start_date === '1970-01-01' || $end_date === '1970-01-01') {
    $start_date = date('Y-m-d', strtotime('-12 months'));
    $end_date = date('Y-m-d');
}

$start_datetime = $start_date . ' 00:00:00';
$end_datetime = $end_date . ' 23:59:59';

// Statistics
$stats = [];

// Total registrations
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM registrations WHERE created_at BETWEEN ? AND ?");
$stmt->bind_param("ss", $start_datetime, $end_datetime);
$stmt->execute();
$result = $stmt->get_result();
$stats['total_registrations'] = $result->fetch_assoc()['total'] ?? 0;
$stmt->close();

// Approved registrations
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM registrations WHERE approval_status = 'approved' AND created_at BETWEEN ? AND ?");
$stmt->bind_param("ss", $start_datetime, $end_datetime);
$stmt->execute();
$result = $stmt->get_result();
$stats['approved_registrations'] = $result->fetch_assoc()['total'] ?? 0;
$stmt->close();

// Payment duration breakdown
$payment_duration_stats = [];
$stmt = $conn->prepare("SELECT payment_duration, COUNT(*) as count FROM registrations WHERE created_at BETWEEN ? AND ? AND payment_duration IS NOT NULL AND payment_duration != '' GROUP BY payment_duration ORDER BY count DESC");
$stmt->bind_param("ss", $start_datetime, $end_datetime);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $payment_duration_stats[] = $row;
}
$stmt->close();

// Payment option breakdown
$payment_option_stats = [];
$stmt = $conn->prepare("SELECT payment_option, COUNT(*) as count FROM registrations WHERE created_at BETWEEN ? AND ? AND payment_option IS NOT NULL AND payment_option != '' GROUP BY payment_option ORDER BY count DESC");
$stmt->bind_param("ss", $start_datetime, $end_datetime);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $payment_option_stats[] = $row;
}
$stmt->close();

// Monthly revenue trend (simplified - assuming base price of 1 unit per year)
$monthly_trend = [];
$months = [];
for ($i = 11; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $months[] = date('M Y', strtotime("-$i months"));
    $monthly_trend[$month] = 0;
}

$monthly_query = "SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(id) as count 
                  FROM registrations 
                  WHERE approval_status = 'approved' AND created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH) 
                  GROUP BY month ORDER BY month ASC";
$monthly_result = $conn->query($monthly_query);
while ($row = $monthly_result->fetch_assoc()) {
    if (isset($monthly_trend[$row['month']])) {
        // Simplified calculation - each registration counts as revenue unit
        $monthly_trend[$row['month']] = $row['count'];
    }
}

// Revenue by payment duration (simplified)
$revenue_by_duration = [];
foreach ($payment_duration_stats as $duration_stat) {
    $duration = $duration_stat['payment_duration'];
    $count = $duration_stat['count'];
    
    // Simple multiplier based on duration
    $multiplier = 1;
    if (strpos($duration, '2') !== false) $multiplier = 2;
    if (strpos($duration, '3') !== false) $multiplier = 3;
    
    $revenue_by_duration[] = [
        'duration' => $duration,
        'count' => $count,
        'estimated_revenue' => $count * $multiplier
    ];
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
                                <h4 class="page-title">Finance Reports</h4>
                                <div>
                                    <a href="reports_dashboard.php" class="btn btn-secondary me-2">
                                        <i class="ri-dashboard-line"></i> Reports Dashboard
                                    </a>
                                    <a href="?export=csv&start_date=<?php echo urlencode($start_date); ?>&end_date=<?php echo urlencode($end_date); ?>" class="btn btn-success">
                                        <i class="ri-download-line"></i> Export CSV
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

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
                                            <a href="reports_finance.php" class="btn btn-secondary">Reset</a>
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
                                        <i class="ri-team-line widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0">Total Registrations</h6>
                                    <h2 class="my-2"><?php echo number_format($stats['total_registrations']); ?></h2>
                                    <p class="mb-0">
                                        <span class="text-white-50">In selected period</span>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6">
                            <div class="card widget-flat text-bg-success">
                                <div class="card-body">
                                    <div class="float-end">
                                        <i class="ri-check-line widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0">Approved Members</h6>
                                    <h2 class="my-2"><?php echo number_format($stats['approved_registrations']); ?></h2>
                                    <p class="mb-0">
                                        <span class="text-white-50"><?php echo $stats['total_registrations'] > 0 ? round(($stats['approved_registrations'] / $stats['total_registrations']) * 100, 1) : 0; ?>% approval rate</span>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6">
                            <div class="card widget-flat text-bg-info">
                                <div class="card-body">
                                    <div class="float-end">
                                        <i class="ri-calendar-line widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0">Payment Durations</h6>
                                    <h2 class="my-2"><?php echo count($payment_duration_stats); ?></h2>
                                    <p class="mb-0">
                                        <span class="text-white-50">Different options</span>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6">
                            <div class="card widget-flat text-bg-warning">
                                <div class="card-body">
                                    <div class="float-end">
                                        <i class="ri-payment-line widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0">Payment Methods</h6>
                                    <h2 class="my-2"><?php echo count($payment_option_stats); ?></h2>
                                    <p class="mb-0">
                                        <span class="text-white-50">Available options</span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Charts Row -->
                    <div class="row mb-4">
                        <div class="col-xl-6">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Monthly Registration Trend</h4>
                                </div>
                                <div class="card-body">
                                    <div id="monthly-trend-chart" style="height: 300px;"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-6">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Payment Duration Distribution</h4>
                                </div>
                                <div class="card-body">
                                    <div id="payment-duration-chart" style="height: 300px;"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Breakdown Cards -->
                    <div class="row mb-4">
                        <div class="col-xl-6">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Revenue by Payment Duration</h4>
                                    <p class="text-muted mb-0 small">Estimated revenue (simplified calculation)</p>
                                </div>
                                <div class="card-body">
                                    <?php if (!empty($revenue_by_duration)): ?>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-hover mb-0">
                                                <thead>
                                                    <tr>
                                                        <th>Payment Duration</th>
                                                        <th class="text-end">Count</th>
                                                        <th class="text-end">Est. Revenue Units</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php 
                                                    $total_revenue = 0;
                                                    foreach ($revenue_by_duration as $item): 
                                                        $total_revenue += $item['estimated_revenue'];
                                                    ?>
                                                        <tr>
                                                            <td><?php echo htmlspecialchars($item['duration']); ?></td>
                                                            <td class="text-end"><?php echo number_format($item['count']); ?></td>
                                                            <td class="text-end"><?php echo number_format($item['estimated_revenue']); ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                    <tr class="table-primary fw-bold">
                                                        <td>Total</td>
                                                        <td class="text-end"><?php echo number_format($stats['approved_registrations']); ?></td>
                                                        <td class="text-end"><?php echo number_format($total_revenue); ?></td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php else: ?>
                                        <p class="text-muted mb-0">No payment duration data available.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-6">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Payment Option Breakdown</h4>
                                </div>
                                <div class="card-body">
                                    <?php if (!empty($payment_option_stats)): ?>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-hover mb-0">
                                                <thead>
                                                    <tr>
                                                        <th>Payment Option</th>
                                                        <th class="text-end">Count</th>
                                                        <th class="text-end">Percentage</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($payment_option_stats as $item): ?>
                                                        <tr>
                                                            <td><?php echo htmlspecialchars($item['payment_option']); ?></td>
                                                            <td class="text-end"><?php echo number_format($item['count']); ?></td>
                                                            <td class="text-end"><?php echo $stats['total_registrations'] > 0 ? number_format(($item['count'] / $stats['total_registrations']) * 100, 1) : 0; ?>%</td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php else: ?>
                                        <p class="text-muted mb-0">No payment option data available.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Note -->
                    <div class="row">
                        <div class="col-12">
                            <div class="alert alert-info">
                                <i class="ri-information-line"></i>
                                <strong>Note:</strong> Revenue calculations are simplified estimates based on registration counts and payment durations. 
                                For accurate financial reporting, please integrate with your actual payment processing system.
                            </div>
                        </div>
                    </div>

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

    <script>
        $(document).ready(function() {
            // Monthly Trend Chart
            var monthlyOptions = {
                series: [{
                    name: 'Approved Registrations',
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
                    title: { text: 'Number of Registrations' }
                },
                tooltip: {
                    y: { formatter: function(val) { return val + ' registrations'; } }
                }
            };
            var monthlyChart = new ApexCharts(document.querySelector("#monthly-trend-chart"), monthlyOptions);
            monthlyChart.render();

            // Payment Duration Chart
            var durationData = <?php echo json_encode(array_column($payment_duration_stats, 'count')); ?>;
            var durationLabels = <?php echo json_encode(array_column($payment_duration_stats, 'payment_duration')); ?>;
            
            var durationOptions = {
                series: durationData,
                chart: {
                    type: 'donut',
                    height: 300
                },
                labels: durationLabels,
                colors: ['#727cf5', '#0acf97', '#fa5c7c', '#ffbc00', '#39afd1'],
                legend: {
                    position: 'bottom'
                }
            };
            var durationChart = new ApexCharts(document.querySelector("#payment-duration-chart"), durationOptions);
            durationChart.render();
        });
    </script>
</body>
</html>

