<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: auth-login.php");
    exit();
}

include 'include/conn.php';
include 'header.php';

// Function to calculate payment amount based on qualification and duration
function calculatePaymentAmount($qualification, $payment_duration) {
    // Base prices per year in ETB
    $prices = [
        'student' => 20,
        'first_degree' => 100,  // BSW, B.A
        'second_degree' => 150, // MSW, M.A
        'third_degree' => 200,  // PhD, DSW
        'organization' => 3000,
        'default' => 100 // Default if qualification not recognized
    ];
    
    // Determine qualification type
    $qual_lower = strtolower($qualification ?? '');
    $base_price = $prices['default'];
    
    if (stripos($qual_lower, 'student') !== false) {
        $base_price = $prices['student'];
    } elseif (stripos($qual_lower, 'phd') !== false || stripos($qual_lower, 'dsw') !== false || stripos($qual_lower, 'doctor') !== false) {
        $base_price = $prices['third_degree'];
    } elseif (stripos($qual_lower, 'msw') !== false || stripos($qual_lower, 'm.a') !== false || stripos($qual_lower, 'master') !== false) {
        $base_price = $prices['second_degree'];
    } elseif (stripos($qual_lower, 'bsw') !== false || stripos($qual_lower, 'b.a') !== false || stripos($qual_lower, 'bachelor') !== false) {
        $base_price = $prices['first_degree'];
    } elseif (stripos($qual_lower, 'organization') !== false || stripos($qual_lower, 'org') !== false) {
        $base_price = $prices['organization'];
    }
    
    // Extract years from payment duration
    $years = 1;
    if (preg_match('/(\d+)/', $payment_duration ?? '', $matches)) {
        $years = (int)$matches[1];
    }
    
    return $base_price * $years;
}

// Handle CSV export
if (isset($_GET['export']) && $_GET['export'] == 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=finance_reports_' . date('Y-m-d') . '.csv');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Date', 'Member Name', 'Membership ID', 'Email', 'Qualification', 'Payment Duration', 'Payment Method', 'Amount (ETB)', 'Status', 'Approval Status', 'Registration Date', 'Expiry Date']);
    
    $start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-12 months'));
    $end_date = $_GET['end_date'] ?? date('Y-m-d');
    $start_datetime = $start_date . ' 00:00:00';
    $end_datetime = $end_date . ' 23:59:59';
    
    $export_query = "SELECT fullname, membership_id, email, qualification, payment_duration, payment_option, status, approval_status, created_at, expiry_date
                     FROM registrations 
                     WHERE created_at BETWEEN ? AND ?
                     ORDER BY created_at DESC";
    
    $stmt = $conn->prepare($export_query);
    $stmt->bind_param("ss", $start_datetime, $end_datetime);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $amount = calculatePaymentAmount($row['qualification'], $row['payment_duration']);
        fputcsv($output, [
            date('Y-m-d', strtotime($row['created_at'])),
            $row['fullname'],
            $row['membership_id'] ?? 'N/A',
            $row['email'],
            $row['qualification'] ?? 'N/A',
            $row['payment_duration'] ?? 'N/A',
            $row['payment_option'] ?? 'N/A',
            number_format($amount, 2),
            $row['status'] ?? 'N/A',
            $row['approval_status'] ?? 'N/A',
            $row['created_at'],
            $row['expiry_date'] ?? 'N/A'
        ]);
    }
    
    fclose($output);
    exit();
}

// Get date range filter
$date_range = $_GET['range'] ?? '12'; // Default: last 12 months
$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime("-{$date_range} months"));
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

// Calculate date range in days
$date1 = new DateTime($start_date);
$date2 = new DateTime($end_date);
$days_diff = $date1->diff($date2)->days;

// ========== COMPREHENSIVE STATISTICS ==========
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

// Pending registrations
$stats['pending_registrations'] = $stats['total_registrations'] - $stats['approved_registrations'];

