<!DOCTYPE html>
<html lang="en">

<?php
include 'head.php';
include 'include/config.php';
include 'include/subscribe_handler.php';

// Get token from URL
$token = isset($_GET['token']) ? trim($_GET['token']) : '';
$result = null;

// Process unsubscribe if token is provided
if (!empty($token)) {
    $result = unsubscribe($token);
}
?>

<body class="hibiscus">
    <!-- Header -->
    <?php include 'header-v1.2.php'; ?>
    <!-- End Header -->

    <!-- Start Unsubscribe Area -->
    <div class="about-area default-padding">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 offset-lg-2">
                    <div class="unsubscribe-content">
                        <div class="unsubscribe-header">
                            <div class="unsubscribe-icon">
                                <i class="fas fa-envelope-open"></i>
                            </div>
                            <h2>Unsubscribe from Newsletter</h2>
                        </div>

                        <?php if ($result): ?>
                            <?php if ($result['success']): ?>
                                <div class="alert alert-success">
                                    <i class="fas fa-check-circle"></i>
                                    <?php echo htmlspecialchars($result['message']); ?>
                                </div>
                                <div class="unsubscribe-actions">
                                    <p>We're sorry to see you go. If you change your mind, you can always subscribe again from our homepage.</p>
                                    <a href="index.php" class="btn btn-primary">Return to Homepage</a>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-error">
                                    <i class="fas fa-exclamation-circle"></i>
                                    <?php echo htmlspecialchars($result['message']); ?>
                                </div>
                                <div class="unsubscribe-actions">
                                    <p>If you continue to experience issues, please contact us at <a href="mailto:info@ethiosocialworker.org">info@ethiosocialworker.org</a></p>
                                    <a href="index.php" class="btn btn-primary">Return to Homepage</a>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <?php if (empty($token)): ?>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i>
                                    <p>Please use the unsubscribe link provided in your email newsletter.</p>
                                    <p>If you don't have the link, you can contact us at <a href="mailto:info@ethiosocialworker.org">info@ethiosocialworker.org</a> to unsubscribe.</p>
                                </div>
                                <div class="unsubscribe-actions">
                                    <a href="index.php" class="btn btn-primary">Return to Homepage</a>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Unsubscribe Area -->

    <!-- Footer -->
    <?php include 'footer.php'; ?>
    <!-- End Footer -->

    <!-- jQuery Frameworks -->
    <script src="assets/js/jquery-1.12.4.min.js"></script>
    <script src="assets/js/popper.min.js"></script>
    <script src="assets/js/bootstrap.min.js"></script>
    <script src="assets/js/bootsnav.js"></script>
    <script src="assets/js/main.js"></script>

    <style>
        .unsubscribe-content {
            background: white;
            padding: 50px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .unsubscribe-header {
            margin-bottom: 30px;
        }

        .unsubscribe-icon {
            width: 100px;
            height: 100px;
            margin: 0 auto 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 48px;
        }

        .unsubscribe-header h2 {
            color: #333;
            font-size: 32px;
            font-weight: 600;
            margin: 0;
        }

        .alert {
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            font-size: 16px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .alert-info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }

        .alert i {
            margin-right: 10px;
            font-size: 20px;
        }

        .unsubscribe-actions {
            margin-top: 30px;
        }

        .unsubscribe-actions p {
            color: #666;
            margin-bottom: 20px;
        }

        .btn {
            display: inline-block;
            padding: 12px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
            color: white;
            text-decoration: none;
        }

        .alert a {
            color: inherit;
            text-decoration: underline;
        }

        @media (max-width: 768px) {
            .unsubscribe-content {
                padding: 30px 20px;
            }

            .unsubscribe-header h2 {
                font-size: 24px;
            }

            .unsubscribe-icon {
                width: 80px;
                height: 80px;
                font-size: 36px;
            }
        }
    </style>
</body>
</html>

