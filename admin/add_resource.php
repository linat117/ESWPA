<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: auth-login.php");
    exit();
}

include 'include/conn.php';
include 'header.php';

$create_sec = "CREATE TABLE IF NOT EXISTS `resource_sections` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `display_order` INT(11) NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
@$conn->query($create_sec);

$sections = [];
$secRes = @$conn->query("SELECT id, name FROM resource_sections ORDER BY display_order ASC, name ASC");
if ($secRes && $secRes->num_rows > 0) {
    while ($r = $secRes->fetch_assoc()) {
        $sections[] = $r;
    }
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
                                <h4 class="page-title">Add Resource</h4>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Upload New Resource</h4>
                                </div>
                                <div class="card-body">
                                    <?php
                                    if (isset($_GET['success'])) {
                                        echo '<div class="alert alert-success alert-dismissible fade show" role="alert">';
                                        echo 'Resource uploaded successfully!';
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

                                    <form action="include/upload_resource.php" method="post" enctype="multipart/form-data">
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="section" class="form-label">Section *</label>
                                                <select class="form-control" id="section" name="section" required>
                                                    <option value="">Select section</option>
                                                    <?php foreach ($sections as $sec): ?>
                                                        <option value="<?php echo htmlspecialchars($sec['name']); ?>"><?php echo htmlspecialchars($sec['name']); ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <small class="text-muted">
                                                    <a href="resource_sections.php" target="_blank">Manage sections</a>
                                                </small>
                                            </div>

                                            <div class="col-md-6 mb-3">
                                                <label for="title" class="form-label">Title *</label>
                                                <input type="text" class="form-control" id="title" name="title" required 
                                                       placeholder="Resource title">
                                            </div>

                                            <div class="col-md-6 mb-3">
                                                <label for="publication_date" class="form-label">Publication Date *</label>
                                                <input type="date" class="form-control" id="publication_date" name="publication_date" required>
                                            </div>

                                            <div class="col-md-6 mb-3">
                                                <label for="author" class="form-label">Author *</label>
                                                <input type="text" class="form-control" id="author" name="author" required 
                                                       placeholder="Author name">
                                            </div>

                                            <div class="col-12 mb-3">
                                                <label for="description" class="form-label">Description</label>
                                                <textarea class="form-control" id="description" name="description" rows="3" 
                                                          placeholder="Optional description of the resource"></textarea>
                                            </div>

                                            <div class="col-md-6 mb-3 status-field-col">
                                                <label for="status" class="form-label">Status *</label>
                                                <select class="form-control" id="status" name="status" required>
                                                    <option value="active" selected>Active</option>
                                                    <option value="inactive">Inactive</option>
                                                    <option value="archived">Archived</option>
                                                </select>
                                            </div>

                                            <div class="col-md-6 mb-3">
                                                <label for="access_level" class="form-label">Access Level *</label>
                                                <select class="form-control" id="access_level" name="access_level" required>
                                                    <option value="public">Public (Everyone)</option>
                                                    <option value="member" selected>Member (Logged In)</option>
                                                    <option value="premium">Premium (Premium Package)</option>
                                                    <option value="restricted">Restricted (Special Permission)</option>
                                                </select>
                                            </div>

                                            <div class="col-md-6 mb-3">
                                                <label for="tags" class="form-label">Tags</label>
                                                <input type="text" class="form-control" id="tags" name="tags" 
                                                       placeholder="Comma-separated tags (e.g., research, guidelines, report)">
                                                <small class="text-muted">Separate multiple tags with commas</small>
                                            </div>

                                            <div class="col-md-6 mb-3">
                                                <div class="form-check mt-4">
                                                    <input class="form-check-input" type="checkbox" id="featured" name="featured" value="1">
                                                    <label class="form-check-label" for="featured">
                                                        Featured Resource
                                                    </label>
                                                    <small class="d-block text-muted">Check to feature this resource prominently</small>
                                                </div>
                                            </div>

                                            <div class="col-12 mb-3">
                                                <label for="pdf_file" class="form-label">PDF File *</label>
                                                <input type="file" class="form-control" id="pdf_file" name="pdf_file" required 
                                                       accept=".pdf" onchange="validatePDF(this)">
                                                <?php
                                                $phpUploadMax = ini_get('upload_max_filesize');
                                                $phpPostMax = ini_get('post_max_size');
                                                $maxAllowed = min($phpUploadMax, $phpPostMax);
                                                ?>
                                                <small class="text-muted">
                                                    Only PDF files are allowed. 
                                                    <strong>Max size: <?php echo $maxAllowed; ?></strong> 
                                                    (PHP limit: upload_max_filesize=<?php echo $phpUploadMax; ?>, post_max_size=<?php echo $phpPostMax; ?>)
                                                </small>
                                                <?php if ($maxAllowed < '10M'): ?>
                                                    <div class="alert alert-warning mt-2">
                                                        <small><strong>Note:</strong> Your PHP configuration limits uploads to <?php echo $maxAllowed; ?>. 
                                                        To allow larger files, increase <code>upload_max_filesize</code> and <code>post_max_size</code> in php.ini.</small>
                                                    </div>
                                                <?php endif; ?>
                                            </div>

                                            <div class="col-12">
                                                <button type="submit" class="btn btn-primary">
                                                    <i class="ri-upload-cloud-line"></i> Upload Resource
                                                </button>
                                                <a href="resources_list.php" class="btn btn-secondary">
                                                    <i class="ri-arrow-left-line"></i> Back to List
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

    <style>
        /* Ensure status field displays as full-width form control matching other fields */
        .status-field-col {
            padding-left: 15px !important;
            padding-right: 15px !important;
            margin-left: 0 !important;
           
        }
        .status-field-col #status {
            display: block !important;
            width: 100% !important;
            max-width: 100% !important;
            padding: 0.45rem 0.9rem !important;
            font-size: 0.875rem !important;
            line-height: 1.5 !important;
            color: var(--tz-body-color, #36404c) !important;
            background-color: var(--tz-secondary-bg, #fff) !important;
            border: 1px solid var(--tz-border-color, #dee2e6) !important;
            border-radius: 0.25rem !important;
            box-sizing: border-box !important;
            margin: 0 !important;
            margin-left: 0 !important;
            margin-right: auto !important;
            margin-top: -1.5rem !important;
            height: calc(1.5em + 0.9rem + 2px) !important;
            appearance: auto !important;
            -webkit-appearance: menulist !important;
            -moz-appearance: menulist !important;
            float: none !important;
            position: relative !important;
            left: 0 !important;
            right: auto !important;
        }
        .status-field-col label[for="status"] {
            display: block !important;
            width: 100% !important;
            margin-bottom: 0 !important;
            margin-top: 0 !important;
            margin-left: 0 !important;
            padding-left: 0 !important;
            padding-bottom: 0 !important;
        }
        .status-field-col #status {
            margin-top: -1.5rem !important;
            margin-bottom: 0 !important;
        }
    </style>

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
        
        // Ensure status field stays visible and doesn't flicker
        document.addEventListener('DOMContentLoaded', function() {
            const statusSelect = document.getElementById('status');
            const statusLabel = document.querySelector('label[for="status"]');
            
            if (statusSelect) {
                // Force visibility
                statusSelect.style.visibility = 'visible';
                statusSelect.style.opacity = '1';
                statusSelect.style.display = 'block';
                
                // Watch for any changes that might hide it
                const observer = new MutationObserver(function(mutations) {
                    mutations.forEach(function(mutation) {
                        if (mutation.type === 'attributes' && mutation.attributeName === 'style') {
                            if (statusSelect.style.visibility === 'hidden' || 
                                statusSelect.style.opacity === '0' || 
                                statusSelect.style.display === 'none') {
                                statusSelect.style.visibility = 'visible';
                                statusSelect.style.opacity = '1';
                                statusSelect.style.display = 'block';
                            }
                        }
                    });
                });
                
                observer.observe(statusSelect, {
                    attributes: true,
                    attributeFilter: ['style', 'class']
                });
            }
            
            if (statusLabel) {
                statusLabel.style.visibility = 'visible';
                statusLabel.style.opacity = '1';
                statusLabel.style.display = 'block';
            }
        });
    </script>
</body>
</html>

