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

// Handle approval/rejection
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['member_id']) && isset($_POST['action'])) {
    $member_id = intval($_POST['member_id']);
    $action = $_POST['action']; // 'approve' or 'reject'
    $notes = trim($_POST['notes'] ?? '');
    
    if ($action == 'approve') {
        // Approve member
        $stmt = $conn->prepare("UPDATE registrations SET approval_status = 'approved', approved_by = ?, approved_at = NOW() WHERE id = ? AND approval_status = 'pending'");
        $user_id = $_SESSION['user_id'];
        $stmt->bind_param("ii", $user_id, $member_id);
        
        if ($stmt->execute()) {
            // Create member_access entry if not exists
            $checkStmt = $conn->prepare("SELECT id FROM member_access WHERE member_id = ?");
            $checkStmt->bind_param("i", $member_id);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();
            $checkStmt->close();
            
            if ($checkResult->num_rows == 0) {
                // Get member email and membership_id
                $memberStmt = $conn->prepare("SELECT email, membership_id FROM registrations WHERE id = ?");
                $memberStmt->bind_param("i", $member_id);
                $memberStmt->execute();
                $memberResult = $memberStmt->get_result();
                $memberData = $memberResult->fetch_assoc();
                $memberStmt->close();
                
                // Create member_access entry (password will be set by member on first login)
                $insertStmt = $conn->prepare("INSERT INTO member_access (member_id, email, membership_id, status) VALUES (?, ?, ?, 'active')");
                $insertStmt->bind_param("iss", $member_id, $memberData['email'], $memberData['membership_id']);
                $insertStmt->execute();
                $insertStmt->close();
            }
            
            $success_message = "Member approved successfully";
        } else {
            $error_message = "Failed to approve member";
        }
        $stmt->close();
    } elseif ($action == 'reject') {
        // Reject member
        $stmt = $conn->prepare("UPDATE registrations SET approval_status = 'rejected' WHERE id = ? AND approval_status = 'pending'");
        $stmt->bind_param("i", $member_id);
        
        if ($stmt->execute()) {
            $success_message = "Member rejected";
        } else {
            $error_message = "Failed to reject member";
        }
        $stmt->close();
    }
}

// Get pending members
$pendingQuery = "SELECT r.*, 
                        (SELECT COUNT(*) FROM member_admin_notes WHERE member_id = r.id) as notes_count
                 FROM registrations r 
                 WHERE r.approval_status = 'pending' 
                 ORDER BY r.created_at DESC";
$pendingResult = $conn->query($pendingQuery);
$pending_members = $pendingResult->fetch_all(MYSQLI_ASSOC);

// Get recently approved/rejected
$recentQuery = "SELECT r.*, u.username as approved_by_name
                FROM registrations r 
                LEFT JOIN user u ON r.approved_by = u.id
                WHERE r.approval_status IN ('approved', 'rejected') 
                ORDER BY COALESCE(r.approved_at, r.updated_at) DESC 
                LIMIT 20";
