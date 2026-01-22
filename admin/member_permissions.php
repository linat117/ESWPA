<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: auth-login.php");
    exit();
}

include 'include/conn.php';
include 'header.php';

$success_message = '';
$error_message = '';

// Handle permission updates
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['member_id'])) {
    $member_id = intval($_POST['member_id']);
    $permissions = $_POST['permissions'] ?? [];
    
    // In a real system, you would have a member_permissions table
    // For now, we'll use a JSON field or notes
    $success_message = "Permissions updated successfully (Note: Implement permissions table for full functionality)";
}

// Get member details if member_id provided
$member = null;
$member_id = intval($_GET['member_id'] ?? 0);

if ($member_id > 0) {
    $stmt = $conn->prepare("SELECT * FROM registrations WHERE id = ?");
    $stmt->bind_param("i", $member_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $member = $result->fetch_assoc();
    }
    $stmt->close();
}

// Get all members
$membersQuery = "SELECT id, fullname, membership_id, email, approval_status FROM registrations WHERE approval_status = 'approved' ORDER BY fullname ASC";
$membersResult = $conn->query($membersQuery);
$all_members = $membersResult->fetch_all(MYSQLI_ASSOC);

// Available permissions (in a real system, these would come from a permissions table)
$available_permissions = [
    ['id' => 'access_resources', 'name' => 'Access Resources', 'description' => 'Can view and download resources', 'category' => 'Resources'],
    ['id' => 'upload_resources', 'name' => 'Upload Resources', 'description' => 'Can upload new resources', 'category' => 'Resources'],
    ['id' => 'create_research', 'name' => 'Create Research', 'description' => 'Can create research projects', 'category' => 'Research'],
    ['id' => 'collaborate_research', 'name' => 'Collaborate on Research', 'description' => 'Can collaborate on research projects', 'category' => 'Research'],
    ['id' => 'manage_events', 'name' => 'Manage Events', 'description' => 'Can create and manage events', 'category' => 'Events'],
    ['id' => 'access_premium', 'name' => 'Premium Access', 'description' => 'Access to premium content', 'category' => 'Premium'],
    ['id' => 'export_data', 'name' => 'Export Data', 'description' => 'Can export their data', 'category' => 'Data'],
];
?>

<!DOCTYPE html>
<html lang="en">

