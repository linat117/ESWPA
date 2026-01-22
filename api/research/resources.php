<?php
/**
 * Resources API Endpoint
 * Provides RESTful API for accessing resources with AI metadata
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

// Check authentication (optional - can be public or require auth)
$requireAuth = true; // Set to false for public API
$member_id = $_SESSION['member_id'] ?? null;
$isAdmin = isset($_SESSION['user_id']);

if ($requireAuth && !$member_id && !$isAdmin) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'Unauthorized access',
        'message' => 'Authentication required'
    ]);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];
$requestUri = $_SERVER['REQUEST_URI'];
$pathParts = explode('/', trim(parse_url($requestUri, PHP_URL_PATH), '/'));

// Get resource ID from URL if present
$resourceId = null;
if (isset($pathParts[3]) && is_numeric($pathParts[3])) {
    $resourceId = intval($pathParts[3]);
}

// Route handling
switch ($method) {
    case 'GET':
        if ($resourceId) {
            // Get specific resource
            getResource($conn, $resourceId, $member_id);
        } elseif (isset($_GET['search'])) {
            // Search resources
            searchResources($conn, $_GET['search'], $member_id);
        } elseif (isset($_GET['recommendations'])) {
            // Get recommendations
            getRecommendations($conn, $member_id ?? intval($_GET['member_id'] ?? 0));
        } else {
            // List all resources
            listResources($conn, $member_id);
        }
        break;
    
    case 'POST':
        // Process resource for AI (admin only)
        if ($isAdmin && isset($_POST['process'])) {
            processResource($conn, intval($_POST['resource_id'] ?? 0));
        } else {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        }
        break;
    
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        break;
}

/**
 * List all resources
 */
function listResources($conn, $member_id) {
    $page = intval($_GET['page'] ?? 1);
    $limit = intval($_GET['limit'] ?? 20);
    $offset = ($page - 1) * $limit;
    
    // Build query with access control
    $where = ["status = 'active' OR status IS NULL"];
    $params = [];
    $types = '';
    
    // Apply filters
    if (isset($_GET['section']) && !empty($_GET['section'])) {
        $where[] = "section = ?";
        $params[] = $_GET['section'];
        $types .= 's';
    }
    
    if (isset($_GET['access_level']) && !empty($_GET['access_level'])) {
        $where[] = "access_level = ?";
        $params[] = $_GET['access_level'];
        $types .= 's';
    }
    
    $whereClause = implode(' AND ', $where);
    $query = "SELECT id, section, title, author, publication_date, description, 
                     tags, featured, download_count, access_level, 
                     metadata_json, ai_processed, ai_processed_at,
                     created_at, updated_at
              FROM resources 
              WHERE $whereClause
              ORDER BY created_at DESC
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
    
    $resources = [];
    while ($row = $result->fetch_assoc()) {
        // Decode metadata JSON
        if (!empty($row['metadata_json'])) {
            $row['metadata'] = json_decode($row['metadata_json'], true);
        }
        unset($row['metadata_json']);
        $resources[] = $row;
    }
    
    // Get total count
    $countQuery = "SELECT COUNT(*) as total FROM resources WHERE $whereClause";
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
            'resources' => $resources,
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
 * Get specific resource
 */
function getResource($conn, $resourceId, $member_id) {
    $query = "SELECT id, section, title, author, publication_date, description, 
                     tags, featured, download_count, access_level, pdf_file,
                     metadata_json, ai_processed, ai_processed_at, extracted_text,
                     created_at, updated_at
              FROM resources 
              WHERE id = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $resourceId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Resource not found']);
        $stmt->close();
        return;
    }
    
    $resource = $result->fetch_assoc();
    
    // Decode metadata JSON
    if (!empty($resource['metadata_json'])) {
        $resource['metadata'] = json_decode($resource['metadata_json'], true);
    }
    unset($resource['metadata_json']);
    
    echo json_encode([
        'success' => true,
        'data' => $resource,
        'metadata' => [
            'ai_enabled' => true,
            'timestamp' => date('c')
        ]
    ]);
    
    $stmt->close();
}

/**
 * Search resources
 */
function searchResources($conn, $searchQuery, $member_id) {
    $page = intval($_GET['page'] ?? 1);
    $limit = intval($_GET['limit'] ?? 20);
    $offset = ($page - 1) * $limit;
    
    $search = "%$searchQuery%";
    $query = "SELECT id, section, title, author, publication_date, description, 
                     tags, access_level, metadata_json, ai_processed
              FROM resources 
              WHERE (status = 'active' OR status IS NULL)
              AND (title LIKE ? OR description LIKE ? OR tags LIKE ? OR extracted_text LIKE ?)
              ORDER BY 
                CASE 
                  WHEN title LIKE ? THEN 1
                  WHEN description LIKE ? THEN 2
                  ELSE 3
                END,
                created_at DESC
              LIMIT ? OFFSET ?";
    
    $exactSearch = "%$searchQuery%";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssssssii", $search, $search, $search, $search, $exactSearch, $exactSearch, $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $resources = [];
    while ($row = $result->fetch_assoc()) {
        if (!empty($row['metadata_json'])) {
            $row['metadata'] = json_decode($row['metadata_json'], true);
        }
        unset($row['metadata_json']);
        $resources[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'data' => [
            'resources' => $resources,
            'query' => $searchQuery,
            'page' => $page,
            'limit' => $limit
        ]
    ]);
    
    $stmt->close();
}

/**
 * Get recommendations for member
 */
function getRecommendations($conn, $member_id) {
    if ($member_id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Member ID required']);
        return;
    }
    
    // Get recommendations from similarity index
    $query = "SELECT si.similar_content_type, si.similar_content_id, si.similarity_score,
                     r.title as resource_title, r.section, r.author,
                     rp.title as research_title, rp.category
              FROM ai_similarity_index si
              LEFT JOIN resources r ON (si.similar_content_type = 'resource' AND si.similar_content_id = r.id)
              LEFT JOIN research_projects rp ON (si.similar_content_type = 'research' AND si.similar_content_id = rp.id)
              WHERE si.content_type = 'resource'
              AND si.content_id IN (
                  SELECT resource_id FROM access_logs 
                  WHERE member_id = ? AND access_granted = 1
                  ORDER BY accessed_at DESC LIMIT 10
              )
              ORDER BY si.similarity_score DESC
              LIMIT 20";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $member_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $recommendations = [];
    while ($row = $result->fetch_assoc()) {
        $recommendations[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'data' => [
            'recommendations' => $recommendations,
            'member_id' => $member_id
        ]
    ]);
    
    $stmt->close();
}

/**
 * Process resource for AI (admin only)
 */
function processResource($conn, $resourceId) {
    if ($resourceId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid resource ID']);
        return;
    }
    
    // Add to processing queue
    $query = "INSERT INTO ai_processing_queue 
              (content_type, content_id, process_type, priority, status) 
              VALUES ('resource', ?, 'analyze', 5, 'pending')";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $resourceId);
    
    if ($stmt->execute()) {
        $queueId = $stmt->insert_id;
        echo json_encode([
            'success' => true,
            'message' => 'Resource added to processing queue',
            'queue_id' => $queueId
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to add to queue']);
    }
    
    $stmt->close();
}

$conn->close();
?>

