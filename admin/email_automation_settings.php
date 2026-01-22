<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: auth-login.php");
    exit();
}

include 'include/conn.php';
include 'header.php';

// Get current content type
$current_type = $_GET['type'] ?? 'news';
$content_types = ['news' => 'News', 'blog' => 'Blog', 'report' => 'Report', 'event' => 'Event', 'resource' => 'Resource'];

// Get settings for current type
$settingsQuery = "SELECT * FROM email_automation_settings WHERE content_type = ? LIMIT 1";
$settingsStmt = $conn->prepare($settingsQuery);
$settingsStmt->bind_param("s", $current_type);
$settingsStmt->execute();
$settingsResult = $settingsStmt->get_result();
$settings = $settingsResult->fetch_assoc();
$settingsStmt->close();

// If no settings exist, create default
if (!$settings) {
    $defaultQuery = "INSERT INTO email_automation_settings (content_type, enabled, send_to_subscribers, send_to_members, send_only_published) 
                     VALUES (?, 0, 1, 1, 1)";
    $defaultStmt = $conn->prepare($defaultQuery);
    $defaultStmt->bind_param("s", $current_type);
    $defaultStmt->execute();
    $defaultStmt->close();
    
    // Fetch again
    $settingsStmt = $conn->prepare($settingsQuery);
    $settingsStmt->bind_param("s", $current_type);
    $settingsStmt->execute();
    $settingsResult = $settingsStmt->get_result();
    $settings = $settingsResult->fetch_assoc();
    $settingsStmt->close();
}

// Get available templates
$templatesQuery = "SELECT id, name FROM email_templates WHERE (content_type = ? OR content_type = 'general') AND is_active = 1 ORDER BY name";
$templatesStmt = $conn->prepare($templatesQuery);
$templatesStmt->bind_param("s", $current_type);
$templatesStmt->execute();
$templatesResult = $templatesStmt->get_result();
$templates = [];
while ($row = $templatesResult->fetch_assoc()) {
    $templates[] = $row;
}
$templatesStmt->close();
?>

