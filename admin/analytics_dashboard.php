<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: auth-login.php");
    exit();
}

include 'include/conn.php';
include 'header.php';

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

// ========== MEMBER GROWTH ANALYTICS ==========
// Total members
$total_members = $conn->query("SELECT COUNT(*) as count FROM registrations")->fetch_assoc()['count'];

// Active members (not expired)
$active_members = 0;
$expired_members = 0;
$pending_members = 0;

$result_all_members = $conn->query("SELECT created_at, payment_duration, approval_status, status FROM registrations");
while ($member = $result_all_members->fetch_assoc()) {
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

// Member growth chart data (last 12 months)
$member_growth_data = [];
$member_growth_months = [];
for ($i = 11; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $member_growth_months[] = date('M Y', strtotime("-$i months"));
    $member_growth_data[$month] = 0;
}

$member_growth_query = "SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(id) as count 
                        FROM registrations 
                        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH) 
                        GROUP BY month ORDER BY month ASC";
$member_growth_result = $conn->query($member_growth_query);
while ($row = $member_growth_result->fetch_assoc()) {
    if (isset($member_growth_data[$row['month']])) {
        $member_growth_data[$row['month']] = (int)$row['count'];
    }
}
$member_growth_values = array_values($member_growth_data);

// ========== EVENT ANALYTICS ==========
// Total events
$total_events = $conn->query("SELECT COUNT(*) as count FROM events")->fetch_assoc()['count'];
$total_upcoming = $conn->query("SELECT COUNT(*) as count FROM upcoming")->fetch_assoc()['count'];

// Events in date range
$events_in_range = $conn->query("SELECT COUNT(*) as count FROM events WHERE event_date BETWEEN '{$start_date}' AND '{$end_date}'")->fetch_assoc()['count'];

// Events created chart data
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

// ========== RESOURCE ANALYTICS ==========
// Total resources
$total_resources = $conn->query("SELECT COUNT(*) as count FROM resources")->fetch_assoc()['count'];

// Total downloads
$total_downloads = $conn->query("SELECT SUM(download_count) as total FROM resources")->fetch_assoc()['total'] ?? 0;

// Top downloaded resources
$top_resources = $conn->query("SELECT id, title, section, download_count FROM resources ORDER BY download_count DESC LIMIT 10")->fetch_all(MYSQLI_ASSOC);

// Resource downloads by section
$resource_sections = $conn->query("SELECT section, SUM(download_count) as total_downloads FROM resources GROUP BY section ORDER BY total_downloads DESC")->fetch_all(MYSQLI_ASSOC);

// ========== USER ENGAGEMENT ANALYTICS ==========
// Total activities
$total_activities = $conn->query("SELECT COUNT(*) as count FROM member_activities")->fetch_assoc()['count'];

// Activities in date range
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

// ========== EMAIL ANALYTICS ==========
// Total emails sent
$total_emails = $conn->query("SELECT COUNT(*) as count FROM sent_emails")->fetch_assoc()['count'];

// Emails in date range
$emails_in_range = $conn->query("SELECT COUNT(*) as count FROM sent_emails WHERE sent_at BETWEEN '{$start_date} 00:00:00' AND '{$end_date} 23:59:59'")->fetch_assoc()['count'];

// Email subscribers
$total_subscribers = $conn->query("SELECT COUNT(*) as count FROM email_subscribers WHERE status = 'active'")->fetch_assoc()['count'];

// ========== NEWS ANALYTICS ==========
// Total news posts
$total_news = $conn->query("SELECT COUNT(*) as count FROM news_media")->fetch_assoc()['count'];

// News in date range
$news_in_range = $conn->query("SELECT COUNT(*) as count FROM news_media WHERE created_at BETWEEN '{$start_date} 00:00:00' AND '{$end_date} 23:59:59'")->fetch_assoc()['count'];

// ========== RESEARCH ANALYTICS ==========
// Total research projects
$total_research = 0;
$research_query = "SELECT COUNT(*) as count FROM research_projects";
$research_result = $conn->query($research_query);
if ($research_result) {
    $total_research = $research_result->fetch_assoc()['count'];
}

// ========== GROWTH RATE CALCULATIONS ==========
// Calculate previous period for comparison
$prev_start = date('Y-m-d', strtotime($start_date . " -{$days_diff} days"));
$prev_end = $start_date;

$prev_new_members = $conn->query("SELECT COUNT(*) as count FROM registrations WHERE created_at BETWEEN '{$prev_start} 00:00:00' AND '{$prev_end} 23:59:59'")->fetch_assoc()['count'];
$member_growth_rate = $prev_new_members > 0 ? (($new_members_count - $prev_new_members) / $prev_new_members) * 100 : 0;

