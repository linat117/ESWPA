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

// Get members list
$membersQuery = "SELECT id, fullname, email FROM registrations ORDER BY fullname ASC";
$membersResult = mysqli_query($conn, $membersQuery);

// Get resources list
$resourcesQuery = "SELECT id, title FROM resources ORDER BY title ASC";
$resourcesResult = mysqli_query($conn, $resourcesQuery);

// Get research list
$researchQuery = "SELECT id, title FROM research_projects ORDER BY title ASC";
$researchResult = mysqli_query($conn, $researchQuery);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $memberId = intval($_POST['member_id'] ?? 0);
    $permissionType = trim($_POST['permission_type'] ?? '');
    $resourceId = !empty($_POST['resource_id']) ? intval($_POST['resource_id']) : null;
    $researchId = !empty($_POST['research_id']) ? intval($_POST['research_id']) : null;
    $expiresAt = !empty($_POST['expires_at']) ? $_POST['expires_at'] : null;
    $notes = trim($_POST['notes'] ?? '');
    $grantedBy = $_SESSION['user_id'];
    
    if ($memberId <= 0) {
        $error = "Please select a member";
    } elseif (empty($permissionType)) {
        $error = "Permission type is required";
    } else {
        $insertQuery = "INSERT INTO special_permissions 
                       (member_id, permission_type, resource_id, research_id, granted_by, expires_at, notes, is_active) 
                       VALUES (?, ?, ?, ?, ?, ?, ?, 1)";
        $stmt = $conn->prepare($insertQuery);
        $stmt->bind_param("isiiiss", $memberId, $permissionType, $resourceId, $researchId, $grantedBy, $expiresAt, $notes);
        
        if ($stmt->execute()) {
            $success = "Special permission granted successfully!";
            header("Location: special_permissions.php?success=" . urlencode($success));
            exit();
        } else {
            $error = "Failed to grant permission: " . $stmt->error;
        }
        $stmt->close();
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
                                <h4 class="page-title">Add Special Permission</h4>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Grant Special Permission</h4>
                                    <p class="text-muted mb-0">Grant special access permissions to a member</p>
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
                                            <label class="form-label">Member *</label>
                                            <select name="member_id" class="form-select" required>
                                                <option value="">-- Select Member --</option>
                                                <?php while ($member = mysqli_fetch_assoc($membersResult)): ?>
                                                    <option value="<?php echo $member['id']; ?>">
                                                        <?php echo htmlspecialchars($member['fullname'] . ' (' . $member['email'] . ')'); ?>
                                                    </option>
                                                <?php endwhile; ?>
                                            </select>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Permission Type *</label>
                                            <select name="permission_type" class="form-select" required id="permissionType" onchange="updateTargetFields()">
                                                <option value="">-- Select Type --</option>
                                                <option value="resource_access">Resource Access</option>
                                                <option value="research_access">Research Access</option>
                                                <option value="unlimited_downloads">Unlimited Downloads</option>
                                                <option value="research_creation">Research Creation</option>
                                                <option value="collaboration">Collaboration</option>
                                                <option value="admin_resources">Admin Resources</option>
                                            </select>
                                        </div>

                                        <div class="mb-3" id="resourceField" style="display: none;">
                                            <label class="form-label">Resource</label>
                                            <select name="resource_id" class="form-select">
                                                <option value="">-- Select Resource (Optional) --</option>
                                                <?php 
                                                mysqli_data_seek($resourcesResult, 0);
                                                while ($resource = mysqli_fetch_assoc($resourcesResult)): 
                                                ?>
                                                    <option value="<?php echo $resource['id']; ?>">
                                                        <?php echo htmlspecialchars($resource['title']); ?>
                                                    </option>
                                                <?php endwhile; ?>
                                            </select>
                                            <small class="form-text text-muted">Leave empty for general resource access</small>
                                        </div>

                                        <div class="mb-3" id="researchField" style="display: none;">
                                            <label class="form-label">Research Project</label>
                                            <select name="research_id" class="form-select">
                                                <option value="">-- Select Research (Optional) --</option>
                                                <?php 
                                                mysqli_data_seek($researchResult, 0);
                                                while ($research = mysqli_fetch_assoc($researchResult)): 
                                                ?>
                                                    <option value="<?php echo $research['id']; ?>">
                                                        <?php echo htmlspecialchars($research['title']); ?>
                                                    </option>
                                                <?php endwhile; ?>
                                            </select>
                                            <small class="form-text text-muted">Leave empty for general research access</small>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Expires At</label>
                                            <input type="datetime-local" name="expires_at" class="form-control">
                                            <small class="form-text text-muted">Leave empty for permanent permission</small>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Notes</label>
                                            <textarea name="notes" class="form-control" rows="3" 
                                                      placeholder="Optional notes about this permission"></textarea>
                                        </div>

                                        <div class="d-flex gap-2">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="ri-save-line"></i> Grant Permission
                                            </button>
                                            <a href="special_permissions.php" class="btn btn-secondary">
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
        function updateTargetFields() {
            const permissionType = document.getElementById('permissionType').value;
            const resourceField = document.getElementById('resourceField');
            const researchField = document.getElementById('researchField');
            
            resourceField.style.display = 'none';
            researchField.style.display = 'none';
            
            if (permissionType === 'resource_access') {
                resourceField.style.display = 'block';
            } else if (permissionType === 'research_access') {
                researchField.style.display = 'block';
            }
        }
    </script>
</body>
</html>

<?php
mysqli_close($conn);
?>

