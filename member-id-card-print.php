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
$company = ($companyResult && $companyResult->num_rows > 0) ? $companyResult->fetch_assoc() : null;
if (!$company) {
    $company = [
        'company_name'      => 'Ethiopian Social Workers Professional Association',
        'address'           => 'Addis Ababa, Ethiopia',
        'phone'             => '+251-XXX-XXX-XXXX',
        'email'             => 'info@eswpa.org',
        'website'           => 'www.eswpa.org',
        'company_signature' => null,
        'terms_conditions'  => ''
    ];
}
$company_name      = $company['company_name'] ?? 'Ethiopian Social Workers Professional Association';
$terms_conditions  = trim($company['terms_conditions'] ?? '');
// Default editable text when no specific back-of-card text is stored
$backDefaultText   = 'For verification, scan the QR code or visit our website. Report lost or stolen cards immediately.';
$words = preg_split('/\s+/', trim($company_name), -1, PREG_SPLIT_NO_EMPTY);
$company_short = $words ? strtoupper(substr(implode('', array_map(function ($w) { return isset($w[0]) ? $w[0] : ''; }, $words)), 0, 8)) : 'ESWPA';

// Generate QR target URL – fixed website for all cards
$verificationUrl = 'https://ethiosocialworkers.igebeya.com';

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
                        <span class="id-card-logo-text"><?php echo htmlspecialchars($company_short); ?></span>
                        <span class="id-card-tagline"><?php echo htmlspecialchars($company_name); ?></span>
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
                <!-- Top Banner (logo + company + address/phone, driven by company_info) -->
                <div class="id-card-back-top-banner">
                    <div class="id-card-logo-img">
                        <?php if (!empty($company['company_logo'])): ?>
                            <img src="<?php echo htmlspecialchars($company['company_logo']); ?>" 
                                 alt="Logo" 
                                 role="presentation" 
                                 class="id-logo-image">
                        <?php else: ?>
                            <div class="id-logo-placeholder"><i class="fas fa-id-card"></i></div>
                        <?php endif; ?>
                    </div>
                    <div class="id-card-back-logo-top">
                        <span class="id-card-back-tagline-top"><?php echo htmlspecialchars($company_name); ?></span>
                        <div class="id-card-back-contact">
                            <?php
                            $addr  = trim($company['address'] ?? '');
                            $phone = trim($company['phone'] ?? '');
                            $parts = array_filter([
                                $addr,
                                $phone ? $phone : ''
                            ]);
                            echo implode('  |  ', array_map('htmlspecialchars', $parts)) ?: '';
                            ?>
                        </div>
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
                    
                    <!-- Information Text (driven by company_info / admin template) -->
                    <p class="id-back-text">
                        This card is the property of <?php echo htmlspecialchars($company_name); ?> and must be returned upon termination of membership.
                    </p>
                    <?php if ($terms_conditions !== ''): ?>
                        <p class="id-back-text"><?php echo nl2br(htmlspecialchars($terms_conditions)); ?></p>
                    <?php else: ?>
                        <p class="id-back-text"><?php echo nl2br(htmlspecialchars($backDefaultText)); ?></p>
                    <?php endif; ?>
                    
                    <!-- QR Code (static image) -->
                    <div class="id-qr-container">
                        <img src="assets/images/id-card-qr.jpg"
                             alt="QR Code"
                             style="width:14mm;height:14mm;object-fit:contain;">
                    </div>
                    
                    <!-- Barcode -->
                    <div class="id-back-barcode">|||| ||| || ||||| || |||| ||| |||||</div>
                </div>
                
                <!-- Bottom Banner (back) with email -->
                <div class="id-card-back-bottom">
                    <div class="id-card-back-logo">
                        <span class="id-card-back-logo-text"><?php echo htmlspecialchars($company_short); ?></span>
                        <span class="id-card-back-tagline"><?php echo htmlspecialchars($company_name); ?></span>
                        <?php if (!empty($company['email'])): ?>
                            <span class="id-card-back-email"><?php echo htmlspecialchars($company['email']); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Static QR image is used for the back of the card; no JS generation needed -->
</body>
</html>

