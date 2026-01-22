<?php
// Start output buffering to prevent any output issues
ob_start();
session_start();

// Check if admin is logged in
if (!isset($_SESSION['user_id'])) {
    ob_end_clean();
    header("Location: auth-login.php");
    exit();
}

include 'include/conn.php';

// Get member_id from GET parameter
$member_id = isset($_GET['member_id']) ? intval($_GET['member_id']) : 0;

if ($member_id <= 0) {
    die("Invalid member ID");
}

// Get member details
$query = "SELECT r.* 
          FROM registrations r 
          WHERE r.id = ? AND r.approval_status = 'approved'";
$stmt = $conn->prepare($query);
if (!$stmt) {
    die("Database error: " . $conn->error);
}
$stmt->bind_param("i", $member_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Member not found or not approved");
}

$member = $result->fetch_assoc();
$stmt->close();

// Check if ID card has been generated
if ($member['id_card_generated'] != 1) {
    die("ID card has not been generated for this member yet.");
}

// Get verification code
$verificationCode = '';
$verificationUrl = '';
if (!empty($member['membership_id'])) {
    $verificationQuery = "SELECT verification_code FROM id_card_verification WHERE membership_id = ? LIMIT 1";
    $verificationStmt = $conn->prepare($verificationQuery);
    if ($verificationStmt) {
        $verificationStmt->bind_param("s", $member['membership_id']);
        $verificationStmt->execute();
        $verificationResult = $verificationStmt->get_result();
        if ($verificationResult->num_rows > 0) {
            $verificationData = $verificationResult->fetch_assoc();
            $verificationCode = $verificationData['verification_code'];
        }
        $verificationStmt->close();
    }
    
    // Generate verification URL only if code exists
    if (!empty($verificationCode)) {
        $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
        $host = $_SERVER['HTTP_HOST'];
        $scriptPath = dirname(dirname($_SERVER['PHP_SELF']));
        $verificationUrl = $protocol . "://" . $host . $scriptPath . "/verify_id.php?code=" . $verificationCode;
    }
}

// Get company info
$companyQuery = "SELECT * FROM company_info LIMIT 1";
$companyResult = $conn->query($companyQuery);
$company = $companyResult ? $companyResult->fetch_assoc() : null;
if (!$company) {
    $company = [
        'company_name' => 'Ethiopian Social Workers Professional Association',
        'address' => 'Addis Ababa, Ethiopia',
        'phone' => '+251-XXX-XXX-XXXX',
        'email' => 'info@eswpa.org',
        'website' => 'www.eswpa.org',
        'company_signature' => null
    ];
}

