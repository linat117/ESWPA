<?php
session_start();

// Check if member is logged in
if (!isset($_SESSION['member_id'])) {
    die('Unauthorized access');
}

// Get the correct path to config.php (it's in the same include directory)
include __DIR__ . '/config.php';

$member_id = intval($_GET['member_id'] ?? 0);

if ($member_id != $_SESSION['member_id']) {
    die('Unauthorized access');
}

// Get member details
$query = "SELECT r.* FROM registrations r WHERE r.id = ? AND r.approval_status = 'approved'";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $member_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die('Member not found');
}

$member = $result->fetch_assoc();
$stmt->close();

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
    <title>ID Card - <?php echo htmlspecialchars($member['fullname']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }
        
        .id-card-container {
            display: flex;
            flex-direction: row;
            gap: 25mm;
            justify-content: center;
            align-items: flex-start;
            width: 100%;
            max-width: 100%;
            margin: 0 auto;
            padding: 0;
        }
        
        /* Card wrapper for cutting guide */
        .id-card-wrapper {
            position: relative;
            display: inline-block;
            padding: 2mm;
            border: 0.5mm dashed #ff0000;
            border-radius: 0;
            background: transparent;
            page-break-inside: avoid;
        }
        
        /* Corner cutting marks */
        .id-card-wrapper::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            pointer-events: none;
            z-index: 10;
            background-image: 
                linear-gradient(to right, #ff0000 0, #ff0000 3mm, transparent 3mm),
                linear-gradient(to bottom, #ff0000 0, #ff0000 3mm, transparent 3mm),
                linear-gradient(to left, #ff0000 0, #ff0000 3mm, transparent 3mm),
                linear-gradient(to bottom, #ff0000 0, #ff0000 3mm, transparent 3mm),
                linear-gradient(to right, #ff0000 0, #ff0000 3mm, transparent 3mm),
                linear-gradient(to top, #ff0000 0, #ff0000 3mm, transparent 3mm),
                linear-gradient(to left, #ff0000 0, #ff0000 3mm, transparent 3mm),
                linear-gradient(to top, #ff0000 0, #ff0000 3mm, transparent 3mm);
            background-position:
                top left, top left,
                top right, top right,
                bottom left, bottom left,
                bottom right, bottom right;
            background-size:
                3mm 0.5mm, 0.5mm 3mm,
                3mm 0.5mm, 0.5mm 3mm,
                3mm 0.5mm, 0.5mm 3mm,
                3mm 0.5mm, 0.5mm 3mm;
            background-repeat: no-repeat;
        }
        
        .id-card {
            width: 85.6mm;
            height: 53.98mm;
            position: relative;
            overflow: hidden;
            flex-shrink: 0;
            box-sizing: border-box;
            margin: 0;
            border-radius: 4mm;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            background: #ffffff;
        }
        
        /* Front Card */
        .id-card-front {
            background: #ffffff;
            border: none;
            padding: 0;
            display: block;
        }
        
        /* Top Red Banner with Angular Cut */
        .id-card-top-banner {
            background: #dc2626;
            height: 8mm;
            display: flex;
            align-items: center;
            padding: 0 2.5mm;
            position: relative;
            overflow: hidden;
        }
        
        /* Angular geometric cut on right side */
        .id-card-top-banner::after {
            content: '';
            position: absolute;
            right: 0;
            top: 0;
            width: 0;
            height: 0;
            border-style: solid;
            border-width: 8mm 7mm 0 0;
            border-color: #dc2626 transparent transparent #ffffff;
        }
        
        .id-card-logo {
            display: flex;
            flex-direction: column;
            color: white;
            z-index: 2;
            position: relative;
        }
        
        .id-card-logo-text {
            font-size: 10pt;
            font-weight: 900;
            letter-spacing: 0.8pt;
            line-height: 1;
            margin: 0;
        }
        
        .id-card-tagline {
            font-size: 4.5pt;
            opacity: 0.95;
            margin-top: 0.2mm;
            line-height: 1;
        }
        
        /* Photo Section - Centered */
        .id-card-photo-section {
            text-align: center;
            padding: 1mm 0;
            background: #ffffff;
        }
        
        .id-card-photo {
            display: inline-block;
            position: relative;
        }
        
        .member-photo {
            width: 18mm;
            height: 18mm;
            border-radius: 50%;
            border: 2px solid #000000;
            object-fit: cover;
            display: block;
            background: #f5f5f5;
            margin: 0 auto;
        }
        
        .photo-placeholder {
            width: 18mm;
            height: 18mm;
            border-radius: 50%;
            border: 2px solid #000000;
            background: #fee2e2;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
        }
        
        .photo-placeholder i {
            font-size: 9pt;
            color: #dc2626;
            opacity: 0.6;
        }
        
        /* Member Info Section - Centered */
        .id-card-member-info {
            padding: 0.5mm 2.5mm 0 2.5mm;
            background: #ffffff;
            text-align: center;
        }
        
        .member-name {
            font-size: 9pt;
            font-weight: 800;
            color: #dc2626;
            margin: 0 0 0.3mm 0;
            text-transform: uppercase;
            letter-spacing: 0.2pt;
            line-height: 1.1;
        }
        
        .member-qualification {
            font-size: 6pt;
            color: #1f2937;
            margin: 0 0 1mm 0;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.15pt;
            line-height: 1.1;
        }
        
        /* Two Column Grid for Details */
        .id-details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.5mm 1.5mm;
            margin-top: 0.5mm;
            text-align: left;
        }
        
        .id-detail-item {
            font-size: 4.5pt;
            color: #374151;
            line-height: 1.2;
            word-break: break-word;
        }
        
        .id-detail-item strong {
            color: #1f2937;
            font-weight: 700;
            display: block;
            margin-bottom: 0.1mm;
            font-size: 4pt;
        }
        
        .id-detail-item .detail-value {
            color: #dc2626;
            font-weight: 600;
            display: block;
            margin-top: 0.1mm;
            font-size: 4.5pt;
        }
        
        /* Bottom Section with Barcode */
        .id-card-bottom {
            background: #ffffff;
            padding: 0.5mm 0 0 0;
            position: relative;
            margin-top: 0.5mm;
        }
        
        .id-card-barcode {
            text-align: center;
            padding: 0.3mm 0;
            background: #ffffff;
            font-family: 'Courier New', monospace;
            font-size: 6pt;
            letter-spacing: 1pt;
            color: #000000;
            line-height: 1;
        }
        
        /* Bottom Red Banner with Angular Cut */
        .id-card-bottom-banner {
            background: #dc2626;
            height: 4mm;
            margin-top: 0.3mm;
            position: relative;
            overflow: hidden;
        }
        
        /* Angular geometric cut on left side */
        .id-card-bottom-banner::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            width: 0;
            height: 0;
            border-style: solid;
            border-width: 4mm 0 0 7mm;
            border-color: #dc2626 transparent transparent #ffffff;
        }
        
        /* Back Card */
        .id-card-back {
            background: #ffffff;
            border: none;
            padding: 0;
            display: block;
        }
        
        /* Top Red Banner (same as front) */
        .id-card-back-top-banner {
            background: #dc2626;
            height: 8mm;
            display: flex;
            align-items: center;
            padding: 0 2.5mm;
            position: relative;
            overflow: hidden;
        }
        
        .id-card-back-top-banner::after {
            content: '';
            position: absolute;
            right: 0;
            top: 0;
            width: 0;
            height: 0;
            border-style: solid;
            border-width: 8mm 7mm 0 0;
            border-color: #dc2626 transparent transparent #ffffff;
        }
        
        .id-card-back-logo-top {
            display: flex;
            flex-direction: column;
            color: white;
            z-index: 2;
            position: relative;
        }
        
        .id-card-back-logo-text-top {
            font-size: 10pt;
            font-weight: 900;
            letter-spacing: 0.8pt;
            line-height: 1;
            margin: 0;
        }
        
        .id-card-back-tagline-top {
            font-size: 4.5pt;
            opacity: 0.95;
            margin-top: 0.2mm;
            line-height: 1;
        }
        
        .id-card-back-content {
            padding: 1mm 2.5mm;
            background: #ffffff;
            min-height: 0;
            display: flex;
            flex-direction: column;
        }
        
        .back-member-name {
            font-size: 9pt;
            font-weight: 800;
            color: #dc2626;
            margin: 0 0 0.2mm 0;
            text-transform: uppercase;
            letter-spacing: 0.2pt;
            line-height: 1.1;
        }
        
        .back-qualification {
            font-size: 6pt;
            color: #1f2937;
            margin: 0 0 0.8mm 0;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.15pt;
            line-height: 1.1;
        }
        
        .id-back-signature {
            margin: 0.5mm 0;
            padding-bottom: 0.3mm;
            border-bottom: 1px solid #e5e7eb;
            min-height: 6mm;
            flex-shrink: 0;
        }
        
        .id-back-signature-line {
            height: 6mm;
            border-bottom: 1px solid #d1d5db;
            margin-bottom: 0.2mm;
        }
        
        .id-back-signature-img {
            max-height: 6mm;
            max-width: 35mm;
            display: block;
            margin: 0 auto;
        }
        
        .back-details {
            margin: 0.5mm 0;
            flex-shrink: 0;
        }
        
        .back-detail-item {
            font-size: 4.5pt;
            color: #374151;
            margin: 0.2mm 0;
            line-height: 1.2;
        }
        
        .back-detail-item strong {
            color: #1f2937;
            font-weight: 700;
            margin-right: 0.3mm;
        }
        
        .id-back-text {
            font-size: 3.5pt;
            color: #6b7280;
            line-height: 1.3;
            margin: 0.4mm 0;
            text-align: justify;
            padding-left: 1.5mm;
            position: relative;
            flex-shrink: 0;
        }
        
        .id-back-text::before {
            content: '•';
            color: #dc2626;
            font-weight: bold;
            position: absolute;
            left: 0;
        }
        
        .id-qr-container {
            text-align: center;
            margin: 0.5mm 0;
            flex-shrink: 0;
        }
        
        #qrcode {
            display: inline-block;
            width: 12mm;
            height: 12mm;
            background: white;
            padding: 0.5mm;
            border-radius: 1mm;
            border: 1px solid #e5e7eb;
        }
        
        #qrcode canvas,
        #qrcode img {
            display: block;
            width: 100%;
            height: 100%;
        }
        
        .id-back-barcode {
            text-align: center;
            padding: 0.3mm 0;
            background: #ffffff;
            font-family: 'Courier New', monospace;
            font-size: 6pt;
            letter-spacing: 1pt;
            color: #000000;
            line-height: 1;
            margin-top: 0.5mm;
            flex-shrink: 0;
        }
        
        /* Bottom Red Banner (same as front) */
        .id-card-back-bottom {
            background: #dc2626;
            height: 4mm;
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            padding: 0 2.5mm;
            margin-top: auto;
        }
        
        .id-card-back-bottom::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            width: 0;
            height: 0;
            border-style: solid;
            border-width: 4mm 0 0 7mm;
            border-color: #dc2626 transparent transparent #ffffff;
        }
        
        .id-card-back-logo {
            display: flex;
            flex-direction: column;
            color: white;
            z-index: 2;
            position: relative;
        }
        
        .id-card-back-logo-text {
            font-size: 10pt;
            font-weight: 900;
            letter-spacing: 0.8pt;
            line-height: 1;
            margin: 0;
        }
        
        .id-card-back-tagline {
            font-size: 4.5pt;
            opacity: 0.95;
            margin-top: 0.2mm;
            line-height: 1;
        }
        
        @media print {
            @page {
                size: A4 landscape;
                margin: 10mm;
            }
            
            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                color-adjust: exact !important;
            }
            
            html, body {
                padding: 0 !important;
                margin: 0 !important;
                width: 100% !important;
                height: 100% !important;
                overflow: visible !important;
                background: white !important;
            }
            
            .id-card-container {
                display: flex !important;
                flex-direction: row !important;
                justify-content: center !important;
                align-items: center !important;
                gap: 20mm !important;
                width: 100% !important;
                height: 100% !important;
                margin: 0 auto !important;
                padding: 0 !important;
                min-height: 100vh !important;
            }
            
            .id-card-wrapper,
            .id-card-wrapper::before {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                color-adjust: exact !important;
            }
            
            .id-card-wrapper::before {
                opacity: 1 !important;
            }
        }
    </style>
</head>
<body>
    <div class="id-card-container">
        <!-- Front of ID Card -->
        <div class="id-card-wrapper">
            <div class="id-card id-card-front">
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
                             class="member-photo">
                        <?php else: ?>
                        <div class="photo-placeholder">
                            <i class="fas fa-user"></i>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Member Info Section -->
                <div class="id-card-member-info">
                    <h4 class="member-name"><?php echo htmlspecialchars($member['fullname']); ?></h4>
                    <p class="member-qualification"><?php echo htmlspecialchars($member['qualification']); ?></p>
                    
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
            <div class="id-card id-card-back">
                <!-- Top Red Banner (same as front) -->
                <div class="id-card-back-top-banner">
                    <div class="id-card-back-logo-top">
                        <span class="id-card-back-logo-text-top">ESWPA</span>
                        <span class="id-card-back-tagline-top">Ethiopian Social Workers Professional Association</span>
                    </div>
                </div>
                
                <!-- Back Content -->
                <div class="id-card-back-content">
                    <h4 class="back-member-name"><?php echo htmlspecialchars($member['fullname']); ?></h4>
                    <p class="back-qualification"><?php echo htmlspecialchars($member['qualification']); ?></p>
                    
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
                    <div class="back-details">
                        <p class="back-detail-item"><strong>Joined Date:</strong> <?php echo date('d/m/Y', strtotime($member['created_at'])); ?></p>
                        <p class="back-detail-item"><strong>Expiry Date:</strong> <?php echo !empty($member['expiry_date']) ? date('d/m/Y', strtotime($member['expiry_date'])) : 'N/A'; ?></p>
                        <p class="back-detail-item"><strong>Emp ID:</strong> <?php echo htmlspecialchars($member['membership_id']); ?></p>
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
        // Generate QR Code
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
