<?php
session_start();

if (!isset($_SESSION['member_id'])) {
    header("Location: ../member-login.php");
    exit();
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/bibliography_handler.php';

$member_id = $_SESSION['member_id'];
$collection_id = intval($_GET['collection_id'] ?? 0);
$format = $_GET['format'] ?? 'text';

if ($collection_id <= 0) {
    die("Invalid collection ID");
}

$items = getCollectionItems($collection_id, $member_id);

if ($format === 'bibtex') {
    header('Content-Type: text/plain');
    header('Content-Disposition: attachment; filename="bibliography.bib"');
    
    foreach ($items as $index => $item) {
        $key = 'item' . ($index + 1);
        echo "@misc{$key,\n";
        echo "  title = {" . addslashes($item['citation_text']) . "},\n";
        if ($item['resource_title']) {
            echo "  note = {Resource: " . addslashes($item['resource_title']) . "},\n";
        } elseif ($item['research_title']) {
            echo "  note = {Research: " . addslashes($item['research_title']) . "},\n";
        }
        echo "}\n\n";
    }
} else {
    header('Content-Type: text/plain');
    header('Content-Disposition: attachment; filename="bibliography.txt"');
    
    foreach ($items as $item) {
        echo $item['citation_text'] . "\n";
        if ($item['notes']) {
            echo "Notes: " . $item['notes'] . "\n";
        }
        echo "\n";
    }
}
?>

