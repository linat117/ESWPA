<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: auth-login.php");
    exit();
}

include 'include/conn.php';
include 'header.php';

// Get filter parameters
$filter_status = $_GET['status'] ?? '';
$filter_search = $_GET['search'] ?? '';
$member_id = $_GET['member_id'] ?? '';

// Build query with filters
$where = [];
$params = [];
$types = '';

if ($member_id) {
    $where[] = "r.id = ?";
    $params[] = $member_id;
    $types .= 'i';
}

if ($filter_status) {
    if ($filter_status == 'generated') {
        $where[] = "r.id_card_generated = 1";
    } elseif ($filter_status == 'pending') {
        $where[] = "(r.id_card_generated = 0 OR r.id_card_generated IS NULL)";
        $where[] = "r.approval_status = 'approved'";
        $where[] = "(r.expiry_date IS NULL OR r.expiry_date >= CURDATE())";
    } elseif ($filter_status == 'expired') {
        $where[] = "r.expiry_date IS NOT NULL AND r.expiry_date < CURDATE()";
    } elseif ($filter_status == 'active') {
        $where[] = "r.id_card_generated = 1";
        $where[] = "(r.expiry_date IS NULL OR r.expiry_date >= CURDATE())";
    }
}

if ($filter_search) {
    $where[] = "(r.fullname LIKE ? OR r.membership_id LIKE ? OR r.email LIKE ?)";
    $searchTerm = "%{$filter_search}%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= 'sss';
}

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : 'WHERE r.approval_status = "approved"';

// Count total records
$countQuery = "SELECT COUNT(*) as total FROM registrations r {$whereClause}";
if (!empty($params)) {
    $countStmt = $conn->prepare($countQuery);
    $countStmt->bind_param($types, ...$params);
    $countStmt->execute();
    $countResult = $countStmt->get_result();
    $total_records = $countResult->fetch_assoc()['total'];
    $countStmt->close();
} else {
    $countResult = $conn->query($countQuery);
    $total_records = $countResult->fetch_assoc()['total'];
}

// Fetch ID cards
$query = "SELECT r.id, r.fullname, r.membership_id, r.email, r.phone, r.qualification, 
                 r.id_card_generated, r.id_card_generated_at, r.expiry_date, r.approval_status,
                 r.photo, r.created_at, r.approved_at
          FROM registrations r 
          {$whereClause}
          ORDER BY r.id_card_generated_at DESC, r.approved_at DESC";

if (!empty($params)) {
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($query);
}

$id_cards = [];
while ($row = $result->fetch_assoc()) {
    $id_cards[] = $row;
}

if (!empty($params) && isset($stmt)) {
    $stmt->close();
}

