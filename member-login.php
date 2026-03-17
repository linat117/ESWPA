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
                        <h2>Member Login</h2>
                    </div>
                </div>
                <ul class="breadcrumb">
                    <li><a href="index.php"><i class="fas fa-home"></i> Home</a></li>
                    <li class="active">Member Login</li>
                </ul>
            </div>
        </div>
    </div>
    <!-- End Breadcrumb -->

    <div class="registration-area half-bg default-padding" style="background-color: white;">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-6 col-md-8">
                    <div class="registration-form shadow p-4 bg-white">
                    <h4 class="text-center mb-4">Member Login</h4>
                    <p class="text-center mb-4">Access your member dashboard</p>

                    <!-- Demo Account Info -->
                    <div class="alert alert-info" role="alert">
                        <strong><i class="fas fa-info-circle"></i> Demo Account:</strong><br>
                        <small>Email: <strong>demo@member.com</strong><br>
                        Password: <strong>demo123</strong></small>
                    </div>

                    <?php
                    if (isset($_GET['error'])) {
                        $error = $_GET['error'];
                        echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">';
                        if ($error == 'invalid') {
                            echo 'Invalid email or password.';
                        } elseif ($error == 'not_approved') {
                            echo 'Your membership is pending approval. Please wait for admin approval.';
                        } elseif ($error == 'expired') {
                            echo 'Your membership has expired. Please renew your membership.';
                        } elseif ($error == 'suspended') {
                            echo 'Your account has been suspended. Please contact support.';
                        } else {
                            echo htmlspecialchars($error);
                        }
                        echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
                        echo '</div>';
                    }
                    
                    if (isset($_GET['success'])) {
                        echo '<div class="alert alert-success alert-dismissible fade show" role="alert">';
                        echo 'Registration successful! Please login with your email and the password you set during registration.';
                        echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
                        echo '</div>';
                    }
                    
                    if (isset($_GET['logout'])) {
                        echo '<div class="alert alert-success alert-dismissible fade show" role="alert">';
                        echo 'You have been logged out successfully.';
                        echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
                        echo '</div>';
                    }
                    
                    if (isset($_GET['success']) && $_GET['success'] == 'password_reset') {
                        echo '<div class="alert alert-success alert-dismissible fade show" role="alert">';
                        echo 'Password reset successfully! You can now login with your new password.';
                        echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
                        echo '</div>';
                    }
                    ?>

                    <!-- Login Form -->
                    <form action="member-login-handler.php" method="post" class="row g-3">
                        <div class="col-12 mb-3">
                            <label for="email" class="form-label">Email Address *</label>
                            <input type="email" class="form-control" id="email" name="email" required 
                                   placeholder="Enter your registered email">
                        </div>

                        <div class="col-12 mb-3">
                            <label for="password" class="form-label">Password *</label>
                            <input type="password" class="form-control" id="password" name="password" required 
                                   placeholder="Enter your password">
                        </div>

                        <div class="col-12 mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="remember" name="remember">
                                <label class="form-check-label" for="remember">
                                    Remember me
                                </label>
                            </div>
                        </div>

                        <div class="col-12">
                            <button type="submit" class="btn btn-primary w-100">Login</button>
                        </div>

                        <div class="col-12 text-center">
                            <p class="mb-0">Don't have an account? <a href="sign-up.php">Register here</a></p>
                            <p class="mb-0 mt-2"><a href="member-forgot-password.php">Forgot Password?</a></p>
                        </div>
                    </form>
                </div>
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

