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
$generated_count = 0;
$failed_count = 0;
$results = [];

// Handle bulk generation
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['member_ids'])) {
    $member_ids = $_POST['member_ids'];
    
    if (!empty($member_ids) && is_array($member_ids)) {
        $ids = array_map('intval', $member_ids);
        $ids = array_filter($ids, function($id) { return $id > 0; });
        
        if (!empty($ids)) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $types = str_repeat('i', count($ids));
            
            // Get eligible members
            $stmt = $conn->prepare("SELECT id, fullname, membership_id, expiry_date, id_card_generated 
                                   FROM registrations 
                                   WHERE id IN ($placeholders) 
                                   AND approval_status = 'approved' 
                                   AND (expiry_date IS NULL OR expiry_date >= CURDATE())");
            $stmt->bind_param($types, ...$ids);
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($member = $result->fetch_assoc()) {
                // Generate verification code if not exists
                $verificationCode = bin2hex(random_bytes(16));
                
                // Check if verification code exists
                $checkStmt = $conn->prepare("SELECT verification_code FROM id_card_verification WHERE membership_id = ?");
                $checkStmt->bind_param("s", $member['membership_id']);
                $checkStmt->execute();
                $checkResult = $checkStmt->get_result();
                
                if ($checkResult->num_rows > 0) {
                    $existing = $checkResult->fetch_assoc();
                    $verificationCode = $existing['verification_code'];
                } else {
                    // Insert new verification code
                    $insertStmt = $conn->prepare("INSERT INTO id_card_verification (membership_id, verification_code) VALUES (?, ?)");
                    $insertStmt->bind_param("ss", $member['membership_id'], $verificationCode);
                    if ($insertStmt->execute()) {
                        // Success
                    }
                    $insertStmt->close();
                }
                $checkStmt->close();
                
                // Update registrations
                $updateStmt = $conn->prepare("UPDATE registrations SET id_card_generated = 1, id_card_generated_at = NOW() WHERE id = ?");
                $updateStmt->bind_param("i", $member['id']);
                
                if ($updateStmt->execute()) {
                    $generated_count++;
                    $results[] = ['member' => $member['fullname'], 'status' => 'success', 'message' => 'ID card generated'];
                } else {
                    $failed_count++;
                    $results[] = ['member' => $member['fullname'], 'status' => 'error', 'message' => 'Failed to update'];
                }
                $updateStmt->close();
            }
            $stmt->close();
            
            if ($generated_count > 0) {
                $success_message = "Successfully generated {$generated_count} ID card(s)";
            }
            if ($failed_count > 0) {
                $error_message = "Failed to generate {$failed_count} ID card(s)";
            }
        }
    }
}

// Get eligible members (approved, not expired, without ID card)
$eligibleQuery = "SELECT id, fullname, membership_id, email, id_card_generated, expiry_date, approved_at
                  FROM registrations 
                  WHERE approval_status = 'approved' 
                  AND (expiry_date IS NULL OR expiry_date >= CURDATE())
                  AND (id_card_generated = 0 OR id_card_generated IS NULL)
                  ORDER BY approved_at DESC";
$eligibleResult = $conn->query($eligibleQuery);
$eligible_members = $eligibleResult->fetch_all(MYSQLI_ASSOC);

