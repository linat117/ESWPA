<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: auth-login.php");
    exit();
}

include 'include/conn.php';
include 'header.php';

// Admin permissions (stored as JSON array in user_roles.permissions)
$admin_permissions = [
    'manage_members'   => 'Manage Members',
    'manage_news'     => 'Manage News & Content',
    'manage_resources' => 'Manage Resources',
    'manage_research' => 'Manage Research',
    'manage_events'   => 'Manage Events',
    'manage_users'    => 'Manage Users & Roles',
    'manage_settings' => 'Manage Settings',
    'view_reports'    => 'View Reports',
    'manage_id_cards' => 'Manage ID Cards',
    'manage_email'    => 'Manage Email & Newsletter',
];

// Roles offered in UI (super_admin removed; existing super_admin users can be changed to Admin)
$available_roles = ['viewer' => 'Viewer', 'editor' => 'Editor', 'admin' => 'Admin'];

// Handle edit user
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_user'])) {
    $user_id = intval($_POST['user_id'] ?? 0);
    $username = trim($_POST['username'] ?? '');
    $role = in_array($_POST['role'] ?? '', ['viewer', 'editor', 'admin']) ? $_POST['role'] : 'viewer';
    $permissions = isset($_POST['permissions']) && is_array($_POST['permissions']) ? $_POST['permissions'] : [];
    $permissions = array_intersect($permissions, array_keys($admin_permissions));
    $perm_json = json_encode(array_values($permissions));

    $error = null;
    if ($user_id <= 0) {
        $error = 'Invalid user.';
    } elseif (strlen($username) < 3) {
        $error = 'Username must be at least 3 characters.';
    }
    if ($error) {
        header("Location: settings_users.php?error=" . urlencode($error));
        exit();
    }
    $checkUser = $conn->prepare("SELECT id FROM user WHERE username = ? AND id != ?");
    $checkUser->bind_param("si", $username, $user_id);
    $checkUser->execute();
    if ($checkUser->get_result()->num_rows > 0) {
        $checkUser->close();
        header("Location: settings_users.php?error=" . urlencode('Username already in use by another user.'));
        exit();
    }
    $checkUser->close();

    $conn->begin_transaction();
    try {
        $upd = $conn->prepare("UPDATE user SET username = ? WHERE id = ?");
        $upd->bind_param("si", $username, $user_id);
        $upd->execute();
        $upd->close();

        $pass = $_POST['password'] ?? '';
        if ($pass !== '') {
            if (strlen($pass) < 6) {
                throw new Exception('Password must be at least 6 characters.');
            }
            if ($pass !== ($_POST['confirm_password'] ?? '')) {
                throw new Exception('Passwords do not match.');
            }
            $hash = password_hash($pass, PASSWORD_DEFAULT);
            $pup = $conn->prepare("UPDATE user SET password = ? WHERE id = ?");
            $pup->bind_param("si", $hash, $user_id);
            $pup->execute();
            $pup->close();
        }

        $roleStmt = $conn->prepare("INSERT INTO user_roles (user_id, role, permissions) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE role = ?, permissions = ?, updated_at = NOW()");
        $roleStmt->bind_param("issss", $user_id, $role, $perm_json, $role, $perm_json);
        $roleStmt->execute();
        $roleStmt->close();

        $conn->commit();
        header("Location: settings_users.php?success=User updated successfully");
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        header("Location: settings_users.php?error=" . urlencode($e->getMessage()));
        exit();
    }
}

