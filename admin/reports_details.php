<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: auth-login.php");
    exit();
}

include 'include/conn.php';
include 'header.php';

// Comprehensive detailed reports combining data from multiple sources

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

// Comprehensive Statistics
$comprehensive_stats = [];

// Members
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM registrations WHERE created_at BETWEEN ? AND ?");
$stmt->bind_param("ss", $start_datetime, $end_datetime);
$stmt->execute();
$result = $stmt->get_result();
$comprehensive_stats['members'] = $result->fetch_assoc()['total'] ?? 0;
$stmt->close();

// Events
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM events WHERE created_at BETWEEN ? AND ?");
$stmt->bind_param("ss", $start_datetime, $end_datetime);
$stmt->execute();
$result = $stmt->get_result();
$comprehensive_stats['events'] = $result->fetch_assoc()['total'] ?? 0;
$stmt->close();

// Resources
$result = $conn->query("SHOW TABLES LIKE 'resources'");
if ($result && $result->num_rows > 0) {
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM resources WHERE created_at BETWEEN ? AND ?");
    $stmt->bind_param("ss", $start_datetime, $end_datetime);
    $stmt->execute();
    $result = $stmt->get_result();
    $comprehensive_stats['resources'] = $result->fetch_assoc()['total'] ?? 0;
    $stmt->close();
} else {
    $comprehensive_stats['resources'] = 0;
}

// Research Projects
$result = $conn->query("SHOW TABLES LIKE 'research_projects'");
if ($result && $result->num_rows > 0) {
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM research_projects WHERE created_at BETWEEN ? AND ?");
    $stmt->bind_param("ss", $start_datetime, $end_datetime);
    $stmt->execute();
    $result = $stmt->get_result();
    $comprehensive_stats['research'] = $result->fetch_assoc()['total'] ?? 0;
    $stmt->close();
} else {
    $comprehensive_stats['research'] = 0;
}

// Sent Emails
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM sent_emails WHERE sent_at BETWEEN ? AND ?");
$stmt->bind_param("ss", $start_datetime, $end_datetime);
$stmt->execute();
$result = $stmt->get_result();
$comprehensive_stats['emails'] = $result->fetch_assoc()['total'] ?? 0;
$stmt->close();

// Activity Logs (if exists)
$result = $conn->query("SHOW TABLES LIKE 'audit_logs'");
if ($result && $result->num_rows > 0) {
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM audit_logs WHERE created_at BETWEEN ? AND ?");
    $stmt->bind_param("ss", $start_datetime, $end_datetime);
    $stmt->execute();
    $result = $stmt->get_result();
    $comprehensive_stats['activities'] = $result->fetch_assoc()['total'] ?? 0;
    $stmt->close();
} else {
    $comprehensive_stats['activities'] = 0;
}

