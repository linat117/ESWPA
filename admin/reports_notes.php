<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: auth-login.php");
    exit();
}

include 'include/conn.php';
include 'header.php';

// Check if notes tables exist
$member_notes_exists = false;
$research_notes_exists = false;
$research_comments_exists = false;

$result = $conn->query("SHOW TABLES LIKE 'member_admin_notes'");
if ($result && $result->num_rows > 0) {
    $member_notes_exists = true;
}
$result = $conn->query("SHOW TABLES LIKE 'research_notes'");
if ($result && $result->num_rows > 0) {
    $research_notes_exists = true;
}
$result = $conn->query("SHOW TABLES LIKE 'research_comments'");
if ($result && $result->num_rows > 0) {
    $research_comments_exists = true;
}

// Handle CSV export
if (isset($_GET['export']) && $_GET['export'] == 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=notes_reports_' . date('Y-m-d') . '.csv');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Type', 'ID', 'Member/Admin', 'Title/Subject', 'Important', 'Created Date']);
    
    $start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
    $end_date = $_GET['end_date'] ?? date('Y-m-d');
    $start_datetime = $start_date . ' 00:00:00';
    $end_datetime = $end_date . ' 23:59:59';
    
    if ($member_notes_exists) {
        $stmt = @$conn->prepare("SELECT 'Admin Note' as type, man.id, r.fullname as member_name, LEFT(man.note, 100) as title, man.is_important, man.created_at
                                 FROM member_admin_notes man
                                 LEFT JOIN registrations r ON man.member_id = r.id
                                 WHERE man.created_at BETWEEN ? AND ?
                                 ORDER BY man.created_at DESC");
        if ($stmt) {
            $stmt->bind_param("ss", $start_datetime, $end_datetime);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    fputcsv($output, [
                        $row['type'],
                        $row['id'],
                        $row['member_name'] ?? 'N/A',
                        $row['title'],
                        !empty($row['is_important']) ? 'Yes' : 'No',
                        $row['created_at']
                    ]);
                }
            }
            $stmt->close();
        }
    }
    if ($research_notes_exists) {
        $stmt = @$conn->prepare("SELECT 'Research Note' as type, rn.id, r.fullname as member_name, rn.title, 0 as is_important, rn.created_at
                                 FROM research_notes rn
                                 LEFT JOIN registrations r ON rn.member_id = r.id
                                 WHERE rn.created_at BETWEEN ? AND ?
                                 ORDER BY rn.created_at DESC");
        if ($stmt) {
            $stmt->bind_param("ss", $start_datetime, $end_datetime);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    fputcsv($output, [
                        $row['type'],
                        $row['id'],
                        $row['member_name'] ?? 'N/A',
                        $row['title'],
                        'No',
                        $row['created_at']
                    ]);
                }
            }
            $stmt->close();
        }
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

// Member admin notes statistics (user table uses 'username', not 'user_name')
$notes_by_admin = [];
$stats['member_notes'] = ['total' => 0, 'important' => 0];
if ($member_notes_exists) {
    $stmt = @$conn->prepare("SELECT COUNT(*) as total, SUM(CASE WHEN is_important = 1 THEN 1 ELSE 0 END) as important 
                             FROM member_admin_notes 
                             WHERE created_at BETWEEN ? AND ?");
    if ($stmt) {
        $stmt->bind_param("ss", $start_datetime, $end_datetime);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result) {
            $row = $result->fetch_assoc();
            if ($row) {
                $stats['member_notes'] = ['total' => (int)($row['total'] ?? 0), 'important' => (int)($row['important'] ?? 0)];
            }
        }
        $stmt->close();
    }

    $stmt = @$conn->prepare("SELECT u.username as user_name, COUNT(*) as count 
                             FROM member_admin_notes man
                             LEFT JOIN user u ON man.admin_id = u.id
                             WHERE man.created_at BETWEEN ? AND ?
                             GROUP BY man.admin_id, u.username
                             ORDER BY count DESC
                             LIMIT 10");
    if ($stmt) {
        $stmt->bind_param("ss", $start_datetime, $end_datetime);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $notes_by_admin[] = $row;
            }
        }
        $stmt->close();
    }
}

