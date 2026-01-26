<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: auth-login.php");
    exit();
}

include 'include/conn.php';
include 'header.php';

$success_message = '';
$error_message = '';

// Handle template settings update
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action']) && $_POST['action'] == 'update_company') {
        $company_name = trim($_POST['company_name'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $website = trim($_POST['website'] ?? '');
        $terms_conditions = trim($_POST['terms_conditions'] ?? '');
        
        // Check if company_info exists and get id
        $companyId = null;
        $checkStmt = $conn->prepare("SELECT id FROM company_info LIMIT 1");
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        if ($checkResult->num_rows > 0) {
            $companyId = (int) $checkResult->fetch_assoc()['id'];
        }
        $checkStmt->close();
        
        if ($companyId !== null) {
            // Update existing row
            $updateStmt = $conn->prepare("UPDATE company_info SET company_name = ?, address = ?, phone = ?, email = ?, website = ?, terms_conditions = ? WHERE id = ?");
            $updateStmt->bind_param("ssssssi", $company_name, $address, $phone, $email, $website, $terms_conditions, $companyId);
            if ($updateStmt->execute()) {
                $success_message = "Company information updated successfully";
            } else {
                $error_message = "Failed to update company information";
            }
            $updateStmt->close();
        } else {
            // Insert new
            $insertStmt = $conn->prepare("INSERT INTO company_info (company_name, address, phone, email, website, terms_conditions) VALUES (?, ?, ?, ?, ?, ?)");
            $insertStmt->bind_param("ssssss", $company_name, $address, $phone, $email, $website, $terms_conditions);
            if ($insertStmt->execute()) {
                $success_message = "Company information saved successfully";
            } else {
                $error_message = "Failed to save company information";
            }
            $insertStmt->close();
        }
    }
}

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
        'website' => 'www.eswpa.org',
        'terms_conditions' => ''
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
                                    <form method="POST">
                                        <input type="hidden" name="action" value="update_company">
                                        
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

</body>
</html>