$recentResult = $conn->query($recentQuery);
$recent_actions = $recentResult->fetch_all(MYSQLI_ASSOC);
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
                            <div class="page-title-box mb-3">
                                <h4 class="page-title mb-2">Member Approval Workflow</h4>
                                <div class="d-inline-flex flex-row flex-nowrap gap-2">
                                    <a href="members_list.php" class="btn btn-primary btn-sm">
                                        <i class="ri-list-check"></i> All Members
                                    </a>
                                    <a href="members_dashboard.php" class="btn btn-secondary btn-sm">
                                        <i class="ri-dashboard-line"></i> Dashboard
                                    </a>
                                    <a href="members_list.php?status=pending" class="btn btn-warning btn-sm">
                                        <i class="ri-time-line"></i> Pending List
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

                    <!-- Summary Card -->
                    <div class="row mb-3">
                        <div class="col-12">
                            <div class="card bg-warning bg-opacity-10 border-warning">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-grow-1">
                                            <h6 class="text-muted mb-1">Pending Approvals</h6>
                                            <h3 class="mb-0"><?php echo number_format(count($pending_members)); ?> members</h3>
                                            <small class="text-muted">Awaiting admin review</small>
                                        </div>
                                        <i class="ri-time-line fs-1 text-warning opacity-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pending Members -->
                    <div class="row mb-3">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Pending Approval</h4>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($pending_members)): ?>
                                        <div class="text-center py-5">
                                            <i class="ri-checkbox-circle-line fs-1 text-success d-block mb-2"></i>
                                            <p class="text-muted">No pending approvals. All members have been reviewed.</p>
                                        </div>
                                    <?php else: ?>
                                        <div class="row g-3">
                                            <?php foreach ($pending_members as $member): ?>
                                                <div class="col-lg-6">
                                                    <div class="card border-warning">
                                                        <div class="card-body">
                                                            <div class="d-flex align-items-start mb-3">
                                                                <?php if (!empty($member['photo'])): ?>
                                                                    <img src="../<?php echo htmlspecialchars($member['photo']); ?>" 
                                                                         alt="Photo" 
                                                                         class="rounded me-3" 
                                                                         style="width: 80px; height: 80px; object-fit: cover;">
                                                                <?php else: ?>
                                                                    <div class="bg-secondary rounded d-flex align-items-center justify-content-center me-3" 
                                                                         style="width: 80px; height: 80px;">
                                                                        <i class="ri-user-line text-white fs-2"></i>
                                                                    </div>
                                                                <?php endif; ?>
                                                                <div class="flex-grow-1">
                                                                    <h5 class="mb-1"><?php echo htmlspecialchars($member['fullname']); ?></h5>
                                                                    <p class="mb-1 text-muted small">
                                                                        <code><?php echo htmlspecialchars($member['membership_id']); ?></code>
                                                                    </p>
                                                                    <p class="mb-1 small">
                                                                        <i class="ri-mail-line"></i> <?php echo htmlspecialchars($member['email']); ?>
                                                                    </p>
                                                                    <p class="mb-0 small">
                                                                        <i class="ri-phone-line"></i> <?php echo htmlspecialchars($member['phone']); ?>
                                                                    </p>
                                                                </div>
                                                            </div>
                                                            
                                                            <div class="mb-3">
                                                                <strong>Qualification:</strong> 
                                                                <span class="badge bg-primary"><?php echo htmlspecialchars($member['qualification']); ?></span>
                                                            </div>
                                                            
                                                            <div class="mb-3">
                                                                <strong>Registered:</strong> <?php echo date('M d, Y', strtotime($member['created_at'])); ?>
                                                            </div>
                                                            
                                                            <div class="d-flex gap-2">
                                                                <button type="button" 
                                                                        class="btn btn-success btn-sm flex-fill" 
                                                                        data-bs-toggle="modal" 
                                                                        data-bs-target="#approveModal"
                                                                        onclick="setApproveMember(<?php echo $member['id']; ?>, '<?php echo htmlspecialchars($member['fullname']); ?>')">
                                                                    <i class="ri-check-line"></i> Approve
                                                                </button>
                                                                <button type="button" 
                                                                        class="btn btn-danger btn-sm flex-fill" 
                                                                        data-bs-toggle="modal" 
                                                                        data-bs-target="#rejectModal"
                                                                        onclick="setRejectMember(<?php echo $member['id']; ?>, '<?php echo htmlspecialchars($member['fullname']); ?>')">
                                                                    <i class="ri-close-line"></i> Reject
                                                                </button>
                                                                <a href="member_profile.php?id=<?php echo $member['id']; ?>" 
                                                                   class="btn btn-info btn-sm">
                                                                    <i class="ri-eye-line"></i> View
                                                                </a>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Actions -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Recent Approval Actions</h4>
                                </div>
                                <div class="card-body">
                                    <!-- Desktop table -->
                                    <div class="table-responsive d-none d-md-block">
                                        <table class="table table-hover mb-0" id="recentActionsTable">
                                            <thead>
                                                <tr>
                                                    <th>Member</th>
                                                    <th>Membership ID</th>
                                                    <th>Action</th>
                                                    <th>Approved By</th>
                                                    <th>Date</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($recent_actions as $action): ?>
                                                    <tr>
                                                        <td><strong><?php echo htmlspecialchars($action['fullname']); ?></strong></td>
                                                        <td><code><?php echo htmlspecialchars($action['membership_id']); ?></code></td>
                                                        <td>
                                                            <span class="badge bg-<?php echo $action['approval_status'] == 'approved' ? 'success' : 'danger'; ?>">
                                                                <?php echo ucfirst($action['approval_status']); ?>
                                                            </span>
                                                        </td>
                                                        <td><?php echo htmlspecialchars($action['approved_by_name'] ?? 'System'); ?></td>
                                                        <td>
                                                            <?php 
                                                            $date = $action['approved_at'] ?? $action['updated_at'] ?? $action['created_at'];
                                                            echo date('M d, Y H:i', strtotime($date)); 
                                                            ?>
                                                        </td>
                                                        <td>
                                                            <a href="member_profile.php?id=<?php echo $action['id']; ?>" class="btn btn-sm btn-light">
                                                                <i class="ri-eye-line"></i>
                                                            </a>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>

                                    <!-- Mobile cards -->
                                    <div class="d-block d-md-none mt-2">
                                        <?php if (empty($recent_actions)): ?>
                                            <p class="text-center text-muted mb-0">No recent actions.</p>
                                        <?php else: ?>
                                            <?php $mobileActionIndex = 1; ?>
                                            <?php foreach ($recent_actions as $action): ?>
                                                <?php 
                                                    $date = $action['approved_at'] ?? $action['updated_at'] ?? $action['created_at'];
                                                    $formattedDate = date('M d, Y H:i', strtotime($date));
                                                ?>
                                                <div class="card mb-2 mobile-action-card">
                                                    <div class="card-body d-flex justify-content-between align-items-center">
                                                        <div class="d-flex align-items-center gap-2">
                                                            <span class="badge bg-secondary rounded-pill"><?php echo $mobileActionIndex++; ?></span>
                                                            <div class="d-flex flex-column">
                                                                <span class="fw-semibold text-truncate" style="max-width: 160px;">
                                                                    <?php echo htmlspecialchars($action['fullname']); ?>
                                                                </span>
                                                                <small class="text-muted">
                                                                    ID: <?php echo htmlspecialchars($action['membership_id']); ?>
                                                                </small>
                                                            </div>
                                                        </div>
                                                        <button type="button"
                                                            class="btn btn-link text-muted p-0 recent-action-more"
                                                            aria-label="View approval detail">
                                                            <i class="ri-more-2-fill" style="font-size: 1.4rem;"></i>
                                                        </button>
                                                    </div>

                                                    <!-- Hidden detail for modal -->
                                                    <div class="d-none recent-action-detail-content">
                                                        <h5 class="mb-1"><?php echo htmlspecialchars($action['fullname']); ?></h5>
                                                        <p class="mb-1">
                                                            <strong>Membership ID:</strong>
                                                            <code><?php echo htmlspecialchars($action['membership_id']); ?></code>
                                                        </p>
                                                        <p class="mb-1">
                                                            <strong>Action:</strong>
                                                            <span class="badge bg-<?php echo $action['approval_status'] == 'approved' ? 'success' : 'danger'; ?>">
                                                                <?php echo ucfirst($action['approval_status']); ?>
                                                            </span>
                                                        </p>
                                                        <p class="mb-1">
                                                            <strong>Approved By:</strong>
                                                            <?php echo htmlspecialchars($action['approved_by_name'] ?? 'System'); ?>
                                                        </p>
                                                        <p class="mb-3">
                                                            <strong>Date:</strong>
                                                            <?php echo $formattedDate; ?>
                                                        </p>
                                                        <a href="member_profile.php?id=<?php echo $action['id']; ?>" class="btn btn-primary btn-sm">
                                                            <i class="ri-eye-line"></i> View Profile
                                                        </a>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
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

    <!-- Approve Modal -->
    <div class="modal fade" id="approveModal" tabindex="-1" aria-labelledby="approveModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="action" value="approve">
                    <input type="hidden" name="member_id" id="approve_member_id">
                    <div class="modal-header">
                        <h5 class="modal-title" id="approveModalLabel">Approve Member</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Are you sure you want to approve <strong id="approve_member_name"></strong>?</p>
                        <div class="mb-3">
                            <label for="approve_notes" class="form-label">Notes (optional)</label>
                            <textarea name="notes" id="approve_notes" class="form-control" rows="3" placeholder="Add any notes about this approval..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">Approve Member</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Reject Modal -->
    <div class="modal fade" id="rejectModal" tabindex="-1" aria-labelledby="rejectModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="action" value="reject">
                    <input type="hidden" name="member_id" id="reject_member_id">
                    <div class="modal-header">
                        <h5 class="modal-title" id="rejectModalLabel">Reject Member</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-warning">
                            <i class="ri-alert-line"></i> This action will reject the member's registration.
                        </div>
                        <p>Are you sure you want to reject <strong id="reject_member_name"></strong>?</p>
                        <div class="mb-3">
                            <label for="reject_notes" class="form-label">Reason for Rejection (optional)</label>
                            <textarea name="notes" id="reject_notes" class="form-control" rows="3" placeholder="Provide reason for rejection..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Reject Member</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Recent Action Detail Modal (mobile) -->
    <div class="modal fade" id="recentActionDetailModal" tabindex="-1" aria-labelledby="recentActionDetailModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="recentActionDetailModalLabel">Approval Detail</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Filled dynamically -->
                </div>
            </div>
        </div>
    </div>

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
            // Desktop table init
            $('#recentActionsTable').DataTable({
                "order": [[4, "desc"]],
                "pageLength": 25
            });

            // Mobile recent action detail modal
            $(document).on('click', '.recent-action-more', function () {
                var card = $(this).closest('.mobile-action-card');
                var contentHtml = card.find('.recent-action-detail-content').html();

                $('#recentActionDetailModal .modal-body').html(contentHtml);

                if (typeof bootstrap !== "undefined" && bootstrap.Modal) {
                    var detailModal = new bootstrap.Modal(document.getElementById('recentActionDetailModal'));
                    detailModal.show();
                } else {
                    $('#recentActionDetailModal').modal('show');
                }
            });
        });

        function setApproveMember(id, name) {
            document.getElementById('approve_member_id').value = id;
            document.getElementById('approve_member_name').textContent = name;
        }

        function setRejectMember(id, name) {
            document.getElementById('reject_member_id').value = id;
            document.getElementById('reject_member_name').textContent = name;
        }
    </script>

</body>
</html>