// Research notes statistics
$notes_by_research = [];
$stats['research_notes'] = ['total' => 0, 'unique_authors' => 0];
if ($research_notes_exists) {
    $stmt = @$conn->prepare("SELECT COUNT(*) as total, COUNT(DISTINCT member_id) as unique_authors 
                             FROM research_notes 
                             WHERE created_at BETWEEN ? AND ?");
    if ($stmt) {
        $stmt->bind_param("ss", $start_datetime, $end_datetime);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result) {
            $row = $result->fetch_assoc();
            if ($row) {
                $stats['research_notes'] = ['total' => (int)($row['total'] ?? 0), 'unique_authors' => (int)($row['unique_authors'] ?? 0)];
            }
        }
        $stmt->close();
    }

    $stmt = @$conn->prepare("SELECT rp.title as research_title, COUNT(*) as count 
                             FROM research_notes rn
                             LEFT JOIN research_projects rp ON rn.research_id = rp.id
                             WHERE rn.created_at BETWEEN ? AND ? AND rn.research_id IS NOT NULL
                             GROUP BY rn.research_id, rp.title
                             ORDER BY count DESC
                             LIMIT 10");
    if ($stmt) {
        $stmt->bind_param("ss", $start_datetime, $end_datetime);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $notes_by_research[] = $row;
            }
        }
        $stmt->close();
    }
}

// Research comments statistics
$stats['research_comments'] = ['total' => 0, 'unique_authors' => 0];
if ($research_comments_exists) {
    $stmt = @$conn->prepare("SELECT COUNT(*) as total, COUNT(DISTINCT member_id) as unique_authors 
                             FROM research_comments 
                             WHERE created_at BETWEEN ? AND ?");
    if ($stmt) {
        $stmt->bind_param("ss", $start_datetime, $end_datetime);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result) {
            $row = $result->fetch_assoc();
            if ($row) {
                $stats['research_comments'] = ['total' => (int)($row['total'] ?? 0), 'unique_authors' => (int)($row['unique_authors'] ?? 0)];
            }
        }
        $stmt->close();
    }
}

// Monthly trend (member admin notes only)
$monthly_trend = [];
$months = [];
for ($i = 11; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $months[] = date('M Y', strtotime("-$i months"));
    $monthly_trend[$month] = 0;
}

if ($member_notes_exists) {
    $monthly_result = @$conn->query("SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(id) as count 
                                     FROM member_admin_notes 
                                     WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH) 
                                     GROUP BY month ORDER BY month ASC");
    if ($monthly_result) {
        while ($row = $monthly_result->fetch_assoc()) {
            if (isset($monthly_trend[$row['month']])) {
                $monthly_trend[$row['month']] = (int)$row['count'];
            }
        }
    }
}

// Recent notes (all types)
$recent_notes = [];

// Member admin notes
if ($member_notes_exists) {
    $stmt = @$conn->prepare("SELECT 'Admin Note' as note_type, man.id, r.fullname as member_name, LEFT(man.note, 100) as title, man.is_important, man.created_at, NULL as research_title
                             FROM member_admin_notes man
                             LEFT JOIN registrations r ON man.member_id = r.id
                             WHERE man.created_at BETWEEN ? AND ?
                             ORDER BY man.created_at DESC
                             LIMIT 50");
    if ($stmt) {
        $stmt->bind_param("ss", $start_datetime, $end_datetime);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $recent_notes[] = $row;
            }
        }
        $stmt->close();
    }
}