$total_eligible = count($eligible_members);
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
                                <h4 class="page-title">Bulk Generate ID Cards</h4>
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

                    <!-- Summary Card -->
                    <div class="row mb-3">
                        <div class="col-12">
                            <div class="card bg-primary bg-opacity-10 border-primary">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-grow-1">
                                            <h6 class="text-muted mb-1">Eligible Members for ID Card Generation</h6>
                                            <h3 class="mb-0"><?php echo number_format($total_eligible); ?> members</h3>
                                            <small class="text-muted">Approved members without ID cards</small>
                                        </div>
                                        <i class="ri-user-line fs-1 text-primary opacity-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Results (if any) -->
                    <?php if (!empty($results)): ?>
                    <div class="row mb-3">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Generation Results</h4>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Member</th>
                                                    <th>Status</th>
                                                    <th>Message</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($results as $result): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($result['member']); ?></td>
                                                        <td>
                                                            <span class="badge bg-<?php echo $result['status'] == 'success' ? 'success' : 'danger'; ?>">
                                                                <?php echo ucfirst($result['status']); ?>
                                                            </span>
                                                        </td>
                                                        <td><?php echo htmlspecialchars($result['message']); ?></td>
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

                    <div class="row">
                        <div class="col-lg-8">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h4 class="header-title">Select Members</h4>
                                    <div>
                                        <button type="button" class="btn btn-sm btn-primary" onclick="selectAll()">
                                            <i class="ri-checkbox-line"></i> Select All
                                        </button>
                                        <button type="button" class="btn btn-sm btn-secondary" onclick="clearSelection()">
                                            <i class="ri-checkbox-blank-line"></i> Clear All
                                        </button>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <form method="POST" id="bulkForm">
                                        <?php if (empty($eligible_members)): ?>
                                            <div class="alert alert-info">
                                                <i class="ri-information-line"></i> No eligible members found. All approved members already have ID cards generated.
                                            </div>
                                        <?php else: ?>
                                            <div class="table-responsive">
                                                <table class="table table-hover mb-0" id="membersTable">
                                                    <thead>
                                                        <tr>
                                                            <th width="50">
                                                                <input type="checkbox" id="select-all" onchange="toggleAll(this)">
                                                            </th>
                                                            <th>Member</th>
                                                            <th>Membership ID</th>
                                                            <th>Email</th>
                                                            <th>Approved</th>
                                                            <th>Status</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($eligible_members as $member): ?>
                                                            <tr>
                                                                <td>
                                                                    <input type="checkbox" 
                                                                           class="member-checkbox" 
                                                                           name="member_ids[]" 
                                                                           value="<?php echo $member['id']; ?>">
                                                                </td>
                                                                <td>
                                                                    <strong><?php echo htmlspecialchars($member['fullname']); ?></strong>
                                                                </td>
                                                                <td>
                                                                    <code><?php echo htmlspecialchars($member['membership_id']); ?></code>
                                                                </td>
                                                                <td><?php echo htmlspecialchars($member['email']); ?></td>
                                                                <td>
                                                                    <?php echo date('M d, Y', strtotime($member['approved_at'])); ?>
                                                                </td>
                                                                <td>
                                                                    <span class="badge bg-warning">Pending ID Card</span>
                                                                </td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                            
                                            <div class="mt-3">
                                                <div class="alert alert-info">
                                                    <strong>Selected:</strong> <span id="selectedCount">0</span> member(s)
                                                </div>
                                                <button type="submit" class="btn btn-primary" id="generateBtn" disabled>
                                                    <i class="ri-add-box-line"></i> Generate ID Cards for Selected Members
                                                </button>
                                            </div>
                                        <?php endif; ?>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-4">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Information</h4>
                                </div>
                                <div class="card-body">
                                    <p class="text-muted small">
                                        <i class="ri-information-line"></i> Only approved members with active memberships are eligible for ID card generation.
                                    </p>
                                    <p class="text-muted small">
                                        <i class="ri-information-line"></i> Each ID card will include a unique QR code for verification.
                                    </p>
                                    <p class="text-muted small">
                                        <i class="ri-information-line"></i> Bulk generation may take a few moments depending on the number of selected members.
                                    </p>
                                </div>
                            </div>

                            <div class="card mt-3">
                                <div class="card-header">
                                    <h4 class="header-title">Quick Actions</h4>
                                </div>
                                <div class="card-body">
                                    <div class="d-grid gap-2">
                                        <a href="id_card_generate.php" class="btn btn-primary">
                                            <i class="ri-add-circle-line"></i> Generate Single ID Card
                                        </a>
                                        <a href="id_cards_list.php?status=pending" class="btn btn-warning">
                                            <i class="ri-time-line"></i> View Pending ID Cards
                                        </a>
                                        <a href="id_cards_list.php" class="btn btn-secondary">
                                            <i class="ri-list-check"></i> View All ID Cards
                                        </a>
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
            $('#membersTable').DataTable({
                "order": [[4, "desc"]],
                "pageLength": 25
            });
        });

        function updateSelection() {
            var selected = $('.member-checkbox:checked').length;
            $('#selectedCount').text(selected);
            $('#generateBtn').prop('disabled', selected === 0);
        }

        function toggleAll(checkbox) {
            $('.member-checkbox').prop('checked', checkbox.checked);
            updateSelection();
        }

        function selectAll() {
            $('.member-checkbox').prop('checked', true);
            $('#select-all').prop('checked', true);
            updateSelection();
        }

        function clearSelection() {
            $('.member-checkbox').prop('checked', false);
            $('#select-all').prop('checked', false);
            updateSelection();
        }

        $(document).on('change', '.member-checkbox', function() {
            updateSelection();
            var total = $('.member-checkbox').length;
            var checked = $('.member-checkbox:checked').length;
            $('#select-all').prop('checked', total === checked && total > 0);
        });

        $('#bulkForm').on('submit', function(e) {
            var selected = $('.member-checkbox:checked').length;
            if (selected === 0) {
                e.preventDefault();
                alert('Please select at least one member');
                return false;
            }
            if (!confirm('Are you sure you want to generate ID cards for ' + selected + ' member(s)?')) {
                e.preventDefault();
                return false;
            }
        });
    </script>

</body>
</html>

