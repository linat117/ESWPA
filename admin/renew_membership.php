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
    $renewal_months = intval($_POST['renewal_months'] ?? 0);
    $renewal_years = intval($_POST['renewal_years'] ?? 0);
    $custom_date = trim($_POST['custom_date'] ?? '');
    
    if ($renewal_months <= 0 && $renewal_years <= 0 && empty($custom_date)) {
        $error = "Please specify renewal duration or custom expiry date";
    } else {
        // Calculate new expiry date
        $currentExpiry = !empty($member['expiry_date']) ? new DateTime($member['expiry_date']) : new DateTime();
        $today = new DateTime();
        
        // Use the later of current expiry or today as base
        $baseDate = $currentExpiry > $today ? $currentExpiry : $today;
        
        if (!empty($custom_date)) {
            $newExpiry = new DateTime($custom_date);
        } else {
            $newExpiry = clone $baseDate;
            if ($renewal_years > 0) {
                $newExpiry->modify("+{$renewal_years} year");
            }
            if ($renewal_months > 0) {
                $newExpiry->modify("+{$renewal_months} month");
            }
        }
        
        // Update member
        $updateQuery = "UPDATE registrations SET 
                       expiry_date = ?,
                       status = 'active',
                       updated_at = NOW()
                       WHERE id = ?";
        $stmt = $conn->prepare($updateQuery);
        $expiryDateStr = $newExpiry->format('Y-m-d');
        $stmt->bind_param("si", $expiryDateStr, $member_id);
        
        if ($stmt->execute()) {
            $stmt->close();
            
            // Log activity
            $logQuery = "INSERT INTO audit_logs (user_id, action_type, description, ip_address) VALUES (?, ?, ?, ?)";
            $logStmt = $conn->prepare($logQuery);
            $admin_id = $_SESSION['user_id'];
            $action_type = 'membership_renewed';
            $description = "Renewed membership for member ID: $member_id. New expiry: " . $expiryDateStr;
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $logStmt->bind_param("isss", $admin_id, $action_type, $description, $ip_address);
            $logStmt->execute();
            $logStmt->close();
            
            // Create notification for member
            require_once '../include/notifications_handler.php';
            $title = "Membership Renewed";
            $message = "Your membership has been renewed. New expiry date: " . $newExpiry->format('F d, Y');
            createNotification($member_id, 'success', $title, $message, $member_id, 'membership_renewal');
            
            $success = "Membership renewed successfully! New expiry date: " . $newExpiry->format('F d, Y');
            
            // Refresh member data
            $query = "SELECT * FROM registrations WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $member_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $member = $result->fetch_assoc();
            $stmt->close();
        } else {
            $error = "Failed to renew membership: " . $conn->error;
            $stmt->close();
        }
    }
}

