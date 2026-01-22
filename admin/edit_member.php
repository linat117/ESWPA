<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: auth-login.php");
    exit();
}

include 'header.php';
include 'include/conn.php';

$member_id = intval($_GET['id'] ?? 0);
$error = '';
$success = '';

if ($member_id <= 0) {
    header("Location: members_list.php?error=Invalid member ID");
    exit();
}

// Get member details
$query = "SELECT * FROM registrations WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $member_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: members_list.php?error=Member not found");
    exit();
}

$member = $result->fetch_assoc();
$stmt->close();

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    require_once 'include/update_member.php';
    $result = updateMemberDetails($member_id, $_POST, $_FILES);
    
    if ($result['success']) {
        $success = $result['message'];
        // Refresh member data
        $query = "SELECT * FROM registrations WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $member_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $member = $result->fetch_assoc();
        $stmt->close();
    } else {
        $error = $result['message'];
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
                    <div class="row">
                        <div class="col-12">
                            <div class="page-title-box d-flex justify-content-between align-items-center">
                                <h4 class="page-title">Edit Member</h4>
                                <div class="page-title-right">
                                    <a href="members_list.php" class="btn btn-outline-secondary">
                                        <i class="ri-arrow-left-line"></i> Back to Members
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if ($success): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?php echo htmlspecialchars($success); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php echo htmlspecialchars($error); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST" enctype="multipart/form-data" id="editMemberForm">
                        <div class="row">
                            <!-- Personal Information -->
                            <div class="col-12 col-lg-6">
                                <div class="card">
                                    <div class="card-header bg-primary text-white">
                                        <h5 class="mb-0"><i class="ri-user-line"></i> Personal Information</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label for="fullname" class="form-label">Full Name <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="fullname" name="fullname" 
                                                   value="<?php echo htmlspecialchars($member['fullname']); ?>" required>
                                        </div>

                                        <div class="mb-3">
                                            <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                            <input type="email" class="form-control" id="email" name="email" 
                                                   value="<?php echo htmlspecialchars($member['email']); ?>" required>
                                            <small class="text-muted">Email is unique and cannot be changed easily. Contact support if needed.</small>
                                        </div>

                                        <div class="mb-3">
                                            <label for="phone" class="form-label">Phone <span class="text-danger">*</span></label>
                                            <input type="tel" class="form-control" id="phone" name="phone" 
                                                   value="<?php echo htmlspecialchars($member['phone']); ?>" required>
                                        </div>

                                        <div class="mb-3">
                                            <label for="sex" class="form-label">Sex <span class="text-danger">*</span></label>
                                            <select class="form-select" id="sex" name="sex" required>
                                                <option value="Male" <?php echo $member['sex'] == 'Male' ? 'selected' : ''; ?>>Male</option>
                                                <option value="Female" <?php echo $member['sex'] == 'Female' ? 'selected' : ''; ?>>Female</option>
                                            </select>
                                        </div>

                                        <div class="mb-3">
                                            <label for="address" class="form-label">Address <span class="text-danger">*</span></label>
                                            <textarea class="form-control" id="address" name="address" rows="3" required><?php echo htmlspecialchars($member['address']); ?></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Qualification & Membership -->
                            <div class="col-12 col-lg-6">
                                <div class="card">
                                    <div class="card-header bg-success text-white">
                                        <h5 class="mb-0"><i class="ri-graduation-cap-line"></i> Qualification & Membership</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label for="qualification" class="form-label">Qualification <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="qualification" name="qualification" 
                                                   value="<?php echo htmlspecialchars($member['qualification']); ?>" required>
                                        </div>

                                        <?php if (!empty($member['graduation_date'])): ?>
                                        <div class="mb-3">
                                            <label for="graduation_date" class="form-label">Graduation Date</label>
                                            <input type="date" class="form-control" id="graduation_date" name="graduation_date" 
                                                   value="<?php echo htmlspecialchars($member['graduation_date']); ?>">
                                        </div>
                                        <?php endif; ?>

                                        <div class="mb-3">
                                            <label for="membership_id" class="form-label">Membership ID</label>
                                            <input type="text" class="form-control" id="membership_id" name="membership_id" 
                                                   value="<?php echo htmlspecialchars($member['membership_id'] ?? ''); ?>" readonly>
                                            <small class="text-muted">Membership ID cannot be changed</small>
                                        </div>

                                        <div class="mb-3">
                                            <label for="approval_status" class="form-label">Approval Status</label>
                                            <select class="form-select" id="approval_status" name="approval_status">
                                                <option value="pending" <?php echo ($member['approval_status'] ?? 'pending') == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                <option value="approved" <?php echo ($member['approval_status'] ?? '') == 'approved' ? 'selected' : ''; ?>>Approved</option>
                                                <option value="rejected" <?php echo ($member['approval_status'] ?? '') == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                            </select>
                                        </div>

                                        <div class="mb-3">
                                            <label for="status" class="form-label">Membership Status</label>
                                            <select class="form-select" id="status" name="status">
                                                <option value="pending" <?php echo ($member['status'] ?? 'pending') == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                <option value="active" <?php echo ($member['status'] ?? '') == 'active' ? 'selected' : ''; ?>>Active</option>
                                                <option value="expired" <?php echo ($member['status'] ?? '') == 'expired' ? 'selected' : ''; ?>>Expired</option>
                                            </select>
                                        </div>

                                        <?php if (!empty($member['expiry_date'])): ?>
                                        <div class="mb-3">
                                            <label for="expiry_date" class="form-label">Expiry Date</label>
                                            <input type="date" class="form-control" id="expiry_date" name="expiry_date" 
                                                   value="<?php echo htmlspecialchars($member['expiry_date']); ?>">
                                            <small class="text-muted">Change this date to manually renew membership</small>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Payment Information -->
                        <div class="row mt-3">
                            <div class="col-12 col-lg-6">
                                <div class="card">
                                    <div class="card-header bg-info text-white">
                                        <h5 class="mb-0"><i class="ri-money-dollar-circle-line"></i> Payment Information</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label for="payment_duration" class="form-label">Payment Duration</label>
                                            <input type="text" class="form-control" id="payment_duration" name="payment_duration" 
                                                   value="<?php echo htmlspecialchars($member['payment_duration']); ?>" readonly>
                                        </div>

                                        <div class="mb-3">
                                            <label for="payment_option" class="form-label">Payment Option</label>
                                            <input type="text" class="form-control" id="payment_option" name="payment_option" 
                                                   value="<?php echo htmlspecialchars($member['payment_option']); ?>" readonly>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Photo -->
                            <div class="col-12 col-lg-6">
                                <div class="card">
                                    <div class="card-header bg-warning text-white">
                                        <h5 class="mb-0"><i class="ri-image-line"></i> Photo</h5>
                                    </div>
                                    <div class="card-body">
                                        <?php if (!empty($member['photo'])): ?>
                                            <div class="mb-3">
                                                <img src="../<?php echo htmlspecialchars($member['photo']); ?>" 
                                                     alt="Member Photo" 
                                                     class="img-thumbnail" 
                                                     style="max-width: 200px; max-height: 200px;">
                                            </div>
                                        <?php endif; ?>
                                        <div class="mb-3">
                                            <label for="photo" class="form-label">Update Photo</label>
                                            <input type="file" class="form-control" id="photo" name="photo" accept="image/*">
                                            <small class="text-muted">Accepted formats: JPG, PNG, WEBP. Max size: 2MB</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="row mt-3">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <a href="members_list.php" class="btn btn-outline-secondary">
                                                <i class="ri-close-line"></i> Cancel
                                            </a>
                                            <button type="submit" class="btn btn-primary">
                                                <i class="ri-save-line"></i> Save Changes
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <?php include 'footer.php'; ?>
        </div>
    </div>

    <script src="assets/js/vendor.min.js"></script>
    <script src="assets/js/app.min.js"></script>
</body>
</html>

