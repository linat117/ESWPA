<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: auth-login.php");
    exit();
}

include 'include/conn.php';
include 'header.php';

// Get quick statistics for overview
$stats = [];

// Total members
$stats['total_members'] = $conn->query("SELECT COUNT(*) as total FROM registrations")->fetch_assoc()['total'] ?? 0;

// Total events
$stats['total_events'] = $conn->query("SELECT COUNT(*) as total FROM events")->fetch_assoc()['total'] ?? 0;
$stats['upcoming_events'] = $conn->query("SELECT COUNT(*) as total FROM upcoming WHERE event_date >= CURDATE()")->fetch_assoc()['total'] ?? 0;

// Total resources
$stats['total_resources'] = $conn->query("SELECT COUNT(*) as total FROM resources")->fetch_assoc()['total'] ?? 0;

// Total research projects
$table_exists = false;
$result = $conn->query("SHOW TABLES LIKE 'research_projects'");
if ($result && $result->num_rows > 0) {
    $table_exists = true;
    $stats['total_research'] = $conn->query("SELECT COUNT(*) as total FROM research_projects")->fetch_assoc()['total'] ?? 0;
} else {
    $stats['total_research'] = 0;
}

// Total sent emails
$stats['total_emails'] = $conn->query("SELECT COUNT(*) as total FROM sent_emails")->fetch_assoc()['total'] ?? 0;

