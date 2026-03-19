<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    die('Unauthorized');
}

include 'include/conn.php';

echo "<h2>member_badges Table Structure</h2>";

// Show table structure
$query = "DESCRIBE member_badges";
$result = $conn->query($query);

if ($result && $result->num_rows > 0) {
    echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr><td>{$row['Field']}</td><td>{$row['Type']}</td><td>{$row['Null']}</td><td>{$row['Key']}</td><td>{$row['Default']}</td><td>{$row['Extra']}</td></tr>";
    }
    echo "</table>";
} else {
    echo "Error: " . $conn->error . "<br>";
}

// Show sample data
echo "<h3>Sample Data:</h3>";
$query = "SELECT * FROM member_badges ORDER BY id DESC LIMIT 5";
$result = $conn->query($query);
if ($result && $result->num_rows > 0) {
    echo "<table border='1'>";
    // Header
    $first_row = $result->fetch_assoc();
    echo "<tr>";
    foreach ($first_row as $key => $value) {
        echo "<th>$key</th>";
    }
    echo "</tr>";
    
    // Data row
    echo "<tr>";
    foreach ($first_row as $key => $value) {
        echo "<td>$value</td>";
    }
    echo "</tr>";
    
    // Remaining rows
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        foreach ($row as $key => $value) {
            echo "<td>$value</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "No data found<br>";
}
?>
