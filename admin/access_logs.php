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
                            <div class="page-title-box">
                                <h4 class="page-title">Access Logs</h4>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Access Logs</h4>
                                    <p class="text-muted mb-0">View all resource and research access attempts</p>
                                </div>
                                <div class="card-body">
                                    <?php
                                    // Check if table exists
                                    $tableCheck = mysqli_query($conn, "SHOW TABLES LIKE 'access_logs'");
                                    if (mysqli_num_rows($tableCheck) == 0) {
                                        echo '<div class="alert alert-warning">';
                                        echo '<strong>Note:</strong> Access logs table has not been created yet. Please run the migration: <code>Sql/migration_access_control.sql</code>';
                                        echo '</div>';
                                    } else {
                                        // Get filter parameters
                                        $filter_member = $_GET['member_id'] ?? '';
                                        $filter_resource = $_GET['resource_id'] ?? '';
                                        $filter_action = $_GET['action'] ?? '';
                                        $filter_granted = $_GET['granted'] ?? '';
                                        
                                        $where = [];
                                        $params = [];
                                        $types = '';
                                        
                                        if ($filter_member) {
                                            $where[] = "al.member_id = ?";
                                            $params[] = $filter_member;
                                            $types .= 'i';
                                        }
                                        if ($filter_resource) {
                                            $where[] = "al.resource_id = ?";
                                            $params[] = $filter_resource;
                                            $types .= 'i';
                                        }
                                        if ($filter_action) {
                                            $where[] = "al.action = ?";
                                            $params[] = $filter_action;
                                            $types .= 's';
                                        }
                                        if ($filter_granted !== '') {
                                            $where[] = "al.access_granted = ?";
                                            $params[] = $filter_granted;
                                            $types .= 'i';
                                        }
                                        
                                        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
                                        
                                        $query = "SELECT al.*, r.fullname as member_name, res.title as resource_title,
                                                 rp.title as research_title
                                                 FROM access_logs al
                                                 LEFT JOIN registrations r ON al.member_id = r.id
                                                 LEFT JOIN resources res ON al.resource_id = res.id
                                                 LEFT JOIN research_projects rp ON al.research_id = rp.id
                                                 $whereClause
                                                 ORDER BY al.accessed_at DESC
                                                 LIMIT 1000";
                                        
                                        if (!empty($params)) {
                                            $stmt = $conn->prepare($query);
                                            $stmt->bind_param($types, ...$params);
                                            $stmt->execute();
                                            $result = $stmt->get_result();
                                        } else {
                                            $result = mysqli_query($conn, $query);
                                        }
                                        
                                        // Statistics
                                        $statsQuery = "SELECT 
                                                      COUNT(*) as total,
                                                      SUM(CASE WHEN access_granted = 1 THEN 1 ELSE 0 END) as granted,
                                                      SUM(CASE WHEN access_granted = 0 THEN 1 ELSE 0 END) as denied
                                                      FROM access_logs";
                                        $statsResult = mysqli_query($conn, $statsQuery);
                                        $stats = mysqli_fetch_assoc($statsResult);
                                        ?>
                                        
                                        <!-- Statistics -->
                                        <div class="row mb-3">
                                            <div class="col-md-4">
                                                <div class="card bg-primary text-white">
                                                    <div class="card-body">
                                                        <h5>Total Logs</h5>
                                                        <h3><?php echo number_format($stats['total']); ?></h3>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="card bg-success text-white">
                                                    <div class="card-body">
                                                        <h5>Granted</h5>
                                                        <h3><?php echo number_format($stats['granted']); ?></h3>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="card bg-danger text-white">
                                                    <div class="card-body">
                                                        <h5>Denied</h5>
                                                        <h3><?php echo number_format($stats['denied']); ?></h3>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Filters -->
                                        <div class="card bg-light mb-3">
                                            <div class="card-body">
                                                <form method="GET" class="row g-3">
                                                    <div class="col-md-3">
                                                        <label class="form-label small">Action</label>
                                                        <select name="action" class="form-select form-select-sm">
                                                            <option value="">All Actions</option>
                                                            <option value="view" <?php echo $filter_action === 'view' ? 'selected' : ''; ?>>View</option>
                                                            <option value="download" <?php echo $filter_action === 'download' ? 'selected' : ''; ?>>Download</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <label class="form-label small">Access Status</label>
                                                        <select name="granted" class="form-select form-select-sm">
                                                            <option value="">All</option>
                                                            <option value="1" <?php echo $filter_granted === '1' ? 'selected' : ''; ?>>Granted</option>
                                                            <option value="0" <?php echo $filter_granted === '0' ? 'selected' : ''; ?>>Denied</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-3 d-flex align-items-end">
                                                        <button type="submit" class="btn btn-sm btn-primary">Filter</button>
                                                        <a href="access_logs.php" class="btn btn-sm btn-secondary ms-2">Reset</a>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                        
                                        <?php
                                        if (mysqli_num_rows($result) > 0) {
                                            echo '<div class="table-responsive">';
                                            echo '<table id="logs-datatable" class="table table-striped dt-responsive nowrap w-100">';
                                            echo '<thead>';
                                            echo '<tr>';
                                            echo '<th>Date/Time</th>';
                                            echo '<th>Member</th>';
                                            echo '<th>Resource</th>';
                                            echo '<th>Research</th>';
                                            echo '<th>Action</th>';
                                            echo '<th>Status</th>';
                                            echo '<th>Reason</th>';
                                            echo '<th>IP Address</th>';
                                            echo '</tr>';
                                            echo '</thead>';
                                            echo '<tbody>';
                                            
                                            while ($row = mysqli_fetch_assoc($result)) {
                                                $statusBadge = $row['access_granted'] 
                                                    ? '<span class="badge bg-success">Granted</span>' 
                                                    : '<span class="badge bg-danger">Denied</span>';
                                                
                                                echo '<tr>';
                                                echo '<td>' . date('M d, Y H:i:s', strtotime($row['accessed_at'])) . '</td>';
                                                echo '<td>' . htmlspecialchars($row['member_name'] ?? 'Guest') . '</td>';
                                                echo '<td>' . htmlspecialchars($row['resource_title'] ?? '-') . '</td>';
                                                echo '<td>' . htmlspecialchars($row['research_title'] ?? '-') . '</td>';
                                                echo '<td><span class="badge bg-info">' . htmlspecialchars($row['action']) . '</span></td>';
                                                echo '<td>' . $statusBadge . '</td>';
                                                echo '<td><small>' . htmlspecialchars($row['denial_reason'] ?? '-') . '</small></td>';
                                                echo '<td><small>' . htmlspecialchars($row['ip_address'] ?? '-') . '</small></td>';
                                                echo '</tr>';
                                            }
                                            
                                            echo '</tbody>';
                                            echo '</table>';
                                            echo '</div>';
                                        } else {
                                            echo '<div class="alert alert-info">No access logs found.</div>';
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
    <script src="assets/js/pages/datatable.init.js"></script>
    <script src="assets/js/app.min.js"></script>

    <script>
        $(document).ready(function() {
            $('#logs-datatable').DataTable({
                responsive: true,
                order: [[0, 'desc']], // Sort by date descending
                pageLength: 50
            });
        });
    </script>
</body>
</html>

