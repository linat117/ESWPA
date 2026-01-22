<?php
/**
 * Research Projects API Endpoint
 * Provides RESTful API for accessing research projects with AI metadata
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

session_start();
require_once __DIR__ . '/../../include/config.php';

// Check authentication
$member_id = $_SESSION['member_id'] ?? null;
$isAdmin = isset($_SESSION['user_id']);

$method = $_SERVER['REQUEST_METHOD'];
$requestUri = $_SERVER['REQUEST_URI'];
$pathParts = explode('/', trim(parse_url($requestUri, PHP_URL_PATH), '/'));

// Get research ID from URL if present
$researchId = null;
if (isset($pathParts[3]) && is_numeric($pathParts[3])) {
    $researchId = intval($pathParts[3]);
}

// Route handling
switch ($method) {
    case 'GET':
        if ($researchId && isset($pathParts[4]) && $pathParts[4] === 'similar') {
            // Get similar research
            getSimilarResearch($conn, $researchId);
        } elseif ($researchId) {
            // Get specific research
            getResearch($conn, $researchId, $member_id);
        } else {
            // List research projects
            listResearch($conn, $member_id);
        }
        break;
    
    case 'POST':
        // Create research with AI suggestions (admin only)
        if ($isAdmin) {
            createResearch($conn);
        } else {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Admin access required']);
        }
        break;
    
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        break;
}

/**
 * List research projects
 */
function listResearch($conn, $member_id) {
    $page = intval($_GET['page'] ?? 1);
    $limit = intval($_GET['limit'] ?? 20);
    $offset = ($page - 1) * $limit;
    
    $where = ["status = 'published'"];
    $params = [];
    $types = '';
    
    // Apply filters
    if (isset($_GET['category']) && !empty($_GET['category'])) {
        $where[] = "category = ?";
        $params[] = $_GET['category'];
        $types .= 's';
    }
    
    if (isset($_GET['type']) && !empty($_GET['type'])) {
        $where[] = "research_type = ?";
        $params[] = $_GET['type'];
        $types .= 's';
    }
    
    $whereClause = implode(' AND ', $where);
    $query = "SELECT rp.id, rp.title, rp.abstract, rp.category, rp.research_type, 
                     rp.status, rp.publication_date, rp.keywords,
                     rp.metadata_json, rp.ai_processed, rp.ai_processed_at, rp.ai_summary,
                     r.fullname as creator_name,
                     (SELECT COUNT(*) FROM research_collaborators WHERE research_id = rp.id) as collaborator_count,
                     (SELECT COUNT(*) FROM research_files WHERE research_id = rp.id) as file_count
              FROM research_projects rp
              LEFT JOIN registrations r ON rp.created_by = r.id
              WHERE $whereClause
              ORDER BY rp.publication_date DESC, rp.created_at DESC
              LIMIT ? OFFSET ?";
    
    $params[] = $limit;
    $params[] = $offset;
    $types .= 'ii';
    
    $stmt = $conn->prepare($query);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    $research = [];
    while ($row = $result->fetch_assoc()) {
        // Decode metadata JSON
        if (!empty($row['metadata_json'])) {
            $row['metadata'] = json_decode($row['metadata_json'], true);
        }
        unset($row['metadata_json']);
        $research[] = $row;
    }
    
    // Get total count
    $countQuery = "SELECT COUNT(*) as total FROM research_projects WHERE $whereClause";
    $countStmt = $conn->prepare($countQuery);
    if (count($params) > 2) {
        $countParams = array_slice($params, 0, -2);
        $countTypes = substr($types, 0, -2);
        if (!empty($countParams)) {
            $countStmt->bind_param($countTypes, ...$countParams);
        }
    }
    $countStmt->execute();
    $total = $countStmt->get_result()->fetch_assoc()['total'];
    
    echo json_encode([
        'success' => true,
        'data' => [
            'research' => $research,
            'total' => intval($total),
            'page' => $page,
            'limit' => $limit,
            'pages' => ceil($total / $limit)
        ],
        'metadata' => [
            'ai_enabled' => true,
            'timestamp' => date('c')
        ]
    ]);
    
    $stmt->close();
    $countStmt->close();
}

/**
 * Get specific research
 */
function getResearch($conn, $researchId, $member_id) {
    $query = "SELECT rp.*, r.fullname as creator_name, r.email as creator_email,
                     (SELECT COUNT(*) FROM research_collaborators WHERE research_id = rp.id) as collaborator_count,
                     (SELECT COUNT(*) FROM research_files WHERE research_id = rp.id) as file_count
              FROM research_projects rp
              LEFT JOIN registrations r ON rp.created_by = r.id
              WHERE rp.id = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $researchId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Research project not found']);
        $stmt->close();
        return;
    }
    
    $research = $result->fetch_assoc();
    
    // Decode metadata JSON
    if (!empty($research['metadata_json'])) {
        $research['metadata'] = json_decode($research['metadata_json'], true);
    }
    unset($research['metadata_json']);
    
    echo json_encode([
        'success' => true,
        'data' => $research,
        'metadata' => [
            'ai_enabled' => true,
            'timestamp' => date('c')
        ]
    ]);
    
    $stmt->close();
}

/**
 * Get similar research projects
 */
function getSimilarResearch($conn, $researchId) {
    $limit = intval($_GET['limit'] ?? 10);
    
    $query = "SELECT si.similar_content_id, si.similarity_score,
                     rp.title, rp.abstract, rp.category, rp.research_type,
                     r.fullname as creator_name
              FROM ai_similarity_index si
              JOIN research_projects rp ON si.similar_content_id = rp.id
              LEFT JOIN registrations r ON rp.created_by = r.id
              WHERE si.content_type = 'research'
              AND si.content_id = ?
              AND si.similar_content_type = 'research'
              AND rp.status = 'published'
              ORDER BY si.similarity_score DESC
              LIMIT ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $researchId, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $similar = [];
    while ($row = $result->fetch_assoc()) {
        $similar[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'data' => [
            'research_id' => $researchId,
            'similar' => $similar
        ]
    ]);
    
    $stmt->close();
}

/**
 * Create research with AI suggestions
 */
function createResearch($conn) {
    // This would integrate with research_handler.php
    // For now, return a placeholder
    echo json_encode([
        'success' => false,
        'error' => 'Not yet implemented',
        'message' => 'Use admin panel to create research projects'
    ]);
}

$conn->close();
?>

