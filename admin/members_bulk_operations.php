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

// Handle bulk operations
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && isset($_POST['member_ids'])) {
    $action = $_POST['action'];
    $member_ids = $_POST['member_ids'];
    
    if (!empty($member_ids) && is_array($member_ids)) {
        $ids = array_map('intval', $member_ids);
        $ids = array_filter($ids, function($id) { return $id > 0; });
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $types = str_repeat('i', count($ids));
        
        if (!empty($ids)) {
            switch ($action) {
                case 'approve':
                    $stmt = $conn->prepare("UPDATE registrations SET approval_status = 'approved', approved_by = ?, approved_at = NOW() WHERE id IN ($placeholders) AND approval_status = 'pending'");
                    $user_id = $_SESSION['user_id'];
                    $params = array_merge([$user_id], $ids);
                    $stmt->bind_param('i' . $types, ...$params);
                    if ($stmt->execute()) {
                        $success_message = count($ids) . " member(s) approved successfully";
                    }
                    $stmt->close();
                    break;
                    
                case 'reject':
                    $stmt = $conn->prepare("UPDATE registrations SET approval_status = 'rejected' WHERE id IN ($placeholders) AND approval_status = 'pending'");
                    $stmt->bind_param($types, ...$ids);
                    if ($stmt->execute()) {
                        $success_message = count($ids) . " member(s) rejected";
                    }
                    $stmt->close();
                    break;
                    
                case 'activate':
                    $stmt = $conn->prepare("UPDATE registrations SET status = 'active' WHERE id IN ($placeholders)");
                    $stmt->bind_param($types, ...$ids);
                    if ($stmt->execute()) {
                        $success_message = count($ids) . " member(s) activated";
                    }
                    $stmt->close();
                    break;
                    
                case 'deactivate':
                    $stmt = $conn->prepare("UPDATE registrations SET status = 'expired' WHERE id IN ($placeholders)");
                    $stmt->bind_param($types, ...$ids);
                    if ($stmt->execute()) {
                        $success_message = count($ids) . " member(s) deactivated";
                    }
                    $stmt->close();
                    break;
            }
        }
    }
}

// Fetch all members
$query = "SELECT id, fullname, membership_id, email, approval_status, status, expiry_date, created_at 
          FROM registrations 
          ORDER BY created_at DESC";
