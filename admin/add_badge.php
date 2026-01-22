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
    $badgeName = trim($_POST['badge_name'] ?? '');
    $resourceAccess = trim($_POST['resource_access'] ?? '');
    $researchAccess = trim($_POST['research_access'] ?? '');
    $specialFeatures = trim($_POST['special_features'] ?? '');
    $description = trim($_POST['description'] ?? '');
    
    if (empty($badgeName)) {
        $error = "Badge name is required";
    } else {
        // Check if badge already exists
        $checkQuery = "SELECT id FROM badge_permissions WHERE badge_name = ?";
        $checkStmt = $conn->prepare($checkQuery);
        $checkStmt->bind_param("s", $badgeName);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        
        if ($checkResult->num_rows > 0) {
            $error = "A badge with this name already exists";
        } else {
            $insertQuery = "INSERT INTO badge_permissions (badge_name, resource_access, research_access, special_features, description) 
                           VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($insertQuery);
            $stmt->bind_param("sssss", $badgeName, $resourceAccess, $researchAccess, $specialFeatures, $description);
            
            if ($stmt->execute()) {
                $success = "Badge permission created successfully!";
                header("Location: badge_permissions.php?success=" . urlencode($success));
                exit();
            } else {
                $error = "Failed to create badge permission: " . $stmt->error;
            }
            $stmt->close();
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
                                <h4 class="page-title">Add Badge Permission</h4>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">New Badge Permission</h4>
                                    <p class="text-muted mb-0">Create a new badge-based permission</p>
                                </div>
                                <div class="card-body">
                                    <?php if ($error): ?>
                                        <div class="alert alert-danger alert-dismissible fade show">
                                            <?php echo htmlspecialchars($error); ?>
                                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                        </div>
                                    <?php endif; ?>

                                    <form method="POST">
                                        <div class="mb-3">
                                            <label class="form-label">Badge Name *</label>
                                            <input type="text" name="badge_name" class="form-control" required 
                                                   placeholder="e.g., Research Leader, Resource Expert">
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Resource Access</label>
                                            <select name="resource_access" class="form-select">
                                                <option value="">None</option>
                                                <option value="basic">Basic</option>
                                                <option value="premium">Premium</option>
                                                <option value="all">All</option>
                                            </select>
                                            <small class="form-text text-muted">Access level for resources</small>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Research Access</label>
                                            <select name="research_access" class="form-select">
                                                <option value="">None</option>
                                                <option value="view">View</option>
                                                <option value="create">Create</option>
                                                <option value="collaborate">Collaborate</option>
                                                <option value="all">All</option>
                                            </select>
                                            <small class="form-text text-muted">Access level for research projects</small>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Special Features</label>
                                            <textarea name="special_features" class="form-control" rows="3" 
                                                      placeholder="Comma-separated list of special features (e.g., unlimited_downloads, priority_support)"></textarea>
                                            <small class="form-text text-muted">Additional features granted by this badge</small>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Description</label>
                                            <textarea name="description" class="form-control" rows="3" 
                                                      placeholder="Description of this badge and its benefits"></textarea>
                                        </div>

                                        <div class="d-flex gap-2">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="ri-save-line"></i> Create Badge
                                            </button>
                                            <a href="badge_permissions.php" class="btn btn-secondary">
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

