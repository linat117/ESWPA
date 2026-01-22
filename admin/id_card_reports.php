<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: auth-login.php");
    exit();
}

include 'include/conn.php';
include 'header.php';

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
$stats['total_generated'] = $conn->query("SELECT COUNT(*) as total FROM registrations WHERE id_card_generated = 1 AND approval_status = 'approved'")->fetch_assoc()['total'];
$stats['in_range'] = $conn->query("SELECT COUNT(*) as total FROM registrations WHERE id_card_generated = 1 AND id_card_generated_at BETWEEN '{$start_datetime}' AND '{$end_datetime}'")->fetch_assoc()['total'];
$stats['total_verifications'] = $conn->query("SELECT COUNT(*) as total FROM id_card_verification")->fetch_assoc()['total'];
$stats['scanned_verifications'] = $conn->query("SELECT COUNT(*) as total FROM id_card_verification WHERE scanned_at IS NOT NULL")->fetch_assoc()['total'];
$stats['recent_scans'] = $conn->query("SELECT COUNT(*) as total FROM id_card_verification WHERE scanned_at BETWEEN '{$start_datetime}' AND '{$end_datetime}'")->fetch_assoc()['total'];

// Monthly generation trend
$monthly_data = [];
$months = [];
for ($i = 11; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $months[] = date('M Y', strtotime("-$i months"));
    $monthly_data[$month] = 0;
}
$monthly_query = "SELECT DATE_FORMAT(id_card_generated_at, '%Y-%m') as month, COUNT(id) as count 
                  FROM registrations 
                  WHERE id_card_generated = 1 AND id_card_generated_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH) 
                  GROUP BY month ORDER BY month ASC";
$monthly_result = $conn->query($monthly_query);
while ($row = $monthly_result->fetch_assoc()) {
    if (isset($monthly_data[$row['month']])) {
        $monthly_data[$row['month']] = $row['count'];
    }
}

// Recent generations
$recent_generations = [];
$stmt = $conn->prepare("SELECT r.id, r.fullname, r.membership_id, r.id_card_generated_at, r.expiry_date 
                       FROM registrations r 
                       WHERE r.id_card_generated = 1 AND r.approval_status = 'approved' 
                       ORDER BY r.id_card_generated_at DESC 
                       LIMIT 50");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $recent_generations[] = $row;
}
$stmt->close();

// Verification activity
$verification_activity = [];
$stmt = $conn->prepare("SELECT iv.*, r.fullname, r.membership_id 
                       FROM id_card_verification iv
                       LEFT JOIN registrations r ON iv.membership_id = r.membership_id
                       WHERE iv.scanned_at IS NOT NULL
                       ORDER BY iv.scanned_at DESC
                       LIMIT 50");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $verification_activity[] = $row;
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
                                <h4 class="page-title">ID Card Reports</h4>
                                <div>
                                    <a href="digital_id_dashboard.php" class="btn btn-secondary me-2">
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
                                        <i class="ri-id-card-line widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0">Total Generated</h6>
                                    <h2 class="my-2"><?php echo number_format($stats['total_generated']); ?></h2>
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
                                        <i class="ri-qr-code-line widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0">Total Verifications</h6>
                                    <h2 class="my-2"><?php echo number_format($stats['total_verifications']); ?></h2>
                                    <p class="mb-0">
                                        <span class="text-white-50"><?php echo number_format($stats['scanned_verifications']); ?> scanned</span>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
                            <div class="card widget-flat text-bg-info">
                                <div class="card-body">
                                    <div class="float-end">
                                        <i class="ri-scan-line widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0">Recent Scans</h6>
                                    <h2 class="my-2"><?php echo number_format($stats['recent_scans']); ?></h2>
                                    <p class="mb-0">
                                        <span class="text-white-50">In date range</span>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
                            <div class="card widget-flat text-bg-warning">
                                <div class="card-body">
                                    <div class="float-end">
                                        <i class="ri-bar-chart-line widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0">Coverage Rate</h6>
                                    <h2 class="my-2">
                                        <?php 
                                        $total_eligible = $conn->query("SELECT COUNT(*) as total FROM registrations WHERE approval_status = 'approved'")->fetch_assoc()['total'];
                                        echo $total_eligible > 0 ? number_format(($stats['total_generated'] / $total_eligible) * 100, 1) : 0; 
                                        ?>%
                                    </h2>
                                    <p class="mb-0">
                                        <span class="text-white-50">ID cards / Eligible</span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Chart -->
                    <div class="row mb-3">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">ID Card Generation Trend (12 Months)</h4>
                                </div>
                                <div class="card-body">
                                    <div id="generation-trend-chart" style="min-height: 350px;"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Generations -->
                    <div class="row mb-3">
                        <div class="col-lg-6">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Recent ID Card Generations</h4>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-hover mb-0" id="generationsTable">
                                            <thead>
                                                <tr>
                                                    <th>Member</th>
                                                    <th>Membership ID</th>
                                                    <th>Generated</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($recent_generations as $gen): ?>
                                                    <tr>
                                                        <td><strong><?php echo htmlspecialchars($gen['fullname']); ?></strong></td>
                                                        <td><code><?php echo htmlspecialchars($gen['membership_id']); ?></code></td>
                                                        <td><?php echo date('M d, Y', strtotime($gen['id_card_generated_at'])); ?></td>
                                                        <td>
                                                            <?php
                                                            $expired = !empty($gen['expiry_date']) && strtotime($gen['expiry_date']) < time();
                                                            echo $expired ? '<span class="badge bg-danger">Expired</span>' : '<span class="badge bg-success">Active</span>';
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

                        <div class="col-lg-6">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Recent Verification Activity</h4>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-hover mb-0" id="verificationsTable">
                                            <thead>
                                                <tr>
                                                    <th>Member</th>
                                                    <th>Scanned At</th>
                                                    <th>IP Address</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($verification_activity as $activity): ?>
                                                    <tr>
                                                        <td>
                                                            <strong><?php echo htmlspecialchars($activity['fullname'] ?? 'N/A'); ?></strong>
                                                            <br><small class="text-muted"><?php echo htmlspecialchars($activity['membership_id']); ?></small>
                                                        </td>
                                                        <td><?php echo date('M d, Y H:i', strtotime($activity['scanned_at'])); ?></td>
                                                        <td><small><?php echo htmlspecialchars($activity['ip_address'] ?? 'N/A'); ?></small></td>
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
            $('#generationsTable, #verificationsTable').DataTable({
                "pageLength": 25,
                "order": [[2, "desc"]]
            });
        });

        // Generation Trend Chart
        var generationTrendOptions = {
            chart: {
                type: 'line',
                height: 350,
                toolbar: { show: false }
            },
            series: [{
                name: 'ID Cards Generated',
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
        new ApexCharts(document.querySelector("#generation-trend-chart"), generationTrendOptions).render();
    </script>

</body>
</html>

