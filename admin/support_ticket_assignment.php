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

// Handle assignment
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['assign_ticket'])) {
    $ticket_id = intval($_POST['ticket_id']);
    $assigned_to = !empty($_POST['assigned_to']) ? intval($_POST['assigned_to']) : null;
    
    // Check if table exists
    $table_exists = false;
    $result = $conn->query("SHOW TABLES LIKE 'support_tickets'");
    if ($result && $result->num_rows > 0) {
        $table_exists = true;
    }
    
    if ($table_exists) {
        if ($assigned_to) {
            $stmt = $conn->prepare("UPDATE support_tickets SET assigned_to = ?, updated_at = NOW() WHERE id = ?");
            $stmt->bind_param("ii", $assigned_to, $ticket_id);
        } else {
            $stmt = $conn->prepare("UPDATE support_tickets SET assigned_to = NULL, updated_at = NOW() WHERE id = ?");
            $stmt->bind_param("i", $ticket_id);
        }
        
        if ($stmt->execute()) {
            $success_message = "Ticket assignment updated successfully";
        } else {
            $error_message = "Failed to update ticket assignment";
        }
        $stmt->close();
    }
}

// Handle bulk assignment
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['bulk_assign'])) {
    $ticket_ids = $_POST['ticket_ids'] ?? [];
    $assigned_to = !empty($_POST['bulk_assigned_to']) ? intval($_POST['bulk_assigned_to']) : null;
    
    if (!empty($ticket_ids)) {
        $table_exists = false;
        $result = $conn->query("SHOW TABLES LIKE 'support_tickets'");
        if ($result && $result->num_rows > 0) {
            $table_exists = true;
        }
        
        if ($table_exists) {
            $placeholders = str_repeat('?,', count($ticket_ids) - 1) . '?';
            if ($assigned_to) {
                $stmt = $conn->prepare("UPDATE support_tickets SET assigned_to = ?, updated_at = NOW() WHERE id IN ($placeholders)");
                $types = 'i' . str_repeat('i', count($ticket_ids));
                $params = array_merge([$assigned_to], $ticket_ids);
            } else {
                $stmt = $conn->prepare("UPDATE support_tickets SET assigned_to = NULL, updated_at = NOW() WHERE id IN ($placeholders)");
                $types = str_repeat('i', count($ticket_ids));
                $params = $ticket_ids;
            }
            
            $stmt->bind_param($types, ...$params);
            if ($stmt->execute()) {
                $success_message = count($ticket_ids) . " tickets assigned successfully";
            } else {
                $error_message = "Failed to assign tickets";
            }
            $stmt->close();
        }
    }
}

// Check if table exists
$table_exists = false;
$result = $conn->query("SHOW TABLES LIKE 'support_tickets'");
if ($result && $result->num_rows > 0) {
    $table_exists = true;
}

// Get admin users for assignment dropdown
$admin_users = [];
$admin_result = $conn->query("SELECT id, username FROM user ORDER BY username");
while ($row = $admin_result->fetch_assoc()) {
    $admin_users[] = $row;
}

// Get unassigned tickets
$unassigned_tickets = [];
$all_tickets = [];
if ($table_exists) {
    $stmt = $conn->prepare("SELECT st.*, r.fullname, r.email 
                           FROM support_tickets st 
                           LEFT JOIN registrations r ON st.member_id = r.id 
                           WHERE st.assigned_to IS NULL AND st.status IN ('open', 'pending', 'in_progress')
                           ORDER BY st.priority DESC, st.created_at DESC");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $unassigned_tickets[] = $row;
    }
    $stmt->close();
    
    // Get all tickets with assignments
    $stmt = $conn->prepare("SELECT st.*, r.fullname, r.email, u.username as assigned_to_name
                           FROM support_tickets st 
                           LEFT JOIN registrations r ON st.member_id = r.id 
                           LEFT JOIN user u ON st.assigned_to = u.id
                           WHERE st.status IN ('open', 'pending', 'in_progress')
                           ORDER BY st.priority DESC, st.created_at DESC");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $all_tickets[] = $row;
    }
    $stmt->close();
}

// Assignment statistics
$assignment_stats = [
    'unassigned' => count($unassigned_tickets),
    'assigned' => 0,
    'by_admin' => []
];

