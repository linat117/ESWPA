<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: auth-login.php");
    exit();
}

include 'include/conn.php';
include 'header.php';

// Handle role assignment
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['assign_role'])) {
    $user_id = intval($_POST['user_id']);
    $role = $_POST['role'];
    $permissions = json_encode($_POST['permissions'] ?? []);
    
    $query = "INSERT INTO user_roles (user_id, role, permissions) 
              VALUES (?, ?, ?) 
              ON DUPLICATE KEY UPDATE role = ?, permissions = ?, updated_at = NOW()";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("issss", $user_id, $role, $permissions, $role, $permissions);
    $stmt->execute();
    $stmt->close();
    
    header("Location: settings_users.php?success=Role assigned successfully");
    exit();
}

// Get all users with their roles
$users_query = "SELECT u.id, u.username, ur.role, ur.permissions 
                FROM user u 
                LEFT JOIN user_roles ur ON u.id = ur.user_id 
                ORDER BY u.id";
$users_result = $conn->query($users_query);
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
                                <h4 class="page-title">User Management</h4>
                                <a href="settings.php" class="btn btn-secondary">
                                    <i class="ri-arrow-left-line"></i> Back to Settings
                                </a>
                            </div>
                        </div>
                    </div>

                    <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($_GET['success']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Users & Roles</h4>
                                </div>
                                <div class="card-body">
                                    <table class="table table-striped datatable">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Username</th>
                                                <th>Current Role</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($user = $users_result->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo $user['id']; ?></td>
                                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php 
                                                        echo $user['role'] == 'super_admin' ? 'danger' : 
                                                            ($user['role'] == 'admin' ? 'primary' : 
                                                            ($user['role'] == 'editor' ? 'info' : 'secondary')); 
                                                    ?>">
                                                        <?php echo ucfirst(str_replace('_', ' ', $user['role'] ?? 'No Role')); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <button type="button" class="btn btn-sm btn-primary" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#roleModal<?php echo $user['id']; ?>">
                                                        <i class="ri-user-settings-line"></i> Assign Role
                                                    </button>
                                                </td>
                                            </tr>

                                            <!-- Role Assignment Modal -->
                                            <div class="modal fade" id="roleModal<?php echo $user['id']; ?>" tabindex="-1">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <form method="POST">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">Assign Role: <?php echo htmlspecialchars($user['username']); ?></h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                                <div class="mb-3">
                                                                    <label for="role<?php echo $user['id']; ?>" class="form-label">Role</label>
                                                                    <select class="form-control" id="role<?php echo $user['id']; ?>" name="role" required>
                                                                        <option value="viewer" <?php echo ($user['role'] ?? '') == 'viewer' ? 'selected' : ''; ?>>Viewer</option>
                                                                        <option value="editor" <?php echo ($user['role'] ?? '') == 'editor' ? 'selected' : ''; ?>>Editor</option>
                                                                        <option value="admin" <?php echo ($user['role'] ?? '') == 'admin' ? 'selected' : ''; ?>>Admin</option>
                                                                        <option value="super_admin" <?php echo ($user['role'] ?? '') == 'super_admin' ? 'selected' : ''; ?>>Super Admin</option>
                                                                    </select>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                <button type="submit" name="assign_role" class="btn btn-primary">Save Role</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
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
    <script src="assets/vendor/datatables.net/js/jquery.dataTables.min.js"></script>
    <script src="assets/vendor/datatables.net-bs5/js/dataTables.bootstrap5.min.js"></script>
    <script src="assets/js/app.min.js"></script>

    <script>
        $(document).ready(function() {
            $('.datatable').DataTable({
                responsive: true
            });
        });
    </script>
</body>
</html>

