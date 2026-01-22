<?php
/**
 * Audit Log Helper Functions
 * Use these functions to log all system actions
 */

function logAudit($action, $table_name = null, $record_id = null, $old_value = null, $new_value = null) {
    include 'conn.php';
    
    $user_id = $_SESSION['user_id'] ?? null;
    $user_type = isset($_SESSION['user_id']) ? 'admin' : (isset($_SESSION['member_id']) ? 'member' : null);
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
    
    // Convert arrays/objects to JSON for storage
    if (is_array($old_value) || is_object($old_value)) {
        $old_value = json_encode($old_value);
    }
    if (is_array($new_value) || is_object($new_value)) {
        $new_value = json_encode($new_value);
    }
    
    $query = "INSERT INTO audit_logs (user_id, user_type, action, table_name, record_id, old_value, new_value, ip_address, user_agent) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("isssiisss", $user_id, $user_type, $action, $table_name, $record_id, $old_value, $new_value, $ip_address, $user_agent);
    $stmt->execute();
    $stmt->close();
    $conn->close();
}

