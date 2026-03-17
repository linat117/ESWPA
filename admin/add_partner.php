<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: auth-login.php");
    exit();
}

include 'include/conn.php';

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $sort_order = (int)($_POST['sort_order'] ?? 0);
    $logo_url = trim($_POST['logo_url'] ?? '');
    
    // Handle file upload for logo
    if (isset($_FILES['logo_file']) && $_FILES['logo_file']['error'] === UPLOAD_ERR_OK) {
        $file_name = $_FILES['logo_file']['name'];
        $file_tmp = $_FILES['logo_file']['tmp_name'];
        $file_size = $_FILES['logo_file']['size'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        // Allowed extensions
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp'];
        
        if (in_array($file_ext, $allowed_extensions)) {
            // Create upload directory if it doesn't exist
            $upload_dir = '../uploads/partners/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            // Generate unique filename
            $new_filename = 'partner_' . time() . '_' . uniqid() . '.' . $file_ext;
            $upload_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($file_tmp, $upload_path)) {
                $logo_url = 'uploads/partners/' . $new_filename;
            } else {
                $error = "Failed to upload logo file.";
            }
        } else {
            $error = "Invalid file type. Allowed types: " . implode(', ', $allowed_extensions);
        }
    }
    
    if (empty($error)) {
        if (empty($name)) {
            $error = "Partner name is required.";
        } else {
            // Insert partner into database (only the fields we need)
            $stmt = $conn->prepare("INSERT INTO partners (name, logo_url, sort_order) VALUES (?, ?, ?)");
            $stmt->bind_param("ssi", $name, $logo_url, $sort_order);
            
            if ($stmt->execute()) {
                $success = "Partner added successfully!";
                // Clear form data
                $name = '';
                $sort_order = 0;
            } else {
                $error = "Error adding partner: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}

// If this is an AJAX request, return JSON response
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    header('Content-Type: application/json');
    echo json_encode(['success' => !empty($success), 'error' => $error, 'message' => $success ?: $error]);
    exit();
}

// If not AJAX and form was submitted successfully, redirect to partners list
if (!empty($success)) {
    header("Location: partners_list.php?success=" . urlencode($success));
    exit();
}
?>

<!-- Add Partner Modal -->
<div class="modal fade" id="addPartnerModal" tabindex="-1" aria-labelledby="addPartnerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addPartnerModalLabel">Add New Partner</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Success/Error Messages -->
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form id="addPartnerForm" method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="name" class="form-label">Partner Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($name ?? ''); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="logo_file" class="form-label">Logo Image</label>
                        <input type="file" class="form-control" id="logo_file" name="logo_file" accept="image/*">
                        <div class="form-text">Upload partner logo. Recommended size: 200x100px</div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="status" class="form-label fw-bold">Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="active" <?php echo ($status ?? 'active') === 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="inactive" <?php echo ($status ?? '') === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                </select>
                                <div class="form-text">Active partners will be displayed</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="sort_order" class="form-label fw-bold">Sort Order</label>
                                <input type="number" class="form-control" id="sort_order" name="sort_order" value="<?php echo htmlspecialchars($sort_order ?? 0); ?>" min="0">
                                <div class="form-text">Lower numbers appear first</div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" form="addPartnerForm" class="btn btn-primary">Add Partner</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle form submission via AJAX
    const form = document.getElementById('addPartnerForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(form);
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            
            // Show loading state
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Adding...';
            submitBtn.disabled = true;
            
            fetch('add_partner.php', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Close modal and refresh page
                    const modal = bootstrap.Modal.getInstance(document.getElementById('addPartnerModal'));
                    modal.hide();
                    
                    // Show success message and redirect
                    setTimeout(() => {
                        window.location.href = 'partners_list.php?success=' + encodeURIComponent(data.message);
                    }, 500);
                } else {
                    // Show error message
                    let alertHtml = '<div class="alert alert-danger alert-dismissible fade show" role="alert">';
                    alertHtml += data.error;
                    alertHtml += '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
                    alertHtml += '</div>';
                    
                    const modalBody = document.querySelector('#addPartnerModal .modal-body');
                    modalBody.insertAdjacentHTML('afterbegin', alertHtml);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            })
            .finally(() => {
                // Reset button state
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            });
        });
    }
});
</script>
