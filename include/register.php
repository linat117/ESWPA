<?php
// Include database connection
// Use absolute path from project root
$configPath = __DIR__ . '/config.php';
if (file_exists($configPath)) {
    include $configPath;
} else {
    // Fallback: try relative path
    include 'config.php';
}

// Get base URL for redirects
$baseUrl = '';
if (isset($_SERVER['HTTP_HOST'])) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $baseUrl = $protocol . '://' . $_SERVER['HTTP_HOST'];
    $scriptPath = dirname($_SERVER['SCRIPT_NAME']);
    if ($scriptPath !== '/') {
        $baseUrl .= $scriptPath;
    }
    // Remove register-handler.php or include/register.php from path
    if (strpos($baseUrl, '/include') !== false) {
        $baseUrl = dirname($baseUrl);
    }
    if (strpos($baseUrl, 'register-handler.php') !== false) {
        $baseUrl = dirname($baseUrl);
    }
}
$signUpUrl = $baseUrl ? $baseUrl . '/sign-up.php' : 'sign-up.php';

// Function to generate unique membership ID
function generateMembershipID($conn) {
    $year = date('Y');
    $prefix = "ESWPA-{$year}-";
    
    // Get the last membership ID for this year
    $query = "SELECT membership_id FROM registrations WHERE membership_id LIKE ? ORDER BY membership_id DESC LIMIT 1";
    $stmt = $conn->prepare($query);
    $pattern = $prefix . "%";
    $stmt->bind_param("s", $pattern);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $last_id = $result->fetch_assoc()['membership_id'];
        // Extract the number part
        $last_number = intval(substr($last_id, strlen($prefix)));
        $new_number = $last_number + 1;
    } else {
        $new_number = 1;
    }
    
    // Format with leading zeros (5 digits)
    $membership_id = $prefix . str_pad($new_number, 5, '0', STR_PAD_LEFT);
    
    return $membership_id;
}

