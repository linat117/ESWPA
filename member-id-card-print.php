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

// Check if membership is expired
if (!empty($member['expiry_date'])) {
    $expiryDate = new DateTime($member['expiry_date']);
    $today = new DateTime();
    if ($expiryDate < $today) {
        header("Location: member-dashboard.php?error=Your membership has expired. Please renew to generate ID card.");
        exit();
    }
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print ID Card - <?php echo htmlspecialchars($member['fullname']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/id-card-print.css">
</head>
<body>
    <!-- Print Controls (hidden when printing) -->
    <div class="print-controls">
        <div class="print-controls-content">
            <h2><i class="fas fa-print"></i> Print Preview</h2>
            <p>Review your ID card below. For <strong>standard ID/ATM size</strong> (85.6 × 54 mm), set scale to <strong>100%</strong> or &quot;Actual size&quot; in the print dialog. Enable &quot;Print backgrounds&quot; for best results.</p>
            <div class="print-actions">
                <button onclick="window.print()" class="btn-print">
                    <i class="fas fa-print"></i> Print ID Card
                </button>
                <button onclick="window.close()" class="btn-close">
                    <i class="fas fa-times"></i> Close
                </button>
                <a href="member-generate-id-card.php" class="btn-back">
                    <i class="fas fa-arrow-left"></i> Back to ID Card
                </a>
            </div>
        </div>
    </div>

    <!-- ID Card Container -->
    <div class="id-card-print-container">
        <!-- Front of ID Card -->
        <div class="id-card-wrapper">
            <div class="id-card-front">
                <!-- Top Red Banner with Logo -->
                <div class="id-card-top-banner">
                    <div class="id-card-logo">
                        <span class="id-card-logo-text">ESWPA</span>
                        <span class="id-card-tagline">Ethiopian Social Workers Professional Association</span>
                    </div>
                </div>
                
                <!-- Photo Section - Circular -->
                <div class="id-card-photo-section">
                    <div class="id-card-photo">
                        <?php if (!empty($member['photo'])): ?>
                            <img src="<?php echo htmlspecialchars($member['photo']); ?>" 
                                 alt="Member Photo" 
                                 class="id-photo-img">
                        <?php else: ?>
                            <div class="id-photo-placeholder">
                                <i class="fas fa-user"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Member Info Section -->
                <div class="id-card-member-info">
                    <h4 class="id-member-name"><?php echo htmlspecialchars($member['fullname']); ?></h4>
                    <p class="id-qualification"><?php echo htmlspecialchars($member['qualification']); ?></p>
                    
                    <div class="id-details-grid">
                        <div class="id-detail-item">
                            <strong>Team</strong>
                            <span class="detail-value"><?php echo htmlspecialchars($company['company_name']); ?></span>
                        </div>
                        <div class="id-detail-item">
                            <strong>Emp ID</strong>
                            <span class="detail-value"><?php echo htmlspecialchars($member['membership_id']); ?></span>
                        </div>
                        <div class="id-detail-item">
                            <strong>Date of Issue</strong>
                            <span class="detail-value"><?php echo date('d/m/Y', strtotime($member['created_at'])); ?></span>
                        </div>
                        <div class="id-detail-item">
                            <strong>Date of Birth</strong>
                            <span class="detail-value"><?php echo !empty($member['date_of_birth']) ? date('d/m/Y', strtotime($member['date_of_birth'])) : 'N/A'; ?></span>
                        </div>
                    </div>
                </div>
                
                <!-- Bottom Section with Barcode -->
                <div class="id-card-bottom">
                    <div class="id-card-barcode">|||| ||| || ||||| || |||| ||| |||||</div>
                    <div class="id-card-bottom-banner"></div>
                </div>
            </div>
        </div>

        <!-- Back of ID Card -->
        <div class="id-card-wrapper">
            <div class="id-card-back">
                <!-- Top Red Banner (same as front) -->
                <div class="id-card-back-top-banner">
                    <div class="id-card-back-logo-top">
                        <span class="id-card-back-logo-text-top">ESWPA</span>
                        <span class="id-card-back-tagline-top">Ethiopian Social Workers Professional Association</span>
                    </div>
                </div>
                
                <!-- Back Content -->
                <div class="id-card-back-content">
                    <h4 class="id-back-member-name"><?php echo htmlspecialchars($member['fullname']); ?></h4>
                    <p class="id-back-qualification"><?php echo htmlspecialchars($member['qualification']); ?></p>
                    
                    <!-- Signature -->
                    <div class="id-back-signature">
                        <?php if (!empty($company['company_signature'])): ?>
                            <img src="<?php echo htmlspecialchars($company['company_signature']); ?>" 
                                 alt="Signature" 
                                 class="id-back-signature-img">
                        <?php else: ?>
                            <div class="id-back-signature-line"></div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Dates and ID -->
                    <div class="id-back-details">
                        <p class="id-back-detail-item"><strong>Joined Date:</strong> <?php echo date('d/m/Y', strtotime($member['created_at'])); ?></p>
                        <p class="id-back-detail-item"><strong>Expiry Date:</strong> <?php echo !empty($member['expiry_date']) ? date('d/m/Y', strtotime($member['expiry_date'])) : 'N/A'; ?></p>
                        <p class="id-back-detail-item"><strong>Emp ID:</strong> <?php echo htmlspecialchars($member['membership_id']); ?></p>
                    </div>
                    
                    <!-- Information Text -->
                    <p class="id-back-text">This card is the property of Ethiopian Social Workers Professional Association and must be returned upon termination of membership.</p>
                    <p class="id-back-text">For verification, scan the QR code or visit our website. Report lost or stolen cards immediately.</p>
                    
                    <!-- QR Code -->
                    <div class="id-qr-container">
                        <div id="qrcode"></div>
                    </div>
                    
                    <!-- Barcode -->
                    <div class="id-back-barcode">|||| ||| || ||||| || |||| ||| |||||</div>
                </div>
                
                <!-- Bottom Red Banner (same as front) -->
                <div class="id-card-back-bottom">
                    <div class="id-card-back-logo">
                        <span class="id-card-back-logo-text">ESWPA</span>
                        <span class="id-card-back-tagline">Ethiopian Social Workers Professional Association</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- QR Code Library -->
    <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
    <script>
        // Generate QR Code after page load
        window.addEventListener('load', function() {
            if (typeof QRCode !== 'undefined') {
                var qrElement = document.getElementById("qrcode");
                if (qrElement) {
                    var qrCode = new QRCode(qrElement, {
                        text: "<?php echo $verificationUrl; ?>",
                        width: 50,
                        height: 50,
                        colorDark: "#000000",
                        colorLight: "#ffffff",
                        correctLevel: QRCode.CorrectLevel.H
                    });
                }
            }
        });
    </script>
</body>
</html>