if ($table_exists) {
    $assigned_count = $conn->query("SELECT COUNT(*) as total FROM support_tickets WHERE assigned_to IS NOT NULL AND status IN ('open', 'pending', 'in_progress')")->fetch_assoc()['total'] ?? 0;
    $assignment_stats['assigned'] = $assigned_count;
    
    // Tickets by admin
    $admin_stats_query = "SELECT u.id, u.username, COUNT(st.id) as ticket_count 
                         FROM user u 
                         LEFT JOIN support_tickets st ON u.id = st.assigned_to AND st.status IN ('open', 'pending', 'in_progress')
                         WHERE st.assigned_to IS NOT NULL
                         GROUP BY u.id, u.username
                         ORDER BY ticket_count DESC";
    $admin_stats_result = $conn->query($admin_stats_query);
    while ($row = $admin_stats_result->fetch_assoc()) {
        $assignment_stats['by_admin'][] = $row;
    }
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
                            <div class="page-title-box d-flex justify-content-between align-items-center">
                                <h4 class="page-title">Ticket Assignment</h4>
                                <div>
                                    <a href="support_dashboard.php" class="btn btn-secondary me-2">
                                        <i class="ri-dashboard-line"></i> Dashboard
                                    </a>
                                    <a href="support_tickets_list.php" class="btn btn-info">
                                        <i class="ri-list-check"></i> All Tickets
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

                    <?php if (!$table_exists): ?>
                        <!-- Table Not Found Alert -->
                        <div class="alert alert-warning" role="alert">
                            <h5 class="alert-heading"><i class="ri-alert-line"></i> Support Tickets Table Not Found</h5>
                            <p>The <code>support_tickets</code> table does not exist in the database.</p>
                            <p class="mb-0">See <a href="support_dashboard.php">Support Dashboard</a> for the required table structure.</p>
                        </div>
                    <?php else: ?>

                    <!-- Statistics Cards -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <div class="card bg-warning bg-opacity-10 border-warning">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-grow-1">
                                            <h6 class="text-muted mb-1">Unassigned Tickets</h6>
                                            <h3 class="mb-0"><?php echo number_format($assignment_stats['unassigned']); ?></h3>
                                            <small class="text-muted">Require assignment</small>
                                        </div>
                                        <i class="ri-user-unfollow-line fs-1 text-warning opacity-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-success bg-opacity-10 border-success">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-grow-1">
                                            <h6 class="text-muted mb-1">Assigned Tickets</h6>
                                            <h3 class="mb-0"><?php echo number_format($assignment_stats['assigned']); ?></h3>
                                            <small class="text-muted">Active assignments</small>
                                        </div>
                                        <i class="ri-user-follow-line fs-1 text-success opacity-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-info bg-opacity-10 border-info">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-grow-1">
                                            <h6 class="text-muted mb-1">Active Admins</h6>
                                            <h3 class="mb-0"><?php echo number_format(count($assignment_stats['by_admin'])); ?></h3>
                                            <small class="text-muted">With assigned tickets</small>
                                        </div>
                                        <i class="ri-team-line fs-1 text-info opacity-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Assignment by Admin -->
                    <?php if (!empty($assignment_stats['by_admin'])): ?>
                    <div class="row mb-3">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Tickets by Admin</h4>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-hover mb-0">
                                            <thead>
                                                <tr>
                                                    <th>Admin</th>
                                                    <th>Assigned Tickets</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($assignment_stats['by_admin'] as $admin): ?>
                                                    <tr>
                                                        <td><strong><?php echo htmlspecialchars($admin['username']); ?></strong></td>
                                                        <td><span class="badge bg-primary"><?php echo number_format($admin['ticket_count']); ?></span></td>
                                                        <td>
                                                            <a href="support_tickets_list.php?assigned_to=<?php echo $admin['id']; ?>" class="btn btn-sm btn-light">
                                                                <i class="ri-eye-line"></i> View Tickets
                                                            </a>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Unassigned Tickets -->
                    <div class="row mb-3">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h4 class="header-title mb-0">Unassigned Tickets</h4>
                                    <?php if (!empty($unassigned_tickets)): ?>
                                        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#bulkAssignModal">
                                            <i class="ri-user-add-line"></i> Bulk Assign
                                        </button>
                                    <?php endif; ?>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($unassigned_tickets)): ?>
                                        <p class="text-muted text-center py-4">No unassigned tickets</p>
                                    <?php else: ?>
                                        <div class="table-responsive">
                                            <table class="table table-hover mb-0">
                                                <thead>
                                                    <tr>
                                                        <th width="40">
                                                            <input type="checkbox" id="selectAll">
                                                        </th>
                                                        <th>Ticket #</th>
                                                        <th>Subject</th>
                                                        <th>Member</th>
                                                        <th>Priority</th>
                                                        <th>Status</th>
                                                        <th>Created</th>
                                                        <th>Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($unassigned_tickets as $ticket): ?>
                                                        <tr>
                                                            <td>
                                                                <input type="checkbox" name="ticket_ids[]" value="<?php echo $ticket['id']; ?>" class="ticket-checkbox">
                                                            </td>
                                                            <td><code><?php echo htmlspecialchars($ticket['ticket_number']); ?></code></td>
                                                            <td><strong><?php echo htmlspecialchars($ticket['subject']); ?></strong></td>
                                                            <td><?php echo htmlspecialchars($ticket['fullname'] ?? 'Guest'); ?></td>
                                                            <td>
                                                                <span class="badge bg-<?php 
                                                                    echo $ticket['priority'] == 'urgent' ? 'danger' : 
                                                                        ($ticket['priority'] == 'high' ? 'warning' : 
                                                                        ($ticket['priority'] == 'medium' ? 'info' : 'secondary')); 
                                                                ?>">
                                                                    <?php echo ucfirst($ticket['priority']); ?>
                                                                </span>
                                                            </td>
                                                            <td>
                                                                <span class="badge bg-<?php 
                                                                    echo $ticket['status'] == 'open' ? 'warning' : 
                                                                        ($ticket['status'] == 'pending' ? 'info' : 'primary'); 
                                                                ?>">
                                                                    <?php echo ucfirst(str_replace('_', ' ', $ticket['status'])); ?>
                                                                </span>
                                                            </td>
                                                            <td><?php echo date('M d, Y H:i', strtotime($ticket['created_at'])); ?></td>
                                                            <td>
                                                                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#assignModal<?php echo $ticket['id']; ?>">
                                                                    <i class="ri-user-add-line"></i> Assign
                                                                </button>
                                                                <a href="support_ticket_detail.php?id=<?php echo $ticket['id']; ?>" class="btn btn-sm btn-light">
                                                                    <i class="ri-eye-line"></i> View
                                                                </a>
                                                            </td>
                                                        </tr>

                                                        <!-- Assign Modal -->
                                                        <div class="modal fade" id="assignModal<?php echo $ticket['id']; ?>" tabindex="-1">
                                                            <div class="modal-dialog">
                                                                <div class="modal-content">
                                                                    <form method="POST">
                                                                        <div class="modal-header">
                                                                            <h5 class="modal-title">Assign Ticket #<?php echo htmlspecialchars($ticket['ticket_number']); ?></h5>
                                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                                        </div>
                                                                        <div class="modal-body">
                                                                            <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
                                                                            <div class="mb-3">
                                                                                <label class="form-label">Assign To</label>
                                                                                <select name="assigned_to" class="form-select">
                                                                                    <option value="">Unassign</option>
                                                                                    <?php foreach ($admin_users as $admin): ?>
                                                                                        <option value="<?php echo $admin['id']; ?>"><?php echo htmlspecialchars($admin['username']); ?></option>
                                                                                    <?php endforeach; ?>
                                                                                </select>
                                                                            </div>
                                                                        </div>
                                                                        <div class="modal-footer">
                                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                            <button type="submit" name="assign_ticket" class="btn btn-primary">Assign</button>
                                                                        </div>
                                                                    </form>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- All Tickets with Assignments -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">All Active Tickets</h4>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table id="ticketsTable" class="table table-striped table-bordered dt-responsive nowrap w-100">
                                            <thead>
                                                <tr>
                                                    <th>Ticket #</th>
                                                    <th>Subject</th>
                                                    <th>Member</th>
                                                    <th>Priority</th>
                                                    <th>Status</th>
                                                    <th>Assigned To</th>
                                                    <th>Created</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($all_tickets as $ticket): ?>
                                                    <tr>
                                                        <td><code><?php echo htmlspecialchars($ticket['ticket_number']); ?></code></td>
                                                        <td><strong><?php echo htmlspecialchars($ticket['subject']); ?></strong></td>
                                                        <td><?php echo htmlspecialchars($ticket['fullname'] ?? 'Guest'); ?></td>
                                                        <td>
                                                            <span class="badge bg-<?php 
                                                                echo $ticket['priority'] == 'urgent' ? 'danger' : 
                                                                    ($ticket['priority'] == 'high' ? 'warning' : 
                                                                    ($ticket['priority'] == 'medium' ? 'info' : 'secondary')); 
                                                            ?>">
                                                                <?php echo ucfirst($ticket['priority']); ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <span class="badge bg-<?php 
                                                                echo $ticket['status'] == 'open' ? 'warning' : 
                                                                    ($ticket['status'] == 'resolved' || $ticket['status'] == 'closed' ? 'success' : 
                                                                    ($ticket['status'] == 'pending' ? 'info' : 'primary')); 
                                                            ?>">
                                                                <?php echo ucfirst(str_replace('_', ' ', $ticket['status'])); ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <?php if ($ticket['assigned_to_name']): ?>
                                                                <span class="badge bg-primary"><?php echo htmlspecialchars($ticket['assigned_to_name']); ?></span>
                                                            <?php else: ?>
                                                                <span class="badge bg-secondary">Unassigned</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td><?php echo date('M d, Y H:i', strtotime($ticket['created_at'])); ?></td>
                                                        <td>
                                                            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#assignModal<?php echo $ticket['id']; ?>">
                                                                <i class="ri-user-add-line"></i> Reassign
                                                            </button>
                                                            <a href="support_ticket_detail.php?id=<?php echo $ticket['id']; ?>" class="btn btn-sm btn-light">
                                                                <i class="ri-eye-line"></i> View
                                                            </a>
                                                        </td>
                                                    </tr>

                                                    <!-- Reassign Modal -->
                                                    <div class="modal fade" id="assignModal<?php echo $ticket['id']; ?>" tabindex="-1">
                                                        <div class="modal-dialog">
                                                            <div class="modal-content">
                                                                <form method="POST">
                                                                    <div class="modal-header">
                                                                        <h5 class="modal-title">Assign Ticket #<?php echo htmlspecialchars($ticket['ticket_number']); ?></h5>
                                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                                    </div>
                                                                    <div class="modal-body">
                                                                        <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
                                                                        <div class="mb-3">
                                                                            <label class="form-label">Assign To</label>
                                                                            <select name="assigned_to" class="form-select">
                                                                                <option value="">Unassign</option>
                                                                                <?php foreach ($admin_users as $admin): ?>
                                                                                    <option value="<?php echo $admin['id']; ?>" <?php echo $ticket['assigned_to'] == $admin['id'] ? 'selected' : ''; ?>>
                                                                                        <?php echo htmlspecialchars($admin['username']); ?>
                                                                                    </option>
                                                                                <?php endforeach; ?>
                                                                            </select>
                                                                        </div>
                                                                    </div>
                                                                    <div class="modal-footer">
                                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                        <button type="submit" name="assign_ticket" class="btn btn-primary">Update Assignment</button>
                                                                    </div>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Bulk Assign Modal -->
                    <div class="modal fade" id="bulkAssignModal" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form method="POST" id="bulkAssignForm">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Bulk Assign Tickets</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <p>Assign selected tickets to an admin:</p>
                                        <div class="mb-3">
                                            <label class="form-label">Assign To</label>
                                            <select name="bulk_assigned_to" class="form-select" required>
                                                <option value="">Select Admin...</option>
                                                <?php foreach ($admin_users as $admin): ?>
                                                    <option value="<?php echo $admin['id']; ?>"><?php echo htmlspecialchars($admin['username']); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" name="bulk_assign" class="btn btn-primary">Assign Selected</button>
                                    </div>
                                </form>
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
                order: [[6, 'desc']],
                pageLength: 25
            });

            // Select all checkbox
            $('#selectAll').change(function() {
                $('.ticket-checkbox').prop('checked', this.checked);
            });

            // Bulk assign form - collect selected ticket IDs
            $('#bulkAssignForm').submit(function(e) {
                var selectedIds = [];
                $('.ticket-checkbox:checked').each(function() {
                    selectedIds.push($(this).val());
                });

                if (selectedIds.length === 0) {
                    e.preventDefault();
                    alert('Please select at least one ticket');
                    return false;
                }

                // Add hidden inputs for each selected ticket ID
                selectedIds.forEach(function(id) {
                    $(this).append('<input type="hidden" name="ticket_ids[]" value="' + id + '">');
                }.bind(this));

                return true;
            });
        });
    </script>

</body>
</html>

