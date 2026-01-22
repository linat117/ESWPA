<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: auth-login.php");
    exit();
}

include 'include/conn.php';
include 'header.php';

// This is a placeholder for detailed accounting reports
// In a real system, this would integrate with actual accounting/financial data

// Handle CSV export
if (isset($_GET['export']) && $_GET['export'] == 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=accounting_reports_' . date('Y-m-d') . '.csv');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Date', 'Transaction Type', 'Member ID', 'Member Name', 'Amount', 'Payment Method', 'Status']);
    
    // Get detailed registration data as accounting entries
    $start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-12 months'));
    $end_date = $_GET['end_date'] ?? date('Y-m-d');
    $start_datetime = $start_date . ' 00:00:00';
    $end_datetime = $end_date . ' 23:59:59';
    
    $stmt = $conn->prepare("SELECT created_at, id, fullname, payment_duration, payment_option, approval_status 
                           FROM registrations 
                           WHERE created_at BETWEEN ? AND ? 
                           ORDER BY created_at DESC");
    $stmt->bind_param("ss", $start_datetime, $end_datetime);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        // Simplified: each approved registration is a transaction
        if ($row['approval_status'] == 'approved') {
            fputcsv($output, [
                $row['created_at'],
                'Membership Registration',
                $row['id'],
                $row['fullname'],
                '1', // Placeholder amount
                $row['payment_option'] ?? 'N/A',
                'Completed'
            ]);
        }
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

// Total transactions (approved registrations)
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM registrations WHERE approval_status = 'approved' AND created_at BETWEEN ? AND ?");
$stmt->bind_param("ss", $start_datetime, $end_datetime);
$stmt->execute();
$result = $stmt->get_result();
$stats['total_transactions'] = $result->fetch_assoc()['total'] ?? 0;
$stmt->close();

// Pending transactions
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM registrations WHERE approval_status = 'pending' AND created_at BETWEEN ? AND ?");
$stmt->bind_param("ss", $start_datetime, $end_datetime);
$stmt->execute();
$result = $stmt->get_result();
$stats['pending_transactions'] = $result->fetch_assoc()['total'] ?? 0;
$stmt->close();

// Transactions by payment method
$transactions_by_method = [];
$stmt = $conn->prepare("SELECT payment_option, COUNT(*) as count FROM registrations WHERE approval_status = 'approved' AND created_at BETWEEN ? AND ? AND payment_option IS NOT NULL AND payment_option != '' GROUP BY payment_option ORDER BY count DESC");
$stmt->bind_param("ss", $start_datetime, $end_datetime);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $transactions_by_method[] = $row;
}
$stmt->close();

// Daily transaction breakdown
$daily_breakdown = [];
$daily_query = "SELECT DATE(created_at) as date, COUNT(*) as count 
                FROM registrations 
                WHERE approval_status = 'approved' AND created_at BETWEEN ? AND ? 
                GROUP BY DATE(created_at) 
                ORDER BY date DESC 
                LIMIT 30";
$stmt = $conn->prepare($daily_query);
$stmt->bind_param("ss", $start_datetime, $end_datetime);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $daily_breakdown[] = $row;
}
$stmt->close();

