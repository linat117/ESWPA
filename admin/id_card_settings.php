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
function get_setting(mysqli $conn, string $key, string $default = ''): string {
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
function save_setting(mysqli $conn, string $key, string $value): void {
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

// Default values (used if no rows exist yet)
$auto_generate          = get_setting($conn, 'id_card_auto_generate', '0');
$code_format            = get_setting($conn, 'id_card_code_format', 'hex32');
$expiry_warning_days    = get_setting($conn, 'id_card_expiry_warning_days', '30');
$require_photo          = get_setting($conn, 'id_card_require_photo', '1');
$default_template       = get_setting($conn, 'id_card_default_template', 'standard');
$enable_qr_scan_setting = get_setting($conn, 'id_card_enable_qr_scan', '1');
$track_scans_setting    = get_setting($conn, 'id_card_track_scans', '1');

// Handle settings update
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action']) && $_POST['action'] === 'update_settings') {
        // Sanitize inputs
        $auto_generate       = isset($_POST['auto_generate']) ? ($_POST['auto_generate'] === '1' ? '1' : '0') : '0';
        $code_format         = $_POST['code_format'] ?? 'hex32';
        $expiry_warning_days = isset($_POST['expiry_warning_days']) ? (string) max(0, min(365, (int) $_POST['expiry_warning_days'])) : '30';
        $require_photo       = isset($_POST['require_photo']) ? ($_POST['require_photo'] === '1' ? '1' : '0') : '1';
        $default_template    = $_POST['default_template'] ?? 'standard';
        $enable_qr_scan_setting = isset($_POST['enable_qr_scan']) && $_POST['enable_qr_scan'] === '1' ? '1' : '0';
        $track_scans_setting    = isset($_POST['track_scans']) && $_POST['track_scans'] === '1' ? '1' : '0';

        // Persist to settings table
        save_setting($conn, 'id_card_auto_generate', $auto_generate);
        save_setting($conn, 'id_card_code_format', $code_format);
        save_setting($conn, 'id_card_expiry_warning_days', $expiry_warning_days);
        save_setting($conn, 'id_card_require_photo', $require_photo);
        save_setting($conn, 'id_card_default_template', $default_template);
        save_setting($conn, 'id_card_enable_qr_scan', $enable_qr_scan_setting);
        save_setting($conn, 'id_card_track_scans', $track_scans_setting);

        $success_message = "ID card settings saved successfully.";
    }
}

