<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: auth-login.php");
    exit();
}

include 'header.php';
include 'include/conn.php';
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
                                <h4 class="page-title">Membership Packages</h4>
                                <a href="add_package.php" class="btn btn-primary">
                                    <i class="ri-add-circle-line"></i> Add Package
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">All Packages</h4>
                                    <p class="text-muted mb-0">Manage membership packages and their permissions</p>
                                </div>
                                <div class="card-body">
                                    <?php
                                    if (isset($_GET['success'])) {
                                        echo '<div class="alert alert-success alert-dismissible fade show" role="alert">';
                                        echo htmlspecialchars($_GET['success']);
                                        echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
                                        echo '</div>';
                                    }
                                    if (isset($_GET['error'])) {
                                        echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">';
                                        echo htmlspecialchars($_GET['error']);
                                        echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
                                        echo '</div>';
                                    }
                                    
                                    // Check if table exists
                                    $tableCheck = mysqli_query($conn, "SHOW TABLES LIKE 'membership_packages'");
                                    if (mysqli_num_rows($tableCheck) == 0) {
                                        echo '<div class="alert alert-warning">';
                                        echo '<strong>Note:</strong> Access control tables have not been created yet. Please run the migration: <code>Sql/migration_access_control.sql</code>';
                                        echo '</div>';
                                    } else {
                                        $query = "SELECT mp.*, pp.resource_access, pp.research_access, pp.max_research_projects, pp.max_resource_downloads,
                                                 (SELECT COUNT(*) FROM registrations WHERE package_id = mp.id) as member_count
                                                 FROM membership_packages mp
                                                 LEFT JOIN package_permissions pp ON mp.id = pp.package_id
                                                 ORDER BY mp.price ASC";
                                        $result = mysqli_query($conn, $query);
                                        
                                        if (mysqli_num_rows($result) > 0) {
                                            echo '<div class="table-responsive">';
                                            echo '<table id="packages-datatable" class="table table-striped dt-responsive nowrap w-100">';
                                            echo '<thead>';
                                            echo '<tr>';
                                            echo '<th>Name</th>';
                                            echo '<th>Price</th>';
                                            echo '<th>Duration</th>';
                                            echo '<th>Resource Access</th>';
                                            echo '<th>Research Access</th>';
                                            echo '<th>Members</th>';
                                            echo '<th>Status</th>';
                                            echo '<th>Action</th>';
                                            echo '</tr>';
                                            echo '</thead>';
                                            echo '<tbody>';
                                            
                                            while ($row = mysqli_fetch_assoc($result)) {
                                                $statusBadge = $row['is_active'] ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Inactive</span>';
                                                $duration = $row['duration_months'] ? $row['duration_months'] . ' months' : 'Lifetime';
                                                $price = $row['price'] ? 'ETB ' . number_format($row['price'], 2) : 'Free';
                                                
                                                echo '<tr>';
                                                echo '<td><strong>' . htmlspecialchars($row['name']) . '</strong><br>';
                                                echo '<small class="text-muted">' . htmlspecialchars($row['description'] ?? '') . '</small></td>';
                                                echo '<td>' . $price . '</td>';
                                                echo '<td>' . $duration . '</td>';
                                                echo '<td><span class="badge bg-info">' . htmlspecialchars($row['resource_access'] ?? 'none') . '</span></td>';
                                                echo '<td><span class="badge bg-info">' . htmlspecialchars($row['research_access'] ?? 'none') . '</span></td>';
                                                echo '<td><span class="badge bg-primary">' . $row['member_count'] . '</span></td>';
                                                echo '<td>' . $statusBadge . '</td>';
                                                echo '<td>';
                                                echo '<div class="btn-group" role="group">';
                                                echo '<a href="edit_package.php?id=' . $row['id'] . '" class="btn btn-sm btn-primary" title="Edit Package">';
                                                echo '<i class="ri-edit-line"></i> Edit';
                                                echo '</a>';
                                                echo '<a href="package_permissions.php?id=' . $row['id'] . '" class="btn btn-sm btn-info" title="Manage Permissions">';
                                                echo '<i class="ri-settings-3-line"></i> Permissions';
                                                echo '</a>';
                                                echo '</div>';
                                                echo '</td>';
                                                echo '</tr>';
                                            }
                                            
                                            echo '</tbody>';
                                            echo '</table>';
                                            echo '</div>';
                                        } else {
                                            echo '<div class="alert alert-info">No packages found. <a href="add_package.php">Create your first package</a></div>';
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
    <script src="assets/js/pages/datatable.init.js"></script>
    <script src="assets/js/app.min.js"></script>

    <script>
        $(document).ready(function() {
            $('#packages-datatable').DataTable({
                responsive: true,
                order: [[1, 'asc']] // Sort by price
            });
        });
    </script>
</body>
</html>

