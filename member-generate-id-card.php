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

// Generate verification code if not exists
$verificationCode = bin2hex(random_bytes(16)); // 32 character code

// Check if verification code already exists for this member
$checkCodeQuery = "SELECT verification_code FROM id_card_verification WHERE membership_id = ?";
$checkStmt = $conn->prepare($checkCodeQuery);
$checkStmt->bind_param("s", $member['membership_id']);
$checkStmt->execute();
$codeResult = $checkStmt->get_result();

if ($codeResult->num_rows > 0) {
    // Use existing code
    $existingCode = $codeResult->fetch_assoc();
    $verificationCode = $existingCode['verification_code'];
} else {
    // Insert new verification code
    $insertCodeQuery = "INSERT INTO id_card_verification (membership_id, verification_code) VALUES (?, ?)";
    $insertStmt = $conn->prepare($insertCodeQuery);
    $insertStmt->bind_param("ss", $member['membership_id'], $verificationCode);
    $insertStmt->execute();
    $insertStmt->close();
}

$checkStmt->close();

// Mark ID card as generated
if ($member['id_card_generated'] == 0) {
    $updateQuery = "UPDATE registrations SET id_card_generated = 1, id_card_generated_at = NOW() WHERE id = ?";
    $updateStmt = $conn->prepare($updateQuery);
    $updateStmt->bind_param("i", $member_id);
    $updateStmt->execute();
    $updateStmt->close();
}

// Get company info
$companyQuery = "SELECT * FROM company_info LIMIT 1";
$companyResult = $conn->query($companyQuery);
$company = $companyResult->fetch_assoc();
if (!$company) {
    // Default company info
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
<?php include 'head.php'; ?>

<body>
    <!-- Header -->
    <?php include 'member-header-v1.2.php'; ?>
    <!-- End Header -->

    <link href="assets/css/member-panel.css" rel="stylesheet">

    <div class="mp-content-wrapper">
        <div class="mp-container">
            <div class="mp-content">
                <div class="mp-flex-between mp-mb-md">
                    <div>
                        <h2 class="mp-page-title"><i class="fas fa-id-card"></i> My ID Card</h2>
                        <p style="color: var(--mp-gray-600); margin: var(--mp-space-xs) 0 0 0; font-size: 0.875rem;">Your official member identification card</p>
                    </div>
                        </div>

                            <!-- ID Card Display -->
                <div class="mp-id-card-container">
                                <!-- Front of ID Card -->
                    <div class="mp-id-card-front">
                        <div class="mp-id-card-header">
                            <h3 class="mp-id-card-logo">ESWPA</h3>
                            <p class="mp-id-card-subtitle">Ethiopian Social Workers Professional Association</p>
                                        </div>
                                        
                        <div class="mp-id-card-photo">
                                            <?php if (!empty($member['photo'])): ?>
                                                <img src="<?php echo htmlspecialchars($member['photo']); ?>" 
                                                     alt="Member Photo" 
                                     class="mp-id-photo-img">
                                            <?php else: ?>
                                <div class="mp-id-photo-placeholder">
                                    <i class="fas fa-user"></i>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        
                        <div class="mp-id-card-info">
                            <h4 class="mp-id-member-name"><?php echo htmlspecialchars($member['fullname']); ?></h4>
                            <p class="mp-id-qualification"><?php echo htmlspecialchars($member['qualification']); ?></p>
                            <div class="mp-id-divider"></div>
                            <div class="mp-id-details">
                                <p class="mp-id-detail-item"><strong>ID:</strong> <?php echo htmlspecialchars($member['membership_id']); ?></p>
                                <p class="mp-id-detail-item"><strong>Email:</strong> <?php echo htmlspecialchars($member['email']); ?></p>
                                <p class="mp-id-detail-item"><strong>Sex:</strong> <?php echo htmlspecialchars($member['sex']); ?></p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Back of ID Card -->
                    <div class="mp-id-card-back">
                        <div class="mp-id-card-back-content">
                            <h5 class="mp-id-back-title">MEMBER INFORMATION</h5>
                                            
                            <ul class="mp-id-back-list">
                                <li><?php echo htmlspecialchars($company['company_name']); ?></li>
                                <li><?php echo htmlspecialchars($company['address'] ?? 'Addis Ababa, Ethiopia'); ?></li>
                                            </ul>
                                            
                            <div class="mp-id-divider"></div>
                                            
                            <div class="mp-id-back-details">
                                <p class="mp-id-back-detail-item"><strong>Join Date:</strong> <?php echo date('M d, Y', strtotime($member['created_at'])); ?></p>
                                <p class="mp-id-back-detail-item"><strong>Expiry Date:</strong> <?php echo !empty($member['expiry_date']) ? date('M d, Y', strtotime($member['expiry_date'])) : 'N/A'; ?></p>
                            </div>
                            
                            <div class="mp-id-divider"></div>
                            
                            <!-- QR Code on Back -->
                            <div class="mp-id-qr-back-container">
                                <div id="qrcode" class="mp-id-qrcode-back"></div>
                                <p class="mp-id-qr-label">Scan to Verify</p>
                            </div>
                            
                            <div class="mp-id-divider"></div>
                                            
                                            <!-- Company Signature Area -->
                            <div class="mp-id-signature">
                                                <?php if (!empty($company['company_signature'])): ?>
                                                    <img src="<?php echo htmlspecialchars($company['company_signature']); ?>" 
                                                         alt="Signature" 
                                         class="mp-id-signature-img">
                                                <?php else: ?>
                                    <div class="mp-id-signature-line">
                                        <p class="mp-id-signature-text">Authorized Signature</p>
                                                    </div>
                                                <?php endif; ?>
                                <p class="mp-id-signature-org"><?php echo htmlspecialchars($company['company_name']); ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                <div class="mp-id-actions">
                    <button onclick="downloadIDCard()" class="mp-btn mp-btn-primary">
                                        <i class="fas fa-download"></i> Download as PDF
                                    </button>
                    <button onclick="window.open('member-id-card-print.php', '_blank')" class="mp-btn mp-btn-success">
                                        <i class="fas fa-print"></i> Print ID Card
                                    </button>
                    <a href="member-dashboard.php" class="mp-btn mp-btn-secondary">
                                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
    
    <!-- QR Code Library (using CDN) -->
    <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js" defer></script>

    <script>
        // Generate QR Code after page load
        let qrCodeGenerated = false;
        window.addEventListener('load', function() {
            if (typeof QRCode !== 'undefined' && document.getElementById("qrcode")) {
                var qrCode = new QRCode(document.getElementById("qrcode"), {
                    text: "<?php echo $verificationUrl; ?>",
                    width: 100,
                    height: 100,
                    colorDark: "#000000",
                    colorLight: "#ffffff",
                    correctLevel: QRCode.CorrectLevel.H
                });
                qrCodeGenerated = true;
            }
        });

        // Download ID Card as PDF
        function downloadIDCard() {
            // Open in new window for PDF download/print
            window.open('include/generate-id-card-pdf.php?member_id=<?php echo $member_id; ?>', '_blank');
        }
        
        // Enhanced print function to ensure QR code is rendered
        window.addEventListener('beforeprint', function() {
            // Ensure QR code is visible
            const qrElement = document.getElementById("qrcode");
            if (qrElement) {
                qrElement.style.visibility = 'visible';
                qrElement.style.display = 'block';
            }
        });
    </script>

</body>

</html>

