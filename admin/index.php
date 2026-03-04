<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: auth-login.php");
    exit();
}?>

<!DOCTYPE html>
<html lang="en">

<?php
include 'include/conn.php';
include 'header.php';

// // Fetch total members
// $result_members = $conn->query("SELECT COUNT(*) AS total_members FROM members");
// $row_members = $result_members->fetch_assoc();
// $total_members = $row_members['total_members'];

// Fetch total events (using prepared statement)
$stmt = $conn->prepare("SELECT COUNT(*) AS total_events FROM events");
$stmt->execute();
$result_events = $stmt->get_result();
$row_events = $result_events->fetch_assoc();
$total_events = $row_events['total_events'] ?? 0;
$stmt->close();

// Fetch upcoming events (using prepared statement)
$stmt = $conn->prepare("SELECT COUNT(*) AS total_upcoming FROM upcoming");
$stmt->execute();
$result_upcoming = $stmt->get_result();
$row_upcoming = $result_upcoming->fetch_assoc();
$total_upcoming = $row_upcoming['total_upcoming'] ?? 0;
$stmt->close();

// Fetch total registered members (using prepared statement)
$stmt = $conn->prepare("SELECT COUNT(*) AS total_registrations FROM registrations");
$stmt->execute();
$result_registers = $stmt->get_result();
$row_registers = $result_registers->fetch_assoc();
$total_registers = $row_registers['total_registrations'] ?? 0;
$stmt->close();

// Calculate Active and Expired Subscribers
$active_subscribers = 0;
$expired_subscribers = 0;

$result_all_members = $conn->query("SELECT created_at, payment_duration FROM registrations");
while ($member = $result_all_members->fetch_assoc()) {
    $start_date = new DateTime($member['created_at']);
    $duration = $member['payment_duration'];

    $expiry_date = clone $start_date;
    if (strpos($duration, 'Year') !== false) {
        $years = (int) filter_var($duration, FILTER_SANITIZE_NUMBER_INT);
        if ($years > 0) {
            $expiry_date->modify("+$years year");
        }
    }

    $today = new DateTime();
    if ($expiry_date > $today) {
        $active_subscribers++;
    } else {
        $expired_subscribers++;
    }
}

// Fetch total sent emails (using prepared statement)
$stmt = $conn->prepare("SELECT COUNT(*) AS total_sent FROM sent_emails");
$stmt->execute();
$result_emails = $stmt->get_result();
$row_emails = $result_emails->fetch_assoc();
$total_sent_emails = $row_emails['total_sent'] ?? 0;
$stmt->close();

// Fetch total resources (using prepared statement)
$stmt = $conn->prepare("SELECT COUNT(*) AS total_resources FROM resources");
$stmt->execute();
$result_resources = $stmt->get_result();
$row_resources = $result_resources->fetch_assoc();
$total_resources = $row_resources['total_resources'] ?? 0;
$stmt->close();

// Fetch total research projects (using prepared statement)
$total_research = 0;
$stmt = $conn->prepare("SELECT COUNT(*) AS total_research FROM research_projects");
if ($stmt) {
    $stmt->execute();
    $research_result = $stmt->get_result();
    if ($research_result) {
        $row_research = $research_result->fetch_assoc();
        $total_research = $row_research['total_research'] ?? 0;
    }
    $stmt->close();
}

// Fetch pending approvals (members with pending status) - using prepared statement
$pending_approvals = 0;
$stmt = $conn->prepare("SELECT COUNT(*) AS total_pending FROM registrations WHERE approval_status = 'pending' OR status = 'pending'");
if ($stmt) {
    $stmt->execute();
    $pending_result = $stmt->get_result();
    if ($pending_result) {
        $row_pending = $pending_result->fetch_assoc();
        $pending_approvals = $row_pending['total_pending'] ?? 0;
    }
    $stmt->close();
}

