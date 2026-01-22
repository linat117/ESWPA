<?php
session_start();

// Check if member is logged in
if (!isset($_SESSION['member_id'])) {
    header("Location: member-login.php");
    exit();
}

include 'include/config.php';

// Get member details
$member_id = $_SESSION['member_id'];
$query = "SELECT * FROM registrations WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $member_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    session_destroy();
    header("Location: member-login.php?error=Member not found");
    exit();
}

$member = $result->fetch_assoc();
$stmt->close();

// Handle form submission
$success = '';
$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    require_once 'include/member_profile_handler.php';
    $result = updateMemberProfile($member_id, $_POST, $_FILES);
    
    if ($result['success']) {
        $success = $result['message'];
        // Refresh member data
        $query = "SELECT * FROM registrations WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $member_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $member = $result->fetch_assoc();
        $stmt->close();
    } else {
        $error = $result['message'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<?php include 'head.php'; ?>
<link href="assets/css/member-panel.css" rel="stylesheet">
<body>
    <!-- Header -->
    <?php include 'member-header-v1.2.php'; ?>
    <!-- End Header -->

    <div class="mp-content-wrapper">
        <div class="mp-container">
            <div class="mp-content">
                <div class="mp-flex-between mp-mb-md">
                    <h2 class="mp-page-title"><i class="fas fa-user-edit"></i> Edit Profile</h2>
                    <a href="member-dashboard.php" class="mp-btn mp-btn-outline-primary mp-btn-sm">
                        <i class="fas fa-arrow-left"></i> Back
                            </a>
                </div>

                <?php if ($success): ?>
                    <div class="mp-alert mp-alert-success">
                        <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="mp-alert mp-alert-danger">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data" id="profileEditForm">
                    <div class="mp-profile-edit-grid">
                        <!-- Personal Information -->
                        <div class="mp-card mp-mb-md">
                            <div class="mp-card-header mp-card-header-primary">
                                <h5><i class="fas fa-user"></i> Personal Information</h5>
                                </div>
                            <div class="mp-card-body">
                                    <div class="mp-form-group">
                                        <label for="fullname" class="mp-form-label">Full Name <span style="color: var(--mp-danger);">*</span></label>
                                        <input type="text" class="mp-form-control" id="fullname" name="fullname" 
                                               value="<?php echo htmlspecialchars($member['fullname']); ?>" required>
                                    </div>

                                    <div class="mp-form-group">
                                        <label for="email" class="mp-form-label">Email <span style="color: var(--mp-danger);">*</span></label>
                                        <input type="email" class="mp-form-control" id="email" name="email" 
                                               value="<?php echo htmlspecialchars($member['email']); ?>" required>
                                        <small style="color: var(--mp-gray-500); font-size: 0.75rem;">Email cannot be changed. Contact admin if needed.</small>
                                    </div>

                                    <div class="mp-form-group">
                                        <label for="phone" class="mp-form-label">Phone <span style="color: var(--mp-danger);">*</span></label>
                                        <input type="tel" class="mp-form-control" id="phone" name="phone" 
                                               value="<?php echo htmlspecialchars($member['phone']); ?>" required>
                                    </div>

                                    <div class="mp-form-group">
                                        <label for="sex" class="mp-form-label">Sex <span style="color: var(--mp-danger);">*</span></label>
                                        <select class="mp-form-control" id="sex" name="sex" required>
                                            <option value="Male" <?php echo $member['sex'] == 'Male' ? 'selected' : ''; ?>>Male</option>
                                            <option value="Female" <?php echo $member['sex'] == 'Female' ? 'selected' : ''; ?>>Female</option>
                                        </select>
                                    </div>

                                    <div class="mp-form-group">
                                        <label for="address" class="mp-form-label">Address <span style="color: var(--mp-danger);">*</span></label>
                                        <textarea class="mp-form-control" id="address" name="address" rows="3" required><?php echo htmlspecialchars($member['address']); ?></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Qualification & Photo -->
                        <div class="mp-card mp-mb-md">
                            <div class="mp-card-header mp-card-header-success">
                                <h5><i class="fas fa-graduation-cap"></i> Qualification & Photo</h5>
                                </div>
                            <div class="mp-card-body">
                                    <div class="mp-form-group">
                                        <label for="qualification" class="mp-form-label">Qualification <span style="color: var(--mp-danger);">*</span></label>
                                        <input type="text" class="mp-form-control" id="qualification" name="qualification" 
                                               value="<?php echo htmlspecialchars($member['qualification']); ?>" required>
                                    </div>

                                    <?php if (!empty($member['graduation_date'])): ?>
                                    <div class="mp-form-group">
                                        <label for="graduation_date" class="mp-form-label">Graduation Date</label>
                                        <input type="date" class="mp-form-control" id="graduation_date" name="graduation_date" 
                                               value="<?php echo htmlspecialchars($member['graduation_date']); ?>">
                                    </div>
                                    <?php endif; ?>

                                    <div class="mp-form-group">
                                        <label for="photo" class="mp-form-label">Profile Photo</label>
                                        <?php if (!empty($member['photo'])): ?>
                                            <div id="photo-preview-wrapper" style="margin-bottom: var(--mp-space-md);">
                                                <img id="photo-preview" src="<?php echo htmlspecialchars($member['photo']); ?>" 
                                                     alt="Current Photo" 
                                                     style="width: 120px; height: 120px; border-radius: 50%; border: 3px solid var(--mp-primary); object-fit: cover; display: block; background: var(--mp-gray-100);">
                                            </div>
                                        <?php else: ?>
                                            <div id="photo-preview-wrapper" style="margin-bottom: var(--mp-space-md); display: none;">
                                                <img id="photo-preview" src="" alt="Photo Preview" 
                                                     style="width: 120px; height: 120px; border-radius: 50%; border: 3px solid var(--mp-primary); object-fit: cover; display: block; background: var(--mp-gray-100);">
                                            </div>
                                        <?php endif; ?>
                                        <input type="file" class="mp-form-control" id="photo" name="photo" accept="image/jpeg,image/jpg,image/png,image/webp">
                                        <small style="color: var(--mp-gray-500); font-size: 0.75rem; display: block; margin-top: var(--mp-space-xs);">Accepted formats: JPG, PNG, WEBP. Max size: 2MB</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="mp-card">
                        <div class="mp-card-body">
                            <div class="mp-flex-between">
                                <a href="member-dashboard.php" class="mp-btn mp-btn-outline">
                                            <i class="fas fa-times"></i> Cancel
                                        </a>
                                <button type="submit" class="mp-btn mp-btn-primary">
                                            <i class="fas fa-save"></i> Save Changes
                                        </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>


    <!-- Scripts -->
    <script src="assets/js/jquery-1.12.4.min.js"></script>
    <script src="assets/js/bootstrap.min.js"></script>
    
    <script>
        // Form validation
        document.getElementById('profileEditForm').addEventListener('submit', function(e) {
            const phone = document.getElementById('phone').value;
            const phoneRegex = /^[0-9+\-\s()]+$/;
            
            if (!phoneRegex.test(phone)) {
                e.preventDefault();
                alert('Please enter a valid phone number');
                return false;
            }
        });

        // Image preview
        document.getElementById('photo').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                if (file.size > 2 * 1024 * 1024) {
                    alert('Image size must be less than 2MB');
                    e.target.value = '';
                    return;
                }
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    const photoPreviewWrapper = document.getElementById('photo-preview-wrapper');
                    const photoPreview = document.getElementById('photo-preview');
                    
                    if (photoPreviewWrapper && photoPreview) {
                        photoPreview.src = e.target.result;
                        photoPreviewWrapper.style.display = 'block';
                    }
                };
                reader.readAsDataURL(file);
            }
        });
    </script>

</body>
</html>

