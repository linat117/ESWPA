<?php
session_start();

// Check if member is logged in
if (!isset($_SESSION['member_id'])) {
    header("Location: member-login.php");
    exit();
}

include 'include/config.php';

// Get member details
$member_id = $_SESSION['member_id'];
$query = "SELECT r.*, ma.last_login 
          FROM registrations r 
          LEFT JOIN member_access ma ON r.id = ma.member_id 
          WHERE r.id = ? AND r.approval_status = 'approved'";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $member_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: member-dashboard.php?error=Member not found or not approved");
    exit();
}

$member = $result->fetch_assoc();
$stmt->close();

// Check if ID card has been generated
if ($member['id_card_generated'] == 0) {
    header("Location: member-generate-id-card.php");
    exit();
}

// Get verification code
$verificationQuery = "SELECT verification_code FROM id_card_verification WHERE membership_id = ? LIMIT 1";
$verificationStmt = $conn->prepare($verificationQuery);
$verificationStmt->bind_param("s", $member['membership_id']);
$verificationStmt->execute();
$verificationResult = $verificationStmt->get_result();
$verificationCode = '';
if ($verificationResult->num_rows > 0) {
    $verificationData = $verificationResult->fetch_assoc();
    $verificationCode = $verificationData['verification_code'];
}
$verificationStmt->close();

// Get company info
$companyQuery = "SELECT * FROM company_info LIMIT 1";
$companyResult = $conn->query($companyQuery);
$company = $companyResult->fetch_assoc();
if (!$company) {
    $company = [
        'company_name' => 'Ethiopian Social Workers Professional Association',
        'address' => 'Addis Ababa, Ethiopia',
        'phone' => '+251-XXX-XXX-XXXX',
        'email' => 'info@eswpa.org',
        'website' => 'www.eswpa.org'
    ];
}

// Generate verification URL
$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
$host = $_SERVER['HTTP_HOST'];
$scriptPath = dirname($_SERVER['PHP_SELF']);
$verificationUrl = $protocol . "://" . $host . $scriptPath . "/verify_id.php?code=" . $verificationCode;

$conn->close();

// Redirect to generate page (which will show the card)
header("Location: member-generate-id-card.php");
exit();
?>

