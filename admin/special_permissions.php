<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: auth-login.php");
    exit();
}

include 'header.php';
include 'include/conn.php';

// Handle delete
if (isset($_GET['delete'])) {
    $permissionId = intval($_GET['delete']);
    $deleteQuery = "DELETE FROM special_permissions WHERE id = ?";
    $stmt = $conn->prepare($deleteQuery);
    $stmt->bind_param("i", $permissionId);
    if ($stmt->execute()) {
        $success = "Special permission deleted successfully!";
    } else {
        $error = "Failed to delete special permission";
    }
    $stmt->close();
}

// Handle toggle active
if (isset($_GET['toggle'])) {
    $permissionId = intval($_GET['toggle']);
    $toggleQuery = "UPDATE special_permissions SET is_active = NOT is_active WHERE id = ?";
    $stmt = $conn->prepare($toggleQuery);
    $stmt->bind_param("i", $permissionId);
    if ($stmt->execute()) {
        $success = "Permission status updated!";
    }
    $stmt->close();
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
                            <div class="page-title-box d-flex justify-content-between align-items-center">
                                <h4 class="page-title">Special Permissions</h4>
                                <a href="add_special_permission.php" class="btn btn-primary">
                                    <i class="ri-add-circle-line"></i> Add Permission
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">All Special Permissions</h4>
                                    <p class="text-muted mb-0">Manage special permissions granted to members</p>
                                </div>
                                <div class="card-body">
                                    <?php
                                    if (isset($success)) {
                                        echo '<div class="alert alert-success alert-dismissible fade show" role="alert">';
                                        echo htmlspecialchars($success);
                                        echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
                                        echo '</div>';
                                    }
                                    if (isset($error)) {
                                        echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">';
                                        echo htmlspecialchars($error);
                                        echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
                                        echo '</div>';
                                    }
                                    
                                    // Get filter parameters
                                    $filter_member = $_GET['member_id'] ?? '';
                                    $filter_type = $_GET['permission_type'] ?? '';
                                    $filter_active = $_GET['is_active'] ?? '';
                                    
                                    $where = [];
                                    $params = [];
                                    $types = '';
                                    
                                    if ($filter_member) {
                                        $where[] = "sp.member_id = ?";
                                        $params[] = $filter_member;
                                        $types .= 'i';
                                    }
                                    if ($filter_type) {
                                        $where[] = "sp.permission_type = ?";
                                        $params[] = $filter_type;
                                        $types .= 's';
                                    }
                                    if ($filter_active !== '') {
                                        $where[] = "sp.is_active = ?";
                                        $params[] = $filter_active;
                                        $types .= 'i';
                                    }
                                    
                                    $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
                                    
                                    // Check if table exists
                                    $tableCheck = mysqli_query($conn, "SHOW TABLES LIKE 'special_permissions'");
                                    if (mysqli_num_rows($tableCheck) == 0) {
                                        echo '<div class="alert alert-warning">';
                                        echo '<strong>Note:</strong> Special permissions table has not been created yet. Please run the migration: <code>Sql/migration_access_control.sql</code>';
                                        echo '</div>';
                                    } else {
                                        // Filters
                                        echo '<div class="row mb-3">';
                                        echo '<div class="col-md-12">';
                                        echo '<div class="card bg-light">';
                                        echo '<div class="card-body">';
                                        echo '<form method="GET" class="row g-3">';
                                        echo '<div class="col-md-3">';
                                        echo '<label class="form-label small">Member</label>';
                                        echo '<input type="text" name="member_id" class="form-control form-control-sm" value="' . htmlspecialchars($filter_member) . '" placeholder="Member ID">';
                                        echo '</div>';
                                        echo '<div class="col-md-3">';
                                        echo '<label class="form-label small">Permission Type</label>';
                                        echo '<select name="permission_type" class="form-select form-select-sm">';
                                        echo '<option value="">All Types</option>';
                                        echo '<option value="resource_access"' . ($filter_type === 'resource_access' ? ' selected' : '') . '>Resource Access</option>';
                                        echo '<option value="research_access"' . ($filter_type === 'research_access' ? ' selected' : '') . '>Research Access</option>';
                                        echo '<option value="unlimited_downloads"' . ($filter_type === 'unlimited_downloads' ? ' selected' : '') . '>Unlimited Downloads</option>';
                                        echo '<option value="research_creation"' . ($filter_type === 'research_creation' ? ' selected' : '') . '>Research Creation</option>';
                                        echo '<option value="collaboration"' . ($filter_type === 'collaboration' ? ' selected' : '') . '>Collaboration</option>';
                                        echo '<option value="admin_resources"' . ($filter_type === 'admin_resources' ? ' selected' : '') . '>Admin Resources</option>';
                                        echo '</select>';
                                        echo '</div>';
                                        echo '<div class="col-md-3">';
                                        echo '<label class="form-label small">Status</label>';
                                        echo '<select name="is_active" class="form-select form-select-sm">';
                                        echo '<option value="">All</option>';
                                        echo '<option value="1"' . ($filter_active === '1' ? ' selected' : '') . '>Active</option>';
                                        echo '<option value="0"' . ($filter_active === '0' ? ' selected' : '') . '>Inactive</option>';
                                        echo '</select>';
                                        echo '</div>';
                                        echo '<div class="col-md-3 d-flex align-items-end">';
                                        echo '<button type="submit" class="btn btn-sm btn-primary">Filter</button>';
                                        echo '<a href="special_permissions.php" class="btn btn-sm btn-secondary ms-2">Reset</a>';
                                        echo '</div>';
                                        echo '</form>';
                                        echo '</div>';
                                        echo '</div>';
                                        echo '</div>';
                                        echo '</div>';
                                        
                                        $query = "SELECT sp.*, r.fullname as member_name, r.email as member_email,
                                                 res.title as resource_title, rp.title as research_title,
                                                 u.username as granted_by_name
                                                 FROM special_permissions sp
                                                 LEFT JOIN registrations r ON sp.member_id = r.id
                                                 LEFT JOIN resources res ON sp.resource_id = res.id
                                                 LEFT JOIN research_projects rp ON sp.research_id = rp.id
                                                 LEFT JOIN user u ON sp.granted_by = u.id
                                                 $whereClause
                                                 ORDER BY sp.granted_at DESC";
                                        
                                        if (!empty($params)) {
                                            $stmt = $conn->prepare($query);
                                            $stmt->bind_param($types, ...$params);
                                            $stmt->execute();
                                            $result = $stmt->get_result();
                                        } else {
                                            $result = mysqli_query($conn, $query);
                                        }
                                        
                                        if (mysqli_num_rows($result) > 0) {
                                            echo '<div class="table-responsive">';
                                            echo '<table id="permissions-datatable" class="table table-striped dt-responsive nowrap w-100">';
                                            echo '<thead>';
                                            echo '<tr>';
                                            echo '<th>Member</th>';
                                            echo '<th>Permission Type</th>';
                                            echo '<th>Target</th>';
                                            echo '<th>Granted By</th>';
                                            echo '<th>Granted At</th>';
                                            echo '<th>Expires At</th>';
                                            echo '<th>Status</th>';
                                            echo '<th>Action</th>';
                                            echo '</tr>';
                                            echo '</thead>';
                                            echo '<tbody>';
                                            
                                            while ($row = mysqli_fetch_assoc($result)) {
                                                $statusBadge = $row['is_active'] ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Inactive</span>';
                                                
                                                // Determine target
                                                $target = '-';
                                                if ($row['resource_id']) {
                                                    $target = '<span class="badge bg-info">Resource: ' . htmlspecialchars($row['resource_title'] ?? 'ID ' . $row['resource_id']) . '</span>';
                                                } elseif ($row['research_id']) {
                                                    $target = '<span class="badge bg-info">Research: ' . htmlspecialchars($row['research_title'] ?? 'ID ' . $row['research_id']) . '</span>';
                                                } else {
                                                    $target = '<span class="badge bg-secondary">General</span>';
                                                }
                                                
                                                $expiresAt = $row['expires_at'] ? date('M d, Y', strtotime($row['expires_at'])) : 'Never';
                                                $grantedAt = date('M d, Y', strtotime($row['granted_at']));
                                                
                                                echo '<tr>';
                                                echo '<td><strong>' . htmlspecialchars($row['member_name'] ?? 'Unknown') . '</strong><br>';
                                                echo '<small class="text-muted">' . htmlspecialchars($row['member_email'] ?? '') . '</small></td>';
                                                echo '<td><span class="badge bg-primary">' . htmlspecialchars($row['permission_type']) . '</span></td>';
                                                echo '<td>' . $target . '</td>';
                                                echo '<td>' . htmlspecialchars($row['granted_by_name'] ?? 'System') . '</td>';
                                                echo '<td>' . $grantedAt . '</td>';
                                                echo '<td>' . $expiresAt . '</td>';
                                                echo '<td>' . $statusBadge . '</td>';
                                                echo '<td>';
                                                echo '<div class="btn-group" role="group">';
                                                echo '<a href="edit_special_permission.php?id=' . $row['id'] . '" class="btn btn-sm btn-primary" title="Edit Permission">';
                                                echo '<i class="ri-edit-line"></i> Edit';
                                                echo '</a>';
                                                echo '<a href="?toggle=' . $row['id'] . '" class="btn btn-sm btn-warning" title="Toggle Status">';
                                                echo '<i class="ri-toggle-line"></i> Toggle';
                                                echo '</a>';
                                                echo '<a href="?delete=' . $row['id'] . '" class="btn btn-sm btn-danger" title="Delete Permission" onclick="return confirm(\'Are you sure you want to delete this permission?\');">';
                                                echo '<i class="ri-delete-bin-line"></i> Delete';
                                                echo '</a>';
                                                echo '</div>';
                                                echo '</td>';
                                                echo '</tr>';
                                            }
                                            
                                            echo '</tbody>';
                                            echo '</table>';
                                            echo '</div>';
                                        } else {
                                            echo '<div class="alert alert-info">No special permissions found. <a href="add_special_permission.php">Create your first special permission</a></div>';
                                        }
                                        
                                        if (isset($stmt)) {
                                            $stmt->close();
                                        }
                                    }
                                    mysqli_close($conn);
                                    ?>
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
    <script src="assets/vendor/datatables.net-responsive/js/dataTables.responsive.min.js"></script>
    <script src="assets/vendor/datatables.net-responsive-bs5/js/responsive.bootstrap5.min.js"></script>
    <script src="assets/js/app.min.js"></script>

    <script>
        $(document).ready(function() {
            $('#permissions-datatable').DataTable({
                responsive: true,
                order: [[4, 'desc']],
                pageLength: 25,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]]
            });
        });
    </script>
</body>
</html>

