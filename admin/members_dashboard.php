<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: auth-login.php");
    exit();
}

include 'include/conn.php';
include 'header.php';

// Fetch comprehensive member statistics
// Total members
$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM registrations");
$stmt->execute();
$result = $stmt->get_result();
$total_members = $result->fetch_assoc()['total'] ?? 0;
$stmt->close();

// Approved members
$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM registrations WHERE approval_status = 'approved'");
$stmt->execute();
$result = $stmt->get_result();
$approved_members = $result->fetch_assoc()['total'] ?? 0;
$stmt->close();

// Pending approval
$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM registrations WHERE approval_status = 'pending'");
$stmt->execute();
$result = $stmt->get_result();
$pending_approval = $result->fetch_assoc()['total'] ?? 0;
$stmt->close();

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

// Recent registrations (last 30 days)
$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM registrations WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
$stmt->execute();
$result = $stmt->get_result();
$recent_registrations = $result->fetch_assoc()['total'] ?? 0;
$stmt->close();

// Members with ID cards
$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM registrations WHERE id_card_generated = 1 AND approval_status = 'approved'");
$stmt->execute();
$result = $stmt->get_result();
$members_with_id_cards = $result->fetch_assoc()['total'] ?? 0;
$stmt->close();

// Members by qualification
$qualification_stats = [];
$stmt = $conn->prepare("SELECT qualification, COUNT(*) as count FROM registrations WHERE approval_status = 'approved' AND qualification IS NOT NULL GROUP BY qualification ORDER BY count DESC LIMIT 10");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $qualification_stats[] = $row;
}
$stmt->close();

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

// Recent members
$recent_members = [];
$stmt = $conn->prepare("SELECT id, fullname, membership_id, email, approval_status, created_at FROM registrations ORDER BY created_at DESC LIMIT 10");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $recent_members[] = $row;
}
$stmt->close();

