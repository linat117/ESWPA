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

// Has admin generated the ID card?
$idCardGenerated = (int)($member['id_card_generated'] ?? 0);

// Get verification code only if card is generated
$verificationCode = '';
$verificationUrl  = '';
if ($idCardGenerated === 1 && !empty($member['membership_id'])) {
    $verificationQuery = "SELECT verification_code FROM id_card_verification WHERE membership_id = ? LIMIT 1";
    $verificationStmt  = $conn->prepare($verificationQuery);
    $verificationStmt->bind_param("s", $member['membership_id']);
    $verificationStmt->execute();
    $verificationResult = $verificationStmt->get_result();
    if ($verificationResult->num_rows > 0) {
        $verificationData  = $verificationResult->fetch_assoc();
        $verificationCode  = $verificationData['verification_code'];
    }
    $verificationStmt->close();

    if ($verificationCode !== '') {
        $protocol   = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
        $host       = $_SERVER['HTTP_HOST'];
        $scriptPath = dirname($_SERVER['PHP_SELF']);
        $verificationUrl = $protocol . "://" . $host . $scriptPath . "/verify_id.php?code=" . $verificationCode;
    }
}

// Get company info (same as admin)
$companyQuery  = "SELECT * FROM company_info LIMIT 1";
$companyResult = $conn->query($companyQuery);
$company       = ($companyResult && $companyResult->num_rows > 0) ? $companyResult->fetch_assoc() : null;
if (!$company) {
    $company = [
        'company_name' => 'Ethiopian Social Workers Professional Association',
        'address'      => 'Addis Ababa, Ethiopia',
        'phone'        => '+251-XXX-XXX-XXXX',
        'email'        => 'info@eswpa.org',
        'website'      => 'www.eswpa.org',
        'terms_conditions'  => '',
        'company_signature' => null,
        'company_logo'      => null,
    ];
}
$company_name    = $company['company_name'] ?? 'Ethiopian Social Workers Professional Association';
$terms_conditions = trim($company['terms_conditions'] ?? '');
$words = preg_split('/\s+/', trim($company_name), -1, PREG_SPLIT_NO_EMPTY);
$company_short   = $words ? strtoupper(substr(implode('', array_map(function ($w) { return isset($w[0]) ? $w[0] : ''; }, $words)), 0, 8)) : 'ESWPA';

// Helper to read a simple string setting (same as admin)
$getSetting = static function (\mysqli $conn, string $key, string $default = '') {
    $stmt = $conn->prepare("SELECT setting_value FROM settings WHERE setting_key = ? LIMIT 1");
    if (!$stmt) {
        return $default;
    }
    $stmt->bind_param("s", $key);
    $stmt->execute();
    $result = $stmt->get_result();
    $value = $default;
    if ($row = $result->fetch_assoc()) {
        $value = (string) $row['setting_value'];
    }
    $stmt->close();
    return $value;
};

// Read QR enable/disable setting from `settings` table (defaults to enabled)
$qrEnabled = $getSetting($conn, 'id_card_enable_qr_scan', '1') !== '0';

// Read logo show/hide setting from `settings` table (defaults to show)
$logoEnabled = $getSetting($conn, 'id_card_show_logo', '1') !== '0';

// Read optional custom QR image path
$customQrImage = $getSetting($conn, 'id_card_custom_qr_image', '');

// Read default back text when no terms & conditions (same as admin)
$backDefaultText = $getSetting(
    $conn,
    'id_card_back_default_text',
    'Scan QR code to visit our website. Report lost or stolen cards immediately.'
);

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<?php include 'head.php'; ?>