<body>
    <div class="wrapper">
        <?php include 'sidebar.php'; ?>
        <div class="content-page">
            <div class="content">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-12">
                            <div class="page-title-box">
                                <div class="page-title-right">
                                    <ol class="breadcrumb m-0">
                                        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                                        <li class="breadcrumb-item active">Email Automation Settings</li>
                                    </ol>
                                </div>
                                <h4 class="page-title">Email Automation Settings</h4>
                            </div>
                        </div>
                    </div>

                    <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($_GET['success']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>

                    <?php if (isset($_GET['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($_GET['error']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <!-- Content Type Tabs -->
                                    <ul class="nav nav-tabs nav-bordered mb-3" role="tablist">
                                        <?php foreach ($content_types as $type => $label): ?>
                                        <li class="nav-item">
                                            <a href="?type=<?php echo $type; ?>" 
                                               class="nav-link <?php echo $current_type == $type ? 'active' : ''; ?>">
                                                <?php echo htmlspecialchars($label); ?>
                                            </a>
                                        </li>
                                        <?php endforeach; ?>
                                    </ul>

                                    <!-- Settings Form -->
                                    <form action="include/save_automation_settings.php" method="POST">
                                        <input type="hidden" name="content_type" value="<?php echo htmlspecialchars($current_type); ?>">
                                        
                                        <div class="row">
                                            <div class="col-md-12 mb-3">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" name="enabled" id="enabled" 
                                                           <?php echo ($settings['enabled'] ?? 0) ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="enabled">
                                                        <strong>Enable Email Automation</strong>
                                                        <small class="text-muted d-block">Automatically send emails when <?php echo strtolower($content_types[$current_type]); ?> content is created/published</small>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>

                                        <hr>

                                        <h5 class="mb-3">Recipients</h5>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="send_to_subscribers" id="send_to_subscribers" 
                                                           <?php echo ($settings['send_to_subscribers'] ?? 1) ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="send_to_subscribers">
                                                        Send to Email Subscribers
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="send_to_members" id="send_to_members" 
                                                           <?php echo ($settings['send_to_members'] ?? 1) ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="send_to_members">
                                                        Send to Members
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="col-md-12 mb-3">
                                                <div class="form-check mb-2">
                                                    <input class="form-check-input" type="checkbox" name="send_to_custom" id="send_to_custom" 
                                                           <?php echo ($settings['send_to_custom'] ?? 0) ? 'checked' : ''; ?>
                                                           onchange="document.getElementById('custom_emails').disabled = !this.checked;">
                                                    <label class="form-check-label" for="send_to_custom">
                                                        Send to Custom Email List
                                                    </label>
                                                </div>
                                                <textarea class="form-control" name="custom_emails" id="custom_emails" rows="3" 
                                                          placeholder="Enter email addresses separated by commas" 
                                                          <?php echo ($settings['send_to_custom'] ?? 0) ? '' : 'disabled'; ?>><?php echo htmlspecialchars($settings['custom_emails'] ?? ''); ?></textarea>
                                                <small class="text-muted">Separate multiple emails with commas</small>
                                            </div>
                                        </div>

                                        <hr>

                                        <h5 class="mb-3">Email Template</h5>
                                        <div class="row">
                                            <div class="col-md-12 mb-3">
                                                <label for="template_id" class="form-label">Select Template</label>
                                                <select class="form-control" name="template_id" id="template_id">
                                                    <option value="">-- Use Default Template --</option>
                                                    <?php foreach ($templates as $template): ?>
                                                    <option value="<?php echo $template['id']; ?>" 
                                                            <?php echo ($settings['template_id'] ?? null) == $template['id'] ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($template['name']); ?>
                                                    </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <small class="text-muted">You can manage templates <a href="email_templates.php">here</a></small>
                                            </div>
                                        </div>

                                        <hr>

                                        <h5 class="mb-3">Options</h5>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="send_only_published" id="send_only_published" 
                                                           <?php echo ($settings['send_only_published'] ?? 1) ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="send_only_published">
                                                        Send only when status = 'published'
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="send_immediately" id="send_immediately" 
                                                           <?php echo ($settings['send_immediately'] ?? 1) ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="send_immediately">
                                                        Send immediately (no scheduling)
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="include_images" id="include_images" 
                                                           <?php echo ($settings['include_images'] ?? 1) ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="include_images">
                                                        Include images in email
                                                    </label>
                                                </div>
                                            </div>
                                        </div>

                                        <hr>
                                        
                                        <h5 class="mb-3">Test Email</h5>
                                        <div class="row">
                                            <div class="col-md-8 mb-3">
                                                <label for="test_email" class="form-label">Test Email Address</label>
                                                <input type="email" class="form-control" id="test_email" 
                                                       placeholder="Enter email address to send test email" 
                                                       value="<?php echo htmlspecialchars($_SESSION['email'] ?? ''); ?>">
                                                <small class="text-muted">Send a test email using the current automation settings and template</small>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">&nbsp;</label>
                                                <button type="button" class="btn btn-info d-block w-100" id="test-email-btn">
                                                    <i class="ri-mail-send-line"></i> Send Test Email
                                                </button>
                                            </div>
                                        </div>
                                        <div id="test-email-result" class="mt-2"></div>

                                        <div class="row mt-3">
                                            <div class="col-12">
                                                <button type="submit" name="save_settings" class="btn btn-primary">
                                                    <i class="ri-save-line"></i> Save Settings
                                                </button>
                                                <a href="email_automation_logs.php" class="btn btn-secondary">
                                                    <i class="ri-file-list-line"></i> View Logs
                                                </a>
                                            </div>
                                        </div>
                                    </form>
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

    <script>
        $(document).ready(function() {
            $('#test-email-btn').on('click', function() {
                const testEmail = $('#test_email').val();
                const contentType = '<?php echo htmlspecialchars($current_type); ?>';
                const btn = $(this);
                const resultDiv = $('#test-email-result');
                
                if (!testEmail) {
                    resultDiv.html('<div class="alert alert-warning">Please enter an email address</div>');
                    return;
                }
                
                // Validate email format
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(testEmail)) {
                    resultDiv.html('<div class="alert alert-warning">Please enter a valid email address</div>');
                    return;
                }
                
                // Disable button and show loading
                btn.prop('disabled', true).html('<i class="ri-loader-4-line spin"></i> Sending...');
                resultDiv.html('<div class="alert alert-info">Sending test email...</div>');
                
                // Send AJAX request
                $.ajax({
                    url: 'include/test_automation_email.php',
                    method: 'POST',
                    data: {
                        content_type: contentType,
                        test_email: testEmail
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            resultDiv.html('<div class="alert alert-success">' + response.message + '</div>');
                        } else {
                            resultDiv.html('<div class="alert alert-danger">' + response.message + '</div>');
                        }
                    },
                    error: function(xhr, status, error) {
                        resultDiv.html('<div class="alert alert-danger">An error occurred: ' + error + '</div>');
                    },
                    complete: function() {
                        btn.prop('disabled', false).html('<i class="ri-mail-send-line"></i> Send Test Email');
                    }
                });
            });
        });
    </script>
    
    <style>
        .spin {
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
    </style>
</body>
</html>