// Recent transactions
$recent_transactions = [];
$stmt = $conn->prepare("SELECT id, fullname, payment_duration, payment_option, approval_status, created_at 
                       FROM registrations 
                       WHERE created_at BETWEEN ? AND ? 
                       ORDER BY created_at DESC 
                       LIMIT 100");
$stmt->bind_param("ss", $start_datetime, $end_datetime);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $recent_transactions[] = $row;
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
                                <h4 class="page-title">Accounting Reports</h4>
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
                                            <a href="reports_accounting.php" class="btn btn-secondary">Reset</a>
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
                                        <i class="ri-file-list-3-line widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0">Total Transactions</h6>
                                    <h2 class="my-2"><?php echo number_format($stats['total_transactions']); ?></h2>
                                    <p class="mb-0">
                                        <span class="text-white-50">Completed</span>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6">
                            <div class="card widget-flat text-bg-warning">
                                <div class="card-body">
                                    <div class="float-end">
                                        <i class="ri-time-line widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0">Pending</h6>
                                    <h2 class="my-2"><?php echo number_format($stats['pending_transactions']); ?></h2>
                                    <p class="mb-0">
                                        <span class="text-white-50">Awaiting approval</span>
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
                                    <h6 class="text-uppercase mt-0">Completion Rate</h6>
                                    <h2 class="my-2"><?php 
                                        $total = $stats['total_transactions'] + $stats['pending_transactions'];
                                        echo $total > 0 ? number_format(($stats['total_transactions'] / $total) * 100, 1) : 0; 
                                    ?>%</h2>
                                    <p class="mb-0">
                                        <span class="text-white-50">Of all transactions</span>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6">
                            <div class="card widget-flat text-bg-info">
                                <div class="card-body">
                                    <div class="float-end">
                                        <i class="ri-payment-line widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0">Payment Methods</h6>
                                    <h2 class="my-2"><?php echo count($transactions_by_method); ?></h2>
                                    <p class="mb-0">
                                        <span class="text-white-50">In use</span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Breakdown Cards -->
                    <div class="row mb-4">
                        <div class="col-xl-6">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Transactions by Payment Method</h4>
                                </div>
                                <div class="card-body">
                                    <?php if (!empty($transactions_by_method)): ?>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-hover mb-0">
                                                <thead>
                                                    <tr>
                                                        <th>Payment Method</th>
                                                        <th class="text-end">Count</th>
                                                        <th class="text-end">Percentage</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($transactions_by_method as $item): ?>
                                                        <tr>
                                                            <td><?php echo htmlspecialchars($item['payment_option']); ?></td>
                                                            <td class="text-end"><?php echo number_format($item['count']); ?></td>
                                                            <td class="text-end"><?php echo $stats['total_transactions'] > 0 ? number_format(($item['count'] / $stats['total_transactions']) * 100, 1) : 0; ?>%</td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php else: ?>
                                        <p class="text-muted mb-0">No payment method data available.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-6">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Daily Transaction Breakdown (Last 30 Days)</h4>
                                </div>
                                <div class="card-body">
                                    <?php if (!empty($daily_breakdown)): ?>
                                        <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                                            <table class="table table-sm table-hover mb-0">
                                                <thead class="sticky-top bg-light">
                                                    <tr>
                                                        <th>Date</th>
                                                        <th class="text-end">Transactions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($daily_breakdown as $item): ?>
                                                        <tr>
                                                            <td><?php echo date('M d, Y', strtotime($item['date'])); ?></td>
                                                            <td class="text-end"><?php echo number_format($item['count']); ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php else: ?>
                                        <p class="text-muted mb-0">No daily data available.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Transactions Table -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Recent Transactions</h4>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped table-bordered dt-responsive nowrap datatable" style="width:100%">
                                            <thead>
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Member Name</th>
                                                    <th>Payment Duration</th>
                                                    <th>Payment Method</th>
                                                    <th>Status</th>
                                                    <th>Date</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($recent_transactions as $transaction): ?>
                                                    <tr>
                                                        <td><?php echo $transaction['id']; ?></td>
                                                        <td><?php echo htmlspecialchars($transaction['fullname']); ?></td>
                                                        <td><?php echo htmlspecialchars($transaction['payment_duration'] ?? '-'); ?></td>
                                                        <td>
                                                            <span class="badge bg-secondary"><?php echo htmlspecialchars($transaction['payment_option'] ?? 'N/A'); ?></span>
                                                        </td>
                                                        <td>
                                                            <?php
                                                            $status_colors = [
                                                                'approved' => 'success',
                                                                'pending' => 'warning',
                                                                'rejected' => 'danger'
                                                            ];
                                                            $status = $transaction['approval_status'] ?? 'pending';
                                                            $status_color = $status_colors[$status] ?? 'secondary';
                                                            ?>
                                                            <span class="badge bg-<?php echo $status_color; ?>"><?php echo ucfirst($status); ?></span>
                                                        </td>
                                                        <td><?php echo date('M d, Y H:i', strtotime($transaction['created_at'])); ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Note -->
                    <div class="row">
                        <div class="col-12">
                            <div class="alert alert-info">
                                <i class="ri-information-line"></i>
                                <strong>Note:</strong> This accounting report is based on member registrations. For a complete accounting system, 
                                integrate with your payment gateway or accounting software to track actual financial transactions, invoices, and payments.
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
                order: [[5, 'desc']],
                pageLength: 25
            });
        });
    </script>
</body>
</html>

