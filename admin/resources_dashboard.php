<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: auth-login.php");
    exit();
}

include 'include/conn.php';
include 'header.php';

// Fetch resource statistics
// Total resources
$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM resources");
$stmt->execute();
$result = $stmt->get_result();
$total_resources = $result->fetch_assoc()['total'] ?? 0;
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

// Featured resources
$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM resources WHERE featured = 1");
$stmt->execute();
$result = $stmt->get_result();
$featured_resources = $result->fetch_assoc()['total'] ?? 0;
$stmt->close();

// Active resources
$active_resources = $status_stats['active'] ?? 0;

// Inactive resources
$inactive_resources = $status_stats['inactive'] ?? 0;

// Archived resources
$archived_resources = $status_stats['archived'] ?? 0;

// Recent resources (last 7 days)
$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM resources WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
$stmt->execute();
$result = $stmt->get_result();
$recent_resources = $result->fetch_assoc()['total'] ?? 0;
$stmt->close();

// Top downloaded resources
$stmt = $conn->prepare("SELECT id, title, section, download_count FROM resources ORDER BY download_count DESC LIMIT 5");
$stmt->execute();
$top_downloaded = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Latest resources
$stmt = $conn->prepare("SELECT id, title, section, status, created_at FROM resources ORDER BY created_at DESC LIMIT 5");
$stmt->execute();
$latest_resources = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
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
                                <h4 class="page-title">Resources Dashboard</h4>
                                <div>
                                    <a href="resources_list.php" class="btn btn-secondary me-2">
                                        <i class="ri-list-check"></i> All Resources
                                    </a>
                                    <a href="add_resource.php" class="btn btn-primary">
                                        <i class="ri-add-circle-line"></i> Add Resource
                                    </a>
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
                                    <a href="resources_list.php" class="text-white-50 small">View all <i class="ri-arrow-right-s-line"></i></a>
                                </div>
                            </div>
                        </div>

                        <!-- Active Resources -->
                        <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
                            <div class="card widget-flat text-bg-success">
                                <div class="card-body">
                                    <div class="float-end">
                                        <i class="ri-checkbox-circle-line widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0">Active</h6>
                                    <h2 class="my-2"><?php echo number_format($active_resources); ?></h2>
                                    <span class="text-white-50 small">Available resources</span>
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

                        <!-- Recent Resources -->
                        <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
                            <div class="card widget-flat text-bg-info">
                                <div class="card-body">
                                    <div class="float-end">
                                        <i class="ri-add-circle-line widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0">Recent (7 Days)</h6>
                                    <h2 class="my-2"><?php echo number_format($recent_resources); ?></h2>
                                    <span class="text-white-50 small">New uploads</span>
                                </div>
                            </div>
                        </div>

                        <!-- Inactive Resources -->
                        <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
                            <div class="card widget-flat text-bg-warning">
                                <div class="card-body">
                                    <div class="float-end">
                                        <i class="ri-pause-circle-line widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0">Inactive</h6>
                                    <h2 class="my-2"><?php echo number_format($inactive_resources); ?></h2>
                                    <span class="text-white-50 small">Hidden resources</span>
                                </div>
                            </div>
                        </div>

                        <!-- Archived Resources -->
                        <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
                            <div class="card widget-flat text-bg-secondary">
                                <div class="card-body">
                                    <div class="float-end">
                                        <i class="ri-archive-line widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0">Archived</h6>
                                    <h2 class="my-2"><?php echo number_format($archived_resources); ?></h2>
                                    <span class="text-white-50 small">Archived items</span>
                                </div>
                            </div>
                        </div>

                        <!-- Public Resources -->
                        <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
                            <div class="card widget-flat" style="background: linear-gradient(135deg, #0ea5e9 0%, #2563eb 100%);">
                                <div class="card-body">
                                    <div class="float-end">
                                        <i class="ri-global-line widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0 text-white">Public Access</h6>
                                    <h2 class="my-2 text-white"><?php echo number_format($access_stats['public'] ?? 0); ?></h2>
                                    <span class="text-white-50 small">Publicly available</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Charts and Lists Row -->
                    <div class="row mt-3">
                        <!-- Status Distribution Chart -->
                        <div class="col-lg-6">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Resource Status Distribution</h4>
                                </div>
                                <div class="card-body">
                                    <div id="status-chart" class="apex-charts" data-colors="#47ad77,#ffbc00,#6c757d"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Access Level Distribution -->
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
                    </div>

                    <!-- Top Sections Chart -->
                    <div class="row mt-3">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Resources by Section (Top 10)</h4>
                                </div>
                                <div class="card-body">
                                    <div id="section-chart" class="apex-charts" data-colors="#3e60d5"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Latest Resources and Top Downloaded -->
                    <div class="row mt-3">
                        <!-- Latest Resources -->
                        <div class="col-lg-6">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h4 class="header-title">Latest Resources</h4>
                                    <a href="resources_list.php" class="btn btn-sm btn-light">See More</a>
                                </div>
                                <div class="card-body pt-0">
                                    <div class="table-responsive">
                                        <table class="table table-hover mb-0">
                                            <thead>
                                                <tr>
                                                    <th>Title</th>
                                                    <th>Section</th>
                                                    <th>Status</th>
                                                    <th>Date</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (empty($latest_resources)): ?>
                                                    <tr>
                                                        <td colspan="4" class="text-center text-muted">No resources found</td>
                                                    </tr>
                                                <?php else: ?>
                                                    <?php foreach ($latest_resources as $resource): ?>
                                                        <tr>
                                                            <td>
                                                                <a href="edit_resource.php?id=<?php echo $resource['id']; ?>" class="text-dark">
                                                                    <?php echo htmlspecialchars(substr($resource['title'], 0, 30)); ?>
                                                                    <?php echo strlen($resource['title']) > 30 ? '...' : ''; ?>
                                                                </a>
                                                            </td>
                                                            <td><?php echo htmlspecialchars($resource['section']); ?></td>
                                                            <td>
                                                                <span class="badge bg-<?php 
                                                                    echo $resource['status'] == 'active' ? 'success' : 
                                                                        ($resource['status'] == 'inactive' ? 'warning' : 'secondary'); 
                                                                ?>">
                                                                    <?php echo ucfirst($resource['status']); ?>
                                                                </span>
                                                            </td>
                                                            <td><?php echo date('M d, Y', strtotime($resource['created_at'])); ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Top Downloaded -->
                        <div class="col-lg-6">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h4 class="header-title">Top Downloaded Resources</h4>
                                    <a href="resources_list.php?sort=downloads" class="btn btn-sm btn-light">See More</a>
                                </div>
                                <div class="card-body pt-0">
                                    <div class="table-responsive">
                                        <table class="table table-hover mb-0">
                                            <thead>
                                                <tr>
                                                    <th>Title</th>
                                                    <th>Section</th>
                                                    <th>Downloads</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (empty($top_downloaded)): ?>
                                                    <tr>
                                                        <td colspan="4" class="text-center text-muted">No downloads yet</td>
                                                    </tr>
                                                <?php else: ?>
                                                    <?php foreach ($top_downloaded as $resource): ?>
                                                        <tr>
                                                            <td>
                                                                <a href="edit_resource.php?id=<?php echo $resource['id']; ?>" class="text-dark">
                                                                    <?php echo htmlspecialchars(substr($resource['title'], 0, 30)); ?>
                                                                    <?php echo strlen($resource['title']) > 30 ? '...' : ''; ?>
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
            // Status Distribution Pie Chart
            var statusData = <?php echo json_encode([
                'Active' => $active_resources,
                'Inactive' => $inactive_resources,
                'Archived' => $archived_resources
            ]); ?>;
            
            var statusOptions = {
                chart: {
                    type: 'pie',
                    height: 320,
                },
                series: Object.values(statusData),
                labels: Object.keys(statusData),
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

            // Access Level Distribution Chart
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

            // Section Distribution Bar Chart
            var sectionData = <?php echo json_encode($section_stats); ?>;
            
            if (Object.keys(sectionData).length > 0) {
                var sectionOptions = {
                    chart: {
                        type: 'bar',
                        height: 350,
                        toolbar: { show: false }
                    },
                    series: [{
                        name: 'Resources',
                        data: Object.values(sectionData)
                    }],
                    xaxis: {
                        categories: Object.keys(sectionData)
                    },
                    colors: ['#3e60d5'],
                    plotOptions: {
                        bar: {
                            horizontal: false,
                            columnWidth: '55%',
                        }
                    }
                };
                new ApexCharts(document.querySelector("#section-chart"), sectionOptions).render();
            } else {
                document.querySelector("#section-chart").innerHTML = '<div class="text-center text-muted p-4">No section data available</div>';
            }
        });
    </script>

</body>
</html>

