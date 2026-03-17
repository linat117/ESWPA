<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: auth-login.php");
    exit();
}

include 'include/conn.php';
include 'header.php';

$success_message = '';
$error_message   = '';

/**
 * Helper to get a setting from the `settings` table.
 */
function idcard_get_setting(mysqli $conn, string $key, string $default = ''): string {
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
}

/**
 * Helper to save a setting into the `settings` table.
 */
function idcard_save_setting(mysqli $conn, string $key, string $value): void {
    $stmt = $conn->prepare("
        INSERT INTO settings (setting_key, setting_value, category)
        VALUES (?, ?, 'id_card')
        ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
    ");
    if (!$stmt) {
        return;
    }
    $stmt->bind_param("ss", $key, $value);
    $stmt->execute();
    $stmt->close();
}

// Load current ID card visual settings
$show_logo_setting   = idcard_get_setting($conn, 'id_card_show_logo', '1');
$qr_setting          = idcard_get_setting($conn, 'id_card_enable_qr_scan', '1');
$custom_qr_image     = idcard_get_setting($conn, 'id_card_custom_qr_image', '');
$back_default_text   = idcard_get_setting(
    $conn,
    'id_card_back_default_text',
    'For verification, scan the QR code or visit our website. Report lost or stolen cards immediately.'
);

// Handle template settings update
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'] ?? '';

    // 1) Company info + logo + show/hide logo
    if ($action === 'update_company') {
        $company_name    = trim($_POST['company_name'] ?? '');
        $address         = trim($_POST['address'] ?? '');
        $phone           = trim($_POST['phone'] ?? '');
        $email           = trim($_POST['email'] ?? '');
        $website         = trim($_POST['website'] ?? '');
        $terms_conditions = trim($_POST['terms_conditions'] ?? '');
        
        // Check if company_info exists and get id and current logo
        $companyId   = null;
        $currentLogo = null;
        $checkStmt   = $conn->prepare("SELECT id, company_logo FROM company_info LIMIT 1");
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        if ($checkResult->num_rows > 0) {
            $row       = $checkResult->fetch_assoc();
            $companyId = (int) $row['id'];
            $currentLogo = $row['company_logo'];
        }
        $checkStmt->close();
        
        // Handle logo upload
        $logoPath = $currentLogo;
        if (isset($_FILES['company_logo']) && $_FILES['company_logo']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '../uploads/company/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $fileType     = $_FILES['company_logo']['type'];
            
            if (in_array($fileType, $allowedTypes)) {
                $extension  = pathinfo($_FILES['company_logo']['name'], PATHINFO_EXTENSION);
                $fileName   = 'company_logo_' . time() . '.' . $extension;
                $targetPath = $uploadDir . $fileName;
                
                if (move_uploaded_file($_FILES['company_logo']['tmp_name'], $targetPath)) {
                    // Delete old logo if exists
                    if ($currentLogo && file_exists('../' . $currentLogo)) {
                        unlink('../' . $currentLogo);
                    }
                    $logoPath = 'uploads/company/' . $fileName;
                } else {
                    $error_message = "Failed to upload logo file";
                }
            } else {
                $error_message = "Invalid file type. Please upload JPG, PNG, GIF or WebP";
            }
        }
        
        // Handle logo removal
        if (isset($_POST['remove_logo']) && $_POST['remove_logo'] == '1') {
            if ($currentLogo && file_exists('../' . $currentLogo)) {
                unlink('../' . $currentLogo);
            }
            $logoPath = null;
        }
        
        // Handle "show logo" toggle (from checkbox)
        $show_logo_setting = isset($_POST['show_logo']) && $_POST['show_logo'] === '1' ? '1' : '0';
        idcard_save_setting($conn, 'id_card_show_logo', $show_logo_setting);

        if (empty($error_message)) {
            if ($companyId !== null) {
                // Update existing row
                $updateStmt = $conn->prepare("UPDATE company_info SET company_name = ?, address = ?, phone = ?, email = ?, website = ?, terms_conditions = ?, company_logo = ? WHERE id = ?");
                $updateStmt->bind_param("sssssssi", $company_name, $address, $phone, $email, $website, $terms_conditions, $logoPath, $companyId);
                if ($updateStmt->execute()) {
                    $success_message = "Company information updated successfully";
                } else {
                    $error_message = "Failed to update company information";
                }
                $updateStmt->close();
            } else {
                // Insert new
                $insertStmt = $conn->prepare("INSERT INTO company_info (company_name, address, phone, email, website, terms_conditions, company_logo) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $insertStmt->bind_param("sssssss", $company_name, $address, $phone, $email, $website, $terms_conditions, $logoPath);
                if ($insertStmt->execute()) {
                    $success_message = "Company information saved successfully";
                } else {
                    $error_message = "Failed to save company information";
                }
                $insertStmt->close();
            }
        }

    // 2) QR toggle, custom QR image, and default back text
    } elseif ($action === 'update_qr_text') {
        // QR enable/disable
        $qr_setting = isset($_POST['enable_qr_scan']) && $_POST['enable_qr_scan'] === '1' ? '1' : '0';
        idcard_save_setting($conn, 'id_card_enable_qr_scan', $qr_setting);

        // Custom QR image upload/removal
        $currentQr = $custom_qr_image;
        if (isset($_FILES['qr_image']) && $_FILES['qr_image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '../uploads/company/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $fileType     = $_FILES['qr_image']['type'];
            if (in_array($fileType, $allowedTypes)) {
                $extension  = pathinfo($_FILES['qr_image']['name'], PATHINFO_EXTENSION);
                $fileName   = 'id_card_qr_' . time() . '.' . $extension;
                $targetPath = $uploadDir . $fileName;
                if (move_uploaded_file($_FILES['qr_image']['tmp_name'], $targetPath)) {
                    if ($currentQr && file_exists('../' . $currentQr)) {
                        unlink('../' . $currentQr);
                    }
                    $custom_qr_image = 'uploads/company/' . $fileName;
                } else {
                    $error_message = $error_message ?: "Failed to upload QR image file";
                }
            } else {
                $error_message = $error_message ?: "Invalid QR image type. Please upload JPG, PNG, GIF or WebP";
            }
        }
        if (isset($_POST['remove_qr_image']) && $_POST['remove_qr_image'] === '1') {
            if ($custom_qr_image && file_exists('../' . $custom_qr_image)) {
                unlink('../' . $custom_qr_image);
            }
            $custom_qr_image = '';
        }
        idcard_save_setting($conn, 'id_card_custom_qr_image', $custom_qr_image);

        // Default back text when no terms & conditions
        $back_default_text = trim($_POST['back_default_text'] ?? '');
        if ($back_default_text === '') {
            $back_default_text = 'For verification, scan the QR code or visit our website. Report lost or stolen cards immediately.';
        }
        idcard_save_setting($conn, 'id_card_back_default_text', $back_default_text);

        if (empty($error_message)) {
            $success_message = "QR and back text settings saved successfully.";
        }
    }
}

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
        'company_logo' => '',
        'company_signature' => ''
    ];
}
?>