// Get statistics
$stats = [];
$stats['total'] = $conn->query("SELECT COUNT(*) as total FROM registrations WHERE approval_status = 'approved'")->fetch_assoc()['total'];
$stats['generated'] = $conn->query("SELECT COUNT(*) as total FROM registrations WHERE id_card_generated = 1 AND approval_status = 'approved'")->fetch_assoc()['total'];
$stats['pending'] = $conn->query("SELECT COUNT(*) as total FROM registrations WHERE (id_card_generated = 0 OR id_card_generated IS NULL) AND approval_status = 'approved' AND (expiry_date IS NULL OR expiry_date >= CURDATE())")->fetch_assoc()['total'];
$stats['expired'] = $conn->query("SELECT COUNT(*) as total FROM registrations WHERE expiry_date IS NOT NULL AND expiry_date < CURDATE() AND approval_status = 'approved'")->fetch_assoc()['total'];
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
                                <h4 class="page-title">ID Cards Management</h4>
                                <div>
                                    <a href="digital_id_dashboard.php" class="btn btn-secondary me-2">
                                        <i class="ri-dashboard-line"></i> Dashboard
                                    </a>
                                    <a href="id_card_generate.php" class="btn btn-primary">
                                        <i class="ri-add-circle-line"></i> Generate ID Card
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Stats -->
                    <div class="row g-2 mb-3">
                        <div class="col-md-3">
                            <div class="card bg-primary bg-opacity-10 border-primary">
                                <div class="card-body p-2">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-grow-1">
                                            <h6 class="mb-0 text-muted small">Total Eligible</h6>
                                            <h4 class="mb-0"><?php echo number_format($stats['total']); ?></h4>
                                        </div>
                                        <i class="ri-user-line fs-1 text-primary opacity-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-success bg-opacity-10 border-success">
                                <div class="card-body p-2">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-grow-1">
                                            <h6 class="mb-0 text-muted small">Generated</h6>
                                            <h4 class="mb-0"><?php echo number_format($stats['generated']); ?></h4>
                                        </div>
                                        <i class="ri-id-card-line fs-1 text-success opacity-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-warning bg-opacity-10 border-warning">
                                <div class="card-body p-2">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-grow-1">
                                            <h6 class="mb-0 text-muted small">Pending</h6>
                                            <h4 class="mb-0"><?php echo number_format($stats['pending']); ?></h4>
                                        </div>
                                        <i class="ri-time-line fs-1 text-warning opacity-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-danger bg-opacity-10 border-danger">
                                <div class="card-body p-2">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-grow-1">
                                            <h6 class="mb-0 text-muted small">Expired</h6>
                                            <h4 class="mb-0"><?php echo number_format($stats['expired']); ?></h4>
                                        </div>
                                        <i class="ri-close-circle-line fs-1 text-danger opacity-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Filters -->
                    <div class="row mb-3">
                        <div class="col-12">
                            <div class="card border">
                                <div class="card-body">
                                    <form method="GET" class="row g-3">
                                        <div class="col-md-3">
                                            <label class="form-label small">Search</label>
                                            <input type="text" name="search" class="form-control form-control-sm" 
                                                   value="<?php echo htmlspecialchars($filter_search); ?>" 
                                                   placeholder="Name, Membership ID, Email...">
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label small">Status</label>
                                            <select name="status" class="form-select form-select-sm">
                                                <option value="">All Status</option>
                                                <option value="generated" <?php echo $filter_status == 'generated' ? 'selected' : ''; ?>>Generated</option>
                                                <option value="pending" <?php echo $filter_status == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                <option value="active" <?php echo $filter_status == 'active' ? 'selected' : ''; ?>>Active</option>
                                                <option value="expired" <?php echo $filter_status == 'expired' ? 'selected' : ''; ?>>Expired</option>
                                            </select>
                                        </div>
                                        <div class="col-md-2 d-flex align-items-end">
                                            <button type="submit" class="btn btn-sm btn-primary w-100">
                                                <i class="ri-filter-line"></i> Filter
                                            </button>
                                        </div>
                                        <div class="col-md-2 d-flex align-items-end">
                                            <a href="id_cards_list.php" class="btn btn-sm btn-secondary w-100">
                                                <i class="ri-refresh-line"></i> Reset
                                            </a>
                                        </div>
                                        <div class="col-md-3 d-flex align-items-end justify-content-end">
                                            <span class="text-muted small">Showing <?php echo number_format(count($id_cards)); ?> of <?php echo number_format($total_records); ?> records</span>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ID Cards List -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h4 class="header-title mb-0">ID Cards List</h4>
                                    <small class="text-muted d-md-none"><i class="ri-arrow-left-right-line"></i> Swipe to scroll</small>
                                </div>
                                <div class="card-body p-0 p-md-3">
                                    <div class="table-responsive" style="-webkit-overflow-scrolling: touch; overflow-x: auto;">
                                        <table class="table table-hover mb-0" id="idCardsTable" style="min-width: 900px;">
                                            <thead>
                                                <tr>
                                                    <th>Member</th>
                                                    <th>Membership ID</th>
                                                    <th>Contact</th>
                                                    <th>Qualification</th>
                                                    <th>ID Card Status</th>
                                                    <th>Membership Status</th>
                                                    <th>Generated</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (empty($id_cards)): ?>
                                                    <tr>
                                                        <td colspan="8" class="text-center py-4">
                                                            <div class="text-muted">
                                                                <i class="ri-inbox-line fs-1 d-block mb-2"></i>
                                                                No ID cards found matching your criteria.
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php else: ?>
                                                    <?php foreach ($id_cards as $card): ?>
                                                        <?php
                                                        $has_card = $card['id_card_generated'] == 1;
                                                        $expired = !empty($card['expiry_date']) && strtotime($card['expiry_date']) < time();
                                                        $active = !$expired && $has_card;
                                                        ?>
                                                        <tr>
                                                            <td>
                                                                <div class="d-flex align-items-center">
                                                                    <?php if (!empty($card['photo'])): ?>
                                                                        <img src="../<?php echo htmlspecialchars($card['photo']); ?>" 
                                                                             alt="<?php echo htmlspecialchars($card['fullname']); ?>" 
                                                                             class="rounded-circle me-2" 
                                                                             style="width: 40px; height: 40px; object-fit: cover;">
                                                                    <?php else: ?>
                                                                        <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center me-2" 
                                                                             style="width: 40px; height: 40px;">
                                                                            <i class="ri-user-line text-white"></i>
                                                                        </div>
                                                                    <?php endif; ?>
                                                                    <div>
                                                                        <strong><?php echo htmlspecialchars($card['fullname']); ?></strong>
                                                                        <br><small class="text-muted"><?php echo htmlspecialchars($card['email']); ?></small>
                                                                    </div>
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <code><?php echo htmlspecialchars($card['membership_id']); ?></code>
                                                            </td>
                                                            <td>
                                                                <small><?php echo htmlspecialchars($card['phone']); ?></small>
                                                            </td>
                                                            <td>
                                                                <span class="badge bg-light text-dark"><?php echo htmlspecialchars($card['qualification']); ?></span>
                                                            </td>
                                                            <td>
                                                                <?php if ($has_card): ?>
                                                                    <span class="badge bg-success">
                                                                        <i class="ri-checkbox-circle-line"></i> Generated
                                                                    </span>
                                                                    <?php if ($card['id_card_generated_at']): ?>
                                                                        <br><small class="text-muted"><?php echo date('M d, Y', strtotime($card['id_card_generated_at'])); ?></small>
                                                                    <?php endif; ?>
                                                                <?php else: ?>
                                                                    <span class="badge bg-warning">
                                                                        <i class="ri-time-line"></i> Pending
                                                                    </span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td>
                                                                <?php if ($expired): ?>
                                                                    <span class="badge bg-danger">Expired</span>
                                                                    <?php if ($card['expiry_date']): ?>
                                                                        <br><small class="text-muted"><?php echo date('M d, Y', strtotime($card['expiry_date'])); ?></small>
                                                                    <?php endif; ?>
                                                                <?php elseif ($active): ?>
                                                                    <span class="badge bg-success">Active</span>
                                                                    <?php if ($card['expiry_date']): ?>
                                                                        <br><small class="text-muted">Expires: <?php echo date('M d, Y', strtotime($card['expiry_date'])); ?></small>
                                                                    <?php else: ?>
                                                                        <br><small class="text-muted">No expiry</small>
                                                                    <?php endif; ?>
                                                                <?php else: ?>
                                                                    <span class="badge bg-secondary">N/A</span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td>
                                                                <?php if ($card['id_card_generated_at']): ?>
                                                                    <?php echo date('M d, Y', strtotime($card['id_card_generated_at'])); ?>
                                                                <?php else: ?>
                                                                    <span class="text-muted">-</span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td>
                                                                <div class="btn-group" role="group">
                                                                    <?php if ($has_card): ?>
                                                                        <a href="member-id-card-print.php?member_id=<?php echo $card['id']; ?>" 
                                                                           class="btn btn-sm btn-info" 
                                                                           title="View ID Card">
                                                                            <i class="ri-eye-line"></i>
                                                                        </a>
                                                                        <a href="id_card_generate.php?member_id=<?php echo $card['id']; ?>&regenerate=1" 
                                                                           class="btn btn-sm btn-warning" 
                                                                           title="Regenerate ID Card">
                                                                            <i class="ri-refresh-line"></i>
                                                                        </a>
                                                                    <?php else: ?>
                                                                        <a href="id_card_generate.php?member_id=<?php echo $card['id']; ?>" 
                                                                           class="btn btn-sm btn-primary" 
                                                                           title="Generate ID Card">
                                                                            <i class="ri-add-circle-line"></i>
                                                                        </a>
                                                                    <?php endif; ?>
                                                                    <a href="members_list.php?id=<?php echo $card['id']; ?>" 
                                                                       class="btn btn-sm btn-light" 
                                                                       title="View Member">
                                                                        <i class="ri-user-line"></i>
                                                                    </a>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <?php include 'footer.php'; ?>
        </div>
    </div>

    <!-- Mobile table scrolling styles -->
    <style>
        @media (max-width: 767.98px) {
            #idCardsTable th,
            #idCardsTable td {
                white-space: nowrap;
            }
            #idCardsTable td:first-child {
                min-width: 180px;
            }
            .table-responsive {
                border-radius: 0;
                margin: 0 -12px;
                padding: 0 12px;
            }
        }
    </style>

    <!-- Vendor js -->
    <script src="assets/js/vendor.min.js"></script>
    
    <!-- Datatables js -->
    <link href="assets/vendor/datatables.net-bs5/css/dataTables.bootstrap5.min.css" rel="stylesheet" type="text/css" />
    <script src="assets/vendor/datatables.net/js/jquery.dataTables.min.js"></script>
    <script src="assets/vendor/datatables.net-bs5/js/dataTables.bootstrap5.min.js"></script>
    
    <!-- App js -->
    <script src="assets/js/app.min.js"></script>

    <script>
        $(document).ready(function() {
            // Initialize DataTable
            $('#idCardsTable').DataTable({
                "order": [[6, "desc"]], // Sort by generated date
                "pageLength": 25,
                "language": {
                    "search": "Search:",
                    "lengthMenu": "Show _MENU_ entries per page",
                    "info": "Showing _START_ to _END_ of _TOTAL_ entries",
                    "infoEmpty": "No entries found",
                    "infoFiltered": "(filtered from _MAX_ total entries)"
                }
            });
        });
    </script>

</body>
</html>