// Handle add new user
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_user'])) {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $role = in_array($_POST['role'] ?? '', ['viewer', 'editor', 'admin']) ? $_POST['role'] : 'viewer';

    $error = null;
    if (strlen($username) < 3) {
        $error = 'Username must be at least 3 characters.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    }
    if ($error) {
        header("Location: settings_users.php?error=" . urlencode($error));
        exit();
    }

    $check = $conn->prepare("SELECT id FROM user WHERE username = ?");
    $check->bind_param("s", $username);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        $check->close();
        header("Location: settings_users.php?error=" . urlencode('Username already exists.'));
        exit();
    }
    $check->close();

    $hashed = password_hash($password, PASSWORD_DEFAULT);
    $ins = $conn->prepare("INSERT INTO user (username, password) VALUES (?, ?)");
    $ins->bind_param("ss", $username, $hashed);
    if (!$ins->execute()) {
        header("Location: settings_users.php?error=" . urlencode('Failed to create user.'));
        exit();
    }
    $new_user_id = $ins->insert_id;
    $ins->close();

    $perm = json_encode([]);
    $roleStmt = $conn->prepare("INSERT INTO user_roles (user_id, role, permissions) VALUES (?, ?, ?)");
    $roleStmt->bind_param("iss", $new_user_id, $role, $perm);
    $roleStmt->execute();
    $roleStmt->close();

    header("Location: settings_users.php?success=User created successfully");
    exit();
}


// Get all users with their roles
$users_query = "SELECT u.id, u.username, ur.role, ur.permissions 
                FROM user u 
                LEFT JOIN user_roles ur ON u.id = ur.user_id 
                ORDER BY u.id";
