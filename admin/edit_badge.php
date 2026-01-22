<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: auth-login.php");
    exit();
}

include 'header.php';
include 'include/conn.php';

$badgeId = intval($_GET['id'] ?? 0);
if ($badgeId <= 0) {
    header("Location: badge_permissions.php?error=Invalid badge ID");
    exit();
}

$error = '';
$success = '';

// Get badge data
$query = "SELECT * FROM badge_permissions WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $badgeId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: badge_permissions.php?error=Badge not found");
    exit();
}

$badge = $result->fetch_assoc();
$stmt->close();

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $badgeName = trim($_POST['badge_name'] ?? '');
    $resourceAccess = trim($_POST['resource_access'] ?? '');
    $researchAccess = trim($_POST['research_access'] ?? '');
    $specialFeatures = trim($_POST['special_features'] ?? '');
    $description = trim($_POST['description'] ?? '');
    
    if (empty($badgeName)) {
        $error = "Badge name is required";
    } else {
        // Check if badge name already exists (excluding current badge)
        $checkQuery = "SELECT id FROM badge_permissions WHERE badge_name = ? AND id != ?";
        $checkStmt = $conn->prepare($checkQuery);
        $checkStmt->bind_param("si", $badgeName, $badgeId);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        
        if ($checkResult->num_rows > 0) {
            $error = "A badge with this name already exists";
        } else {
            $updateQuery = "UPDATE badge_permissions 
                           SET badge_name = ?, resource_access = ?, research_access = ?, 
                               special_features = ?, description = ?
                           WHERE id = ?";
            $stmt = $conn->prepare($updateQuery);
            $stmt->bind_param("sssssi", $badgeName, $resourceAccess, $researchAccess, $specialFeatures, $description, $badgeId);
            
            if ($stmt->execute()) {
                $success = "Badge permission updated successfully!";
                header("Location: badge_permissions.php?success=" . urlencode($success));
                exit();
            } else {
                $error = "Failed to update badge permission: " . $stmt->error;
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
                                <h4 class="page-title">Edit Badge Permission</h4>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Edit Badge Permission</h4>
                                    <p class="text-muted mb-0">Update badge-based permission settings</p>
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
                                                   value="<?php echo htmlspecialchars($badge['badge_name']); ?>">
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Resource Access</label>
                                            <select name="resource_access" class="form-select">
                                                <option value="" <?php echo empty($badge['resource_access']) ? 'selected' : ''; ?>>None</option>
                                                <option value="basic" <?php echo $badge['resource_access'] === 'basic' ? 'selected' : ''; ?>>Basic</option>
                                                <option value="premium" <?php echo $badge['resource_access'] === 'premium' ? 'selected' : ''; ?>>Premium</option>
                                                <option value="all" <?php echo $badge['resource_access'] === 'all' ? 'selected' : ''; ?>>All</option>
                                            </select>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Research Access</label>
                                            <select name="research_access" class="form-select">
                                                <option value="" <?php echo empty($badge['research_access']) ? 'selected' : ''; ?>>None</option>
                                                <option value="view" <?php echo $badge['research_access'] === 'view' ? 'selected' : ''; ?>>View</option>
                                                <option value="create" <?php echo $badge['research_access'] === 'create' ? 'selected' : ''; ?>>Create</option>
                                                <option value="collaborate" <?php echo $badge['research_access'] === 'collaborate' ? 'selected' : ''; ?>>Collaborate</option>
                                                <option value="all" <?php echo $badge['research_access'] === 'all' ? 'selected' : ''; ?>>All</option>
                                            </select>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Special Features</label>
                                            <textarea name="special_features" class="form-control" rows="3"><?php echo htmlspecialchars($badge['special_features'] ?? ''); ?></textarea>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Description</label>
                                            <textarea name="description" class="form-control" rows="3"><?php echo htmlspecialchars($badge['description'] ?? ''); ?></textarea>
                                        </div>

                                        <div class="d-flex gap-2">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="ri-save-line"></i> Update Badge
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