// Active members (not expired)
$active_members = 0;
$expired_members = 0;
$result_all = $conn->query("SELECT created_at, payment_duration, approval_status, status, expiry_date FROM registrations WHERE created_at BETWEEN '{$start_datetime}' AND '{$end_datetime}'");
while ($member = $result_all->fetch_assoc()) {
    if ($member['approval_status'] == 'approved' && $member['status'] == 'active') {
        if (!empty($member['expiry_date'])) {
            $expiry = new DateTime($member['expiry_date']);
            $today = new DateTime();
            if ($expiry > $today) {
                $active_members++;
            } else {
                $expired_members++;
            }
        } else {
            // Calculate expiry if not set
            $start_date_member = new DateTime($member['created_at']);
            $duration = $member['payment_duration'];
            $expiry_date = clone $start_date_member;
            
            if (strpos($duration, 'Year') !== false || strpos($duration, 'year') !== false) {
                $years = (int) filter_var($duration, FILTER_SANITIZE_NUMBER_INT);
                if ($years > 0) {
                    $expiry_date->modify("+$years year");
                }
            }
            
            $today = new DateTime();
            if ($expiry_date > $today) {
                $active_members++;
            } else {
                $expired_members++;
            }
        }
    }
}
$stats['active_members'] = $active_members;
$stats['expired_members'] = $expired_members;

// Members with bank slip (payment proof)
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM registrations WHERE bank_slip IS NOT NULL AND bank_slip != '' AND created_at BETWEEN ? AND ?");
$stmt->bind_param("ss", $start_datetime, $end_datetime);
$stmt->execute();
$result = $stmt->get_result();
$stats['with_payment_proof'] = $result->fetch_assoc()['total'] ?? 0;
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

// Status breakdown
$status_stats = [];
$stmt = $conn->prepare("SELECT status, COUNT(*) as count FROM registrations WHERE created_at BETWEEN ? AND ? AND status IS NOT NULL GROUP BY status ORDER BY count DESC");
$stmt->bind_param("ss", $start_datetime, $end_datetime);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $status_stats[] = $row;
}
$stmt->close();

// Approval status breakdown
$approval_stats = [];
$stmt = $conn->prepare("SELECT approval_status, COUNT(*) as count FROM registrations WHERE created_at BETWEEN ? AND ? AND approval_status IS NOT NULL GROUP BY approval_status ORDER BY count DESC");
$stmt->bind_param("ss", $start_datetime, $end_datetime);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $approval_stats[] = $row;
}
$stmt->close();

// Monthly registration trend (last 12 months)
$monthly_trend = [];
$months = [];
for ($i = 11; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $months[] = date('M Y', strtotime("-$i months"));
    $monthly_trend[$month] = 0;
}

$monthly_query = "SELECT DATE_FORMAT(created_at, '%Y-%m') as month, qualification, payment_duration 
                  FROM registrations 
                  WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH) 
";
$monthly_result = $conn->query($monthly_query);
while ($row = $monthly_result->fetch_assoc()) {
    if (isset($monthly_trend[$row['month']])) {
        $monthly_trend[$row['month']] = (int)$row['count'];
    }
}
$monthly_trend_values = array_values($monthly_trend);

// Monthly approved trend with revenue
$monthly_approved_trend = [];
$monthly_approved_revenue = [];
for ($i = 11; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $monthly_approved_trend[$month] = 0;
    $monthly_approved_revenue[$month] = 0;
}

$monthly_approved_query = "SELECT DATE_FORMAT(created_at, '%Y-m') as month, COUNT(id) as count 
                           FROM registrations 
                           WHERE approval_status = 'approved' AND created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH) 
                           ";
$monthly_approved_result = $conn->query($monthly_approved_query);
while ($row = $monthly_approved_result->fetch_assoc()) {
    $month = $row['month'];
    if (isset($monthly_approved_trend[$month])) {
        $monthly_approved_trend[$month]++;
        $monthly_approved_revenue[$month] += calculatePaymentAmount($row['qualification'], $row['payment_duration']);
    }
}
$monthly_approved_trend_values = array_values($monthly_approved_trend);
$monthly_approved_revenue_values = array_values($monthly_approved_revenue);

