<?php
session_start();
include 'conn.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth-login.php");
    exit();
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($action === 'add' && $_SERVER["REQUEST_METHOD"] == "POST") {
    $research_id = intval($_POST['research_id'] ?? 0);
    $member_id = intval($_POST['member_id'] ?? 0);
    $role = $_POST['role'] ?? 'contributor';
    $contribution_percentage = !empty($_POST['contribution_percentage']) ? floatval($_POST['contribution_percentage']) : null;
    
    if ($research_id <= 0 || $member_id <= 0) {
        header("Location: ../research_collaborators.php?id=" . $research_id . "&error=Invalid research or member ID");
        exit();
    }
    
    // Check if already a collaborator
    $checkQuery = "SELECT id FROM research_collaborators WHERE research_id = ? AND member_id = ?";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->bind_param("ii", $research_id, $member_id);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows > 0) {
        $checkStmt->close();
        header("Location: ../research_collaborators.php?id=" . $research_id . "&error=Member is already a collaborator");
        exit();
    }
    $checkStmt->close();
    
    // Insert collaborator
    $query = "INSERT INTO research_collaborators (research_id, member_id, role, contribution_percentage) 
              VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iisd", $research_id, $member_id, $role, $contribution_percentage);
    
    if ($stmt->execute()) {
        $stmt->close();
        header("Location: ../research_collaborators.php?id=" . $research_id . "&success=Collaborator added successfully");
        exit();
    } else {
        $stmt->close();
        header("Location: ../research_collaborators.php?id=" . $research_id . "&error=Failed to add collaborator: " . $conn->error);
        exit();
    }
    
} elseif ($action === 'remove' && isset($_GET['collab_id'])) {
    $research_id = intval($_GET['research_id'] ?? 0);
    $collab_id = intval($_GET['collab_id'] ?? 0);
    
    if ($collab_id <= 0) {
        header("Location: ../research_collaborators.php?id=" . $research_id . "&error=Invalid collaborator ID");
        exit();
    }
    
    $query = "DELETE FROM research_collaborators WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $collab_id);
    
    if ($stmt->execute()) {
        $stmt->close();
        header("Location: ../research_collaborators.php?id=" . $research_id . "&success=Collaborator removed successfully");
        exit();
    } else {
        $stmt->close();
        header("Location: ../research_collaborators.php?id=" . $research_id . "&error=Failed to remove collaborator: " . $conn->error);
        exit();
    }
} else {
    header("Location: ../research_list.php");
    exit();
}

$conn->close();
?>

