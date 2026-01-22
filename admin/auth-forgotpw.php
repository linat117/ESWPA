<!DOCTYPE html>
<html lang="en">
<?php
include 'header.php';
?>

<body class="authentication-bg position-relative">
    <div class="account-pages pt-2 pt-sm-5 pb-4 pb-sm-5 position-relative">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-xxl-8 col-lg-10">
                    <div class="card overflow-hidden">
                        <div class="row g-0">
                            <div class="col-lg-6 d-none d-lg-block p-2">
                                <img src="assets/images/auth-img.jpg" alt="" class="img-fluid rounded h-100">
                            </div>
                            <div class="col-lg-6">
                                <div class="d-flex flex-column h-100">
                                    <div class="auth-brand p-4">
                                        <a href="index.html" class="logo-light">
                                            <img src="assets/images/logo-light.png" alt="logo" height="100">
                                        </a>
                                        <a href="index.html" class="logo-dark">
                                            <img src="assets/images/logo-light.png" alt="dark logo" height="100">
                                        </a>
                                    </div>
                                    <div class="p-4 my-auto">
                                        <h4 class="fs-20">Reset Password</h4>
                                        <p class="text-muted mb-3">Enter your username and new password to reset your account.</p>

                                        <!-- form -->
                                        <form action="include/forgotpass.php" method="POST">
                                            <div class="mb-3">
                                                <label for="username" class="form-label">Username</label>
                                                <input class="form-control" type="text" name="username" id="username" placeholder="Enter your username" required>
                                            </div>
                                            <div class="mb-3">
                                                <label for="new_password" class="form-label">New Password</label>
                                                <input class="form-control" type="password" name="new_password" id="new_password" placeholder="Enter your new password" required>
                                            </div>
                                            <div class="mb-3">
                                                <label for="confirm_password" class="form-label">Retype Password</label>
                                                <input class="form-control" type="password" name="confirm_password" id="confirm_password" placeholder="Retype your new password" required>
                                            </div>
                                            <input type="hidden" name="action" value="reset_password">
                                            <button class="btn btn-soft-primary w-100" type="submit">
                                                <i class="ri-lock-unlock-fill me-1"></i>
                                                <span class="fw-bold">Reset Password</span>
                                            </button>
                                        </form>
                                        <!-- end form-->
                                    </div>
                                </div>
                            </div> <!-- end col -->
                        </div>
                    </div>
                </div>
                <!-- end row -->
            </div>
        </div>
        <!-- end container -->
    </div>
    <!-- end page -->

    <?php
    include 'footer.php';
    ?>
    <!-- Vendor js -->
    <script src="assets/js/vendor.min.js"></script>

    <!-- App js -->
    <script src="assets/js/app.min.js"></script>
</body>
</html>
