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
    header('Content-Disposition: attachment; filename=members_reports_' . date('Y-m-d') . '.csv');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'Membership ID', 'Name', 'Email', 'Phone', 'Sex', 'Qualification', 'Approval Status', 'Status', 'Expiry Date', 'ID Card Generated', 'Registration Date']);
    
    $start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
    $end_date = $_GET['end_date'] ?? date('Y-m-d');
    $start_datetime = $start_date . ' 00:00:00';
    $end_datetime = $end_date . ' 23:59:59';
    
    $stmt = $conn->prepare("SELECT id, membership_id, fullname, email, phone, sex, qualification, approval_status, status, expiry_date, id_card_generated, created_at 
                           FROM registrations 
                           WHERE created_at BETWEEN ? AND ? 
                           ORDER BY created_at DESC");
    $stmt->bind_param("ss", $start_datetime, $end_datetime);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, [
            $row['id'],
            $row['membership_id'] ?? '',
            $row['fullname'],
            $row['email'],
            $row['phone'],
            $row['sex'],
            $row['qualification'] ?? '',
            $row['approval_status'] ?? 'pending',
            $row['status'] ?? 'pending',
            $row['expiry_date'] ?? '',
            $row['id_card_generated'] ? 'Yes' : 'No',
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

// Total members
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM registrations WHERE created_at BETWEEN ? AND ?");
$stmt->bind_param("ss", $start_datetime, $end_datetime);
$stmt->execute();
$result = $stmt->get_result();
$stats['total_members'] = $result->fetch_assoc()['total'] ?? 0;
$stmt->close();

// Approval status breakdown
$approval_status = [];
$stmt = $conn->prepare("SELECT COALESCE(approval_status, 'pending') as status, COUNT(*) as count FROM registrations WHERE created_at BETWEEN ? AND ? GROUP BY status ORDER BY count DESC");
$stmt->bind_param("ss", $start_datetime, $end_datetime);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $approval_status[] = $row;
}
$stmt->close();

// Membership status breakdown
$membership_status = [];
$stmt = $conn->prepare("SELECT COALESCE(status, 'pending') as status, COUNT(*) as count FROM registrations WHERE created_at BETWEEN ? AND ? GROUP BY status ORDER BY count DESC");
$stmt->bind_param("ss", $start_datetime, $end_datetime);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $membership_status[] = $row;
}
$stmt->close();

// Gender breakdown
$gender_breakdown = [];
$stmt = $conn->prepare("SELECT sex, COUNT(*) as count FROM registrations WHERE created_at BETWEEN ? AND ? AND sex IS NOT NULL GROUP BY sex ORDER BY count DESC");
$stmt->bind_param("ss", $start_datetime, $end_datetime);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $gender_breakdown[] = $row;
}
$stmt->close();

// Qualification breakdown
$qualification_breakdown = [];
$stmt = $conn->prepare("SELECT qualification, COUNT(*) as count FROM registrations WHERE created_at BETWEEN ? AND ? AND qualification IS NOT NULL AND qualification != '' GROUP BY qualification ORDER BY count DESC LIMIT 10");
$stmt->bind_param("ss", $start_datetime, $end_datetime);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $qualification_breakdown[] = $row;
}
$stmt->close();

// With ID cards
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM registrations WHERE created_at BETWEEN ? AND ? AND id_card_generated = 1");
$stmt->bind_param("ss", $start_datetime, $end_datetime);
$stmt->execute();
$result = $stmt->get_result();
$stats['with_id_cards'] = $result->fetch_assoc()['total'] ?? 0;
$stmt->close();

// Expired members
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM registrations WHERE created_at BETWEEN ? AND ? AND expiry_date IS NOT NULL AND expiry_date < CURDATE() AND approval_status = 'approved'");
$stmt->bind_param("ss", $start_datetime, $end_datetime);
$stmt->execute();
$result = $stmt->get_result();
$stats['expired_members'] = $result->fetch_assoc()['total'] ?? 0;
$stmt->close();

// Monthly registration trend (last 12 months)
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

