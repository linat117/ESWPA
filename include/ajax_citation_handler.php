<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['member_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

include 'config.php';

$member_id = $_SESSION['member_id'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'save') {
    $resource_id = !empty($_POST['resource_id']) ? intval($_POST['resource_id']) : null;
    $research_id = !empty($_POST['research_id']) ? intval($_POST['research_id']) : null;
    $citation_format = $_POST['citation_format'] ?? 'apa';
    $citation_text = trim($_POST['citation_text'] ?? '');
    
    // Decode HTML entities and URL encoding if present
    $citation_text = html_entity_decode($citation_text, ENT_QUOTES, 'UTF-8');
    $citation_text = urldecode($citation_text);
    
    if (!empty($citation_text)) {
        $query = "INSERT INTO member_citations (member_id, resource_id, research_id, citation_format, citation_text) 
                  VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("iiiss", $member_id, $resource_id, $research_id, $citation_format, $citation_text);
        
        if ($stmt->execute()) {
            $citation_id = $stmt->insert_id;
            $stmt->close();
            
            // Get the saved citation with related data
            $getQuery = "SELECT mc.*, r.title as resource_title, rp.title as research_title
                        FROM member_citations mc
                        LEFT JOIN resources r ON mc.resource_id = r.id
                        LEFT JOIN research_projects rp ON mc.research_id = rp.id
                        WHERE mc.id = ? AND mc.member_id = ?";
            $getStmt = $conn->prepare($getQuery);
            $getStmt->bind_param("ii", $citation_id, $member_id);
            $getStmt->execute();
            $result = $getStmt->get_result();
            $citation = $result->fetch_assoc();
            $getStmt->close();
            
            echo json_encode([
                'success' => true,
                'message' => 'Citation saved successfully!',
                'citation' => $citation
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to save citation: ' . $conn->error]);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Citation text is required']);
    }
} elseif ($action === 'delete') {
    $citation_id = intval($_POST['citation_id'] ?? $_GET['citation_id'] ?? 0);
    
    if ($citation_id > 0) {
        $query = "DELETE FROM member_citations WHERE id = ? AND member_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ii", $citation_id, $member_id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Citation deleted successfully!']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to delete citation']);
        }
        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid citation ID']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid action']);
}

mysqli_close($conn);
?>

