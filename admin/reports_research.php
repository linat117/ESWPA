<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: auth-login.php");
    exit();
}

include 'include/conn.php';
include 'header.php';

// Check if research tables exist
$research_table_exists = false;
$collaborators_table_exists = false;
$result = $conn->query("SHOW TABLES LIKE 'research_projects'");
if ($result && $result->num_rows > 0) {
    $research_table_exists = true;
}
$result = $conn->query("SHOW TABLES LIKE 'research_collaborators'");
if ($result && $result->num_rows > 0) {
    $collaborators_table_exists = true;
}

// Handle CSV export
if (isset($_GET['export']) && $_GET['export'] == 'csv' && $research_table_exists) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=research_reports_' . date('Y-m-d') . '.csv');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'Title', 'Status', 'Research Type', 'Category', 'Created By', 'Start Date', 'End Date', 'Publication Date', 'Created Date']);
    
    $start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
    $end_date = $_GET['end_date'] ?? date('Y-m-d');
    $start_datetime = $start_date . ' 00:00:00';
    $end_datetime = $end_date . ' 23:59:59';
    
    $stmt = $conn->prepare("SELECT rp.id, rp.title, rp.status, rp.research_type, rp.category, rp.created_by, rp.start_date, rp.end_date, rp.publication_date, rp.created_at,
                           r.fullname as creator_name
                           FROM research_projects rp
                           LEFT JOIN registrations r ON rp.created_by = r.id
                           WHERE rp.created_at BETWEEN ? AND ?
                           ORDER BY rp.created_at DESC");
    $stmt->bind_param("ss", $start_datetime, $end_datetime);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, [
            $row['id'],
            $row['title'],
            $row['status'],
            $row['research_type'] ?? '',
            $row['category'] ?? '',
            $row['creator_name'] ?? 'N/A',
            $row['start_date'] ?? '',
            $row['end_date'] ?? '',
            $row['publication_date'] ?? '',
            $row['created_at']
        ]);
    }
    
    fclose($output);
    exit();
}

// Get date range filter
$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
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

// Statistics
$stats = [];

