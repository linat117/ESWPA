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
        'terms_conditions' => '',
        'company_signature' => null
    ];
}
$company_name = $company['company_name'] ?? 'Ethiopian Social Workers Professional Association';
$terms_conditions = trim($company['terms_conditions'] ?? '');

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
            min-height: 100vh;
            background: #f5f5f5;
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

        /* Mobile: stack buttons full-width */
        @media screen and (max-width: 600px) {
            .print-actions {
                flex-direction: column;
                align-items: stretch;
            }

            .print-actions button,
            .print-actions a {
                justify-content: center;
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
                <!-- Top blue header: logo left, title + contact right -->
                <div class="id-card-front-header">
                    <div class="id-card-header-left">
                        <div class="id-card-logo-img">
                            <?php if (!empty($company['company_logo'])): ?>
                                <img src="../<?php echo htmlspecialchars($company['company_logo']); ?>" alt="" role="presentation" class="id-logo-image" onerror="this.style.display='none'; this.nextElementSibling&&(this.nextElementSibling.style.display='flex');">
                                <div class="id-logo-placeholder" style="display:none"><i class="fas fa-id-card"></i></div>
                            <?php else: ?>
                                <div class="id-logo-placeholder"><i class="fas fa-id-card"></i></div>
                            <?php endif; ?>
                        </div>
                        <div class="id-card-logo">
                            <span class="id-card-logo-text"></span>
                            <span class="id-card-tagline"><?php echo htmlspecialchars($company_name); ?></span>
                        </div>
                    </div>
                    <div class="id-card-header-right">
                        <div class="id-card-title"></div>
                        <div class="id-card-contact">
                            <?php
                            $addr = trim($company['address'] ?? '');
                            $phone = trim($company['phone'] ?? '');
                            $email = trim($company['email'] ?? '');
                            $parts = array_filter([
                                $addr,
                                $phone ? 'Ph: ' . $phone : '',
                                $email ? 'Email: ' . $email : ''
                            ]);
                            echo implode('  ', array_map('htmlspecialchars', $parts)) ?: '—';
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
                            <span class="id-card-label">NAME</span>
                            <span class="id-card-val"><?php echo htmlspecialchars(strtoupper($member['fullname'])); ?></span>
                        </div>
                        <div class="id-card-field">
                            <span class="id-card-label">MEMBER ID</span>
                            <span class="id-card-val"><?php echo htmlspecialchars($member['membership_id']); ?></span>
                        </div>
                        <div class="id-card-field">
                            <span class="id-card-label">DATE ISSUED</span>
                            <span class="id-card-val"><?php echo date('d M Y', strtotime($member['created_at'])); ?></span>
                        </div>
                        <div class="id-card-field">
                            <span class="id-card-label">QUALIFICATION</span>
                            <span class="id-card-val"><?php echo htmlspecialchars($member['qualification']); ?></span>
                        </div>
                        <div class="id-card-field">
                            <span class="id-card-label">EXPIRES</span>
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
                        <span class="id-card-footer-text">This ID holder is a member of ESWPA</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Back of ID Card -->
        <div class="id-card-wrapper">
            <div class="id-card-back">
                <!-- Top Banner (same as front) -->
                <div class="id-card-back-top-banner">
                    <div class="id-card-logo-img">
                        <?php if (!empty($company['company_logo'])): ?>
                            <img src="../<?php echo htmlspecialchars($company['company_logo']); ?>" alt="" role="presentation" class="id-logo-image" onerror="this.style.display='none'; this.nextElementSibling&&(this.nextElementSibling.style.display='flex');">
                            <div class="id-logo-placeholder" style="display:none"><i class="fas fa-id-card"></i></div>
                        <?php else: ?>
                            <div class="id-logo-placeholder"><i class="fas fa-id-card"></i></div>
                        <?php endif; ?>
                    </div>
                    <div class="id-card-back-logo-top">
                        <span class="id-card-back-logo-text-top">ESWPA</span>
                        <span class="id-card-back-tagline-top"><?php echo htmlspecialchars($company_name); ?></span>
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
                    <p class="id-back-text">For verification, scan the QR code or visit our website. Report lost or stolen cards immediately.</p>
                    <?php endif; ?>
                    
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
                    
                </div>
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
