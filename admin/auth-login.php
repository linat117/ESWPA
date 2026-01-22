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
                                        <h4 class="fs-20">Sign In</h4>
                                        <p class="text-muted mb-3">Enter your email address and password to access
                                            account.
                                        </p>

                                        <!-- form -->
                                        <form action="admin-login-handler.php" method="POST">
                                            <div class="mb-3">
                                                <label for="username" class="form-label">Username</label>
                                                <input class="form-control" type="text" name="username" id="username" placeholder="Enter your username" required>
                                            </div>
                                            <div class="mb-3">
                                                <a href="auth-forgotpw.php" class="text-muted float-end"><small>Forgot your password?</small></a>
                                                <label for="password" class="form-label">Password</label>
                                                <input class="form-control" type="password" name="password" id="password" placeholder="Enter your password" required>
                                            </div>
                                            <input type="hidden" name="action" value="login">
                                            <button class="btn btn-soft-primary w-100" type="submit">
                                                <i class="ri-login-circle-fill me-1"></i>
                                                <span class="fw-bold">Log In</span>
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