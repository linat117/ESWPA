<!DOCTYPE html>
<html lang="en">
<?php
include 'head.php';
include 'include/config.php';

// Get token from URL
$token = $_GET['token'] ?? '';

if (empty($token)) {
    header("Location: member-forgot-password.php?error=Invalid reset link");
    exit();
}

// Validate token
$query = "SELECT prt.*, r.fullname, r.email 
          FROM password_reset_tokens prt
          INNER JOIN registrations r ON prt.member_id = r.id
          WHERE prt.token = ? AND prt.used = 0 AND prt.expires_at > NOW()";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    header("Location: member-forgot-password.php?error=Invalid or expired reset link. Please request a new one.");
    exit();
}

$tokenData = $result->fetch_assoc();
$stmt->close();
?>

<body>
    <!-- Header -->
    <?php
    include 'header-v1.2.php';
    ?>
    <!-- End Header -->

    <!-- Start Breadcrumb -->
    <div class="breadcrumb-area text-center shadow dark bg-fixed padding-xl text-light" style="background-image: url(assets/img/bgregister.png);">
        <div class="container">
            <div class="breadcrumb-items">
                <div class="row">
                    <div class="col-lg-12">
                        <h2>Reset Password</h2>
                    </div>
                </div>
                <ul class="breadcrumb">
                    <li><a href="index.php"><i class="fas fa-home"></i> Home</a></li>
                    <li><a href="member-login.php">Member Login</a></li>
                    <li class="active">Reset Password</li>
                </ul>
            </div>
        </div>
    </div>
    <!-- End Breadcrumb -->

    <div class="registration-area half-bg default-padding" style="background-color: white;">
        <div class="row justify-content-center">
            <div class="col-lg-6 col-md-8">
                <div class="registration-form shadow p-4 bg-white">
                    <h4 class="text-center mb-4">Reset Your Password</h4>
                    <p class="text-center mb-4">Hello <?php echo htmlspecialchars($tokenData['fullname']); ?>, please enter your new password below</p>

                    <?php
                    if (isset($_GET['error'])) {
                        $error = $_GET['error'];
                        echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">';
                        if ($error == 'password_mismatch') {
                            echo 'Passwords do not match.';
                        } elseif ($error == 'password_weak') {
                            echo 'Password must be at least 8 characters long.';
                        } else {
                            echo htmlspecialchars($error);
                        }
                        echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
                        echo '</div>';
                    }
                    
                    if (isset($_GET['success'])) {
                        echo '<div class="alert alert-success alert-dismissible fade show" role="alert">';
                        echo 'Password reset successfully! You can now login with your new password.';
                        echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
                        echo '</div>';
                    }
                    ?>

                    <!-- Reset Password Form -->
                    <form action="include/member-reset-password.php" method="post" class="row g-3" id="resetPasswordForm">
                        <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                        
                        <div class="col-12 mb-3">
                            <label for="password" class="form-label">New Password *</label>
                            <input type="password" class="form-control" id="password" name="password" required 
                                   placeholder="Enter your new password (min 8 characters)" minlength="8">
                            <small class="text-muted">Password must be at least 8 characters long</small>
                        </div>

                        <div class="col-12 mb-3">
                            <label for="confirm_password" class="form-label">Confirm New Password *</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required 
                                   placeholder="Confirm your new password">
                        </div>

                        <div class="col-12">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-key"></i> Reset Password
                            </button>
                        </div>

                        <div class="col-12 text-center">
                            <p class="mb-0"><a href="member-login.php">Back to Login</a></p>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php
    include 'footer.php';
    ?>

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

    <script>
        // Validate password match
        document.getElementById('resetPasswordForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match!');
                return false;
            }
            
            if (password.length < 8) {
                e.preventDefault();
                alert('Password must be at least 8 characters long!');
                return false;
            }
        });
    </script>

</body>

</html>