// Function to calculate expiry date based on payment duration
function calculateExpiryDate($paymentDuration, $createdAt) {
    $startDate = new DateTime($createdAt);
    $expiryDate = clone $startDate;
    
    // Parse payment duration
    if (strpos($paymentDuration, '1_year') !== false || strpos($paymentDuration, '1 year') !== false) {
        $expiryDate->modify('+1 year');
    } elseif (strpos($paymentDuration, '6_months') !== false || strpos($paymentDuration, '6 months') !== false) {
        $expiryDate->modify('+6 months');
    } elseif (strpos($paymentDuration, '3_months') !== false || strpos($paymentDuration, '3 months') !== false) {
        $expiryDate->modify('+3 months');
    } else {
        // Default to 1 year if unclear
        $expiryDate->modify('+1 year');
    }
    
    return $expiryDate->format('Y-m-d');
}

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate payment slip is uploaded (required)
    if (empty($_FILES['bankSlip']['name'])) {
        echo "<script>
                alert('Payment slip is required. Please upload your bank slip.');
                window.location.href = '" . htmlspecialchars($signUpUrl) . "';
              </script>";
        exit();
    }
    
    // Get and sanitize form data
    $fullname = trim($_POST['fullname'] ?? '');
    $sex = $_POST['sex'] ?? '';
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $qualification = trim($_POST['qualification'] ?? '');
    $paymentDuration = $_POST['paymentDuration'] ?? '';
    $paymentOption = $_POST['paymentOption'] ?? '';
    $idCard = isset($_POST['idCard']) ? 1 : 0;
    
    // Validate required fields
    if (empty($fullname) || empty($email) || empty($phone) || empty($address) || empty($paymentDuration) || empty($paymentOption)) {
        echo "<script>
                alert('Please fill in all required fields.');
                window.location.href = '" . htmlspecialchars($signUpUrl) . "';
              </script>";
        exit();
    }
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "<script>
                alert('Please enter a valid email address.');
                window.location.href = '" . htmlspecialchars($signUpUrl) . "';
              </script>";
        exit();
    }
    
    // Validate first registration must be 1 year payment
    if ($paymentDuration !== '1_year') {
        // Check if this email already exists in registrations
        $checkQuery = "SELECT id FROM registrations WHERE email = ?";
        $checkStmt = $conn->prepare($checkQuery);
        $checkStmt->bind_param("s", $email);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        
        // If email doesn't exist, this is first registration - must be 1 year
        if ($checkResult->num_rows == 0) {
            echo "<script>
                    alert('First registration must be for one year. Please select \"Within 1 year\" payment duration.');
                    window.location.href = '" . htmlspecialchars($signUpUrl) . "';
                  </script>";
            exit();
        }
    }
    
    // Check if email already exists
    $emailCheckQuery = "SELECT id FROM registrations WHERE email = ?";
    $emailCheckStmt = $conn->prepare($emailCheckQuery);
    $emailCheckStmt->bind_param("s", $email);
    $emailCheckStmt->execute();
    $emailCheckResult = $emailCheckStmt->get_result();
    
    if ($emailCheckResult->num_rows > 0) {
        echo "<script>
                alert('This email is already registered. Please use a different email or contact support.');
                window.location.href = '" . htmlspecialchars($signUpUrl) . "';
              </script>";
        exit();
    }
    
    // Set upload directories (relative to project root)
    $baseDir = dirname(__DIR__);
    $photoUploadDir = $baseDir . '/uploads/members/';
    $bankSlipUploadDir = $baseDir . '/uploads/bankslip/';
    
    // Ensure directories exist
    if (!file_exists($photoUploadDir)) {
        mkdir($photoUploadDir, 0777, true);
    }
    if (!file_exists($bankSlipUploadDir)) {
        mkdir($bankSlipUploadDir, 0777, true);
    }
    
    // Handle photo upload
    $photo = null;
    if (!empty($_FILES['photo']['name'])) {
        $photoFile = $_FILES['photo'];
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
        $maxSize = 2 * 1024 * 1024; // 2MB
        
        if (in_array($photoFile['type'], $allowedTypes) && $photoFile['size'] <= $maxSize) {
            $photoExtension = pathinfo($photoFile['name'], PATHINFO_EXTENSION);
            $photoName = time() . '_' . uniqid() . '_' . basename($photoFile['name']);
            $photoPath = $photoUploadDir . $photoName;
            
            if (move_uploaded_file($photoFile['tmp_name'], $photoPath)) {
                $photo = 'uploads/members/' . $photoName;
            } else {
                error_log("Failed to move photo file: " . $photoFile['tmp_name'] . " to " . $photoPath);
            }
        }
    }
    
    // Handle bank slip upload (required)
    $bankSlip = null;
    if (!empty($_FILES['bankSlip']['name']) && isset($_FILES['bankSlip']['error']) && $_FILES['bankSlip']['error'] === UPLOAD_ERR_OK) {
        $bankSlipFile = $_FILES['bankSlip'];
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp', 'application/pdf'];
        $maxSize = 5 * 1024 * 1024; // 5MB
        
        if (in_array($bankSlipFile['type'], $allowedTypes) && $bankSlipFile['size'] <= $maxSize) {
            $bankSlipExtension = pathinfo($bankSlipFile['name'], PATHINFO_EXTENSION);
            $bankSlipName = time() . '_' . uniqid() . '_' . basename($bankSlipFile['name']);
            $bankSlipPath = $bankSlipUploadDir . $bankSlipName;
            
            if (move_uploaded_file($bankSlipFile['tmp_name'], $bankSlipPath)) {
                $bankSlip = 'uploads/bankslip/' . $bankSlipName;
            } else {
                error_log("Failed to move bank slip file: " . $bankSlipFile['tmp_name'] . " to " . $bankSlipPath);
                echo "<script>
                        alert('Failed to upload bank slip. Please try again.');
                        window.location.href = '" . htmlspecialchars($signUpUrl) . "';
                      </script>";
                exit();
            }
        } else {
            echo "<script>
                    alert('Invalid bank slip file. Please upload a valid image or PDF (max 5MB).');
                    window.location.href = '" . htmlspecialchars($signUpUrl) . "';
                  </script>";
            exit();
        }
    }
    
    // Generate membership ID
    $membership_id = generateMembershipID($conn);
    
    // Calculate expiry date
    $createdAt = date('Y-m-d H:i:s');
    $expiryDate = calculateExpiryDate($paymentDuration, $createdAt);
    
    // Normalize sex value (Male/Female)
    $sex = ucfirst(strtolower($sex));
    if ($sex !== 'Male' && $sex !== 'Female') {
        $sex = 'Male'; // Default
    }
    
    // Insert data into the database using prepared statement
    $sql = "INSERT INTO registrations (
        membership_id, fullname, sex, email, phone, address, qualification, 
        payment_duration, payment_option, id_card, photo, bank_slip, 
        approval_status, status, expiry_date, created_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'pending', ?, ?)";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("SQL Prepare Error: " . $conn->error);
        echo "<script>
                alert('Registration Failed: Database error. Please try again.');
                window.location.href = '" . htmlspecialchars($signUpUrl) . "';
              </script>";
        exit();
    }
    
    // Bind parameters: 14 parameters (s=string, i=integer, null handling)
    // membership_id, fullname, sex, email, phone, address, qualification, 
    // payment_duration, payment_option, id_card, photo, bank_slip, expiry_date, created_at
    // Note: photo and bank_slip can be null, so we need to handle them properly
    $photo = $photo ?? null;
    $bankSlip = $bankSlip ?? null;
    
    $stmt->bind_param(
        "sssssssssissss",
        $membership_id,
        $fullname,
        $sex,
        $email,
        $phone,
        $address,
        $qualification,
        $paymentDuration,
        $paymentOption,
        $idCard,
        $photo,
        $bankSlip,
        $expiryDate,
        $createdAt
    );
    
    if ($stmt->execute()) {
        // Send confirmation email (to be implemented)
        // TODO: Send no-reply email with membership ID
        
        echo "<script>
                alert('Registration Successful! Your Membership ID is: {$membership_id}\\n\\nYour registration is pending admin approval. You will receive an email once approved.');
                window.location.href = '" . htmlspecialchars($signUpUrl) . "';
              </script>";
    } else {
        error_log("SQL Execute Error: " . $stmt->error);
        echo "<script>
                alert('Registration Failed: " . addslashes($stmt->error) . "');
                window.location.href = '" . htmlspecialchars($signUpUrl) . "';
              </script>";
    }
    
    $stmt->close();
    $conn->close();
} else {
    header("Location: " . $signUpUrl);
    exit();
}
?>