// Fetch recent registrations (last 7 days) - using prepared statement
$recent_registers = 0;
$stmt = $conn->prepare("SELECT COUNT(*) AS total_recent FROM registrations WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
if ($stmt) {
    $stmt->execute();
    $recent_result = $stmt->get_result();
    if ($recent_result) {
        $row_recent = $recent_result->fetch_assoc();
        $recent_registers = $row_recent['total_recent'] ?? 0;
    }
    $stmt->close();
}

// Fetch email subscribers count (using prepared statement)
$total_subscribers = 0;
$stmt = $conn->prepare("SELECT COUNT(*) AS total_subscribers FROM email_subscribers WHERE status = 'active'");
if ($stmt) {
    $stmt->execute();
    $subscribers_result = $stmt->get_result();
    if ($subscribers_result) {
        $row_subscribers = $subscribers_result->fetch_assoc();
        $total_subscribers = $row_subscribers['total_subscribers'] ?? 0;
    }
    $stmt->close();
}

// Fetch total downloads (resources) - using prepared statement
$total_downloads = 0;
$stmt = $conn->prepare("SELECT SUM(download_count) AS total_downloads FROM resources");
if ($stmt) {
    $stmt->execute();
    $downloads_result = $stmt->get_result();
    if ($downloads_result) {
        $row_downloads = $downloads_result->fetch_assoc();
        $total_downloads = $row_downloads['total_downloads'] ?? 0;
    }
    $stmt->close();
}

// Fetch total news posts (using prepared statement)
$total_news = 0;
$stmt = $conn->prepare("SELECT COUNT(*) AS total_news FROM news_media");
if ($stmt) {
    $stmt->execute();
    $news_result = $stmt->get_result();
    if ($news_result) {
        $row_news = $news_result->fetch_assoc();
        $total_news = $row_news['total_news'] ?? 0;
    }
    $stmt->close();
}

// Data for Registrations Chart
$registrations_data = [];
$months = [];
for ($i = 11; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $months[] = date('M Y', strtotime("-$i months"));
    $registrations_data[$month] = 0;
}
$reg_query = "SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(id) as count 
              FROM registrations 
              WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH) 
              GROUP BY month ORDER BY month ASC";
$reg_result = $conn->query($reg_query);
while ($row = $reg_result->fetch_assoc()) {
    $registrations_data[$row['month']] = (int)$row['count'];
}
$registration_values = array_values($registrations_data);

// Data for Events Chart
$events_data = [];
for ($i = 11; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $events_data[$month] = 0;
}

$event_query = "SELECT DATE_FORMAT(event_date, '%Y-%m') as month, COUNT(id) as count
                FROM events 
                WHERE event_date >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
                GROUP BY month ORDER BY month ASC";
$event_result = $conn->query($event_query);
while ($row = $event_result->fetch_assoc()) {
    if (isset($events_data[$row['month']])) {
        $events_data[$row['month']] = (int)$row['count'];
    }
}
$event_values = array_values($events_data);

// Fetch Latest Upcoming Events
$upcoming_events_list = [];
$upcoming_query = "SELECT event_header, event_date FROM upcoming ORDER BY event_date ASC LIMIT 5";
$upcoming_result = $conn->query($upcoming_query);
if ($upcoming_result) {
    while($row = $upcoming_result->fetch_assoc()) {
        $upcoming_events_list[] = $row;
    }
}

// Fetch Latest Regular Events
$latest_events_list = [];
$latest_query = "SELECT event_header, event_date FROM events ORDER BY event_date DESC LIMIT 5";
$latest_result = $conn->query($latest_query);
if ($latest_result) {
    while($row = $latest_result->fetch_assoc()) {
        $latest_events_list[] = $row;
    }
}

// ========== ANALYTICS DASHBOARD QUERIES ==========
// Get date range filter
$date_range = $_GET['range'] ?? '30'; // Default: last 30 days
$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime("-{$date_range} days"));
$end_date = $_GET['end_date'] ?? date('Y-m-d');

// Sanitize dates
$start_date = date('Y-m-d', strtotime($start_date));
$end_date = date('Y-m-d', strtotime($end_date));

// Calculate date range in days
$date1 = new DateTime($start_date);
$date2 = new DateTime($end_date);
$days_diff = $date1->diff($date2)->days;

// Enhanced member analytics (with pending status)
$total_members = $total_registers; // Use existing total
$active_members = 0;
$expired_members = 0;
$pending_members = 0;