// Revenue calculation by payment duration with actual amounts
$revenue_by_duration = [];
$total_revenue = 0;

// Get all registrations with qualification and payment duration
$revenue_query = "SELECT qualification, payment_duration, COUNT(*) as count 
                  FROM registrations 
                  WHERE created_at BETWEEN ? AND ? 
                  AND payment_duration IS NOT NULL 
                  AND payment_duration != ''
                  GROUP BY qualification, payment_duration";
$stmt = $conn->prepare($revenue_query);
$stmt->bind_param("ss", $start_datetime, $end_datetime);
$stmt->execute();
$result = $stmt->get_result();

$revenue_by_duration_detail = [];
while ($row = $result->fetch_assoc()) {
    $amount = calculatePaymentAmount($row['qualification'], $row['payment_duration']);
    $total_for_group = $amount * $row['count'];
    $total_revenue += $total_for_group;
    
    $duration = $row['payment_duration'];
    if (!isset($revenue_by_duration_detail[$duration])) {
        $revenue_by_duration_detail[$duration] = [
            'duration' => $duration,
            'count' => 0,
            'total_amount' => 0
        ];
    }
    $revenue_by_duration_detail[$duration]['count'] += $row['count'];
    $revenue_by_duration_detail[$duration]['total_amount'] += $total_for_group;
}
$stmt->close();

// Convert to array format
foreach ($revenue_by_duration_detail as $item) {
    $years = 1;
    if (preg_match('/(\d+)/', $item['duration'], $matches)) {
        $years = (int)$matches[1];
    }
    $revenue_by_duration[] = [
        'duration' => $item['duration'],
        'count' => $item['count'],
        'years' => $years,
        'estimated_revenue' => $item['total_amount']
    ];
}

// Sort by revenue descending
usort($revenue_by_duration, function($a, $b) {
    return $b['estimated_revenue'] - $a['estimated_revenue'];
});

// Calculate total revenue from all approved registrations
$total_revenue_approved = 0;
$revenue_query_all = "SELECT qualification, payment_duration 
                       FROM registrations 
                       WHERE approval_status = 'approved' 
                       AND created_at BETWEEN ? AND ?
                       AND payment_duration IS NOT NULL 
                       AND payment_duration != ''";
$stmt = $conn->prepare($revenue_query_all);
$stmt->bind_param("ss", $start_datetime, $end_datetime);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $total_revenue_approved += calculatePaymentAmount($row['qualification'], $row['payment_duration']);
}
$stmt->close();

// Recent registrations with payment details
$recent_registrations = [];
$recent_query = "SELECT id, fullname, membership_id, email, payment_duration, payment_option, status, approval_status, created_at, expiry_date, bank_slip
                 FROM registrations 
                 WHERE created_at BETWEEN ? AND ?
                 ORDER BY created_at DESC 
                 LIMIT 100";
$stmt = $conn->prepare($recent_query);
$stmt->bind_param("ss", $start_datetime, $end_datetime);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $recent_registrations[] = $row;
}
$stmt->close();

// Growth rate calculation
$prev_start = date('Y-m-d', strtotime($start_date . " -{$days_diff} days"));
$prev_end = $start_date;
$prev_start_datetime = $prev_start . ' 00:00:00';
$prev_end_datetime = $prev_end . ' 23:59:59';

$stmt = $conn->prepare("SELECT COUNT(*) as total FROM registrations WHERE created_at BETWEEN ? AND ?");
$stmt->bind_param("ss", $prev_start_datetime, $prev_end_datetime);
$stmt->execute();
$result = $stmt->get_result();
$prev_registrations = $result->fetch_assoc()['total'] ?? 0;
$stmt->close();