// Research notes
if ($research_notes_exists) {
    $stmt = @$conn->prepare("SELECT 'Research Note' as note_type, rn.id, r.fullname as member_name, rn.title, 0 as is_important, rn.created_at, rp.title as research_title
                             FROM research_notes rn
                             LEFT JOIN registrations r ON rn.member_id = r.id
                             LEFT JOIN research_projects rp ON rn.research_id = rp.id
                             WHERE rn.created_at BETWEEN ? AND ?
                             ORDER BY rn.created_at DESC
                             LIMIT 50");
    if ($stmt) {
        $stmt->bind_param("ss", $start_datetime, $end_datetime);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $recent_notes[] = $row;
            }
        }
        $stmt->close();
    }
}

// Sort by date
usort($recent_notes, function($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});
$recent_notes = array_slice($recent_notes, 0, 100);
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
                                <h4 class="page-title">Notes Reports</h4>
                                <div>
                                    <a href="reports_dashboard.php" class="btn btn-secondary me-2">
                                        <i class="ri-dashboard-line"></i> Reports Dashboard
                                    </a>
                                    <?php if ($member_notes_exists || $research_notes_exists): ?>
                                        <a href="?export=csv&start_date=<?php echo urlencode($start_date); ?>&end_date=<?php echo urlencode($end_date); ?>" class="btn btn-success">
                                            <i class="ri-download-line"></i> Export CSV
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if (!$member_notes_exists && !$research_notes_exists && !$research_comments_exists): ?>
                        <!-- Missing Tables Alert -->
                        <div class="row">
                            <div class="col-12">
                                <div class="alert alert-warning">
                                    <h5 class="alert-heading"><i class="ri-alert-line"></i> Notes Tables Not Found</h5>
                                    <p>The notes tables (<code>member_admin_notes</code>, <code>research_notes</code>, <code>research_comments</code>) do not exist in the database.</p>
                                    <hr>
                                    <p class="mb-0">You can find migration SQL files in the <code>Sql/</code> directory to create these tables.</p>
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
                                            <a href="reports_notes.php" class="btn btn-secondary">Reset</a>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Statistics Cards -->
                    <div class="row g-3 mb-4">
                        <?php if ($member_notes_exists): ?>
                        <div class="col-xl-3 col-md-6">
                            <div class="card widget-flat text-bg-primary">
                                <div class="card-body">
                                    <div class="float-end">
                                        <i class="ri-sticky-note-line widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0">Admin Notes</h6>
                                    <h2 class="my-2"><?php echo number_format($stats['member_notes']['total']); ?></h2>
                                    <p class="mb-0">
                                        <span class="text-white-50"><?php echo number_format($stats['member_notes']['important']); ?> important</span>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        <?php if ($research_notes_exists): ?>
                        <div class="col-xl-3 col-md-6">
                            <div class="card widget-flat text-bg-success">
                                <div class="card-body">
                                    <div class="float-end">
                                        <i class="ri-file-text-line widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0">Research Notes</h6>
                                    <h2 class="my-2"><?php echo number_format($stats['research_notes']['total']); ?></h2>
                                    <p class="mb-0">
                                        <span class="text-white-50"><?php echo number_format($stats['research_notes']['unique_authors']); ?> authors</span>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        <?php if ($research_comments_exists): ?>
                        <div class="col-xl-3 col-md-6">
                            <div class="card widget-flat text-bg-info">
                                <div class="card-body">
                                    <div class="float-end">
                                        <i class="ri-message-3-line widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0">Research Comments</h6>
                                    <h2 class="my-2"><?php echo number_format($stats['research_comments']['total']); ?></h2>
                                    <p class="mb-0">
                                        <span class="text-white-50"><?php echo number_format($stats['research_comments']['unique_authors']); ?> authors</span>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        <div class="col-xl-3 col-md-6">
                            <div class="card widget-flat text-bg-warning">
                                <div class="card-body">
                                    <div class="float-end">
                                        <i class="ri-time-line widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0">Date Range</h6>
                                    <h2 class="my-2"><?php echo date('M d', strtotime($start_date)); ?></h2>
                                    <p class="mb-0">
                                        <span class="text-white-50">to <?php echo date('M d, Y', strtotime($end_date)); ?></span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if ($member_notes_exists && !empty($monthly_trend)): ?>
                    <!-- Charts Row -->
                    <div class="row mb-4">
                        <div class="col-xl-6">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Monthly Notes Trend (Admin Notes)</h4>
                                </div>
                                <div class="card-body">
                                    <div id="monthly-trend-chart" style="height: 300px;"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-6">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Notes by Admin</h4>
                                </div>
                                <div class="card-body">
                                    <?php if (!empty($notes_by_admin)): ?>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-hover mb-0">
                                                <thead>
                                                    <tr>
                                                        <th>Admin</th>
                                                        <th class="text-end">Count</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($notes_by_admin as $item): ?>
                                                        <tr>
                                                            <td><?php echo htmlspecialchars($item['user_name'] ?? 'Unknown'); ?></td>
                                                            <td class="text-end"><?php echo number_format($item['count']); ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php else: ?>
                                        <p class="text-muted mb-0">No admin data available.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Breakdown Cards -->
                    <?php if ($research_notes_exists && !empty($notes_by_research)): ?>
                    <div class="row mb-4">
                        <div class="col-xl-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Research Notes by Project</h4>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-sm table-hover mb-0">
                                            <thead>
                                                <tr>
                                                    <th>Research Project</th>
                                                    <th class="text-end">Note Count</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($notes_by_research as $item): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($item['research_title'] ?? 'Uncategorized'); ?></td>
                                                        <td class="text-end"><?php echo number_format($item['count']); ?></td>
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

                    <!-- Recent Notes Table -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Recent Notes</h4>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped table-bordered dt-responsive nowrap datatable" style="width:100%">
                                            <thead>
                                                <tr>
                                                    <th>Type</th>
                                                    <th>ID</th>
                                                    <th>Member/Author</th>
                                                    <th>Title/Subject</th>
                                                    <th>Research Project</th>
                                                    <th>Important</th>
                                                    <th>Created Date</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($recent_notes as $note): ?>
                                                    <tr>
                                                        <td>
                                                            <span class="badge bg-<?php echo $note['note_type'] == 'Admin Note' ? 'primary' : 'success'; ?>">
                                                                <?php echo htmlspecialchars($note['note_type']); ?>
                                                            </span>
                                                        </td>
                                                        <td><?php echo $note['id']; ?></td>
                                                        <td><?php echo htmlspecialchars($note['member_name'] ?? 'N/A'); ?></td>
                                                        <td><?php echo htmlspecialchars($note['title']); ?></td>
                                                        <td><?php echo htmlspecialchars($note['research_title'] ?? '-'); ?></td>
                                                        <td>
                                                            <?php if ($note['is_important']): ?>
                                                                <span class="badge bg-danger">Important</span>
                                                            <?php else: ?>
                                                                <span class="badge bg-secondary">-</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td><?php echo date('M d, Y H:i', strtotime($note['created_at'])); ?></td>
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

    <?php
    $has_notes_content = $member_notes_exists || $research_notes_exists || $research_comments_exists;
    $has_charts = $member_notes_exists && !empty($monthly_trend);
    ?>
    <?php if ($has_notes_content): ?>
    <script>
        $(document).ready(function() {
            if ($.fn.DataTable && $('.datatable').length) {
                $('.datatable').DataTable({
                    responsive: true,
                    order: [[6, 'desc']],
                    pageLength: 25
                });
            }
            <?php if ($has_charts): ?>
            var monthlyOptions = {
                series: [{
                    name: 'Notes',
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
                    title: { text: 'Number of Notes' }
                },
                tooltip: {
                    y: { formatter: function(val) { return val + ' notes'; } }
                }
            };
            var el = document.querySelector("#monthly-trend-chart");
            if (el && typeof ApexCharts !== 'undefined') {
                var monthlyChart = new ApexCharts(el, monthlyOptions);
                monthlyChart.render();
            }
            <?php endif; ?>
        });
    </script>
    <?php endif; ?>
</body>
</html>

