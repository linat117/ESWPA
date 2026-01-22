<?php
/**
 * Bibliography Handler
 * Handles CRUD operations for bibliography collections and items
 */

require_once __DIR__ . '/config.php';

/**
 * Create a bibliography collection
 */
function createBibliographyCollection($member_id, $name, $description = null, $is_public = false) {
    global $conn;
    
    $query = "INSERT INTO bibliography_collections (member_id, name, description, is_public) 
              VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $is_public_int = $is_public ? 1 : 0;
    $stmt->bind_param("issi", $member_id, $name, $description, $is_public_int);
    
    if ($stmt->execute()) {
        $collection_id = $stmt->insert_id;
        $stmt->close();
        return ['success' => true, 'collection_id' => $collection_id];
    } else {
        $error = $stmt->error;
        $stmt->close();
        return ['success' => false, 'error' => $error];
    }
}

/**
 * Add item to bibliography collection
 */
function addBibliographyItem($collection_id, $citation_text, $resource_id = null, $research_id = null, $notes = null) {
    global $conn;
    
    $query = "INSERT INTO bibliography_items (collection_id, resource_id, research_id, citation_text, notes) 
              VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iiiss", $collection_id, $resource_id, $research_id, $citation_text, $notes);
    
    if ($stmt->execute()) {
        $item_id = $stmt->insert_id;
        $stmt->close();
        return ['success' => true, 'item_id' => $item_id];
    } else {
        $error = $stmt->error;
        $stmt->close();
        return ['success' => false, 'error' => $error];
    }
}

/**
 * Get bibliography collections for a member
 */
function getMemberCollections($member_id) {
    global $conn;
    
    $query = "SELECT bc.*, COUNT(bi.id) as item_count
              FROM bibliography_collections bc
              LEFT JOIN bibliography_items bi ON bc.id = bi.collection_id
              WHERE bc.member_id = ?
              GROUP BY bc.id
              ORDER BY bc.created_at DESC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $member_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $collections = [];
    while ($row = $result->fetch_assoc()) {
        $collections[] = $row;
    }
    
    $stmt->close();
    return $collections;
}

/**
 * Get items in a collection
 */
function getCollectionItems($collection_id, $member_id) {
    global $conn;
    
    $query = "SELECT bi.*, r.title as resource_title, rp.title as research_title
              FROM bibliography_items bi
              LEFT JOIN resources r ON bi.resource_id = r.id
              LEFT JOIN research_projects rp ON bi.research_id = rp.id
              WHERE bi.collection_id = ? AND EXISTS (
                  SELECT 1 FROM bibliography_collections WHERE id = ? AND member_id = ?
              )
              ORDER BY bi.added_at DESC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iii", $collection_id, $collection_id, $member_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $items = [];
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
    
    $stmt->close();
    return $items;
}

/**
 * Delete collection
 */
function deleteCollection($collection_id, $member_id) {
    global $conn;
    
    $query = "DELETE FROM bibliography_collections WHERE id = ? AND member_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $collection_id, $member_id);
    
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
 * Delete item from collection
 */
function deleteBibliographyItem($item_id, $member_id) {
    global $conn;
    
    $query = "DELETE bi FROM bibliography_items bi
              INNER JOIN bibliography_collections bc ON bi.collection_id = bc.id
              WHERE bi.id = ? AND bc.member_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $item_id, $member_id);
    
    if ($stmt->execute()) {
        $stmt->close();
        return ['success' => true];
    } else {
        $error = $stmt->error;
        $stmt->close();
        return ['success' => false, 'error' => $error];
    }
}

