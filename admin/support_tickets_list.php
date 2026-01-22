<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: auth-login.php");
    exit();
}

include 'header.php';
include 'include/conn.php';

// Handle export
if (isset($_GET['export']) && $_GET['export'] == 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=support_tickets_' . date('Y-m-d') . '.csv');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Ticket #', 'Subject', 'Member', 'Category', 'Priority', 'Status', 'Assigned To', 'Created', 'Updated']);
    
    // Check if table exists
    $table_exists = false;
    $result = $conn->query("SHOW TABLES LIKE 'support_tickets'");
    if ($result && $result->num_rows > 0) {
        $table_exists = true;
        $query = "SELECT st.*, r.fullname, r.email 
                  FROM support_tickets st 
                  LEFT JOIN registrations r ON st.member_id = r.id 
                  ORDER BY st.created_at DESC";
        $result = $conn->query($query);
        
        while ($row = $result->fetch_assoc()) {
            fputcsv($output, [
                $row['ticket_number'],
                $row['subject'],
                $row['fullname'] ?? 'Guest',
                $row['category'] ?? 'N/A',
                $row['priority'],
                $row['status'],
                $row['assigned_to'] ?? 'Unassigned',
                $row['created_at'],
                $row['updated_at'] ?? ''
            ]);
        }
    }
    
    fclose($output);
    exit();
}

