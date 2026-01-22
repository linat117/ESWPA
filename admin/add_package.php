<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: auth-login.php");
    exit();
}

include 'header.php';
include 'include/conn.php';

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = !empty($_POST['price']) ? floatval($_POST['price']) : null;
    $durationMonths = !empty($_POST['duration_months']) ? intval($_POST['duration_months']) : null;
    $features = trim($_POST['features'] ?? '');
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    
    // Package permissions
    $resourceAccess = $_POST['resource_access'] ?? 'basic';
    $researchAccess = $_POST['research_access'] ?? 'view';
    $maxResearchProjects = intval($_POST['max_research_projects'] ?? 0);
    $maxResourceDownloads = intval($_POST['max_resource_downloads'] ?? 0);
    $canCollaborate = isset($_POST['can_collaborate']) ? 1 : 0;
    $canUploadResources = isset($_POST['can_upload_resources']) ? 1 : 0;
    $canCreateResearch = isset($_POST['can_create_research']) ? 1 : 0;
    
    if (empty($name)) {
        $error = "Package name is required";
    } elseif (empty($slug)) {
        $error = "Package slug is required";
    } else {
        // Generate slug from name if not provided
        if (empty($slug)) {
            $slug = strtolower(preg_replace('/[^A-Za-z0-9-]+/', '-', $name));
        }
        
        // Check if slug already exists
        $checkQuery = "SELECT id FROM membership_packages WHERE slug = ?";
        $checkStmt = $conn->prepare($checkQuery);
        $checkStmt->bind_param("s", $slug);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        
        if ($checkResult->num_rows > 0) {
            $error = "A package with this slug already exists";
        } else {
            // Start transaction
            $conn->begin_transaction();
            
            try {
                // Insert package
                $insertQuery = "INSERT INTO membership_packages 
                               (name, slug, description, price, duration_months, features, is_active) 
                               VALUES (?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($insertQuery);
                $stmt->bind_param("sssdiss", $name, $slug, $description, $price, $durationMonths, $features, $isActive);
                
                if ($stmt->execute()) {
                    $packageId = $stmt->insert_id;
                    $stmt->close();
                    
                    // Insert package permissions
                    $permQuery = "INSERT INTO package_permissions 
                                 (package_id, resource_access, research_access, max_research_projects, 
                                  max_resource_downloads, can_collaborate, can_upload_resources, can_create_research) 
                                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                    $permStmt = $conn->prepare($permQuery);
                    $permStmt->bind_param("issiiiii", $packageId, $resourceAccess, $researchAccess, 
                                         $maxResearchProjects, $maxResourceDownloads, 
                                         $canCollaborate, $canUploadResources, $canCreateResearch);
                    
                    if ($permStmt->execute()) {
                        $conn->commit();
                        $success = "Package created successfully!";
                        header("Location: membership_packages.php?success=" . urlencode($success));
                        exit();
                    } else {
                        throw new Exception("Failed to create package permissions: " . $permStmt->error);
                    }
                    $permStmt->close();
                } else {
                    throw new Exception("Failed to create package: " . $stmt->error);
                }
            } catch (Exception $e) {
                $conn->rollback();
                $error = $e->getMessage();
            }
        }
        $checkStmt->close();
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
                                <h4 class="page-title">Add Membership Package</h4>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">New Membership Package</h4>
                                    <p class="text-muted mb-0">Create a new membership package with permissions</p>
                                </div>
                                <div class="card-body">
                                    <?php if ($error): ?>
                                        <div class="alert alert-danger alert-dismissible fade show">
                                            <?php echo htmlspecialchars($error); ?>
                                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                        </div>
                                    <?php endif; ?>

                                    <form method="POST">
                                        <h5 class="mb-3">Package Information</h5>
                                        
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Package Name *</label>
                                                <input type="text" name="name" class="form-control" required 
                                                       placeholder="e.g., Premium, Professional">
                                            </div>
                                            
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Slug *</label>
                                                <input type="text" name="slug" class="form-control" required 
                                                       placeholder="e.g., premium, professional">
                                                <small class="form-text text-muted">URL-friendly identifier (auto-generated from name if empty)</small>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Description</label>
                                            <textarea name="description" class="form-control" rows="3" 
                                                      placeholder="Package description and benefits"></textarea>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Price (ETB)</label>
                                                <input type="number" name="price" class="form-control" step="0.01" 
                                                       placeholder="0.00">
                                                <small class="form-text text-muted">Leave empty for free package</small>
                                            </div>
                                            
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Duration (Months)</label>
                                                <input type="number" name="duration_months" class="form-control" 
                                                       placeholder="12">
                                                <small class="form-text text-muted">Leave empty for lifetime</small>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Features</label>
                                            <textarea name="features" class="form-control" rows="3" 
                                                      placeholder="Comma-separated list of features"></textarea>
                                        </div>

                                        <div class="mb-3">
                                            <div class="form-check">
                                                <input type="checkbox" name="is_active" class="form-check-input" id="isActive" checked>
                                                <label class="form-check-label" for="isActive">Active</label>
                                            </div>
                                        </div>

                                        <hr class="my-4">
                                        <h5 class="mb-3">Package Permissions</h5>

                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Resource Access</label>
                                                <select name="resource_access" class="form-select">
                                                    <option value="none">None</option>
                                                    <option value="basic" selected>Basic</option>
                                                    <option value="premium">Premium</option>
                                                    <option value="all">All</option>
                                                </select>
                                            </div>
                                            
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Research Access</label>
                                                <select name="research_access" class="form-select">
                                                    <option value="none">None</option>
                                                    <option value="view" selected>View</option>
                                                    <option value="create">Create</option>
                                                    <option value="collaborate">Collaborate</option>
                                                    <option value="all">All</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Max Research Projects</label>
                                                <input type="number" name="max_research_projects" class="form-control" 
                                                       value="0" min="0">
                                                <small class="form-text text-muted">0 = unlimited</small>
                                            </div>
                                            
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Max Resource Downloads</label>
                                                <input type="number" name="max_resource_downloads" class="form-control" 
                                                       value="0" min="0">
                                                <small class="form-text text-muted">0 = unlimited</small>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <div class="form-check">
                                                <input type="checkbox" name="can_collaborate" class="form-check-input" id="canCollaborate">
                                                <label class="form-check-label" for="canCollaborate">Can Collaborate on Research</label>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <div class="form-check">
                                                <input type="checkbox" name="can_upload_resources" class="form-check-input" id="canUploadResources">
                                                <label class="form-check-label" for="canUploadResources">Can Upload Resources</label>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <div class="form-check">
                                                <input type="checkbox" name="can_create_research" class="form-check-input" id="canCreateResearch">
                                                <label class="form-check-label" for="canCreateResearch">Can Create Research Projects</label>
                                            </div>
                                        </div>

                                        <div class="d-flex gap-2">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="ri-save-line"></i> Create Package
                                            </button>
                                            <a href="membership_packages.php" class="btn btn-secondary">
                                                <i class="ri-close-line"></i> Cancel
                                            </a>
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
        // Auto-generate slug from name
        document.querySelector('input[name="name"]').addEventListener('input', function() {
            const slugInput = document.querySelector('input[name="slug"]');
            if (!slugInput.value || slugInput.dataset.autoGenerated === 'true') {
                const slug = this.value.toLowerCase()
                    .replace(/[^a-z0-9]+/g, '-')
                    .replace(/^-+|-+$/g, '');
                slugInput.value = slug;
                slugInput.dataset.autoGenerated = 'true';
            }
        });
        
        // Clear auto-generated flag when user manually edits slug
        document.querySelector('input[name="slug"]').addEventListener('input', function() {
            this.dataset.autoGenerated = 'false';
        });
    </script>
</body>
</html>

<?php
mysqli_close($conn);
?>

