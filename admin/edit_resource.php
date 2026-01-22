<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: auth-login.php");
    exit();
}

include 'include/conn.php';
include 'header.php';

// Get resource ID
$resource_id = intval($_GET['id'] ?? 0);

if ($resource_id <= 0) {
    header("Location: resources_list.php?error=Invalid resource ID");
    exit();
}

// Fetch resource data
$query = "SELECT * FROM resources WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $resource_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: resources_list.php?error=Resource not found");
    exit();
}

$resource = $result->fetch_assoc();
$stmt->close();
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
                                <h4 class="page-title">Edit Resource</h4>
                                <a href="resources_list.php" class="btn btn-secondary">
                                    <i class="ri-arrow-left-line"></i> Back to List
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Edit Resource</h4>
                                </div>
                                <div class="card-body">
                                    <?php
                                    if (isset($_GET['success'])) {
                                        echo '<div class="alert alert-success alert-dismissible fade show" role="alert">';
                                        echo 'Resource updated successfully!';
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

                                    <form action="include/update_resource.php" method="post" enctype="multipart/form-data">
                                        <input type="hidden" name="resource_id" value="<?php echo htmlspecialchars($resource['id']); ?>">
                                        
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="section" class="form-label">Section *</label>
                                                <input type="text" class="form-control" id="section" name="section" required 
                                                       value="<?php echo htmlspecialchars($resource['section']); ?>"
                                                       placeholder="e.g., Guidelines, Reports, Manuals">
                                            </div>

                                            <div class="col-md-6 mb-3">
                                                <label for="title" class="form-label">Title *</label>
                                                <input type="text" class="form-control" id="title" name="title" required 
                                                       value="<?php echo htmlspecialchars($resource['title']); ?>"
                                                       placeholder="Resource title">
                                            </div>

                                            <div class="col-md-6 mb-3">
                                                <label for="publication_date" class="form-label">Publication Date *</label>
                                                <input type="date" class="form-control" id="publication_date" name="publication_date" required
                                                       value="<?php echo htmlspecialchars($resource['publication_date']); ?>">
                                            </div>

                                            <div class="col-md-6 mb-3">
                                                <label for="author" class="form-label">Author *</label>
                                                <input type="text" class="form-control" id="author" name="author" required 
                                                       value="<?php echo htmlspecialchars($resource['author']); ?>"
                                                       placeholder="Author name">
                                            </div>

                                            <div class="col-12 mb-3">
                                                <label for="description" class="form-label">Description</label>
                                                <textarea class="form-control" id="description" name="description" rows="3" 
                                                          placeholder="Optional description of the resource"><?php echo htmlspecialchars($resource['description'] ?? ''); ?></textarea>
                                            </div>

                                            <div class="col-md-6 mb-3">
                                                <label for="status" class="form-label">Status *</label>
                                                <select class="form-control" id="status" name="status" required>
                                                    <option value="active" <?php echo (($resource['status'] ?? 'active') === 'active') ? 'selected' : ''; ?>>Active</option>
                                                    <option value="inactive" <?php echo (($resource['status'] ?? '') === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                                                    <option value="archived" <?php echo (($resource['status'] ?? '') === 'archived') ? 'selected' : ''; ?>>Archived</option>
                                                </select>
                                            </div>

                                            <div class="col-md-6 mb-3">
                                                <label for="access_level" class="form-label">Access Level *</label>
                                                <select class="form-control" id="access_level" name="access_level" required>
                                                    <option value="public" <?php echo (($resource['access_level'] ?? 'member') === 'public') ? 'selected' : ''; ?>>Public (Everyone)</option>
                                                    <option value="member" <?php echo (($resource['access_level'] ?? 'member') === 'member') ? 'selected' : ''; ?>>Member (Logged In)</option>
                                                    <option value="premium" <?php echo (($resource['access_level'] ?? '') === 'premium') ? 'selected' : ''; ?>>Premium (Premium Package)</option>
                                                    <option value="restricted" <?php echo (($resource['access_level'] ?? '') === 'restricted') ? 'selected' : ''; ?>>Restricted (Special Permission)</option>
                                                </select>
                                            </div>

                                            <div class="col-md-6 mb-3">
                                                <label for="tags" class="form-label">Tags</label>
                                                <input type="text" class="form-control" id="tags" name="tags" 
                                                       value="<?php echo htmlspecialchars($resource['tags'] ?? ''); ?>"
                                                       placeholder="Comma-separated tags (e.g., research, guidelines, report)">
                                                <small class="text-muted">Separate multiple tags with commas</small>
                                            </div>

                                            <div class="col-md-6 mb-3">
                                                <div class="form-check mt-4">
                                                    <input class="form-check-input" type="checkbox" id="featured" name="featured" value="1" 
                                                           <?php echo (isset($resource['featured']) && $resource['featured'] == 1) ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="featured">
                                                        Featured Resource
                                                    </label>
                                                    <small class="d-block text-muted">Check to feature this resource prominently</small>
                                                </div>
                                            </div>

                                            <div class="col-12 mb-3">
                                                <label for="pdf_file" class="form-label">PDF File</label>
                                                <input type="file" class="form-control" id="pdf_file" name="pdf_file" 
                                                       accept=".pdf" onchange="validatePDF(this)">
                                                <small class="text-muted">
                                                    Current file: 
                                                    <?php if (!empty($resource['pdf_file'])): ?>
                                                        <a href="../<?php echo htmlspecialchars($resource['pdf_file']); ?>" target="_blank">
                                                            <?php echo htmlspecialchars(basename($resource['pdf_file'])); ?>
                                                        </a>
                                                        <br>
                                                    <?php endif; ?>
                                                    Leave empty to keep current file. Only PDF files are allowed (Max size: 10MB)
                                                </small>
                                            </div>

                                            <div class="col-12">
                                                <button type="submit" class="btn btn-primary">
                                                    <i class="ri-save-line"></i> Update Resource
                                                </button>
                                                <a href="resources_list.php" class="btn btn-secondary">
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

    <script>
        function validatePDF(input) {
            const file = input.files[0];
            if (file) {
                if (file.type !== 'application/pdf') {
                    alert('Please select a PDF file.');
                    input.value = '';
                    return false;
                }
                if (file.size > 10 * 1024 * 1024) { // 10MB
                    alert('File size exceeds 10MB. Please select a smaller file.');
                    input.value = '';
                    return false;
                }
            }
        }
    </script>
</body>
</html>

<?php
$conn->close();
?>