if ($research_table_exists) {
    // Total research projects
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM research_projects WHERE created_at BETWEEN ? AND ?");
    $stmt->bind_param("ss", $start_datetime, $end_datetime);
    $stmt->execute();
    $result = $stmt->get_result();
    $stats['total_projects'] = $result->fetch_assoc()['total'] ?? 0;
    $stmt->close();

    // Status breakdown
    $status_breakdown = [];
    $stmt = $conn->prepare("SELECT status, COUNT(*) as count FROM research_projects WHERE created_at BETWEEN ? AND ? GROUP BY status ORDER BY count DESC");
    $stmt->bind_param("ss", $start_datetime, $end_datetime);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $status_breakdown[] = $row;
    }
    $stmt->close();

    // Research type breakdown
    $research_type_breakdown = [];
    $stmt = $conn->prepare("SELECT research_type, COUNT(*) as count FROM research_projects WHERE created_at BETWEEN ? AND ? AND research_type IS NOT NULL GROUP BY research_type ORDER BY count DESC");
    $stmt->bind_param("ss", $start_datetime, $end_datetime);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $research_type_breakdown[] = $row;
    }
    $stmt->close();

    // Top categories
    $category_breakdown = [];
    $stmt = $conn->prepare("SELECT category, COUNT(*) as count FROM research_projects WHERE created_at BETWEEN ? AND ? AND category IS NOT NULL AND category != '' GROUP BY category ORDER BY count DESC LIMIT 10");
    $stmt->bind_param("ss", $start_datetime, $end_datetime);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $category_breakdown[] = $row;
    }
    $stmt->close();

    // Total collaborators (if table exists)
    if ($collaborators_table_exists) {
        $stmt = $conn->prepare("SELECT COUNT(DISTINCT member_id) as total FROM research_collaborators rc 
                               INNER JOIN research_projects rp ON rc.research_id = rp.id 
                               WHERE rp.created_at BETWEEN ? AND ?");
        $stmt->bind_param("ss", $start_datetime, $end_datetime);
        $stmt->execute();
        $result = $stmt->get_result();
        $stats['total_collaborators'] = $result->fetch_assoc()['total'] ?? 0;
        $stmt->close();
    } else {
        $stats['total_collaborators'] = 0;
    }

    // Monthly trend (last 12 months)
    $monthly_trend = [];
    $months = [];
    for ($i = 11; $i >= 0; $i--) {
        $month = date('Y-m', strtotime("-$i months"));
        $months[] = date('M Y', strtotime("-$i months"));
        $monthly_trend[$month] = 0;
    }

    $monthly_query = "SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(id) as count 
                      FROM research_projects 
                      WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH) 
                      GROUP BY month ORDER BY month ASC";
    $monthly_result = $conn->query($monthly_query);
    while ($row = $monthly_result->fetch_assoc()) {
        if (isset($monthly_trend[$row['month']])) {
            $monthly_trend[$row['month']] = $row['count'];
        }
    }

    // Recent research projects
    $recent_projects = [];
    $stmt = $conn->prepare("SELECT rp.id, rp.title, rp.status, rp.research_type, rp.category, rp.created_by, rp.start_date, rp.end_date, rp.publication_date, rp.created_at,
                           r.fullname as creator_name,
                           (SELECT COUNT(*) FROM research_collaborators WHERE research_id = rp.id) as collaborator_count
                           FROM research_projects rp
                           LEFT JOIN registrations r ON rp.created_by = r.id
                           WHERE rp.created_at BETWEEN ? AND ?
                           ORDER BY rp.created_at DESC
                           LIMIT 100");
    $stmt->bind_param("ss", $start_datetime, $end_datetime);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $recent_projects[] = $row;
    }
    $stmt->close();
} else {
    $stats['total_projects'] = 0;
    $stats['total_collaborators'] = 0;
    $status_breakdown = [];
    $research_type_breakdown = [];
    $category_breakdown = [];
    $monthly_trend = [];
    $months = [];
    $recent_projects = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<!-- ApexCharts css -->
<link href="assets/vendor/apexcharts/apexcharts.css" rel="stylesheet" type="text/css" />
<!-- Datatables css -->
<link href="assets/vendor/datatables.net-bs5/css/dataTables.bootstrap5.min.css" rel="stylesheet" type="text/css" />
<link href="assets/vendor/datatables.net-responsive-bs5/css/responsive.bootstrap5.min.css" rel="stylesheet" type="text/css" />

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
                                <h4 class="page-title">Research Reports</h4>
                                <div>
                                    <a href="reports_dashboard.php" class="btn btn-secondary me-2">
                                        <i class="ri-dashboard-line"></i> Reports Dashboard
                                    </a>
                                    <?php if ($research_table_exists): ?>
                                        <a href="?export=csv&start_date=<?php echo urlencode($start_date); ?>&end_date=<?php echo urlencode($end_date); ?>" class="btn btn-success">
                                            <i class="ri-download-line"></i> Export CSV
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if (!$research_table_exists): ?>
                        <!-- Missing Table Alert -->
                        <div class="row">
                            <div class="col-12">
                                <div class="alert alert-warning">
                                    <h5 class="alert-heading"><i class="ri-alert-line"></i> Research Tables Not Found</h5>
                                    <p>The <code>research_projects</code> table does not exist in the database. Please create it first to use this reports page.</p>
                                    <hr>
                                    <p class="mb-0">You can find the migration SQL file at: <code>Sql/migration_research_tables.sql</code></p>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>

                    <!-- Date Range Filter -->
                    <div class="row mb-3">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <form method="GET" class="row gy-2 gx-2 align-items-end">
                                        <div class="col-auto">
                                            <label class="form-label">Start Date</label>
                                            <input type="date" class="form-control" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>">
                                        </div>
                                        <div class="col-auto">
                                            <label class="form-label">End Date</label>
                                            <input type="date" class="form-control" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>">
                                        </div>
                                        <div class="col-auto">
                                            <button type="submit" class="btn btn-primary">Apply Filter</button>
                                            <a href="reports_research.php" class="btn btn-secondary">Reset</a>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Statistics Cards -->
                    <div class="row g-3 mb-4">
                        <div class="col-xl-3 col-md-6">
                            <div class="card widget-flat text-bg-primary">
                                <div class="card-body">
                                    <div class="float-end">
                                        <i class="ri-search-line widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0">Total Projects</h6>
                                    <h2 class="my-2"><?php echo number_format($stats['total_projects']); ?></h2>
                                    <p class="mb-0">
                                        <span class="text-white-50">In selected period</span>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6">
                            <div class="card widget-flat text-bg-success">
                                <div class="card-body">
                                    <div class="float-end">
                                        <i class="ri-team-line widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0">Total Collaborators</h6>
                                    <h2 class="my-2"><?php echo number_format($stats['total_collaborators']); ?></h2>
                                    <p class="mb-0">
                                        <span class="text-white-50">Unique members</span>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6">
                            <div class="card widget-flat text-bg-info">
                                <div class="card-body">
                                    <div class="float-end">
                                        <i class="ri-file-paper-line widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0">Research Types</h6>
                                    <h2 class="my-2"><?php echo count($research_type_breakdown); ?></h2>
                                    <p class="mb-0">
                                        <span class="text-white-50">Different types</span>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6">
                            <div class="card widget-flat text-bg-warning">
                                <div class="card-body">
                                    <div class="float-end">
                                        <i class="ri-folder-line widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0">Categories</h6>
                                    <h2 class="my-2"><?php echo count($category_breakdown); ?></h2>
                                    <p class="mb-0">
                                        <span class="text-white-50">Top categories</span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Charts Row -->
                    <div class="row mb-4">
                        <div class="col-xl-6">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Monthly Research Trend</h4>
                                </div>
                                <div class="card-body">
                                    <div id="monthly-trend-chart" style="height: 300px;"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-6">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Status Distribution</h4>
                                </div>
                                <div class="card-body">
                                    <div id="status-chart" style="height: 300px;"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Breakdown Cards -->
                    <div class="row mb-4">
                        <div class="col-xl-4">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Status Breakdown</h4>
                                </div>
                                <div class="card-body">
                                    <?php if (!empty($status_breakdown)): ?>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-hover mb-0">
                                                <thead>
                                                    <tr>
                                                        <th>Status</th>
                                                        <th class="text-end">Count</th>
                                                        <th class="text-end">Percentage</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($status_breakdown as $item): ?>
                                                        <tr>
                                                            <td><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $item['status']))); ?></td>
                                                            <td class="text-end"><?php echo number_format($item['count']); ?></td>
                                                            <td class="text-end"><?php echo $stats['total_projects'] > 0 ? number_format(($item['count'] / $stats['total_projects']) * 100, 1) : 0; ?>%</td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php else: ?>
                                        <p class="text-muted mb-0">No status data available.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-4">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Research Type Breakdown</h4>
                                </div>
                                <div class="card-body">
                                    <?php if (!empty($research_type_breakdown)): ?>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-hover mb-0">
                                                <thead>
                                                    <tr>
                                                        <th>Type</th>
                                                        <th class="text-end">Count</th>
                                                        <th class="text-end">Percentage</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($research_type_breakdown as $item): ?>
                                                        <tr>
                                                            <td><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $item['research_type']))); ?></td>
                                                            <td class="text-end"><?php echo number_format($item['count']); ?></td>
                                                            <td class="text-end"><?php echo $stats['total_projects'] > 0 ? number_format(($item['count'] / $stats['total_projects']) * 100, 1) : 0; ?>%</td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php else: ?>
                                        <p class="text-muted mb-0">No research type data available.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-4">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Top Categories</h4>
                                </div>
                                <div class="card-body">
                                    <?php if (!empty($category_breakdown)): ?>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-hover mb-0">
                                                <thead>
                                                    <tr>
                                                        <th>Category</th>
                                                        <th class="text-end">Count</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($category_breakdown as $item): ?>
                                                        <tr>
                                                            <td><?php echo htmlspecialchars($item['category']); ?></td>
                                                            <td class="text-end"><?php echo number_format($item['count']); ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php else: ?>
                                        <p class="text-muted mb-0">No category data available.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Projects Table -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Recent Research Projects</h4>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped table-bordered dt-responsive nowrap datatable" style="width:100%">
                                            <thead>
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Title</th>
                                                    <th>Status</th>
                                                    <th>Research Type</th>
                                                    <th>Category</th>
                                                    <th>Created By</th>
                                                    <th>Collaborators</th>
                                                    <th>Created Date</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($recent_projects as $project): ?>
                                                    <tr>
                                                        <td><?php echo $project['id']; ?></td>
                                                        <td><?php echo htmlspecialchars($project['title']); ?></td>
                                                        <td>
                                                            <?php
                                                            $status_colors = [
                                                                'draft' => 'secondary',
                                                                'in_progress' => 'info',
                                                                'completed' => 'success',
                                                                'published' => 'primary',
                                                                'archived' => 'dark'
                                                            ];
                                                            $status_color = $status_colors[$project['status']] ?? 'secondary';
                                                            ?>
                                                            <span class="badge bg-<?php echo $status_color; ?>"><?php echo ucfirst(str_replace('_', ' ', $project['status'])); ?></span>
                                                        </td>
                                                        <td><?php echo $project['research_type'] ? htmlspecialchars(ucfirst(str_replace('_', ' ', $project['research_type']))) : '-'; ?></td>
                                                        <td><?php echo $project['category'] ? htmlspecialchars($project['category']) : '-'; ?></td>
                                                        <td><?php echo htmlspecialchars($project['creator_name'] ?? 'N/A'); ?></td>
                                                        <td>
                                                            <span class="badge bg-info"><?php echo $project['collaborator_count']; ?></span>
                                                        </td>
                                                        <td><?php echo date('M d, Y', strtotime($project['created_at'])); ?></td>
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
    <script src="assets/vendor/datatables.net/js/jquery.dataTables.min.js"></script>
    <script src="assets/vendor/datatables.net-bs5/js/dataTables.bootstrap5.min.js"></script>
    <script src="assets/vendor/datatables.net-responsive/js/dataTables.responsive.min.js"></script>
    <script src="assets/vendor/datatables.net-responsive-bs5/js/responsive.bootstrap5.min.js"></script>
    <!-- App js -->
    <script src="assets/js/app.min.js"></script>

    <?php if ($research_table_exists && !empty($monthly_trend)): ?>
    <script>
        $(document).ready(function() {
            // Initialize DataTable
            $('.datatable').DataTable({
                responsive: true,
                order: [[7, 'desc']],
                pageLength: 25
            });

            // Monthly Trend Chart
            var monthlyOptions = {
                series: [{
                    name: 'Projects',
                    data: [<?php echo implode(',', array_values($monthly_trend)); ?>]
                }],
                chart: {
                    type: 'line',
                    height: 300,
                    toolbar: { show: false }
                },
                colors: ['#727cf5'],
                stroke: {
                    width: 3,
                    curve: 'smooth'
                },
                xaxis: {
                    categories: [<?php echo "'" . implode("','", $months) . "'"; ?>],
                    labels: { rotate: -45 }
                },
                yaxis: {
                    title: { text: 'Number of Projects' }
                },
                tooltip: {
                    y: { formatter: function(val) { return val + ' projects'; } }
                }
            };
            var monthlyChart = new ApexCharts(document.querySelector("#monthly-trend-chart"), monthlyOptions);
            monthlyChart.render();

            // Status Distribution Chart
            var statusData = <?php echo json_encode(array_column($status_breakdown, 'count')); ?>;
            var statusLabels = <?php echo json_encode(array_map(function($s) { return ucfirst(str_replace('_', ' ', $s['status'])); }, $status_breakdown)); ?>;
            
            var statusOptions = {
                series: statusData,
                chart: {
                    type: 'donut',
                    height: 300
                },
                labels: statusLabels,
                colors: ['#727cf5', '#0acf97', '#fa5c7c', '#ffbc00', '#39afd1'],
                legend: {
                    position: 'bottom'
                }
            };
            var statusChart = new ApexCharts(document.querySelector("#status-chart"), statusOptions);
            statusChart.render();
        });
    </script>
    <?php endif; ?>
</body>
</html>

