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
                        <h2>Become a Member</h2>
                    </div>
                </div>
                <ul class="breadcrumb">
                    <li><a href="index.php"><i class="fas fa-home"></i> Home</a></li>
                    <li class="active">Register</li>
                </ul>
            </div>
        </div>
    </div>
    <!-- End Breadcrumb -->


    <div class="registration-area half-bg default-padding" style="background-color: white;">
        <div class="row justify-content-center">
            <div class="col-lg-8 col-md-10">
                <div class="registration-form shadow p-4 bg-white">
                    <h4 class="text-center mb-4">Membership Registration</h4>
                    <p class="text-center mb-4">Join Ethiopian Social Workers Professional Association</p>

                    <!-- Registration Form -->
                    <form action="register-handler.php" method="post" enctype="multipart/form-data" class="row g-3">
                        <!-- Full Name and Sex in same line -->
                        <div class="row mb-3">
                            <div class="col-md-8">
                                <label for="fullname" class="form-label">Full Name *</label>
                                <input type="text" class="form-control" id="fullname" name="fullname" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Sex *</label>
                                <div class="gender-options">
                                    <div class="gender-option">
                                        <input type="radio" name="sex" id="male" value="male" required class="gender-input">
                                        <label for="male" class="gender-label">
                                            <i class="fas fa-male"></i>
                                            Male
                                        </label>
                                    </div>
                                    <div class="gender-option">
                                        <input type="radio" name="sex" id="female" value="female" class="gender-input">
                                        <label for="female" class="gender-label">
                                            <i class="fas fa-female"></i>
                                            Female
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Photo Upload -->
                        <div class="col-md-12 mb-3">
                            <label for="photo" class="form-label">Photo</label>
                            <input type="file" class="form-control" id="photo" name="photo" accept="image/*">
                            <small class="text-muted">Upload a professional photo (Max size: 2MB)</small>
                        </div>

                        <!-- Email -->
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Email Address *</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>

                        <!-- Phone -->
                        <div class="col-md-6 mb-3">
                            <label for="phone" class="form-label">Phone Number *</label>
                            <input type="tel" class="form-control" id="phone" name="phone" required>
                        </div>

                        <!-- Address -->
                        <div class="col-12 mb-3">
                            <label for="address" class="form-label">Address *</label>
                            <textarea class="form-control" id="address" name="address" rows="2" required></textarea>
                        </div>

                        <!-- Qualification Details -->
                        <div class="col-12 mb-3">
                            <label for="qualification" class="form-label">Details of Qualification</label>
                            <textarea class="form-control" id="qualification" name="qualification" rows="3"
                                placeholder="Please provide details about your educational background and professional qualifications"></textarea>
                            <small class="text-muted">Optional: Include your degrees, certifications, and relevant experience</small>
                        </div>

                        <!-- Membership Payment Duration -->
                        <div class="col-12 mb-3">
                            <label for="paymentDuration" class="form-label">Membership Payment Duration *</label>
                            <select class="form-control" id="paymentDuration" name="paymentDuration" required>
                                <option value="3_months">Within 3 months</option>
                                <option value="6_months">Within 6 months</option>
                                <option value="1_year">With in 1 year</option>
                            </select>
                        </div>

                        <!-- Payment Options -->
                        <div class="col-12 mb-3">
                            <label class="form-label">Payment Options *</label>
                            <div class="payment-options">
                                <div class="payment-option">
                                    <input type="radio" name="paymentOption" id="bank" value="bank" required class="payment-input">
                                    <label for="bank" class="payment-label">
                                        Bank
                                    </label>
                                </div>
                                <div class="payment-option">
                                    <input type="radio" name="paymentOption" id="cash" value="cash" class="payment-input">
                                    <label for="cash" class="payment-label">
                                        Cash
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Bank Slip Screenshot Upload -->
                        <div class="col-12 mb-3">
                            <label for="bankSlip" class="form-label">Bank Slip Screenshot*</label>
                            <input type="file" class="form-control" id="bankSlip" name="bankSlip" required accept="image/*">
                            <small class="text-muted">Upload a screenshot of your bank slip (Max size: 2MB)</small>
                        </div>

                        <!-- Update the ID Card Payment Option -->
                        <div class="col-12 mb-4">
                            <div class="id-card-option">
                                <div class="form-check custom-checkbox">
                                    <input class="form-check-input custom-checkbox-input" type="checkbox" id="idCard" name="idCard">
                                    <label class="form-check-label" for="idCard">
                                        I am willing to pay 300 ETB for ID card
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="col-12">
                            <button type="submit" id="submitBtn" class="btn btn-primary w-100" disabled>Submit Registration</button>
                            <small class="text-muted d-block mt-2" id="submitHint">Please upload your payment slip to enable submission</small>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <style>
        .registration-form {
            border-radius: 10px;
        }

        .form-label {
            font-weight: 500;
            color: #333;
        }

        .form-control {
            border: 1px solid #ddd;
            padding: 10px 15px;
            border-radius: 5px;
        }

        .form-control:focus {
            border-color: #1273c3;
            box-shadow: 0 0 0 0.2rem rgba(18, 115, 195, 0.25);
        }

        .btn-primary {
            background-color: #1273c3;
            border: none;
            padding: 12px 30px;
            font-weight: 500;
            border-radius: 5px;
        }

        .btn-primary:hover {
            background-color: #0b5a9e;
        }

        .text-muted {
            font-size: 0.85rem;
        }

        .form-check-input:checked {
            background-color: #1273c3;
            border-color: #1273c3;
        }

        @media (max-width: 768px) {
            .registration-form {
                padding: 20px !important;
            }
        }

        .gender-options {
            display: flex;
            gap: 10px;
        }

        .gender-option {
            flex: 1;
        }

        .gender-input {
            display: none;
        }

        .gender-label {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 8px 12px;
            font-size: 14px;
            border: 2px solid #ddd;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s ease;
            background: #fff;
            color: #666;
            font-weight: 500;
        }

        .gender-label i {
            font-size: 16px;
        }

        .gender-input:checked+.gender-label {
            background: #1273c3;
            color: white;
            border-color: #1273c3;
        }

        .gender-label:hover {
            border-color: #1273c3;
            color: #1273c3;
            box-shadow: 0 2px 8px rgba(18, 115, 195, 0.1);
        }

        .gender-input:checked+.gender-label:hover {
            color: white;
            box-shadow: 0 2px 8px rgba(18, 115, 195, 0.2);
        }

        @media (max-width: 768px) {
            .gender-options {
                margin-top: 5px;
            }

            .gender-label {
                padding: 6px 10px;
                font-size: 13px;
            }

            .gender-label i {
                font-size: 14px;
            }
        }

        .small-checkbox {
            width: 16px;
            height: 16px;
            margin-top: 0.2em;
        }

        .id-card-option {
            background-color: #f8f9fa;
            padding: 8px 12px;
            border: 1px solid #e9ecef;
        }

        .custom-checkbox {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 2px 0;
        }

        .custom-checkbox-input {
            width: 18px !important;
            height: 18px !important;
            min-width: 18px !important;
            min-height: 18px !important;
            border-radius: 50% !important;
            margin: 0 !important;
            cursor: pointer;
            appearance: none !important;
            -webkit-appearance: none !important;
            background-color: #fff;
            border: 1.5px solid #999 !important;
            position: relative;
            padding: 0 !important;
            vertical-align: middle;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .custom-checkbox-input:checked {
            background-color: #1273c3;
            border-color: #1273c3 !important;
        }

        .custom-checkbox-input:checked::after {
            content: '✓';
            position: absolute;
            color: white;
            font-size: 12px;
            font-weight: bold;
            line-height: 1;
            margin-top: -1px;
        }

        .custom-checkbox .form-check-label {
            font-size: 14px;
            color: #666;
            cursor: pointer;
            line-height: 18px;
            display: flex;
            align-items: center;
        }

        .payment-options {
            display: flex;
            gap: 10px;
        }

        .payment-option {
            flex: 1;
        }

        .payment-input {
            display: none;
        }

        .payment-label {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 8px 12px;
            font-size: 14px;
            border: 2px solid #ddd;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s ease;
            background: #fff;
            color: #666;
            font-weight: 500;
        }

        .payment-input:checked+.payment-label {
            background: #1273c3;
            color: white;
            border-color: #1273c3;
        }

        .payment-label:hover {
            border-color: #1273c3;
            color: #1273c3;
            box-shadow: 0 2px 8px rgba(18, 115, 195, 0.1);
        }

        .payment-input:checked+.payment-label:hover {
            color: white;
            box-shadow: 0 2px 8px rgba(18, 115, 195, 0.2);
        }

        @media (max-width: 768px) {
            .payment-options {
                margin-top: 5px;
            }

            .payment-label {
                padding: 6px 10px;
                font-size: 13px;
            }
        }
    </style>

    <!-- End Organization Registration Form -->

    <!-- Start Footer -->
    <?php
    include 'footer.php';
    ?>
    <!-- End Footer -->

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
        $(document).ready(function() {
            const bankSlipInput = $('#bankSlip');
            const paymentDurationSelect = $('#paymentDuration');
            const submitBtn = $('#submitBtn');
            const submitHint = $('#submitHint');
            
            // Check if email exists in system (for first registration validation)
            let isFirstRegistration = true;
            
            // Enable/disable submit button based on payment slip upload
            bankSlipInput.on('change', function() {
                if (this.files && this.files.length > 0) {
                    const file = this.files[0];
                    const maxSize = 5 * 1024 * 1024; // 5MB
                    const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp', 'application/pdf'];
                    
                    if (file.size > maxSize) {
                        alert('File size exceeds 5MB. Please upload a smaller file.');
                        $(this).val('');
                        submitBtn.prop('disabled', true);
                        submitHint.text('Please upload your payment slip to enable submission');
                        return;
                    }
                    
                    if (!allowedTypes.includes(file.type)) {
                        alert('Invalid file type. Please upload an image (JPG, PNG, WEBP) or PDF.');
                        $(this).val('');
                        submitBtn.prop('disabled', true);
                        submitHint.text('Please upload your payment slip to enable submission');
                        return;
                    }
                    
                    // Validate first registration must be 1 year
                    if (isFirstRegistration && paymentDurationSelect.val() !== '1_year') {
                        alert('First registration must be for one year. Please select "Within 1 year" payment duration.');
                        paymentDurationSelect.val('1_year');
                    }
                    
                    submitBtn.prop('disabled', false);
                    submitHint.text('Payment slip uploaded. You can now submit your registration.');
                    submitHint.removeClass('text-muted').addClass('text-success');
                } else {
                    submitBtn.prop('disabled', true);
                    submitHint.text('Please upload your payment slip to enable submission');
                    submitHint.removeClass('text-success').addClass('text-muted');
                }
            });
            
            // Validate payment duration for first registration
            paymentDurationSelect.on('change', function() {
                if (isFirstRegistration && $(this).val() !== '1_year') {
                    alert('First registration must be for one year. Please select "Within 1 year" payment duration.');
                    $(this).val('1_year');
                }
            });
            
            // Check email on blur to determine if first registration
            $('#email').on('blur', function() {
                const email = $(this).val();
                if (email) {
                    // You can add AJAX call here to check if email exists
                    // For now, we'll assume it's first registration
                    isFirstRegistration = true;
                }
            });
            
            // Form submission validation
            $('form').on('submit', function(e) {
                if (!bankSlipInput[0].files || bankSlipInput[0].files.length === 0) {
                    e.preventDefault();
                    alert('Please upload your payment slip before submitting.');
                    return false;
                }
                
                // Final validation: first registration must be 1 year
                if (isFirstRegistration && paymentDurationSelect.val() !== '1_year') {
                    e.preventDefault();
                    alert('First registration must be for one year. Please select "Within 1 year" payment duration.');
                    paymentDurationSelect.focus();
                    return false;
                }
            });
        });
    </script>

</body>

</html>