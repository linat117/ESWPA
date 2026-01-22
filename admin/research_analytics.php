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

// Check if research tables exist
$tableCheck = $conn->query("SHOW TABLES LIKE 'research_projects'");
$tables_exist = $tableCheck->num_rows > 0;

if (!$tables_exist) {
    $error_message = "Research tables have not been created yet.";
} else {
    // Total research projects
    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM research_projects");
    $stmt->execute();
    $result = $stmt->get_result();
    $total_research = $result->fetch_assoc()['total'] ?? 0;
    $stmt->close();

    // Research created in date range
    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM research_projects WHERE created_at BETWEEN ? AND ?");
    $start_datetime = $start_date . ' 00:00:00';
    $end_datetime = $end_date . ' 23:59:59';
    $stmt->bind_param("ss", $start_datetime, $end_datetime);
    $stmt->execute();
    $result = $stmt->get_result();
    $research_in_range = $result->fetch_assoc()['total'] ?? 0;
    $stmt->close();

    // Research by status
    $status_stats = [];
    $stmt = $conn->prepare("SELECT status, COUNT(*) AS count FROM research_projects GROUP BY status");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $status_stats[$row['status']] = $row['count'];
    }
    $stmt->close();

    // Research by type
    $type_stats = [];
    $stmt = $conn->prepare("SELECT research_type, COUNT(*) AS count FROM research_projects WHERE research_type IS NOT NULL GROUP BY research_type");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $type_stats[$row['research_type']] = $row['count'];
    }
    $stmt->close();

    // Research by category
    $category_stats = [];
    $stmt = $conn->prepare("SELECT category, COUNT(*) AS count FROM research_projects WHERE category IS NOT NULL AND category != '' GROUP BY category ORDER BY count DESC LIMIT 10");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $category_stats[$row['category']] = $row['count'];
    }
    $stmt->close();

    // Monthly research creation trend
    $monthly_data = [];
    $months = [];
    for ($i = 11; $i >= 0; $i--) {
        $month = date('Y-m', strtotime("-$i months"));
        $months[] = date('M Y', strtotime("-$i months"));
        $monthly_data[$month] = 0;
    }
    $monthly_query = "SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(id) as count 
                      FROM research_projects 
                      WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH) 
                      GROUP BY month ORDER BY month ASC";
    $monthly_result = $conn->query($monthly_query);
    while ($row = $monthly_result->fetch_assoc()) {
        if (isset($monthly_data[$row['month']])) {
            $monthly_data[$row['month']] = (int)$row['count'];
        }
    }
    $monthly_values = array_values($monthly_data);

    // Total collaborators
    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM research_collaborators");
    $stmt->execute();
    $result = $stmt->get_result();
    $total_collaborators = $result->fetch_assoc()['total'] ?? 0;
    $stmt->close();

    // Average collaborators per project
    $avg_collaborators = 0;
    if ($total_research > 0) {
        $avg_collaborators = round($total_collaborators / $total_research, 2);
    }

    // Total files
    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM research_files");
    $stmt->execute();
    $result = $stmt->get_result();
    $total_files = $result->fetch_assoc()['total'] ?? 0;
    $stmt->close();

    // Most active researchers (by project count)
    $stmt = $conn->prepare("SELECT r.id, r.fullname, r.email, COUNT(rp.id) as project_count 
                           FROM registrations r 
                           LEFT JOIN research_projects rp ON r.id = rp.created_by 
                           GROUP BY r.id 
                           HAVING project_count > 0 
                           ORDER BY project_count DESC 
                           LIMIT 10");
    $stmt->execute();
    $top_researchers = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Research with most collaborators
    $stmt = $conn->prepare("SELECT rp.id, rp.title, COUNT(rc.id) as collab_count 
                           FROM research_projects rp 
                           LEFT JOIN research_collaborators rc ON rp.id = rc.research_id 
                           GROUP BY rp.id 
                           HAVING collab_count > 0
                           ORDER BY collab_count DESC 
                           LIMIT 10");
    $stmt->execute();
    $top_collaborations = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Published research count
    $published_count = $status_stats['published'] ?? 0;

    // Calculate growth rate (compare with previous period)
    $prev_start = date('Y-m-d', strtotime($start_date . " -{$days_diff} days"));
    $prev_end = $start_date;
    $prev_start_datetime = $prev_start . ' 00:00:00';
    $prev_end_datetime = $prev_end . ' 23:59:59';
    
    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM research_projects WHERE created_at BETWEEN ? AND ?");
    $stmt->bind_param("ss", $prev_start_datetime, $prev_end_datetime);
    $stmt->execute();
    $result = $stmt->get_result();
    $prev_research = $result->fetch_assoc()['total'] ?? 0;
    $stmt->close();

    $growth_rate = 0;
    if ($prev_research > 0) {
        $growth_rate = round((($research_in_range - $prev_research) / $prev_research) * 100, 2);
    } elseif ($research_in_range > 0) {
        $growth_rate = 100;
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
                                <h4 class="page-title">Research Analytics</h4>
                                <div>
                                    <a href="research_dashboard.php" class="btn btn-secondary me-2">
                                        <i class="ri-dashboard-line"></i> Dashboard
                                    </a>
                                    <a href="research_list.php" class="btn btn-secondary">
                                        <i class="ri-list-check"></i> All Research
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if (isset($error_message)): ?>
                        <div class="row">
                            <div class="col-12">
                                <div class="alert alert-warning">
                                    <strong>Note:</strong> <?php echo htmlspecialchars($error_message); ?>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>

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
                        <!-- Total Research -->
                        <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
                            <div class="card widget-flat" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                                <div class="card-body">
                                    <div class="float-end">
                                        <i class="ri-search-line widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0 text-white">Total Research</h6>
                                    <h2 class="my-2 text-white"><?php echo number_format($total_research); ?></h2>
                                    <span class="text-white-50 small">All projects</span>
                                </div>
                            </div>
                        </div>

                        <!-- Research in Range -->
                        <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
                            <div class="card widget-flat text-bg-info">
                                <div class="card-body">
                                    <div class="float-end">
                                        <i class="ri-calendar-line widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0">In Date Range</h6>
                                    <h2 class="my-2"><?php echo number_format($research_in_range); ?></h2>
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

                        <!-- Published Research -->
                        <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
                            <div class="card widget-flat text-bg-success">
                                <div class="card-body">
                                    <div class="float-end">
                                        <i class="ri-checkbox-circle-line widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0">Published</h6>
                                    <h2 class="my-2"><?php echo number_format($published_count); ?></h2>
                                    <span class="text-white-50 small">Publicly available</span>
                                </div>
                            </div>
                        </div>

                        <!-- Total Collaborators -->
                        <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
                            <div class="card widget-flat text-bg-primary">
                                <div class="card-body">
                                    <div class="float-end">
                                        <i class="ri-team-line widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0">Collaborators</h6>
                                    <h2 class="my-2"><?php echo number_format($total_collaborators); ?></h2>
                                    <span class="text-white-50 small">Avg: <?php echo $avg_collaborators; ?> per project</span>
                                </div>
                            </div>
                        </div>

                        <!-- Total Files -->
                        <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
                            <div class="card widget-flat text-bg-secondary">
                                <div class="card-body">
                                    <div class="float-end">
                                        <i class="ri-file-line widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0">Research Files</h6>
                                    <h2 class="my-2"><?php echo number_format($total_files); ?></h2>
                                    <span class="text-white-50 small">Uploaded files</span>
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
                                    <h4 class="header-title">Monthly Research Creation Trend</h4>
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
                                    <div id="status-chart" class="apex-charts" data-colors="#47ad77,#3e60d5,#ffbc00,#fa5c7c"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Type and Category Charts -->
                    <div class="row mt-3">
                        <!-- Research Type -->
                        <div class="col-lg-6">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Research Type Distribution</h4>
                                </div>
                                <div class="card-body">
                                    <div id="type-chart" class="apex-charts" data-colors="#667eea,#764ba2,#f093fb,#f5576c"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Research Category -->
                        <div class="col-lg-6">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Top Research Categories</h4>
                                </div>
                                <div class="card-body">
                                    <div id="category-chart" class="apex-charts" data-colors="#4facfe"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Top Researchers and Collaborations -->
                    <div class="row mt-3">
                        <!-- Top Researchers -->
                        <div class="col-lg-6">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Top Researchers</h4>
                                    <p class="text-muted mb-0">By project count</p>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-hover mb-0">
                                            <thead>
                                                <tr>
                                                    <th>Researcher</th>
                                                    <th>Email</th>
                                                    <th>Projects</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (empty($top_researchers)): ?>
                                                    <tr>
                                                        <td colspan="4" class="text-center text-muted">No researchers found</td>
                                                    </tr>
                                                <?php else: ?>
                                                    <?php foreach ($top_researchers as $researcher): ?>
                                                        <tr>
                                                            <td><?php echo htmlspecialchars($researcher['fullname']); ?></td>
                                                            <td><?php echo htmlspecialchars($researcher['email']); ?></td>
                                                            <td>
                                                                <span class="badge bg-primary"><?php echo $researcher['project_count']; ?></span>
                                                            </td>
                                                            <td>
                                                                <a href="members_list.php?search=<?php echo urlencode($researcher['email']); ?>" class="btn btn-sm btn-light">
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

                        <!-- Top Collaborations -->
                        <div class="col-lg-6">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Top Collaborations</h4>
                                    <p class="text-muted mb-0">Projects with most collaborators</p>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-hover mb-0">
                                            <thead>
                                                <tr>
                                                    <th>Research Title</th>
                                                    <th>Collaborators</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (empty($top_collaborations)): ?>
                                                    <tr>
                                                        <td colspan="3" class="text-center text-muted">No collaborations found</td>
                                                    </tr>
                                                <?php else: ?>
                                                    <?php foreach ($top_collaborations as $collab): ?>
                                                        <tr>
                                                            <td>
                                                                <a href="research_details.php?id=<?php echo $collab['id']; ?>" class="text-dark">
                                                                    <?php echo htmlspecialchars(substr($collab['title'], 0, 40)); ?>
                                                                    <?php echo strlen($collab['title']) > 40 ? '...' : ''; ?>
                                                                </a>
                                                            </td>
                                                            <td>
                                                                <span class="badge bg-primary"><?php echo $collab['collab_count']; ?> members</span>
                                                            </td>
                                                            <td>
                                                                <a href="research_details.php?id=<?php echo $collab['id']; ?>" class="btn btn-sm btn-light">
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
    
    <!-- Apex Charts js -->
    <script src="assets/vendor/apexcharts/apexcharts.min.js"></script>
    
    <!-- App js -->
    <script src="assets/js/app.min.js"></script>

    <?php if ($tables_exist): ?>
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
                    name: 'Research Projects',
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
            var statusLabels = Object.keys(statusData).map(key => key.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase()));
            
            var statusOptions = {
                chart: {
                    type: 'pie',
                    height: 300,
                },
                series: Object.values(statusData),
                labels: statusLabels,
                colors: ['#47ad77', '#3e60d5', '#ffbc00', '#fa5c7c'],
                legend: {
                    show: true,
                    position: 'bottom',
                },
                dataLabels: {
                    enabled: true,
                }
            };
            new ApexCharts(document.querySelector("#status-chart"), statusOptions).render();

            // Research Type Distribution
            var typeData = <?php echo json_encode($type_stats); ?>;
            
            if (Object.keys(typeData).length > 0) {
                var typeOptions = {
                    chart: {
                        type: 'donut',
                        height: 320,
                    },
                    series: Object.values(typeData),
                    labels: Object.keys(typeData).map(key => key.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())),
                    colors: ['#667eea', '#764ba2', '#f093fb', '#f5576c', '#4facfe', '#00f2fe'],
                    legend: {
                        show: true,
                        position: 'bottom',
                    },
                    dataLabels: {
                        enabled: true,
                    }
                };
                new ApexCharts(document.querySelector("#type-chart"), typeOptions).render();
            } else {
                document.querySelector("#type-chart").innerHTML = '<div class="text-center text-muted p-4">No research type data available</div>';
            }

            // Category Distribution Bar Chart
            var categoryData = <?php echo json_encode($category_stats); ?>;
            
            if (Object.keys(categoryData).length > 0) {
                var categoryOptions = {
                    chart: {
                        type: 'bar',
                        height: 320,
                        toolbar: { show: false }
                    },
                    series: [{
                        name: 'Research Projects',
                        data: Object.values(categoryData)
                    }],
                    xaxis: {
                        categories: Object.keys(categoryData)
                    },
                    colors: ['#4facfe'],
                    plotOptions: {
                        bar: {
                            horizontal: false,
                            columnWidth: '55%',
                        }
                    }
                };
                new ApexCharts(document.querySelector("#category-chart"), categoryOptions).render();
            } else {
                document.querySelector("#category-chart").innerHTML = '<div class="text-center text-muted p-4">No category data available</div>';
            }
        });
    </script>
    <?php endif; ?>

</body>
</html>