// Support Tickets (if exists)
$result = $conn->query("SHOW TABLES LIKE 'support_tickets'");
if ($result && $result->num_rows > 0) {
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM support_tickets WHERE created_at BETWEEN ? AND ?");
    $stmt->bind_param("ss", $start_datetime, $end_datetime);
    $stmt->execute();
    $result = $stmt->get_result();
    $comprehensive_stats['support_tickets'] = $result->fetch_assoc()['total'] ?? 0;
    $stmt->close();
} else {
    $comprehensive_stats['support_tickets'] = 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<!-- ApexCharts css -->
<link href="assets/vendor/apexcharts/apexcharts.css" rel="stylesheet" type="text/css" />

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
                                <h4 class="page-title">Detailed Comprehensive Reports</h4>
                                <div>
                                    <a href="reports_dashboard.php" class="btn btn-secondary">
                                        <i class="ri-dashboard-line"></i> Reports Dashboard
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
                                            <a href="reports_details.php" class="btn btn-secondary">Reset</a>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Comprehensive Statistics -->
                    <div class="row g-3 mb-4">
                        <div class="col-xl-3 col-lg-4 col-md-6">
                            <div class="card widget-flat text-bg-primary">
                                <div class="card-body">
                                    <div class="float-end">
                                        <i class="ri-team-line widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0">Members</h6>
                                    <h2 class="my-2"><?php echo number_format($comprehensive_stats['members']); ?></h2>
                                    <p class="mb-0">
                                        <a href="reports_members.php?start_date=<?php echo urlencode($start_date); ?>&end_date=<?php echo urlencode($end_date); ?>" class="text-white-50">View Details <i class="ri-arrow-right-line"></i></a>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-lg-4 col-md-6">
                            <div class="card widget-flat text-bg-info">
                                <div class="card-body">
                                    <div class="float-end">
                                        <i class="ri-calendar-event-line widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0">Events</h6>
                                    <h2 class="my-2"><?php echo number_format($comprehensive_stats['events']); ?></h2>
                                    <p class="mb-0">
                                        <span class="text-white-50">Created</span>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-lg-4 col-md-6">
                            <div class="card widget-flat text-bg-success">
                                <div class="card-body">
                                    <div class="float-end">
                                        <i class="ri-file-paper-2-line widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0">Resources</h6>
                                    <h2 class="my-2"><?php echo number_format($comprehensive_stats['resources']); ?></h2>
                                    <p class="mb-0">
                                        <a href="resources_reports.php?start_date=<?php echo urlencode($start_date); ?>&end_date=<?php echo urlencode($end_date); ?>" class="text-white-50">View Details <i class="ri-arrow-right-line"></i></a>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-lg-4 col-md-6">
                            <div class="card widget-flat text-bg-warning">
                                <div class="card-body">
                                    <div class="float-end">
                                        <i class="ri-search-line widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0">Research</h6>
                                    <h2 class="my-2"><?php echo number_format($comprehensive_stats['research']); ?></h2>
                                    <p class="mb-0">
                                        <a href="reports_research.php?start_date=<?php echo urlencode($start_date); ?>&end_date=<?php echo urlencode($end_date); ?>" class="text-white-50">View Details <i class="ri-arrow-right-line"></i></a>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-lg-4 col-md-6">
                            <div class="card widget-flat text-bg-danger">
                                <div class="card-body">
                                    <div class="float-end">
                                        <i class="ri-mail-line widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0">Emails Sent</h6>
                                    <h2 class="my-2"><?php echo number_format($comprehensive_stats['emails']); ?></h2>
                                    <p class="mb-0">
                                        <span class="text-white-50">In period</span>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-lg-4 col-md-6">
                            <div class="card widget-flat text-bg-secondary">
                                <div class="card-body">
                                    <div class="float-end">
                                        <i class="ri-file-list-3-line widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0">Activities</h6>
                                    <h2 class="my-2"><?php echo number_format($comprehensive_stats['activities']); ?></h2>
                                    <p class="mb-0">
                                        <a href="reports_activity.php?start_date=<?php echo urlencode($start_date); ?>&end_date=<?php echo urlencode($end_date); ?>" class="text-white-50">View Details <i class="ri-arrow-right-line"></i></a>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-lg-4 col-md-6">
                            <div class="card widget-flat" style="background: linear-gradient(135deg, #0ea5e9 0%, #2563eb 100%); color: white;">
                                <div class="card-body">
                                    <div class="float-end">
                                        <i class="ri-customer-service-2-line widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0">Support Tickets</h6>
                                    <h2 class="my-2"><?php echo number_format($comprehensive_stats['support_tickets']); ?></h2>
                                    <p class="mb-0">
                                        <a href="support_reports.php?start_date=<?php echo urlencode($start_date); ?>&end_date=<?php echo urlencode($end_date); ?>" class="text-white-50">View Details <i class="ri-arrow-right-line"></i></a>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-lg-4 col-md-6">
                            <div class="card widget-flat text-bg-dark">
                                <div class="card-body">
                                    <div class="float-end">
                                        <i class="ri-bar-chart-2-line widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0">Total Activity</h6>
                                    <h2 class="my-2"><?php echo number_format(array_sum($comprehensive_stats)); ?></h2>
                                    <p class="mb-0">
                                        <span class="text-white-50">All combined</span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Links -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Quick Links to Detailed Reports</h4>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-md-3">
                                            <a href="reports_members.php?start_date=<?php echo urlencode($start_date); ?>&end_date=<?php echo urlencode($end_date); ?>" class="btn btn-primary w-100">
                                                <i class="ri-team-line"></i> Members Reports
                                            </a>
                                        </div>
                                        <div class="col-md-3">
                                            <a href="reports_payments.php?start_date=<?php echo urlencode($start_date); ?>&end_date=<?php echo urlencode($end_date); ?>" class="btn btn-success w-100">
                                                <i class="ri-money-dollar-circle-line"></i> Payment Reports
                                            </a>
                                        </div>
                                        <div class="col-md-3">
                                            <a href="reports_research.php?start_date=<?php echo urlencode($start_date); ?>&end_date=<?php echo urlencode($end_date); ?>" class="btn btn-info w-100">
                                                <i class="ri-search-line"></i> Research Reports
                                            </a>
                                        </div>
                                        <div class="col-md-3">
                                            <a href="reports_activity.php?start_date=<?php echo urlencode($start_date); ?>&end_date=<?php echo urlencode($end_date); ?>" class="btn btn-warning w-100">
                                                <i class="ri-history-line"></i> Activity Reports
                                            </a>
                                        </div>
                                        <div class="col-md-3">
                                            <a href="reports_notes.php?start_date=<?php echo urlencode($start_date); ?>&end_date=<?php echo urlencode($end_date); ?>" class="btn btn-secondary w-100">
                                                <i class="ri-sticky-note-line"></i> Notes Reports
                                            </a>
                                        </div>
                                        <div class="col-md-3">
                                            <a href="reports_users.php?start_date=<?php echo urlencode($start_date); ?>&end_date=<?php echo urlencode($end_date); ?>" class="btn btn-danger w-100">
                                                <i class="ri-user-settings-line"></i> Users Reports
                                            </a>
                                        </div>
                                        <div class="col-md-3">
                                            <a href="reports_finance.php?start_date=<?php echo urlencode($start_date); ?>&end_date=<?php echo urlencode($end_date); ?>" class="btn btn-primary w-100">
                                                <i class="ri-bank-line"></i> Finance Reports
                                            </a>
                                        </div>
                                        <div class="col-md-3">
                                            <a href="reports_accounting.php?start_date=<?php echo urlencode($start_date); ?>&end_date=<?php echo urlencode($end_date); ?>" class="btn btn-success w-100">
                                                <i class="ri-calculator-line"></i> Accounting Reports
                                            </a>
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
    <!-- App js -->
    <script src="assets/js/app.min.js"></script>
</body>
</html>

