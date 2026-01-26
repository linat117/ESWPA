<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: auth-login.php");
    exit();
}

include 'include/conn.php';
include 'header.php';

// Get research ID
$research_id = intval($_GET['id'] ?? 0);

if ($research_id <= 0) {
    header("Location: research_list.php?error=Invalid research ID");
    exit();
}

// Fetch research data
$query = "SELECT * FROM research_projects WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $research_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: research_list.php?error=Research project not found");
    exit();
}

$research = $result->fetch_assoc();
$stmt->close();

// Get members for created_by dropdown
$membersQuery = "SELECT id, fullname, email FROM registrations ORDER BY fullname";
$membersResult = mysqli_query($conn, $membersQuery);

// Ensure research_categories table exists and get categories
$categories = [];
$create_cat = "CREATE TABLE IF NOT EXISTS `research_categories` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `display_order` INT(11) NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
@$conn->query($create_cat);
$catRes = @$conn->query("SELECT id, name FROM research_categories ORDER BY display_order ASC, name ASC");
if ($catRes && $catRes->num_rows > 0) {
    while ($r = $catRes->fetch_assoc()) {
        $categories[] = $r;
    }
}
$current_cat = trim($research['category'] ?? '');
$cat_names = array_column($categories, 'name');
$include_current = $current_cat !== '' && !in_array($current_cat, $cat_names);
?>

