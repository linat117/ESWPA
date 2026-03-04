<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: auth-login.php");
    exit();
}

include 'include/conn.php';
include 'header.php';

// Fetch ID card statistics
// Total approved members (eligible for ID cards)
$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM registrations WHERE approval_status = 'approved'");
$stmt->execute();
$result = $stmt->get_result();
$total_eligible = $result->fetch_assoc()['total'] ?? 0;
$stmt->close();

// Members with ID cards generated
$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM registrations WHERE id_card_generated = 1 AND approval_status = 'approved'");
$stmt->execute();
$result = $stmt->get_result();
$cards_generated = $result->fetch_assoc()['total'] ?? 0;
$stmt->close();

// Members without ID cards
$pending_cards = $total_eligible - $cards_generated;

// Active memberships (not expired)
$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM registrations WHERE approval_status = 'approved' AND (expiry_date IS NULL OR expiry_date >= CURDATE())");
$stmt->execute();
$result = $stmt->get_result();
$active_memberships = $result->fetch_assoc()['total'] ?? 0;
$stmt->close();

// Expired memberships
$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM registrations WHERE approval_status = 'approved' AND expiry_date IS NOT NULL AND expiry_date < CURDATE()");
$stmt->execute();
$result = $stmt->get_result();
$expired_memberships = $result->fetch_assoc()['total'] ?? 0;
$stmt->close();

// Recent ID card generations (last 30 days)
$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM registrations WHERE id_card_generated = 1 AND id_card_generated_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
$stmt->execute();
$result = $stmt->get_result();
$recent_generations = $result->fetch_assoc()['total'] ?? 0;
$stmt->close();

// Total verifications
$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM id_card_verification");
$stmt->execute();
$result = $stmt->get_result();
$total_verifications = $result->fetch_assoc()['total'] ?? 0;
$stmt->close();

// Monthly ID card generation trend
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

// Status distribution
$status_distribution = [
    'Generated' => $cards_generated,
    'Pending' => $pending_cards,
    'Expired' => $expired_memberships
];

// Recent ID card generations
$recent_cards = [];
$stmt = $conn->prepare("SELECT r.id, r.fullname, r.membership_id, r.id_card_generated_at, r.expiry_date 
                        FROM registrations r 
                        WHERE r.id_card_generated = 1 AND r.approval_status = 'approved' 
                        ORDER BY r.id_card_generated_at DESC 
                        LIMIT 10");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $recent_cards[] = $row;
}
$stmt->close();

