<?php
include 'include/config.php';

$query = 'SELECT id, fullname, membership_id, id_card_generated, approval_status FROM registrations WHERE id = 1 LIMIT 1';
$result = $conn->query($query);

if ($result && $result->num_rows > 0) {
    $member = $result->fetch_assoc();
    echo 'ID: ' . $member['id'] . PHP_EOL;
    echo 'Name: ' . $member['fullname'] . PHP_EOL;
    echo 'Member ID: ' . $member['membership_id'] . PHP_EOL;
    echo 'ID Card Generated: ' . $member['id_card_generated'] . PHP_EOL;
    echo 'Approval Status: ' . $member['approval_status'] . PHP_EOL;
} else {
    echo 'Member not found';
}
?>
