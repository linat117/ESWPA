<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['member_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/bibliography_handler.php';

$member_id = $_SESSION['member_id'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'create_collection') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $is_public = isset($_POST['is_public']);
    
    if (!empty($name)) {
        $result = createBibliographyCollection($member_id, $name, $description, $is_public);
        if ($result['success']) {
            // Get the created collection
            $collections = getMemberCollections($member_id);
            $collection = null;
            foreach ($collections as $col) {
                if ($col['id'] == $result['collection_id']) {
                    $collection = $col;
                    break;
                }
            }
            echo json_encode([
                'success' => true,
                'message' => 'Bibliography collection created successfully!',
                'collection' => $collection
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => $result['error']]);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Collection name is required']);
    }
} elseif ($action === 'add_item') {
    $collection_id = intval($_POST['collection_id'] ?? 0);
    $citation_text = trim($_POST['citation_text'] ?? '');
    $resource_id = !empty($_POST['resource_id']) ? intval($_POST['resource_id']) : null;
    $research_id = !empty($_POST['research_id']) ? intval($_POST['research_id']) : null;
    $notes = trim($_POST['notes'] ?? '');
    
    if ($collection_id > 0 && !empty($citation_text)) {
        $result = addBibliographyItem($collection_id, $citation_text, $resource_id, $research_id, $notes);
        if ($result['success']) {
            // Get the created item
            $items = getCollectionItems($collection_id, $member_id);
            $item = null;
            foreach ($items as $it) {
                if ($it['id'] == $result['item_id']) {
                    $item = $it;
                    break;
                }
            }
            echo json_encode([
                'success' => true,
                'message' => 'Item added to bibliography!',
                'item' => $item
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => $result['error']]);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Collection ID and citation text are required']);
    }
} elseif ($action === 'delete_collection') {
    $collection_id = intval($_POST['collection_id'] ?? $_GET['collection_id'] ?? 0);
    
    if ($collection_id > 0) {
        $result = deleteCollection($collection_id, $member_id);
        if ($result['success']) {
            echo json_encode(['success' => true, 'message' => 'Collection deleted successfully!']);
        } else {
            echo json_encode(['success' => false, 'error' => $result['error'] ?? 'Failed to delete collection']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid collection ID']);
    }
} elseif ($action === 'delete_item') {
    $item_id = intval($_POST['item_id'] ?? $_GET['item_id'] ?? 0);
    
    if ($item_id > 0) {
        $result = deleteBibliographyItem($item_id, $member_id);
        if ($result['success']) {
            echo json_encode(['success' => true, 'message' => 'Item deleted successfully!']);
        } else {
            echo json_encode(['success' => false, 'error' => $result['error'] ?? 'Failed to delete item']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid item ID']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid action']);
}
?>

