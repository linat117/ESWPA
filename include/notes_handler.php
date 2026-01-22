<?php
/**
 * Notes Handler
 * Handles CRUD operations for research notes
 */

require_once __DIR__ . '/config.php';

/**
 * Create a new note
 */
function createNote($member_id, $title, $content, $research_id = null, $resource_id = null, $tags = null, $is_shared = false) {
    global $conn;
    
    $query = "INSERT INTO research_notes (member_id, research_id, resource_id, title, content, tags, is_shared) 
              VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $is_shared_int = $is_shared ? 1 : 0;
    $stmt->bind_param("iiisssi", $member_id, $research_id, $resource_id, $title, $content, $tags, $is_shared_int);
    
    if ($stmt->execute()) {
        $note_id = $stmt->insert_id;
        $stmt->close();
        return ['success' => true, 'note_id' => $note_id];
    } else {
        $error = $stmt->error;
        $stmt->close();
        return ['success' => false, 'error' => $error];
    }
}

/**
 * Update a note
 */
function updateNote($note_id, $member_id, $title, $content, $tags = null, $is_shared = false) {
    global $conn;
    
    $query = "UPDATE research_notes SET title = ?, content = ?, tags = ?, is_shared = ? 
              WHERE id = ? AND member_id = ?";
    $stmt = $conn->prepare($query);
    $is_shared_int = $is_shared ? 1 : 0;
    $stmt->bind_param("sssiii", $title, $content, $tags, $is_shared_int, $note_id, $member_id);
    
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
 * Delete a note
 */
function deleteNote($note_id, $member_id) {
    global $conn;
    
    $query = "DELETE FROM research_notes WHERE id = ? AND member_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $note_id, $member_id);
    
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
 * Get notes for a member
 */
function getMemberNotes($member_id, $research_id = null, $resource_id = null, $search = null) {
    global $conn;
    
    $where = ["member_id = ?"];
    $params = [$member_id];
    $types = 'i';
    
    if ($research_id) {
        $where[] = "research_id = ?";
        $params[] = $research_id;
        $types .= 'i';
    }
    
    if ($resource_id) {
        $where[] = "resource_id = ?";
        $params[] = $resource_id;
        $types .= 'i';
    }
    
    if ($search) {
        $where[] = "(title LIKE ? OR content LIKE ?)";
        $searchParam = "%$search%";
        $params[] = $searchParam;
        $params[] = $searchParam;
        $types .= 'ss';
    }
    
    $whereClause = implode(' AND ', $where);
    $query = "SELECT * FROM research_notes WHERE $whereClause ORDER BY updated_at DESC";
    
    $stmt = $conn->prepare($query);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    $notes = [];
    while ($row = $result->fetch_assoc()) {
        $notes[] = $row;
    }
    
    $stmt->close();
    return $notes;
}

/**
 * Get a single note
 */
function getNote($note_id, $member_id) {
    global $conn;
    
    $query = "SELECT * FROM research_notes WHERE id = ? AND member_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $note_id, $member_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $note = $result->fetch_assoc();
        $stmt->close();
        return $note;
    }
    
    $stmt->close();
    return null;
}

