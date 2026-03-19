<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    die('Unauthorized');
}

include 'include/conn.php';

echo "<h2>Find Member ID</h2>";

// Find Hilina Teshome
$query = "SELECT id, fullname, membership_id, email FROM registrations WHERE fullname LIKE '%Hilina%' OR fullname LIKE '%Teshome%' ORDER BY fullname";
$result = $conn->query($query);

if ($result && $result->num_rows > 0) {
    echo "<table border='1'><tr><th>ID</th><th>Full Name</th><th>Membership ID</th><th>Email</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr><td>{$row['id']}</td><td>{$row['fullname']}</td><td>{$row['membership_id']}</td><td>{$row['email']}</td></tr>";
    }
    echo "</table>";
} else {
    echo "No members found with name containing 'Hilina' or 'Teshome'<br>";
}

// Show all members with Research Leader badge
echo "<h3>All members with Research Leader badge:</h3>";
$query = "SELECT r.id, r.fullname, r.membership_id, mb.is_active 
          FROM registrations r 
          JOIN member_badges mb ON r.id = mb.member_id 
          WHERE mb.badge_name = 'Research Leader' 
          ORDER BY r.fullname";
$result = $conn->query($query);

if ($result && $result->num_rows > 0) {
    echo "<table border='1'><tr><th>Member ID</th><th>Full Name</th><th>Membership ID</th><th>Badge Active</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr><td>{$row['id']}</td><td>{$row['fullname']}</td><td>{$row['membership_id']}</td><td>{$row['is_active']}</td></tr>";
    }
    echo "</table>";
} else {
    echo "No members found with Research Leader badge<br>";
}
?>
