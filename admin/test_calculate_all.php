<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    die('Unauthorized');
}

include 'include/conn.php';
include_once '../include/badge_calculator.php';

echo "<h2>Test calculateAllBadges()</h2>";

// Check current state
echo "<h3>Before calculateAllBadges():</h3>";
$query = "SELECT id, member_id, badge_name, is_active FROM member_badges WHERE member_id = 1 ORDER BY id DESC";
$result = $conn->query($query);
if ($result && $result->num_rows > 0) {
    echo "<table border='1'><tr><th>ID</th><th>Badge Name</th><th>Is Active</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr><td>{$row['id']}</td><td>{$row['badge_name']}</td><td>{$row['is_active']}</td></tr>";
    }
    echo "</table>";
} else {
    echo "No badges found<br>";
}

// Call calculateAllBadges
echo "<h3>Calling calculateAllBadges(1)...</h3>";
calculateAllBadges(1);

// Check state after
echo "<h3>After calculateAllBadges():</h3>";
$result = $conn->query($query);
if ($result && $result->num_rows > 0) {
    echo "<table border='1'><tr><th>ID</th><th>Badge Name</th><th>Is Active</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr><td>{$row['id']}</td><td>{$row['badge_name']}</td><td>{$row['is_active']}</td></tr>";
    }
    echo "</table>";
} else {
    echo "No badges found<br>";
}

// Test getMemberBadges
echo "<h3>getMemberBadges() result:</h3>";
$badges = getMemberBadges(1);
echo "Count: " . count($badges) . "<br>";
foreach ($badges as $badge) {
    echo "- {$badge['badge_name']} (active: " . ($badge['is_active'] ?? 'N/A') . ")<br>";
}
?>
