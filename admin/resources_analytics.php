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

// Fetch resource statistics
// Total resources
$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM resources");
$stmt->execute();
$result = $stmt->get_result();
$total_resources = $result->fetch_assoc()['total'] ?? 0;
$stmt->close();

// Resources created in date range
$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM resources WHERE created_at BETWEEN ? AND ?");
$start_datetime = $start_date . ' 00:00:00';
$end_datetime = $end_date . ' 23:59:59';
$stmt->bind_param("ss", $start_datetime, $end_datetime);
$stmt->execute();
$result = $stmt->get_result();
$resources_in_range = $result->fetch_assoc()['total'] ?? 0;
$stmt->close();

// Resources by status
$status_stats = [];
$stmt = $conn->prepare("SELECT status, COUNT(*) AS count FROM resources GROUP BY status");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $status_stats[$row['status']] = $row['count'];
}
$stmt->close();

// Resources by access level
$access_stats = [];
$stmt = $conn->prepare("SELECT access_level, COUNT(*) AS count FROM resources GROUP BY access_level");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $access_stats[$row['access_level']] = $row['count'];
}
$stmt->close();

// Resources by section
$section_stats = [];
$stmt = $conn->prepare("SELECT section, COUNT(*) AS count FROM resources GROUP BY section ORDER BY count DESC LIMIT 10");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $section_stats[$row['section']] = $row['count'];
}
$stmt->close();

// Total downloads
$stmt = $conn->prepare("SELECT SUM(download_count) AS total FROM resources");
$stmt->execute();
$result = $stmt->get_result();
$total_downloads = $result->fetch_assoc()['total'] ?? 0;
$stmt->close();

// Downloads in date range (approximate - using created_at as proxy)
$stmt = $conn->prepare("SELECT SUM(download_count) AS total FROM resources WHERE created_at BETWEEN ? AND ?");
$stmt->bind_param("ss", $start_datetime, $end_datetime);
$stmt->execute();
$result = $stmt->get_result();
$downloads_in_range = $result->fetch_assoc()['total'] ?? 0;
$stmt->close();

// Monthly resource creation trend
$monthly_data = [];
$months = [];
for ($i = 11; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $months[] = date('M Y', strtotime("-$i months"));
    $monthly_data[$month] = 0;
}
$monthly_query = "SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(id) as count 
                  FROM resources 
                  WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH) 
                  GROUP BY month ORDER BY month ASC";
$monthly_result = $conn->query($monthly_query);
while ($row = $monthly_result->fetch_assoc()) {
    if (isset($monthly_data[$row['month']])) {
        $monthly_data[$row['month']] = (int)$row['count'];
    }
}
$monthly_values = array_values($monthly_data);

// Monthly download trend (approximate)
$download_monthly_data = [];
for ($i = 11; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $download_monthly_data[$month] = 0;
}
// Note: This is approximate since we don't have download date tracking
// In a real system, you'd have a downloads table with timestamps

// Featured resources
$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM resources WHERE featured = 1");
$stmt->execute();
$result = $stmt->get_result();
$featured_resources = $result->fetch_assoc()['total'] ?? 0;
$stmt->close();

// Top downloaded resources
$stmt = $conn->prepare("SELECT id, title, section, download_count FROM resources ORDER BY download_count DESC LIMIT 10");
$stmt->execute();
$top_downloaded = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Top sections by downloads
$stmt = $conn->prepare("SELECT section, SUM(download_count) as total_downloads FROM resources GROUP BY section ORDER BY total_downloads DESC LIMIT 10");
$stmt->execute();
$top_sections = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Calculate growth rate
$prev_start = date('Y-m-d', strtotime($start_date . " -{$days_diff} days"));
$prev_end = $start_date;
$prev_start_datetime = $prev_start . ' 00:00:00';
$prev_end_datetime = $prev_end . ' 23:59:59';

$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM resources WHERE created_at BETWEEN ? AND ?");
$stmt->bind_param("ss", $prev_start_datetime, $prev_end_datetime);
$stmt->execute();
$result = $stmt->get_result();
$prev_resources = $result->fetch_assoc()['total'] ?? 0;
$stmt->close();

