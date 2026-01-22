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
    header('Content-Disposition: attachment; filename=payment_reports_' . date('Y-m-d') . '.csv');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'Member Name', 'Email', 'Payment Duration', 'Payment Option', 'Bank Slip', 'Registration Date']);
    
    $start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
    $end_date = $_GET['end_date'] ?? date('Y-m-d');
    $start_datetime = $start_date . ' 00:00:00';
    $end_datetime = $end_date . ' 23:59:59';
    
    $stmt = $conn->prepare("SELECT id, fullname, email, payment_duration, payment_option, bank_slip, created_at 
                           FROM registrations 
                           WHERE created_at BETWEEN ? AND ? 
                           ORDER BY created_at DESC");
    $stmt->bind_param("ss", $start_datetime, $end_datetime);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, [
            $row['id'],
            $row['fullname'],
            $row['email'],
            $row['payment_duration'],
            $row['payment_option'],
            $row['bank_slip'] ? 'Yes' : 'No',
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

// Total registrations with payment info
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM registrations WHERE created_at BETWEEN ? AND ?");
$stmt->bind_param("ss", $start_datetime, $end_datetime);
$stmt->execute();
$result = $stmt->get_result();
$stats['total_registrations'] = $result->fetch_assoc()['total'] ?? 0;
$stmt->close();

// Payment duration breakdown
$payment_duration = [];
$stmt = $conn->prepare("SELECT payment_duration, COUNT(*) as count FROM registrations WHERE created_at BETWEEN ? AND ? AND payment_duration IS NOT NULL AND payment_duration != '' GROUP BY payment_duration ORDER BY count DESC");
$stmt->bind_param("ss", $start_datetime, $end_datetime);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $payment_duration[] = $row;
}
$stmt->close();

// Payment option breakdown
$payment_option = [];
$stmt = $conn->prepare("SELECT payment_option, COUNT(*) as count FROM registrations WHERE created_at BETWEEN ? AND ? AND payment_option IS NOT NULL AND payment_option != '' GROUP BY payment_option ORDER BY count DESC");
$stmt->bind_param("ss", $start_datetime, $end_datetime);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $payment_option[] = $row;
}
$stmt->close();

// With bank slip
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM registrations WHERE created_at BETWEEN ? AND ? AND bank_slip IS NOT NULL AND bank_slip != ''");
$stmt->bind_param("ss", $start_datetime, $end_datetime);
$stmt->execute();
$result = $stmt->get_result();
$stats['with_bank_slip'] = $result->fetch_assoc()['total'] ?? 0;
$stmt->close();

// Monthly payment trend (last 12 months)
$monthly_trend = [];
$months = [];
for ($i = 11; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $months[] = date('M Y', strtotime("-$i months"));
    $monthly_trend[$month] = 0;
}

$monthly_query = "SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(id) as count 
                  FROM registrations 
                  WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH) 
                  GROUP BY month ORDER BY month ASC";
$monthly_result = $conn->query($monthly_query);
while ($row = $monthly_result->fetch_assoc()) {
    if (isset($monthly_trend[$row['month']])) {
        $monthly_trend[$row['month']] = $row['count'];
    }
}

