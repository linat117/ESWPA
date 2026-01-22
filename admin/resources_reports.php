<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: auth-login.php");
    exit();
}

include 'include/conn.php';
include 'header.php';

// Handle export requests
if (isset($_GET['export']) && $_GET['export'] == 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=resources_report_' . date('Y-m-d') . '.csv');
    
    $output = fopen('php://output', 'w');
    
    // CSV headers
    fputcsv($output, ['ID', 'Title', 'Section', 'Author', 'Status', 'Access Level', 'Downloads', 'Featured', 'Created Date']);
    
    // Fetch all resources
    $query = "SELECT id, title, section, author, status, access_level, download_count, featured, created_at 
              FROM resources 
              ORDER BY created_at DESC";
    $result = $conn->query($query);
    
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, [
            $row['id'],
            $row['title'],
            $row['section'],
            $row['author'],
            $row['status'],
            $row['access_level'],
            $row['download_count'],
            $row['featured'] ? 'Yes' : 'No',
            $row['created_at']
        ]);
    }
    
    fclose($output);
    exit();
}

// Get date range filter
$date_range = $_GET['range'] ?? '30'; // Default: last 30 days
$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime("-{$date_range} days"));
$end_date = $_GET['end_date'] ?? date('Y-m-d');

// Sanitize dates
$start_date = date('Y-m-d', strtotime($start_date));
$end_date = date('Y-m-d', strtotime($end_date));

// Validate dates
if (!$start_date || !$end_date || $start_date === '1970-01-01' || $end_date === '1970-01-01') {
    $start_date = date('Y-m-d', strtotime('-30 days'));
    $end_date = date('Y-m-d');
}

$start_datetime = $start_date . ' 00:00:00';
$end_datetime = $end_date . ' 23:59:59';

// Calculate date range label
$date1 = new DateTime($start_date);
$date2 = new DateTime($end_date);
$days_diff = $date1->diff($date2)->days;
$date_range_label = $days_diff == 0 ? 'Today' : ($days_diff == 1 ? 'Yesterday' : "Last {$days_diff} days");

// Fetch comprehensive statistics
// Total resources
$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM resources");
$stmt->execute();
$result = $stmt->get_result();
$total_resources = $result->fetch_assoc()['total'] ?? 0;
$stmt->close();

// Resources created in date range
$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM resources WHERE created_at BETWEEN ? AND ?");
$stmt->bind_param("ss", $start_datetime, $end_datetime);
$stmt->execute();
$result = $stmt->get_result();
$resources_in_range = $result->fetch_assoc()['total'] ?? 0;
$stmt->close();

// Total downloads
$stmt = $conn->prepare("SELECT SUM(download_count) AS total FROM resources");
$stmt->execute();
$result = $stmt->get_result();
$total_downloads = $result->fetch_assoc()['total'] ?? 0;
$stmt->close();

// Downloads in range (resources created in range)
$stmt = $conn->prepare("SELECT SUM(download_count) AS total FROM resources WHERE created_at BETWEEN ? AND ?");
$stmt->bind_param("ss", $start_datetime, $end_datetime);
$stmt->execute();
$result = $stmt->get_result();
$downloads_in_range = $result->fetch_assoc()['total'] ?? 0;
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
$stmt = $conn->prepare("SELECT section, COUNT(*) AS count, SUM(download_count) AS downloads FROM resources WHERE section IS NOT NULL AND section != '' GROUP BY section ORDER BY count DESC LIMIT 15");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $section_stats[] = $row;
}
$stmt->close();

// Top downloaded resources
$top_downloaded = [];
$stmt = $conn->prepare("SELECT id, title, section, download_count, access_level, status FROM resources ORDER BY download_count DESC LIMIT 20");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $top_downloaded[] = $row;
}
$stmt->close();

// Featured resources
$featured_count = 0;
$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM resources WHERE featured = 1");
$stmt->execute();
$result = $stmt->get_result();
$featured_count = $result->fetch_assoc()['total'] ?? 0;
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
        $monthly_data[$row['month']] = $row['count'];
    }
}

// Resources by author
$author_stats = [];
$stmt = $conn->prepare("SELECT author, COUNT(*) AS count, SUM(download_count) AS downloads FROM resources WHERE author IS NOT NULL AND author != '' GROUP BY author ORDER BY count DESC LIMIT 15");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $author_stats[] = $row;
}
$stmt->close();