$conn->close();
ob_end_flush();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print ID Card - <?php echo htmlspecialchars($member['fullname']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Load our styles FIRST, then external CSS, then override again */
        /* COMPREHENSIVE FIX - Ensure cards are proportional and professional */
        
        /* Container layout - FIXED to prevent overlap */
        .id-card-print-container {
            display: flex !important;
            flex-direction: row !important;
            justify-content: center !important;
            align-items: flex-start !important;
            gap: 200px !important;
            width: 100% !important;
            margin: 0 auto !important;
            padding: 40px 20px !important;
            min-width: 1200px !important;
            position: relative !important;
            overflow-x: auto !important;
        }
        
        .id-card-wrapper {
            position: relative !important;
            display: block !important;
            transform: scale(1.5) !important;
            transform-origin: top center !important;
            margin: 0 !important;
            padding: 2mm !important;
            flex-shrink: 0 !important;
            flex-grow: 0 !important;
            width: auto !important;
            height: auto !important;
            isolation: isolate !important;
        }
        
        /* Ensure cards don't overlap */
        .id-card-wrapper:first-child {
            margin-right: 0 !important;
        }
        
        .id-card-wrapper:last-child {
            margin-left: 0 !important;
        }
        
        /* STRICT CARD DIMENSIONS - No overflow allowed */
        .id-card-front,
        .id-card-back {
            width: 85.6mm !important;
            height: 53.98mm !important;
            max-width: 85.6mm !important;
            max-height: 53.98mm !important;
            overflow: hidden !important;
            display: flex !important;
            flex-direction: column !important;
            box-sizing: border-box !important;
            position: relative !important;
            z-index: 1 !important;
            isolation: isolate !important;
        }
        
        /* Prevent any stacking context issues */
        .id-card-wrapper:first-child .id-card-front,
        .id-card-wrapper:first-child .id-card-back {
            z-index: 1 !important;
        }
        
        .id-card-wrapper:last-child .id-card-front,
        .id-card-wrapper:last-child .id-card-back {
            z-index: 1 !important;
        }
        
        /* REDUCE ALL FONT SIZES - Smaller to fit well */
        .id-card-front *,
        .id-card-back * {
            font-size: 3pt !important;
            line-height: 1.15 !important;
        }
        
        /* Front header - much smaller text */
        .id-card-logo-text {
            font-size: 2pt !important;
            font-weight: 900 !important;
            line-height: 1 !important;
        }
        
        .id-card-tagline {
            font-size: 0.8pt !important;
            line-height: 1 !important;
        }
        
        /* Back header - much smaller text */
        .id-card-back-logo-text-top,
        .id-card-back-logo-text {
            font-size: 2pt !important;
            font-weight: 900 !important;
            line-height: 1 !important;
        }
        
        .id-card-back-tagline-top,
        .id-card-back-tagline {
            font-size: 0.8pt !important;
            line-height: 1 !important;
        }
        
        .id-member-name {
            font-size: 4.5pt !important;
            font-weight: 700 !important;
            line-height: 1.1 !important;
            margin: 0.4mm 0 0.15mm 0 !important;
        }
        
        .id-qualification {
            font-size: 3.5pt !important;
            font-weight: 500 !important;
            line-height: 1.1 !important;
            margin: 0 0 0.6mm 0 !important;
        }
        
        .id-detail-item {
            font-size: 3pt !important;
            line-height: 1.1 !important;
            margin: 0.25mm 0 !important;
        }
        
        .id-detail-item strong {
            font-size: 3pt !important;
            font-weight: 600 !important;
        }
        
        .detail-value {
            font-size: 3pt !important;
        }
        
        /* Back side - ALL elements reduced much more */
        .id-back-member-name {
            font-size: 2.8pt !important;
            font-weight: 700 !important;
            line-height: 1.1 !important;
            margin: 0.2mm 0 0.06mm 0 !important;
        }
        
        .id-back-qualification {
            font-size: 2.2pt !important;
            font-weight: 500 !important;
            line-height: 1.1 !important;
            margin: 0 0 0.25mm 0 !important;
        }
        
        .id-back-detail-item {
            font-size: 1.8pt !important;
            line-height: 1.1 !important;
            margin: 0.12mm 0 !important;
        }
        
        .id-back-detail-item strong {
            font-size: 1.8pt !important;
            font-weight: 600 !important;
        }
        
        .id-back-text {
            font-size: 1.5pt !important;
            line-height: 1.1 !important;
            margin: 0.06mm 0 !important;
        }
        
        /* Reduce all other back side elements */
        .id-card-back-content * {
            font-size: 1.8pt !important;
        }
        
        .id-card-back-content .id-back-member-name {
            font-size: 2.8pt !important;
        }
        
        .id-card-back-content .id-back-qualification {
            font-size: 2.2pt !important;
        }
        
        .id-card-back-content .id-back-text {
            font-size: 1.5pt !important;
        }
        
        /* TIGHT SPACING - Everything must fit */
        /* Front header - more height, smaller text */
        .id-card-top-banner {
            height: 8mm !important;
            flex-shrink: 0 !important;
            padding: 1.5mm 2mm !important;
            display: flex !important;
            align-items: center !important;
        }
        
        /* Back header - more height, smaller text */
        .id-card-back-top-banner {
            height: 7.5mm !important;
            flex-shrink: 0 !important;
            padding: 1.5mm 2mm !important;
            display: flex !important;
            align-items: center !important;
        }
        
        .id-card-photo-section {
            padding: 0.4mm 0 !important;
            flex-shrink: 0 !important;
        }
        
        .id-photo-img,
        .id-photo-placeholder {
            width: 11mm !important;
            height: 11mm !important;
        }
        
        .id-card-member-info {
            padding: 0.4mm 0.8mm !important;
            flex: 1 !important;
            min-height: 0 !important;
            overflow: hidden !important;
            display: flex !important;
            flex-direction: column !important;
            justify-content: flex-start !important;
        }
        
        .id-details-grid {
            gap: 0.25mm 0.6mm !important;
            margin-top: 0.25mm !important;
        }
        
        .id-card-back-content {
            padding: 0.5mm !important;
            flex: 1 !important;
            min-height: 0 !important;
            overflow: hidden !important;
            display: flex !important;
            flex-direction: column !important;
            justify-content: flex-start !important;
        }
        
        .id-back-details {
            margin: 0.1mm 0 !important;
        }
        
        .id-back-signature {
            height: 2.5mm !important;
            margin: 0.1mm 0 !important;
            flex-shrink: 0 !important;
        }
        
        .id-qr-container {
            width: 7mm !important;
            height: 7mm !important;
            margin: 0.1mm auto !important;
            flex-shrink: 0 !important;
        }
        
        .id-card-bottom,
        .id-card-bottom-banner,
        .id-card-barcode,
        .id-back-barcode,
        .id-card-back-bottom {
            display: none !important; /* Remove all bottom bars and barcodes */
        }
        
        /* Prevent text overflow */
        * {
            word-wrap: break-word !important;
            overflow-wrap: break-word !important;
        }
        
        body {
            margin: 0 !important;
            padding: 0 !important;
        }
        
        /* CRITICAL: Override any absolute positioning from external CSS */
        .id-card-wrapper {
            position: relative !important;
        }
        
        .id-card-front,
        .id-card-back {
            position: relative !important;
        }
        
        /* Ensure no transform causes overlap */
        .id-card-print-container > * {
            will-change: transform !important;
        }
        
        /* Force proper spacing - nuclear option */
        .id-card-print-container .id-card-wrapper:first-child {
            order: 1 !important;
        }
        
        .id-card-print-container .id-card-wrapper:last-child {
            order: 2 !important;
        }
    </style>
    <link rel="stylesheet" href="../assets/css/id-card-print.css">
    <style>
        /* FINAL OVERRIDE - After external CSS loads, ensure no overlap */
        .id-card-print-container {
            display: flex !important;
            flex-direction: row !important;
            gap: 200px !important;
        }
        
        .id-card-wrapper {
            position: relative !important;
            transform: scale(1.5) !important;
            margin: 0 !important;
        }
        
        .id-card-front,
        .id-card-back {
            position: relative !important;
            transform: none !important;
        }
    </style>
</head>
<body>
    <!-- Print Controls (hidden when printing) -->
    <div class="print-controls">
        <div class="print-controls-content">
            <h2><i class="fas fa-print"></i> Print Preview</h2>
            <p>Review the ID card below. Ensure "Print backgrounds" is enabled in your print settings for best results. Click the print button when ready.</p>
            <div class="print-actions">
                <button onclick="window.print()" class="btn-print">
                    <i class="fas fa-print"></i> Print ID Card
                </button>
                <button onclick="window.close()" class="btn-close">
                    <i class="fas fa-times"></i> Close
                </button>
                <a href="id_cards_list.php" class="btn-back">
                    <i class="fas fa-arrow-left"></i> Back to ID Cards List
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
                            <img src="../<?php echo htmlspecialchars($member['photo']); ?>" 
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
                
                <!-- Bottom Section with Barcode - Removed -->
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
                    <!-- Name, Qualification, Dates and ID removed as requested -->
                    
                    <!-- Signature -->
                    <div class="id-back-signature">
                        <?php if (!empty($company['company_signature'])): ?>
                            <img src="../<?php echo htmlspecialchars($company['company_signature']); ?>" 
                                 alt="Signature" 
                                 class="id-back-signature-img">
                        <?php else: ?>
                            <div class="id-back-signature-line"></div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Information Text -->
                    <p class="id-back-text">This card is the property of Ethiopian Social Workers Professional Association and must be returned upon termination of membership.</p>
                    <p class="id-back-text">For verification, scan the QR code or visit our website. Report lost or stolen cards immediately.</p>
                    
                    <!-- QR Code -->
                    <?php if (!empty($verificationUrl)): ?>
                    <div class="id-qr-container">
                        <div id="qrcode"></div>
                    </div>
                    <?php else: ?>
                    <div class="id-qr-container">
                        <p class="text-muted small">Verification code not available</p>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Barcode - Removed -->
                </div>
                
                <!-- Bottom Red Banner - Removed -->
            </div>
        </div>
    </div>

    <!-- QR Code Library -->
    <?php if (!empty($verificationUrl)): ?>
    <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
    <script>
        // Generate QR Code after page load
        window.addEventListener('load', function() {
            if (typeof QRCode !== 'undefined') {
                var qrElement = document.getElementById("qrcode");
                if (qrElement) {
                    try {
                        var qrCode = new QRCode(qrElement, {
                            text: "<?php echo htmlspecialchars($verificationUrl, ENT_QUOTES); ?>",
                            width: 50,
                            height: 50,
                            colorDark: "#000000",
                            colorLight: "#ffffff",
                            correctLevel: QRCode.CorrectLevel.H
                        });
                    } catch (e) {
                        console.error("QR Code generation error:", e);
                    }
                }
            }
        });
    </script>
    <?php endif; ?>
</body>
</html>
