<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: auth-login.php");
    exit();
}

include 'include/conn.php';
include 'header.php';

// Check if support_tickets table exists
$table_exists = false;
$result = $conn->query("SHOW TABLES LIKE 'support_tickets'");
if ($result && $result->num_rows > 0) {
    $table_exists = true;
}

// Fetch support ticket statistics (only if table exists)
$total_tickets = 0;
$open_tickets = 0;
$pending_tickets = 0;
$resolved_tickets = 0;
$closed_tickets = 0;
$high_priority = 0;
$recent_tickets = 0;
$avg_resolution_time = 0;

$recent_tickets_list = [];
$priority_distribution = [];
$status_distribution = [];
$category_stats = [];

if ($table_exists) {
    // Total tickets
    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM support_tickets");
    $stmt->execute();
    $result = $stmt->get_result();
    $total_tickets = $result->fetch_assoc()['total'] ?? 0;
    $stmt->close();

    // Open tickets (status = 'open')
    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM support_tickets WHERE status = 'open'");
    $stmt->execute();
    $result = $stmt->get_result();
    $open_tickets = $result->fetch_assoc()['total'] ?? 0;
    $stmt->close();

    // Pending tickets (status = 'pending')
    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM support_tickets WHERE status = 'pending'");
    $stmt->execute();
    $result = $stmt->get_result();
    $pending_tickets = $result->fetch_assoc()['total'] ?? 0;
    $stmt->close();

    // Resolved tickets (status = 'resolved')
    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM support_tickets WHERE status = 'resolved'");
    $stmt->execute();
    $result = $stmt->get_result();
    $resolved_tickets = $result->fetch_assoc()['total'] ?? 0;
    $stmt->close();

    // Closed tickets (status = 'closed')
    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM support_tickets WHERE status = 'closed'");
    $stmt->execute();
    $result = $stmt->get_result();
    $closed_tickets = $result->fetch_assoc()['total'] ?? 0;
    $stmt->close();

    // High priority tickets
    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM support_tickets WHERE priority = 'high' AND status IN ('open', 'pending')");
    $stmt->execute();
    $result = $stmt->get_result();
    $high_priority = $result->fetch_assoc()['total'] ?? 0;
    $stmt->close();

    // Recent tickets (last 7 days)
    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM support_tickets WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
    $stmt->execute();
    $result = $stmt->get_result();
    $recent_tickets = $result->fetch_assoc()['total'] ?? 0;
    $stmt->close();

    // Recent tickets list
    $stmt = $conn->prepare("SELECT st.*, r.fullname, r.email 
                           FROM support_tickets st 
                           LEFT JOIN registrations r ON st.member_id = r.id 
                           ORDER BY st.created_at DESC 
                           LIMIT 10");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $recent_tickets_list[] = $row;
    }
    $stmt->close();

    // Priority distribution
    $stmt = $conn->prepare("SELECT priority, COUNT(*) as count FROM support_tickets GROUP BY priority");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $priority_distribution[$row['priority']] = $row['count'];
    }
    $stmt->close();

    // Status distribution
    $stmt = $conn->prepare("SELECT status, COUNT(*) as count FROM support_tickets GROUP BY status");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $status_distribution[$row['status']] = $row['count'];
    }
    $stmt->close();

    // Category statistics
    $stmt = $conn->prepare("SELECT category, COUNT(*) as count FROM support_tickets GROUP BY category ORDER BY count DESC LIMIT 10");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $category_stats[] = $row;
    }
    $stmt->close();

    // Monthly ticket trend
    $monthly_data = [];
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
                                <h4 class="page-title">Support Dashboard</h4>
                                <div>
                                    <a href="support_tickets_list.php" class="btn btn-secondary me-2">
                                        <i class="ri-list-check"></i> All Tickets
                                    </a>
                                    <?php if ($table_exists): ?>
                                        <a href="support_tickets_list.php?status=open" class="btn btn-warning">
                                            <i class="ri-time-line"></i> Open Tickets
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if (!$table_exists): ?>
                        <!-- Table Not Found Alert -->
                        <div class="alert alert-warning" role="alert">
                            <h5 class="alert-heading"><i class="ri-alert-line"></i> Support Tickets Table Not Found</h5>
                            <p>The <code>support_tickets</code> table does not exist in the database. Please create it to use the support system.</p>
                            <hr>
                            <p class="mb-0"><strong>Required Table Structure:</strong></p>
                            <pre class="mt-2 mb-0"><code>CREATE TABLE `support_tickets` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `ticket_number` VARCHAR(50) NOT NULL UNIQUE,
  `member_id` INT(11) NULL,
  `subject` VARCHAR(255) NOT NULL,
  `message` TEXT NOT NULL,
  `category` VARCHAR(100) NULL,
  `priority` ENUM('low','medium','high','urgent') NOT NULL DEFAULT 'medium',
  `status` ENUM('open','pending','in_progress','resolved','closed') NOT NULL DEFAULT 'open',
  `assigned_to` INT(11) NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
  `resolved_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_status` (`status`),
  INDEX `idx_priority` (`priority`),
  INDEX `idx_member_id` (`member_id`),
  INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;</code></pre>
                        </div>
                    <?php else: ?>

                    <!-- Summary Cards -->
                    <div class="row g-3 mb-3">
                        <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
                            <div class="card widget-flat text-bg-primary">
                                <div class="card-body">
                                    <div class="float-end">
                                        <i class="ri-customer-service-2-line widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0">Total Tickets</h6>
                                    <h2 class="my-2"><?php echo number_format($total_tickets); ?></h2>
                                    <p class="mb-0">
                                        <span class="text-white-50">All tickets</span>
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
                                    <h6 class="text-uppercase mt-0">Open Tickets</h6>
                                    <h2 class="my-2"><?php echo number_format($open_tickets); ?></h2>
                                    <p class="mb-0">
                                        <a href="support_tickets_list.php?status=open" class="text-white-50">View Open <i class="ri-arrow-right-line"></i></a>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
                            <div class="card widget-flat text-bg-info">
                                <div class="card-body">
                                    <div class="float-end">
                                        <i class="ri-hourglass-line widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0">Pending</h6>
                                    <h2 class="my-2"><?php echo number_format($pending_tickets); ?></h2>
                                    <p class="mb-0">
                                        <a href="support_tickets_list.php?status=pending" class="text-white-50">View Pending <i class="ri-arrow-right-line"></i></a>
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
                                    <h6 class="text-uppercase mt-0">Resolved</h6>
                                    <h2 class="my-2"><?php echo number_format($resolved_tickets); ?></h2>
                                    <p class="mb-0">
                                        <a href="support_tickets_list.php?status=resolved" class="text-white-50">View Resolved <i class="ri-arrow-right-line"></i></a>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
                            <div class="card widget-flat text-bg-secondary">
                                <div class="card-body">
                                    <div class="float-end">
                                        <i class="ri-close-circle-line widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0">Closed</h6>
                                    <h2 class="my-2"><?php echo number_format($closed_tickets); ?></h2>
                                    <p class="mb-0">
                                        <span class="text-white-50">Total closed</span>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
                            <div class="card widget-flat text-bg-danger">
                                <div class="card-body">
                                    <div class="float-end">
                                        <i class="ri-alert-line widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0">High Priority</h6>
                                    <h2 class="my-2"><?php echo number_format($high_priority); ?></h2>
                                    <p class="mb-0">
                                        <span class="text-white-50">Requires attention</span>
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
                                    <h6 class="text-uppercase mt-0">Recent (7 Days)</h6>
                                    <h2 class="my-2"><?php echo number_format($recent_tickets); ?></h2>
                                    <p class="mb-0">
                                        <span class="text-white-50">New tickets</span>
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
                                    <h6 class="text-uppercase mt-0">Resolution Rate</h6>
                                    <h2 class="my-2">
                                        <?php echo $total_tickets > 0 ? number_format((($resolved_tickets + $closed_tickets) / $total_tickets) * 100, 1) : 0; ?>%
                                    </h2>
                                    <p class="mb-0">
                                        <span class="text-white-50">Resolved / Total</span>
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
                                    <h4 class="header-title">Top Categories</h4>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-sm mb-0">
                                            <thead>
                                                <tr>
                                                    <th>Category</th>
                                                    <th>Count</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (empty($category_stats)): ?>
                                                    <tr>
                                                        <td colspan="2" class="text-center text-muted">No data available</td>
                                                    </tr>
                                                <?php else: ?>
                                                    <?php foreach ($category_stats as $cat): ?>
                                                        <tr>
                                                            <td><?php echo htmlspecialchars($cat['category'] ?? 'Uncategorized'); ?></td>
                                                            <td><span class="badge bg-primary"><?php echo number_format($cat['count']); ?></span></td>
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

                    <!-- Recent Tickets -->
                    <div class="row mb-3">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Recent Tickets</h4>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-hover mb-0">
                                            <thead>
                                                <tr>
                                                    <th>Ticket #</th>
                                                    <th>Subject</th>
                                                    <th>Member</th>
                                                    <th>Priority</th>
                                                    <th>Status</th>
                                                    <th>Created</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (empty($recent_tickets_list)): ?>
                                                    <tr>
                                                        <td colspan="7" class="text-center text-muted">No tickets found</td>
                                                    </tr>
                                                <?php else: ?>
                                                    <?php foreach ($recent_tickets_list as $ticket): ?>
                                                        <tr>
                                                            <td><code><?php echo htmlspecialchars($ticket['ticket_number']); ?></code></td>
                                                            <td><strong><?php echo htmlspecialchars($ticket['subject']); ?></strong></td>
                                                            <td><?php echo htmlspecialchars($ticket['fullname'] ?? 'Guest'); ?></td>
                                                            <td>
                                                                <span class="badge bg-<?php 
                                                                    echo $ticket['priority'] == 'urgent' ? 'danger' : 
                                                                        ($ticket['priority'] == 'high' ? 'warning' : 
                                                                        ($ticket['priority'] == 'medium' ? 'info' : 'secondary')); 
                                                                ?>">
                                                                    <?php echo ucfirst($ticket['priority']); ?>
                                                                </span>
                                                            </td>
                                                            <td>
                                                                <span class="badge bg-<?php 
                                                                    echo $ticket['status'] == 'open' ? 'warning' : 
                                                                        ($ticket['status'] == 'resolved' || $ticket['status'] == 'closed' ? 'success' : 
                                                                        ($ticket['status'] == 'pending' ? 'info' : 'primary')); 
                                                                ?>">
                                                                    <?php echo ucfirst(str_replace('_', ' ', $ticket['status'])); ?>
                                                                </span>
                                                            </td>
                                                            <td><?php echo date('M d, Y H:i', strtotime($ticket['created_at'])); ?></td>
                                                            <td>
                                                                <a href="support_ticket_detail.php?id=<?php echo $ticket['id']; ?>" class="btn btn-sm btn-light">
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
                    </div>

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

    <?php if ($table_exists && !empty($monthly_data)): ?>
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
            stroke: {
                width: 3
            },
            markers: {
                size: 5
            }
        };
        new ApexCharts(document.querySelector("#ticket-trend-chart"), ticketTrendOptions).render();
    </script>
    <?php endif; ?>

</body>
</html>

