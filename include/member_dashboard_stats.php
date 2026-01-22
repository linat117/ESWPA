<?php
/**
 * Member Dashboard Statistics Helper
 * Provides functions to get member statistics for dashboard
 * 
 * Last Updated: December 24, 2025
 */

/**
 * Get member statistics
 */
function getMemberDashboardStats($member_id) {
    global $conn;
    
    $stats = [
        'research_count' => 0,
        'citations_count' => 0,
        'notes_count' => 0,
        'bibliography_count' => 0,
        'resources_downloaded' => 0,
        'reading_progress' => 0,
        'unread_notifications' => 0,
        'recent_activities' => []
    ];
    
    // Research projects count
    $query = "SELECT COUNT(*) as count FROM research_projects WHERE created_by = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $member_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stats['research_count'] = $row['count'] ?? 0;
    $stmt->close();
    
    // Citations count
    $query = "SELECT COUNT(*) as count FROM member_citations WHERE member_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $member_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stats['citations_count'] = $row['count'] ?? 0;
    $stmt->close();
    
    // Notes count
    $query = "SELECT COUNT(*) as count FROM research_notes WHERE member_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $member_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stats['notes_count'] = $row['count'] ?? 0;
    $stmt->close();
    
    // Bibliography collections count
    $query = "SELECT COUNT(*) as count FROM bibliography_collections WHERE member_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $member_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stats['bibliography_count'] = $row['count'] ?? 0;
    $stmt->close();
    
    // Reading progress (completed items)
    $query = "SELECT COUNT(*) as count FROM reading_progress WHERE member_id = ? AND completed = 1";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $member_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stats['reading_progress'] = $row['count'] ?? 0;
    $stmt->close();
    
    // Unread notifications count
    $checkTable = "SHOW TABLES LIKE 'notifications'";
    $tableResult = $conn->query($checkTable);
    if ($tableResult && $tableResult->num_rows > 0) {
        $query = "SELECT COUNT(*) as count FROM notifications WHERE member_id = ? AND is_read = 0";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $member_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stats['unread_notifications'] = $row['count'] ?? 0;
        $stmt->close();
    }
    
    // Recent activities (last 10)
    $checkTable = "SHOW TABLES LIKE 'member_activities'";
    $tableResult = $conn->query($checkTable);
    if ($tableResult && $tableResult->num_rows > 0) {
        $query = "SELECT * FROM member_activities WHERE member_id = ? ORDER BY created_at DESC LIMIT 10";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $member_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $stats['recent_activities'] = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }
    
    return $stats;
}

/**
 * Get recent news (for recommendations)
 */
function getRecentNews($limit = 5) {
    global $conn;
    
    $query = "SELECT * FROM news_media WHERE status = 'published' ORDER BY created_at DESC LIMIT ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    $news = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    return $news;
}

/**
 * Get upcoming events
 */
function getUpcomingEvents($limit = 5) {
    global $conn;
    
    $query = "SELECT * FROM upcoming WHERE event_date >= CURDATE() ORDER BY event_date ASC LIMIT ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    $events = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    return $events;
}

/**
 * Get recommended resources
 */
function getRecommendedResources($member_id, $limit = 5) {
    global $conn;
    
    // Get resources based on member's qualification or recent uploads
    $query = "SELECT * FROM resources WHERE status = 'active' ORDER BY created_at DESC LIMIT ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    $resources = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    return $resources;
}

?>

