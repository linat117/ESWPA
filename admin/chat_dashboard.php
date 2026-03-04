<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: auth-login.php");
    exit();
}

include 'include/conn.php';
include 'header.php';

// Check if telegram_messages table exists
$table_exists = false;
$result = $conn->query("SHOW TABLES LIKE 'telegram_messages'");
if ($result && $result->num_rows > 0) {
    $table_exists = true;
}

// Fetch chat statistics (only if table exists)
$total_messages = 0;
$sent_messages = 0;
$failed_messages = 0;
$pending_messages = 0;
$recent_messages = 0;
$unique_users = 0;

$recent_messages_list = [];
$status_distribution = [];
$monthly_data = [];

if ($table_exists) {
    // Total messages
    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM telegram_messages");
    $stmt->execute();
    $result = $stmt->get_result();
    $total_messages = $result->fetch_assoc()['total'] ?? 0;
    $stmt->close();

    // Sent messages
    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM telegram_messages WHERE status = 'sent'");
    $stmt->execute();
    $result = $stmt->get_result();
    $sent_messages = $result->fetch_assoc()['total'] ?? 0;
    $stmt->close();

    // Failed messages
    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM telegram_messages WHERE status = 'failed'");
    $stmt->execute();
    $result = $stmt->get_result();
    $failed_messages = $result->fetch_assoc()['total'] ?? 0;
    $stmt->close();

    // Pending messages
    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM telegram_messages WHERE status = 'pending'");
    $stmt->execute();
    $result = $stmt->get_result();
    $pending_messages = $result->fetch_assoc()['total'] ?? 0;
    $stmt->close();

    // Recent messages (last 7 days)
    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM telegram_messages WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
    $stmt->execute();
    $result = $stmt->get_result();
    $recent_messages = $result->fetch_assoc()['total'] ?? 0;
    $stmt->close();

    // Unique users (by email)
    $stmt = $conn->prepare("SELECT COUNT(DISTINCT user_email) AS total FROM telegram_messages WHERE user_email IS NOT NULL AND user_email != ''");
    $stmt->execute();
    $result = $stmt->get_result();
    $unique_users = $result->fetch_assoc()['total'] ?? 0;
    $stmt->close();

    // Recent messages list
    $stmt = $conn->prepare("SELECT * FROM telegram_messages ORDER BY created_at DESC LIMIT 10");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $recent_messages_list[] = $row;
    }
    $stmt->close();

    // Status distribution
    $stmt = $conn->prepare("SELECT status, COUNT(*) as count FROM telegram_messages GROUP BY status");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $status_distribution[$row['status']] = $row['count'];
    }
    $stmt->close();

    // Monthly message trend
    $months = [];
    for ($i = 11; $i >= 0; $i--) {
        $month = date('Y-m', strtotime("-$i months"));
        $months[] = date('M Y', strtotime("-$i months"));
        $monthly_data[$month] = 0;
    }
    $monthly_query = "SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(id) as count 
                      FROM telegram_messages 
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
                                <h4 class="page-title">Chat Dashboard</h4>
                                <div>
                                    <a href="chat_conversations.php" class="btn btn-secondary me-2">
                                        <i class="ri-message-3-line"></i> All Messages
                                    </a>
                                    <a href="chat_settings.php" class="btn btn-info">
                                        <i class="ri-settings-line"></i> Settings
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if (!$table_exists): ?>
                        <!-- Table Not Found Alert -->
                        <div class="alert alert-info" role="alert">
                            <h5 class="alert-heading"><i class="ri-information-line"></i> Telegram Messages Table Not Found</h5>
                            <p>The <code>telegram_messages</code> table does not exist in the database. This is optional - the chat system works without it.</p>
                            <p class="mb-0"><strong>Optional Table Structure:</strong></p>
                            <pre class="mt-2 mb-0"><code>CREATE TABLE `telegram_messages` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_name` VARCHAR(255) NULL,
  `user_email` VARCHAR(191) NULL,
  `user_phone` VARCHAR(50) NULL,
  `message` TEXT NOT NULL,
  `telegram_message_id` VARCHAR(100) NULL,
  `status` ENUM('sent','failed','pending') NOT NULL DEFAULT 'pending',
  `ip_address` VARCHAR(45) NULL,
  `user_agent` TEXT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_status` (`status`),
  INDEX `idx_created_at` (`created_at`),
  INDEX `idx_user_email` (`user_email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;</code></pre>
                        </div>
                    <?php else: ?>

                    <!-- Summary Cards -->
                    <div class="row g-3 mb-3">
                        <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
                            <div class="card widget-flat text-bg-primary">
                                <div class="card-body">
                                    <div class="float-end">
                                        <i class="ri-message-3-line widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0">Total Messages</h6>
                                    <h2 class="my-2"><?php echo number_format($total_messages); ?></h2>
                                    <p class="mb-0">
                                        <span class="text-white-50">All messages</span>
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
                                    <h6 class="text-uppercase mt-0">Sent</h6>
                                    <h2 class="my-2"><?php echo number_format($sent_messages); ?></h2>
                                    <p class="mb-0">
                                        <span class="text-white-50">Successfully sent</span>
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
                                    <h6 class="text-uppercase mt-0">Failed</h6>
                                    <h2 class="my-2"><?php echo number_format($failed_messages); ?></h2>
                                    <p class="mb-0">
                                        <span class="text-white-50">Delivery failed</span>
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
                                    <h2 class="my-2"><?php echo number_format($pending_messages); ?></h2>
                                    <p class="mb-0">
                                        <span class="text-white-50">Awaiting delivery</span>
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
                                    <h2 class="my-2"><?php echo number_format($recent_messages); ?></h2>
                                    <p class="mb-0">
                                        <span class="text-white-50">New messages</span>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
                            <div class="card widget-flat text-bg-info">
                                <div class="card-body">
                                    <div class="float-end">
                                        <i class="ri-user-line widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0">Unique Users</h6>
                                    <h2 class="my-2"><?php echo number_format($unique_users); ?></h2>
                                    <p class="mb-0">
                                        <span class="text-white-50">By email</span>
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
                                    <h6 class="text-uppercase mt-0">Success Rate</h6>
                                    <h2 class="my-2">
                                        <?php echo $total_messages > 0 ? number_format(($sent_messages / $total_messages) * 100, 1) : 0; ?>%
                                    </h2>
                                    <p class="mb-0">
                                        <span class="text-white-50">Sent / Total</span>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
                            <div class="card widget-flat text-bg-dark">
                                <div class="card-body">
                                    <div class="float-end">
                                        <i class="ri-bar-chart-line widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0">Avg per Day</h6>
                                    <h2 class="my-2">
                                        <?php 
                                        $days = 30; // Last 30 days
                                        $avg = $total_messages > 0 ? round($total_messages / max($days, 1)) : 0;
                                        echo number_format($avg); 
                                        ?>
                                    </h2>
                                    <p class="mb-0">
                                        <span class="text-white-50">Last 30 days</span>
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
                                    <h4 class="header-title">Message Trend (12 Months)</h4>
                                </div>
                                <div class="card-body">
                                    <div id="message-trend-chart" style="min-height: 350px;"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Status Distribution</h4>
                                </div>
                                <div class="card-body">
                                    <div id="status-chart" style="min-height: 350px;"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Messages -->
                    <div class="row mb-3">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Recent Messages</h4>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-hover mb-0">
                                            <thead>
                                                <tr>
                                                    <th>User</th>
                                                    <th>Email</th>
                                                    <th>Message</th>
                                                    <th>Status</th>
                                                    <th>Created</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (empty($recent_messages_list)): ?>
                                                    <tr>
                                                        <td colspan="6" class="text-center text-muted">No messages found</td>
                                                    </tr>
                                                <?php else: ?>
                                                    <?php foreach ($recent_messages_list as $msg): ?>
                                                        <tr>
                                                            <td><strong><?php echo htmlspecialchars($msg['user_name'] ?? 'Anonymous'); ?></strong></td>
                                                            <td><?php echo htmlspecialchars($msg['user_email'] ?? '-'); ?></td>
                                                            <td><?php echo htmlspecialchars(substr($msg['message'], 0, 100)) . (strlen($msg['message']) > 100 ? '...' : ''); ?></td>
                                                            <td>
                                                                <span class="badge bg-<?php 
                                                                    echo $msg['status'] == 'sent' ? 'success' : 
                                                                        ($msg['status'] == 'failed' ? 'danger' : 'warning'); 
                                                                ?>">
                                                                    <?php echo ucfirst($msg['status']); ?>
                                                                </span>
                                                            </td>
                                                            <td><?php echo date('M d, Y H:i', strtotime($msg['created_at'])); ?></td>
                                                            <td>
                                                                <a href="chat_conversations.php?view=<?php echo $msg['id']; ?>" class="btn btn-sm btn-light">
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
        // Message Trend Chart
        var messageTrendOptions = {
            chart: {
                type: 'line',
                height: 350,
                toolbar: { show: false }
            },
            series: [{
                name: 'Messages',
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
        new ApexCharts(document.querySelector("#message-trend-chart"), messageTrendOptions).render();

        // Status Distribution Chart
        var statusChartOptions = {
            chart: {
                type: 'donut',
                height: 350
            },
            series: <?php echo json_encode(array_values($status_distribution)); ?>,
            labels: <?php echo json_encode(array_map('ucfirst', array_keys($status_distribution))); ?>,
            colors: ['#0acf97', '#fa5c7c', '#f7b84b'],
            legend: { position: 'bottom' }
        };
        new ApexCharts(document.querySelector("#status-chart"), statusChartOptions).render();
    </script>
    <?php endif; ?>

</body>
</html>