// Recent registrations
$recent_registrations = [];
$stmt = $conn->prepare("SELECT id, membership_id, fullname, email, phone, sex, qualification, approval_status, status, expiry_date, id_card_generated, created_at 
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
                                <h4 class="page-title">Members Reports</h4>
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
                                            <a href="reports_members.php" class="btn btn-secondary">Reset</a>
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
                                    <h6 class="text-uppercase mt-0">Total Members</h6>
                                    <h2 class="my-2"><?php echo number_format($stats['total_members']); ?></h2>
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
                                        <i class="ri-id-card-line widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0">With ID Cards</h6>
                                    <h2 class="my-2"><?php echo number_format($stats['with_id_cards']); ?></h2>
                                    <p class="mb-0">
                                        <span class="text-white-50"><?php echo $stats['total_members'] > 0 ? round(($stats['with_id_cards'] / $stats['total_members']) * 100, 1) : 0; ?>% of total</span>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6">
                            <div class="card widget-flat text-bg-info">
                                <div class="card-body">
                                    <div class="float-end">
                                        <i class="ri-user-line widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0">Approval Statuses</h6>
                                    <h2 class="my-2"><?php echo count($approval_status); ?></h2>
                                    <p class="mb-0">
                                        <span class="text-white-50">Different statuses</span>
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
                                    <h6 class="text-uppercase mt-0">Expired Members</h6>
                                    <h2 class="my-2"><?php echo number_format($stats['expired_members']); ?></h2>
                                    <p class="mb-0">
                                        <span class="text-white-50">In selected period</span>
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
                                    <h4 class="header-title">Approval Status Distribution</h4>
                                </div>
                                <div class="card-body">
                                    <div id="approval-status-chart" style="height: 300px;"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Breakdown Cards -->
                    <div class="row mb-4">
                        <div class="col-xl-4">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Approval Status Breakdown</h4>
                                </div>
                                <div class="card-body">
                                    <?php if (!empty($approval_status)): ?>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-hover mb-0">
                                                <thead>
                                                    <tr>
                                                        <th>Status</th>
                                                        <th class="text-end">Count</th>
                                                        <th class="text-end">Percentage</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($approval_status as $item): ?>
                                                        <tr>
                                                            <td><?php echo htmlspecialchars(ucfirst($item['status'])); ?></td>
                                                            <td class="text-end"><?php echo number_format($item['count']); ?></td>
                                                            <td class="text-end"><?php echo $stats['total_members'] > 0 ? number_format(($item['count'] / $stats['total_members']) * 100, 1) : 0; ?>%</td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php else: ?>
                                        <p class="text-muted mb-0">No approval status data available.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-4">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Gender Breakdown</h4>
                                </div>
                                <div class="card-body">
                                    <?php if (!empty($gender_breakdown)): ?>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-hover mb-0">
                                                <thead>
                                                    <tr>
                                                        <th>Gender</th>
                                                        <th class="text-end">Count</th>
                                                        <th class="text-end">Percentage</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($gender_breakdown as $item): ?>
                                                        <tr>
                                                            <td><?php echo htmlspecialchars($item['sex']); ?></td>
                                                            <td class="text-end"><?php echo number_format($item['count']); ?></td>
                                                            <td class="text-end"><?php echo $stats['total_members'] > 0 ? number_format(($item['count'] / $stats['total_members']) * 100, 1) : 0; ?>%</td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php else: ?>
                                        <p class="text-muted mb-0">No gender data available.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-4">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Top Qualifications</h4>
                                </div>
                                <div class="card-body">
                                    <?php if (!empty($qualification_breakdown)): ?>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-hover mb-0">
                                                <thead>
                                                    <tr>
                                                        <th>Qualification</th>
                                                        <th class="text-end">Count</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($qualification_breakdown as $item): ?>
                                                        <tr>
                                                            <td><?php echo htmlspecialchars($item['qualification']); ?></td>
                                                            <td class="text-end"><?php echo number_format($item['count']); ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php else: ?>
                                        <p class="text-muted mb-0">No qualification data available.</p>
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
                                    <h4 class="header-title">Recent Member Registrations</h4>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped table-bordered dt-responsive nowrap datatable" style="width:100%">
                                            <thead>
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Membership ID</th>
                                                    <th>Name</th>
                                                    <th>Email</th>
                                                    <th>Phone</th>
                                                    <th>Gender</th>
                                                    <th>Qualification</th>
                                                    <th>Approval Status</th>
                                                    <th>ID Card</th>
                                                    <th>Registration Date</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($recent_registrations as $member): ?>
                                                    <tr>
                                                        <td><?php echo $member['id']; ?></td>
                                                        <td><?php echo htmlspecialchars($member['membership_id'] ?? '-'); ?></td>
                                                        <td><?php echo htmlspecialchars($member['fullname']); ?></td>
                                                        <td><?php echo htmlspecialchars($member['email']); ?></td>
                                                        <td><?php echo htmlspecialchars($member['phone']); ?></td>
                                                        <td><?php echo htmlspecialchars($member['sex']); ?></td>
                                                        <td><?php echo htmlspecialchars($member['qualification'] ?? '-'); ?></td>
                                                        <td>
                                                            <?php
                                                            $status = $member['approval_status'] ?? 'pending';
                                                            $status_colors = [
                                                                'approved' => 'success',
                                                                'pending' => 'warning',
                                                                'rejected' => 'danger'
                                                            ];
                                                            $status_color = $status_colors[$status] ?? 'secondary';
                                                            ?>
                                                            <span class="badge bg-<?php echo $status_color; ?>"><?php echo ucfirst($status); ?></span>
                                                        </td>
                                                        <td>
                                                            <?php if ($member['id_card_generated']): ?>
                                                                <span class="badge bg-success">Yes</span>
                                                            <?php else: ?>
                                                                <span class="badge bg-secondary">No</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td><?php echo date('M d, Y', strtotime($member['created_at'])); ?></td>
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
                order: [[9, 'desc']],
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
                    y: { formatter: function(val) { return val + ' members'; } }
                }
            };
            var monthlyChart = new ApexCharts(document.querySelector("#monthly-trend-chart"), monthlyOptions);
            monthlyChart.render();

            // Approval Status Chart
            var approvalData = <?php echo json_encode(array_column($approval_status, 'count')); ?>;
            var approvalLabels = <?php echo json_encode(array_map(function($s) { return ucfirst($s['status']); }, $approval_status)); ?>;
            
            var approvalOptions = {
                series: approvalData,
                chart: {
                    type: 'donut',
                    height: 300
                },
                labels: approvalLabels,
                colors: ['#0acf97', '#ffbc00', '#fa5c7c'],
                legend: {
                    position: 'bottom'
                }
            };
            var approvalChart = new ApexCharts(document.querySelector("#approval-status-chart"), approvalOptions);
            approvalChart.render();
        });
    </script>
</body>
</html>

