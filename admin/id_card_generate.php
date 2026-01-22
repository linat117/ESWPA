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
$member_id = $_GET['member_id'] ?? 0;
$regenerate = isset($_GET['regenerate']) && $_GET['regenerate'] == 1;

// Handle ID card generation
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['member_id'])) {
    $member_id = intval($_POST['member_id']);
    
    // Get member details
    $stmt = $conn->prepare("SELECT * FROM registrations WHERE id = ? AND approval_status = 'approved'");
    $stmt->bind_param("i", $member_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $error_message = "Member not found or not approved";
    } else {
        $member = $result->fetch_assoc();
        
        // Check if membership is expired
        if (!empty($member['expiry_date'])) {
            $expiryDate = new DateTime($member['expiry_date']);
            $today = new DateTime();
            if ($expiryDate < $today) {
                $error_message = "Cannot generate ID card for expired membership";
            }
        }
        
        if (empty($error_message)) {
            // Generate verification code if not exists
            $verificationCode = bin2hex(random_bytes(16)); // 32 character code
            
            // Check if verification code already exists
            $checkStmt = $conn->prepare("SELECT verification_code FROM id_card_verification WHERE membership_id = ?");
            $checkStmt->bind_param("s", $member['membership_id']);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();
            
            if ($checkResult->num_rows > 0) {
                // Use existing code
                $existing = $checkResult->fetch_assoc();
                $verificationCode = $existing['verification_code'];
            } else {
                // Insert new verification code
                $insertStmt = $conn->prepare("INSERT INTO id_card_verification (membership_id, verification_code) VALUES (?, ?)");
                $insertStmt->bind_param("ss", $member['membership_id'], $verificationCode);
                $insertStmt->execute();
                $insertStmt->close();
            }
            $checkStmt->close();
            
            // Update registrations table
            $updateStmt = $conn->prepare("UPDATE registrations SET id_card_generated = 1, id_card_generated_at = NOW() WHERE id = ?");
            $updateStmt->bind_param("i", $member_id);
            
            if ($updateStmt->execute()) {
                $success_message = "ID card generated successfully for " . htmlspecialchars($member['fullname']);
            } else {
                $error_message = "Failed to update ID card status";
            }
            $updateStmt->close();
        }
    }
    $stmt->close();
}

// Get member details if member_id is provided
$member = null;
if ($member_id > 0) {
    $stmt = $conn->prepare("SELECT * FROM registrations WHERE id = ? AND approval_status = 'approved'");
    $stmt->bind_param("i", $member_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $member = $result->fetch_assoc();
    }
    $stmt->close();
}

// Get all eligible members (approved, not expired, optionally without ID card)
$eligibleQuery = "SELECT id, fullname, membership_id, email, id_card_generated, expiry_date 
                  FROM registrations 
                  WHERE approval_status = 'approved' 
                  AND (expiry_date IS NULL OR expiry_date >= CURDATE())
                  ORDER BY fullname ASC";