// Recent resources
$recent_resources = [];
$stmt = $conn->prepare("SELECT id, title, section, author, status, access_level, download_count, created_at FROM resources ORDER BY created_at DESC LIMIT 50");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $recent_resources[] = $row;
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
                                <h4 class="page-title">Resource Reports</h4>
                                <div>
                                    <a href="resources_dashboard.php" class="btn btn-secondary me-2">
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
                                                <option value="custom" <?php echo $date_range == 'custom' ? 'selected' : ''; ?>>Custom Range</option>
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
                                        <i class="ri-file-paper-line widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0">Total Resources</h6>
                                    <h2 class="my-2"><?php echo number_format($total_resources); ?></h2>
                                    <p class="mb-0">
                                        <span class="text-white-50"><?php echo number_format($resources_in_range); ?> in range</span>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
                            <div class="card widget-flat text-bg-success">
                                <div class="card-body">
                                    <div class="float-end">
                                        <i class="ri-download-line widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0">Total Downloads</h6>
                                    <h2 class="my-2"><?php echo number_format($total_downloads); ?></h2>
                                    <p class="mb-0">
                                        <span class="text-white-50"><?php echo number_format($downloads_in_range); ?> in range</span>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
                            <div class="card widget-flat text-bg-warning">
                                <div class="card-body">
                                    <div class="float-end">
                                        <i class="ri-star-line widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0">Featured Resources</h6>
                                    <h2 class="my-2"><?php echo number_format($featured_count); ?></h2>
                                    <p class="mb-0">
                                        <span class="text-white-50"><?php echo $total_resources > 0 ? number_format(($featured_count / $total_resources) * 100, 1) : 0; ?>% of total</span>
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
                                    <h6 class="text-uppercase mt-0">Date Range</h6>
                                    <h2 class="my-2" style="font-size: 1.2rem;"><?php echo $date_range_label; ?></h2>
                                    <p class="mb-0">
                                        <span class="text-white-50"><?php echo date('M d', strtotime($start_date)); ?> - <?php echo date('M d, Y', strtotime($end_date)); ?></span>
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
                                    <h4 class="header-title">Resource Creation Trend (12 Months)</h4>
                                </div>
                                <div class="card-body">
                                    <div id="creation-trend-chart" style="min-height: 350px;"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Resources by Status</h4>
                                </div>
                                <div class="card-body">
                                    <div id="status-distribution-chart" style="min-height: 350px;"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-lg-6">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Resources by Access Level</h4>
                                </div>
                                <div class="card-body">
                                    <div id="access-distribution-chart" style="min-height: 350px;"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Top Sections by Resource Count</h4>
                                </div>
                                <div class="card-body">
                                    <div id="sections-chart" style="min-height: 350px;"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Top Downloaded Resources -->
                    <div class="row mb-3">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Top Downloaded Resources</h4>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-hover mb-0" id="topDownloadedTable">
                                            <thead>
                                                <tr>
                                                    <th>Rank</th>
                                                    <th>Title</th>
                                                    <th>Section</th>
                                                    <th>Downloads</th>
                                                    <th>Access Level</th>
                                                    <th>Status</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($top_downloaded as $index => $resource): ?>
                                                    <tr>
                                                        <td>
                                                            <span class="badge bg-primary">#<?php echo $index + 1; ?></span>
                                                        </td>
                                                        <td>
                                                            <a href="resource_details.php?id=<?php echo $resource['id']; ?>" class="text-dark">
                                                                <?php echo htmlspecialchars(substr($resource['title'], 0, 60)); ?>
                                                                <?php echo strlen($resource['title']) > 60 ? '...' : ''; ?>
                                                            </a>
                                                        </td>
                                                        <td><?php echo htmlspecialchars($resource['section']); ?></td>
                                                        <td>
                                                            <strong><?php echo number_format($resource['download_count']); ?></strong>
                                                        </td>
                                                        <td>
                                                            <span class="badge bg-info"><?php echo ucfirst($resource['access_level']); ?></span>
                                                        </td>
                                                        <td>
                                                            <span class="badge bg-<?php echo $resource['status'] == 'active' ? 'success' : 'warning'; ?>">
                                                                <?php echo ucfirst($resource['status']); ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <a href="resource_details.php?id=<?php echo $resource['id']; ?>" class="btn btn-sm btn-light">
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

                    <!-- Resources by Section -->
                    <div class="row mb-3">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Resources by Section</h4>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-hover mb-0" id="sectionsTable">
                                            <thead>
                                                <tr>
                                                    <th>Section</th>
                                                    <th>Resource Count</th>
                                                    <th>Total Downloads</th>
                                                    <th>Avg Downloads</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($section_stats as $section): ?>
                                                    <tr>
                                                        <td><strong><?php echo htmlspecialchars($section['section']); ?></strong></td>
                                                        <td>
                                                            <span class="badge bg-primary"><?php echo number_format($section['count']); ?></span>
                                                        </td>
                                                        <td>
                                                            <span class="badge bg-success"><?php echo number_format($section['downloads']); ?></span>
                                                        </td>
                                                        <td>
                                                            <?php echo number_format($section['downloads'] / max($section['count'], 1), 1); ?>
                                                        </td>
                                                        <td>
                                                            <a href="resources_list.php?section=<?php echo urlencode($section['section']); ?>" class="btn btn-sm btn-light">
                                                                <i class="ri-eye-line"></i> View Resources
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

                    <!-- Resources by Author -->
                    <?php if (!empty($author_stats)): ?>
                    <div class="row mb-3">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Top Authors</h4>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-hover mb-0" id="authorsTable">
                                            <thead>
                                                <tr>
                                                    <th>Author</th>
                                                    <th>Resources</th>
                                                    <th>Total Downloads</th>
                                                    <th>Avg Downloads</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($author_stats as $author): ?>
                                                    <tr>
                                                        <td><strong><?php echo htmlspecialchars($author['author']); ?></strong></td>
                                                        <td>
                                                            <span class="badge bg-primary"><?php echo number_format($author['count']); ?></span>
                                                        </td>
                                                        <td>
                                                            <span class="badge bg-success"><?php echo number_format($author['downloads']); ?></span>
                                                        </td>
                                                        <td>
                                                            <?php echo number_format($author['downloads'] / max($author['count'], 1), 1); ?>
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
                    <?php endif; ?>

                    <!-- Recent Resources -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Recent Resources</h4>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-hover mb-0" id="recentResourcesTable">
                                            <thead>
                                                <tr>
                                                    <th>Title</th>
                                                    <th>Section</th>
                                                    <th>Author</th>
                                                    <th>Status</th>
                                                    <th>Access</th>
                                                    <th>Downloads</th>
                                                    <th>Created</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($recent_resources as $resource): ?>
                                                    <tr>
                                                        <td>
                                                            <a href="resource_details.php?id=<?php echo $resource['id']; ?>" class="text-dark">
                                                                <?php echo htmlspecialchars(substr($resource['title'], 0, 50)); ?>
                                                                <?php echo strlen($resource['title']) > 50 ? '...' : ''; ?>
                                                            </a>
                                                        </td>
                                                        <td><?php echo htmlspecialchars($resource['section']); ?></td>
                                                        <td><?php echo htmlspecialchars($resource['author']); ?></td>
                                                        <td>
                                                            <span class="badge bg-<?php echo $resource['status'] == 'active' ? 'success' : 'warning'; ?>">
                                                                <?php echo ucfirst($resource['status']); ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <span class="badge bg-info"><?php echo ucfirst($resource['access_level']); ?></span>
                                                        </td>
                                                        <td><?php echo number_format($resource['download_count']); ?></td>
                                                        <td><?php echo date('M d, Y', strtotime($resource['created_at'])); ?></td>
                                                        <td>
                                                            <a href="resource_details.php?id=<?php echo $resource['id']; ?>" class="btn btn-sm btn-light">
                                                                <i class="ri-eye-line"></i>
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
            // Initialize DataTables
            $('#topDownloadedTable, #sectionsTable, #authorsTable, #recentResourcesTable').DataTable({
                "pageLength": 25,
                "order": [[3, "desc"]], // Sort by downloads/count
                "language": {
                    "search": "Search:",
                    "lengthMenu": "Show _MENU_ entries per page",
                    "info": "Showing _START_ to _END_ of _TOTAL_ entries"
                }
            });
        });

        // Creation Trend Chart
        var creationTrendOptions = {
            chart: {
                type: 'line',
                height: 350,
                toolbar: { show: false }
            },
            series: [{
                name: 'Resources Created',
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
        new ApexCharts(document.querySelector("#creation-trend-chart"), creationTrendOptions).render();

        // Status Distribution Chart
        var statusData = <?php echo json_encode(array_values($status_stats)); ?>;
        var statusLabels = <?php echo json_encode(array_keys($status_stats)); ?>;
        var statusOptions = {
            chart: {
                type: 'pie',
                height: 350
            },
            series: statusData,
            labels: statusLabels,
            colors: ['#10b981', '#f59e0b', '#6b7280'],
            legend: {
                position: 'bottom'
            }
        };
        new ApexCharts(document.querySelector("#status-distribution-chart"), statusOptions).render();

        // Access Level Distribution Chart
        var accessData = <?php echo json_encode(array_values($access_stats)); ?>;
        var accessLabels = <?php echo json_encode(array_keys($access_stats)); ?>;
        var accessOptions = {
            chart: {
                type: 'donut',
                height: 350
            },
            series: accessData,
            labels: accessLabels,
            colors: ['#3e60d5', '#10b981', '#f59e0b', '#ef4444'],
            legend: {
                position: 'bottom'
            }
        };
        new ApexCharts(document.querySelector("#access-distribution-chart"), accessOptions).render();

        // Sections Chart
        var sectionsData = <?php echo json_encode(array_column($section_stats, 'count')); ?>;
        var sectionsLabels = <?php echo json_encode(array_column($section_stats, 'section')); ?>;
        var sectionsOptions = {
            chart: {
                type: 'bar',
                height: 350,
                toolbar: { show: false }
            },
            series: [{
                name: 'Resources',
                data: sectionsData
            }],
            xaxis: {
                categories: sectionsLabels
            },
            colors: ['#10b981'],
            plotOptions: {
                bar: {
                    horizontal: true,
                    borderRadius: 4
                }
            }
        };
        new ApexCharts(document.querySelector("#sections-chart"), sectionsOptions).render();
    </script>

</body>
</html>

