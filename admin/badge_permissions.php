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
    $badgeId = intval($_GET['delete']);
    $deleteQuery = "DELETE FROM badge_permissions WHERE id = ?";
    $stmt = $conn->prepare($deleteQuery);
    $stmt->bind_param("i", $badgeId);
    if ($stmt->execute()) {
        $success = "Badge permission deleted successfully!";
    } else {
        $error = "Failed to delete badge permission";
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
                                <h4 class="page-title">Badge Permissions</h4>
                                <a href="add_badge.php" class="btn btn-primary">
                                    <i class="ri-add-circle-line"></i> Add Badge
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">All Badge Permissions</h4>
                                    <p class="text-muted mb-0">Manage badge-based access permissions</p>
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
                                    
                                    // Check if table exists
                                    $tableCheck = mysqli_query($conn, "SHOW TABLES LIKE 'badge_permissions'");
                                    if (mysqli_num_rows($tableCheck) == 0) {
                                        echo '<div class="alert alert-warning">';
                                        echo '<strong>Note:</strong> Badge permissions table has not been created yet. Please run the migration: <code>Sql/migration_access_control.sql</code>';
                                        echo '</div>';
                                    } else {
                                        $query = "SELECT bp.*, 
                                                 (SELECT COUNT(*) FROM member_badges WHERE badge_name = bp.badge_name AND is_active = 1) as member_count
                                                 FROM badge_permissions bp
                                                 ORDER BY bp.badge_name ASC";
                                        $result = mysqli_query($conn, $query);
                                        
                                        if (mysqli_num_rows($result) > 0) {
                                            echo '<div class="table-responsive">';
                                            echo '<table id="badges-datatable" class="table table-striped dt-responsive nowrap w-100">';
                                            echo '<thead>';
                                            echo '<tr>';
                                            echo '<th>Badge Name</th>';
                                            echo '<th>Resource Access</th>';
                                            echo '<th>Research Access</th>';
                                            echo '<th>Special Features</th>';
                                            echo '<th>Members</th>';
                                            echo '<th>Description</th>';
                                            echo '<th>Action</th>';
                                            echo '</tr>';
                                            echo '</thead>';
                                            echo '<tbody>';
                                            
                                            while ($row = mysqli_fetch_assoc($result)) {
                                                $resourceAccess = $row['resource_access'] ? htmlspecialchars($row['resource_access']) : 'None';
                                                $researchAccess = $row['research_access'] ? htmlspecialchars($row['research_access']) : 'None';
                                                $specialFeatures = $row['special_features'] ? htmlspecialchars($row['special_features']) : '-';
                                                
                                                echo '<tr>';
                                                echo '<td><strong>' . htmlspecialchars($row['badge_name']) . '</strong></td>';
                                                echo '<td><span class="badge bg-info">' . $resourceAccess . '</span></td>';
                                                echo '<td><span class="badge bg-info">' . $researchAccess . '</span></td>';
                                                echo '<td><small>' . $specialFeatures . '</small></td>';
                                                echo '<td><span class="badge bg-primary">' . $row['member_count'] . '</span></td>';
                                                echo '<td><small class="text-muted">' . htmlspecialchars(substr($row['description'] ?? '', 0, 50)) . '...</small></td>';
                                                echo '<td>';
                                                echo '<div class="btn-group" role="group">';
                                                echo '<a href="edit_badge.php?id=' . $row['id'] . '" class="btn btn-sm btn-primary" title="Edit Badge">';
                                                echo '<i class="ri-edit-line"></i> Edit';
                                                echo '</a>';
                                                echo '<a href="?delete=' . $row['id'] . '" class="btn btn-sm btn-danger" title="Delete Badge" onclick="return confirm(\'Are you sure you want to delete this badge permission?\');">';
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
                                            echo '<div class="alert alert-info">No badge permissions found. <a href="add_badge.php">Create your first badge permission</a></div>';
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
            $('#badges-datatable').DataTable({
                responsive: true,
                order: [[0, 'asc']],
                pageLength: 25,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]]
            });
        });
    </script>
</body>
</html>