$eligibleResult = $conn->query($eligibleQuery);
$eligible_members = $eligibleResult->fetch_all(MYSQLI_ASSOC);
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
                                <h4 class="page-title">Generate ID Card</h4>
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

                    <div class="row">
                        <div class="col-lg-8">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Select Member</h4>
                                </div>
                                <div class="card-body">
                                    <form method="POST" id="generateForm">
                                        <input type="hidden" name="member_id" id="selected_member_id">
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Search and Select Member</label>
                                            <select class="form-select" id="member_select" required>
                                                <option value="">-- Select a member --</option>
                                                <?php foreach ($eligible_members as $m): ?>
                                                    <option value="<?php echo $m['id']; ?>" 
                                                            data-name="<?php echo htmlspecialchars($m['fullname']); ?>"
                                                            data-membership="<?php echo htmlspecialchars($m['membership_id']); ?>"
                                                            data-email="<?php echo htmlspecialchars($m['email']); ?>"
                                                            data-generated="<?php echo $m['id_card_generated']; ?>"
                                                            <?php echo ($member && $member['id'] == $m['id']) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($m['fullname']); ?> 
                                                        (<?php echo htmlspecialchars($m['membership_id']); ?>)
                                                        <?php if ($m['id_card_generated'] == 1): ?>
                                                            - [Already Generated]
                                                        <?php endif; ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <?php if ($member): ?>
                                        <div class="alert alert-info">
                                            <h6>Member Information:</h6>
                                            <p class="mb-1"><strong>Name:</strong> <?php echo htmlspecialchars($member['fullname']); ?></p>
                                            <p class="mb-1"><strong>Membership ID:</strong> <code><?php echo htmlspecialchars($member['membership_id']); ?></code></p>
                                            <p class="mb-1"><strong>Email:</strong> <?php echo htmlspecialchars($member['email']); ?></p>
                                            <p class="mb-1"><strong>Qualification:</strong> <?php echo htmlspecialchars($member['qualification']); ?></p>
                                            <?php if ($member['id_card_generated'] == 1): ?>
                                                <p class="mb-0 text-warning">
                                                    <i class="ri-alert-line"></i> This member already has an ID card. Generating again will update the verification code.
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                        <?php endif; ?>

                                        <div class="d-flex gap-2">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="ri-add-circle-line"></i> 
                                                <?php echo ($member && $member['id_card_generated'] == 1) ? 'Regenerate' : 'Generate'; ?> ID Card
                                            </button>
                                            <?php if ($member && $member['id_card_generated'] == 1): ?>
                                                <a href="member-id-card-print.php?member_id=<?php echo $member['id']; ?>" 
                                                   class="btn btn-info">
                                                    <i class="ri-eye-line"></i> View Current ID Card
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-4">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Quick Actions</h4>
                                </div>
                                <div class="card-body">
                                    <div class="d-grid gap-2">
                                        <a href="id_cards_list.php?status=pending" class="btn btn-warning">
                                            <i class="ri-time-line"></i> View Pending ID Cards
                                        </a>
                                        <a href="id_cards_list.php?status=generated" class="btn btn-success">
                                            <i class="ri-id-card-line"></i> View Generated ID Cards
                                        </a>
                                        <a href="id_card_bulk_generate.php" class="btn btn-primary">
                                            <i class="ri-add-box-line"></i> Bulk Generate ID Cards
                                        </a>
                                        <a href="id_card_templates.php" class="btn btn-secondary">
                                            <i class="ri-file-edit-line"></i> Manage Templates
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <div class="card mt-3">
                                <div class="card-header">
                                    <h4 class="header-title">Information</h4>
                                </div>
                                <div class="card-body">
                                    <p class="text-muted small">
                                        <i class="ri-information-line"></i> ID cards can only be generated for approved members with active memberships.
                                    </p>
                                    <p class="text-muted small">
                                        <i class="ri-information-line"></i> Each ID card includes a unique QR code for verification.
                                    </p>
                                    <p class="text-muted small">
                                        <i class="ri-information-line"></i> Regenerating an ID card will create a new verification code.
                                    </p>
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

    <script>
        document.getElementById('member_select').addEventListener('change', function() {
            var selectedOption = this.options[this.selectedIndex];
            document.getElementById('selected_member_id').value = this.value;
            
            if (this.value) {
                var generated = selectedOption.getAttribute('data-generated') == '1';
                var form = document.getElementById('generateForm');
                var submitBtn = form.querySelector('button[type="submit"]');
                
                if (generated) {
                    submitBtn.innerHTML = '<i class="ri-refresh-line"></i> Regenerate ID Card';
                    submitBtn.className = 'btn btn-warning';
                } else {
                    submitBtn.innerHTML = '<i class="ri-add-circle-line"></i> Generate ID Card';
                    submitBtn.className = 'btn btn-primary';
                }
            }
        });

        // Set initial member if provided in URL
        <?php if ($member_id > 0 && $member): ?>
            document.getElementById('selected_member_id').value = <?php echo $member_id; ?>;
        <?php endif; ?>
    </script>

</body>
</html>