<body class="member-id-card-page">
    <!-- Header -->
    <?php include 'member-header-v1.2.php'; ?>
    <!-- End Header -->

    <link href="assets/css/member-panel.css" rel="stylesheet">

    <?php
    // Reuse the admin print CSS for identical design
    $cssFile = __DIR__ . '/assets/css/id-card-print.css';
    ?>
    <link rel="stylesheet" href="assets/css/id-card-print.css?v=<?php echo file_exists($cssFile) ? filemtime($cssFile) : time(); ?>">

    <!-- Member-side layout tweaks so card is clean and responsive on mobile -->
    <style>
        /* Keep member view layout simple and consistent with admin print */
        .member-id-card-page .id-card-print-container {
            justify-content: center;
            align-items: flex-start;
            gap: 12px;
            padding: 24px 16px 40px;
            width: 100%;
            box-sizing: border-box;
        }
        
        /* Force ID card styles to override main website styles */
        .member-id-card-page .id-card-front,
        .member-id-card-page .id-card-back {
            background: #ffffff !important;
            border: none !important;
            padding: 0 !important;
            display: flex !important;
            flex-direction: column !important;
            border-radius: 4mm !important;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15) !important;
        }
        
        .member-id-card-page .id-card-front-header {
            background: linear-gradient(135deg, #1a5276 0%, #2874a6 45%, #3498db 100%) !important;
            min-height: 8mm !important;
            max-height: 12mm !important;
            display: flex !important;
            align-items: center !important;
            justify-content: space-between !important;
            padding: 0.6mm 1.5mm !important;
            flex-shrink: 0 !important;
            position: relative !important;
            overflow: hidden !important;
        }
        
        .member-id-card-page .id-card-front-footer {
            background: linear-gradient(135deg, #1a5276 0%, #3498db 100%) !important;
            min-height: 1.2mm !important;
            display: flex !important;
            align-items: center !important;
            justify-content: flex-end !important;
            padding: 0.1mm 1.5mm !important;
            flex-shrink: 0 !important;
        }
        
        .member-id-card-page .id-card-back-top-banner {
            background: linear-gradient(135deg, #1a5276 0%, #2874a6 45%, #3498db 100%) !important;
            min-height: 8mm !important;
            max-height: 12mm !important;
            display: flex !important;
            align-items: center !important;
            padding: 0.5mm 1.5mm !important;
            position: relative !important;
            overflow: hidden !important;
            flex-shrink: 0 !important;
        }
        
        .member-id-card-page .id-card-val {
            color: #1a5276 !important;
            font-weight: 700 !important;
        }
        
        .member-id-card-page .id-back-text::before {
            color: #1a5276 !important;
        }
    </style>

    <div class="mp-content-wrapper">
        <div class="mp-container">
            <div class="mp-content">
                <div class="mp-flex-between mp-mb-md">
                    <div>
                        <h2 class="mp-page-title"><i class="fas fa-id-card"></i> My ID Card</h2>
                        <p style="color: var(--mp-gray-600); margin: var(--mp-space-xs) 0 0 0; font-size: 0.875rem;">
                            This is your official <?php echo htmlspecialchars($company_name); ?> member ID card, as generated by the association.
                        </p>
                    </div>
                </div>

                <?php if (isset($_GET['error']) && $idCardGenerated !== 1): ?>
                    <div class="alert alert-warning alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($_GET['error']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php elseif (isset($_GET['error']) && $idCardGenerated === 1): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <strong>Success:</strong> ID card is now available. The previous error has been resolved.
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if ($idCardGenerated !== 1): ?>
                    <div class="alert alert-info" role="alert">
                        Your ID card has not been generated yet. Please contact the association/admin to generate your ID card.
                    </div>
                <?php else: ?>
                    <!-- Same card design as admin/member-id-card-print.php (front + back) -->
                    <div class="id-card-print-container" style="margin-top: 20px;">
                        <!-- Front of ID Card (match admin layout) -->
                        <div class="id-card-wrapper">
                            <div class="id-card-front">
                                <!-- Top header: logo + company name + address/phone (no email) -->
                                <div class="id-card-front-header">
                                    <div class="id-card-header-left">
                                        <div class="id-card-logo-img">
                                            <?php if ($logoEnabled && !empty($company['company_logo'])): ?>
                                                <img src="<?php echo htmlspecialchars($company['company_logo']); ?>"
                                                     alt=""
                                                     role="presentation"
                                                     class="id-logo-image"
                                                     onerror="this.style.display='none'; this.nextElementSibling&&(this.nextElementSibling.style.display='flex');">
                                                <div class="id-logo-placeholder" style="display:none"><i class="fas fa-id-card"></i></div>
                                            <?php else: ?>
                                                <div class="id-logo-placeholder"><i class="fas fa-id-card"></i></div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="id-card-logo">
                                            <!-- Show only full company name in header (no ESWPA text) -->
                                            <span class="id-card-tagline"><?php echo htmlspecialchars($company_name); ?></span>
                                        </div>
                                    </div>
                                    <div class="id-card-header-right">
                                        <div class="id-card-title"></div>
                                        <div class="id-card-contact">
                                            <?php
                                            $addr  = trim($company['address'] ?? '');
                                            $phone = trim($company['phone'] ?? '');
                                            $parts = array_filter([
                                                $addr,
                                                $phone ? $phone : ''
                                            ]);
                                            echo implode('  |  ', array_map('htmlspecialchars', $parts)) ?: '—';
                                            ?>
                                        </div>
                                    </div>
                                </div>

                                <!-- White body: photo left (centered), data right - name first -->
                                <div class="id-card-front-body">
                                    <div class="id-card-front-left">
                                        <div class="id-card-photo id-card-photo-square">
                                            <?php $photo_placeholder_id = 'ph-' . (int)$member['id']; ?>
                                            <?php if (!empty($member['photo'])): ?>
                                                <img src="<?php echo htmlspecialchars($member['photo']); ?>"
                                                     alt="Photo"
                                                     class="id-photo-img"
                                                     onerror="this.style.display='none'; var p=document.getElementById('<?php echo $photo_placeholder_id; ?>'); if(p) p.style.display='flex';">
                                                <div id="<?php echo $photo_placeholder_id; ?>" class="id-photo-placeholder" style="display:none"><span>Photo</span></div>
                                            <?php else: ?>
                                                <div class="id-photo-placeholder"><span>Photo</span></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="id-card-front-right">
                                        <div class="id-card-field id-card-field-name">
                                            <span class="id-card-label">Name</span>
                                            <span class="id-card-val"><?php echo htmlspecialchars(strtoupper($member['fullname'])); ?></span>
                                        </div>
                                        <div class="id-card-field">
                                            <span class="id-card-label">Member ID</span>
                                            <span class="id-card-val"><?php echo htmlspecialchars($member['membership_id']); ?></span>
                                        </div>
                                        <div class="id-card-field">
                                            <span class="id-card-label">Date issued</span>
                                            <span class="id-card-val"><?php echo date('d M Y', strtotime($member['created_at'])); ?></span>
                                        </div>
                                        <div class="id-card-field">
                                            <span class="id-card-label">Qualification</span>
                                            <span class="id-card-val"><?php echo htmlspecialchars($member['qualification']); ?></span>
                                        </div>
                                        <div class="id-card-field">
                                            <span class="id-card-label">Expires</span>
                                            <?php $expiryDate = strtotime('+1 year', strtotime($member['created_at'])); ?>
                                            <span class="id-card-val"><?php echo date('d M Y', $expiryDate); ?></span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Bottom blue footer with signature -->
                                <div class="id-card-front-footer">
                                    <?php if (!empty($company['company_signature'])): ?>
                                        <img src="<?php echo htmlspecialchars($company['company_signature']); ?>"
                                             alt="Signature"
                                             class="id-card-footer-signature-img">
                                    <?php else: ?>
                                        <span class="id-card-footer-text">This ID holder is a member of <?php echo htmlspecialchars($company_name); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                <!-- Back of ID Card -->
        <div class="id-card-wrapper">
            <div class="id-card-back">
                        <!-- Top Banner (same content style as front: logo + company + address/phone) -->
                <div class="id-card-back-top-banner">
                    <div class="id-card-logo-img">
                        <?php if ($logoEnabled && !empty($company['company_logo'])): ?>
                            <img src="<?php echo htmlspecialchars($company['company_logo']); ?>" alt="" role="presentation" class="id-logo-image" onerror="this.style.display='none'; this.nextElementSibling&&(this.nextElementSibling.style.display='flex');">
                            <div class="id-logo-placeholder" style="display:none"><i class="fas fa-id-card"></i></div>
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
                    <!-- Name, Qualification, Dates and ID removed as requested -->
                    
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
                    
                    <!-- Information Text (from template: terms_conditions and company name) -->
                    <p class="id-back-text">This card is the property of <?php echo htmlspecialchars($company_name); ?> and must be returned upon termination of membership.</p>
                    <?php if ($terms_conditions !== ''): ?>
                    <p class="id-back-text"><?php echo nl2br(htmlspecialchars($terms_conditions)); ?></p>
                    <?php else: ?>
                    <p class="id-back-text"><?php echo nl2br(htmlspecialchars($backDefaultText)); ?></p>
                    <?php endif; ?>
                    
                    <!-- QR Code (static image shared with member page) -->
                    <div class="id-qr-container">
                        <img src="assets/images/id-card-qr.jpg"
                             alt="QR Code"
                             style="width: 14mm; height: 14mm; object-fit: contain;">
                    </div>
                    
                </div>
            </div>
        </div>
                    </div>

                    <!-- Back to Dashboard -->
                    <div style="margin-top: 1.5rem;">
                        <a href="member-dashboard.php" class="mp-btn mp-btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Dashboard
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if ($idCardGenerated === 1 && isset($_GET['error'])): ?>
    <script>
        // Clean URL by removing error parameter when ID card is available
        if (window.history.replaceState) {
            const cleanUrl = window.location.pathname;
            window.history.replaceState({}, '', cleanUrl);
        }
    </script>
    <?php endif; ?>
</body>
</html>