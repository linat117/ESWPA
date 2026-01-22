<?php
/**
 * AI Processing API Endpoint
 * Handles AI processing requests for resources and research
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

// Admin only
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized - Admin access required']);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'POST':
        processContent($conn);
        break;
    
    case 'GET':
        if (isset($_GET['status']) && isset($_GET['job_id'])) {
            getProcessingStatus($conn, intval($_GET['job_id']));
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Job ID required']);
        }
        break;
    
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        break;
}

/**
 * Process content (resource or research)
 */
function processContent($conn) {
    $contentType = $_POST['content_type'] ?? '';
    $contentId = intval($_POST['content_id'] ?? 0);
    $processType = $_POST['process_type'] ?? 'analyze';
    $pluginId = isset($_POST['plugin_id']) ? intval($_POST['plugin_id']) : null;
    $priority = intval($_POST['priority'] ?? 5);
    
    if (empty($contentType) || !in_array($contentType, ['resource', 'research'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid content type']);
        return;
    }
    
    if ($contentId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid content ID']);
        return;
    }
    
    $validProcessTypes = ['extract_text', 'summarize', 'analyze', 'extract_keywords', 'find_similar', 'generate_recommendations'];
    if (!in_array($processType, $validProcessTypes)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid process type']);
        return;
    }
    
    // Add to processing queue
    $query = "INSERT INTO ai_processing_queue 
              (content_type, content_id, process_type, plugin_id, priority, status) 
              VALUES (?, ?, ?, ?, ?, 'pending')";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sisi", $contentType, $contentId, $processType, $pluginId, $priority);
    
    if ($stmt->execute()) {
        $queueId = $stmt->insert_id;
        echo json_encode([
            'success' => true,
            'message' => 'Content added to processing queue',
            'data' => [
                'queue_id' => $queueId,
                'content_type' => $contentType,
                'content_id' => $contentId,
                'process_type' => $processType,
                'status' => 'pending'
            ]
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to add to queue: ' . $stmt->error]);
    }
    
    $stmt->close();
}

/**
 * Get processing status
 */
function getProcessingStatus($conn, $jobId) {
    $query = "SELECT id, content_type, content_id, process_type, status, 
                     attempts, max_attempts, error_message, result_json,
                     started_at, completed_at, created_at
              FROM ai_processing_queue 
              WHERE id = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $jobId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Job not found']);
        $stmt->close();
        return;
    }
    
    $job = $result->fetch_assoc();
    
    // Decode result JSON if exists
    if (!empty($job['result_json'])) {
        $job['result'] = json_decode($job['result_json'], true);
    }
    unset($job['result_json']);
    
    echo json_encode([
        'success' => true,
        'data' => $job
    ]);
    
    $stmt->close();
}

$conn->close();
?>

