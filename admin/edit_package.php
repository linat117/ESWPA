<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: auth-login.php");
    exit();
}

include 'header.php';
include 'include/conn.php';

$packageId = intval($_GET['id'] ?? 0);
if ($packageId <= 0) {
    header("Location: membership_packages.php?error=Invalid package ID");
    exit();
}

$error = '';
$success = '';

// Get package data
$query = "SELECT mp.*, pp.resource_access, pp.research_access, pp.max_research_projects, 
                 pp.max_resource_downloads, pp.can_collaborate, pp.can_upload_resources, pp.can_create_research
          FROM membership_packages mp
          LEFT JOIN package_permissions pp ON mp.id = pp.package_id
          WHERE mp.id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $packageId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: membership_packages.php?error=Package not found");
    exit();
}

$package = $result->fetch_assoc();
$stmt->close();

// Handle form submission
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
        // Check if slug already exists (excluding current package)
        $checkQuery = "SELECT id FROM membership_packages WHERE slug = ? AND id != ?";
        $checkStmt = $conn->prepare($checkQuery);
        $checkStmt->bind_param("si", $slug, $packageId);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        
        if ($checkResult->num_rows > 0) {
            $error = "A package with this slug already exists";
        } else {
            // Start transaction
            $conn->begin_transaction();
            
            try {
                // Update package
                $updateQuery = "UPDATE membership_packages 
                               SET name = ?, slug = ?, description = ?, price = ?, 
                                   duration_months = ?, features = ?, is_active = ?
                               WHERE id = ?";
                $stmt = $conn->prepare($updateQuery);
                $stmt->bind_param("sssdissi", $name, $slug, $description, $price, $durationMonths, $features, $isActive, $packageId);
                
                if ($stmt->execute()) {
                    $stmt->close();
                    
                    // Update or insert package permissions
                    $checkPermQuery = "SELECT id FROM package_permissions WHERE package_id = ?";
                    $checkPermStmt = $conn->prepare($checkPermQuery);
                    $checkPermStmt->bind_param("i", $packageId);
                    $checkPermStmt->execute();
                    $permExists = $checkPermStmt->get_result()->num_rows > 0;
                    $checkPermStmt->close();
                    
                    if ($permExists) {
                        $permQuery = "UPDATE package_permissions 
                                     SET resource_access = ?, research_access = ?, max_research_projects = ?, 
                                         max_resource_downloads = ?, can_collaborate = ?, 
                                         can_upload_resources = ?, can_create_research = ?
                                     WHERE package_id = ?";
                        $permStmt = $conn->prepare($permQuery);
                        $permStmt->bind_param("ssiiiiii", $resourceAccess, $researchAccess, 
                                             $maxResearchProjects, $maxResourceDownloads, 
                                             $canCollaborate, $canUploadResources, $canCreateResearch, $packageId);
                    } else {
                        $permQuery = "INSERT INTO package_permissions 
                                     (package_id, resource_access, research_access, max_research_projects, 
                                      max_resource_downloads, can_collaborate, can_upload_resources, can_create_research) 
                                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                        $permStmt = $conn->prepare($permQuery);
                        $permStmt->bind_param("issiiiii", $packageId, $resourceAccess, $researchAccess, 
                                             $maxResearchProjects, $maxResourceDownloads, 
                                             $canCollaborate, $canUploadResources, $canCreateResearch);
                    }
                    
                    if ($permStmt->execute()) {
                        $conn->commit();
                        $success = "Package updated successfully!";
                        header("Location: membership_packages.php?success=" . urlencode($success));
                        exit();
                    } else {
                        throw new Exception("Failed to update package permissions: " . $permStmt->error);
                    }
                    $permStmt->close();
                } else {
                    throw new Exception("Failed to update package: " . $stmt->error);
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
                                <h4 class="page-title">Edit Membership Package</h4>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Edit Membership Package</h4>
                                    <p class="text-muted mb-0">Update package information and permissions</p>
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
                                                       value="<?php echo htmlspecialchars($package['name']); ?>">
                                            </div>
                                            
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Slug *</label>
                                                <input type="text" name="slug" class="form-control" required 
                                                       value="<?php echo htmlspecialchars($package['slug']); ?>">
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Description</label>
                                            <textarea name="description" class="form-control" rows="3"><?php echo htmlspecialchars($package['description'] ?? ''); ?></textarea>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Price (ETB)</label>
                                                <input type="number" name="price" class="form-control" step="0.01" 
                                                       value="<?php echo $package['price'] ?? ''; ?>">
                                            </div>
                                            
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Duration (Months)</label>
                                                <input type="number" name="duration_months" class="form-control" 
                                                       value="<?php echo $package['duration_months'] ?? ''; ?>">
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Features</label>
                                            <textarea name="features" class="form-control" rows="3"><?php echo htmlspecialchars($package['features'] ?? ''); ?></textarea>
                                        </div>

                                        <div class="mb-3">
                                            <div class="form-check">
                                                <input type="checkbox" name="is_active" class="form-check-input" id="isActive" 
                                                       <?php echo $package['is_active'] ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="isActive">Active</label>
                                            </div>
                                        </div>

                                        <hr class="my-4">
                                        <h5 class="mb-3">Package Permissions</h5>

                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Resource Access</label>
                                                <select name="resource_access" class="form-select">
                                                    <option value="none" <?php echo ($package['resource_access'] ?? '') === 'none' ? 'selected' : ''; ?>>None</option>
                                                    <option value="basic" <?php echo ($package['resource_access'] ?? 'basic') === 'basic' ? 'selected' : ''; ?>>Basic</option>
                                                    <option value="premium" <?php echo ($package['resource_access'] ?? '') === 'premium' ? 'selected' : ''; ?>>Premium</option>
                                                    <option value="all" <?php echo ($package['resource_access'] ?? '') === 'all' ? 'selected' : ''; ?>>All</option>
                                                </select>
                                            </div>
                                            
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Research Access</label>
                                                <select name="research_access" class="form-select">
                                                    <option value="none" <?php echo ($package['research_access'] ?? '') === 'none' ? 'selected' : ''; ?>>None</option>
                                                    <option value="view" <?php echo ($package['research_access'] ?? 'view') === 'view' ? 'selected' : ''; ?>>View</option>
                                                    <option value="create" <?php echo ($package['research_access'] ?? '') === 'create' ? 'selected' : ''; ?>>Create</option>
                                                    <option value="collaborate" <?php echo ($package['research_access'] ?? '') === 'collaborate' ? 'selected' : ''; ?>>Collaborate</option>
                                                    <option value="all" <?php echo ($package['research_access'] ?? '') === 'all' ? 'selected' : ''; ?>>All</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Max Research Projects</label>
                                                <input type="number" name="max_research_projects" class="form-control" 
                                                       value="<?php echo $package['max_research_projects'] ?? 0; ?>" min="0">
                                            </div>
                                            
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Max Resource Downloads</label>
                                                <input type="number" name="max_resource_downloads" class="form-control" 
                                                       value="<?php echo $package['max_resource_downloads'] ?? 0; ?>" min="0">
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <div class="form-check">
                                                <input type="checkbox" name="can_collaborate" class="form-check-input" id="canCollaborate" 
                                                       <?php echo ($package['can_collaborate'] ?? 0) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="canCollaborate">Can Collaborate on Research</label>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <div class="form-check">
                                                <input type="checkbox" name="can_upload_resources" class="form-check-input" id="canUploadResources" 
                                                       <?php echo ($package['can_upload_resources'] ?? 0) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="canUploadResources">Can Upload Resources</label>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <div class="form-check">
                                                <input type="checkbox" name="can_create_research" class="form-check-input" id="canCreateResearch" 
                                                       <?php echo ($package['can_create_research'] ?? 0) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="canCreateResearch">Can Create Research Projects</label>
                                            </div>
                                        </div>

                                        <div class="d-flex gap-2">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="ri-save-line"></i> Update Package
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
</body>
</html>

<?php
mysqli_close($conn);
?>

