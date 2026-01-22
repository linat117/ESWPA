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
    header('Content-Disposition: attachment; filename=member_reports_' . date('Y-m-d') . '.csv');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'Membership ID', 'Name', 'Email', 'Phone', 'Qualification', 'Approval Status', 'Expiry Date', 'ID Card Generated', 'Registered']);
    
    $query = "SELECT id, membership_id, fullname, email, phone, qualification, approval_status, expiry_date, id_card_generated, created_at 
              FROM registrations 
              ORDER BY created_at DESC";
    $result = $conn->query($query);
    
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, [
            $row['id'],
            $row['membership_id'],
            $row['fullname'],
            $row['email'],
            $row['phone'],
            $row['qualification'],
            $row['approval_status'],
            $row['expiry_date'] ?? '',
            $row['id_card_generated'] ? 'Yes' : 'No',
            $row['created_at']
        ]);
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

// Statistics
$stats = [];
$stats['total'] = $conn->query("SELECT COUNT(*) as total FROM registrations")->fetch_assoc()['total'];
$stats['approved'] = $conn->query("SELECT COUNT(*) as total FROM registrations WHERE approval_status = 'approved'")->fetch_assoc()['total'];
$stats['pending'] = $conn->query("SELECT COUNT(*) as total FROM registrations WHERE approval_status = 'pending'")->fetch_assoc()['total'];
$stats['expired'] = $conn->query("SELECT COUNT(*) as total FROM registrations WHERE expiry_date IS NOT NULL AND expiry_date < CURDATE() AND approval_status = 'approved'")->fetch_assoc()['total'];
$stats['in_range'] = $conn->query("SELECT COUNT(*) as total FROM registrations WHERE created_at BETWEEN '{$start_datetime}' AND '{$end_datetime}'")->fetch_assoc()['total'];
$stats['with_id_cards'] = $conn->query("SELECT COUNT(*) as total FROM registrations WHERE id_card_generated = 1 AND approval_status = 'approved'")->fetch_assoc()['total'];

// Monthly registration trend
$monthly_data = [];
$months = [];
for ($i = 11; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $months[] = date('M Y', strtotime("-$i months"));
    $monthly_data[$month] = 0;
}
$monthly_query = "SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(id) as count 
                  FROM registrations 
                  WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH) 
                  GROUP BY month ORDER BY month ASC";
$monthly_result = $conn->query($monthly_query);
while ($row = $monthly_result->fetch_assoc()) {
    if (isset($monthly_data[$row['month']])) {
        $monthly_data[$row['month']] = $row['count'];
    }
}

// Approval status distribution
$approval_distribution = [];
$stmt = $conn->prepare("SELECT approval_status, COUNT(*) as count FROM registrations GROUP BY approval_status");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $approval_distribution[$row['approval_status']] = $row['count'];
}
$stmt->close();

// Qualification distribution
$qualification_distribution = [];
$stmt = $conn->prepare("SELECT qualification, COUNT(*) as count FROM registrations WHERE approval_status = 'approved' AND qualification IS NOT NULL GROUP BY qualification ORDER BY count DESC LIMIT 10");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $qualification_distribution[] = $row;
}
$stmt->close();