$growth_rate = 0;
if ($prev_registrations > 0) {
    $growth_rate = (($stats['total_registrations'] - $prev_registrations) / $prev_registrations) * 100;
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
                                <div>
                                    <h4 class="page-title">Finance Reports</h4>
                                    <p class="page-subtitle mb-0">Comprehensive financial analysis and member payment insights</p>
                                </div>
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
                                    <form method="GET" class="row g-3 align-items-end">
                                        <div class="col-md-2">
                                            <label class="form-label">Quick Range</label>
                                            <select name="range" class="form-select" onchange="this.form.submit()">
                                                <option value="1" <?php echo $date_range == '1' ? 'selected' : ''; ?>>Last Month</option>
                                                <option value="3" <?php echo $date_range == '3' ? 'selected' : ''; ?>>Last 3 Months</option>
                                                <option value="6" <?php echo $date_range == '6' ? 'selected' : ''; ?>>Last 6 Months</option>
                                                <option value="12" <?php echo $date_range == '12' ? 'selected' : ''; ?>>Last 12 Months</option>
                                                <option value="24" <?php echo $date_range == '24' ? 'selected' : ''; ?>>Last 2 Years</option>
                                                <option value="custom" <?php echo $date_range == 'custom' ? 'selected' : ''; ?>>Custom Range</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Start Date</label>
                                            <input type="date" class="form-control" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>" required>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">End Date</label>
                                            <input type="date" class="form-control" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>" required>
                                        </div>
                                        <div class="col-md-4">
                                            <button type="submit" class="btn btn-primary me-2">
                                                <i class="ri-filter-line"></i> Apply Filter
                                            </button>
                                            <a href="reports_finance.php" class="btn btn-secondary">
                                                <i class="ri-refresh-line"></i> Reset
                                            </a>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Key Statistics Cards -->
                    <div class="row g-3 mb-4">
                        <!-- Total Registrations -->
                        <div class="col-xl-3 col-lg-4 col-md-6">
                            <div class="card widget-flat text-bg-primary">
                                <div class="card-body">
                                    <div class="float-end">
                                        <i class="ri-team-line widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0">Total Registrations</h6>
                                    <h2 class="my-2"><?php echo number_format($stats['total_registrations']); ?></h2>
                                    <p class="mb-0">
                                        <?php if ($growth_rate > 0): ?>
                                            <span class="text-success"><i class="ri-arrow-up-line"></i> <?php echo number_format($growth_rate, 1); ?>%</span>
                                        <?php elseif ($growth_rate < 0): ?>
                                            <span class="text-danger"><i class="ri-arrow-down-line"></i> <?php echo number_format(abs($growth_rate), 1); ?>%</span>
                                        <?php else: ?>
                                            <span class="text-white-50">No change</span>
                                        <?php endif; ?>
                                        <span class="text-white-50 ms-1">vs previous period</span>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Approved Members -->
                        <div class="col-xl-3 col-lg-4 col-md-6">
                            <div class="card widget-flat text-bg-success">
                                <div class="card-body">
                                    <div class="float-end">
                                        <i class="ri-checkbox-circle-line widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0">Approved Members</h6>
                                    <h2 class="my-2"><?php echo number_format($stats['approved_registrations']); ?></h2>
                                    <p class="mb-0">
                                        <span class="text-white-50">
                                            <?php echo $stats['total_registrations'] > 0 ? number_format(($stats['approved_registrations'] / $stats['total_registrations']) * 100, 1) : 0; ?>% approval rate
                                        </span>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Active Members -->
                        <div class="col-xl-3 col-lg-4 col-md-6">
                            <div class="card widget-flat text-bg-info">
                                <div class="card-body">
                                    <div class="float-end">
                                        <i class="ri-user-star-line widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0">Active Members</h6>
                                    <h2 class="my-2"><?php echo number_format($stats['active_members']); ?></h2>
                                    <p class="mb-0">
                                        <span class="text-white-50">Expired: <?php echo number_format($stats['expired_members']); ?></span>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Total Revenue -->
                        <div class="col-xl-3 col-lg-4 col-md-6">
                            <div class="card widget-flat text-bg-warning">
                                <div class="card-body">
                                    <div class="float-end">
                                        <i class="ri-money-dollar-circle-line widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0">Total Revenue</h6>
                                    <h2 class="my-2"><?php echo number_format($total_revenue_approved, 2); ?> ETB</h2>
                                    <p class="mb-0">
                                        <span class="text-white-50">From approved members</span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Charts Row 1 -->
                    <div class="row mb-4">
                        <!-- Monthly Registration & Revenue Trend -->
                        <div class="col-lg-8">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Monthly Registration & Revenue Trend (Last 12 Months)</h4>
                                </div>
                                <div class="card-body">
                                    <div id="monthly-trend-chart" style="min-height: 350px;"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Status Distribution -->
                        <div class="col-lg-4">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Membership Status</h4>
                                </div>
                                <div class="card-body">
                                    <div id="status-chart" style="min-height: 350px;"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Charts Row 2 -->
                    <div class="row mb-4">
                        <!-- Payment Duration Distribution -->
                        <div class="col-lg-6">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Payment Duration Distribution</h4>
                                </div>
                                <div class="card-body">
                                    <div id="payment-duration-chart" style="min-height: 300px;"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Payment Method Distribution -->
                        <div class="col-lg-6">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Payment Method Distribution</h4>
                                </div>
                                <div class="card-body">
                                    <div id="payment-method-chart" style="min-height: 300px;"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Revenue & Breakdown Tables -->
                    <div class="row mb-4">
                        <!-- Revenue by Duration -->
                        <div class="col-lg-6">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h4 class="header-title">Revenue by Payment Duration</h4>
                                    <span class="badge bg-primary">Total: <?php echo number_format($total_revenue, 2); ?> ETB</span>
                                </div>
                                <div class="card-body">
                                    <?php if (!empty($revenue_by_duration)): ?>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-hover mb-0">
                                                <thead>
                                                    <tr>
                                                        <th>Payment Duration</th>
                                                        <th class="text-end">Members</th>
                                                        <th class="text-end">Years</th>
                                                        <th class="text-end">Revenue (ETB)</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($revenue_by_duration as $item): ?>
                                                        <tr>
                                                            <td><strong><?php echo htmlspecialchars($item['duration']); ?></strong></td>
                                                            <td class="text-end"><?php echo number_format($item['count']); ?></td>
                                                            <td class="text-end"><?php echo $item['years']; ?></td>
                                                            <td class="text-end">
                                                                <span class="badge bg-success"><?php echo number_format($item['estimated_revenue'], 2); ?> ETB</span>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                    <tr class="table-primary fw-bold">
                                                        <td>Total</td>
                                                        <td class="text-end"><?php echo number_format($stats['approved_registrations']); ?></td>
                                                        <td class="text-end">-</td>
                                                        <td class="text-end"><strong><?php echo number_format($total_revenue, 2); ?> ETB</strong></td>
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

                        <!-- Payment Options Breakdown -->
                        <div class="col-lg-6">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Payment Options Breakdown</h4>
                                </div>
                                <div class="card-body">
                                    <?php if (!empty($payment_option_stats)): ?>
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
                                                    <?php foreach ($payment_option_stats as $item): ?>
                                                        <tr>
                                                            <td><strong><?php echo htmlspecialchars($item['payment_option']); ?></strong></td>
                                                            <td class="text-end"><?php echo number_format($item['count']); ?></td>
                                                            <td class="text-end">
                                                                <span class="badge bg-info">
                                                                    <?php echo $stats['total_registrations'] > 0 ? number_format(($item['count'] / $stats['total_registrations']) * 100, 1) : 0; ?>%
                                                                </span>
                                                            </td>
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

                    <!-- Detailed Member Payments Table -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h4 class="header-title">Member Payment Details</h4>
                                    <span class="badge bg-primary"><?php echo count($recent_registrations); ?> records</span>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-hover mb-0" id="paymentsTable">
                                            <thead>
                                                <tr>
                                                    <th>Member</th>
                                                    <th>Membership ID</th>
                                                    <th>Email</th>
                                                    <th>Qualification</th>
                                                    <th>Payment Duration</th>
                                                    <th>Payment Method</th>
                                                    <th class="text-end">Amount (ETB)</th>
                                                    <th>Status</th>
                                                    <th>Approval</th>
                                                    <th>Payment Proof</th>
                                                    <th>Registration Date</th>
                                                    <th>Expiry Date</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($recent_registrations as $reg): ?>
                                                    <tr>
                                                        <td><strong><?php echo htmlspecialchars($reg['fullname']); ?></strong></td>
                                                        <td><?php echo htmlspecialchars($reg['membership_id'] ?? 'N/A'); ?></td>
                                                        <td><?php echo htmlspecialchars($reg['email']); ?></td>
                                                        <td><?php echo htmlspecialchars($reg['qualification'] ?? 'N/A'); ?></td>
                                                        <td>
                                                            <span class="badge bg-secondary"><?php echo htmlspecialchars($reg['payment_duration'] ?? 'N/A'); ?></span>
                                                        </td>
                                                        <td><?php echo htmlspecialchars($reg['payment_option'] ?? 'N/A'); ?></td>
                                                        <td class="text-end">
                                                            <strong class="text-success"><?php echo number_format($reg['payment_amount'], 2); ?> ETB</strong>
                                                        </td>
                                                        <td>
                                                            <?php
                                                            $status_color = 'secondary';
                                                            if ($reg['status'] == 'active') $status_color = 'success';
                                                            elseif ($reg['status'] == 'expired') $status_color = 'danger';
                                                            elseif ($reg['status'] == 'pending') $status_color = 'warning';
                                                            ?>
                                                            <span class="badge bg-<?php echo $status_color; ?>">
                                                                <?php echo ucfirst($reg['status'] ?? 'N/A'); ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <?php
                                                            $approval_color = 'warning';
                                                            if ($reg['approval_status'] == 'approved') $approval_color = 'success';
                                                            elseif ($reg['approval_status'] == 'rejected') $approval_color = 'danger';
                                                            ?>
                                                            <span class="badge bg-<?php echo $approval_color; ?>">
                                                                <?php echo ucfirst($reg['approval_status'] ?? 'N/A'); ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <?php if (!empty($reg['bank_slip'])): ?>
                                                                <span class="badge bg-success"><i class="ri-check-line"></i> Yes</span>
                                                            <?php else: ?>
                                                                <span class="badge bg-secondary"><i class="ri-close-line"></i> No</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td><?php echo date('M d, Y', strtotime($reg['created_at'])); ?></td>
                                                        <td>
                                                            <?php 
                                                            if (!empty($reg['expiry_date'])) {
                                                                echo date('M d, Y', strtotime($reg['expiry_date']));
                                                            } else {
                                                                echo '<span class="text-muted">N/A</span>';
                                                            }
                                                            ?>
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

                    <!-- Info Note -->
                    <div class="row mt-3">
                        <div class="col-12">
                                <div class="alert alert-info">
                                <i class="ri-information-line"></i>
                                <strong>Pricing Structure:</strong> 
                                <ul class="mb-0 mt-2">
                                    <li>Students: 20 ETB per year</li>
                                    <li>First Degree (BSW, B.A): 100 ETB per year</li>
                                    <li>Second Degree (MSW, M.A): 150 ETB per year</li>
                                    <li>Third Degree (PhD, DSW): 200 ETB per year</li>
                                    <li>Organizations: 3000 ETB per year</li>
                                </ul>
                                <p class="mb-0 mt-2"><strong>Note:</strong> Amounts are calculated based on qualification and payment duration. Multi-year payments are calculated as (base price × number of years).</p>
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
            $('#paymentsTable').DataTable({
                "pageLength": 25,
                "order": [[10, "desc"]], // Sort by registration date
                "responsive": true,
                "language": {
                    "search": "Search:",
                    "lengthMenu": "Show _MENU_ entries per page",
                    "info": "Showing _START_ to _END_ of _TOTAL_ entries"
                },
                "columnDefs": [
                    { "type": "num", "targets": 6 } // Amount column for proper sorting
                ]
            });

            // Monthly Trend Chart (Dual Y-axis: Count and Revenue)
            var monthlyOptions = {
                series: [
                    {
                        name: 'Approved Registrations',
                        type: 'column',
                        data: <?php echo json_encode($monthly_approved_trend_values); ?>
                    },
                    {
                        name: 'Revenue (ETB)',
                        type: 'line',
                        data: <?php echo json_encode($monthly_approved_revenue_values); ?>
                    }
                ],
                chart: {
                    height: 350,
                    type: 'line',
                    toolbar: { show: true }
                },
                colors: ['#0acf97', '#fa5c7c'],
                stroke: {
                    width: [0, 3],
                    curve: 'smooth'
                },
                markers: {
                    size: [0, 5]
                },
                xaxis: {
                    categories: <?php echo json_encode($months); ?>
                },
                yaxis: [
                    {
                        title: { text: 'Number of Registrations' },
                        labels: {
                            formatter: function(val) { return Math.round(val); }
                        }
                    },
                    {
                        opposite: true,
                        title: { text: 'Revenue (ETB)' },
                        labels: {
                            formatter: function(val) { return Math.round(val).toLocaleString(); }
                        }
                    }
                ],
                legend: {
                    position: 'top'
                },
                tooltip: {
                    shared: true,
                    y: [
                        {
                            formatter: function(val) { return val + ' registrations'; }
                        },
                        {
                            formatter: function(val) { return val.toLocaleString() + ' ETB'; }
                        }
                    ]
                }
            };
            var monthlyChart = new ApexCharts(document.querySelector("#monthly-trend-chart"), monthlyOptions);
            monthlyChart.render();

            // Status Distribution Chart
            var statusData = <?php echo json_encode(array_column($status_stats, 'count')); ?>;
            var statusLabels = <?php echo json_encode(array_column($status_stats, 'status')); ?>;
            
            if (statusData.length > 0) {
                var statusOptions = {
                    series: statusData,
                    chart: {
                        type: 'donut',
                        height: 350
                    },
                    labels: statusLabels,
                    colors: ['#0acf97', '#fa5c7c', '#ffbc00'],
                    legend: {
                        position: 'bottom'
                    },
                    dataLabels: {
                        enabled: true
                    }
                };
                var statusChart = new ApexCharts(document.querySelector("#status-chart"), statusOptions);
                statusChart.render();
            }

            // Payment Duration Chart
            var durationData = <?php echo json_encode(array_column($payment_duration_stats, 'count')); ?>;
            var durationLabels = <?php echo json_encode(array_column($payment_duration_stats, 'payment_duration')); ?>;
            
            if (durationData.length > 0) {
                var durationOptions = {
                    series: durationData,
                    chart: {
                        type: 'pie',
                        height: 300
                    },
                    labels: durationLabels,
                    colors: ['#727cf5', '#0acf97', '#fa5c7c', '#ffbc00', '#39afd1'],
                    legend: {
                        position: 'bottom'
                    },
                    dataLabels: {
                        enabled: true
                    }
                };
                var durationChart = new ApexCharts(document.querySelector("#payment-duration-chart"), durationOptions);
                durationChart.render();
            }

            // Payment Method Chart
            var methodData = <?php echo json_encode(array_column($payment_option_stats, 'count')); ?>;
            var methodLabels = <?php echo json_encode(array_column($payment_option_stats, 'payment_option')); ?>;
            
            if (methodData.length > 0) {
                var methodOptions = {
                    series: methodData,
                    chart: {
                        type: 'bar',
                        height: 300,
                        horizontal: true,
                        toolbar: { show: false }
                    },
                    plotOptions: {
                        bar: {
                            borderRadius: 4
                        }
                    },
                    dataLabels: {
                        enabled: true
                    },
                    xaxis: {
                        categories: methodLabels
                    },
                    colors: ['#39afd1']
                };
                var methodChart = new ApexCharts(document.querySelector("#payment-method-chart"), methodOptions);
                methodChart.render();
            }
        });
    </script>
</body>
</html>