// Members pending ID card generation
$pending_list = [];
$stmt = $conn->prepare("SELECT r.id, r.fullname, r.membership_id, r.approved_at, r.expiry_date 
                        FROM registrations r 
                        WHERE r.approval_status = 'approved' AND (r.id_card_generated = 0 OR r.id_card_generated IS NULL)
                        AND (r.expiry_date IS NULL OR r.expiry_date >= CURDATE())
                        ORDER BY r.approved_at DESC 
                        LIMIT 10");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $pending_list[] = $row;
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
                                <h4 class="page-title">Digital ID Card Dashboard</h4>
                                <div>
                                    <a href="id_cards_list.php" class="btn btn-secondary me-2">
                                        <i class="ri-list-check"></i> All ID Cards
                                    </a>
                                    <a href="id_card_generate.php" class="btn btn-primary">
                                        <i class="ri-add-circle-line"></i> Generate ID Card
                                    </a>
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
                                        <i class="ri-user-line widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0">Eligible Members</h6>
                                    <h2 class="my-2"><?php echo number_format($total_eligible); ?></h2>
                                    <p class="mb-0">
                                        <span class="text-white-50">Approved members</span>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
                            <div class="card widget-flat text-bg-success">
                                <div class="card-body">
                                    <div class="float-end">
                                        <i class="ri-id-card-line widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0">ID Cards Generated</h6>
                                    <h2 class="my-2"><?php echo number_format($cards_generated); ?></h2>
                                    <p class="mb-0">
                                        <span class="text-white-50"><?php echo $total_eligible > 0 ? number_format(($cards_generated / $total_eligible) * 100, 1) : 0; ?>% coverage</span>
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
                                    <h6 class="text-uppercase mt-0">Pending Generation</h6>
                                    <h2 class="my-2"><?php echo number_format($pending_cards); ?></h2>
                                    <p class="mb-0">
                                        <a href="id_cards_list.php?status=pending" class="text-white-50">View Pending <i class="ri-arrow-right-line"></i></a>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
                            <div class="card widget-flat text-bg-info">
                                <div class="card-body">
                                    <div class="float-end">
                                        <i class="ri-checkbox-circle-line widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0">Active Memberships</h6>
                                    <h2 class="my-2"><?php echo number_format($active_memberships); ?></h2>
                                    <p class="mb-0">
                                        <span class="text-white-50">Not expired</span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
                            <div class="card widget-flat text-bg-danger">
                                <div class="card-body">
                                    <div class="float-end">
                                        <i class="ri-close-circle-line widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0">Expired Memberships</h6>
                                    <h2 class="my-2"><?php echo number_format($expired_memberships); ?></h2>
                                    <p class="mb-0">
                                        <a href="id_cards_list.php?status=expired" class="text-white-50">View Expired <i class="ri-arrow-right-line"></i></a>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
                            <div class="card widget-flat text-bg-info">
                                <div class="card-body">
                                    <div class="float-end">
                                        <i class="ri-calendar-line widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0">Recent (30 Days)</h6>
                                    <h2 class="my-2"><?php echo number_format($recent_generations); ?></h2>
                                    <p class="mb-0">
                                        <span class="text-white-50">New ID cards</span>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
                            <div class="card widget-flat text-bg-secondary">
                                <div class="card-body">
                                    <div class="float-end">
                                        <i class="ri-qr-scan-line widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0">Total Verifications</h6>
                                    <h2 class="my-2"><?php echo number_format($total_verifications); ?></h2>
                                    <p class="mb-0">
                                        <a href="id_card_verify.php" class="text-white-50">View Verifications <i class="ri-arrow-right-line"></i></a>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
                            <div class="card widget-flat text-bg-dark">
                                <div class="card-body">
                                    <div class="float-end">
                                        <i class="ri-file-list-line widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0">Coverage Rate</h6>
                                    <h2 class="my-2"><?php echo $total_eligible > 0 ? number_format(($cards_generated / $total_eligible) * 100, 1) : 0; ?>%</h2>
                                    <p class="mb-0">
                                        <span class="text-white-50">ID cards / Eligible</span>
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
                                    <h4 class="header-title">ID Card Generation Trend (12 Months)</h4>
                                </div>
                                <div class="card-body">
                                    <div id="generation-trend-chart" style="min-height: 350px;"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">ID Card Status Distribution</h4>
                                </div>
                                <div class="card-body">
                                    <div id="status-distribution-chart" style="min-height: 350px;"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Generations and Pending -->
                    <div class="row mb-3">
                        <div class="col-lg-6">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Recent ID Card Generations</h4>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-hover mb-0">
                                            <thead>
                                                <tr>
                                                    <th>Member</th>
                                                    <th>Membership ID</th>
                                                    <th>Generated</th>
                                                    <th>Status</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (empty($recent_cards)): ?>
                                                    <tr>
                                                        <td colspan="5" class="text-center text-muted">No recent generations</td>
                                                    </tr>
                                                <?php else: ?>
                                                    <?php foreach ($recent_cards as $card): ?>
                                                        <tr>
                                                            <td><strong><?php echo htmlspecialchars($card['fullname']); ?></strong></td>
                                                            <td><code><?php echo htmlspecialchars($card['membership_id']); ?></code></td>
                                                            <td><?php echo date('M d, Y', strtotime($card['id_card_generated_at'])); ?></td>
                                                            <td>
                                                                <?php
                                                                $expired = !empty($card['expiry_date']) && strtotime($card['expiry_date']) < time();
                                                                if ($expired) {
                                                                    echo '<span class="badge bg-danger">Expired</span>';
                                                                } else {
                                                                    echo '<span class="badge bg-success">Active</span>';
                                                                }
                                                                ?>
                                                            </td>
                                                            <td>
                                                                <a href="id_cards_list.php?member_id=<?php echo $card['id']; ?>" class="btn btn-sm btn-light">
                                                                    <i class="ri-eye-line"></i> View
                                                                </a>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Pending ID Card Generation</h4>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-hover mb-0">
                                            <thead>
                                                <tr>
                                                    <th>Member</th>
                                                    <th>Membership ID</th>
                                                    <th>Approved</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (empty($pending_list)): ?>
                                                    <tr>
                                                        <td colspan="4" class="text-center text-muted">No pending generations</td>
                                                    </tr>
                                                <?php else: ?>
                                                    <?php foreach ($pending_list as $member): ?>
                                                        <tr>
                                                            <td><strong><?php echo htmlspecialchars($member['fullname']); ?></strong></td>
                                                            <td><code><?php echo htmlspecialchars($member['membership_id']); ?></code></td>
                                                            <td><?php echo date('M d, Y', strtotime($member['approved_at'])); ?></td>
                                                            <td>
                                                                <a href="id_card_generate.php?member_id=<?php echo $member['id']; ?>" class="btn btn-sm btn-primary">
                                                                    <i class="ri-add-circle-line"></i> Generate
                                                                </a>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
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
    
    <!-- App js -->
    <script src="assets/js/app.min.js"></script>

    <script>
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

        // Status Distribution Chart
        var statusData = <?php echo json_encode(array_values($status_distribution)); ?>;
        var statusLabels = <?php echo json_encode(array_keys($status_distribution)); ?>;
        var statusOptions = {
            chart: {
                type: 'donut',
                height: 350
            },
            series: statusData,
            labels: statusLabels,
            colors: ['#10b981', '#f59e0b', '#ef4444'],
            legend: {
                position: 'bottom'
            }
        };
        new ApexCharts(document.querySelector("#status-distribution-chart"), statusOptions).render();
    </script>

</body>
</html>

