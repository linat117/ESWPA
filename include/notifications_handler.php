<?php
/**
 * Notifications Handler
 * Functions to create and manage member notifications
 * 
 * Last Updated: December 24, 2025
 */

/**
 * Create a notification for a member
 */
function createNotification($member_id, $type, $title, $message, $related_id = null, $related_type = null) {
    global $conn;
    
    // Check if notifications table exists
    $checkTable = "SHOW TABLES LIKE 'notifications'";
    $tableResult = $conn->query($checkTable);
    
    if (!$tableResult || $tableResult->num_rows == 0) {
        return false; // Table doesn't exist yet
    }
    
    $query = "INSERT INTO notifications (member_id, type, title, message, related_id, related_type) 
              VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        return false;
    }
    
    $stmt->bind_param("isssis", $member_id, $type, $title, $message, $related_id, $related_type);
    
    if ($stmt->execute()) {
        $notification_id = $stmt->insert_id;
        $stmt->close();
        return $notification_id;
    } else {
        $stmt->close();
        return false;
    }
}

/**
 * Create notification for multiple members
 */
function createBulkNotifications($member_ids, $type, $title, $message, $related_id = null, $related_type = null) {
    global $conn;
    
    // Check if notifications table exists
    $checkTable = "SHOW TABLES LIKE 'notifications'";
    $tableResult = $conn->query($checkTable);
    
    if (!$tableResult || $tableResult->num_rows == 0) {
        return false;
    }
    
    $query = "INSERT INTO notifications (member_id, type, title, message, related_id, related_type) VALUES ";
    $values = [];
    $types = '';
    $params = [];
    
    foreach ($member_ids as $member_id) {
        $values[] = "(?, ?, ?, ?, ?, ?)";
        $types .= 'isssis';
        $params[] = $member_id;
        $params[] = $type;
        $params[] = $title;
        $params[] = $message;
        $params[] = $related_id;
        $params[] = $related_type;
    }
    
    $query .= implode(', ', $values);
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        return false;
    }
    
    $stmt->bind_param($types, ...$params);
    $result = $stmt->execute();
    $stmt->close();
    
    return $result;
}

/**
 * Mark notification as read
 */
function markNotificationAsRead($notification_id, $member_id) {
    global $conn;
    
    $query = "UPDATE notifications SET is_read = 1, read_at = NOW() WHERE id = ? AND member_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $notification_id, $member_id);
    $result = $stmt->execute();
    $stmt->close();
    
    return $result;
}

/**
 * Get unread notification count for a member
 */
function getUnreadNotificationCount($member_id) {
    global $conn;
    
    $checkTable = "SHOW TABLES LIKE 'notifications'";
    $tableResult = $conn->query($checkTable);
    
    if (!$tableResult || $tableResult->num_rows == 0) {
        return 0;
    }
    
    $query = "SELECT COUNT(*) as count FROM notifications WHERE member_id = ? AND is_read = 0";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $member_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    return $row['count'] ?? 0;
}

/**
 * Create notification when member is approved
 */
function notifyMemberApproved($member_id) {
    $title = "Membership Approved";
    $message = "Congratulations! Your membership has been approved. You can now access all member features and generate your ID card.";
    return createNotification($member_id, 'success', $title, $message, $member_id, 'member_approval');
}

/**
 * Create notification when membership is about to expire
 */
function notifyMembershipExpiring($member_id, $days_remaining) {
    $title = "Membership Expiring Soon";
    $message = "Your membership will expire in {$days_remaining} day(s). Please renew your membership to continue enjoying member benefits.";
    return createNotification($member_id, 'warning', $title, $message, $member_id, 'membership_expiry');
}

/**
 * Create notification when membership expires
 */
function notifyMembershipExpired($member_id) {
    $title = "Membership Expired";
    $message = "Your membership has expired. Please renew your membership to regain access to member features.";
    return createNotification($member_id, 'warning', $title, $message, $member_id, 'membership_expiry');
}

/**
 * Create notification for new news/article
 */
function notifyNewNews($member_id, $news_id, $news_title) {
    $title = "New Article Published";
    $message = "A new article has been published: {$news_title}";
    return createNotification($member_id, 'info', $title, $message, $news_id, 'news');
}

/**
 * Create notification for new event
 */
function notifyNewEvent($member_id, $event_id, $event_title) {
    $title = "New Event";
    $message = "A new event has been scheduled: {$event_title}";
    return createNotification($member_id, 'info', $title, $message, $event_id, 'event');
}

/**
 * Create notification for new resource
 */
function notifyNewResource($member_id, $resource_id, $resource_title) {
    $title = "New Resource Available";
    $message = "A new resource has been added: {$resource_title}";
    return createNotification($member_id, 'info', $title, $message, $resource_id, 'resource');
}

?>

