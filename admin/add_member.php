<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: auth-login.php");
    exit();
}

include 'include/conn.php';
include 'header.php';

$success = '';
$error = '';

function generateMembershipID($conn) {
    $year = date('Y');
    $prefix = "ESWPA-{$year}-";
    $query = "SELECT membership_id FROM registrations WHERE membership_id LIKE ? ORDER BY membership_id DESC LIMIT 1";
    $stmt = $conn->prepare($query);
    $pattern = $prefix . "%";
    $stmt->bind_param("s", $pattern);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $last_id = $result->fetch_assoc()['membership_id'];
        $last_number = (int) substr($last_id, strlen($prefix));
        $new_number = $last_number + 1;
    } else {
        $new_number = 1;
    }
    return $prefix . str_pad($new_number, 5, '0', STR_PAD_LEFT);
}

function calculateExpiryDate($paymentDuration, $createdAt) {
    $start = new DateTime($createdAt);
    $expiry = clone $start;
    if (strpos($paymentDuration, '1_year') !== false || strpos($paymentDuration, '1 year') !== false) {
        $expiry->modify('+1 year');
    } elseif (strpos($paymentDuration, '6_months') !== false || strpos($paymentDuration, '6 months') !== false) {
        $expiry->modify('+6 months');
    } elseif (strpos($paymentDuration, '3_months') !== false || strpos($paymentDuration, '3 months') !== false) {
        $expiry->modify('+3 months');
    } else {
        $expiry->modify('+1 year');
    }
    return $expiry->format('Y-m-d');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim($_POST['fullname'] ?? '');
    $sex = in_array($_POST['sex'] ?? '', ['Male', 'Female']) ? $_POST['sex'] : 'Male';
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $qualification = trim($_POST['qualification'] ?? '');
    $payment_duration = $_POST['payment_duration'] ?? '1_year';
    $payment_option = $_POST['payment_option'] ?? 'bank';
    $approval_status = in_array($_POST['approval_status'] ?? 'pending', ['pending', 'approved']) ? $_POST['approval_status'] : 'pending';
    $id_card = isset($_POST['id_card']) ? 1 : 0;

    if (empty($fullname) || empty($email) || empty($phone) || empty($address)) {
        $error = 'Please fill in all required fields (Full name, Email, Phone, Address).';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $check = $conn->prepare("SELECT id FROM registrations WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $error = 'This email is already registered. Use a different email.';
            $check->close();
        } else {
            $check->close();

            $baseDir = dirname(__DIR__);
            $photoDir = $baseDir . '/uploads/members/';
            $bankDir = $baseDir . '/uploads/bankslip/';
            if (!is_dir($photoDir)) mkdir($photoDir, 0777, true);
            if (!is_dir($bankDir)) mkdir($bankDir, 0777, true);

            $photo = null;
            if (!empty($_FILES['photo']['name']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                $f = $_FILES['photo'];
                if (in_array($f['type'], ['image/jpeg', 'image/jpg', 'image/png', 'image/webp']) && $f['size'] <= 2 * 1024 * 1024) {
                    $name = time() . '_' . uniqid() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($f['name']));
                    if (move_uploaded_file($f['tmp_name'], $photoDir . $name)) {
                        $photo = 'uploads/members/' . $name;
                    }
                }
            }

            $bank_slip = null;
            if (!empty($_FILES['bank_slip']['name']) && $_FILES['bank_slip']['error'] === UPLOAD_ERR_OK) {
                $f = $_FILES['bank_slip'];
                if (in_array($f['type'], ['image/jpeg', 'image/jpg', 'image/png', 'image/webp', 'application/pdf']) && $f['size'] <= 5 * 1024 * 1024) {
                    $name = time() . '_' . uniqid() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($f['name']));
                    if (move_uploaded_file($f['tmp_name'], $bankDir . $name)) {
                        $bank_slip = 'uploads/bankslip/' . $name;
                    }
                }
            }

            $membership_id = generateMembershipID($conn);
            $created_at = date('Y-m-d H:i:s');
            $expiry_date = calculateExpiryDate($payment_duration, $created_at);
            $status = $approval_status === 'approved' ? 'active' : 'pending';
            $photo = $photo ?? null;
            $bank_slip = $bank_slip ?? null;

            if ($approval_status === 'approved') {
                $approved_by = (int) ($_SESSION['user_id'] ?? 0);
                $sql = "INSERT INTO registrations (
                    membership_id, fullname, sex, email, phone, address, qualification,
                    payment_duration, payment_option, id_card, photo, bank_slip,
                    approval_status, status, expiry_date, created_at, approved_by, approved_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                if ($stmt) {
                    $stmt->bind_param(
                        "sssssssssissssssis",
                        $membership_id, $fullname, $sex, $email, $phone, $address, $qualification,
                        $payment_duration, $payment_option, $id_card, $photo, $bank_slip,
                        $approval_status, $status, $expiry_date, $created_at, $approved_by, $created_at
                    );
                }
            } else {
                $sql = "INSERT INTO registrations (
                    membership_id, fullname, sex, email, phone, address, qualification,
                    payment_duration, payment_option, id_card, photo, bank_slip,
                    approval_status, status, expiry_date, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                if ($stmt) {
                    $stmt->bind_param(
                        "sssssssssissssss",
                        $membership_id, $fullname, $sex, $email, $phone, $address, $qualification,
                        $payment_duration, $payment_option, $id_card, $photo, $bank_slip,
                        $approval_status, $status, $expiry_date, $created_at
                    );
                }
            }

            if (!$stmt) {
                $error = 'Database error. Please try again.';
            } elseif ($stmt->execute()) {
                $stmt->close();
                header("Location: members_list.php?success=Member added successfully. Membership ID: " . urlencode($membership_id));
                exit();
            } else {
                $error = 'Failed to save member: ' . $conn->error;
                $stmt->close();
            }
        }
    }
}
?>
<body>
    <div class="wrapper">
        <?php include 'sidebar.php'; ?>
        <div class="content-page">
            <div class="content">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-12">
                            <div class="page-title-box d-flex justify-content-between align-items-center">
                                <h4 class="page-title">Add Member</h4>
                                <div>
                                    <a href="members_list.php" class="btn btn-outline-secondary">
                                        <i class="ri-arrow-left-line"></i> Back to Members
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <?php echo htmlspecialchars($error); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form method="post" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-12 col-lg-6">
                                <div class="card">
                                    <div class="card-header bg-primary text-white">
                                        <h5 class="mb-0"><i class="ri-user-line"></i> Personal Information</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label for="fullname" class="form-label">Full Name <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="fullname" name="fullname" required
                                                   value="<?php echo htmlspecialchars($_POST['fullname'] ?? ''); ?>">
                                        </div>
                                        <div class="mb-3">
                                            <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                            <input type="email" class="form-control" id="email" name="email" required
                                                   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                                        </div>
                                        <div class="mb-3">
                                            <label for="phone" class="form-label">Phone <span class="text-danger">*</span></label>
                                            <input type="tel" class="form-control" id="phone" name="phone" required
                                                   value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                                        </div>
                                        <div class="mb-3">
                                            <label for="sex" class="form-label">Sex <span class="text-danger">*</span></label>
                                            <select class="form-select" id="sex" name="sex" required>
                                                <option value="Male" <?php echo ($_POST['sex'] ?? '') === 'Male' ? 'selected' : ''; ?>>Male</option>
                                                <option value="Female" <?php echo ($_POST['sex'] ?? '') === 'Female' ? 'selected' : ''; ?>>Female</option>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label for="address" class="form-label">Address <span class="text-danger">*</span></label>
                                            <textarea class="form-control" id="address" name="address" rows="3" required><?php echo htmlspecialchars($_POST['address'] ?? ''); ?></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 col-lg-6">
                                <div class="card">
                                    <div class="card-header bg-success text-white">
                                        <h5 class="mb-0"><i class="ri-graduation-cap-line"></i> Qualification & Membership</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label for="qualification" class="form-label">Qualification</label>
                                            <input type="text" class="form-control" id="qualification" name="qualification"
                                                   value="<?php echo htmlspecialchars($_POST['qualification'] ?? ''); ?>"
                                                   placeholder="e.g. BSW, MSW, PhD">
                                        </div>
                                        <div class="mb-3">
                                            <label for="payment_duration" class="form-label">Payment Duration <span class="text-danger">*</span></label>
                                            <select class="form-select" id="payment_duration" name="payment_duration" required>
                                                <option value="3_months" <?php echo ($_POST['payment_duration'] ?? '') === '3_months' ? 'selected' : ''; ?>>Within 3 months</option>
                                                <option value="6_months" <?php echo ($_POST['payment_duration'] ?? '') === '6_months' ? 'selected' : ''; ?>>Within 6 months</option>
                                                <option value="1_year" <?php echo ($_POST['payment_duration'] ?? '1_year') === '1_year' ? 'selected' : ''; ?>>Within 1 year</option>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label for="payment_option" class="form-label">Payment Option <span class="text-danger">*</span></label>
                                            <select class="form-select" id="payment_option" name="payment_option" required>
                                                <option value="bank" <?php echo ($_POST['payment_option'] ?? 'bank') === 'bank' ? 'selected' : ''; ?>>Bank</option>
                                                <option value="cash" <?php echo ($_POST['payment_option'] ?? '') === 'cash' ? 'selected' : ''; ?>>Cash</option>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label for="approval_status" class="form-label">Approval Status</label>
                                            <select class="form-select" id="approval_status" name="approval_status">
                                                <option value="pending" <?php echo ($_POST['approval_status'] ?? 'pending') === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                <option value="approved" <?php echo ($_POST['approval_status'] ?? '') === 'approved' ? 'selected' : ''; ?>>Approved</option>
                                            </select>
                                            <small class="text-muted">Approved members can log in and use member features.</small>
                                        </div>
                                        <div class="mb-3">
                                            <div class="form-check">
                                                <input type="checkbox" class="form-check-input" id="id_card" name="id_card" value="1" <?php echo !empty($_POST['id_card']) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="id_card">Request ID card</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="card mt-3">
                                    <div class="card-header bg-info text-white">
                                        <h5 class="mb-0"><i class="ri-image-line"></i> Photo & Documents</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label for="photo" class="form-label">Photo (optional)</label>
                                            <input type="file" class="form-control" id="photo" name="photo" accept="image/*">
                                            <small class="text-muted">JPG, PNG, WEBP. Max 2MB</small>
                                        </div>
                                        <div class="mb-3">
                                            <label for="bank_slip" class="form-label">Bank slip / proof (optional)</label>
                                            <input type="file" class="form-control" id="bank_slip" name="bank_slip" accept="image/*,application/pdf">
                                            <small class="text-muted">Image or PDF. Max 5MB</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="d-flex gap-2">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="ri-add-circle-line"></i> Add Member
                                            </button>
                                            <a href="members_list.php" class="btn btn-outline-secondary">Cancel</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <?php include 'footer.php'; ?>
        </div>
    </div>
    <script src="assets/js/vendor.min.js"></script>
    <script src="assets/js/app.min.js"></script>
</body>
</html>
