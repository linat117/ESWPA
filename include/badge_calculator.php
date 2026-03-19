<?php
/**
 * Badge Calculator and Auto-Assignment System
 * Automatically assigns badges to members based on their activities and achievements
 */

require_once __DIR__ . '/../admin/include/conn.php';

/**
 * Calculate and assign membership duration badges
 * Bronze (1-2 years), Silver (3-5 years), Gold (6-10 years), Platinum (10+ years)
 */
function assignMembershipBadges($member_id) {
    global $conn;
    
    // Get member registration date
    $query = "SELECT created_at, payment_duration FROM registrations WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $member_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $stmt->close();
        return;
    }
    
    $member = $result->fetch_assoc();
    $stmt->close();
    
    $registrationDate = new DateTime($member['created_at']);
    $now = new DateTime();
    $years = $now->diff($registrationDate)->y;
    
    // Determine badge based on years
    $badgeName = null;
    if ($years >= 10) {
        $badgeName = 'Platinum Member';
    } elseif ($years >= 6) {
        $badgeName = 'Gold Member';
    } elseif ($years >= 3) {
        $badgeName = 'Silver Member';
    } elseif ($years >= 1) {
        $badgeName = 'Bronze Member';
    }
    
    if ($badgeName) {
        assignBadge($member_id, $badgeName, 'Membership duration: ' . $years . ' year(s)');
    }
}

/**
 * Calculate and assign research badges
 */
function assignResearchBadges($member_id) {
    global $conn;
    
    // Count research projects where member is creator
    $leaderQuery = "SELECT COUNT(*) as count FROM research_projects WHERE created_by = ?";
    $stmt = $conn->prepare($leaderQuery);
    $stmt->bind_param("i", $member_id);
    $stmt->execute();
    $leaderCount = $stmt->get_result()->fetch_assoc()['count'];
    $stmt->close();
    
    // Count research projects where member is collaborator
    $collabQuery = "SELECT COUNT(DISTINCT research_id) as count FROM research_collaborators WHERE member_id = ?";
    $stmt = $conn->prepare($collabQuery);
    $stmt->bind_param("i", $member_id);
    $stmt->execute();
    $collabCount = $stmt->get_result()->fetch_assoc()['count'];
    $stmt->close();
    
    $totalResearch = $leaderCount + $collabCount;
    
    // Assign badges based on research participation
    if ($leaderCount >= 1) {
        assignBadge($member_id, 'Research Leader', 'Led ' . $leaderCount . ' research project(s)');
    }
    
    if ($totalResearch >= 10) {
        assignBadge($member_id, 'Research Collaborator', 'Contributed to ' . $totalResearch . ' research projects');
    } elseif ($totalResearch >= 4) {
        assignBadge($member_id, 'Research Collaborator', 'Contributed to ' . $totalResearch . ' research projects');
    } elseif ($totalResearch >= 1) {
        assignBadge($member_id, 'Research Participant', 'Contributed to ' . $totalResearch . ' research project(s)');
    }
}

/**
 * Calculate and assign activity badges
 */
function assignActivityBadges($member_id) {
    global $conn;
    
    // Count total activities
    $activityQuery = "SELECT COUNT(*) as count FROM member_activities WHERE member_id = ?";
    $stmt = $conn->prepare($activityQuery);
    $stmt->bind_param("i", $member_id);
    $stmt->execute();
    $activityCount = $stmt->get_result()->fetch_assoc()['count'];
    $stmt->close();
    
    // Count activities in last 30 days
    $recentQuery = "SELECT COUNT(*) as count FROM member_activities 
                    WHERE member_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
    $stmt = $conn->prepare($recentQuery);
    $stmt->bind_param("i", $member_id);
    $stmt->execute();
    $recentCount = $stmt->get_result()->fetch_assoc()['count'];
    $stmt->close();
    
    // Assign badges based on activity
    if ($activityCount >= 100) {
        assignBadge($member_id, 'Community Champion', 'High engagement with ' . $activityCount . ' activities');
    } elseif ($activityCount >= 50) {
        assignBadge($member_id, 'Active Member', 'Engaged member with ' . $activityCount . ' activities');
    }
    
    if ($recentCount >= 20) {
        assignBadge($member_id, 'Highly Active', 'Very active in the last 30 days');
    }
}

/**
 * Calculate and assign resource contribution badges
 */
function assignResourceBadges($member_id) {
    global $conn;
    
    // Count resources downloaded (using member_activities)
    $downloadQuery = "SELECT COUNT(DISTINCT related_id) as count FROM member_activities 
                      WHERE member_id = ? AND activity_type = 'resource_download'";
    $stmt = $conn->prepare($downloadQuery);
    $stmt->bind_param("i", $member_id);
    $stmt->execute();
    $downloadCount = $stmt->get_result()->fetch_assoc()['count'];
    $stmt->close();
    
    // Note: Resource upload tracking would need to be added if members can upload resources
    // For now, we'll focus on download/usage badges
    
    if ($downloadCount >= 50) {
        assignBadge($member_id, 'Resource Enthusiast', 'Downloaded ' . $downloadCount . ' resources');
    } elseif ($downloadCount >= 20) {
        assignBadge($member_id, 'Resource User', 'Downloaded ' . $downloadCount . ' resources');
    }
}

/**
 * Calculate and assign event participation badges
 */
function assignEventBadges($member_id) {
    global $conn;
    
    // Count event-related activities
    $eventQuery = "SELECT COUNT(DISTINCT related_id) as count FROM member_activities 
                   WHERE member_id = ? AND (activity_type LIKE '%event%' OR related_type = 'event')";
    $stmt = $conn->prepare($eventQuery);
    $stmt->bind_param("i", $member_id);
    $stmt->execute();
    $eventCount = $stmt->get_result()->fetch_assoc()['count'];
    $stmt->close();
    
    if ($eventCount >= 10) {
        assignBadge($member_id, 'Event Enthusiast', 'Participated in ' . $eventCount . ' events');
    } elseif ($eventCount >= 5) {
        assignBadge($member_id, 'Active Member', 'Attended ' . $eventCount . ' events');
    }
}

