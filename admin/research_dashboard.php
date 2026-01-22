<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: auth-login.php");
    exit();
}

include 'include/conn.php';
include 'header.php';

// Check if research tables exist
$tableCheck = $conn->query("SHOW TABLES LIKE 'research_projects'");
$tables_exist = $tableCheck->num_rows > 0;

if (!$tables_exist) {
    $error_message = "Research tables have not been created yet. Please run the migration: Sql/migration_research_tables.sql";
} else {
    // Fetch research statistics
    // Total research projects
    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM research_projects");
    $stmt->execute();
    $result = $stmt->get_result();
    $total_research = $result->fetch_assoc()['total'] ?? 0;
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

    // Total collaborators
    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM research_collaborators");
    $stmt->execute();
    $result = $stmt->get_result();
    $total_collaborators = $result->fetch_assoc()['total'] ?? 0;
    $stmt->close();

    // Total research files
    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM research_files");
    $stmt->execute();
    $result = $stmt->get_result();
    $total_files = $result->fetch_assoc()['total'] ?? 0;
    $stmt->close();

    // Recent research (last 7 days)
    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM research_projects WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
    $stmt->execute();
    $result = $stmt->get_result();
    $recent_research = $result->fetch_assoc()['total'] ?? 0;
    $stmt->close();

    // Published research
    $published_research = $status_stats['published'] ?? 0;

    // In progress research
    $in_progress_research = $status_stats['in_progress'] ?? 0;

    // Draft research
    $draft_research = $status_stats['draft'] ?? 0;

    // Completed research
    $completed_research = $status_stats['completed'] ?? 0;

    // Latest research projects
    $stmt = $conn->prepare("SELECT rp.*, r.fullname as creator_name 
                           FROM research_projects rp 
                           LEFT JOIN registrations r ON rp.created_by = r.id 
                           ORDER BY rp.created_at DESC 
                           LIMIT 5");
    $stmt->execute();
    $latest_research = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Research with most collaborators
    $stmt = $conn->prepare("SELECT rp.id, rp.title, COUNT(rc.id) as collab_count 
                           FROM research_projects rp 
                           LEFT JOIN research_collaborators rc ON rp.id = rc.research_id 
                           GROUP BY rp.id 
                           ORDER BY collab_count DESC 
                           LIMIT 5");
    $stmt->execute();
    $top_collaborations = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
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
                                <h4 class="page-title">Research Dashboard</h4>
                                <div>
                                    <a href="research_list.php" class="btn btn-secondary me-2">
                                        <i class="ri-list-check"></i> All Research
                                    </a>
                                    <a href="add_research.php" class="btn btn-primary">
                                        <i class="ri-add-circle-line"></i> Add Research
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

                    <!-- Summary Cards -->
                    <div class="row g-3">
                        <!-- Total Research Projects -->
                        <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
                            <div class="card widget-flat" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                                <div class="card-body">
                                    <div class="float-end">
                                        <i class="ri-search-line widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0 text-white">Total Research</h6>
                                    <h2 class="my-2 text-white"><?php echo number_format($total_research); ?></h2>
                                    <a href="research_list.php" class="text-white-50 small">View all <i class="ri-arrow-right-s-line"></i></a>
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
                                    <h2 class="my-2"><?php echo number_format($published_research); ?></h2>
                                    <span class="text-white-50 small">Publicly available</span>
                                </div>
                            </div>
                        </div>

                        <!-- In Progress -->
                        <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
                            <div class="card widget-flat text-bg-info">
                                <div class="card-body">
                                    <div class="float-end">
                                        <i class="ri-time-line widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0">In Progress</h6>
                                    <h2 class="my-2"><?php echo number_format($in_progress_research); ?></h2>
                                    <span class="text-white-50 small">Active projects</span>
                                </div>
                            </div>
                        </div>

                        <!-- Draft -->
                        <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
                            <div class="card widget-flat text-bg-warning">
                                <div class="card-body">
                                    <div class="float-end">
                                        <i class="ri-file-edit-line widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0">Draft</h6>
                                    <h2 class="my-2"><?php echo number_format($draft_research); ?></h2>
                                    <span class="text-white-50 small">Under development</span>
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
                                    <a href="research_collaborators.php" class="text-white-50 small">Manage <i class="ri-arrow-right-s-line"></i></a>
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

                        <!-- Recent Research -->
                        <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
                            <div class="card widget-flat" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                                <div class="card-body">
                                    <div class="float-end">
                                        <i class="ri-add-circle-line widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0 text-white">Recent (7 Days)</h6>
                                    <h2 class="my-2 text-white"><?php echo number_format($recent_research); ?></h2>
                                    <span class="text-white-50 small">New projects</span>
                                </div>
                            </div>
                        </div>

                        <!-- Completed -->
                        <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
                            <div class="card widget-flat text-bg-dark">
                                <div class="card-body">
                                    <div class="float-end">
                                        <i class="ri-check-double-line widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0">Completed</h6>
                                    <h2 class="my-2"><?php echo number_format($completed_research); ?></h2>
                                    <span class="text-white-50 small">Finished projects</span>
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
                                    <h4 class="header-title">Research Status Distribution</h4>
                                </div>
                                <div class="card-body">
                                    <div id="status-chart" class="apex-charts" data-colors="#47ad77,#3e60d5,#ffbc00,#fa5c7c"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Research Type Distribution -->
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
                    </div>

                    <!-- Latest Research and Top Collaborations -->
                    <div class="row mt-3">
                        <!-- Latest Research Projects -->
                        <div class="col-lg-6">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h4 class="header-title">Latest Research Projects</h4>
                                    <a href="research_list.php" class="btn btn-sm btn-light">See More</a>
                                </div>
                                <div class="card-body pt-0">
                                    <div class="table-responsive">
                                        <table class="table table-hover mb-0">
                                            <thead>
                                                <tr>
                                                    <th>Title</th>
                                                    <th>Creator</th>
                                                    <th>Status</th>
                                                    <th>Date</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (empty($latest_research)): ?>
                                                    <tr>
                                                        <td colspan="4" class="text-center text-muted">No research projects found</td>
                                                    </tr>
                                                <?php else: ?>
                                                    <?php foreach ($latest_research as $research): ?>
                                                        <tr>
                                                            <td>
                                                                <a href="research_details.php?id=<?php echo $research['id']; ?>" class="text-dark">
                                                                    <?php echo htmlspecialchars(substr($research['title'], 0, 40)); ?>
                                                                    <?php echo strlen($research['title']) > 40 ? '...' : ''; ?>
                                                                </a>
                                                            </td>
                                                            <td><?php echo htmlspecialchars($research['creator_name'] ?? 'N/A'); ?></td>
                                                            <td>
                                                                <span class="badge bg-<?php 
                                                                    echo $research['status'] == 'published' ? 'success' : 
                                                                        ($research['status'] == 'in_progress' ? 'info' : 
                                                                        ($research['status'] == 'completed' ? 'dark' : 'warning')); 
                                                                ?>">
                                                                    <?php echo ucfirst(str_replace('_', ' ', $research['status'])); ?>
                                                                </span>
                                                            </td>
                                                            <td><?php echo date('M d, Y', strtotime($research['created_at'])); ?></td>
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
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h4 class="header-title">Top Collaborations</h4>
                                    <a href="research_collaborators.php" class="btn btn-sm btn-light">See More</a>
                                </div>
                                <div class="card-body pt-0">
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
            // Status Distribution Pie Chart
            var statusData = <?php echo json_encode([
                'Published' => $published_research,
                'In Progress' => $in_progress_research,
                'Draft' => $draft_research,
                'Completed' => $completed_research
            ]); ?>;
            
            var statusOptions = {
                chart: {
                    type: 'pie',
                    height: 320,
                },
                series: Object.values(statusData),
                labels: Object.keys(statusData),
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

            // Research Type Distribution Chart
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
        });
    </script>
    <?php endif; ?>

</body>
</html>

