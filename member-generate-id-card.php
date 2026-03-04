<?php
session_start();

// Check if member is logged in
if (!isset($_SESSION['member_id'])) {
    header("Location: member-login.php");
    exit();
}

// Member-side ID card generation is disabled.
// ID cards should be generated from the admin panel only.
header("Location: member-id-card.php?error=ID card generation is handled by the admin. Please contact the association.");
exit();