// Check if table exists
$table_exists = false;
$result = $conn->query("SHOW TABLES LIKE 'support_tickets'");
if ($result && $result->num_rows > 0) {
    $table_exists = true;
}
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
                            <div class="page-title-box d-flex justify-content-between align-items-center mb-3">
                                <h4 class="page-title">Support Tickets</h4>
                                <div>
                                    <a href="support_dashboard.php" class="btn btn-secondary me-2">
                                        <i class="ri-dashboard-line"></i> Dashboard
                                    </a>
                                    <?php if ($table_exists): ?>
                                        <a href="?export=csv" class="btn btn-success">
                                            <i class="ri-download-line"></i> Export CSV
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if (!$table_exists): ?>
                        <!-- Table Not Found Alert -->
                        <div class="alert alert-warning" role="alert">
                            <h5 class="alert-heading"><i class="ri-alert-line"></i> Support Tickets Table Not Found</h5>
                            <p>The <code>support_tickets</code> table does not exist in the database. Please create it to use the support system.</p>
                            <p class="mb-0">See <a href="support_dashboard.php">Support Dashboard</a> for the required table structure.</p>
                        </div>
                    <?php else: ?>

                    <!-- Quick Stats -->
                    <div class="row g-2 mb-3">
                        <?php
                        $totalQuery = "SELECT COUNT(*) as total FROM support_tickets";
                        $openQuery = "SELECT COUNT(*) as total FROM support_tickets WHERE status = 'open'";
                        $pendingQuery = "SELECT COUNT(*) as total FROM support_tickets WHERE status = 'pending'";
                        $resolvedQuery = "SELECT COUNT(*) as total FROM support_tickets WHERE status IN ('resolved', 'closed')";
                        
                        $total = mysqli_fetch_assoc(mysqli_query($conn, $totalQuery))['total'] ?? 0;
                        $open = mysqli_fetch_assoc(mysqli_query($conn, $openQuery))['total'] ?? 0;
                        $pending = mysqli_fetch_assoc(mysqli_query($conn, $pendingQuery))['total'] ?? 0;
                        $resolved = mysqli_fetch_assoc(mysqli_query($conn, $resolvedQuery))['total'] ?? 0;
                        ?>
                        <div class="col-md-3">
                            <div class="card bg-primary bg-opacity-10 border-primary">
                                <div class="card-body p-2">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-grow-1">
                                            <h6 class="mb-0 text-muted small">Total Tickets</h6>
                                            <h4 class="mb-0"><?php echo number_format($total); ?></h4>
                                        </div>
                                        <i class="ri-customer-service-2-line fs-1 text-primary opacity-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-warning bg-opacity-10 border-warning">
                                <div class="card-body p-2">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-grow-1">
                                            <h6 class="mb-0 text-muted small">Open</h6>
                                            <h4 class="mb-0"><?php echo number_format($open); ?></h4>
                                        </div>
                                        <i class="ri-time-line fs-1 text-warning opacity-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-info bg-opacity-10 border-info">
                                <div class="card-body p-2">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-grow-1">
                                            <h6 class="mb-0 text-muted small">Pending</h6>
                                            <h4 class="mb-0"><?php echo number_format($pending); ?></h4>
                                        </div>
                                        <i class="ri-hourglass-line fs-1 text-info opacity-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-success bg-opacity-10 border-success">
                                <div class="card-body p-2">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-grow-1">
                                            <h6 class="mb-0 text-muted small">Resolved</h6>
                                            <h4 class="mb-0"><?php echo number_format($resolved); ?></h4>
                                        </div>
                                        <i class="ri-checkbox-circle-line fs-1 text-success opacity-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Advanced Filters -->
                    <div class="row mb-3">
                        <div class="col-12">
                            <div class="card border">
                                <div class="card-body">
                                    <h6 class="card-title mb-3 d-flex justify-content-between align-items-center">
                                        <span><i class="ri-filter-line"></i> Advanced Filters & Search</span>
                                        <button class="btn btn-sm btn-link" type="button" data-bs-toggle="collapse" data-bs-target="#filterCollapse">
                                            <i class="ri-arrow-down-s-line"></i> Toggle
                                        </button>
                                    </h6>
                                    <div class="collapse show" id="filterCollapse">
                                        <form method="GET" class="row g-3">
                                            <div class="col-md-3">
                                                <label class="form-label small">Search (Ticket #/Subject)</label>
                                                <input type="text" name="search" class="form-control form-control-sm" 
                                                       value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>" 
                                                       placeholder="Search tickets...">
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label small">Status</label>
                                                <select name="status" class="form-select form-select-sm">
                                                    <option value="">All Status</option>
                                                    <option value="open" <?php echo ($_GET['status'] ?? '') == 'open' ? 'selected' : ''; ?>>Open</option>
                                                    <option value="pending" <?php echo ($_GET['status'] ?? '') == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                    <option value="in_progress" <?php echo ($_GET['status'] ?? '') == 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                                                    <option value="resolved" <?php echo ($_GET['status'] ?? '') == 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                                                    <option value="closed" <?php echo ($_GET['status'] ?? '') == 'closed' ? 'selected' : ''; ?>>Closed</option>
                                                </select>
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label small">Priority</label>
                                                <select name="priority" class="form-select form-select-sm">
                                                    <option value="">All Priorities</option>
                                                    <option value="low" <?php echo ($_GET['priority'] ?? '') == 'low' ? 'selected' : ''; ?>>Low</option>
                                                    <option value="medium" <?php echo ($_GET['priority'] ?? '') == 'medium' ? 'selected' : ''; ?>>Medium</option>
                                                    <option value="high" <?php echo ($_GET['priority'] ?? '') == 'high' ? 'selected' : ''; ?>>High</option>
                                                    <option value="urgent" <?php echo ($_GET['priority'] ?? '') == 'urgent' ? 'selected' : ''; ?>>Urgent</option>
                                                </select>
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label small">Category</label>
                                                <input type="text" name="category" class="form-control form-control-sm" 
                                                       value="<?php echo htmlspecialchars($_GET['category'] ?? ''); ?>" 
                                                       placeholder="Category...">
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label small">Date From</label>
                                                <input type="date" name="date_from" class="form-control form-control-sm" 
                                                       value="<?php echo htmlspecialchars($_GET['date_from'] ?? ''); ?>">
                                            </div>
                                            <div class="col-md-1 d-flex align-items-end gap-2">
                                                <button type="submit" class="btn btn-sm btn-primary w-100">
                                                    <i class="ri-filter-line"></i> Filter
                                                </button>
                                            </div>
                                        </form>
                                        <div class="row mt-2">
                                            <div class="col-md-12">
                                                <a href="support_tickets_list.php" class="btn btn-sm btn-secondary">
                                                    <i class="ri-refresh-line"></i> Reset Filters
                                                </a>
                                                <a href="?export=csv" class="btn btn-sm btn-success">
                                                    <i class="ri-download-line"></i> Export CSV
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tickets Table -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table id="ticketsTable" class="table table-striped table-bordered dt-responsive nowrap w-100">
                                            <thead>
                                                <tr>
                                                    <th>Ticket #</th>
                                                    <th>Subject</th>
                                                    <th>Member</th>
                                                    <th>Category</th>
                                                    <th>Priority</th>
                                                    <th>Status</th>
                                                    <th>Created</th>
                                                    <th>Updated</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                // Build query with filters
                                                $where = [];
                                                $params = [];
                                                $types = '';
                                                
                                                $statusFilter = $_GET['status'] ?? '';
                                                $priorityFilter = $_GET['priority'] ?? '';
                                                $search = $_GET['search'] ?? '';
                                                $category = $_GET['category'] ?? '';
                                                $date_from = $_GET['date_from'] ?? '';
                                                
                                                if ($statusFilter) {
                                                    $where[] = "st.status = ?";
                                                    $params[] = $statusFilter;
                                                    $types .= 's';
                                                }
                                                
                                                if ($priorityFilter) {
                                                    $where[] = "st.priority = ?";
                                                    $params[] = $priorityFilter;
                                                    $types .= 's';
                                                }
                                                
                                                if ($category) {
                                                    $where[] = "st.category LIKE ?";
                                                    $params[] = "%$category%";
                                                    $types .= 's';
                                                }
                                                
                                                if ($search) {
                                                    $where[] = "(st.ticket_number LIKE ? OR st.subject LIKE ?)";
                                                    $params[] = "%$search%";
                                                    $params[] = "%$search%";
                                                    $types .= 'ss';
                                                }
                                                
                                                if ($date_from) {
                                                    $where[] = "DATE(st.created_at) >= ?";
                                                    $params[] = $date_from;
                                                    $types .= 's';
                                                }
                                                
                                                $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
                                                
                                                $query = "SELECT st.*, r.fullname, r.email 
                                                         FROM support_tickets st 
                                                         LEFT JOIN registrations r ON st.member_id = r.id 
                                                         $whereClause
                                                         ORDER BY st.created_at DESC";
                                                
                                                $stmt = $conn->prepare($query);
                                                if (!empty($params)) {
                                                    $stmt->bind_param($types, ...$params);
                                                }
                                                $stmt->execute();
                                                $result = $stmt->get_result();
                                                
                                                while ($row = $result->fetch_assoc()):
                                                ?>
                                                    <tr>
                                                        <td><code><?php echo htmlspecialchars($row['ticket_number']); ?></code></td>
                                                        <td><strong><?php echo htmlspecialchars($row['subject']); ?></strong></td>
                                                        <td><?php echo htmlspecialchars($row['fullname'] ?? 'Guest'); ?></td>
                                                        <td><?php echo htmlspecialchars($row['category'] ?? 'N/A'); ?></td>
                                                        <td>
                                                            <span class="badge bg-<?php 
                                                                echo $row['priority'] == 'urgent' ? 'danger' : 
                                                                    ($row['priority'] == 'high' ? 'warning' : 
                                                                    ($row['priority'] == 'medium' ? 'info' : 'secondary')); 
                                                            ?>">
                                                                <?php echo ucfirst($row['priority']); ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <span class="badge bg-<?php 
                                                                echo $row['status'] == 'open' ? 'warning' : 
                                                                    ($row['status'] == 'resolved' || $row['status'] == 'closed' ? 'success' : 
                                                                    ($row['status'] == 'pending' ? 'info' : 'primary')); 
                                                            ?>">
                                                                <?php echo ucfirst(str_replace('_', ' ', $row['status'])); ?>
                                                            </span>
                                                        </td>
                                                        <td><?php echo date('M d, Y H:i', strtotime($row['created_at'])); ?></td>
                                                        <td><?php echo $row['updated_at'] ? date('M d, Y H:i', strtotime($row['updated_at'])) : '-'; ?></td>
                                                        <td>
                                                            <a href="support_ticket_detail.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-primary">
                                                                <i class="ri-eye-line"></i> View
                                                            </a>
                                                        </td>
                                                    </tr>
                                                <?php
                                                endwhile;
                                                $stmt->close();
                                                ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php endif; ?>

                </div>
            </div>

            <?php include 'footer.php'; ?>
        </div>
    </div>

    <!-- Vendor js -->
    <script src="assets/js/vendor.min.js"></script>
    
    <!-- DataTables js -->
    <script src="assets/vendor/datatables.net/js/jquery.dataTables.min.js"></script>
    <script src="assets/vendor/datatables.net-bs5/js/dataTables.bootstrap5.min.js"></script>
    <script src="assets/vendor/datatables.net-responsive/js/dataTables.responsive.min.js"></script>
    <script src="assets/vendor/datatables.net-responsive-bs5/js/responsive.bootstrap5.min.js"></script>
    
    <!-- App js -->
    <script src="assets/js/app.min.js"></script>

    <script>
        $(document).ready(function() {
            $('#ticketsTable').DataTable({
                responsive: true,
                order: [[6, 'desc']], // Sort by created date descending
                pageLength: 25,
                language: {
                    search: "_INPUT_",
                    searchPlaceholder: "Search tickets..."
                }
            });
        });
    </script>

</body>
</html>

