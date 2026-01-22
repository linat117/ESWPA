<?php
include 'include/config.php';

// Get verification code from URL
$code = $_GET['code'] ?? '';

if (empty($code)) {
    die('Invalid verification code');
}

// Verify the code
$query = "SELECT iv.*, r.fullname, r.membership_id, r.email, r.qualification, r.sex, r.photo, 
                 r.created_at, r.expiry_date, r.status, r.approval_status
          FROM id_card_verification iv
          INNER JOIN registrations r ON iv.membership_id = r.membership_id
          WHERE iv.verification_code = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $code);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die('Invalid or expired verification code');
}

$member = $result->fetch_assoc();

// Update scan information
$updateQuery = "UPDATE id_card_verification SET 
                scanned_at = NOW(), 
                ip_address = ?,
                user_agent = ?
                WHERE verification_code = ?";
$updateStmt = $conn->prepare($updateQuery);
$ip = $_SERVER['REMOTE_ADDR'] ?? '';
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
$updateStmt->bind_param("sss", $ip, $userAgent, $code);
$updateStmt->execute();
$updateStmt->close();

$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<?php include 'head.php'; ?>

<body>
    <!-- Header -->
    <?php include 'header-v1.2.php'; ?>
    <!-- End Header -->

    <!-- Start Breadcrumb -->
    <div class="breadcrumb-area text-center shadow dark bg-fixed padding-xl text-light" style="background-image: url(assets/img/bgregister.png);">
        <div class="container">
            <div class="breadcrumb-items">
                <div class="row">
                    <div class="col-lg-12">
                        <h2>ID Card Verification</h2>
                    </div>
                </div>
                <ul class="breadcrumb">
                    <li><a href="index.php"><i class="fas fa-home"></i> Home</a></li>
                    <li class="active">Verify ID</li>
                </ul>
            </div>
        </div>
    </div>
    <!-- End Breadcrumb -->

    <div class="registration-area half-bg default-padding" style="background-color: #f8f9fa; padding: 50px 0;">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="card shadow">
                        <div class="card-header bg-success text-white text-center">
                            <h4 class="mb-0"><i class="fas fa-check-circle"></i> Verified Member ID Card</h4>
                        </div>
                        <div class="card-body p-4">
                            <div class="alert alert-success text-center">
                                <i class="fas fa-shield-alt fa-2x mb-3"></i>
                                <h5>This ID Card is Verified and Valid</h5>
                            </div>

                            <div class="row">
                                <div class="col-md-4 text-center mb-3">
                                    <?php if (!empty($member['photo'])): ?>
                                        <img src="<?php echo htmlspecialchars($member['photo']); ?>" 
                                             alt="Member Photo" 
                                             class="img-thumbnail" 
                                             style="max-width: 150px; max-height: 180px;">
                                    <?php else: ?>
                                        <div class="img-thumbnail d-inline-flex align-items-center justify-content-center" 
                                             style="width: 150px; height: 180px; background: #f0f0f0;">
                                            <i class="fas fa-user fa-3x text-muted"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-8">
                                    <h4 class="text-primary"><?php echo htmlspecialchars($member['fullname']); ?></h4>
                                    <hr>
                                    <p><strong>Membership ID:</strong> 
                                        <span class="badge bg-info"><?php echo htmlspecialchars($member['membership_id']); ?></span>
                                    </p>
                                    <p><strong>Email:</strong> <?php echo htmlspecialchars($member['email']); ?></p>
                                    <p><strong>Sex:</strong> <?php echo htmlspecialchars($member['sex']); ?></p>
                                    <p><strong>Qualification:</strong> <?php echo htmlspecialchars($member['qualification']); ?></p>
                                    <p><strong>Member Since:</strong> <?php echo date('F d, Y', strtotime($member['created_at'])); ?></p>
                                    <?php if (!empty($member['expiry_date'])): ?>
                                        <p><strong>Expiry Date:</strong> 
                                            <?php 
                                            $expiryDate = new DateTime($member['expiry_date']);
                                            $today = new DateTime();
                                            if ($expiryDate < $today) {
                                                echo '<span class="text-danger">' . date('F d, Y', strtotime($member['expiry_date'])) . ' (Expired)</span>';
                                            } else {
                                                echo date('F d, Y', strtotime($member['expiry_date']));
                                            }
                                            ?>
                                        </p>
                                    <?php endif; ?>
                                    <p><strong>Status:</strong> 
                                        <span class="badge <?php echo $member['status'] == 'active' ? 'bg-success' : 'bg-danger'; ?>">
                                            <?php echo ucfirst($member['status']); ?>
                                        </span>
                                    </p>
                                </div>
                            </div>

                            <div class="text-center mt-4">
                                <a href="index.php" class="btn btn-primary">
                                    <i class="fas fa-home"></i> Back to Home
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php include 'footer.php'; ?>

    <!-- jQuery Frameworks -->
    <script src="assets/js/jquery-1.12.4.min.js"></script>
    <script src="assets/js/popper.min.js"></script>
    <script src="assets/js/bootstrap.min.js"></script>
    <script src="assets/js/equal-height.min.js"></script>
    <script src="assets/js/jquery.appear.js"></script>
    <script src="assets/js/jquery.easing.min.js"></script>
    <script src="assets/js/jquery.magnific-popup.min.js"></script>
    <script src="assets/js/modernizr.custom.13711.js"></script>
    <script src="assets/js/owl.carousel.min.js"></script>
    <script src="assets/js/wow.min.js"></script>
    <script src="assets/js/progress-bar.min.js"></script>
    <script src="assets/js/isotope.pkgd.min.js"></script>
    <script src="assets/js/imagesloaded.pkgd.min.js"></script>
    <script src="assets/js/count-to.js"></script>
    <script src="assets/js/YTPlayer.min.js"></script>
    <script src="assets/js/jquery.nice-select.min.js"></script>
    <script src="assets/js/bootsnav.js"></script>
    <script src="assets/js/main.js"></script>

</body>

</html>