// Recent activity (last 7 days)
$stats['recent_members'] = $conn->query("SELECT COUNT(*) as total FROM registrations WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetch_assoc()['total'] ?? 0;
$stats['recent_emails'] = $conn->query("SELECT COUNT(*) as total FROM sent_emails WHERE sent_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetch_assoc()['total'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">

<body>
    <div class="wrapper">
        <?php include 'sidebar.php'; ?>

        <div class="content-page">
            <div class="content">
                <div class="container-fluid">
                    <!-- Breadcrumb -->
                    <?php
                    $breadcrumb_items = [
                        'Dashboard' => 'index.php',
                        'Reports Dashboard' => ''
                    ];
                    include 'include/breadcrumb.php';
                    ?>

                    <!-- Page Title -->
                    <div class="row">
                        <div class="col-12">
                            <div class="page-title-box d-flex justify-content-between align-items-center">
                                <div>
                                    <h4 class="page-title">Reports Dashboard</h4>
                                    <p class="page-subtitle mb-0">Comprehensive overview of all system reports and analytics</p>
                                </div>
                                <div>
                                    <a href="index.php" class="btn btn-secondary">
                                        <i class="ri-dashboard-line"></i> Main Dashboard
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Statistics -->
                    <div class="row g-3 mb-4">
                        <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
                            <div class="card widget-flat text-bg-primary">
                                <div class="card-body">
                                    <div class="float-end">
                                        <i class="ri-team-line widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0">Total Members</h6>
                                    <h2 class="my-2"><?php echo number_format($stats['total_members']); ?></h2>
                                    <p class="mb-0">
                                        <a href="reports_members.php" class="text-white-50">View Reports <i class="ri-arrow-right-line"></i></a>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
                            <div class="card widget-flat text-bg-info">
                                <div class="card-body">
                                    <div class="float-end">
                                        <i class="ri-calendar-event-line widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0">Total Events</h6>
                                    <h2 class="my-2"><?php echo number_format($stats['total_events']); ?></h2>
                                    <p class="mb-0">
                                        <span class="text-white-50"><?php echo number_format($stats['upcoming_events']); ?> upcoming</span>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
                            <div class="card widget-flat text-bg-success">
                                <div class="card-body">
                                    <div class="float-end">
                                        <i class="ri-file-paper-2-line widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0">Resources</h6>
                                    <h2 class="my-2"><?php echo number_format($stats['total_resources']); ?></h2>
                                    <p class="mb-0">
                                        <a href="resources_reports.php" class="text-white-50">View Reports <i class="ri-arrow-right-line"></i></a>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
                            <div class="card widget-flat text-bg-warning">
                                <div class="card-body">
                                    <div class="float-end">
                                        <i class="ri-mail-line widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0">Emails Sent</h6>
                                    <h2 class="my-2"><?php echo number_format($stats['total_emails']); ?></h2>
                                    <p class="mb-0">
                                        <span class="text-white-50"><?php echo number_format($stats['recent_emails']); ?> in last 7 days</span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Reports Categories -->
                    <div class="row">
                        <!-- Core Reports -->
                        <div class="col-lg-6 mb-4">
                            <div class="card">
                                <div class="card-header bg-primary text-white">
                                    <h4 class="header-title mb-0"><i class="ri-bar-chart-box-line"></i> Core Reports</h4>
                                </div>
                                <div class="card-body">
                                    <div class="list-group list-group-flush">
                                        <a href="reports_members.php" class="list-group-item list-group-item-action">
                                            <div class="d-flex w-100 justify-content-between align-items-center">
                                                <div>
                                                    <i class="ri-team-line text-primary me-2"></i>
                                                    <strong>Members Reports</strong>
                                                    <p class="mb-0 text-muted small">Registrations, memberships, demographics, analytics</p>
                                                </div>
                                                <i class="ri-arrow-right-s-line"></i>
                                            </div>
                                        </a>
                                        <a href="reports_payments.php" class="list-group-item list-group-item-action">
                                            <div class="d-flex w-100 justify-content-between align-items-center">
                                                <div>
                                                    <i class="ri-money-dollar-circle-line text-success me-2"></i>
                                                    <strong>Payment Reports</strong>
                                                    <p class="mb-0 text-muted small">Transactions, revenue, payment analysis</p>
                                                </div>
                                                <i class="ri-arrow-right-s-line"></i>
                                            </div>
                                        </a>
                                        <a href="reports_research.php" class="list-group-item list-group-item-action">
                                            <div class="d-flex w-100 justify-content-between align-items-center">
                                                <div>
                                                    <i class="ri-search-line text-info me-2"></i>
                                                    <strong>Research Reports</strong>
                                                    <p class="mb-0 text-muted small">Projects, collaborators, publications, analytics</p>
                                                </div>
                                                <i class="ri-arrow-right-s-line"></i>
                                            </div>
                                        </a>
                                        <a href="resources_reports.php" class="list-group-item list-group-item-action">
                                            <div class="d-flex w-100 justify-content-between align-items-center">
                                                <div>
                                                    <i class="ri-file-paper-2-line text-warning me-2"></i>
                                                    <strong>Resources Reports</strong>
                                                    <p class="mb-0 text-muted small">Downloads, access, analytics</p>
                                                </div>
                                                <i class="ri-arrow-right-s-line"></i>
                                            </div>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- System & Activity Reports -->
                        <div class="col-lg-6 mb-4">
                            <div class="card">
                                <div class="card-header bg-info text-white">
                                    <h4 class="header-title mb-0"><i class="ri-file-chart-line"></i> System & Activity Reports</h4>
                                </div>
                                <div class="card-body">
                                    <div class="list-group list-group-flush">
                                        <a href="reports_activity.php" class="list-group-item list-group-item-action">
                                            <div class="d-flex w-100 justify-content-between align-items-center">
                                                <div>
                                                    <i class="ri-history-line text-info me-2"></i>
                                                    <strong>Activity Reports</strong>
                                                    <p class="mb-0 text-muted small">User activity, system activity, audit logs</p>
                                                </div>
                                                <i class="ri-arrow-right-s-line"></i>
                                            </div>
                                        </a>
                                        <a href="reports_notes.php" class="list-group-item list-group-item-action">
                                            <div class="d-flex w-100 justify-content-between align-items-center">
                                                <div>
                                                    <i class="ri-sticky-note-line text-warning me-2"></i>
                                                    <strong>Notes Reports</strong>
                                                    <p class="mb-0 text-muted small">Member notes, research notes, admin notes</p>
                                                </div>
                                                <i class="ri-arrow-right-s-line"></i>
                                            </div>
                                        </a>
                                        <a href="reports_users.php" class="list-group-item list-group-item-action">
                                            <div class="d-flex w-100 justify-content-between align-items-center">
                                                <div>
                                                    <i class="ri-user-settings-line text-danger me-2"></i>
                                                    <strong>Users/Admin Reports</strong>
                                                    <p class="mb-0 text-muted small">Admin activity, user management, access logs</p>
                                                </div>
                                                <i class="ri-arrow-right-s-line"></i>
                                            </div>
                                        </a>
                                        <a href="reports_finance.php" class="list-group-item list-group-item-action">
                                            <div class="d-flex w-100 justify-content-between align-items-center">
                                                <div>
                                                    <i class="ri-bank-line text-success me-2"></i>
                                                    <strong>Finance Reports</strong>
                                                    <p class="mb-0 text-muted small">Revenue, expenses, financial overview</p>
                                                </div>
                                                <i class="ri-arrow-right-s-line"></i>
                                            </div>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Specialized Reports -->
                        <div class="col-lg-6 mb-4">
                            <div class="card">
                                <div class="card-header bg-success text-white">
                                    <h4 class="header-title mb-0"><i class="ri-file-list-3-line"></i> Specialized Reports</h4>
                                </div>
                                <div class="card-body">
                                    <div class="list-group list-group-flush">
                                        <a href="reports_accounting.php" class="list-group-item list-group-item-action">
                                            <div class="d-flex w-100 justify-content-between align-items-center">
                                                <div>
                                                    <i class="ri-calculator-line text-success me-2"></i>
                                                    <strong>Accounting Reports</strong>
                                                    <p class="mb-0 text-muted small">Detailed accounting data, ledger entries</p>
                                                </div>
                                                <i class="ri-arrow-right-s-line"></i>
                                            </div>
                                        </a>
                                        <a href="reports_details.php" class="list-group-item list-group-item-action">
                                            <div class="d-flex w-100 justify-content-between align-items-center">
                                                <div>
                                                    <i class="ri-file-text-line text-primary me-2"></i>
                                                    <strong>Detailed Reports</strong>
                                                    <p class="mb-0 text-muted small">Comprehensive reports with full details</p>
                                                </div>
                                                <i class="ri-arrow-right-s-line"></i>
                                            </div>
                                        </a>
                                        <a href="support_reports.php" class="list-group-item list-group-item-action">
                                            <div class="d-flex w-100 justify-content-between align-items-center">
                                                <div>
                                                    <i class="ri-customer-service-2-line text-warning me-2"></i>
                                                    <strong>Support Reports</strong>
                                                    <p class="mb-0 text-muted small">Ticket analytics, resolution times, performance</p>
                                                </div>
                                                <i class="ri-arrow-right-s-line"></i>
                                            </div>
                                        </a>
                                        <a href="notifications_reports.php" class="list-group-item list-group-item-action">
                                            <div class="d-flex w-100 justify-content-between align-items-center">
                                                <div>
                                                    <i class="ri-notification-line text-info me-2"></i>
                                                    <strong>Notification Reports</strong>
                                                    <p class="mb-0 text-muted small">Notification delivery, read rates, analytics</p>
                                                </div>
                                                <i class="ri-arrow-right-s-line"></i>
                                            </div>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Quick Access & Legacy -->
                        <div class="col-lg-6 mb-4">
                            <div class="card">
                                <div class="card-header bg-secondary text-white">
                                    <h4 class="header-title mb-0"><i class="ri-file-list-line"></i> Quick Access</h4>
                                </div>
                                <div class="card-body">
                                    <div class="list-group list-group-flush">
                                        <a href="member_reports.php" class="list-group-item list-group-item-action">
                                            <div class="d-flex w-100 justify-content-between align-items-center">
                                                <div>
                                                    <i class="ri-user-line text-primary me-2"></i>
                                                    <strong>Member Reports (Enhanced)</strong>
                                                    <p class="mb-0 text-muted small">Advanced member analytics and reports</p>
                                                </div>
                                                <i class="ri-arrow-right-s-line"></i>
                                            </div>
                                        </a>
                                        <a href="id_card_reports.php" class="list-group-item list-group-item-action">
                                            <div class="d-flex w-100 justify-content-between align-items-center">
                                                <div>
                                                    <i class="ri-id-card-line text-info me-2"></i>
                                                    <strong>ID Card Reports</strong>
                                                    <p class="mb-0 text-muted small">ID card generation, verification reports</p>
                                                </div>
                                                <i class="ri-arrow-right-s-line"></i>
                                            </div>
                                        </a>
                                        <a href="chat_analytics.php" class="list-group-item list-group-item-action">
                                            <div class="d-flex w-100 justify-content-between align-items-center">
                                                <div>
                                                    <i class="ri-message-3-line text-success me-2"></i>
                                                    <strong>Chat Analytics</strong>
                                                    <p class="mb-0 text-muted small">Chat message analytics and reports</p>
                                                </div>
                                                <i class="ri-arrow-right-s-line"></i>
                                            </div>
                                        </a>
                                        <a href="report.php" class="list-group-item list-group-item-action">
                                            <div class="d-flex w-100 justify-content-between align-items-center">
                                                <div>
                                                    <i class="ri-file-history-line text-secondary me-2"></i>
                                                    <strong>Legacy Reports</strong>
                                                    <p class="mb-0 text-muted small">Original reports system (daily, monthly, etc.)</p>
                                                </div>
                                                <i class="ri-arrow-right-s-line"></i>
                                            </div>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Report Statistics Summary -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Recent Activity Summary (Last 7 Days)</h4>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-md-3">
                                            <div class="text-center p-3 bg-primary bg-opacity-10 rounded">
                                                <h3 class="mb-0"><?php echo number_format($stats['recent_members']); ?></h3>
                                                <small class="text-muted">New Members</small>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="text-center p-3 bg-info bg-opacity-10 rounded">
                                                <h3 class="mb-0"><?php echo number_format($stats['recent_emails']); ?></h3>
                                                <small class="text-muted">Emails Sent</small>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="text-center p-3 bg-success bg-opacity-10 rounded">
                                                <h3 class="mb-0"><?php echo number_format($stats['total_research']); ?></h3>
                                                <small class="text-muted">Research Projects</small>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="text-center p-3 bg-warning bg-opacity-10 rounded">
                                                <h3 class="mb-0"><?php echo number_format($stats['total_resources']); ?></h3>
                                                <small class="text-muted">Total Resources</small>
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
    
    <!-- App js -->
    <script src="assets/js/app.min.js"></script>
    
    <!-- Design System JS (UI/UX Enhancements) -->
    <script src="assets/js/design-system.js"></script>
    
    <!-- Export Helper JS -->
    <script src="assets/js/export-helper.js"></script>

</body>
</html>
