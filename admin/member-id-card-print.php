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

// Get verification URL: fixed website for all cards
$verificationUrl = 'https://ethiosocialworkers.igebeya.com';

// Get company info
$companyQuery = "SELECT * FROM company_info LIMIT 1";
$companyResult = $conn->query($companyQuery);
$company = ($companyResult && $companyResult->num_rows > 0) ? $companyResult->fetch_assoc() : null;
if (!$company) {
    $company = [
        'company_name' => 'Ethiopian Social Workers Professional Association',
        'address' => 'Addis Ababa, Ethiopia',
        'phone' => '+251-XXX-XXX-XXXX',
        'email' => 'info@eswpa.org',
        'website' => 'www.eswpa.org',
        'terms_conditions' => '',
        'company_signature' => null
    ];
}
$company_name = $company['company_name'] ?? 'Ethiopian Social Workers Professional Association';
$terms_conditions = trim($company['terms_conditions'] ?? '');

// (company website field no longer controls the QR destination)
$words = preg_split('/\s+/', trim($company_name), -1, PREG_SPLIT_NO_EMPTY);
$company_short = $words ? strtoupper(substr(implode('', array_map(function ($w) { return isset($w[0]) ? $w[0] : ''; }, $words)), 0, 8)) : 'ESWPA';

// Helper to read a simple string setting
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

// Read default back text when no terms & conditions
$backDefaultText = $getSetting(
    $conn,
    'id_card_back_default_text',
    'Scan the QR code to visit our website. Report lost or stolen cards immediately.'
);

$conn->close();
ob_end_flush();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>Print ID Card - <?php echo htmlspecialchars($member['fullname']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <?php $cssFile = __DIR__ . '/../assets/css/id-card-print.css'; ?>
    <link rel="stylesheet" href="../assets/css/id-card-print.css?v=<?php echo file_exists($cssFile) ? filemtime($cssFile) : time(); ?>">
    <style>
        /* Keep layout/print logic in id-card-print.css; only style the page shell here */
        body {
            margin: 0;
            padding: 0;
            overflow-x: hidden;
            /* Use a subtle neutral background so extra space around the cards is less visible,
               while letting the content define the height. */
            min-height: auto;
            background: #f5f7fa;
        }

        /* Print Controls - Base Styles */
        .print-controls {
            background: #ffffff;
            padding: 12px 16px;
            margin-bottom: 12px;
            border-radius: 10px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.06);
            border: 1px solid rgba(0, 0, 0, 0.04);
        }
        
        .print-controls-content {
            max-width: 800px;
            margin: 0 auto;
            text-align: center;
            color: #333;
        }
        
        .print-controls-content h2 {
            margin: 0;
            font-size: 1.1rem;
            font-weight: 600;
        }
                
        .print-actions {
            display: flex;
            justify-content: center;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        .print-actions button,
        .print-actions a {
            padding: 6px 12px;
            border: none;
            border-radius: 8px;
            font-size: 0.8rem;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .btn-print {
            background: #28a745;
            color: white;
        }
        
        .btn-close {
            background: #dc3545;
            color: white;
        }
        
        .btn-back {
            background: white;
            color: #333;
        }
        
        .print-actions button:hover,
        .print-actions a:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }

        /* Mobile: stack buttons full-width and minimize space above cards */
        @media screen and (max-width: 600px) {
            .print-actions {
                flex-direction: column;
                align-items: stretch;
            }

            .print-actions button,
            .print-actions a {
                justify-content: center;
            }

            .print-controls {
                margin-bottom: 4px;
                padding: 8px 8px;
            }
        }

        /* Hide controls when printing */
        @media print {
            .print-controls {
                display: none !important;
            }
        }
    </style>
</head>
<body>
    <!-- Print Controls (hidden when printing) -->
    <div class="print-controls">
        <div class="print-controls-content">
            <h2><i class="fas fa-print"></i> Print Preview</h2>
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
        <!-- Front of ID Card - identity card style (blue header, white body, blue footer) -->
        <div class="id-card-wrapper">
            <div class="id-card-front">
                <!-- Top header: logo + company name + address/phone (no email) -->
                <div class="id-card-front-header">
                    <div class="id-card-header-left">
                        <div class="id-card-logo-img">
                            <?php if ($logoEnabled && !empty($company['company_logo'])): ?>
                                <img src="../<?php echo htmlspecialchars($company['company_logo']); ?>" alt="" role="presentation" class="id-logo-image" onerror="this.style.display='none'; this.nextElementSibling&&(this.nextElementSibling.style.display='flex');">
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
                                <img src="../<?php echo htmlspecialchars($member['photo']); ?>" alt="Photo" class="id-photo-img" onerror="this.style.display='none'; var p=document.getElementById('<?php echo $photo_placeholder_id; ?>'); if(p) p.style.display='flex';">
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
                        <img src="../<?php echo htmlspecialchars($company['company_signature']); ?>" alt="Signature" class="id-card-footer-signature-img">
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
                            <img src="../<?php echo htmlspecialchars($company['company_logo']); ?>" alt="" role="presentation" class="id-logo-image" onerror="this.style.display='none'; this.nextElementSibling&&(this.nextElementSibling.style.display='flex');">
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
                            <img src="../<?php echo htmlspecialchars($company['company_signature']); ?>" 
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
                        <img src="../assets/images/id-card-qr.jpg"
                             alt="QR Code"
                             style="width: 14mm; height: 14mm; object-fit: contain;">
                    </div>
                    
                </div>
            </div>
        </div>
    </div>

    <!-- Static QR image is used for the back of the card; no JS generation needed -->
</body>
</html>
