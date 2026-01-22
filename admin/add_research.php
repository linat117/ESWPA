<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: auth-login.php");
    exit();
}

include 'include/conn.php';
include 'header.php';

// Get members for created_by dropdown
$membersQuery = "SELECT id, fullname, email FROM registrations ORDER BY fullname";
$membersResult = mysqli_query($conn, $membersQuery);
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
                                <h4 class="page-title">Add Research Project</h4>
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
                                    <h4 class="header-title">Create New Research Project</h4>
                                </div>
                                <div class="card-body">
                                    <?php
                                    if (isset($_GET['success'])) {
                                        echo '<div class="alert alert-success alert-dismissible fade show" role="alert">';
                                        echo 'Research project created successfully!';
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
                                        <input type="hidden" name="action" value="create">
                                        
                                        <div class="row">
                                            <div class="col-md-8 mb-3">
                                                <label for="title" class="form-label">Research Title *</label>
                                                <input type="text" class="form-control" id="title" name="title" required 
                                                       placeholder="Enter research project title">
                                            </div>

                                            <div class="col-md-4 mb-3">
                                                <label for="created_by" class="form-label">Created By (Member) *</label>
                                                <select class="form-control" id="created_by" name="created_by" required>
                                                    <option value="">Select Member</option>
                                                    <?php
                                                    while ($member = mysqli_fetch_assoc($membersResult)) {
                                                        echo '<option value="' . $member['id'] . '">' . 
                                                             htmlspecialchars($member['fullname'] . ' (' . $member['email'] . ')') . 
                                                             '</option>';
                                                    }
                                                    ?>
                                                </select>
                                            </div>

                                            <div class="col-12 mb-3">
                                                <label for="description" class="form-label">Description *</label>
                                                <textarea class="form-control" id="description" name="description" rows="4" required 
                                                          placeholder="Detailed description of the research project"></textarea>
                                            </div>

                                            <div class="col-12 mb-3">
                                                <label for="abstract" class="form-label">Abstract</label>
                                                <textarea class="form-control" id="abstract" name="abstract" rows="3" 
                                                          placeholder="Research abstract or summary"></textarea>
                                            </div>

                                            <div class="col-md-4 mb-3">
                                                <label for="category" class="form-label">Category</label>
                                                <input type="text" class="form-control" id="category" name="category" 
                                                       placeholder="e.g., Social Work, Psychology, Education">
                                            </div>

                                            <div class="col-md-4 mb-3">
                                                <label for="research_type" class="form-label">Research Type</label>
                                                <select class="form-control" id="research_type" name="research_type">
                                                    <option value="">Select Type</option>
                                                    <option value="thesis">Thesis</option>
                                                    <option value="journal_article">Journal Article</option>
                                                    <option value="case_study">Case Study</option>
                                                    <option value="survey">Survey</option>
                                                    <option value="experiment">Experiment</option>
                                                    <option value="review">Review</option>
                                                    <option value="other">Other</option>
                                                </select>
                                            </div>

                                            <div class="col-md-4 mb-3 status-field-col">
                                                <label for="status" class="form-label">Status *</label>
                                                <select class="form-control" id="status" name="status" required>
                                                    <option value="draft" selected>Draft</option>
                                                    <option value="in_progress">In Progress</option>
                                                    <option value="completed">Completed</option>
                                                    <option value="published">Published</option>
                                                    <option value="archived">Archived</option>
                                                </select>
                                            </div>

                                            <div class="col-md-4 mb-3">
                                                <label for="start_date" class="form-label">Start Date</label>
                                                <input type="date" class="form-control" id="start_date" name="start_date">
                                            </div>

                                            <div class="col-md-4 mb-3">
                                                <label for="end_date" class="form-label">End Date</label>
                                                <input type="date" class="form-control" id="end_date" name="end_date">
                                            </div>

                                            <div class="col-md-4 mb-3">
                                                <label for="publication_date" class="form-label">Publication Date</label>
                                                <input type="date" class="form-control" id="publication_date" name="publication_date">
                                            </div>

                                            <div class="col-md-6 mb-3">
                                                <label for="doi" class="form-label">DOI (Digital Object Identifier)</label>
                                                <input type="text" class="form-control" id="doi" name="doi" 
                                                       placeholder="e.g., 10.1000/xyz123">
                                            </div>

                                            <div class="col-md-6 mb-3">
                                                <label for="keywords" class="form-label">Keywords</label>
                                                <input type="text" class="form-control" id="keywords" name="keywords" 
                                                       placeholder="Comma-separated keywords">
                                                <small class="text-muted">Separate keywords with commas</small>
                                            </div>

                                            <div class="col-12 mb-3">
                                                <label for="research_files" class="form-label">Research Files</label>
                                                <input type="file" class="form-control" id="research_files" name="research_files[]" 
                                                       multiple accept=".pdf,.doc,.docx,.txt">
                                                <small class="text-muted">You can upload multiple files (PDF, DOC, DOCX, TXT). Max 10MB per file.</small>
                                            </div>

                                            <div class="col-12">
                                                <button type="submit" class="btn btn-primary">
                                                    <i class="ri-save-line"></i> Create Research Project
                                                </button>
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

    <style>
        /* Ensure status field displays correctly (similar to resources page) */
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
            border-radius: 0.5rem !important;
            box-sizing: border-box !important;
            margin: 0 !important;
            margin-left: 0 !important;
            margin-right: auto !important;
            height: calc(1.7em + 0.9rem + 2px) !important;
            appearance: auto !important;
            -webkit-appearance: menulist !important;
            -moz-appearance: menulist !important;
            float: none !important;
            position: relative !important;
            left: 0 !important;
            right: auto !important;
            margin-top: -2.2rem !important;
        }
        .status-field-col label[for="status"] {
            display: block !important;
            width: 100% !important;
            margin-bottom: 0.25rem !important;
            margin-top: 0 !important;
            margin-left: 0 !important;
            padding-left: 0 !important;
            padding-bottom: 0 !important;
        }
    </style>

    <script>
        // Ensure status field stays visible and doesn't get hidden by theme scripts
        document.addEventListener('DOMContentLoaded', function() {
            const statusSelect = document.getElementById('status');
            const statusLabel = document.querySelector('label[for="status"]');

            if (statusSelect) {
                statusSelect.style.visibility = 'visible';
                statusSelect.style.opacity = '1';
                statusSelect.style.display = 'block';

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

<?php
mysqli_close($conn);
?>