$growth_rate = 0;
if ($prev_resources > 0) {
    $growth_rate = round((($resources_in_range - $prev_resources) / $prev_resources) * 100, 2);
} elseif ($resources_in_range > 0) {
    $growth_rate = 100;
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
                                <h4 class="page-title">Resources Analytics</h4>
                                <div>
                                    <a href="resources_dashboard.php" class="btn btn-secondary me-2">
                                        <i class="ri-dashboard-line"></i> Dashboard
                                    </a>
                                    <a href="resources_list.php" class="btn btn-secondary">
                                        <i class="ri-list-check"></i> All Resources
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
                                    <form method="GET" class="row g-3 align-items-end">
                                        <div class="col-md-3">
                                            <label class="form-label">Quick Range</label>
                                            <select name="range" class="form-select" onchange="this.form.submit()">
                                                <option value="7" <?php echo $date_range == '7' ? 'selected' : ''; ?>>Last 7 days</option>
                                                <option value="30" <?php echo $date_range == '30' ? 'selected' : ''; ?>>Last 30 days</option>
                                                <option value="90" <?php echo $date_range == '90' ? 'selected' : ''; ?>>Last 90 days</option>
                                                <option value="365" <?php echo $date_range == '365' ? 'selected' : ''; ?>>Last year</option>
                                                <option value="custom" <?php echo $date_range == 'custom' ? 'selected' : ''; ?>>Custom</option>
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
                                        <div class="col-md-3">
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
                    <div class="row g-3">
                        <!-- Total Resources -->
                        <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
                            <div class="card widget-flat text-bg-dark">
                                <div class="card-body">
                                    <div class="float-end">
                                        <i class="ri-file-paper-line widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0">Total Resources</h6>
                                    <h2 class="my-2"><?php echo number_format($total_resources); ?></h2>
                                    <span class="text-white-50 small">All resources</span>
                                </div>
                            </div>
                        </div>

                        <!-- Resources in Range -->
                        <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
                            <div class="card widget-flat text-bg-info">
                                <div class="card-body">
                                    <div class="float-end">
                                        <i class="ri-calendar-line widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0">In Date Range</h6>
                                    <h2 class="my-2"><?php echo number_format($resources_in_range); ?></h2>
                                    <span class="text-white-50 small">
                                        <?php if ($growth_rate > 0): ?>
                                            <i class="ri-arrow-up-line"></i> +<?php echo $growth_rate; ?>%
                                        <?php elseif ($growth_rate < 0): ?>
                                            <i class="ri-arrow-down-line"></i> <?php echo $growth_rate; ?>%
                                        <?php else: ?>
                                            No change
                                        <?php endif; ?>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Total Downloads -->
                        <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
                            <div class="card widget-flat text-bg-primary">
                                <div class="card-body">
                                    <div class="float-end">
                                        <i class="ri-download-line widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0">Total Downloads</h6>
                                    <h2 class="my-2"><?php echo number_format($total_downloads); ?></h2>
                                    <span class="text-white-50 small">All time</span>
                                </div>
                            </div>
                        </div>

                        <!-- Featured Resources -->
                        <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
                            <div class="card widget-flat" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                                <div class="card-body">
                                    <div class="float-end">
                                        <i class="ri-star-line widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0 text-white">Featured</h6>
                                    <h2 class="my-2 text-white"><?php echo number_format($featured_resources); ?></h2>
                                    <span class="text-white-50 small">Highlighted resources</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Charts Row -->
                    <div class="row mt-3">
                        <!-- Monthly Trend -->
                        <div class="col-lg-8">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Monthly Resource Creation Trend</h4>
                                </div>
                                <div class="card-body">
                                    <div id="monthly-trend-chart" class="apex-charts" data-colors="#3e60d5"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Status Distribution -->
                        <div class="col-lg-4">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Status Distribution</h4>
                                </div>
                                <div class="card-body">
                                    <div id="status-chart" class="apex-charts" data-colors="#47ad77,#ffbc00,#6c757d"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Access Level and Section Charts -->
                    <div class="row mt-3">
                        <!-- Access Level -->
                        <div class="col-lg-6">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Access Level Distribution</h4>
                                </div>
                                <div class="card-body">
                                    <div id="access-chart" class="apex-charts" data-colors="#667eea,#764ba2,#f093fb,#f5576c"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Top Sections -->
                        <div class="col-lg-6">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Top Sections by Downloads</h4>
                                </div>
                                <div class="card-body">
                                    <div id="section-downloads-chart" class="apex-charts" data-colors="#4facfe"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Top Downloaded Resources -->
                    <div class="row mt-3">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h4 class="header-title">Top Downloaded Resources</h4>
                                    <a href="resources_list.php?sort=downloads" class="btn btn-sm btn-light">See More</a>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-hover mb-0">
                                            <thead>
                                                <tr>
                                                    <th>Rank</th>
                                                    <th>Title</th>
                                                    <th>Section</th>
                                                    <th>Downloads</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (empty($top_downloaded)): ?>
                                                    <tr>
                                                        <td colspan="5" class="text-center text-muted">No downloads yet</td>
                                                    </tr>
                                                <?php else: ?>
                                                    <?php $rank = 1; foreach ($top_downloaded as $resource): ?>
                                                        <tr>
                                                            <td>
                                                                <span class="badge bg-<?php echo $rank <= 3 ? 'primary' : 'secondary'; ?>">
                                                                    #<?php echo $rank++; ?>
                                                                </span>
                                                            </td>
                                                            <td>
                                                                <a href="edit_resource.php?id=<?php echo $resource['id']; ?>" class="text-dark">
                                                                    <?php echo htmlspecialchars(substr($resource['title'], 0, 50)); ?>
                                                                    <?php echo strlen($resource['title']) > 50 ? '...' : ''; ?>
                                                                </a>
                                                            </td>
                                                            <td><?php echo htmlspecialchars($resource['section']); ?></td>
                                                            <td>
                                                                <span class="badge bg-primary"><?php echo number_format($resource['download_count']); ?></span>
                                                            </td>
                                                            <td>
                                                                <a href="edit_resource.php?id=<?php echo $resource['id']; ?>" class="btn btn-sm btn-light">
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

                </div>
            </div>

            <?php include 'footer.php'; ?>
        </div>
    </div>

    <!-- Vendor js -->
    <script src="assets/js/vendor.min.js"></script>
    
    <!-- Apex Charts js -->
    <script src="assets/vendor/apexcharts/apexcharts.min.js"></script>
    
    <!-- App js -->
    <script src="assets/js/app.min.js"></script>

    <script>
        $(document).ready(function() {
            // Monthly Trend Line Chart
            var monthlyOptions = {
                chart: {
                    type: 'line',
                    height: 350,
                    toolbar: { show: false }
                },
                series: [{
                    name: 'Resources Created',
                    data: <?php echo json_encode($monthly_values); ?>
                }],
                xaxis: {
                    categories: <?php echo json_encode($months); ?>
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
            new ApexCharts(document.querySelector("#monthly-trend-chart"), monthlyOptions).render();

            // Status Distribution Pie Chart
            var statusData = <?php echo json_encode($status_stats); ?>;
            var statusLabels = Object.keys(statusData).map(key => key.charAt(0).toUpperCase() + key.slice(1));
            
            var statusOptions = {
                chart: {
                    type: 'pie',
                    height: 300,
                },
                series: Object.values(statusData),
                labels: statusLabels,
                colors: ['#47ad77', '#ffbc00', '#6c757d'],
                legend: {
                    show: true,
                    position: 'bottom',
                },
                dataLabels: {
                    enabled: true,
                }
            };
            new ApexCharts(document.querySelector("#status-chart"), statusOptions).render();

            // Access Level Distribution
            var accessData = <?php echo json_encode($access_stats); ?>;
            
            if (Object.keys(accessData).length > 0) {
                var accessOptions = {
                    chart: {
                        type: 'donut',
                        height: 320,
                    },
                    series: Object.values(accessData),
                    labels: Object.keys(accessData).map(key => key.charAt(0).toUpperCase() + key.slice(1)),
                    colors: ['#667eea', '#764ba2', '#f093fb', '#f5576c'],
                    legend: {
                        show: true,
                        position: 'bottom',
                    },
                    dataLabels: {
                        enabled: true,
                    }
                };
                new ApexCharts(document.querySelector("#access-chart"), accessOptions).render();
            } else {
                document.querySelector("#access-chart").innerHTML = '<div class="text-center text-muted p-4">No access level data available</div>';
            }

            // Top Sections by Downloads Bar Chart
            var sectionData = <?php echo json_encode(array_column($top_sections, 'total_downloads', 'section')); ?>;
            
            if (Object.keys(sectionData).length > 0) {
                var sectionOptions = {
                    chart: {
                        type: 'bar',
                        height: 320,
                        toolbar: { show: false }
                    },
                    series: [{
                        name: 'Downloads',
                        data: Object.values(sectionData)
                    }],
                    xaxis: {
                        categories: Object.keys(sectionData)
                    },
                    colors: ['#4facfe'],
                    plotOptions: {
                        bar: {
                            horizontal: false,
                            columnWidth: '55%',
                        }
                    }
                };
                new ApexCharts(document.querySelector("#section-downloads-chart"), sectionOptions).render();
            } else {
                document.querySelector("#section-downloads-chart").innerHTML = '<div class="text-center text-muted p-4">No section data available</div>';
            }
        });
    </script>

</body>
</html>

