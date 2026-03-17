<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: auth-login.php");
    exit();
}

include 'include/conn.php';

$success = '';
$error = '';
$partner = null;

// Get partner data
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $partner_id = (int)$_GET['id'];
    
    $stmt = $conn->prepare("SELECT * FROM partners WHERE id = ?");
    $stmt->bind_param("i", $partner_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $partner = $result->fetch_assoc();
    } else {
        header("Location: partners_list.php?error=Partner not found");
        exit();
    }
    $stmt->close();
} else {
    header("Location: partners_list.php?error=Invalid partner ID");
    exit();
}

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
            // Update partner in database (only the fields we need)
            $stmt = $conn->prepare("UPDATE partners SET name = ?, logo_url = ?, sort_order = ? WHERE id = ?");
            $stmt->bind_param("ssi", $name, $logo_url, $sort_order, $partner_id);
            
            if ($stmt->execute()) {
                $success = "Partner updated successfully!";
                // Refresh partner data
                $partner['name'] = $name;
                $partner['logo_url'] = $logo_url;
                $partner['sort_order'] = $sort_order;
            } else {
                $error = "Error updating partner: " . $stmt->error;
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

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Edit Partner - Ethio Social Worker</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="A fully responsive admin theme which can be used to build CRM, CMS,ERP etc." name="description" />
    <meta content="Techzaa" name="author" />

    <!-- App favicon -->
    <!-- <link rel="shortcut icon" href="assets/images/favicon.ico"> -->

    <!-- Theme Config Js -->
    <script src="assets/js/config.js"></script>

    <!-- App css -->
    <link href="assets/css/app.min.css" rel="stylesheet" type="text/css" id="app-style" />

    <!-- Icons css -->
    <link href="assets/css/icons.min.css" rel="stylesheet" type="text/css" />
    
    <!-- Design System CSS (UI/UX Enhancements) -->
    <link href="assets/css/design-system.css" rel="stylesheet" type="text/css" />
    
    <!-- Icon System Consistency CSS -->
    <link href="assets/css/icons-consistency.css" rel="stylesheet" type="text/css" />
</head>
<body>
    <!-- Edit Partner Modal -->
    <div class="modal fade" id="editPartnerModal" tabindex="-1" aria-labelledby="editPartnerModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editPartnerModalLabel">Edit Partner</h5>
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

                    <form id="editPartnerForm" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="id" value="<?php echo $partner_id; ?>">
                        
                        <div class="mb-3">
                            <label for="name" class="form-label">Partner Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($partner['name']); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="logo_file" class="form-label">Logo Image</label>
                            <input type="file" class="form-control" id="logo_file" name="logo_file" accept="image/*">
                            <div class="form-text">Upload new partner logo. Recommended size: 200x100px</div>
                        </div>
                        
                        <?php if (!empty($partner['logo_url'])): ?>
                        <div class="mb-3">
                            <label class="form-label">Current Logo</label>
                            <div class="d-flex align-items-center gap-3">
                                <?php if (strpos($partner['logo_url'], 'http') === 0): ?>
                                    <img src="<?php echo htmlspecialchars($partner['logo_url']); ?>" alt="Current Logo" style="max-width: 100px; max-height: 60px; object-fit: contain;">
                                <?php else: ?>
                                    <img src="../<?php echo htmlspecialchars($partner['logo_url']); ?>" alt="Current Logo" style="max-width: 100px; max-height: 60px; object-fit: contain;">
                                <?php endif; ?>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="remove_logo" id="remove_logo">
                                    <label class="form-check-label" for="remove_logo">
                                        Remove current logo
                                    </label>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="status" class="form-label fw-bold">Status</label>
                                    <select class="form-select" id="status" name="status">
                                        <option value="active" <?php echo $partner['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                        <option value="inactive" <?php echo $partner['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                    </select>
                                    <div class="form-text">Active partners will be displayed</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="sort_order" class="form-label fw-bold">Sort Order</label>
                                    <input type="number" class="form-control" id="sort_order" name="sort_order" value="<?php echo (int)$partner['sort_order']; ?>" min="0">
                                    <div class="form-text">Lower numbers appear first</div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" form="editPartnerForm" class="btn btn-primary">Update Partner</button>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/vendor.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Handle form submission via AJAX
        const form = document.getElementById('editPartnerForm');
        if (form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const submitBtn = this.querySelector('button[type="submit"]');
                if (!submitBtn) {
                    console.error('Submit button not found');
                    return;
                }
                
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Updating...';
                submitBtn.disabled = true;
                
                const formData = new FormData(this);
                
                fetch('edit_partner.php', {
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
                        const modalElement = document.getElementById('editPartnerModal');
                        if (modalElement) {
                            const modal = bootstrap.Modal.getInstance(modalElement);
                            if (modal) {
                                modal.hide();
                            }
                        }
                        
                        setTimeout(() => {
                            window.location.href = 'partners_list.php?success=' + encodeURIComponent(data.message);
                        }, 500);
                    } else {
                        // Show error message
                        const modalBody = document.querySelector('#editPartnerModal .modal-body');
                        if (modalBody) {
                            let alertHtml = '<div class="alert alert-danger alert-dismissible fade show" role="alert">';
                            alertHtml += data.error;
                            alertHtml += '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
                            alertHtml += '</div>';
                            
                            modalBody.insertAdjacentHTML('afterbegin', alertHtml);
                        }
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred. Please try again.');
                })
                .finally(() => {
                    if (submitBtn) {
                        submitBtn.innerHTML = originalText;
                        submitBtn.disabled = false;
                    }
                });
            });
        }
        
        // Auto-open modal if editing
        const modalElement = document.getElementById('editPartnerModal');
        if (modalElement) {
            const modal = new bootstrap.Modal(modalElement);
            modal.show();
        }
    });
    </script>
</body>
</html>
