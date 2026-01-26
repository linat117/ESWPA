<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: auth-login.php");
    exit();
}

include 'include/conn.php';
include 'header.php';

// Helper: safe count from query (returns 0 if table missing or error)
$safeCount = function ($query) use ($conn) {
    $r = @$conn->query($query);
    return $r ? (int) ($r->fetch_assoc()['total'] ?? 0) : 0;
};

// Get quick statistics for overview (summary for all reports)
$stats = [];

// ---- Members (reports_members, member_reports) ----
$stats['total_members'] = $safeCount("SELECT COUNT(*) as total FROM registrations");
$stats['approved_members'] = $safeCount("SELECT COUNT(*) as total FROM registrations WHERE approval_status = 'approved'");
$stats['pending_members'] = $safeCount("SELECT COUNT(*) as total FROM registrations WHERE approval_status = 'pending'");
$stats['recent_members'] = $safeCount("SELECT COUNT(*) as total FROM registrations WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
$stats['with_payment_proof'] = $safeCount("SELECT COUNT(*) as total FROM registrations WHERE (bank_slip IS NOT NULL AND bank_slip != '')");

// ---- Events ----
$stats['total_events'] = $safeCount("SELECT COUNT(*) as total FROM events");
$stats['upcoming_events'] = $safeCount("SELECT COUNT(*) as total FROM upcoming WHERE event_date >= CURDATE()");

// ---- Resources (resources_reports) ----
$stats['total_resources'] = $safeCount("SELECT COUNT(*) as total FROM resources");

// ---- Research (reports_research) ----
$stats['total_research'] = $safeCount("SELECT COUNT(*) as total FROM research_projects");
$stats['research_collaborators'] = $safeCount("SELECT COUNT(*) as total FROM research_collaborators");

// ---- Finance / Payments (reports_finance, reports_payments) ----
$stats['total_emails'] = $safeCount("SELECT COUNT(*) as total FROM sent_emails");
$stats['recent_emails'] = $safeCount("SELECT COUNT(*) as total FROM sent_emails WHERE sent_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");

// ---- ID Cards (id_card_reports) ----
$stats['id_cards_generated'] = $safeCount("SELECT COUNT(*) as total FROM registrations WHERE id_card_generated = 1 AND approval_status = 'approved'");
$stats['id_cards_pending'] = $safeCount("SELECT COUNT(*) as total FROM registrations WHERE (id_card_generated = 0 OR id_card_generated IS NULL) AND approval_status = 'approved' AND (expiry_date IS NULL OR expiry_date >= CURDATE())");
$stats['id_verifications'] = $safeCount("SELECT COUNT(*) as total FROM id_card_verification");

// ---- Support (support_reports) ----
$stats['support_tickets'] = $safeCount("SELECT COUNT(*) as total FROM support_tickets");
$stats['support_open'] = $safeCount("SELECT COUNT(*) as total FROM support_tickets WHERE status = 'open'");
$stats['support_7d'] = $safeCount("SELECT COUNT(*) as total FROM support_tickets WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");

// ---- Notifications (notifications_reports) ----
$stats['notifications_total'] = $safeCount("SELECT COUNT(*) as total FROM notifications");
$stats['notifications_unread'] = $safeCount("SELECT COUNT(*) as total FROM notifications WHERE is_read = 0");

// ---- Users / Activity (reports_users, reports_activity) ----
$stats['admin_users'] = $safeCount("SELECT COUNT(*) as total FROM user");
$stats['audit_logs'] = $safeCount("SELECT COUNT(*) as total FROM audit_logs");
$stats['audit_logs_7d'] = $safeCount("SELECT COUNT(*) as total FROM audit_logs WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");

// ---- Notes (reports_notes) ----
$stats['member_notes'] = $safeCount("SELECT COUNT(*) as total FROM member_admin_notes");
$stats['research_notes'] = $safeCount("SELECT COUNT(*) as total FROM research_notes");

// ---- Accounting (reports_accounting) ----
$stats['accounting_approved'] = $safeCount("SELECT COUNT(*) as total FROM registrations WHERE approval_status = 'approved'");
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

                    <p class="text-muted mb-3">Summary of key metrics from all report areas. Use the links to jump to detailed reports.</p>

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
                                        <span class="text-white-50"><?php echo number_format($stats['approved_members']); ?> approved</span>
                                        · <a href="reports_members.php" class="text-white-50">Reports <i class="ri-arrow-right-line"></i></a>
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

                    <!-- Members & ID Cards Summary -->
                    <div class="row g-3 mb-4">
                        <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
                            <div class="card widget-flat border-primary">
                                <div class="card-body">
                                    <div class="float-end">
                                        <i class="ri-checkbox-circle-line text-primary widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0 text-muted">Approved Members</h6>
                                    <h2 class="my-2"><?php echo number_format($stats['approved_members']); ?></h2>
                                    <p class="mb-0">
                                        <a href="reports_members.php" class="text-primary">Reports <i class="ri-arrow-right-line"></i></a>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
                            <div class="card widget-flat border-warning">
                                <div class="card-body">
                                    <div class="float-end">
                                        <i class="ri-time-line text-warning widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0 text-muted">Pending Members</h6>
                                    <h2 class="my-2"><?php echo number_format($stats['pending_members']); ?></h2>
                                    <p class="mb-0">
                                        <a href="reports_members.php" class="text-warning">Reports <i class="ri-arrow-right-line"></i></a>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
                            <div class="card widget-flat border-info">
                                <div class="card-body">
                                    <div class="float-end">
                                        <i class="ri-id-card-line text-info widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0 text-muted">ID Cards Generated</h6>
                                    <h2 class="my-2"><?php echo number_format($stats['id_cards_generated']); ?></h2>
                                    <p class="mb-0">
                                        <span class="text-muted"><?php echo number_format($stats['id_cards_pending']); ?> pending</span>
                                        · <a href="id_card_reports.php" class="text-info">Reports <i class="ri-arrow-right-line"></i></a>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
                            <div class="card widget-flat border-success">
                                <div class="card-body">
                                    <div class="float-end">
                                        <i class="ri-bank-card-line text-success widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0 text-muted">With Payment Proof</h6>
                                    <h2 class="my-2"><?php echo number_format($stats['with_payment_proof']); ?></h2>
                                    <p class="mb-0">
                                        <a href="reports_payments.php" class="text-success">Reports <i class="ri-arrow-right-line"></i></a>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Support, Notifications, Users & Activity -->
                    <div class="row g-3 mb-4">
                        <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
                            <div class="card widget-flat text-bg-danger">
                                <div class="card-body">
                                    <div class="float-end">
                                        <i class="ri-customer-service-2-line widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0">Support Tickets</h6>
                                    <h2 class="my-2"><?php echo number_format($stats['support_tickets']); ?></h2>
                                    <p class="mb-0">
                                        <span class="text-white-50"><?php echo number_format($stats['support_open']); ?> open</span>
                                        · <a href="support_reports.php" class="text-white-50">Reports <i class="ri-arrow-right-line"></i></a>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
                            <div class="card widget-flat text-bg-secondary">
                                <div class="card-body">
                                    <div class="float-end">
                                        <i class="ri-notification-3-line widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0">Notifications</h6>
                                    <h2 class="my-2"><?php echo number_format($stats['notifications_total']); ?></h2>
                                    <p class="mb-0">
                                        <span class="text-white-50"><?php echo number_format($stats['notifications_unread']); ?> unread</span>
                                        · <a href="notifications_reports.php" class="text-white-50">Reports <i class="ri-arrow-right-line"></i></a>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
                            <div class="card widget-flat border-dark">
                                <div class="card-body">
                                    <div class="float-end">
                                        <i class="ri-user-settings-line text-dark widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0 text-muted">Admin Users</h6>
                                    <h2 class="my-2"><?php echo number_format($stats['admin_users']); ?></h2>
                                    <p class="mb-0">
                                        <a href="reports_users.php" class="text-dark">Reports <i class="ri-arrow-right-line"></i></a>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
                            <div class="card widget-flat border-info">
                                <div class="card-body">
                                    <div class="float-end">
                                        <i class="ri-history-line text-info widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0 text-muted">Activity (7 days)</h6>
                                    <h2 class="my-2"><?php echo number_format($stats['audit_logs_7d']); ?></h2>
                                    <p class="mb-0">
                                        <span class="text-muted"><?php echo number_format($stats['audit_logs']); ?> total</span>
                                        · <a href="reports_activity.php" class="text-info">Reports <i class="ri-arrow-right-line"></i></a>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Research, Notes, Accounting -->
                    <div class="row g-3 mb-4">
                        <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
                            <div class="card widget-flat border-info">
                                <div class="card-body">
                                    <div class="float-end">
                                        <i class="ri-user-add-line text-info widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0 text-muted">Research Collaborators</h6>
                                    <h2 class="my-2"><?php echo number_format($stats['research_collaborators']); ?></h2>
                                    <p class="mb-0">
                                        <span class="text-muted"><?php echo number_format($stats['total_research']); ?> projects</span>
                                        · <a href="reports_research.php" class="text-info">Reports <i class="ri-arrow-right-line"></i></a>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
                            <div class="card widget-flat border-warning">
                                <div class="card-body">
                                    <div class="float-end">
                                        <i class="ri-sticky-note-line text-warning widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0 text-muted">Notes</h6>
                                    <h2 class="my-2"><?php echo number_format($stats['member_notes'] + $stats['research_notes']); ?></h2>
                                    <p class="mb-0">
                                        <span class="text-muted"><?php echo number_format($stats['member_notes']); ?> member, <?php echo number_format($stats['research_notes']); ?> research</span>
                                        · <a href="reports_notes.php" class="text-warning">Reports <i class="ri-arrow-right-line"></i></a>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
                            <div class="card widget-flat border-success">
                                <div class="card-body">
                                    <div class="float-end">
                                        <i class="ri-calculator-line text-success widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0 text-muted">Finance / Accounting</h6>
                                    <h2 class="my-2"><?php echo number_format($stats['accounting_approved']); ?></h2>
                                    <p class="mb-0">
                                        <span class="text-muted">approved registrations</span>
                                        · <a href="reports_finance.php" class="text-success">Finance</a>
                                        · <a href="reports_accounting.php" class="text-success">Accounting</a>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
                            <div class="card widget-flat border-secondary">
                                <div class="card-body">
                                    <div class="float-end">
                                        <i class="ri-qr-scan-2-line text-secondary widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0 text-muted">ID Verifications</h6>
                                    <h2 class="my-2"><?php echo number_format($stats['id_verifications']); ?></h2>
                                    <p class="mb-0">
                                        <a href="id_card_reports.php" class="text-secondary">ID Card Reports <i class="ri-arrow-right-line"></i></a>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php /* Reports Categories - commented out for now
                    <!-- Core Reports, System & Activity, Specialized, Quick Access -->
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
                    */ ?>

                    <!-- Recent Activity Summary (Last 7 Days) -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Recent Activity Summary (Last 7 Days)</h4>
                                    <p class="text-muted mb-0 small">Quick snapshot of recent system activity</p>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-6 col-md-3">
                                            <div class="text-center p-3 bg-primary bg-opacity-10 rounded">
                                                <h3 class="mb-0"><?php echo number_format($stats['recent_members']); ?></h3>
                                                <small class="text-muted">New Members</small>
                                            </div>
                                        </div>
                                        <div class="col-6 col-md-3">
                                            <div class="text-center p-3 bg-info bg-opacity-10 rounded">
                                                <h3 class="mb-0"><?php echo number_format($stats['recent_emails']); ?></h3>
                                                <small class="text-muted">Emails Sent</small>
                                            </div>
                                        </div>
                                        <div class="col-6 col-md-3">
                                            <div class="text-center p-3 bg-success bg-opacity-10 rounded">
                                                <h3 class="mb-0"><?php echo number_format($stats['audit_logs_7d']); ?></h3>
                                                <small class="text-muted">Audit Log Entries</small>
                                            </div>
                                        </div>
                                        <div class="col-6 col-md-3">
                                            <div class="text-center p-3 bg-warning bg-opacity-10 rounded">
                                                <h3 class="mb-0"><?php echo number_format($stats['support_7d']); ?></h3>
                                                <small class="text-muted">New Support Tickets</small>
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