<body>
    <div class="wrapper">
        <?php include 'sidebar.php'; ?>

        <div class="content-page">
            <div class="content">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-12">
                            <div class="page-title-box d-flex justify-content-between align-items-center">
                                <h4 class="page-title">Edit Research Project</h4>
                                <a href="research_list.php" class="btn btn-secondary">
                                    <i class="ri-arrow-left-line"></i> Back to List
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Edit Research Project</h4>
                                </div>
                                <div class="card-body">
                                    <?php
                                    if (isset($_GET['success'])) {
                                        echo '<div class="alert alert-success alert-dismissible fade show" role="alert">';
                                        echo 'Research project updated successfully!';
                                        echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
                                        echo '</div>';
                                    }
                                    if (isset($_GET['error'])) {
                                        echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">';
                                        echo htmlspecialchars($_GET['error']);
                                        echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
                                        echo '</div>';
                                    }
                                    ?>

                                    <form action="include/research_handler.php" method="post" enctype="multipart/form-data">
                                        <input type="hidden" name="action" value="update">
                                        <input type="hidden" name="research_id" value="<?php echo htmlspecialchars($research['id']); ?>">
                                        
                                        <div class="row">
                                            <div class="col-md-8 mb-3">
                                                <label for="title" class="form-label">Research Title *</label>
                                                <input type="text" class="form-control" id="title" name="title" required 
                                                       value="<?php echo htmlspecialchars($research['title']); ?>"
                                                       placeholder="Enter research project title">
                                            </div>

                                            <div class="col-md-4 mb-3">
                                                <label for="created_by" class="form-label">Created By (Member)</label>
                                                <input type="text" class="form-control" value="<?php 
                                                    $creatorQuery = "SELECT fullname FROM registrations WHERE id = " . $research['created_by'];
                                                    $creatorResult = mysqli_query($conn, $creatorQuery);
                                                    if ($creatorRow = mysqli_fetch_assoc($creatorResult)) {
                                                        echo htmlspecialchars($creatorRow['fullname']);
                                                    }
                                                ?>" disabled>
                                                <small class="text-muted">Cannot change creator</small>
                                            </div>

                                            <div class="col-12 mb-3">
                                                <label for="description" class="form-label">Description *</label>
                                                <textarea class="form-control" id="description" name="description" rows="4" required><?php echo htmlspecialchars($research['description']); ?></textarea>
                                            </div>

                                            <div class="col-12 mb-3">
                                                <label for="abstract" class="form-label">Abstract</label>
                                                <textarea class="form-control" id="abstract" name="abstract" rows="3"><?php echo htmlspecialchars($research['abstract'] ?? ''); ?></textarea>
                                            </div>

                                            <div class="col-md-4 mb-3">
                                                <label for="category" class="form-label">Category</label>
                                                <select class="form-control" id="category" name="category">
                                                    <option value="">Select category</option>
                                                    <?php if ($include_current): ?>
                                                        <option value="<?php echo htmlspecialchars($current_cat); ?>" selected><?php echo htmlspecialchars($current_cat); ?></option>
                                                    <?php endif; ?>
                                                    <?php foreach ($categories as $cat): ?>
                                                        <option value="<?php echo htmlspecialchars($cat['name']); ?>" <?php echo ($current_cat === $cat['name']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat['name']); ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <small class="text-muted">
                                                    <a href="research_categories.php" target="_blank">Manage categories</a>
                                                </small>
                                            </div>

                                            <div class="col-md-4 mb-3">
                                                <label for="research_type" class="form-label">Research Type</label>
                                                <select class="form-control" id="research_type" name="research_type">
                                                    <option value="">Select Type</option>
                                                    <option value="thesis" <?php echo ($research['research_type'] ?? '') === 'thesis' ? 'selected' : ''; ?>>Thesis</option>
                                                    <option value="journal_article" <?php echo ($research['research_type'] ?? '') === 'journal_article' ? 'selected' : ''; ?>>Journal Article</option>
                                                    <option value="case_study" <?php echo ($research['research_type'] ?? '') === 'case_study' ? 'selected' : ''; ?>>Case Study</option>
                                                    <option value="survey" <?php echo ($research['research_type'] ?? '') === 'survey' ? 'selected' : ''; ?>>Survey</option>
                                                    <option value="experiment" <?php echo ($research['research_type'] ?? '') === 'experiment' ? 'selected' : ''; ?>>Experiment</option>
                                                    <option value="review" <?php echo ($research['research_type'] ?? '') === 'review' ? 'selected' : ''; ?>>Review</option>
                                                    <option value="other" <?php echo ($research['research_type'] ?? '') === 'other' ? 'selected' : ''; ?>>Other</option>
                                                </select>
                                            </div>

                                            <div class="col-md-4 mb-3">
                                                <label for="status" class="form-label">Status *</label>
                                                <select class="form-control" id="status" name="status" required>
                                                    <option value="draft" <?php echo ($research['status'] ?? 'draft') === 'draft' ? 'selected' : ''; ?>>Draft</option>
                                                    <option value="in_progress" <?php echo ($research['status'] ?? '') === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                                                    <option value="completed" <?php echo ($research['status'] ?? '') === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                                    <option value="published" <?php echo ($research['status'] ?? '') === 'published' ? 'selected' : ''; ?>>Published</option>
                                                    <option value="archived" <?php echo ($research['status'] ?? '') === 'archived' ? 'selected' : ''; ?>>Archived</option>
                                                </select>
                                            </div>

                                            <div class="col-md-4 mb-3">
                                                <label for="start_date" class="form-label">Start Date</label>
                                                <input type="date" class="form-control" id="start_date" name="start_date" 
                                                       value="<?php echo htmlspecialchars($research['start_date'] ?? ''); ?>">
                                            </div>

                                            <div class="col-md-4 mb-3">
                                                <label for="end_date" class="form-label">End Date</label>
                                                <input type="date" class="form-control" id="end_date" name="end_date" 
                                                       value="<?php echo htmlspecialchars($research['end_date'] ?? ''); ?>">
                                            </div>

                                            <div class="col-md-4 mb-3">
                                                <label for="publication_date" class="form-label">Publication Date</label>
                                                <input type="date" class="form-control" id="publication_date" name="publication_date" 
                                                       value="<?php echo htmlspecialchars($research['publication_date'] ?? ''); ?>">
                                            </div>

                                            <div class="col-md-4 mb-3">
                                                <label for="doi" class="form-label">DOI</label>
                                                <input type="text" class="form-control" id="doi" name="doi" 
                                                       value="<?php echo htmlspecialchars($research['doi'] ?? ''); ?>"
                                                       placeholder="e.g., 10.1000/xyz123">
                                            </div>

                                            <div class="col-md-4 mb-3">
                                                <label for="keywords" class="form-label">Keywords</label>
                                                <input type="text" class="form-control" id="keywords" name="keywords" 
                                                       value="<?php echo htmlspecialchars($research['keywords'] ?? ''); ?>"
                                                       placeholder="Comma-separated keywords">
                                            </div>

                                            <div class="col-12 mb-3">
                                                <label class="form-label">Current Files</label>
                                                <?php
                                                $filesQuery = "SELECT * FROM research_files WHERE research_id = ?";
                                                $filesStmt = $conn->prepare($filesQuery);
                                                $filesStmt->bind_param("i", $research_id);
                                                $filesStmt->execute();
                                                $filesResult = $filesStmt->get_result();
                                                
                                                if ($filesResult->num_rows > 0) {
                                                    echo '<div class="list-group">';
                                                    while ($file = $filesResult->fetch_assoc()) {
                                                        echo '<div class="list-group-item d-flex justify-content-between align-items-center">';
                                                        echo '<span>' . htmlspecialchars($file['file_name']) . ' <small class="text-muted">(' . number_format($file['file_size'] / 1024, 2) . ' KB)</small></span>';
                                                        echo '<a href="../' . htmlspecialchars($file['file_path']) . '" target="_blank" class="btn btn-sm btn-info">View</a>';
                                                        echo '</div>';
                                                    }
                                                    echo '</div>';
                                                } else {
                                                    echo '<p class="text-muted">No files uploaded yet.</p>';
                                                }
                                                $filesStmt->close();
                                                ?>
                                            </div>

                                            <div class="col-12 mb-3">
                                                <label for="research_files" class="form-label">Add More Files</label>
                                                <input type="file" class="form-control" id="research_files" name="research_files[]" 
                                                       multiple accept=".pdf,.doc,.docx,.txt">
                                                <small class="text-muted">You can upload multiple files (PDF, DOC, DOCX, TXT). Max 10MB per file.</small>
                                            </div>

                                            <div class="col-12">
                                                <button type="submit" class="btn btn-primary">
                                                    <i class="ri-save-line"></i> Update Research Project
                                                </button>
                                                <a href="research_details.php?id=<?php echo $research_id; ?>" class="btn btn-info">
                                                    <i class="ri-eye-line"></i> View Details
                                                </a>
                                                <a href="research_list.php" class="btn btn-secondary">
                                                    <i class="ri-close-line"></i> Cancel
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

    <script src="assets/js/vendor.min.js"></script>
    <script src="assets/js/app.min.js"></script>
</body>
</html>

<?php
mysqli_close($conn);
?>