// Calculate current expiry info
$currentExpiry = null;
$daysRemaining = null;
if (!empty($member['expiry_date'])) {
    $currentExpiry = new DateTime($member['expiry_date']);
    $today = new DateTime();
    $daysRemaining = $today->diff($currentExpiry)->days;
    if ($currentExpiry < $today) {
        $daysRemaining = -$daysRemaining; // Negative if expired
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
                                <h4 class="page-title">Renew Membership</h4>
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

                    <div class="row">
                        <!-- Member Info -->
                        <div class="col-12 col-lg-6">
                            <div class="card">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="mb-0"><i class="ri-user-line"></i> Member Information</h5>
                                </div>
                                <div class="card-body">
                                    <p><strong>Name:</strong> <?php echo htmlspecialchars($member['fullname']); ?></p>
                                    <p><strong>Email:</strong> <?php echo htmlspecialchars($member['email']); ?></p>
                                    <p><strong>Membership ID:</strong> <?php echo htmlspecialchars($member['membership_id'] ?? 'N/A'); ?></p>
                                    <p><strong>Current Status:</strong> 
                                        <span class="badge bg-<?php echo $member['status'] == 'active' ? 'success' : ($member['status'] == 'expired' ? 'danger' : 'warning'); ?>">
                                            <?php echo ucfirst($member['status']); ?>
                                        </span>
                                    </p>
                                    <?php if ($currentExpiry): ?>
                                        <p><strong>Current Expiry:</strong> <?php echo $currentExpiry->format('F d, Y'); ?></p>
                                        <?php if ($daysRemaining !== null): ?>
                                            <?php if ($daysRemaining < 0): ?>
                                                <p class="text-danger"><strong>Expired:</strong> <?php echo abs($daysRemaining); ?> days ago</p>
                                            <?php else: ?>
                                                <p class="text-success"><strong>Days Remaining:</strong> <?php echo $daysRemaining; ?> days</p>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <p class="text-muted">No expiry date set</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Renewal Form -->
                        <div class="col-12 col-lg-6">
                            <div class="card">
                                <div class="card-header bg-success text-white">
                                    <h5 class="mb-0"><i class="ri-refresh-line"></i> Renew Membership</h5>
                                </div>
                                <div class="card-body">
                                    <form method="POST" id="renewalForm">
                                        <div class="mb-3">
                                            <label class="form-label">Renewal Duration</label>
                                            <div class="row">
                                                <div class="col-6">
                                                    <label for="renewal_years" class="form-label small">Years</label>
                                                    <input type="number" class="form-control" id="renewal_years" name="renewal_years" 
                                                           min="0" max="10" value="1">
                                                </div>
                                                <div class="col-6">
                                                    <label for="renewal_months" class="form-label small">Months</label>
                                                    <input type="number" class="form-control" id="renewal_months" name="renewal_months" 
                                                           min="0" max="11" value="0">
                                                </div>
                                            </div>
                                            <small class="text-muted">Or specify custom expiry date below</small>
                                        </div>

                                        <div class="mb-3">
                                            <label for="custom_date" class="form-label">Or Custom Expiry Date</label>
                                            <input type="date" class="form-control" id="custom_date" name="custom_date">
                                            <small class="text-muted">Leave empty to use duration above</small>
                                        </div>

                                        <div class="alert alert-info">
                                            <strong>New Expiry Date:</strong> <span id="newExpiryDate">-</span>
                                        </div>

                                        <div class="d-flex justify-content-between">
                                            <a href="members_list.php" class="btn btn-outline-secondary">
                                                <i class="ri-close-line"></i> Cancel
                                            </a>
                                            <button type="submit" class="btn btn-success">
                                                <i class="ri-check-line"></i> Renew Membership
                                            </button>
                                        </div>
                                    </form>
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
    <script src="assets/js/app.min.js"></script>
    <script>
        // Calculate new expiry date
        function calculateNewExpiry() {
            const years = parseInt(document.getElementById('renewal_years').value) || 0;
            const months = parseInt(document.getElementById('renewal_months').value) || 0;
            const customDate = document.getElementById('custom_date').value;
            
            if (customDate) {
                const date = new Date(customDate);
                document.getElementById('newExpiryDate').textContent = date.toLocaleDateString('en-US', { 
                    year: 'numeric', 
                    month: 'long', 
                    day: 'numeric' 
                });
            } else if (years > 0 || months > 0) {
                const baseDate = new Date('<?php echo $currentExpiry ? $currentExpiry->format('Y-m-d') : date('Y-m-d'); ?>');
                const today = new Date();
                const startDate = baseDate > today ? baseDate : today;
                
                const newDate = new Date(startDate);
                newDate.setFullYear(newDate.getFullYear() + years);
                newDate.setMonth(newDate.getMonth() + months);
                
                document.getElementById('newExpiryDate').textContent = newDate.toLocaleDateString('en-US', { 
                    year: 'numeric', 
                    month: 'long', 
                    day: 'numeric' 
                });
            } else {
                document.getElementById('newExpiryDate').textContent = '-';
            }
        }
        
        document.getElementById('renewal_years').addEventListener('input', calculateNewExpiry);
        document.getElementById('renewal_months').addEventListener('input', calculateNewExpiry);
        document.getElementById('custom_date').addEventListener('input', calculateNewExpiry);
        
        // Initial calculation
        calculateNewExpiry();
    </script>
</body>
</html>

