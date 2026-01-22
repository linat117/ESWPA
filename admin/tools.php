<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: auth-login.php");
    exit();
}

include 'include/conn.php';
include 'header.php';
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
                        'Tools & Plugins' => ''
                    ];
                    include 'include/breadcrumb.php';
                    ?>

                    <!-- Page Title -->
                    <div class="row">
                        <div class="col-12">
                            <div class="page-title-box d-flex justify-content-between align-items-center">
                                <div>
                                    <h4 class="page-title">Tools & Plugins</h4>
                                    <p class="page-subtitle mb-0">Access all admin tools and plugin management interfaces</p>
                                </div>
                                <div>
                                    <a href="index.php" class="btn btn-secondary">
                                        <i class="ri-dashboard-line"></i> Dashboard
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Email Tools -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h5 class="mb-3"><i class="ri-mail-line text-primary me-2"></i> Email Tools</h5>
                        </div>
                        <div class="col-xl-3 col-lg-4 col-md-6 mb-3">
                            <a href="email_automation_settings.php" class="text-decoration-none">
                                <div class="card tool-card h-100">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="ri-settings-4-line display-4 text-primary"></i>
                                        </div>
                                        <h5 class="card-title">Email Automation</h5>
                                        <p class="text-muted mb-0 small">Configure automated email triggers and workflows</p>
                                    </div>
                                </div>
                            </a>
                        </div>
                        <div class="col-xl-3 col-lg-4 col-md-6 mb-3">
                            <a href="email_templates.php" class="text-decoration-none">
                                <div class="card tool-card h-100">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="ri-file-text-line display-4 text-info"></i>
                                        </div>
                                        <h5 class="card-title">Email Templates</h5>
                                        <p class="text-muted mb-0 small">Manage email templates and designs</p>
                                    </div>
                                </div>
                            </a>
                        </div>
                        <div class="col-xl-3 col-lg-4 col-md-6 mb-3">
                            <a href="email_automation_logs.php" class="text-decoration-none">
                                <div class="card tool-card h-100">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="ri-file-list-3-line display-4 text-success"></i>
                                        </div>
                                        <h5 class="card-title">Automation Logs</h5>
                                        <p class="text-muted mb-0 small">View email automation activity logs</p>
                                    </div>
                                </div>
                            </a>
                        </div>
                    </div>

                    <!-- Chat & Support Tools -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h5 class="mb-3"><i class="ri-customer-service-2-line text-info me-2"></i> Chat & Support Tools</h5>
                        </div>
                        <div class="col-xl-3 col-lg-4 col-md-6 mb-3">
                            <a href="chat_analytics.php" class="text-decoration-none">
                                <div class="card tool-card h-100">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="ri-bar-chart-box-line display-4 text-warning"></i>
                                        </div>
                                        <h5 class="card-title">Chat Analytics</h5>
                                        <p class="text-muted mb-0 small">View chat message analytics and trends</p>
                                    </div>
                                </div>
                            </a>
                        </div>
                        <div class="col-xl-3 col-lg-4 col-md-6 mb-3">
                            <a href="chat_settings.php" class="text-decoration-none">
                                <div class="card tool-card h-100">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="ri-settings-3-line display-4 text-primary"></i>
                                        </div>
                                        <h5 class="card-title">Chat Settings</h5>
                                        <p class="text-muted mb-0 small">Configure chat widget and preferences</p>
                                    </div>
                                </div>
                            </a>
                        </div>
                        <div class="col-xl-3 col-lg-4 col-md-6 mb-3">
                            <a href="support_knowledge_base.php" class="text-decoration-none">
                                <div class="card tool-card h-100">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="ri-book-open-line display-4 text-success"></i>
                                        </div>
                                        <h5 class="card-title">Knowledge Base</h5>
                                        <p class="text-muted mb-0 small">Manage support knowledge base articles</p>
                                    </div>
                                </div>
                            </a>
                        </div>
                        <div class="col-xl-3 col-lg-4 col-md-6 mb-3">
                            <a href="support_faq_manage.php" class="text-decoration-none">
                                <div class="card tool-card h-100">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="ri-questionnaire-line display-4 text-info"></i>
                                        </div>
                                        <h5 class="card-title">FAQ Management</h5>
                                        <p class="text-muted mb-0 small">Manage frequently asked questions</p>
                                    </div>
                                </div>
                            </a>
                        </div>
                        <div class="col-xl-3 col-lg-4 col-md-6 mb-3">
                            <a href="support_reports.php" class="text-decoration-none">
                                <div class="card tool-card h-100">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="ri-file-chart-line display-4 text-danger"></i>
                                        </div>
                                        <h5 class="card-title">Support Reports</h5>
                                        <p class="text-muted mb-0 small">View support ticket analytics and reports</p>
                                    </div>
                                </div>
                            </a>
                        </div>
                    </div>

                    <!-- Notification Tools -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h5 class="mb-3"><i class="ri-notification-line text-warning me-2"></i> Notification Tools</h5>
                        </div>
                        <div class="col-xl-3 col-lg-4 col-md-6 mb-3">
                            <a href="notification_settings.php" class="text-decoration-none">
                                <div class="card tool-card h-100">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="ri-settings-2-line display-4 text-primary"></i>
                                        </div>
                                        <h5 class="card-title">Notification Settings</h5>
                                        <p class="text-muted mb-0 small">Configure notification preferences</p>
                                    </div>
                                </div>
                            </a>
                        </div>
                        <div class="col-xl-3 col-lg-4 col-md-6 mb-3">
                            <a href="notification_templates.php" class="text-decoration-none">
                                <div class="card tool-card h-100">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="ri-file-edit-line display-4 text-info"></i>
                                        </div>
                                        <h5 class="card-title">Notification Templates</h5>
                                        <p class="text-muted mb-0 small">Manage notification templates</p>
                                    </div>
                                </div>
                            </a>
                        </div>
                        <div class="col-xl-3 col-lg-4 col-md-6 mb-3">
                            <a href="notifications_reports.php" class="text-decoration-none">
                                <div class="card tool-card h-100">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="ri-bar-chart-2-line display-4 text-success"></i>
                                        </div>
                                        <h5 class="card-title">Notification Reports</h5>
                                        <p class="text-muted mb-0 small">View notification analytics and reports</p>
                                    </div>
                                </div>
                            </a>
                        </div>
                    </div>

                    <!-- Content & Media Tools -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h5 class="mb-3"><i class="ri-image-line text-success me-2"></i> Content & Media Tools</h5>
                        </div>
                        <div class="col-xl-3 col-lg-4 col-md-6 mb-3">
                            <a href="media_library.php" class="text-decoration-none">
                                <div class="card tool-card h-100">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="ri-folder-image-line display-4 text-primary"></i>
                                        </div>
                                        <h5 class="card-title">Media Library</h5>
                                        <p class="text-muted mb-0 small">Manage media files and images</p>
                                    </div>
                                </div>
                            </a>
                        </div>
                    </div>

                    <!-- Access Control Tools -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h5 class="mb-3"><i class="ri-shield-check-line text-danger me-2"></i> Access Control Tools</h5>
                        </div>
                        <div class="col-xl-3 col-lg-4 col-md-6 mb-3">
                            <a href="badge_permissions.php" class="text-decoration-none">
                                <div class="card tool-card h-100">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="ri-award-line display-4 text-warning"></i>
                                        </div>
                                        <h5 class="card-title">Badge Permissions</h5>
                                        <p class="text-muted mb-0 small">Configure badge-based permissions</p>
                                    </div>
                                </div>
                            </a>
                        </div>
                        <div class="col-xl-3 col-lg-4 col-md-6 mb-3">
                            <a href="special_permissions.php" class="text-decoration-none">
                                <div class="card tool-card h-100">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="ri-lock-password-line display-4 text-info"></i>
                                        </div>
                                        <h5 class="card-title">Special Permissions</h5>
                                        <p class="text-muted mb-0 small">Manage special access permissions</p>
                                    </div>
                                </div>
                            </a>
                        </div>
                        <div class="col-xl-3 col-lg-4 col-md-6 mb-3">
                            <a href="access_logs.php" class="text-decoration-none">
                                <div class="card tool-card h-100">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="ri-file-list-2-line display-4 text-success"></i>
                                        </div>
                                        <h5 class="card-title">Access Logs</h5>
                                        <p class="text-muted mb-0 small">View system access logs and activity</p>
                                    </div>
                                </div>
                            </a>
                        </div>
                    </div>

                    <!-- System & Maintenance Tools -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h5 class="mb-3"><i class="ri-tools-line text-secondary me-2"></i> System & Maintenance Tools</h5>
                        </div>
                        <div class="col-xl-3 col-lg-4 col-md-6 mb-3">
                            <a href="ai_settings.php" class="text-decoration-none">
                                <div class="card tool-card h-100">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="ri-robot-line display-4 text-primary"></i>
                                        </div>
                                        <h5 class="card-title">AI Settings</h5>
                                        <p class="text-muted mb-0 small">Configure AI features and preferences</p>
                                    </div>
                                </div>
                            </a>
                        </div>
                        <div class="col-xl-3 col-lg-4 col-md-6 mb-3">
                            <a href="ai_queue.php" class="text-decoration-none">
                                <div class="card tool-card h-100">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="ri-list-check display-4 text-info"></i>
                                        </div>
                                        <h5 class="card-title">AI Processing Queue</h5>
                                        <p class="text-muted mb-0 small">Monitor AI processing tasks</p>
                                    </div>
                                </div>
                            </a>
                        </div>
                        <div class="col-xl-3 col-lg-4 col-md-6 mb-3">
                            <a href="settings_backup.php" class="text-decoration-none">
                                <div class="card tool-card h-100">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="ri-database-2-line display-4 text-success"></i>
                                        </div>
                                        <h5 class="card-title">Backup & Restore</h5>
                                        <p class="text-muted mb-0 small">Manage database backups and restore</p>
                                    </div>
                                </div>
                            </a>
                        </div>
                        <div class="col-xl-3 col-lg-4 col-md-6 mb-3">
                            <a href="settings_sync.php" class="text-decoration-none">
                                <div class="card tool-card h-100">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="ri-refresh-line display-4 text-warning"></i>
                                        </div>
                                        <h5 class="card-title">Data Sync</h5>
                                        <p class="text-muted mb-0 small">Synchronize data between systems</p>
                                    </div>
                                </div>
                            </a>
                        </div>
                        <div class="col-xl-3 col-lg-4 col-md-6 mb-3">
                            <a href="changelog_list.php" class="text-decoration-none">
                                <div class="card tool-card h-100">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="ri-history-line display-4 text-secondary"></i>
                                        </div>
                                        <h5 class="card-title">Changelogs</h5>
                                        <p class="text-muted mb-0 small">View system changelog and updates</p>
                                    </div>
                                </div>
                            </a>
                        </div>
                        <div class="col-xl-3 col-lg-4 col-md-6 mb-3">
                            <a href="future_enhancement.php" class="text-decoration-none">
                                <div class="card tool-card h-100">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="ri-lightbulb-line display-4 text-danger"></i>
                                        </div>
                                        <h5 class="card-title">Future Plans</h5>
                                        <p class="text-muted mb-0 small">View planned enhancements and features</p>
                                    </div>
                                </div>
                            </a>
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

    <style>
        .tool-card {
            transition: all 0.3s ease;
            border: 1px solid #e3e6f0;
            cursor: pointer;
        }
        
        .tool-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            border-color: #727cf5;
        }
        
        .tool-card .card-body {
            padding: 2rem 1.5rem;
        }
        
        .tool-card .display-4 {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        
        .tool-card h5 {
            font-size: 1.1rem;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 0.5rem;
        }
        
        .tool-card .text-muted {
            font-size: 0.875rem;
            line-height: 1.5;
        }
        
        .tool-card a {
            color: inherit;
        }
    </style>

</body>
</html>