// Pending approvals
$pending_list = [];
$stmt = $conn->prepare("SELECT id, fullname, membership_id, email, created_at FROM registrations WHERE approval_status = 'pending' ORDER BY created_at DESC LIMIT 10");
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
                            <div class="page-title-box">
                                <h4 class="page-title mb-2">Members Dashboard</h4>
                                <div class="d-inline-flex flex-row flex-nowrap gap-2">
                                    <a href="members_list.php" class="btn btn-secondary btn-sm">
                                        <i class="ri-list-check"></i> All Members
                                    </a>
                                    <a href="members_list.php?status=pending" class="btn btn-warning btn-sm">
                                        <i class="ri-time-line"></i> Pending Approvals
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
                                        <i class="ri-team-line widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0">Total Members</h6>
                                    <h2 class="my-2"><?php echo number_format($total_members); ?></h2>
                                    <p class="mb-0">
                                        <span class="text-white-50">All registrations</span>
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
                                    <h2 class="my-2"><?php echo number_format($approved_members); ?></h2>
                                    <p class="mb-0">
                                        <a href="members_list.php?status=approved" class="text-white-50">View Approved <i class="ri-arrow-right-line"></i></a>
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
                                    <h6 class="text-uppercase mt-0">Pending Approval</h6>
                                    <h2 class="my-2"><?php echo number_format($pending_approval); ?></h2>
                                    <p class="mb-0">
                                        <a href="members_list.php?status=pending" class="text-white-50">Review Now <i class="ri-arrow-right-line"></i></a>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
                            <div class="card widget-flat text-bg-info">
                                <div class="card-body">
                                    <div class="float-end">
                                        <i class="ri-user-settings-line widget-icon"></i>
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
                                    <h6 class="text-uppercase mt-0">Expired</h6>
                                    <h2 class="my-2"><?php echo number_format($expired_memberships); ?></h2>
                                    <p class="mb-0">
                                        <a href="members_list.php?status=expired" class="text-white-50">View Expired <i class="ri-arrow-right-line"></i></a>
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
                                    <h2 class="my-2"><?php echo number_format($recent_registrations); ?></h2>
                                    <p class="mb-0">
                                        <span class="text-white-50">New registrations</span>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
                            <div class="card widget-flat text-bg-secondary">
                                <div class="card-body">
                                    <div class="float-end">
                                        <i class="ri-id-card-line widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0">With ID Cards</h6>
                                    <h2 class="my-2"><?php echo number_format($members_with_id_cards); ?></h2>
                                    <p class="mb-0">
                                        <a href="id_cards_list.php" class="text-white-50">View ID Cards <i class="ri-arrow-right-line"></i></a>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
                            <div class="card widget-flat text-bg-dark">
                                <div class="card-body">
                                    <div class="float-end">
                                        <i class="ri-percent-line widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0">Approval Rate</h6>
                                    <h2 class="my-2">
                                        <?php echo $total_members > 0 ? number_format(($approved_members / $total_members) * 100, 1) : 0; ?>%
                                    </h2>
                                    <p class="mb-0">
                                        <span class="text-white-50">Approved / Total</span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Charts Row -->
                    <div class="row mb-3">
                        <div class="col-lg-8">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Member Registration Trend (12 Months)</h4>
                                </div>
                                <div class="card-body">
                                    <div id="registration-trend-chart" style="min-height: 350px;"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Top Qualifications</h4>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-sm mb-0">
                                            <thead>
                                                <tr>
                                                    <th>Qualification</th>
                                                    <th>Count</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($qualification_stats as $qual): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($qual['qualification']); ?></td>
                                                        <td><span class="badge bg-primary"><?php echo number_format($qual['count']); ?></span></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Members and Pending -->
                    <div class="row mb-3">
                        <div class="col-lg-6">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Recent Members</h4>
                                </div>
                                <div class="card-body">
                                    <!-- Desktop table -->
                                    <div class="table-responsive d-none d-md-block">
                                        <table class="table table-hover mb-0">
                                            <thead>
                                                <tr>
                                                    <th>Member</th>
                                                    <th>Membership ID</th>
                                                    <th>Status</th>
                                                    <th>Registered</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($recent_members as $member): ?>
                                                    <tr>
                                                        <td><strong><?php echo htmlspecialchars($member['fullname']); ?></strong></td>
                                                        <td><code><?php echo htmlspecialchars($member['membership_id']); ?></code></td>
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

                                    <!-- Mobile cards -->
                                    <div class="d-block d-md-none">
                                        <?php $mobileRecentIndex = 1; ?>
                                        <?php foreach ($recent_members as $member): ?>
                                            <div class="card mb-2 mobile-member-card">
                                                <div class="card-body d-flex justify-content-between align-items-center">
                                                    <div class="d-flex align-items-center gap-2">
                                                        <span class="badge bg-primary rounded-pill"><?php echo $mobileRecentIndex++; ?></span>
                                                        <div class="d-flex flex-column">
                                                            <span class="fw-semibold text-truncate" style="max-width: 160px;">
                                                                <?php echo htmlspecialchars($member['fullname']); ?>
                                                            </span>
                                                            <small class="text-muted">
                                                                ID: <?php echo htmlspecialchars($member['membership_id']); ?>
                                                            </small>
                                                        </div>
                                                    </div>
                                                    <button type="button"
                                                        class="btn btn-link text-muted p-0 member-more"
                                                        data-type="recent"
                                                        aria-label="View member detail">
                                                        <i class="ri-more-2-fill" style="font-size: 1.4rem;"></i>
                                                    </button>
                                                </div>

                                                <!-- Hidden detail for modal -->
                                                <div class="d-none member-detail-content">
                                                    <h5 class="mb-1"><?php echo htmlspecialchars($member['fullname']); ?></h5>
                                                    <p class="mb-1">
                                                        <strong>Membership ID:</strong>
                                                        <code><?php echo htmlspecialchars($member['membership_id']); ?></code>
                                                    </p>
                                                    <p class="mb-1">
                                                        <strong>Status:</strong>
                                                        <?php echo ucfirst($member['approval_status']); ?>
                                                    </p>
                                                    <p class="mb-3">
                                                        <strong>Registered:</strong>
                                                        <?php echo date('M d, Y', strtotime($member['created_at'])); ?>
                                                    </p>
                                                    <a href="member_profile.php?id=<?php echo $member['id']; ?>" class="btn btn-primary btn-sm">
                                                        <i class="ri-eye-line"></i> View Profile
                                                    </a>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Pending Approvals</h4>
                                </div>
                                <div class="card-body">
                                    <!-- Desktop table -->
                                    <div class="table-responsive d-none d-md-block">
                                        <table class="table table-hover mb-0">
                                            <thead>
                                                <tr>
                                                    <th>Member</th>
                                                    <th>Membership ID</th>
                                                    <th>Registered</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (empty($pending_list)): ?>
                                                    <tr>
                                                        <td colspan="4" class="text-center text-muted">No pending approvals</td>
                                                    </tr>
                                                <?php else: ?>
                                                    <?php foreach ($pending_list as $member): ?>
                                                        <tr>
                                                            <td><strong><?php echo htmlspecialchars($member['fullname']); ?></strong></td>
                                                            <td><code><?php echo htmlspecialchars($member['membership_id']); ?></code></td>
                                                            <td><?php echo date('M d, Y', strtotime($member['created_at'])); ?></td>
                                                            <td>
                                                                <a href="member_profile.php?id=<?php echo $member['id']; ?>" class="btn btn-sm btn-primary">
                                                                    <i class="ri-check-line"></i> Review
                                                                </a>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>

                                    <!-- Mobile cards -->
                                    <div class="d-block d-md-none">
                                        <?php if (empty($pending_list)): ?>
                                            <p class="text-center text-muted mb-0">No pending approvals</p>
                                        <?php else: ?>
                                            <?php $mobilePendingIndex = 1; ?>
                                            <?php foreach ($pending_list as $member): ?>
                                                <div class="card mb-2 mobile-member-card">
                                                    <div class="card-body d-flex justify-content-between align-items-center">
                                                        <div class="d-flex align-items-center gap-2">
                                                            <span class="badge bg-warning rounded-pill text-dark"><?php echo $mobilePendingIndex++; ?></span>
                                                            <div class="d-flex flex-column">
                                                                <span class="fw-semibold text-truncate" style="max-width: 160px;">
                                                                    <?php echo htmlspecialchars($member['fullname']); ?>
                                                                </span>
                                                                <small class="text-muted">
                                                                    ID: <?php echo htmlspecialchars($member['membership_id']); ?>
                                                                </small>
                                                            </div>
                                                        </div>
                                                        <button type="button"
                                                            class="btn btn-link text-muted p-0 member-more"
                                                            data-type="pending"
                                                            aria-label="View member detail">
                                                            <i class="ri-more-2-fill" style="font-size: 1.4rem;"></i>
                                                        </button>
                                                    </div>

                                                    <!-- Hidden detail for modal -->
                                                    <div class="d-none member-detail-content">
                                                        <h5 class="mb-1"><?php echo htmlspecialchars($member['fullname']); ?></h5>
                                                        <p class="mb-1">
                                                            <strong>Membership ID:</strong>
                                                            <code><?php echo htmlspecialchars($member['membership_id']); ?></code>
                                                        </p>
                                                        <p class="mb-3">
                                                            <strong>Registered:</strong>
                                                            <?php echo date('M d, Y', strtotime($member['created_at'])); ?>
                                                        </p>
                                                        <a href="member_profile.php?id=<?php echo $member['id']; ?>" class="btn btn-primary btn-sm">
                                                            <i class="ri-check-line"></i> Review Member
                                                        </a>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
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

    <!-- Member Detail Modal (mobile) -->
    <div class="modal fade" id="memberDetailModal" tabindex="-1" aria-labelledby="memberDetailModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="memberDetailModalLabel">Member Detail</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Filled dynamically -->
                </div>
            </div>
        </div>
    </div>

    <!-- Vendor js -->
    <script src="assets/js/vendor.min.js"></script>
    
    <!-- ApexCharts js -->
    <script src="assets/vendor/apexcharts/apexcharts.min.js"></script>
    
    <!-- App js -->
    <script src="assets/js/app.min.js"></script>

    <script>
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

        // Mobile member detail modal
        $(document).on('click', '.member-more', function () {
            var card = $(this).closest('.mobile-member-card');
            var contentHtml = card.find('.member-detail-content').html();

            $('#memberDetailModal .modal-body').html(contentHtml);

            if (typeof bootstrap !== "undefined" && bootstrap.Modal) {
                var detailModal = new bootstrap.Modal(document.getElementById('memberDetailModal'));
                detailModal.show();
            } else {
                $('#memberDetailModal').modal('show');
            }
        });
    </script>

</body>
</html>

