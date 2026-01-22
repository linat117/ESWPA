<?php
/**
 * Reading Progress Tracker
 * Tracks reading progress for resources and research
 */

require_once __DIR__ . '/config.php';

/**
 * Update reading progress
 */
function updateReadingProgress($member_id, $resource_id = null, $research_id = null, $page_number = 1, $total_pages = null, $time_spent_minutes = 0) {
    global $conn;
    
    // Check if progress exists
    $checkQuery = "SELECT id FROM reading_progress WHERE member_id = ? AND resource_id " . 
                  ($resource_id ? "= ?" : "IS NULL") . " AND research_id " . 
                  ($research_id ? "= ?" : "IS NULL");
    $checkStmt = $conn->prepare($checkQuery);
    
    if ($resource_id && $research_id) {
        $checkStmt->bind_param("iii", $member_id, $resource_id, $research_id);
    } elseif ($resource_id) {
        $checkStmt->bind_param("ii", $member_id, $resource_id);
    } elseif ($research_id) {
        $checkStmt->bind_param("ii", $member_id, $research_id);
    } else {
        return ['success' => false, 'error' => 'Either resource_id or research_id required'];
    }
    
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    $checkStmt->close();
    
    if ($checkResult->num_rows > 0) {
        // Update existing
        $existing = $checkResult->fetch_assoc();
        $progress_id = $existing['id'];
        
        $updateQuery = "UPDATE reading_progress SET page_number = ?, total_pages = ?, 
                       time_spent_minutes = time_spent_minutes + ?, last_read_at = NOW() 
                       WHERE id = ?";
        $updateStmt = $conn->prepare($updateQuery);
        $updateStmt->bind_param("iiii", $page_number, $total_pages, $time_spent_minutes, $progress_id);
        
        if ($updateStmt->execute()) {
            $updateStmt->close();
            return ['success' => true, 'progress_id' => $progress_id];
        } else {
            $error = $updateStmt->error;
            $updateStmt->close();
            return ['success' => false, 'error' => $error];
        }
    } else {
        // Create new
        $insertQuery = "INSERT INTO reading_progress 
                       (member_id, resource_id, research_id, page_number, total_pages, time_spent_minutes) 
                       VALUES (?, ?, ?, ?, ?, ?)";
        $insertStmt = $conn->prepare($insertQuery);
        $insertStmt->bind_param("iiiiii", $member_id, $resource_id, $research_id, $page_number, $total_pages, $time_spent_minutes);
        
        if ($insertStmt->execute()) {
            $progress_id = $insertStmt->insert_id;
            $insertStmt->close();
            return ['success' => true, 'progress_id' => $progress_id];
        } else {
            $error = $insertStmt->error;
            $insertStmt->close();
            return ['success' => false, 'error' => $error];
        }
    }
}

/**
 * Get reading progress for a member
 */
function getMemberReadingProgress($member_id, $limit = 20) {
    global $conn;
    
    $query = "SELECT rp.*, 
              res.title as resource_title, res.pdf_file as resource_file,
              rproj.title as research_title
              FROM reading_progress rp
              LEFT JOIN resources res ON rp.resource_id = res.id
              LEFT JOIN research_projects rproj ON rp.research_id = rproj.id
              WHERE rp.member_id = ?
              ORDER BY rp.last_read_at DESC
              LIMIT ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $member_id, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $progress = [];
    while ($row = $result->fetch_assoc()) {
        $progress[] = $row;
    }
    
    $stmt->close();
    return $progress;
}

/**
 * Mark as completed
 */
function markReadingCompleted($member_id, $resource_id = null, $research_id = null) {
    global $conn;
    
    $query = "UPDATE reading_progress SET completed = 1, completed_at = NOW() 
              WHERE member_id = ? AND resource_id " . 
              ($resource_id ? "= ?" : "IS NULL") . " AND research_id " . 
              ($research_id ? "= ?" : "IS NULL");
    $stmt = $conn->prepare($query);
    
    if ($resource_id && $research_id) {
        $stmt->bind_param("iii", $member_id, $resource_id, $research_id);
    } elseif ($resource_id) {
        $stmt->bind_param("ii", $member_id, $resource_id);
    } elseif ($research_id) {
        $stmt->bind_param("ii", $member_id, $research_id);
    } else {
        return ['success' => false, 'error' => 'Either resource_id or research_id required'];
    }
    
    if ($stmt->execute()) {
        $stmt->close();
        return ['success' => true];
    } else {
        $error = $stmt->error;
        $stmt->close();
        return ['success' => false, 'error' => $error];
    }
}

/**
 * Get reading statistics
 */
function getReadingStatistics($member_id) {
    global $conn;
    
    $query = "SELECT 
              COUNT(*) as total_items,
              SUM(time_spent_minutes) as total_minutes,
              SUM(CASE WHEN completed = 1 THEN 1 ELSE 0 END) as completed_count
              FROM reading_progress
              WHERE member_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $member_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $stats = $result->fetch_assoc();
    $stmt->close();
    
    return $stats;
}