// Recent registrations
$recent_registrations = [];
$stmt = $conn->prepare("SELECT id, fullname, membership_id, email, approval_status, created_at FROM registrations ORDER BY created_at DESC LIMIT 50");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $recent_registrations[] = $row;
}
$stmt->close();
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
                                <h4 class="page-title">Member Reports</h4>
                                <div>
                                    <a href="members_dashboard.php" class="btn btn-secondary me-2">
                                        <i class="ri-dashboard-line"></i> Dashboard
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
                                    <form method="GET" class="row g-3">
                                        <div class="col-md-3">
                                            <label class="form-label">Quick Range</label>
                                            <select name="range" class="form-select" onchange="this.form.submit()">
                                                <option value="7" <?php echo $date_range == '7' ? 'selected' : ''; ?>>Last 7 days</option>
                                                <option value="30" <?php echo $date_range == '30' ? 'selected' : ''; ?>>Last 30 days</option>
                                                <option value="90" <?php echo $date_range == '90' ? 'selected' : ''; ?>>Last 90 days</option>
                                                <option value="365" <?php echo $date_range == '365' ? 'selected' : ''; ?>>Last year</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Start Date</label>
                                            <input type="date" name="start_date" class="form-control" value="<?php echo htmlspecialchars($start_date); ?>" required>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">End Date</label>
                                            <input type="date" name="end_date" class="form-control" value="<?php echo htmlspecialchars($end_date); ?>" required>
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

                    <!-- Summary Cards -->
                    <div class="row g-3 mb-3">
                        <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
                            <div class="card widget-flat text-bg-primary">
                                <div class="card-body">
                                    <div class="float-end">
                                        <i class="ri-team-line widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0">Total Members</h6>
                                    <h2 class="my-2"><?php echo number_format($stats['total']); ?></h2>
                                    <p class="mb-0">
                                        <span class="text-white-50"><?php echo number_format($stats['in_range']); ?> in range</span>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
                            <div class="card widget-flat text-bg-success">
                                <div class="card-body">
                                    <div class="float-end">
                                        <i class="ri-checkbox-circle-line widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0">Approved</h6>
                                    <h2 class="my-2"><?php echo number_format($stats['approved']); ?></h2>
                                    <p class="mb-0">
                                        <span class="text-white-50"><?php echo $stats['total'] > 0 ? number_format(($stats['approved'] / $stats['total']) * 100, 1) : 0; ?>% of total</span>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
                            <div class="card widget-flat text-bg-warning">
                                <div class="card-body">
                                    <div class="float-end">
                                        <i class="ri-time-line widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0">Pending</h6>
                                    <h2 class="my-2"><?php echo number_format($stats['pending']); ?></h2>
                                    <p class="mb-0">
                                        <span class="text-white-50">Awaiting approval</span>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
                            <div class="card widget-flat text-bg-danger">
                                <div class="card-body">
                                    <div class="float-end">
                                        <i class="ri-close-circle-line widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0">Expired</h6>
                                    <h2 class="my-2"><?php echo number_format($stats['expired']); ?></h2>
                                    <p class="mb-0">
                                        <span class="text-white-50">Memberships expired</span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Charts Row -->
                    <div class="row mb-3">
                        <div class="col-lg-6">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Registration Trend (12 Months)</h4>
                                </div>
                                <div class="card-body">
                                    <div id="registration-trend-chart" style="min-height: 350px;"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Approval Status Distribution</h4>
                                </div>
                                <div class="card-body">
                                    <div id="approval-distribution-chart" style="min-height: 350px;"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Qualification Distribution -->
                    <div class="row mb-3">
                        <div class="col-lg-6">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Top Qualifications</h4>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-hover mb-0">
                                            <thead>
                                                <tr>
                                                    <th>Qualification</th>
                                                    <th>Count</th>
                                                    <th>Percentage</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php 
                                                $total_approved = $stats['approved'];
                                                foreach ($qualification_distribution as $qual): 
                                                    $percentage = $total_approved > 0 ? ($qual['count'] / $total_approved) * 100 : 0;
                                                ?>
                                                    <tr>
                                                        <td><strong><?php echo htmlspecialchars($qual['qualification']); ?></strong></td>
                                                        <td><span class="badge bg-primary"><?php echo number_format($qual['count']); ?></span></td>
                                                        <td><?php echo number_format($percentage, 1); ?>%</td>
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
                                    <h4 class="header-title">Quick Statistics</h4>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <h6 class="text-muted small">Members with ID Cards</h6>
                                        <h4><?php echo number_format($stats['with_id_cards']); ?></h4>
                                        <small class="text-muted">
                                            <?php echo $stats['approved'] > 0 ? number_format(($stats['with_id_cards'] / $stats['approved']) * 100, 1) : 0; ?>% of approved members
                                        </small>
                                    </div>
                                    <div class="mb-3">
                                        <h6 class="text-muted small">Approval Rate</h6>
                                        <h4><?php echo $stats['total'] > 0 ? number_format(($stats['approved'] / $stats['total']) * 100, 1) : 0; ?>%</h4>
                                        <small class="text-muted">Approved / Total registrations</small>
                                    </div>
                                    <div class="mb-0">
                                        <h6 class="text-muted small">Active Memberships</h6>
                                        <h4><?php echo number_format($stats['approved'] - $stats['expired']); ?></h4>
                                        <small class="text-muted">Approved and not expired</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Registrations -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Recent Registrations</h4>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-hover mb-0" id="recentRegistrationsTable">
                                            <thead>
                                                <tr>
                                                    <th>Member</th>
                                                    <th>Membership ID</th>
                                                    <th>Email</th>
                                                    <th>Status</th>
                                                    <th>Registered</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($recent_registrations as $member): ?>
                                                    <tr>
                                                        <td><strong><?php echo htmlspecialchars($member['fullname']); ?></strong></td>
                                                        <td><code><?php echo htmlspecialchars($member['membership_id']); ?></code></td>
                                                        <td><?php echo htmlspecialchars($member['email']); ?></td>
                                                        <td>
                                                            <span class="badge bg-<?php 
                                                                echo $member['approval_status'] == 'approved' ? 'success' : 
                                                                    ($member['approval_status'] == 'pending' ? 'warning' : 'danger'); 
                                                            ?>">
                                                                <?php echo ucfirst($member['approval_status']); ?>
                                                            </span>
                                                        </td>
                                                        <td><?php echo date('M d, Y', strtotime($member['created_at'])); ?></td>
                                                        <td>
                                                            <a href="member_profile.php?id=<?php echo $member['id']; ?>" class="btn btn-sm btn-light">
                                                                <i class="ri-eye-line"></i> View
                                                            </a>
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
    <link href="assets/vendor/datatables.net-bs5/css/dataTables.bootstrap5.min.css" rel="stylesheet" type="text/css" />
    <script src="assets/vendor/datatables.net/js/jquery.dataTables.min.js"></script>
    <script src="assets/vendor/datatables.net-bs5/js/dataTables.bootstrap5.min.js"></script>
    
    <!-- App js -->
    <script src="assets/js/app.min.js"></script>

    <script>
        $(document).ready(function() {
            $('#recentRegistrationsTable').DataTable({
                "order": [[4, "desc"]],
                "pageLength": 25
            });
        });

        // Registration Trend Chart
        var registrationTrendOptions = {
            chart: {
                type: 'line',
                height: 350,
                toolbar: { show: false }
            },
            series: [{
                name: 'New Members',
                data: <?php echo json_encode(array_values($monthly_data)); ?>
            }],
            xaxis: {
                categories: <?php echo json_encode($months); ?>
            },
            colors: ['#3e60d5'],
            stroke: {
                width: 3
            },
            markers: {
                size: 5
            }
        };
        new ApexCharts(document.querySelector("#registration-trend-chart"), registrationTrendOptions).render();

        // Approval Distribution Chart
        var approvalData = <?php echo json_encode(array_values($approval_distribution)); ?>;
        var approvalLabels = <?php echo json_encode(array_keys($approval_distribution)); ?>;
        var approvalOptions = {
            chart: {
                type: 'donut',
                height: 350
            },
            series: approvalData,
            labels: approvalLabels,
            colors: ['#10b981', '#f59e0b', '#ef4444'],
            legend: {
                position: 'bottom'
            }
        };
        new ApexCharts(document.querySelector("#approval-distribution-chart"), approvalOptions).render();
    </script>

</body>
</html>

