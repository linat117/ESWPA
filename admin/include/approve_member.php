<?php
session_start();
include 'conn.php';

// Check if user is logged in as admin
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

// Get parameters
$member_id = intval($_POST['member_id'] ?? 0);
$action = $_POST['action'] ?? '';
$reason = trim($_POST['reason'] ?? '');
$redirect_to_profile = isset($_POST['redirect']) && $_POST['redirect'] === 'profile';

function redirect_profile($member_id, $success, $message) {
    $param = $success ? 'success' : 'error';
    header('Location: ../member_profile.php?id=' . (int) $member_id . '&' . $param . '=' . urlencode($message));
    exit;
}

if ($member_id <= 0 || empty($action)) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit();
}

if (!in_array($action, ['approve', 'reject'])) {
    if ($redirect_to_profile) {
        redirect_profile($member_id, false, 'Invalid action');
    }
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
    exit();
}

// Get member details
$memberQuery = "SELECT * FROM registrations WHERE id = ?";
$memberStmt = $conn->prepare($memberQuery);
$memberStmt->bind_param("i", $member_id);
$memberStmt->execute();
$memberResult = $memberStmt->get_result();

if ($memberResult->num_rows === 0) {
    if ($redirect_to_profile) {
        redirect_profile($member_id, false, 'Member not found');
    }
    echo json_encode(['success' => false, 'message' => 'Member not found']);
    exit();
}

$member = $memberResult->fetch_assoc();
$admin_id = $_SESSION['user_id'];
$now = date('Y-m-d H:i:s');

if ($action === 'approve') {
    // Update approval status
    $updateQuery = "UPDATE registrations SET 
                    approval_status = 'approved',
                    approved_by = ?,
                    approved_at = ?,
                    status = 'active'
                    WHERE id = ?";
    
    $updateStmt = $conn->prepare($updateQuery);
    $updateStmt->bind_param("isi", $admin_id, $now, $member_id);
    
    if ($updateStmt->execute()) {
        // Create member_access record if it doesn't exist
        $checkAccessQuery = "SELECT id FROM member_access WHERE member_id = ?";
        $checkStmt = $conn->prepare($checkAccessQuery);
        $checkStmt->bind_param("i", $member_id);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        
        if ($checkResult->num_rows == 0) {
            // Generate temporary password (member will set their own on first login)
            $tempPassword = bin2hex(random_bytes(8)); // Temporary password
            $hashedPassword = password_hash($tempPassword, PASSWORD_DEFAULT);
            
            $insertAccessQuery = "INSERT INTO member_access (member_id, email, password, membership_id, status) 
                                  VALUES (?, ?, ?, ?, 'active')";
            $insertStmt = $conn->prepare($insertAccessQuery);
            $insertStmt->bind_param("isss", $member_id, $member['email'], $hashedPassword, $member['membership_id']);
            
            if ($insertStmt->execute()) {
                // TODO: Send approval email to member with temporary password and set password link
                // For now, we'll redirect them to set password page on first login
            }
            
            $insertStmt->close();
        }
        
        $checkStmt->close();
        
        // TODO: Send approval email to member
        // Include membership ID and login instructions

        $updateStmt->close();
        if ($redirect_to_profile) {
            redirect_profile($member_id, true, 'Member approved successfully. They can now access the member panel.');
        }
        echo json_encode([
            'success' => true,
            'message' => 'Member approved successfully. They can now access the member panel.'
        ]);
        exit;
    }
    $updateStmt->close();
    if ($redirect_to_profile) {
        redirect_profile($member_id, false, 'Failed to approve member: ' . $conn->error);
    }
    echo json_encode([
        'success' => false,
        'message' => 'Failed to approve member: ' . $conn->error
    ]);
    exit;
} elseif ($action === 'reject') {
    // Update approval status
    $updateQuery = "UPDATE registrations SET 
                    approval_status = 'rejected',
                    approved_by = ?,
                    approved_at = ?,
                    status = 'pending'
                    WHERE id = ?";
    
    $updateStmt = $conn->prepare($updateQuery);
    $updateStmt->bind_param("isi", $admin_id, $now, $member_id);
    
    if ($updateStmt->execute()) {
        $updateStmt->close();
        if ($redirect_to_profile) {
            redirect_profile($member_id, true, 'Member rejected successfully.');
        }
        echo json_encode([
            'success' => true,
            'message' => 'Member rejected successfully.'
        ]);
        exit;
    }
    $updateStmt->close();
    if ($redirect_to_profile) {
        redirect_profile($member_id, false, 'Failed to reject member: ' . $conn->error);
    }
    echo json_encode([
        'success' => false,
        'message' => 'Failed to reject member: ' . $conn->error
    ]);
    exit;
}

$memberStmt->close();
$conn->close();
?>