// Get current statistics
$stats = [];
$stats['total_codes']    = $conn->query("SELECT COUNT(*) as total FROM id_card_verification")->fetch_assoc()['total'];
$stats['generated_cards'] = $conn->query("SELECT COUNT(*) as total FROM registrations WHERE id_card_generated = 1 AND approval_status = 'approved'")->fetch_assoc()['total'];
$stats['pending_cards']   = $conn->query("SELECT COUNT(*) as total FROM registrations WHERE (id_card_generated = 0 OR id_card_generated IS NULL) AND approval_status = 'approved' AND (expiry_date IS NULL OR expiry_date >= CURDATE())")->fetch_assoc()['total'];
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
                                <h4 class="page-title">ID Card Settings</h4>
                                <div>
                                    <a href="digital_id_dashboard.php" class="btn btn-secondary me-2">
                                        <i class="ri-dashboard-line"></i> Dashboard
                                    </a>
                                    <a href="id_card_templates.php" class="btn btn-secondary">
                                        <i class="ri-file-edit-line"></i> Templates
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
                                    <h4 class="header-title">ID Card System Settings</h4>
                                </div>
                                <div class="card-body">
                                    <form method="POST">
                                        <input type="hidden" name="action" value="update_settings">
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Auto-Generate ID Cards</label>
                                            <select name="auto_generate" class="form-select">
                                                <option value="0" <?php echo $auto_generate === '0' ? 'selected' : ''; ?>>Manual - Admin must generate</option>
                                                <option value="1" <?php echo $auto_generate === '1' ? 'selected' : ''; ?>>Automatic - Generate on approval</option>
                                            </select>
                                            <small class="text-muted">Automatically generate ID cards when a member is approved</small>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Verification Code Format</label>
                                            <select name="code_format" class="form-select">
                                                <option value="hex32" <?php echo $code_format === 'hex32' ? 'selected' : ''; ?>>32-character hexadecimal (default)</option>
                                                <option value="alphanumeric32" <?php echo $code_format === 'alphanumeric32' ? 'selected' : ''; ?>>32-character alphanumeric</option>
                                                <option value="uuid" <?php echo $code_format === 'uuid' ? 'selected' : ''; ?>>UUID format</option>
                                            </select>
                                            <small class="text-muted">Format for QR code verification codes</small>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">ID Card Expiry Warning Days</label>
                                            <input type="number" 
                                                   name="expiry_warning_days" 
                                                   class="form-control" 
                                                   value="<?php echo htmlspecialchars($expiry_warning_days); ?>" 
                                                   min="0" 
                                                   max="365">
                                            <small class="text-muted">Number of days before expiry to show warning</small>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Require Photo for ID Card</label>
                                            <select name="require_photo" class="form-select">
                                                <option value="1" <?php echo $require_photo === '1' ? 'selected' : ''; ?>>Yes - Photo required</option>
                                                <option value="0" <?php echo $require_photo === '0' ? 'selected' : ''; ?>>No - Photo optional</option>
                                            </select>
                                            <small class="text-muted">Whether a member photo is required to generate ID card</small>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Default ID Card Template</label>
                                            <select name="default_template" class="form-select">
                                                <option value="standard" <?php echo $default_template === 'standard' ? 'selected' : ''; ?>>Standard Template</option>
                                                <option value="premium" <?php echo $default_template === 'premium' ? 'selected' : ''; ?>>Premium Template</option>
                                                <option value="custom" <?php echo $default_template === 'custom' ? 'selected' : ''; ?>>Custom Template</option>
                                            </select>
                                            <small class="text-muted">Default template to use for new ID cards</small>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label d-block">QR Code on ID Card</label>
                                            <div class="form-check">
                                                <input type="checkbox" 
                                                       class="form-check-input" 
                                                       name="enable_qr_scan" 
                                                       id="enable_qr_scan" 
                                                       value="1" 
                                                       <?php echo $enable_qr_scan_setting === '1' ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="enable_qr_scan">
                                                    Show QR code on back side of ID card
                                                </label>
                                            </div>
                                            <small class="text-muted">If enabled, a QR code for online verification is printed on the back of the card.</small>
                                        </div>

                                        <div class="mb-3">
                                            <div class="form-check">
                                                <input type="checkbox" 
                                                       class="form-check-input" 
                                                       name="track_scans" 
                                                       id="track_scans" 
                                                       value="1" 
                                                       <?php echo $track_scans_setting === '1' ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="track_scans">
                                                    Track Verification Scans
                                                </label>
                                            </div>
                                            <small class="text-muted">Record IP address and timestamp for each verification scan</small>
                                        </div>

                                        <button type="submit" class="btn btn-primary">
                                            <i class="ri-save-line"></i> Save Settings
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-4">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">System Statistics</h4>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <h6 class="text-muted small">Total Verification Codes</h6>
                                        <h4><?php echo number_format($stats['total_codes']); ?></h4>
                                    </div>
                                    <div class="mb-3">
                                        <h6 class="text-muted small">Generated ID Cards</h6>
                                        <h4><?php echo number_format($stats['generated_cards']); ?></h4>
                                    </div>
                                    <div class="mb-0">
                                        <h6 class="text-muted small">Pending Generation</h6>
                                        <h4><?php echo number_format($stats['pending_cards']); ?></h4>
                                    </div>
                                </div>
                            </div>

                            <div class="card mt-3">
                                <div class="card-header">
                                    <h4 class="header-title">Quick Actions</h4>
                                </div>
                                <div class="card-body">
                                    <div class="d-grid gap-2">
                                        <a href="id_card_templates.php" class="btn btn-primary">
                                            <i class="ri-file-edit-line"></i> Manage Templates
                                        </a>
                                        <a href="id_card_generate.php" class="btn btn-secondary">
                                            <i class="ri-add-circle-line"></i> Generate ID Card
                                        </a>
                                        <a href="id_card_verify.php" class="btn btn-info">
                                            <i class="ri-qr-scan-line"></i> Verify ID Card
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <div class="card mt-3">
                                <div class="card-header">
                                    <h4 class="header-title">Information</h4>
                                </div>
                                <div class="card-body">
                                    <p class="text-muted small">
                                        <i class="ri-information-line"></i> Settings are saved to the database and apply to all new ID card operations.
                                    </p>
                                    <p class="text-muted small">
                                        <i class="ri-information-line"></i> Changes to settings do not affect existing ID cards.
                                    </p>
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

</body>
</html>