$prev_activities = $conn->query("SELECT COUNT(*) as count FROM member_activities WHERE created_at BETWEEN '{$prev_start} 00:00:00' AND '{$prev_end} 23:59:59'")->fetch_assoc()['count'];
$activity_growth_rate = $prev_activities > 0 ? (($activities_in_range - $prev_activities) / $prev_activities) * 100 : 0;
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
                                <h4 class="page-title">Analytics Dashboard</h4>
                                <div class="page-title-right">
                                    <form method="GET" class="d-inline-flex gap-2">
                                        <select name="range" class="form-select form-select-sm" onchange="this.form.submit()">
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
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Key Metrics Cards -->
                    <div class="row">
                        <!-- Total Members -->
                        <div class="col-lg-3 col-md-6">
                            <div class="card widget-flat text-bg-primary">
                                <div class="card-body">
                                    <div class="float-end">
                                        <i class="ri-team-line widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0">Total Members</h6>
                                    <h2 class="my-2"><?php echo number_format($total_members); ?></h2>
                                    <p class="mb-0">
                                        <span class="badge bg-light text-dark">Active: <?php echo number_format($active_members); ?></span>
                                        <span class="badge bg-light text-dark ms-1">Pending: <?php echo number_format($pending_members); ?></span>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- New Members (Date Range) -->
                        <div class="col-lg-3 col-md-6">
                            <div class="card widget-flat text-bg-success">
                                <div class="card-body">
                                    <div class="float-end">
                                        <i class="ri-user-add-line widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0">New Members</h6>
                                    <h2 class="my-2"><?php echo number_format($new_members_count); ?></h2>
                                    <p class="mb-0">
                                        <?php if ($member_growth_rate > 0): ?>
                                            <span class="text-success"><i class="ri-arrow-up-line"></i> <?php echo number_format($member_growth_rate, 1); ?>%</span>
                                        <?php elseif ($member_growth_rate < 0): ?>
                                            <span class="text-danger"><i class="ri-arrow-down-line"></i> <?php echo number_format(abs($member_growth_rate), 1); ?>%</span>
                                        <?php else: ?>
                                            <span class="text-muted">No change</span>
                                        <?php endif; ?>
                                        <span class="text-muted ms-1">vs previous period</span>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Total Resources -->
                        <div class="col-lg-3 col-md-6">
                            <div class="card widget-flat text-bg-info">
                                <div class="card-body">
                                    <div class="float-end">
                                        <i class="ri-file-paper-line widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0">Total Resources</h6>
                                    <h2 class="my-2"><?php echo number_format($total_resources); ?></h2>
                                    <p class="mb-0">
                                        <span class="text-muted">Downloads: <?php echo number_format($total_downloads); ?></span>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- User Engagement -->
                        <div class="col-lg-3 col-md-6">
                            <div class="card widget-flat text-bg-warning">
                                <div class="card-body">
                                    <div class="float-end">
                                        <i class="ri-pulse-line widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0">User Activities</h6>
                                    <h2 class="my-2"><?php echo number_format($activities_in_range); ?></h2>
                                    <p class="mb-0">
                                        <?php if ($activity_growth_rate > 0): ?>
                                            <span class="text-success"><i class="ri-arrow-up-line"></i> <?php echo number_format($activity_growth_rate, 1); ?>%</span>
                                        <?php elseif ($activity_growth_rate < 0): ?>
                                            <span class="text-danger"><i class="ri-arrow-down-line"></i> <?php echo number_format(abs($activity_growth_rate), 1); ?>%</span>
                                        <?php else: ?>
                                            <span class="text-muted">No change</span>
                                        <?php endif; ?>
                                        <span class="text-muted ms-1">vs previous period</span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Charts Row -->
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

                    <!-- Additional Stats -->
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
            </div>

            <?php include 'footer.php'; ?>
        </div>
    </div>

    <!-- Vendor js -->
    <script src="assets/js/vendor.min.js"></script>
    <script src="assets/vendor/apexcharts/apexcharts.min.js"></script>
    <script src="assets/js/app.min.js"></script>

    <script>
        $(document).ready(function() {
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

            // Events Chart
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
            var resourceSections = <?php echo json_encode(array_column($resource_sections, 'section')); ?>;
            var resourceDownloads = <?php echo json_encode(array_column($resource_sections, 'total_downloads')); ?>;
            
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

            // Activity Types Chart
            var activityTypes = <?php echo json_encode(array_column($activity_types, 'activity_type')); ?>;
            var activityCounts = <?php echo json_encode(array_column($activity_types, 'count')); ?>;
            
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
        });
    </script>
</body>
</html>

