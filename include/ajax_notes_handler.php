<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['member_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/notes_handler.php';

$member_id = $_SESSION['member_id'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'create') {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $research_id = !empty($_POST['research_id']) ? intval($_POST['research_id']) : null;
    $resource_id = !empty($_POST['resource_id']) ? intval($_POST['resource_id']) : null;
    $tags = trim($_POST['tags'] ?? '');
    $is_shared = isset($_POST['is_shared']);
    
    if (!empty($title) && !empty($content)) {
        $result = createNote($member_id, $title, $content, $research_id, $resource_id, $tags, $is_shared);
        if ($result['success']) {
            // Get the created note
            $note = getNote($result['note_id'], $member_id);
            echo json_encode([
                'success' => true,
                'message' => 'Note created successfully!',
                'note' => $note
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => $result['error']]);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Please fill in title and content']);
    }
} elseif ($action === 'update') {
    $note_id = intval($_POST['note_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $tags = trim($_POST['tags'] ?? '');
    $is_shared = isset($_POST['is_shared']);
    
    if ($note_id > 0 && !empty($title) && !empty($content)) {
        $result = updateNote($note_id, $member_id, $title, $content, $tags, $is_shared);
        if ($result['success']) {
            $note = getNote($note_id, $member_id);
            echo json_encode([
                'success' => true,
                'message' => 'Note updated successfully!',
                'note' => $note
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => $result['error']]);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid note data']);
    }
} elseif ($action === 'delete') {
    $note_id = intval($_POST['note_id'] ?? $_GET['note_id'] ?? 0);
    
    if ($note_id > 0) {
        $result = deleteNote($note_id, $member_id);
        if ($result['success']) {
            echo json_encode(['success' => true, 'message' => 'Note deleted successfully!']);
        } else {
            echo json_encode(['success' => false, 'error' => $result['error'] ?? 'Failed to delete note']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid note ID']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid action']);
}
?>

