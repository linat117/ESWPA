<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: auth-login.php");
    exit();
}

include 'include/conn.php';
include 'header.php';

// Check if notifications table exists
$table_exists = false;
$result = $conn->query("SHOW TABLES LIKE 'notifications'");
if ($result && $result->num_rows > 0) {
    $table_exists = true;
}

// Fetch notification statistics (only if table exists)
$total_notifications = 0;
$unread_notifications = 0;
$read_notifications = 0;
$recent_notifications = 0;
$notifications_today = 0;

$recent_notifications_list = [];
$type_distribution = [];
$monthly_data = [];

if ($table_exists) {
    // Total notifications
    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM notifications");
    $stmt->execute();
    $result = $stmt->get_result();
    $total_notifications = $result->fetch_assoc()['total'] ?? 0;
    $stmt->close();

    // Unread notifications
    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM notifications WHERE is_read = 0");
    $stmt->execute();
    $result = $stmt->get_result();
    $unread_notifications = $result->fetch_assoc()['total'] ?? 0;
    $stmt->close();

    // Read notifications
    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM notifications WHERE is_read = 1");
    $stmt->execute();
    $result = $stmt->get_result();
    $read_notifications = $result->fetch_assoc()['total'] ?? 0;
    $stmt->close();

    // Recent notifications (last 7 days)
    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM notifications WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
    $stmt->execute();
    $result = $stmt->get_result();
    $recent_notifications = $result->fetch_assoc()['total'] ?? 0;
    $stmt->close();

    // Notifications today
    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM notifications WHERE DATE(created_at) = CURDATE()");
    $stmt->execute();
    $result = $stmt->get_result();
    $notifications_today = $result->fetch_assoc()['total'] ?? 0;
    $stmt->close();

    // Recent notifications list
    $stmt = $conn->prepare("SELECT n.*, r.fullname, r.email 
                           FROM notifications n 
                           LEFT JOIN registrations r ON n.member_id = r.id 
                           ORDER BY n.created_at DESC 
                           LIMIT 20");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $recent_notifications_list[] = $row;
    }
    $stmt->close();

    // Type distribution
    $stmt = $conn->prepare("SELECT type, COUNT(*) as count FROM notifications GROUP BY type ORDER BY count DESC LIMIT 10");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $type_distribution[] = $row;
    }
    $stmt->close();

    // Monthly notification trend
    $months = [];
    for ($i = 11; $i >= 0; $i--) {
        $month = date('Y-m', strtotime("-$i months"));
        $months[] = date('M Y', strtotime("-$i months"));
        $monthly_data[$month] = 0;
    }
    $monthly_query = "SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(id) as count 
                      FROM notifications 
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
                                <h4 class="page-title">Notifications Center</h4>
                                <div>
                                    <a href="notifications_list.php" class="btn btn-secondary me-2">
                                        <i class="ri-notification-line"></i> All Notifications
                                    </a>
                                    <a href="notification_settings.php" class="btn btn-info">
                                        <i class="ri-settings-line"></i> Settings
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if (!$table_exists): ?>
                        <!-- Table Not Found Alert -->
                        <div class="alert alert-warning" role="alert">
                            <h5 class="alert-heading"><i class="ri-alert-line"></i> Notifications Table Not Found</h5>
                            <p>The <code>notifications</code> table does not exist in the database.</p>
                            <p class="mb-0"><strong>Required Table Structure:</strong></p>
                            <pre class="mt-2 mb-0"><code>CREATE TABLE `notifications` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `member_id` INT(11) NOT NULL,
  `type` VARCHAR(50) NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `message` TEXT NOT NULL,
  `is_read` TINYINT(1) NOT NULL DEFAULT 0,
  `related_id` INT(11) NULL,
  `related_type` VARCHAR(50) NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `read_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_member_id` (`member_id`),
  INDEX `idx_is_read` (`is_read`),
  INDEX `idx_type` (`type`),
  INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;</code></pre>
                        </div>
                    <?php else: ?>

                    <!-- Summary Cards -->
                    <div class="row g-3 mb-3">
                        <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
                            <div class="card widget-flat text-bg-primary">
                                <div class="card-body">
                                    <div class="float-end">
                                        <i class="ri-notification-line widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0">Total Notifications</h6>
                                    <h2 class="my-2"><?php echo number_format($total_notifications); ?></h2>
                                    <p class="mb-0">
                                        <span class="text-white-50">All notifications</span>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
                            <div class="card widget-flat text-bg-warning">
                                <div class="card-body">
                                    <div class="float-end">
                                        <i class="ri-mail-unread-line widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0">Unread</h6>
                                    <h2 class="my-2"><?php echo number_format($unread_notifications); ?></h2>
                                    <p class="mb-0">
                                        <a href="notifications_list.php?filter=unread" class="text-white-50">View Unread <i class="ri-arrow-right-line"></i></a>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
                            <div class="card widget-flat text-bg-success">
                                <div class="card-body">
                                    <div class="float-end">
                                        <i class="ri-mail-open-line widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0">Read</h6>
                                    <h2 class="my-2"><?php echo number_format($read_notifications); ?></h2>
                                    <p class="mb-0">
                                        <span class="text-white-50">Already read</span>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
                            <div class="card widget-flat text-bg-info">
                                <div class="card-body">
                                    <div class="float-end">
                                        <i class="ri-calendar-todo-line widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0">Today</h6>
                                    <h2 class="my-2"><?php echo number_format($notifications_today); ?></h2>
                                    <p class="mb-0">
                                        <span class="text-white-50">Sent today</span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
                            <div class="card widget-flat text-bg-info">
                                <div class="card-body">
                                    <div class="float-end">
                                        <i class="ri-calendar-line widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0">Recent (7 Days)</h6>
                                    <h2 class="my-2"><?php echo number_format($recent_notifications); ?></h2>
                                    <p class="mb-0">
                                        <span class="text-white-50">New notifications</span>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
                            <div class="card widget-flat text-bg-secondary">
                                <div class="card-body">
                                    <div class="float-end">
                                        <i class="ri-percent-line widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0">Read Rate</h6>
                                    <h2 class="my-2">
                                        <?php echo $total_notifications > 0 ? number_format(($read_notifications / $total_notifications) * 100, 1) : 0; ?>%
                                    </h2>
                                    <p class="mb-0">
                                        <span class="text-white-50">Read / Total</span>
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
                                    <h4 class="header-title">Notification Trend (12 Months)</h4>
                                </div>
                                <div class="card-body">
                                    <div id="notification-trend-chart" style="min-height: 350px;"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Top Notification Types</h4>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-sm mb-0">
                                            <thead>
                                                <tr>
                                                    <th>Type</th>
                                                    <th>Count</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (empty($type_distribution)): ?>
                                                    <tr>
                                                        <td colspan="2" class="text-center text-muted">No data available</td>
                                                    </tr>
                                                <?php else: ?>
                                                    <?php foreach ($type_distribution as $type): ?>
                                                        <tr>
                                                            <td><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $type['type']))); ?></td>
                                                            <td><span class="badge bg-primary"><?php echo number_format($type['count']); ?></span></td>
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

                    <!-- Recent Notifications -->
                    <div class="row mb-3">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Recent Notifications</h4>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-hover mb-0">
                                            <thead>
                                                <tr>
                                                    <th>Member</th>
                                                    <th>Type</th>
                                                    <th>Title</th>
                                                    <th>Status</th>
                                                    <th>Created</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (empty($recent_notifications_list)): ?>
                                                    <tr>
                                                        <td colspan="6" class="text-center text-muted">No notifications found</td>
                                                    </tr>
                                                <?php else: ?>
                                                    <?php foreach ($recent_notifications_list as $notif): ?>
                                                        <tr>
                                                            <td><strong><?php echo htmlspecialchars($notif['fullname'] ?? 'Unknown'); ?></strong></td>
                                                            <td>
                                                                <span class="badge bg-info"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $notif['type']))); ?></span>
                                                            </td>
                                                            <td><?php echo htmlspecialchars($notif['title']); ?></td>
                                                            <td>
                                                                <span class="badge bg-<?php echo $notif['is_read'] ? 'success' : 'warning'; ?>">
                                                                    <?php echo $notif['is_read'] ? 'Read' : 'Unread'; ?>
                                                                </span>
                                                            </td>
                                                            <td><?php echo date('M d, Y H:i', strtotime($notif['created_at'])); ?></td>
                                                            <td>
                                                                <a href="notifications_list.php?view=<?php echo $notif['id']; ?>" class="btn btn-sm btn-light">
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
        // Notification Trend Chart
        var notificationTrendOptions = {
            chart: {
                type: 'line',
                height: 350,
                toolbar: { show: false }
            },
            series: [{
                name: 'Notifications',
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
        new ApexCharts(document.querySelector("#notification-trend-chart"), notificationTrendOptions).render();
    </script>
    <?php endif; ?>

</body>
</html>