$users_result = $conn->query($users_query);
$users_list = [];
while ($row = $users_result->fetch_assoc()) {
    $row['permissions_arr'] = [];
    if (!empty($row['permissions'])) {
        $dec = json_decode($row['permissions'], true);
        if (is_array($dec)) {
            $row['permissions_arr'] = $dec;
        }
    }
    $users_list[] = $row;
}
$current_user_id = (int) $_SESSION['user_id'];
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
                    <?php if (isset($_GET['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($_GET['error']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h4 class="header-title">Users & Roles</h4>
                                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                                        <i class="ri-user-add-line"></i> Add User
                                    </button>
                                </div>
                            </div>
                            <div class="card">
                                <div class="card-body">
                                    <table class="table table-striped datatable">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Username</th>
                                                <th>Current Role</th>
                                                <th>Role</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($users_list as $user): 
                                                $role_display = $user['role'] ?? 'No Role';
                                                $role_value = ($role_display === 'super_admin') ? 'admin' : $role_display; // map super_admin to admin in dropdown
                                            ?>
                                            <tr>
                                                <td><?php echo $user['id']; ?></td>
                                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php 
                                                        echo $role_display == 'super_admin' ? 'danger' : 
                                                            ($role_display == 'admin' ? 'primary' : 
                                                            ($role_display == 'editor' ? 'info' : 'secondary')); 
                                                    ?>">
                                                        <?php echo ucfirst(str_replace('_', ' ', $role_display)); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <button type="button" class="btn btn-primary edit-user-btn" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $user['id']; ?>" 
                                                    data-user-id="<?php echo $user['id']; ?>" 
                                                    data-username="<?php echo htmlspecialchars($user['username']); ?>" 
                                                    data-role="<?php echo $user['role']; ?>" 
                                                    data-permissions='<?php echo htmlspecialchars(json_encode($user['permissions_arr'])); ?>'>
                                                            <i class="ri-pencil-line"></i> Edit
                                                        </button>
                                                        <?php if ($user['id'] != $current_user_id): ?>
                                                        <a href="include/delete_user.php?id=<?php echo $user['id']; ?>" class="btn btn-danger" onclick="return confirm('Delete this user? This cannot be undone.');">
                                                            <i class="ri-delete-bin-line"></i> Delete
                                                        </a>
                                                        <?php else: ?>
                                                        <span class="btn btn-outline-secondary disabled" title="Cannot delete your own account"><i class="ri-delete-bin-line"></i> Delete</span>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Edit User Modals (moved outside table) -->
                    <?php foreach ($users_list as $user): 
                        $role_display = $user['role'] ?? 'No Role';
                        $role_value = ($role_display === 'super_admin') ? 'admin' : $role_display;
                    ?>
                    <div class="modal fade" id="editModal<?php echo $user['id']; ?>" tabindex="-1">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <form method="POST">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Edit User: <?php echo htmlspecialchars($user['username']); ?></h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="edit_username_<?php echo $user['id']; ?>" class="form-label">Username</label>
                                                <input type="text" class="form-control" id="edit_username_<?php echo $user['id']; ?>" name="username" required minlength="3" value="<?php echo htmlspecialchars($user['username']); ?>">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="edit_role_<?php echo $user['id']; ?>" class="form-label">Role</label>
                                                <select class="form-control" id="edit_role_<?php echo $user['id']; ?>" name="role" required>
                                                    <?php foreach ($available_roles as $rval => $rlabel): ?>
                                                    <option value="<?php echo $rval; ?>" <?php echo $role_value === $rval ? 'selected' : ''; ?>><?php echo $rlabel; ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="edit_password_<?php echo $user['id']; ?>" class="form-label">New Password <small class="text-muted">(leave blank to keep current)</small></label>
                                                <input type="password" class="form-control" id="edit_password_<?php echo $user['id']; ?>" name="password" minlength="6" placeholder="Optional">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="edit_confirm_<?php echo $user['id']; ?>" class="form-label">Confirm New Password</label>
                                                <input type="password" class="form-control" id="edit_confirm_<?php echo $user['id']; ?>" name="confirm_password" minlength="6" placeholder="If changing password">
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Permissions</label>
                                            <div class="row g-2">
                                                <?php foreach ($admin_permissions as $perm_id => $perm_label): ?>
                                                <div class="col-md-6">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" name="permissions[]" value="<?php echo htmlspecialchars($perm_id); ?>" id="perm_<?php echo $user['id']; ?>_<?php echo $perm_id; ?>" <?php echo in_array($perm_id, $user['permissions_arr']) ? 'checked' : ''; ?>>
                                                        <label class="form-check-label" for="perm_<?php echo $user['id']; ?>_<?php echo $perm_id; ?>"><?php echo htmlspecialchars($perm_label); ?></label>
                                                    </div>
                                                </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" name="edit_user" class="btn btn-primary">Save Changes</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>

                    <!-- Add User Modal -->
                    <div class="modal fade" id="addUserModal" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form method="POST">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Add New User</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <label for="new_username" class="form-label">Username</label>
                                            <input type="text" class="form-control" id="new_username" name="username" required
                                                   minlength="3" placeholder="At least 3 characters"
                                                   value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                                        </div>
                                        <div class="mb-3">
                                            <label for="new_password" class="form-label">Password</label>
                                            <input type="password" class="form-control" id="new_password" name="password" required
                                                   minlength="6" placeholder="At least 6 characters">
                                        </div>
                                        <div class="mb-3">
                                            <label for="new_confirm_password" class="form-label">Confirm Password</label>
                                            <input type="password" class="form-control" id="new_confirm_password" name="confirm_password" required
                                                   minlength="6" placeholder="Re-enter password">
                                        </div>
                                        <div class="mb-3">
                                            <label for="new_role" class="form-label">Role</label>
                                            <select class="form-control" id="new_role" name="role">
                                                <?php foreach ($available_roles as $rval => $rlabel): ?>
                                                <option value="<?php echo $rval; ?>"><?php echo $rlabel; ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" name="add_user" class="btn btn-primary">Create User</button>
                                    </div>
                                </form>
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
            
            // Handle edit user button clicks
            $('.edit-user-btn').on('click', function(e) {
                e.preventDefault();
                try {
                    var userId = $(this).data('user-id');
                    var username = $(this).data('username');
                    var role = $(this).data('role');
                    var permissions = $(this).data('permissions');
                    
                    console.log('Edit button clicked - User ID:', userId, 'Username:', username, 'Role:', role, 'Permissions:', permissions);
                    
                    // Wait a bit before populating modal
                    setTimeout(function() {
                        // Populate edit modal with user data
                        $('#edit_username_' + userId).val(username);
                        $('#edit_role_' + userId).val(role);
                        
                        // Set permissions checkboxes only for this modal
                        $('#editModal' + userId + ' input[name="permissions[]"]').each(function() {
                            var permId = $(this).val();
                            if (permissions.includes(permId)) {
                                $(this).prop('checked', true);
                            } else {
                                $(this).prop('checked', false);
                            }
                        });
                    }, 100);
                } catch (error) {
                    console.error('Error in edit modal:', error);
                    alert('Error loading user data. Please check console for details.');
                }
            });
        });
    </script>
</body>
</html>

