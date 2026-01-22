<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: auth-login.php");
    exit();
}

include 'include/conn.php';
include 'header.php';

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    header("Location: email_templates.php?error=Invalid template ID");
    exit();
}

$query = "SELECT * FROM email_templates WHERE id = ? LIMIT 1";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$template = $result->fetch_assoc();
$stmt->close();

if (!$template) {
    header("Location: email_templates.php?error=Template not found");
    exit();
}
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
                                        <li class="breadcrumb-item"><a href="email_templates.php">Email Templates</a></li>
                                        <li class="breadcrumb-item active">Edit Template</li>
                                    </ol>
                                </div>
                                <h4 class="page-title">Edit Email Template</h4>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <form action="include/save_email_template.php" method="POST">
                                        <input type="hidden" name="action" value="update">
                                        <input type="hidden" name="id" value="<?php echo $template['id']; ?>">
                                        
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="name" class="form-label">Template Name *</label>
                                                <input type="text" class="form-control" name="name" id="name" 
                                                       value="<?php echo htmlspecialchars($template['name']); ?>" required>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="content_type" class="form-label">Content Type *</label>
                                                <select class="form-control" name="content_type" id="content_type" required>
                                                    <option value="news" <?php echo $template['content_type'] == 'news' ? 'selected' : ''; ?>>News</option>
                                                    <option value="blog" <?php echo $template['content_type'] == 'blog' ? 'selected' : ''; ?>>Blog</option>
                                                    <option value="report" <?php echo $template['content_type'] == 'report' ? 'selected' : ''; ?>>Report</option>
                                                    <option value="event" <?php echo $template['content_type'] == 'event' ? 'selected' : ''; ?>>Event</option>
                                                    <option value="resource" <?php echo $template['content_type'] == 'resource' ? 'selected' : ''; ?>>Resource</option>
                                                    <option value="general" <?php echo $template['content_type'] == 'general' ? 'selected' : ''; ?>>General</option>
                                                </select>
                                            </div>
                                            <div class="col-12 mb-3">
                                                <label for="subject" class="form-label">Email Subject *</label>
                                                <input type="text" class="form-control" name="subject" id="subject" required 
                                                       value="<?php echo htmlspecialchars($template['subject']); ?>">
                                                <small class="text-muted">Use {TITLE}, {AUTHOR}, {DATE}, etc. as variables</small>
                                            </div>
                                            <div class="col-12 mb-3">
                                                <label for="body" class="form-label">Email Body (HTML) *</label>
                                                <textarea class="form-control" name="body" id="body" rows="15" required><?php echo htmlspecialchars($template['body']); ?></textarea>
                                                <small class="text-muted">
                                                    Available variables: {TITLE}, {CONTENT}, {EXCERPT}, {AUTHOR}, {DATE}, {LINK}, {TYPE}, {IMAGE}, {UNSUBSCRIBE_LINK}
                                                </small>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" name="is_active" id="is_active" 
                                                           <?php echo $template['is_active'] ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="is_active">Active</label>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row mt-3">
                                            <div class="col-12">
                                                <button type="submit" class="btn btn-primary">
                                                    <i class="ri-save-line"></i> Update Template
                                                </button>
                                                <a href="email_templates.php" class="btn btn-secondary">Cancel</a>
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
</body>
</html>

