<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: auth-login.php");
    exit();
}

include 'include/conn.php';
include 'header.php';

$verification_code = $_GET['code'] ?? '';
$search_query = $_GET['search'] ?? '';
$verification_result = null;
$verification_history = [];

// Handle verification code lookup
if ($verification_code) {
    $stmt = $conn->prepare("SELECT iv.*, r.fullname, r.membership_id, r.email, r.phone, r.qualification, 
                                   r.approval_status, r.expiry_date, r.id_card_generated, r.photo
                            FROM id_card_verification iv
                            LEFT JOIN registrations r ON iv.membership_id = r.membership_id
                            WHERE iv.verification_code = ?");
    $stmt->bind_param("s", $verification_code);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $verification_result = $result->fetch_assoc();
        
        // Record verification scan
        $updateStmt = $conn->prepare("UPDATE id_card_verification 
                                     SET scanned_at = NOW(), 
                                         ip_address = ?, 
                                         user_agent = ? 
                                     WHERE verification_code = ?");
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $updateStmt->bind_param("sss", $ip_address, $user_agent, $verification_code);
        $updateStmt->execute();
        $updateStmt->close();
    }
    $stmt->close();
}

// Get verification history (recent scans)
$historyQuery = "SELECT iv.*, r.fullname, r.membership_id 
                 FROM id_card_verification iv
                 LEFT JOIN registrations r ON iv.membership_id = r.membership_id
                 WHERE iv.scanned_at IS NOT NULL
                 ORDER BY iv.scanned_at DESC
                 LIMIT 50";
$historyResult = $conn->query($historyQuery);
while ($row = $historyResult->fetch_assoc()) {
    $verification_history[] = $row;
}

// Statistics
$stats = [];
$stats['total_codes'] = $conn->query("SELECT COUNT(*) as total FROM id_card_verification")->fetch_assoc()['total'];
$stats['scanned_codes'] = $conn->query("SELECT COUNT(*) as total FROM id_card_verification WHERE scanned_at IS NOT NULL")->fetch_assoc()['total'];
$stats['unscanned_codes'] = $stats['total_codes'] - $stats['scanned_codes'];
$stats['recent_scans'] = $conn->query("SELECT COUNT(*) as total FROM id_card_verification WHERE scanned_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetch_assoc()['total'];
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
                                <h4 class="page-title">ID Card Verification</h4>
                                <div>
                                    <a href="digital_id_dashboard.php" class="btn btn-secondary me-2">
                                        <i class="ri-dashboard-line"></i> Dashboard
                                    </a>
                                    <a href="id_cards_list.php" class="btn btn-secondary">
                                        <i class="ri-list-check"></i> All ID Cards
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Statistics -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-3">
                            <div class="card widget-flat text-bg-primary">
                                <div class="card-body">
                                    <div class="float-end">
                                        <i class="ri-qr-code-line widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0">Total Codes</h6>
                                    <h2 class="my-2"><?php echo number_format($stats['total_codes']); ?></h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card widget-flat text-bg-success">
                                <div class="card-body">
                                    <div class="float-end">
                                        <i class="ri-checkbox-circle-line widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0">Scanned</h6>
                                    <h2 class="my-2"><?php echo number_format($stats['scanned_codes']); ?></h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card widget-flat text-bg-warning">
                                <div class="card-body">
                                    <div class="float-end">
                                        <i class="ri-time-line widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0">Unscanned</h6>
                                    <h2 class="my-2"><?php echo number_format($stats['unscanned_codes']); ?></h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card widget-flat text-bg-info">
                                <div class="card-body">
                                    <div class="float-end">
                                        <i class="ri-calendar-line widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0">Recent (7 Days)</h6>
                                    <h2 class="my-2"><?php echo number_format($stats['recent_scans']); ?></h2>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Verification Form -->
                    <div class="row mb-3">
                        <div class="col-lg-6">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Verify ID Card</h4>
                                </div>
                                <div class="card-body">
                                    <form method="GET">
                                        <div class="mb-3">
                                            <label class="form-label">Verification Code</label>
                                            <input type="text" 
                                                   name="code" 
                                                   class="form-control" 
                                                   value="<?php echo htmlspecialchars($verification_code); ?>" 
                                                   placeholder="Enter verification code or scan QR code">
                                            <small class="text-muted">Enter the verification code from the ID card QR code</small>
                                        </div>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="ri-search-line"></i> Verify
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <?php if ($verification_result): ?>
                                <div class="card border-<?php echo $verification_result['approval_status'] == 'approved' && 
                                                               ($verification_result['expiry_date'] == null || strtotime($verification_result['expiry_date']) >= time()) ? 'success' : 'danger'; ?>">
                                    <div class="card-header bg-<?php echo $verification_result['approval_status'] == 'approved' && 
                                                                   ($verification_result['expiry_date'] == null || strtotime($verification_result['expiry_date']) >= time()) ? 'success' : 'danger'; ?> text-white">
                                        <h4 class="header-title mb-0">
                                            <?php if ($verification_result['approval_status'] == 'approved' && 
                                                      ($verification_result['expiry_date'] == null || strtotime($verification_result['expiry_date']) >= time())): ?>
                                                <i class="ri-checkbox-circle-line"></i> Valid ID Card
                                            <?php else: ?>
                                                <i class="ri-close-circle-line"></i> Invalid/Expired ID Card
                                            <?php endif; ?>
                                        </h4>
                                    </div>
                                    <div class="card-body">
                                        <?php if ($verification_result['fullname']): ?>
                                            <div class="row mb-3">
                                                <?php if (!empty($verification_result['photo'])): ?>
                                                    <div class="col-auto">
                                                        <img src="../<?php echo htmlspecialchars($verification_result['photo']); ?>" 
                                                             alt="Member Photo" 
                                                             class="rounded" 
                                                             style="width: 100px; height: 100px; object-fit: cover;">
                                                    </div>
                                                <?php endif; ?>
                                                <div class="col">
                                                    <h5><?php echo htmlspecialchars($verification_result['fullname']); ?></h5>
                                                    <p class="mb-1"><strong>Membership ID:</strong> <code><?php echo htmlspecialchars($verification_result['membership_id']); ?></code></p>
                                                    <p class="mb-1"><strong>Email:</strong> <?php echo htmlspecialchars($verification_result['email']); ?></p>
                                                    <p class="mb-1"><strong>Phone:</strong> <?php echo htmlspecialchars($verification_result['phone']); ?></p>
                                                    <p class="mb-1"><strong>Qualification:</strong> <?php echo htmlspecialchars($verification_result['qualification']); ?></p>
                                                    <?php if ($verification_result['expiry_date']): ?>
                                                        <p class="mb-0">
                                                            <strong>Expiry Date:</strong> 
                                                            <span class="badge bg-<?php echo strtotime($verification_result['expiry_date']) >= time() ? 'success' : 'danger'; ?>">
                                                                <?php echo date('M d, Y', strtotime($verification_result['expiry_date'])); ?>
                                                            </span>
                                                        </p>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <hr>
                                            <p class="mb-1"><strong>Verification Code:</strong> <code><?php echo htmlspecialchars($verification_result['verification_code']); ?></code></p>
                                            <?php if ($verification_result['scanned_at']): ?>
                                                <p class="mb-0"><strong>Last Scanned:</strong> <?php echo date('M d, Y H:i:s', strtotime($verification_result['scanned_at'])); ?></p>
                                            <?php else: ?>
                                                <p class="mb-0 text-muted">First time scanned</p>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <div class="alert alert-warning">
                                                <i class="ri-alert-line"></i> Verification code found but member information not available.
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php elseif ($verification_code): ?>
                                <div class="card border-danger">
                                    <div class="card-header bg-danger text-white">
                                        <h4 class="header-title mb-0"><i class="ri-close-circle-line"></i> Invalid Verification Code</h4>
                                    </div>
                                    <div class="card-body">
                                        <p>The verification code you entered is not valid or does not exist in our system.</p>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Verification History -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Recent Verification Scans</h4>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-hover mb-0" id="verificationHistoryTable">
                                            <thead>
                                                <tr>
                                                    <th>Member</th>
                                                    <th>Membership ID</th>
                                                    <th>Verification Code</th>
                                                    <th>Scanned At</th>
                                                    <th>IP Address</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (empty($verification_history)): ?>
                                                    <tr>
                                                        <td colspan="6" class="text-center text-muted">No verification scans yet</td>
                                                    </tr>
                                                <?php else: ?>
                                                    <?php foreach ($verification_history as $scan): ?>
                                                        <tr>
                                                            <td>
                                                                <strong><?php echo htmlspecialchars($scan['fullname'] ?? 'N/A'); ?></strong>
                                                            </td>
                                                            <td>
                                                                <code><?php echo htmlspecialchars($scan['membership_id']); ?></code>
                                                            </td>
                                                            <td>
                                                                <code class="small"><?php echo substr(htmlspecialchars($scan['verification_code']), 0, 16); ?>...</code>
                                                            </td>
                                                            <td>
                                                                <?php echo date('M d, Y H:i:s', strtotime($scan['scanned_at'])); ?>
                                                            </td>
                                                            <td>
                                                                <small><?php echo htmlspecialchars($scan['ip_address'] ?? 'N/A'); ?></small>
                                                            </td>
                                                            <td>
                                                                <span class="badge bg-success">Verified</span>
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
            $('#verificationHistoryTable').DataTable({
                "order": [[3, "desc"]],
                "pageLength": 25
            });
        });
    </script>

</body>
</html>