// Recent registrations with payment details
$recent_registrations = [];
$stmt = $conn->prepare("SELECT id, fullname, email, payment_duration, payment_option, bank_slip, created_at 
                       FROM registrations 
                       WHERE created_at BETWEEN ? AND ? 
                       ORDER BY created_at DESC 
                       LIMIT 100");
$stmt->bind_param("ss", $start_datetime, $end_datetime);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $recent_registrations[] = $row;
}
$stmt->close();
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
                                <h4 class="page-title">Payment Reports</h4>
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
                                            <a href="reports_payments.php" class="btn btn-secondary">Reset</a>
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
                                        <i class="ri-money-dollar-circle-line widget-icon"></i>
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
                                        <i class="ri-bank-card-line widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0">With Bank Slip</h6>
                                    <h2 class="my-2"><?php echo number_format($stats['with_bank_slip']); ?></h2>
                                    <p class="mb-0">
                                        <span class="text-white-50"><?php echo $stats['total_registrations'] > 0 ? round(($stats['with_bank_slip'] / $stats['total_registrations']) * 100, 1) : 0; ?>% of total</span>
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
                                    <h2 class="my-2"><?php echo count($payment_duration); ?></h2>
                                    <p class="mb-0">
                                        <span class="text-white-50">Different types</span>
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
                                    <h2 class="my-2"><?php echo count($payment_option); ?></h2>
                                    <p class="mb-0">
                                        <span class="text-white-50">Different options</span>
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
                                    <h4 class="header-title">Monthly Payment Trend</h4>
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

                    <!-- Distribution Cards -->
                    <div class="row mb-4">
                        <div class="col-xl-6">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Payment Duration Breakdown</h4>
                                </div>
                                <div class="card-body">
                                    <?php if (!empty($payment_duration)): ?>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-hover mb-0">
                                                <thead>
                                                    <tr>
                                                        <th>Duration</th>
                                                        <th class="text-end">Count</th>
                                                        <th class="text-end">Percentage</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($payment_duration as $item): ?>
                                                        <tr>
                                                            <td><?php echo htmlspecialchars($item['payment_duration']); ?></td>
                                                            <td class="text-end"><?php echo number_format($item['count']); ?></td>
                                                            <td class="text-end"><?php echo $stats['total_registrations'] > 0 ? number_format(($item['count'] / $stats['total_registrations']) * 100, 1) : 0; ?>%</td>
                                                        </tr>
                                                    <?php endforeach; ?>
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
                                    <?php if (!empty($payment_option)): ?>
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
                                                    <?php foreach ($payment_option as $item): ?>
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

                    <!-- Recent Registrations Table -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Recent Registrations with Payment Details</h4>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped table-bordered dt-responsive nowrap datatable" style="width:100%">
                                            <thead>
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Member Name</th>
                                                    <th>Email</th>
                                                    <th>Payment Duration</th>
                                                    <th>Payment Option</th>
                                                    <th>Bank Slip</th>
                                                    <th>Registration Date</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($recent_registrations as $reg): ?>
                                                    <tr>
                                                        <td><?php echo $reg['id']; ?></td>
                                                        <td><?php echo htmlspecialchars($reg['fullname']); ?></td>
                                                        <td><?php echo htmlspecialchars($reg['email']); ?></td>
                                                        <td>
                                                            <span class="badge bg-primary"><?php echo htmlspecialchars($reg['payment_duration']); ?></span>
                                                        </td>
                                                        <td>
                                                            <span class="badge bg-success"><?php echo htmlspecialchars($reg['payment_option']); ?></span>
                                                        </td>
                                                        <td>
                                                            <?php if ($reg['bank_slip']): ?>
                                                                <span class="badge bg-info">Yes</span>
                                                            <?php else: ?>
                                                                <span class="badge bg-secondary">No</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td><?php echo date('M d, Y', strtotime($reg['created_at'])); ?></td>
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
            // Initialize DataTable
            $('.datatable').DataTable({
                responsive: true,
                order: [[6, 'desc']],
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
                    title: { text: 'Number of Registrations' }
                },
                tooltip: {
                    y: { formatter: function(val) { return val + ' registrations'; } }
                }
            };
            var monthlyChart = new ApexCharts(document.querySelector("#monthly-trend-chart"), monthlyOptions);
            monthlyChart.render();

            // Payment Duration Chart
            var durationOptions = {
                series: [<?php echo implode(',', array_column($payment_duration, 'count')); ?>],
                chart: {
                    type: 'donut',
                    height: 300
                },
                labels: [<?php echo "'" . implode("','", array_column($payment_duration, 'payment_duration')) . "'"; ?>],
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

