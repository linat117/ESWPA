<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: auth-login.php");
    exit();
}

include 'include/conn.php';
include 'header.php';

$member_id = intval($_GET['id'] ?? 0);

if ($member_id <= 0) {
    header("Location: members_list.php?error=Invalid member ID");
    exit();
}

// Get member details
$stmt = $conn->prepare("SELECT r.*, u.username as approved_by_name 
                       FROM registrations r 
                       LEFT JOIN user u ON r.approved_by = u.id 
                       WHERE r.id = ?");
$stmt->bind_param("i", $member_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: members_list.php?error=Member not found");
    exit();
}

$member = $result->fetch_assoc();
$stmt->close();

// Get ID card status
$id_card_generated = $member['id_card_generated'] == 1;
$verification_code = '';
if ($id_card_generated) {
    $stmt = $conn->prepare("SELECT verification_code FROM id_card_verification WHERE membership_id = ? LIMIT 1");
    $stmt->bind_param("s", $member['membership_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $verification_code = $result->fetch_assoc()['verification_code'];
    }
    $stmt->close();
}

// Check if expired
$is_expired = !empty($member['expiry_date']) && strtotime($member['expiry_date']) < time();
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
                                <h4 class="page-title">Member Profile</h4>
                                <div>
                                    <a href="members_list.php" class="btn btn-secondary me-2">
                                        <i class="ri-arrow-left-line"></i> Back to List
                                    </a>
                                    <a href="edit_member.php?id=<?php echo $member_id; ?>" class="btn btn-primary">
                                        <i class="ri-edit-line"></i> Edit Member
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Left Column - Member Info -->
                        <div class="col-lg-8">
                            <!-- Personal Information -->
                            <div class="card mb-3">
                                <div class="card-header">
                                    <h4 class="header-title">Personal Information</h4>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-3 text-center mb-3">
                                            <?php if (!empty($member['photo'])): ?>
                                                <img src="../<?php echo htmlspecialchars($member['photo']); ?>" 
                                                     alt="Member Photo" 
                                                     class="img-thumbnail rounded-circle" 
                                                     style="width: 150px; height: 150px; object-fit: cover;">
                                            <?php else: ?>
                                                <div class="bg-secondary rounded-circle d-flex align-items-center justify-content-center mx-auto" 
                                                     style="width: 150px; height: 150px;">
                                                    <i class="ri-user-line text-white" style="font-size: 4rem;"></i>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="col-md-9">
                                            <h3><?php echo htmlspecialchars($member['fullname']); ?></h3>
                                            <p class="text-muted mb-2">
                                                <strong>Membership ID:</strong> <code><?php echo htmlspecialchars($member['membership_id']); ?></code>
                                            </p>
                                            <p class="mb-2">
                                                <strong>Email:</strong> <?php echo htmlspecialchars($member['email']); ?>
                                            </p>
                                            <p class="mb-2">
                                                <strong>Phone:</strong> <?php echo htmlspecialchars($member['phone']); ?>
                                            </p>
                                            <p class="mb-2">
                                                <strong>Sex:</strong> <?php echo htmlspecialchars($member['sex']); ?>
                                            </p>
                                            <p class="mb-0">
                                                <strong>Qualification:</strong> 
                                                <span class="badge bg-primary"><?php echo htmlspecialchars($member['qualification']); ?></span>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Address Information -->
                            <div class="card mb-3">
                                <div class="card-header">
                                    <h4 class="header-title">Address Information</h4>
                                </div>
                                <div class="card-body">
                                    <p><?php echo nl2br(htmlspecialchars($member['address'])); ?></p>
                                </div>
                            </div>

                            <!-- Membership Information -->
                            <div class="card mb-3">
                                <div class="card-header">
                                    <h4 class="header-title">Membership Information</h4>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <p><strong>Approval Status:</strong> 
                                                <span class="badge bg-<?php 
                                                    echo $member['approval_status'] == 'approved' ? 'success' : 
                                                        ($member['approval_status'] == 'pending' ? 'warning' : 'danger'); 
                                                ?>">
                                                    <?php echo ucfirst($member['approval_status']); ?>
                                                </span>
                                            </p>
                                            <?php if ($member['approved_by_name']): ?>
                                                <p><strong>Approved By:</strong> <?php echo htmlspecialchars($member['approved_by_name']); ?></p>
                                            <?php endif; ?>
                                            <?php if ($member['approved_at']): ?>
                                                <p><strong>Approved At:</strong> <?php echo date('M d, Y H:i:s', strtotime($member['approved_at'])); ?></p>
                                            <?php endif; ?>
                                        </div>
                                        <div class="col-md-6">
                                            <p><strong>Payment Duration:</strong> <?php echo htmlspecialchars($member['payment_duration']); ?></p>
                                            <p><strong>Payment Option:</strong> <?php echo htmlspecialchars($member['payment_option']); ?></p>
                                            <?php if ($member['expiry_date']): ?>
                                                <p><strong>Expiry Date:</strong> 
                                                    <span class="badge bg-<?php echo $is_expired ? 'danger' : 'success'; ?>">
                                                        <?php echo date('M d, Y', strtotime($member['expiry_date'])); ?>
                                                        <?php echo $is_expired ? ' (Expired)' : ''; ?>
                                                    </span>
                                                </p>
                                            <?php else: ?>
                                                <p><strong>Expiry Date:</strong> <span class="text-muted">No expiry</span></p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- ID Card Information -->
                            <div class="card mb-3">
                                <div class="card-header">
                                    <h4 class="header-title">ID Card Information</h4>
                                </div>
                                <div class="card-body">
                                    <?php if ($id_card_generated): ?>
                                        <p><strong>Status:</strong> <span class="badge bg-success">Generated</span></p>
                                        <?php if ($member['id_card_generated_at']): ?>
                                            <p><strong>Generated At:</strong> <?php echo date('M d, Y H:i:s', strtotime($member['id_card_generated_at'])); ?></p>
                                        <?php endif; ?>
                                        <?php if ($verification_code): ?>
                                            <p><strong>Verification Code:</strong> <code><?php echo htmlspecialchars($verification_code); ?></code></p>
                                        <?php endif; ?>
                                        <div class="mt-3">
                                            <a href="member-id-card-print.php?member_id=<?php echo $member_id; ?>" 
                                               class="btn btn-info me-2">
                                                <i class="ri-eye-line"></i> View ID Card
                                            </a>
                                            <a href="id_card_generate.php?member_id=<?php echo $member_id; ?>&regenerate=1" 
                                               class="btn btn-warning">
                                                <i class="ri-refresh-line"></i> Regenerate ID Card
                                            </a>
                                        </div>
                                    <?php else: ?>
                                        <p><strong>Status:</strong> <span class="badge bg-warning">Not Generated</span></p>
                                        <a href="id_card_generate.php?member_id=<?php echo $member_id; ?>" class="btn btn-primary">
                                            <i class="ri-add-circle-line"></i> Generate ID Card
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Documents -->
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Documents</h4>
                                </div>
                                <div class="card-body">
                                    <?php if (!empty($member['bank_slip'])): ?>
                                        <p><strong>Bank Slip:</strong> 
                                            <a href="../<?php echo htmlspecialchars($member['bank_slip']); ?>" 
                                               target="_blank" 
                                               class="btn btn-sm btn-light">
                                                <i class="ri-file-line"></i> View Bank Slip
                                            </a>
                                        </p>
                                    <?php else: ?>
                                        <p class="text-muted">No bank slip uploaded</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Right Column - Quick Actions & Info -->
                        <div class="col-lg-4">
                            <!-- Quick Actions -->
                            <div class="card mb-3">
                                <div class="card-header">
                                    <h4 class="header-title">Quick Actions</h4>
                                </div>
                                <div class="card-body">
                                    <div class="d-grid gap-2">
                                        <?php if ($member['approval_status'] == 'pending'): ?>
                                            <a href="include/approve_member.php?id=<?php echo $member_id; ?>" 
                                               class="btn btn-success"
                                               onclick="return confirm('Approve this member?')">
                                                <i class="ri-check-line"></i> Approve Member
                                            </a>
                                        <?php endif; ?>
                                        <a href="edit_member.php?id=<?php echo $member_id; ?>" class="btn btn-primary">
                                            <i class="ri-edit-line"></i> Edit Member
                                        </a>
                                        <a href="member_notes.php?member_id=<?php echo $member_id; ?>" class="btn btn-info">
                                            <i class="ri-file-text-line"></i> View Notes
                                        </a>
                                        <a href="member_activity_log.php?id=<?php echo $member_id; ?>" class="btn btn-secondary">
                                            <i class="ri-history-line"></i> Activity Log
                                        </a>
                                        <?php if ($member['approval_status'] == 'approved' && !$is_expired): ?>
                                            <a href="renew_membership.php?id=<?php echo $member_id; ?>" class="btn btn-warning">
                                                <i class="ri-refresh-line"></i> Renew Membership
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Registration Info -->
                            <div class="card mb-3">
                                <div class="card-header">
                                    <h4 class="header-title">Registration Information</h4>
                                </div>
                                <div class="card-body">
                                    <p class="mb-2"><strong>Registered:</strong> <?php echo date('M d, Y H:i:s', strtotime($member['created_at'])); ?></p>
                                    <p class="mb-0"><strong>Member ID:</strong> <code><?php echo $member_id; ?></code></p>
                                </div>
                            </div>

                            <!-- Status Summary -->
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Status Summary</h4>
                                </div>
                                <div class="card-body">
                                    <div class="mb-2">
                                        <strong>Approval:</strong> 
                                        <span class="badge bg-<?php 
                                            echo $member['approval_status'] == 'approved' ? 'success' : 
                                                ($member['approval_status'] == 'pending' ? 'warning' : 'danger'); 
                                        ?>">
                                            <?php echo ucfirst($member['approval_status']); ?>
                                        </span>
                                    </div>
                                    <div class="mb-2">
                                        <strong>Membership:</strong> 
                                        <span class="badge bg-<?php echo $is_expired ? 'danger' : 'success'; ?>">
                                            <?php echo $is_expired ? 'Expired' : 'Active'; ?>
                                        </span>
                                    </div>
                                    <div class="mb-0">
                                        <strong>ID Card:</strong> 
                                        <span class="badge bg-<?php echo $id_card_generated ? 'success' : 'warning'; ?>">
                                            <?php echo $id_card_generated ? 'Generated' : 'Pending'; ?>
                                        </span>
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

    <!-- Vendor js -->
    <script src="assets/js/vendor.min.js"></script>
    <script src="assets/js/app.min.js"></script>

</body>
</html>

