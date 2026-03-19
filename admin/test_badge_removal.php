<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
if (!isset($_SESSION['user_id'])) {
    die('Unauthorized');
}

include 'include/conn.php';

echo "<h2>Badge Removal Test</h2>";

// Test 1: Check if is_active column exists
echo "<h3>1. Checking is_active column:</h3>";
$checkColumn = "SHOW COLUMNS FROM member_badges LIKE 'is_active'";
$columnResult = $conn->query($checkColumn);
if ($columnResult && $columnResult->num_rows > 0) {
    echo "✓ is_active column exists<br>";
} else {
    echo "✗ is_active column does NOT exist<br>";
}

// Test 2: Show all member_badges for a specific member
echo "<h3>2. All badges for member ID 1:</h3>";
$query = "SELECT id, member_id, badge_name, is_active, assigned_at FROM member_badges WHERE member_id = 1 ORDER BY id DESC";
echo "Query: " . $query . "<br>";
$result = $conn->query($query);
if ($result === false) {
    echo "Query failed: " . $conn->error . "<br>";
} elseif ($result && $result->num_rows > 0) {
    echo "<table border='1'><tr><th>ID</th><th>Member ID</th><th>Badge Name</th><th>Is Active</th><th>Assigned At</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr><td>{$row['id']}</td><td>{$row['member_id']}</td><td>{$row['badge_name']}</td><td>{$row['is_active']}</td><td>{$row['assigned_at']}</td></tr>";
    }
    echo "</table>";
    echo "<br><strong>Total records:</strong> " . $result->num_rows . "<br>";
} else {
    echo "No badges found for member 1<br>";
}

// Test 3: Try to manually set a badge to inactive
echo "<h3>3. Manually setting a badge to inactive:</h3>";
$updateQuery = "UPDATE member_badges SET is_active = 0 WHERE member_id = 1 AND badge_name = 'Research Leader' LIMIT 1";
if ($conn->query($updateQuery)) {
    $affectedRows = $conn->affected_rows;
    echo "✓ Update successful - Affected rows: $affectedRows<br>";
} else {
    echo "✗ Update failed: " . $conn->error . "<br>";
}

// Test 4: Check results after update
echo "<h3>4. Badges after manual update:</h3>";
$result = $conn->query($query);
if ($result && $result->num_rows > 0) {
    echo "<table border='1'><tr><th>ID</th><th>Member ID</th><th>Badge Name</th><th>Is Active</th><th>Assigned At</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr><td>{$row['id']}</td><td>{$row['member_id']}</td><td>{$row['badge_name']}</td><td>{$row['is_active']}</td><td>{$row['assigned_at']}</td></tr>";
    }
    echo "</table>";
    echo "<br><strong>Total records:</strong> " . $result->num_rows . "<br>";
} else {
    echo "No badges found for member 123<br>";
}

// Test 5: Test what getMemberBadges returns
echo "<h3>5. What getMemberBadges() returns:</h3>";
include_once '../include/badge_calculator.php';
$badges = getMemberBadges(1);
echo "Count: " . count($badges) . "<br>";
foreach ($badges as $badge) {
    echo "- {$badge['badge_name']} (active: " . ($badge['is_active'] ?? 'N/A') . ")<br>";
}

// Test 6: Test assignBadge function
echo "<h3>6. Testing assignBadge function:</h3>";
echo "Calling assignBadge(1, 'Research Leader', 'Test')...<br>";
assignBadge(1, 'Research Leader', 'Test');

// Test 7: Final check
echo "<h3>7. Final state after assignBadge:</h3>";
$result = $conn->query($query);
if ($result && $result->num_rows > 0) {
    echo "<table border='1'><tr><th>ID</th><th>Member ID</th><th>Badge Name</th><th>Is Active</th><th>Assigned At</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr><td>{$row['id']}</td><td>{$row['member_id']}</td><td>{$row['badge_name']}</td><td>{$row['is_active']}</td><td>{$row['assigned_at']}</td></tr>";
    }
    echo "</table>";
    echo "<br><strong>Total records:</strong> " . $result->num_rows . "<br>";
} else {
    echo "No badges found for member 1<br>";
}

echo "<h3>8. Final getMemberBadges() result:</h3>";
$badges = getMemberBadges(1);
echo "Count: " . count($badges) . "<br>";
foreach ($badges as $badge) {
    echo "- {$badge['badge_name']} (active: " . ($badge['is_active'] ?? 'N/A') . ")<br>";
}
?>
