<!DOCTYPE html>
<html lang="en">

<?php
include 'header.php';
?>

<body class="authentication-bg">

    <div class="account-pages pt-2 pt-sm-5 pb-4 pb-sm-5 position-relative">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-xxl-8 col-lg-10">
                    <div class="card overflow-hidden bg-opacity-25">
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
                                        <h4 class="fs-20">Register</h4>
                                        <p class="text-muted mb-3">Add username and password to access
                                            admin panel.</p>

                                        <!-- form -->
                                        <form action="include/auth.php" method="POST">
                                            <div class="mb-3">
                                                <label for="fullname" class="form-label">Username</label>
                                                <input class="form-control" type="text" name="username" id="username"
                                                    placeholder="Enter your username" required="">
                                            </div>
                                            <div class="mb-3">
                                                <label for="password" class="form-label">Password</label>
                                                <input class="form-control" type="password" name="password" required="" id="password"
                                                    placeholder="Enter your password">
                                            </div>
                                            <div class="mb-0 d-grid text-center">
                                                <input type="hidden" name="action" value="signup">
                                                <button class="btn btn-primary fw-semibold" type="submit">Sign
                                                    Up</button>
                                            </div>
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

            <!-- end row -->
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