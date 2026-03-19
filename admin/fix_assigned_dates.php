<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    die('Unauthorized');
}

include 'include/conn.php';

echo "<h2>Fix Assigned Dates</h2>";

// Update all badges with NULL assigned_at to current timestamp
echo "<h3>Updating badges with NULL assigned_at...</h3>";
$current_time = date('Y-m-d H:i:s');
$updateQuery = "UPDATE member_badges SET assigned_at = ? WHERE assigned_at IS NULL OR assigned_at = ''";
$stmt = $conn->prepare($updateQuery);
$stmt->bind_param("s", $current_time);
if ($stmt->execute()) {
    $affectedRows = $stmt->affected_rows;
    echo "✓ Updated $affectedRows badge records with assigned dates<br>";
} else {
    echo "✗ Update failed: " . $stmt->error . "<br>";
}
$stmt->close();

// Show sample of updated records
echo "<h3>Sample of updated records:</h3>";
$query = "SELECT id, member_id, badge_name, is_active, assigned_at FROM member_badges WHERE assigned_at IS NOT NULL ORDER BY id DESC LIMIT 10";
$result = $conn->query($query);
if ($result && $result->num_rows > 0) {
    echo "<table border='1'><tr><th>ID</th><th>Member ID</th><th>Badge Name</th><th>Is Active</th><th>Assigned At</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr><td>{$row['id']}</td><td>{$row['member_id']}</td><td>{$row['badge_name']}</td><td>{$row['is_active']}</td><td>{$row['assigned_at']}</td></tr>";
    }
    echo "</table>";
} else {
    echo "No records found<br>";
}

echo "<h3>Done! Assigned dates have been fixed.</h3>";
echo "<p><a href='member_badges.php'>Back to Member Badges</a></p>";
?>