<!DOCTYPE html>
<html lang="en">

<body>
    <div class="wrapper">
        <?php include 'sidebar.php'; ?>

        <div class="content-page">
            <div class="content">
                <div class="container-fluid">
                    <!-- Page Title -->
                    <div class="row">
                        <div class="col-12">
                            <div class="page-title-box d-flex justify-content-between align-items-center">
                                <h4 class="page-title">ID Card Templates & Settings</h4>
                                <div>
                                    <a href="digital_id_dashboard.php" class="btn btn-secondary me-2">
                                        <i class="ri-dashboard-line"></i> Dashboard
                                    </a>
                                    <a href="id_cards_list.php" class="btn btn-secondary">
                                        <i class="ri-list-check"></i> All ID Cards
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php
                    if ($success_message) {
                        echo '<div class="alert alert-success alert-dismissible fade show" role="alert">';
                        echo htmlspecialchars($success_message);
                        echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
                        echo '</div>';
                    }
                    if ($error_message) {
                        echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">';
                        echo htmlspecialchars($error_message);
                        echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
                        echo '</div>';
                    }
                    ?>

                    <div class="row">
                        <div class="col-lg-8">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Company/Organization Information</h4>
                                    <p class="text-muted mb-0">This information appears on ID cards</p>
                                </div>
                                <div class="card-body">
                                    <form method="POST" enctype="multipart/form-data">
                                        <input type="hidden" name="action" value="update_company">
                                        
                                        <!-- Logo Upload Section -->
                                        <div class="mb-4">
                                            <label class="form-label">Organization Logo</label>
                                            <div class="d-flex align-items-start gap-3">
                                                <div class="logo-preview-container">
                                                    <?php if (!empty($company['company_logo'])): ?>
                                                        <img src="../<?php echo htmlspecialchars($company['company_logo']); ?>" 
                                                             alt="Company Logo" 
                                                             class="logo-preview img-thumbnail"
                                                             style="max-width: 120px; max-height: 120px; object-fit: contain;">
                                                    <?php else: ?>
                                                        <div class="logo-placeholder bg-light border rounded d-flex align-items-center justify-content-center" 
                                                             style="width: 120px; height: 120px;">
                                                            <i class="ri-image-add-line fs-1 text-muted"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <input type="file" 
                                                           name="company_logo" 
                                                           class="form-control mb-2" 
                                                           accept="image/jpeg,image/png,image/gif,image/webp"
                                                           id="logoInput">
                                                    <small class="text-muted d-block mb-2">Recommended: Square image, 200x200px or larger. JPG, PNG, GIF, WebP accepted.</small>
                                                    <?php if (!empty($company['company_logo'])): ?>
                                                        <div class="form-check">
                                                            <input type="checkbox" class="form-check-input" name="remove_logo" value="1" id="removeLogo">
                                                            <label class="form-check-label text-danger" for="removeLogo">
                                                                <i class="ri-delete-bin-line"></i> Remove current logo
                                                            </label>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label d-block">Show Logo on ID Card</label>
                                            <div class="form-check">
                                                <input type="checkbox"
                                                       class="form-check-input"
                                                       name="show_logo"
                                                       id="show_logo"
                                                       value="1"
                                                       <?php echo $show_logo_setting === '1' ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="show_logo">
                                                    Display this logo on the printed ID card
                                                </label>
                                            </div>
                                            <small class="text-muted">Uncheck if you want to temporarily hide the logo while keeping it uploaded.</small>
                                        </div>

                                        <hr class="mb-4">
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Company/Organization Name</label>
                                            <input type="text" 
                                                   name="company_name" 
                                                   class="form-control" 
                                                   value="<?php echo htmlspecialchars($company['company_name']); ?>" 
                                                   required>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Address</label>
                                            <textarea name="address" 
                                                      class="form-control" 
                                                      rows="2" 
                                                      required><?php echo htmlspecialchars($company['address']); ?></textarea>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Phone</label>
                                                    <input type="text" 
                                                           name="phone" 
                                                           class="form-control" 
                                                           value="<?php echo htmlspecialchars($company['phone']); ?>" 
                                                           required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Email</label>
                                                    <input type="email" 
                                                           name="email" 
                                                           class="form-control" 
                                                           value="<?php echo htmlspecialchars($company['email']); ?>" 
                                                           required>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Website</label>
                                            <input type="text" 
                                                   name="website" 
                                                   class="form-control" 
                                                   value="<?php echo htmlspecialchars($company['website']); ?>" 
                                                   placeholder="www.example.org">
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Terms & Conditions (for ID card back)</label>
                                            <textarea name="terms_conditions" 
                                                      class="form-control" 
                                                      rows="4" 
                                                      placeholder="Enter terms and conditions that appear on the back of ID cards..."><?php echo htmlspecialchars($company['terms_conditions']); ?></textarea>
                                            <small class="text-muted">This text will appear on the back side of ID cards</small>
                                        </div>

                                        <button type="submit" class="btn btn-primary">
                                            <i class="ri-save-line"></i> Save Company Information
                                        </button>
                                    </form>
                                </div>
                            </div>

                            <div class="card mt-3">
                                <div class="card-header">
                                    <h4 class="header-title">ID Card Design Preview</h4>
                                </div>
                                <div class="card-body">
                                    <div class="alert alert-info">
                                        <h6><i class="ri-information-line"></i> ID Card Design Elements</h6>
                                        <p class="mb-2"><strong>Front Side:</strong></p>
                                        <ul class="mb-0">
                                            <li>Member photo (circular/square)</li>
                                            <li>Member full name</li>
                                            <li>Membership ID number</li>
                                            <li>Qualification</li>
                                            <li>Date of Birth</li>
                                            <li>Email address</li>
                                            <li>QR code for verification</li>
                                            <li>Organization logo</li>
                                        </ul>
                                        <p class="mb-2 mt-3"><strong>Back Side:</strong></p>
                                        <ul class="mb-0">
                                            <li>Organization signature</li>
                                            <li>Company information (from above)</li>
                                            <li>Member join date</li>
                                            <li>Membership expiry date</li>
                                            <li>Terms and conditions</li>
                                        </ul>
                                    </div>
                                    <p class="text-muted small">
                                        <i class="ri-alert-line"></i> To customize the visual design (colors, fonts, layout), you may need to modify the CSS files in <code>assets/css/id-card-print.css</code>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-4">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">QR Code & Background Text</h4>
                                </div>
                                <div class="card-body">
                                    <form method="POST" enctype="multipart/form-data">
                                        <input type="hidden" name="action" value="update_qr_text">
                                        
                                        <div class="mb-3">
                                            <label class="form-label d-block">QR Code on ID Card</label>
                                            <div class="form-check mb-2">
                                                <input type="checkbox"
                                                       class="form-check-input"
                                                       name="enable_qr_scan"
                                                       id="tpl_enable_qr_scan"
                                                       value="1"
                                                       <?php echo $qr_setting === '1' ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="tpl_enable_qr_scan">
                                                    Show QR code on back side of ID card
                                                </label>
                                            </div>
                                            <small class="text-muted d-block mb-2">
                                                When enabled, the system will generate a QR code (or use the custom image below) for online verification.
                                            </small>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Custom QR Image (optional)</label>
                                            <div class="d-flex align-items-start gap-3">
                                                <div class="logo-preview-container">
                                                    <?php if (!empty($custom_qr_image)): ?>
                                                        <img src="../<?php echo htmlspecialchars($custom_qr_image); ?>"
                                                             alt="QR Image"
                                                             class="img-thumbnail"
                                                             style="max-width: 100px; max-height: 100px; object-fit: contain;">
                                                    <?php else: ?>
                                                        <div class="bg-light border rounded d-flex align-items-center justify-content-center"
                                                             style="width: 100px; height: 100px;">
                                                            <i class="ri-qr-code-line fs-2 text-muted"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <input type="file"
                                                           name="qr_image"
                                                           class="form-control mb-2"
                                                           accept="image/jpeg,image/png,image/gif,image/webp">
                                                    <small class="text-muted d-block mb-2">
                                                        If set, this image will be printed instead of the generated QR square.
                                                    </small>
                                                    <?php if (!empty($custom_qr_image)): ?>
                                                        <div class="form-check">
                                                            <input type="checkbox"
                                                                   class="form-check-input"
                                                                   name="remove_qr_image"
                                                                   id="remove_qr_image"
                                                                   value="1">
                                                            <label class="form-check-label text-danger" for="remove_qr_image">
                                                                <i class="ri-delete-bin-line"></i> Remove current QR image
                                                            </label>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Default Back Text (when no Terms & Conditions)</label>
                                            <textarea name="back_default_text"
                                                      class="form-control"
                                                      rows="3"
                                                      placeholder="Enter the default guidance text shown on the back when there are no Terms &amp; Conditions set..."><?php echo htmlspecialchars($back_default_text); ?></textarea>
                                            <small class="text-muted">
                                                This text appears on the back of the card if the Terms &amp; Conditions field is empty.
                                            </small>
                                        </div>

                                        <button type="submit" class="btn btn-sm btn-primary">
                                            <i class="ri-save-line"></i> Save QR & Text
                                        </button>
                                    </form>
                                </div>
                            </div>

                            <div class="card mt-3">
                                <div class="card-header">
                                    <h4 class="header-title">Quick Actions</h4>
                                </div>
                                <div class="card-body">
                                    <div class="d-grid gap-2">
                                        <a href="id_card_generate.php" class="btn btn-primary">
                                            <i class="ri-add-circle-line"></i> Generate ID Card
                                        </a>
                                        <a href="id_cards_list.php" class="btn btn-secondary">
                                            <i class="ri-list-check"></i> View All ID Cards
                                        </a>
                                        <a href="id_card_verify.php" class="btn btn-info">
                                            <i class="ri-qr-scan-line"></i> Verify ID Card
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <div class="card mt-3">
                                <div class="card-header">
                                    <h4 class="header-title">Template Files</h4>
                                </div>
                                <div class="card-body">
                                    <p class="text-muted small mb-2">ID card template files:</p>
                                    <ul class="list-unstyled small">
                                        <li><code>member-id-card-print.php</code></li>
                                        <li><code>include/generate-id-card-pdf.php</code></li>
                                        <li><code>assets/css/id-card-print.css</code></li>
                                    </ul>
                                    <p class="text-muted small">
                                        <i class="ri-information-line"></i> Modify these files to change the ID card design.
                                    </p>
                                </div>
                            </div>

                            <div class="card mt-3">
                                <div class="card-header">
                                    <h4 class="header-title">Current Settings</h4>
                                </div>
                                <div class="card-body">
                                    <?php if (!empty($company['company_logo'])): ?>
                                    <div class="text-center mb-3">
                                        <img src="../<?php echo htmlspecialchars($company['company_logo']); ?>" 
                                             alt="Logo" 
                                             class="img-thumbnail"
                                             style="max-width: 80px; max-height: 80px; object-fit: contain;">
                                        <p class="small text-success mt-1 mb-0"><i class="ri-checkbox-circle-fill"></i> Logo set</p>
                                    </div>
                                    <?php else: ?>
                                    <div class="text-center mb-3">
                                        <div class="bg-light border rounded d-inline-flex align-items-center justify-content-center" 
                                             style="width: 80px; height: 80px;">
                                            <i class="ri-image-line fs-2 text-muted"></i>
                                        </div>
                                        <p class="small text-warning mt-1 mb-0"><i class="ri-alert-line"></i> No logo</p>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <p class="mb-2"><strong>Organization:</strong></p>
                                    <p class="small text-muted mb-3"><?php echo htmlspecialchars($company['company_name']); ?></p>
                                    
                                    <p class="mb-2"><strong>Contact:</strong></p>
                                    <p class="small text-muted mb-1"><?php echo htmlspecialchars($company['phone']); ?></p>
                                    <p class="small text-muted mb-3"><?php echo htmlspecialchars($company['email']); ?></p>
                                    
                                    <p class="mb-2"><strong>Location:</strong></p>
                                    <p class="small text-muted"><?php echo htmlspecialchars($company['address']); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <?php include 'footer.php'; ?>
        </div>
    </div>

    <!-- Vendor js -->
    <script src="assets/js/vendor.min.js"></script>
    <script src="assets/js/app.min.js"></script>
    
    <script>
        // Logo preview functionality
        document.getElementById('logoInput').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const container = document.querySelector('.logo-preview-container');
                    container.innerHTML = '<img src="' + e.target.result + '" alt="Logo Preview" class="logo-preview img-thumbnail" style="max-width: 120px; max-height: 120px; object-fit: contain;">';
                };
                reader.readAsDataURL(file);
            }
        });
        
        // Uncheck remove logo when new file is selected
        document.getElementById('logoInput').addEventListener('change', function() {
            const removeCheckbox = document.getElementById('removeLogo');
            if (removeCheckbox) {
                removeCheckbox.checked = false;
            }
        });
    </script>

</body>
</html>