$result_all_members_analytics = $conn->query("SELECT created_at, payment_duration, approval_status, status FROM registrations");
while ($member = $result_all_members_analytics->fetch_assoc()) {
    if ($member['approval_status'] == 'pending' || $member['status'] == 'pending') {
        $pending_members++;
    } else {
        $start_date_member = new DateTime($member['created_at']);
        $duration = $member['payment_duration'];
        $expiry_date = clone $start_date_member;
        
        if (strpos($duration, 'Year') !== false) {
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

// New members in date range
$new_members_count = $conn->query("SELECT COUNT(*) as count FROM registrations WHERE created_at BETWEEN '{$start_date} 00:00:00' AND '{$end_date} 23:59:59'")->fetch_assoc()['count'];

// Member growth chart data (last 12 months) - using existing $member_growth_months and $member_growth_values
$member_growth_months = $months; // Reuse existing months array
$member_growth_values = $registration_values; // Reuse existing registration values

// Events created chart data (using created_at instead of event_date)
$events_created_data = [];
for ($i = 11; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $events_created_data[$month] = 0;
}

$events_query = "SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(id) as count
                 FROM events 
                 WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
                 GROUP BY month ORDER BY month ASC";
$events_result = $conn->query($events_query);
while ($row = $events_result->fetch_assoc()) {
    if (isset($events_created_data[$row['month']])) {
        $events_created_data[$row['month']] = (int)$row['count'];
    }
}
$events_created_values = array_values($events_created_data);

// Top downloaded resources
$top_resources = $conn->query("SELECT id, title, section, download_count FROM resources ORDER BY download_count DESC LIMIT 10")->fetch_all(MYSQLI_ASSOC);

// Resource downloads by section
$resource_sections = $conn->query("SELECT section, SUM(download_count) as total_downloads FROM resources GROUP BY section ORDER BY total_downloads DESC")->fetch_all(MYSQLI_ASSOC);

// User engagement analytics
$total_activities = $conn->query("SELECT COUNT(*) as count FROM member_activities")->fetch_assoc()['count'];
$activities_in_range = $conn->query("SELECT COUNT(*) as count FROM member_activities WHERE created_at BETWEEN '{$start_date} 00:00:00' AND '{$end_date} 23:59:59'")->fetch_assoc()['count'];

// Activity types breakdown
$activity_types = $conn->query("SELECT activity_type, COUNT(*) as count FROM member_activities GROUP BY activity_type ORDER BY count DESC")->fetch_all(MYSQLI_ASSOC);

// Most active members
$active_members_list = $conn->query("SELECT r.id, r.fullname, r.email, COUNT(ma.id) as activity_count 
                                     FROM registrations r 
                                     LEFT JOIN member_activities ma ON r.id = ma.member_id 
                                     GROUP BY r.id 
                                     ORDER BY activity_count DESC 
                                     LIMIT 10")->fetch_all(MYSQLI_ASSOC);

// Email analytics
$total_emails = $total_sent_emails; // Use existing value
$emails_in_range = $conn->query("SELECT COUNT(*) as count FROM sent_emails WHERE sent_at BETWEEN '{$start_date} 00:00:00' AND '{$end_date} 23:59:59'")->fetch_assoc()['count'];

// Growth rate calculations
$prev_start = date('Y-m-d', strtotime($start_date . " -{$days_diff} days"));
$prev_end = $start_date;

$prev_new_members = $conn->query("SELECT COUNT(*) as count FROM registrations WHERE created_at BETWEEN '{$prev_start} 00:00:00' AND '{$prev_end} 23:59:59'")->fetch_assoc()['count'];
$member_growth_rate = $prev_new_members > 0 ? (($new_members_count - $prev_new_members) / $prev_new_members) * 100 : 0;

$prev_activities = $conn->query("SELECT COUNT(*) as count FROM member_activities WHERE created_at BETWEEN '{$prev_start} 00:00:00' AND '{$prev_end} 23:59:59'")->fetch_assoc()['count'];
$activity_growth_rate = $prev_activities > 0 ? (($activities_in_range - $prev_activities) / $prev_activities) * 100 : 0;
?>


<body>
    <!-- Begin page -->
    <div class="wrapper">

        <!-- sidebar -->
        <?php
        include 'sidebar.php';
        ?>
        <!-- ============================================================== -->
        <!-- Start Page Content here -->
        <!-- ============================================================== -->

        <div class="content-page">
            <div class="content">

                <!-- Start Content-->
                <div class="container-fluid">

                    <!-- Page Title with Enhanced Design -->
                    <div class="row">
                        <div class="col-12">
                            <div class="page-title-box d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2 gap-md-0">
                                <div class="text-start">
                                    <h4 class="page-title mb-1 mb-md-0">Dashboard</h4>
                                    <p class="page-subtitle mb-0">Welcome back! Here's what's happening with your organization today.</p>
                                </div>
                                <div class="d-flex flex-wrap gap-2 justify-content-start justify-content-md-end w-100 w-md-auto">
                                    <form method="GET" class="d-flex flex-wrap gap-2 align-items-center">
                                        <select name="range" class="form-select form-select-sm" style="height: 32px;" onchange="this.form.submit()">
                                            <option value="7" <?php echo $date_range == '7' ? 'selected' : ''; ?>>Last 7 Days</option>
                                            <option value="30" <?php echo $date_range == '30' ? 'selected' : ''; ?>>Last 30 Days</option>
                                            <option value="90" <?php echo $date_range == '90' ? 'selected' : ''; ?>>Last 90 Days</option>
                                            <option value="365" <?php echo $date_range == '365' ? 'selected' : ''; ?>>Last Year</option>
                                            <option value="custom" <?php echo $date_range == 'custom' ? 'selected' : ''; ?>>Custom Range</option>
                                        </select>
                                        <?php if ($date_range == 'custom'): ?>
                                            <input type="date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>" class="form-control form-control-sm">
                                            <input type="date" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>" class="form-control form-control-sm">
                                            <button type="submit" class="btn btn-sm btn-primary">Apply</button>
                                        <?php endif; ?>
                                    </form>
                                    <div class="d-flex flex-row gap-1">
                                        <a href="reports_dashboard.php" class="btn btn-secondary btn-sm" style="height: 32px;">
                                            <i class="ri-bar-chart-line"></i> Reports
                                        </a>
                                        <a href="tools.php" class="btn btn-outline-primary btn-sm" style="height: 32px;">
                                            <i class="ri-tools-line"></i> Tools
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- end page title -->

                    <!-- Summary Cards - Smart Grid Layout -->
                    <div class="row g-3">
                        <!-- Row 1: Core Statistics -->
                        <!-- Total Members -->
                        <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
                            <div class="card widget-flat text-bg-primary">
                                <div class="card-body">
                                    <div class="float-end">
                                        <i class="ri-team-line widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0">Total Members</h6>
                                    <h2 class="my-2"><?php echo number_format($total_registers); ?></h2>
                                    <a href="members_list.php" class="text-white-50 small">View all <i class="ri-arrow-right-s-line"></i></a>
                                </div>
                            </div>
                        </div>

                        <!-- Active Subscribers -->
                        <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
                            <div class="card widget-flat text-bg-success">
                                <div class="card-body">
                                    <div class="float-end">
                                        <i class="ri-user-follow-line widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0">Active Subscribers</h6>
                                    <h2 class="my-2"><?php echo number_format($active_subscribers); ?></h2>
                                    <span class="text-white-50 small">Expired: <?php echo number_format($expired_subscribers); ?></span>
                                </div>
                            </div>
                        </div>

                        <!-- Pending Approvals -->
                        <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
                            <div class="card widget-flat text-bg-warning">
                                <div class="card-body">
                                    <div class="float-end">
                                        <i class="ri-time-line widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0">Pending Approvals</h6>
                                    <h2 class="my-2"><?php echo number_format($pending_approvals); ?></h2>
                                    <?php if ($pending_approvals > 0): ?>
                                        <a href="members_list.php?status=pending" class="text-white-50 small">Review <i class="ri-arrow-right-s-line"></i></a>
                                    <?php else: ?>
                                        <span class="text-white-50 small">All processed</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Recent Registrations (Last 7 Days) -->
                        <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
                            <div class="card widget-flat text-bg-info">
                                <div class="card-body">
                                    <div class="float-end">
                                        <i class="ri-user-add-line widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0">Recent Registrations</h6>
                                    <h2 class="my-2"><?php echo number_format($recent_registers); ?></h2>
                                    <span class="text-white-50 small">Last 7 days</span>
                                </div>
                            </div>
                        </div>

                        <!-- Row 2: Content & Resources -->
                        <!-- Total Events -->
                        <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
                            <div class="card widget-flat text-bg-info">
                                <div class="card-body">
                                    <div class="float-end">
                                        <i class="ri-calendar-check-line widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0">Total Events</h6>
                                    <h2 class="my-2"><?php echo number_format($total_events); ?></h2>
                                    <span class="text-white-50 small">Upcoming: <?php echo number_format($total_upcoming); ?></span>
                                </div>
                            </div>
                        </div>

                        <!-- Total Resources -->
                        <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
                            <div class="card widget-flat text-bg-dark">
                                <div class="card-body">
                                    <div class="float-end">
                                        <i class="ri-file-paper-line widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0">Total Resources</h6>
                                    <h2 class="my-2"><?php echo number_format($total_resources); ?></h2>
                                    <a href="resources_list.php" class="text-white-50 small">Manage <i class="ri-arrow-right-s-line"></i></a>
                                </div>
                            </div>
                        </div>

                        <!-- Total Research Projects -->
                        <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
                            <div class="card widget-flat" style="background: linear-gradient(135deg, #0ea5e9 0%, #2563eb 100%);">
                                <div class="card-body">
                                    <div class="float-end">
                                        <i class="ri-search-line widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0 text-white">Research Projects</h6>
                                    <h2 class="my-2 text-white"><?php echo number_format($total_research); ?></h2>
                                    <a href="research_list.php" class="text-white-50 small">View all <i class="ri-arrow-right-s-line"></i></a>
                                </div>
                            </div>
                        </div>

                        <!-- Total Downloads -->
                        <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
                            <div class="card widget-flat text-bg-secondary">
                                <div class="card-body">
                                    <div class="float-end">
                                        <i class="ri-download-line widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0">Total Downloads</h6>
                                    <h2 class="my-2"><?php echo number_format($total_downloads); ?></h2>
                                    <span class="text-white-50 small">Resources</span>
                                </div>
                            </div>
                        </div>

                        <!-- Row 3: Communications & Content -->
                        <!-- Total Sent Emails -->
                        <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
                            <div class="card widget-flat text-bg-danger">
                                <div class="card-body">
                                    <div class="float-end">
                                        <i class="ri-mail-send-line widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0">Sent Emails</h6>
                                    <h2 class="my-2"><?php echo number_format($total_sent_emails); ?></h2>
                                    <a href="sent_emails_list.php" class="text-white-50 small">View log <i class="ri-arrow-right-s-line"></i></a>
                                </div>
                            </div>
                        </div>

                        <!-- Email Subscribers -->
                        <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
                            <div class="card widget-flat" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                                <div class="card-body">
                                    <div class="float-end">
                                        <i class="ri-mail-line widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0 text-white">Email Subscribers</h6>
                                    <h2 class="my-2 text-white"><?php echo number_format($total_subscribers); ?></h2>
                                    <a href="subscribers_list.php" class="text-white-50 small">Manage <i class="ri-arrow-right-s-line"></i></a>
                                </div>
                            </div>
                        </div>

                        <!-- Total News Posts -->
                        <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
                            <div class="card widget-flat" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                                <div class="card-body">
                                    <div class="float-end">
                                        <i class="ri-newspaper-line widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0 text-white">News Posts</h6>
                                    <h2 class="my-2 text-white"><?php echo number_format($total_news); ?></h2>
                                    <a href="news_list.php" class="text-white-50 small">Manage <i class="ri-arrow-right-s-line"></i></a>
                                </div>
                            </div>
                        </div>

                        <!-- System Health (Placeholder) -->
                        <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
                            <div class="card widget-flat text-bg-success">
                                <div class="card-body">
                                    <div class="float-end">
                                        <i class="ri-health-book-line widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0">System Status</h6>
                                    <h2 class="my-2"><i class="ri-checkbox-circle-line"></i> Online</h2>
                                    <span class="text-white-50 small">All systems operational</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-lg-4">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Subscription Status</h4>
                                </div>
                                <div class="card-body">
                                    <div id="subscription-status-chart" class="apex-charts" data-colors="#47ad77,#fa5c7c"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-8">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Monthly New Members</h4>
                                </div>
                                <div class="card-body">
                                    <div id="registrations-over-time-chart" class="apex-charts" data-colors="#3e60d5"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Monthly Events Created</h4>
                                </div>
                                <div class="card-body">
                                    <div id="events-per-month-chart" class="apex-charts" data-colors="#ffbc00"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-lg-6">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h4 class="header-title">Upcoming Events</h4>
                                    <a href="upcoming_list.php" class="btn btn-sm btn-light">See More</a>
                                </div>
                                <div class="card-body pt-0">
                                    <ul class="list-group list-group-flush">
                                        <?php foreach($upcoming_events_list as $event): ?>
                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                <?php echo htmlspecialchars($event['event_header']); ?>
                                                <span class="badge bg-primary rounded-pill"><?php echo date('M d, Y', strtotime($event['event_date'])); ?></span>
                                            </li>
                                        <?php endforeach; ?>
                                        <?php if (empty($upcoming_events_list)): ?>
                                            <li class="list-group-item">No upcoming events found.</li>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h4 class="header-title">Latest Events</h4>
                                    <a href="regular_list.php" class="btn btn-sm btn-light">See More</a>
                                </div>
                                <div class="card-body pt-0">
                                    <ul class="list-group list-group-flush">
                                        <?php foreach($latest_events_list as $event): ?>
                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                <?php echo htmlspecialchars($event['event_header']); ?>
                                                <span class="badge bg-info rounded-pill"><?php echo date('M d, Y', strtotime($event['event_date'])); ?></span>
                                            </li>
                                        <?php endforeach; ?>
                                        <?php if (empty($latest_events_list)): ?>
                                            <li class="list-group-item">No recent events found.</li>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Analytics Charts Row -->
                    <div class="row">
                        <!-- Member Growth Chart -->
                        <div class="col-lg-8">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Member Growth (Last 12 Months)</h4>
                                </div>
                                <div class="card-body">
                                    <div id="member-growth-chart" class="apex-charts" data-colors="#3e60d5"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Membership Status -->
                        <div class="col-lg-4">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Membership Status</h4>
                                </div>
                                <div class="card-body">
                                    <div id="membership-status-chart" class="apex-charts" data-colors="#47ad77,#fa5c7c,#ffbc00"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Events & Resources Row -->
                    <div class="row">
                        <!-- Events Chart -->
                        <div class="col-lg-6">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Events Created (Last 12 Months)</h4>
                                </div>
                                <div class="card-body">
                                    <div id="events-chart" class="apex-charts" data-colors="#ffbc00"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Resource Downloads by Section -->
                        <div class="col-lg-6">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Resource Downloads by Section</h4>
                                </div>
                                <div class="card-body">
                                    <div id="resource-downloads-chart" class="apex-charts" data-colors="#6c757d"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Activity Types & Top Resources -->
                    <div class="row">
                        <!-- Activity Types -->
                        <div class="col-lg-6">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Activity Types Breakdown</h4>
                                </div>
                                <div class="card-body">
                                    <div id="activity-types-chart" class="apex-charts" data-colors="#10b759"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Top Resources -->
                        <div class="col-lg-6">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Top Downloaded Resources</h4>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-sm table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Resource</th>
                                                    <th>Section</th>
                                                    <th class="text-end">Downloads</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($top_resources as $resource): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($resource['title']); ?></td>
                                                    <td><span class="badge bg-secondary"><?php echo htmlspecialchars($resource['section']); ?></span></td>
                                                    <td class="text-end"><strong><?php echo number_format($resource['download_count']); ?></strong></td>
                                                </tr>
                                                <?php endforeach; ?>
                                                <?php if (empty($top_resources)): ?>
                                                <tr>
                                                    <td colspan="3" class="text-center text-muted">No resources found</td>
                                                </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Most Active Members & Summary Stats -->
                    <div class="row">
                        <!-- Most Active Members -->
                        <div class="col-lg-6">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Most Active Members</h4>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-sm table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Member</th>
                                                    <th>Email</th>
                                                    <th class="text-end">Activities</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($active_members_list as $member): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($member['fullname']); ?></td>
                                                    <td><?php echo htmlspecialchars($member['email']); ?></td>
                                                    <td class="text-end"><strong><?php echo number_format($member['activity_count']); ?></strong></td>
                                                </tr>
                                                <?php endforeach; ?>
                                                <?php if (empty($active_members_list)): ?>
                                                <tr>
                                                    <td colspan="3" class="text-center text-muted">No activity data found</td>
                                                </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Summary Stats -->
                        <div class="col-lg-6">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Summary Statistics</h4>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-6">
                                            <div class="text-center p-3 border rounded">
                                                <h3 class="mb-0 text-primary"><?php echo number_format($total_events); ?></h3>
                                                <p class="mb-0 text-muted">Total Events</p>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="text-center p-3 border rounded">
                                                <h3 class="mb-0 text-info"><?php echo number_format($total_upcoming); ?></h3>
                                                <p class="mb-0 text-muted">Upcoming Events</p>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="text-center p-3 border rounded">
                                                <h3 class="mb-0 text-success"><?php echo number_format($total_emails); ?></h3>
                                                <p class="mb-0 text-muted">Emails Sent</p>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="text-center p-3 border rounded">
                                                <h3 class="mb-0 text-warning"><?php echo number_format($total_subscribers); ?></h3>
                                                <p class="mb-0 text-muted">Email Subscribers</p>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="text-center p-3 border rounded">
                                                <h3 class="mb-0 text-purple"><?php echo number_format($total_news); ?></h3>
                                                <p class="mb-0 text-muted">News Posts</p>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="text-center p-3 border rounded">
                                                <h3 class="mb-0 text-danger"><?php echo number_format($total_research); ?></h3>
                                                <p class="mb-0 text-muted">Research Projects</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
                <!-- container -->

            </div>
            <!-- content -->

            <!-- Footer Start -->
            <?php
            include 'footer.php';
            ?>
            <!-- end Footer -->

        </div>

        <!-- ============================================================== -->
        <!-- End Page content -->
        <!-- ============================================================== -->

    </div>
    <!-- END wrapper -->


    <!-- Vendor js -->
    <script src="assets/js/vendor.min.js"></script>

    <!-- Daterangepicker js -->
    <script src="assets/vendor/daterangepicker/moment.min.js"></script>
    <script src="assets/vendor/daterangepicker/daterangepicker.js"></script>

    <!-- Apex Charts js -->
    <script src="assets/vendor/apexcharts/apexcharts.min.js"></script>

    <!-- Vector Map js -->
    <script src="assets/vendor/admin-resources/jquery.vectormap/jquery-jvectormap-1.2.2.min.js"></script>
    <script src="assets/vendor/admin-resources/jquery.vectormap/maps/jquery-jvectormap-world-mill-en.js"></script>

    <!-- Dashboard App js -->
    <!-- <script src="assets/js/pages/dashboard.js"></script> -->


    <!-- App js -->
    <script src="assets/js/app.min.js"></script>

    <script>
        $(document).ready(function() {
            // Subscription Status Pie Chart
            var subStatusOptions = {
                chart: {
                    type: 'pie',
                    height: 320,
                },
                series: [<?php echo $active_subscribers; ?>, <?php echo $expired_subscribers; ?>],
                labels: ['Active Subscribers', 'Expired Subscribers'],
                colors: ['#47ad77', '#fa5c7c'],
                legend: {
                    show: true,
                    position: 'bottom',
                },
                dataLabels: {
                    enabled: true,
                    formatter: function (val, opts) {
                        return opts.w.globals.labels[opts.seriesIndex]
                    },
                }
            };
            new ApexCharts(document.querySelector("#subscription-status-chart"), subStatusOptions).render();

            // Registrations Line Chart
            var regOptions = {
                chart: {
                    type: 'line',
                    height: 350,
                    toolbar: { show: false }
                },
                series: [{
                    name: 'New Members',
                    data: <?php echo json_encode($registration_values); ?>
                }],
                xaxis: {
                    categories: <?php echo json_encode($months); ?>
                },
                colors: ['#3e60d5'],
                stroke: {
                    width: 3
                }
            };
            new ApexCharts(document.querySelector("#registrations-over-time-chart"), regOptions).render();

            // Events Bar Chart
            var eventOptions = {
                chart: {
                    type: 'bar',
                    height: 350,
                    toolbar: { show: false }
                },
                series: [{
                    name: 'Events Created',
                    data: <?php echo json_encode($event_values); ?>
                }],
                xaxis: {
                    categories: <?php echo json_encode($months); ?>
                },
                colors: ['#ffbc00']
            };
            new ApexCharts(document.querySelector("#events-per-month-chart"), eventOptions).render();

            // Member Growth Chart
            var memberGrowthOptions = {
                chart: {
                    type: 'line',
                    height: 350,
                    toolbar: { show: true }
                },
                series: [{
                    name: 'New Members',
                    data: <?php echo json_encode($member_growth_values); ?>
                }],
                xaxis: {
                    categories: <?php echo json_encode($member_growth_months); ?>
                },
                colors: ['#3e60d5'],
                stroke: {
                    width: 3,
                    curve: 'smooth'
                },
                markers: {
                    size: 5
                }
            };
            new ApexCharts(document.querySelector("#member-growth-chart"), memberGrowthOptions).render();

            // Membership Status Pie Chart
            var membershipStatusOptions = {
                chart: {
                    type: 'pie',
                    height: 300
                },
                series: [<?php echo $active_members; ?>, <?php echo $expired_members; ?>, <?php echo $pending_members; ?>],
                labels: ['Active', 'Expired', 'Pending'],
                colors: ['#47ad77', '#fa5c7c', '#ffbc00'],
                legend: {
                    show: true,
                    position: 'bottom'
                }
            };
            new ApexCharts(document.querySelector("#membership-status-chart"), membershipStatusOptions).render();

            // Events Created Chart
            var eventsOptions = {
                chart: {
                    type: 'bar',
                    height: 350,
                    toolbar: { show: true }
                },
                series: [{
                    name: 'Events Created',
                    data: <?php echo json_encode($events_created_values); ?>
                }],
                xaxis: {
                    categories: <?php echo json_encode($member_growth_months); ?>
                },
                colors: ['#ffbc00']
            };
            new ApexCharts(document.querySelector("#events-chart"), eventsOptions).render();

            // Resource Downloads Chart
            var resourceSections = <?php echo json_encode(!empty($resource_sections) ? array_column($resource_sections, 'section') : []); ?>;
            var resourceDownloads = <?php echo json_encode(!empty($resource_sections) ? array_column($resource_sections, 'total_downloads') : []); ?>;
            
            if (resourceSections.length > 0) {
                var resourceDownloadsOptions = {
                    chart: {
                        type: 'bar',
                        height: 350,
                        toolbar: { show: true },
                        horizontal: true
                    },
                    series: [{
                        name: 'Downloads',
                        data: resourceDownloads
                    }],
                    xaxis: {
                        categories: resourceSections
                    },
                    colors: ['#6c757d']
                };
                new ApexCharts(document.querySelector("#resource-downloads-chart"), resourceDownloadsOptions).render();
            }

            // Activity Types Chart
            var activityTypes = <?php echo json_encode(!empty($activity_types) ? array_column($activity_types, 'activity_type') : []); ?>;
            var activityCounts = <?php echo json_encode(!empty($activity_types) ? array_column($activity_types, 'count') : []); ?>;
            
            if (activityTypes.length > 0) {
                var activityTypesOptions = {
                    chart: {
                        type: 'donut',
                        height: 350
                    },
                    series: activityCounts,
                    labels: activityTypes,
                    colors: ['#10b759', '#3e60d5', '#ffbc00', '#fa5c7c', '#6c757d'],
                    legend: {
                        show: true,
                        position: 'bottom'
                    }
                };
                new ApexCharts(document.querySelector("#activity-types-chart"), activityTypesOptions).render();
            }
        });
    </script>

</body>

</html>