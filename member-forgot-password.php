<!DOCTYPE html>
<html lang="en">
<?php
include 'head.php';
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
                        <h2>Forgot Password</h2>
                    </div>
                </div>
                <ul class="breadcrumb">
                    <li><a href="index.php"><i class="fas fa-home"></i> Home</a></li>
                    <li><a href="member-login.php">Member Login</a></li>
                    <li class="active">Forgot Password</li>
                </ul>
            </div>
        </div>
    </div>
    <!-- End Breadcrumb -->

    <div class="registration-area half-bg default-padding" style="background-color: white;">
        <div class="row justify-content-center">
            <div class="col-lg-6 col-md-8">
                <div class="registration-form shadow p-4 bg-white">
                    <h4 class="text-center mb-4">Forgot Password</h4>
                    <p class="text-center mb-4">Enter your email address and we'll send you a link to reset your password</p>

                    <?php
                    if (isset($_GET['error'])) {
                        $error = $_GET['error'];
                        echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">';
                        if ($error == 'email_not_found') {
                            echo 'Email address not found. Please check your email and try again.';
                        } elseif ($error == 'not_approved') {
                            echo 'Your membership is not approved yet. Please contact support.';
                        } elseif ($error == 'email_failed') {
                            echo 'Failed to send email. Please try again later or contact support.';
                        } elseif ($error == 'expired_reset_link') {
                            echo 'Invalid or expired reset link. Please request a new one.';
                        } else {
                            echo htmlspecialchars($error);
                        }
                        echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
                        echo '</div>';
                    }
                    
                    if (isset($_GET['success'])) {
                        echo '<div class="alert alert-success alert-dismissible fade show" role="alert">';
                        echo 'Password reset link has been sent to your email address. Please check your inbox and follow the instructions.';
                        echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
                        echo '</div>';
                    }
                    ?>

                    <!-- Forgot Password Form -->
                    <form action="member-forgot-password-handler.php" method="post" class="row g-3" id="forgotPasswordForm">
                        <div class="col-12 mb-3">
                            <label for="email" class="form-label">Email Address *</label>
                            <input type="email" class="form-control" id="email" name="email" required 
                                   placeholder="Enter your registered email address">
                            <small class="text-muted">We'll send a password reset link to this email</small>
                        </div>

                        <div class="col-12">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-paper-plane"></i> Send Reset Link
                            </button>
                        </div>

                        <div class="col-12 text-center">
                            <p class="mb-0"><a href="member-login.php">Back to Login</a></p>
                            <p class="mb-0 mt-2">Don't have an account? <a href="sign-up.php">Register here</a></p>
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

</body>

</html>

