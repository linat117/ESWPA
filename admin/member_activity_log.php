<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: auth-login.php");
    exit();
}

include 'include/conn.php';
include 'header.php';

$member_id = intval($_GET['id'] ?? 0);
$member = null;

if ($member_id > 0) {
    // Get member details
    $stmt = $conn->prepare("SELECT id, fullname, membership_id, email FROM registrations WHERE id = ?");
    $stmt->bind_param("i", $member_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $member = $result->fetch_assoc();
    }
    $stmt->close();
}

// Check if audit_logs table exists and has member-related logs
$activity_logs = [];

// Try to get activity from audit_logs if it exists
$checkTable = "SHOW TABLES LIKE 'audit_logs'";
$tableResult = $conn->query($checkTable);
$tableExists = $tableResult && $tableResult->num_rows > 0;

if ($tableExists && $member_id > 0) {
    // Get audit logs for this member
    $stmt = $conn->prepare("SELECT * FROM audit_logs WHERE entity_type = 'member' AND entity_id = ? ORDER BY created_at DESC LIMIT 100");
    $stmt->bind_param("i", $member_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $activity_logs[] = $row;
    }
    $stmt->close();
}

// If no audit logs, create sample activity from member data
if (empty($activity_logs) && $member) {
    // Registration
    $activity_logs[] = [
        'action' => 'registration',
        'description' => 'Member registered',
        'created_at' => $member['created_at'] ?? date('Y-m-d H:i:s'),
        'user_id' => null
    ];
    
    // Get approval info if exists
    if ($member_id > 0) {
        $stmt = $conn->prepare("SELECT approved_at, approved_by FROM registrations WHERE id = ?");
        $stmt->bind_param("i", $member_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $member_data = $result->fetch_assoc();
        $stmt->close();
        
        if (!empty($member_data['approved_at'])) {
            $activity_logs[] = [
                'action' => 'approval',
                'description' => 'Member approved',
                'created_at' => $member_data['approved_at'],
                'user_id' => $member_data['approved_by']
            ];
        }
        
        // Check ID card generation
        $stmt = $conn->prepare("SELECT id_card_generated_at FROM registrations WHERE id = ? AND id_card_generated = 1");
        $stmt->bind_param("i", $member_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $id_card_data = $result->fetch_assoc();
            if (!empty($id_card_data['id_card_generated_at'])) {
                $activity_logs[] = [
                    'action' => 'id_card_generated',
                    'description' => 'ID card generated',
                    'created_at' => $id_card_data['id_card_generated_at'],
                    'user_id' => null
                ];
            }
        }
        $stmt->close();
    }
}

// Sort by date descending
usort($activity_logs, function($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});
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
                                <h4 class="page-title">Member Activity Log</h4>
                                <div>
                                    <?php if ($member): ?>
                                        <a href="member_profile.php?id=<?php echo $member_id; ?>" class="btn btn-secondary me-2">
                                            <i class="ri-arrow-left-line"></i> Back to Profile
                                        </a>
                                    <?php else: ?>
                                        <a href="members_list.php" class="btn btn-secondary me-2">
                                            <i class="ri-arrow-left-line"></i> Back to List
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if ($member): ?>
                    <!-- Member Info Card -->
                    <div class="row mb-3">
                        <div class="col-12">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-grow-1">
                                            <h5 class="mb-1"><?php echo htmlspecialchars($member['fullname']); ?></h5>
                                            <p class="mb-0 text-muted">
                                                <code><?php echo htmlspecialchars($member['membership_id']); ?></code> | 
                                                <?php echo htmlspecialchars($member['email']); ?>
                                            </p>
                                        </div>
                                        <a href="member_profile.php?id=<?php echo $member_id; ?>" class="btn btn-primary">
                                            <i class="ri-user-line"></i> View Profile
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Activity Log -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Activity History</h4>
                                    <p class="text-muted mb-0">
                                        <?php if ($member): ?>
                                            Activity log for <?php echo htmlspecialchars($member['fullname']); ?>
                                        <?php else: ?>
                                            Select a member to view their activity log
                                        <?php endif; ?>
                                    </p>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($activity_logs)): ?>
                                        <div class="text-center py-5">
                                            <i class="ri-inbox-line fs-1 text-muted d-block mb-2"></i>
                                            <p class="text-muted">No activity logs found</p>
                                        </div>
                                    <?php else: ?>
                                        <div class="table-responsive">
                                            <table class="table table-hover mb-0" id="activityTable">
                                                <thead>
                                                    <tr>
                                                        <th>Date & Time</th>
                                                        <th>Action</th>
                                                        <th>Description</th>
                                                        <th>Performed By</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($activity_logs as $log): ?>
                                                        <tr>
                                                            <td>
                                                                <?php echo date('M d, Y H:i:s', strtotime($log['created_at'])); ?>
                                                            </td>
                                                            <td>
                                                                <span class="badge bg-<?php 
                                                                    $action = strtolower($log['action'] ?? '');
                                                                    echo $action == 'approval' ? 'success' : 
                                                                        ($action == 'registration' ? 'primary' : 
                                                                        ($action == 'id_card_generated' ? 'info' : 'secondary')); 
                                                                ?>">
                                                                    <?php echo ucfirst(str_replace('_', ' ', $log['action'] ?? 'activity')); ?>
                                                                </span>
                                                            </td>
                                                            <td><?php echo htmlspecialchars($log['description'] ?? 'Activity recorded'); ?></td>
                                                            <td>
                                                                <?php if (!empty($log['user_id'])): ?>
                                                                    <?php
                                                                    $userStmt = $conn->prepare("SELECT username FROM user WHERE id = ?");
                                                                    $userStmt->bind_param("i", $log['user_id']);
                                                                    $userStmt->execute();
                                                                    $userResult = $userStmt->get_result();
                                                                    if ($userResult->num_rows > 0) {
                                                                        $user = $userResult->fetch_assoc();
                                                                        echo htmlspecialchars($user['username']);
                                                                    } else {
                                                                        echo 'System';
                                                                    }
                                                                    $userStmt->close();
                                                                    ?>
                                                                <?php else: ?>
                                                                    <span class="text-muted">System</span>
                                                                <?php endif; ?>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <?php include 'footer.php'; ?>
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
            $('#activityTable').DataTable({
                "order": [[0, "desc"]],
                "pageLength": 25
            });
        });
    </script>

</body>
</html>