$result = $conn->query($query);
$all_members = $result->fetch_all(MYSQLI_ASSOC);
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
                                <h4 class="page-title mb-2">Bulk Member Operations</h4>
                                <div class="d-inline-flex flex-row flex-nowrap gap-2">
                                    <a href="members_dashboard.php" class="btn btn-secondary btn-sm">
                                        <i class="ri-dashboard-line"></i> Dashboard
                                    </a>
                                    <a href="members_list.php" class="btn btn-secondary btn-sm">
                                        <i class="ri-list-check"></i> All Members
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

                    <!-- Bulk Operations Panel -->
                    <div class="row mb-3">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Bulk Operations</h4>
                                    <p class="text-muted mb-0">Select members and choose an operation</p>
                                </div>
                                <div class="card-body">
                                    <form method="POST" id="bulkForm">
                                        <input type="hidden" name="action" id="bulk_action">
                                        
                                        <div class="row mb-3">
                                            <div class="col-md-12">
                                                <label class="form-label">Select Operation</label>
                                                <select class="form-select" id="operationSelect" required>
                                                    <option value="">Choose an operation...</option>
                                                    <option value="approve">Approve Selected Members</option>
                                                    <option value="reject">Reject Selected Members</option>
                                                    <option value="activate">Activate Selected Members</option>
                                                    <option value="deactivate">Deactivate Selected Members</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="row mb-3">
                                            <div class="col-md-12">
                                                <div class="alert alert-info">
                                                    <strong>Selected:</strong> <span id="selectedCount">0</span> member(s)
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-12">
                                                <button type="submit" class="btn btn-primary" id="executeBtn" disabled>
                                                    <i class="ri-check-line"></i> Execute Operation
                                                </button>
                                                <button type="button" class="btn btn-secondary" onclick="clearSelection()">
                                                    <i class="ri-refresh-line"></i> Clear Selection
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Members List -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title mb-2">All Members</h4>
                                    <div class="d-inline-flex flex-row flex-nowrap gap-2">
                                        <button type="button" class="btn btn-sm btn-primary" onclick="selectAll()">
                                            <i class="ri-checkbox-line"></i> Select All
                                        </button>
                                        <button type="button" class="btn btn-sm btn-secondary" onclick="clearSelection()">
                                            <i class="ri-checkbox-blank-line"></i> Clear All
                                        </button>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <!-- Desktop / tablet table -->
                                    <div class="table-responsive d-none d-md-block">
                                        <table class="table table-hover mb-0" id="membersTable">
                                            <thead>
                                                <tr>
                                                    <th width="50">
                                                        <input type="checkbox" id="selectAllCheckbox" onchange="toggleAll(this)">
                                                    </th>
                                                    <th>Member</th>
                                                    <th>Membership ID</th>
                                                    <th>Email</th>
                                                    <th>Approval Status</th>
                                                    <th>Membership Status</th>
                                                    <th>Registered</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($all_members as $member): ?>
                                                    <tr>
                                                        <td>
                                                            <input type="checkbox" 
                                                                   class="member-checkbox" 
                                                                   name="member_ids[]" 
                                                                   value="<?php echo $member['id']; ?>"
                                                                   onchange="updateSelection()">
                                                        </td>
                                                        <td>
                                                            <strong><?php echo htmlspecialchars($member['fullname']); ?></strong>
                                                        </td>
                                                        <td>
                                                            <code><?php echo htmlspecialchars($member['membership_id']); ?></code>
                                                        </td>
                                                        <td><?php echo htmlspecialchars($member['email']); ?></td>
                                                        <td>
                                                            <span class="badge bg-<?php 
                                                                echo $member['approval_status'] == 'approved' ? 'success' : 
                                                                    ($member['approval_status'] == 'pending' ? 'warning' : 'danger'); 
                                                            ?>">
                                                                <?php echo ucfirst($member['approval_status']); ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <?php
                                                            $expired = !empty($member['expiry_date']) && strtotime($member['expiry_date']) < time();
                                                            if ($expired) {
                                                                echo '<span class="badge bg-danger">Expired</span>';
                                                            } else {
                                                                echo '<span class="badge bg-success">Active</span>';
                                                            }
                                                            ?>
                                                        </td>
                                                        <td><?php echo date('M d, Y', strtotime($member['created_at'])); ?></td>
                                                        <td>
                                                            <a href="member_profile.php?id=<?php echo $member['id']; ?>" class="btn btn-sm btn-light">
                                                                <i class="ri-eye-line"></i>
                                                            </a>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>

                                    <!-- Mobile card list -->
                                    <div class="d-block d-md-none">
                                        <?php foreach ($all_members as $member): ?>
                                            <?php
                                                $expired = !empty($member['expiry_date']) && strtotime($member['expiry_date']) < time();
                                            ?>
                                            <div class="card mb-2 mobile-member-card">
                                                <div class="card-body d-flex justify-content-between align-items-center">
                                                    <div class="form-check me-2">
                                                        <input type="checkbox"
                                                               class="form-check-input member-checkbox"
                                                               name="member_ids[]"
                                                               value="<?php echo $member['id']; ?>"
                                                               onchange="updateSelection()">
                                                    </div>
                                                    <div class="flex-grow-1 ms-1">
                                                        <div class="d-flex flex-column">
                                                            <span class="fw-semibold text-truncate" style="max-width: 150px;">
                                                                <?php echo htmlspecialchars($member['fullname']); ?>
                                                            </span>
                                                            <small class="text-muted">
                                                                ID: <?php echo htmlspecialchars($member['membership_id']); ?>
                                                            </small>
                                                            <small class="text-muted">
                                                                <?php echo htmlspecialchars($member['email']); ?>
                                                            </small>
                                                        </div>
                                                    </div>
                                                    <button type="button"
                                                            class="btn btn-link text-muted p-0 mobile-member-more"
                                                            aria-label="View member detail">
                                                        <i class="ri-more-2-fill" style="font-size: 1.4rem;"></i>
                                                    </button>
                                                </div>

                                                <!-- Hidden detail for modal -->
                                                <div class="d-none mobile-member-detail-content">
                                                    <h5 class="mb-1"><?php echo htmlspecialchars($member['fullname']); ?></h5>
                                                    <p class="mb-1">
                                                        <strong>Membership ID:</strong>
                                                        <code><?php echo htmlspecialchars($member['membership_id']); ?></code>
                                                    </p>
                                                    <p class="mb-1">
                                                        <strong>Email:</strong>
                                                        <?php echo htmlspecialchars($member['email']); ?>
                                                    </p>
                                                    <p class="mb-1">
                                                        <strong>Approval Status:</strong>
                                                        <span class="badge bg-<?php 
                                                            echo $member['approval_status'] == 'approved' ? 'success' : 
                                                                ($member['approval_status'] == 'pending' ? 'warning' : 'danger'); 
                                                        ?>">
                                                            <?php echo ucfirst($member['approval_status']); ?>
                                                        </span>
                                                    </p>
                                                    <p class="mb-3">
                                                        <strong>Membership Status:</strong>
                                                        <?php if ($expired): ?>
                                                            <span class="badge bg-danger">Expired</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-success">Active</span>
                                                        <?php endif; ?>
                                                    </p>
                                                    <p class="mb-3">
                                                        <strong>Registered:</strong>
                                                        <?php echo date('M d, Y', strtotime($member['created_at'])); ?>
                                                    </p>
                                                    <a href="member_profile.php?id=<?php echo $member['id']; ?>" class="btn btn-primary btn-sm">
                                                        <i class="ri-eye-line"></i> View Profile
                                                    </a>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
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

    <!-- Mobile member detail modal -->
    <div class="modal fade" id="bulkMemberDetailModal" tabindex="-1" aria-labelledby="bulkMemberDetailModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="bulkMemberDetailModalLabel">Member Detail</h5>
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
        var table;
        $(document).ready(function() {
            table = $('#membersTable').DataTable({
                "order": [[6, "desc"]],
                "pageLength": 25
            });

            $('#operationSelect').on('change', function() {
                $('#bulk_action').val($(this).val());
                updateExecuteButton();
            });

            // Mobile member detail modal
            $(document).on('click', '.mobile-member-more', function () {
                var card = $(this).closest('.mobile-member-card');
                var contentHtml = card.find('.mobile-member-detail-content').html();

                $('#bulkMemberDetailModal .modal-body').html(contentHtml);

                if (typeof bootstrap !== "undefined" && bootstrap.Modal) {
                    var detailModal = new bootstrap.Modal(document.getElementById('bulkMemberDetailModal'));
                    detailModal.show();
                } else {
                    $('#bulkMemberDetailModal').modal('show');
                }
            });
        });

        function updateSelection() {
            var selected = $('.member-checkbox:checked').length;
            $('#selectedCount').text(selected);
            updateExecuteButton();
        }

        function updateExecuteButton() {
            var selected = $('.member-checkbox:checked').length;
            var action = $('#operationSelect').val();
            $('#executeBtn').prop('disabled', !(selected > 0 && action));
        }

        function toggleAll(checkbox) {
            $('.member-checkbox').prop('checked', checkbox.checked);
            updateSelection();
        }

        function selectAll() {
            $('.member-checkbox').prop('checked', true);
            $('#selectAllCheckbox').prop('checked', true);
            updateSelection();
        }

        function clearSelection() {
            $('.member-checkbox').prop('checked', false);
            $('#selectAllCheckbox').prop('checked', false);
            updateSelection();
        }

        $('#bulkForm').on('submit', function(e) {
            var action = $('#operationSelect').val();
            var selected = $('.member-checkbox:checked').length;
            var message = 'Are you sure you want to ' + action.toUpperCase() + ' ' + selected + ' member(s)?';
            
            if (!confirm(message)) {
                e.preventDefault();
                return false;
            }
        });
    </script>

</body>
</html>

