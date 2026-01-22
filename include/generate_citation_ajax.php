<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['member_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit();
}

require_once __DIR__ . '/citation_generator.php';

$source_type = $_POST['source_type'] ?? '';
$format = $_POST['format'] ?? 'apa';
$source_data = json_decode($_POST['source_data'] ?? '{}', true);

if (empty($source_data)) {
    echo json_encode(['success' => false, 'error' => 'No source data']);
    exit();
}

try {
    if ($source_type === 'resource') {
        $citation = generateResourceCitation($source_data, $format);
    } elseif ($source_type === 'research') {
        $citation = generateResearchCitation($source_data, $format);
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid source type']);
        exit();
    }
    
    echo json_encode(['success' => true, 'citation' => $citation]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>