<body>
    <div class="wrapper">
        <?php include 'sidebar.php'; ?>

        <div class="content-page">
            <div class="content">
                <div class="container-fluid">
                    <!-- Page Title -->
                    <div class="row">
                        <div class="col-12">
                            <div class="page-title-box d-flex justify-content-between align-items-center">
                                <h4 class="page-title">Member Permissions Management</h4>
                                <div>
                                    <a href="members_dashboard.php" class="btn btn-secondary me-2">
                                        <i class="ri-dashboard-line"></i> Dashboard
                                    </a>
                                    <a href="members_list.php" class="btn btn-secondary">
                                        <i class="ri-list-check"></i> All Members
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php
                    if ($success_message) {
                        echo '<div class="alert alert-success alert-dismissible fade show" role="alert">';
                        echo htmlspecialchars($success_message);
                        echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
                        echo '</div>';
                    }
                    if ($error_message) {
                        echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">';
                        echo htmlspecialchars($error_message);
                        echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
                        echo '</div>';
                    }
                    ?>

                    <div class="row">
                        <!-- Member Selection -->
                        <div class="col-lg-4">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Select Member</h4>
                                </div>
                                <div class="card-body">
                                    <form method="GET" class="mb-3">
                                        <div class="mb-3">
                                            <label class="form-label">Member</label>
                                            <select name="member_id" class="form-select" onchange="this.form.submit()">
                                                <option value="">-- Select a member --</option>
                                                <?php foreach ($all_members as $m): ?>
                                                    <option value="<?php echo $m['id']; ?>" <?php echo $member_id == $m['id'] ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($m['fullname']); ?> 
                                                        (<?php echo htmlspecialchars($m['membership_id']); ?>)
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </form>

                                    <?php if ($member): ?>
                                        <div class="card bg-light">
                                            <div class="card-body">
                                                <h6><?php echo htmlspecialchars($member['fullname']); ?></h6>
                                                <p class="mb-1 small">
                                                    <strong>ID:</strong> <code><?php echo htmlspecialchars($member['membership_id']); ?></code>
                                                </p>
                                                <p class="mb-1 small">
                                                    <strong>Email:</strong> <?php echo htmlspecialchars($member['email']); ?>
                                                </p>
                                                <p class="mb-0 small">
                                                    <strong>Status:</strong> 
                                                    <span class="badge bg-success"><?php echo ucfirst($member['approval_status']); ?></span>
                                                </p>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <div class="card mt-3">
                                        <div class="card-header">
                                            <h4 class="header-title">Quick Actions</h4>
                                        </div>
                                        <div class="card-body">
                                            <div class="d-grid gap-2">
                                                <a href="member_profile.php?id=<?php echo $member_id; ?>" class="btn btn-primary">
                                                    <i class="ri-user-line"></i> View Profile
                                                </a>
                                                <a href="member_badges.php" class="btn btn-secondary">
                                                    <i class="ri-award-line"></i> Manage Badges
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Permissions Management -->
                        <div class="col-lg-8">
                            <?php if ($member): ?>
                                <div class="card">
                                    <div class="card-header">
                                        <h4 class="header-title">Manage Permissions for <?php echo htmlspecialchars($member['fullname']); ?></h4>
                                    </div>
                                    <div class="card-body">
                                        <form method="POST">
                                            <input type="hidden" name="member_id" value="<?php echo $member_id; ?>">
                                            
                                            <?php
                                            $categories = [];
                                            foreach ($available_permissions as $perm) {
                                                if (!isset($categories[$perm['category']])) {
                                                    $categories[$perm['category']] = [];
                                                }
                                                $categories[$perm['category']][] = $perm;
                                            }
                                            
                                            foreach ($categories as $category => $perms):
                                            ?>
                                                <div class="mb-4">
                                                    <h5 class="mb-3"><?php echo htmlspecialchars($category); ?></h5>
                                                    <div class="row g-2">
                                                        <?php foreach ($perms as $perm): ?>
                                                            <div class="col-md-6">
                                                                <div class="card border">
                                                                    <div class="card-body p-2">
                                                                        <div class="form-check">
                                                                            <input class="form-check-input" 
                                                                                   type="checkbox" 
                                                                                   name="permissions[]" 
                                                                                   value="<?php echo htmlspecialchars($perm['id']); ?>" 
                                                                                   id="perm_<?php echo htmlspecialchars($perm['id']); ?>">
                                                                            <label class="form-check-label" for="perm_<?php echo htmlspecialchars($perm['id']); ?>">
                                                                                <strong><?php echo htmlspecialchars($perm['name']); ?></strong>
                                                                                <br><small class="text-muted"><?php echo htmlspecialchars($perm['description']); ?></small>
                                                                            </label>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>

                                            <div class="d-flex gap-2">
                                                <button type="submit" class="btn btn-primary">
                                                    <i class="ri-save-line"></i> Save Permissions
                                                </button>
                                                <button type="button" class="btn btn-secondary" onclick="selectAllPermissions()">
                                                    <i class="ri-checkbox-line"></i> Select All
                                                </button>
                                                <button type="button" class="btn btn-secondary" onclick="clearAllPermissions()">
                                                    <i class="ri-checkbox-blank-line"></i> Clear All
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="card">
                                    <div class="card-body text-center py-5">
                                        <i class="ri-user-search-line fs-1 text-muted d-block mb-3"></i>
                                        <p class="text-muted">Select a member from the dropdown to manage their permissions</p>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <div class="card mt-3">
                                <div class="card-header">
                                    <h4 class="header-title">Information</h4>
                                </div>
                                <div class="card-body">
                                    <div class="alert alert-info">
                                        <i class="ri-information-line"></i> 
                                        <strong>Note:</strong> Permission management requires a database table structure. 
                                        To implement full functionality, create a `member_permissions` table with:
                                        <ul class="mb-0 mt-2">
                                            <li>id, member_id, permission_id, granted_at, granted_by</li>
                                        </ul>
                                    </div>
                                    <p class="text-muted small">
                                        Permissions control what actions members can perform in the system. 
                                        Default permissions are usually based on membership packages.
                                    </p>
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
    <script src="assets/js/app.min.js"></script>

    <script>
        function selectAllPermissions() {
            document.querySelectorAll('input[name="permissions[]"]').forEach(function(checkbox) {
                checkbox.checked = true;
            });
        }

        function clearAllPermissions() {
            document.querySelectorAll('input[name="permissions[]"]').forEach(function(checkbox) {
                checkbox.checked = false;
            });
        }
    </script>

</body>
</html>