/**
 * Remove duplicate badge entries for all members
 */
function removeDuplicateBadges() {
    global $conn;
    
    // Find and remove duplicates, keeping only the most recent entry for each member-badge combination
    $cleanupQuery = "DELETE mb1 FROM member_badges mb1
                     INNER JOIN member_badges mb2 
                     WHERE mb1.member_id = mb2.member_id 
                     AND mb1.badge_name = mb2.badge_name 
                     AND mb1.id < mb2.id";
    
    $result = $conn->query($cleanupQuery);
    return $result;
}

/**
 * Assign a badge to a member (if not already assigned)
 */
function assignBadge($member_id, $badge_name, $reason = '') {
    global $conn;
    
    // Check if badge already exists in badge_permissions
    $checkBadgeQuery = "SELECT id FROM badge_permissions WHERE badge_name = ?";
    $stmt = $conn->prepare($checkBadgeQuery);
    $stmt->bind_param("s", $badge_name);
    $stmt->execute();
    $badgeExists = $stmt->get_result()->num_rows > 0;
    $stmt->close();
    
    if (!$badgeExists) {
        // Create badge permission if it doesn't exist
        $createBadgeQuery = "INSERT INTO badge_permissions (badge_name, description) VALUES (?, ?)";
        $stmt = $conn->prepare($createBadgeQuery);
        $description = "Automatically assigned badge: " . $badge_name;
        $stmt->bind_param("ss", $badge_name, $description);
        $stmt->execute();
        $stmt->close();
    }
    
    // Check if member already has this badge (including inactive ones)
    $checkMemberQuery = "SELECT id, is_active FROM member_badges WHERE member_id = ? AND badge_name = ? ORDER BY id DESC LIMIT 1";
    $stmt = $conn->prepare($checkMemberQuery);
    $stmt->bind_param("is", $member_id, $badge_name);
    $stmt->execute();
    $existingBadge = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    // If badge exists and is active, don't re-assign
    if ($existingBadge && $existingBadge['is_active'] == 1) {
        return; // Badge already exists and is active
    }
    
    // If badge exists but is inactive (manually removed by admin), don't re-assign
    if ($existingBadge && $existingBadge['is_active'] == 0) {
        // Log that we're respecting admin removal
        error_log("Skipping assignment of '$badge_name' to member $member_id - badge was manually removed by admin");
        return; // Badge was manually removed, respect admin decision
    }
    
    // Assign new badge only if no record exists at all
    $current_time = date('Y-m-d H:i:s');
    $assignQuery = "INSERT INTO member_badges (member_id, badge_name, assigned_at) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($assignQuery);
    $stmt->bind_param("iss", $member_id, $badge_name, $current_time);
    $stmt->execute();
    $stmt->close();
    
    // Log activity
    logBadgeActivity($member_id, $badge_name, $reason);
    
    // Create notification
    require_once __DIR__ . '/notifications_handler.php';
    createNotification($member_id, 'badge_earned', 'New Badge Earned!', 
        'Congratulations! You have earned the "' . $badge_name . '" badge. ' . $reason);
}

/**
 * Log badge assignment activity
 */
function logBadgeActivity($member_id, $badge_name, $reason) {
    global $conn;
    
    // Check if member_activities table exists
    $checkTable = "SHOW TABLES LIKE 'member_activities'";
    $result = $conn->query($checkTable);
    if ($result && $result->num_rows > 0) {
        $activityQuery = "INSERT INTO member_activities (member_id, activity_type, activity_description) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($activityQuery);
        $activityType = 'badge_earned';
        $description = "Earned badge: " . $badge_name . ($reason ? " - " . $reason : "");
        $stmt->bind_param("iss", $member_id, $activityType, $description);
        $stmt->execute();
        $stmt->close();
    }
}

/**
 * Calculate and assign all badges for a member
 */
function calculateAllBadges($member_id) {
    assignMembershipBadges($member_id);
    assignResearchBadges($member_id);
    assignActivityBadges($member_id);
    assignResourceBadges($member_id);
    assignEventBadges($member_id);
}

/**
 * Get all badges for a member
 */
function getMemberBadges($member_id) {
    global $conn;
    
    // Check if is_active column exists
    $checkColumn = "SHOW COLUMNS FROM member_badges LIKE 'is_active'";
    $columnResult = $conn->query($checkColumn);
    $hasActiveColumn = $columnResult && $columnResult->num_rows > 0;
    
    $query = "SELECT DISTINCT mb.*, bp.description, bp.resource_access, bp.research_access 
              FROM member_badges mb
              LEFT JOIN badge_permissions bp ON mb.badge_name = bp.badge_name
              WHERE mb.member_id = ?";
    
    // Only add is_active filter if column exists
    if ($hasActiveColumn) {
        $query .= " AND mb.is_active = 1";
    }
    
    $query .= " ORDER BY mb.earned_at DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $member_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $badges = [];
    while ($row = $result->fetch_assoc()) {
        $badges[] = $row;
    }
    $stmt->close();
    
    return $badges;
}

/**
 * Get badge count for a member
 */
function getMemberBadgeCount($member_id) {
    global $conn;
    
    $query = "SELECT COUNT(*) as count FROM member_badges WHERE member_id = ? AND is_active = 1";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $member_id);
    $stmt->execute();
    $count = $stmt->get_result()->fetch_assoc()['count'];
    $stmt->close();
    
    return $count;
}

?>

