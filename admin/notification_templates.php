<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: auth-login.php");
    exit();
}

include 'include/conn.php';
include 'header.php';

$success_message = '';
$error_message = '';

// Handle template actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_template'])) {
        $name = trim($_POST['name'] ?? '');
        $type = trim($_POST['type'] ?? '');
        $subject = trim($_POST['subject'] ?? '');
        $body = trim($_POST['body'] ?? '');
        $is_active = $_POST['is_active'] ?? '0';
        
        // Check if table exists
        $table_exists = false;
        $result = $conn->query("SHOW TABLES LIKE 'notification_templates'");
        if ($result && $result->num_rows > 0) {
            $table_exists = true;
        }
        
        if ($table_exists && !empty($name) && !empty($type) && !empty($subject) && !empty($body)) {
            $stmt = $conn->prepare("INSERT INTO notification_templates (name, type, subject, body, is_active, created_by, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
            $user_id = $_SESSION['user_id'];
            $stmt->bind_param("ssssii", $name, $type, $subject, $body, $is_active, $user_id);
            
            if ($stmt->execute()) {
                $success_message = "Template created successfully";
            } else {
                $error_message = "Failed to create template";
            }
            $stmt->close();
        }
    } elseif (isset($_POST['update_template'])) {
        $template_id = intval($_POST['template_id']);
        $name = trim($_POST['name'] ?? '');
        $type = trim($_POST['type'] ?? '');
        $subject = trim($_POST['subject'] ?? '');
        $body = trim($_POST['body'] ?? '');
        $is_active = $_POST['is_active'] ?? '0';
        
        $table_exists = false;
        $result = $conn->query("SHOW TABLES LIKE 'notification_templates'");
        if ($result && $result->num_rows > 0) {
            $table_exists = true;
        }
        
        if ($table_exists && !empty($name) && !empty($type) && !empty($subject) && !empty($body)) {
            $stmt = $conn->prepare("UPDATE notification_templates SET name = ?, type = ?, subject = ?, body = ?, is_active = ?, updated_at = NOW() WHERE id = ?");
            $stmt->bind_param("ssssii", $name, $type, $subject, $body, $is_active, $template_id);
            
            if ($stmt->execute()) {
                $success_message = "Template updated successfully";
            } else {
                $error_message = "Failed to update template";
            }
            $stmt->close();
        }
    } elseif (isset($_POST['delete_template'])) {
        $template_id = intval($_POST['template_id']);
        
        $table_exists = false;
        $result = $conn->query("SHOW TABLES LIKE 'notification_templates'");
        if ($result && $result->num_rows > 0) {
            $table_exists = true;
        }
        
        if ($table_exists) {
            $stmt = $conn->prepare("DELETE FROM notification_templates WHERE id = ?");
            $stmt->bind_param("i", $template_id);
            
            if ($stmt->execute()) {
                $success_message = "Template deleted successfully";
            } else {
                $error_message = "Failed to delete template";
            }
            $stmt->close();
        }
    }
}

// Check if table exists
$table_exists = false;
$result = $conn->query("SHOW TABLES LIKE 'notification_templates'");
if ($result && $result->num_rows > 0) {
    $table_exists = true;
}

$templates = [];
$stats = [
    'total' => 0,
    'active' => 0,
    'inactive' => 0
];

if ($table_exists) {
    // Get templates
    $stmt = $conn->prepare("SELECT t.*, u.username as created_by_name 
                            FROM notification_templates t 
                            LEFT JOIN user u ON t.created_by = u.id 
                            ORDER BY t.created_at DESC");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $templates[] = $row;
    }
    $stmt->close();
    
    // Statistics
    $stats['total'] = count($templates);
    $stats['active'] = $conn->query("SELECT COUNT(*) as total FROM notification_templates WHERE is_active = 1")->fetch_assoc()['total'] ?? 0;
    $stats['inactive'] = $stats['total'] - $stats['active'];
}

// Get template for editing
$edit_template = null;
if (isset($_GET['edit']) && $table_exists) {
    $edit_id = intval($_GET['edit']);
    $stmt = $conn->prepare("SELECT * FROM notification_templates WHERE id = ?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $edit_template = $result->fetch_assoc();
    }
    $stmt->close();
}

// Common notification types
$notification_types = ['membership', 'event', 'research', 'resource', 'system', 'payment', 'approval', 'general'];
?>

<!DOCTYPE html>
<html lang="en">

<body>
    <div class="wrapper">
        <?php include 'sidebar.php'; ?>

        <div class="content-page">
            <div class="content">
                <div class="container-fluid">
                    <!-- Page Title -->
                    <div class="row">
                        <div class="col-12">
                            <div class="page-title-box d-flex justify-content-between align-items-center">
                                <h4 class="page-title">Notification Templates</h4>
                                <div>
                                    <a href="notifications_center.php" class="btn btn-secondary me-2">
                                        <i class="ri-dashboard-line"></i> Dashboard
                                    </a>
                                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTemplateModal">
                                        <i class="ri-add-line"></i> Add Template
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php
                    if ($success_message) {
                        echo '<div class="alert alert-success alert-dismissible fade show" role="alert">';
                        echo htmlspecialchars($success_message);
                        echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
                        echo '</div>';
                    }
                    if ($error_message) {
                        echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">';
                        echo htmlspecialchars($error_message);
                        echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
                        echo '</div>';
                    }
                    ?>

                    <?php if (!$table_exists): ?>
                        <!-- Table Not Found Alert -->
                        <div class="alert alert-warning" role="alert">
                            <h5 class="alert-heading"><i class="ri-alert-line"></i> Notification Templates Table Not Found</h5>
                            <p>The <code>notification_templates</code> table does not exist in the database.</p>
                            <p class="mb-0"><strong>Required Table Structure:</strong></p>
                            <pre class="mt-2 mb-0"><code>CREATE TABLE `notification_templates` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `type` VARCHAR(50) NOT NULL,
  `subject` VARCHAR(255) NOT NULL,
  `body` TEXT NOT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_by` INT(11) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_type` (`type`),
  INDEX `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;</code></pre>
                        </div>
                    <?php else: ?>

                    <!-- Statistics Cards -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <div class="card bg-primary bg-opacity-10 border-primary">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-grow-1">
                                            <h6 class="text-muted mb-1">Total Templates</h6>
                                            <h3 class="mb-0"><?php echo number_format($stats['total']); ?></h3>
                                        </div>
                                        <i class="ri-file-text-line fs-1 text-primary opacity-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-success bg-opacity-10 border-success">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-grow-1">
                                            <h6 class="text-muted mb-1">Active</h6>
                                            <h3 class="mb-0"><?php echo number_format($stats['active']); ?></h3>
                                        </div>
                                        <i class="ri-checkbox-circle-line fs-1 text-success opacity-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-warning bg-opacity-10 border-warning">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-grow-1">
                                            <h6 class="text-muted mb-1">Inactive</h6>
                                            <h3 class="mb-0"><?php echo number_format($stats['inactive']); ?></h3>
                                        </div>
                                        <i class="ri-file-edit-line fs-1 text-warning opacity-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Templates List -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Notification Templates</h4>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table id="templatesTable" class="table table-striped table-bordered dt-responsive nowrap w-100">
                                            <thead>
                                                <tr>
                                                    <th>Name</th>
                                                    <th>Type</th>
                                                    <th>Subject</th>
                                                    <th>Status</th>
                                                    <th>Created By</th>
                                                    <th>Created</th>
                                                    <th>Updated</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($templates as $template): ?>
                                                    <tr>
                                                        <td><strong><?php echo htmlspecialchars($template['name']); ?></strong></td>
                                                        <td><span class="badge bg-info"><?php echo htmlspecialchars(ucfirst($template['type'])); ?></span></td>
                                                        <td><?php echo htmlspecialchars($template['subject']); ?></td>
                                                        <td>
                                                            <span class="badge bg-<?php echo $template['is_active'] ? 'success' : 'secondary'; ?>">
                                                                <?php echo $template['is_active'] ? 'Active' : 'Inactive'; ?>
                                                            </span>
                                                        </td>
                                                        <td><?php echo htmlspecialchars($template['created_by_name'] ?? 'Unknown'); ?></td>
                                                        <td><?php echo date('M d, Y', strtotime($template['created_at'])); ?></td>
                                                        <td><?php echo $template['updated_at'] ? date('M d, Y', strtotime($template['updated_at'])) : '-'; ?></td>
                                                        <td>
                                                            <a href="?edit=<?php echo $template['id']; ?>" class="btn btn-sm btn-primary">
                                                                <i class="ri-edit-line"></i> Edit
                                                            </a>
                                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this template?');">
                                                                <input type="hidden" name="template_id" value="<?php echo $template['id']; ?>">
                                                                <button type="submit" name="delete_template" class="btn btn-sm btn-danger">
                                                                    <i class="ri-delete-bin-line"></i> Delete
                                                                </button>
                                                            </form>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Add/Edit Template Modal -->
                    <div class="modal fade" id="addTemplateModal" tabindex="-1">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <form method="POST">
                                    <div class="modal-header">
                                        <h5 class="modal-title"><?php echo $edit_template ? 'Edit Template' : 'Add Template'; ?></h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <?php if ($edit_template): ?>
                                            <input type="hidden" name="template_id" value="<?php echo $edit_template['id']; ?>">
                                        <?php endif; ?>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Template Name</label>
                                            <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($edit_template['name'] ?? ''); ?>" required>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Notification Type</label>
                                            <select name="type" class="form-select" required>
                                                <option value="">Select Type...</option>
                                                <?php foreach ($notification_types as $type): ?>
                                                    <option value="<?php echo $type; ?>" <?php echo ($edit_template['type'] ?? '') == $type ? 'selected' : ''; ?>>
                                                        <?php echo ucfirst($type); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Subject</label>
                                            <input type="text" name="subject" class="form-control" value="<?php echo htmlspecialchars($edit_template['subject'] ?? ''); ?>" required>
                                            <small class="text-muted">Use variables: {member_name}, {title}, {date}, etc.</small>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Body</label>
                                            <textarea name="body" class="form-control" rows="10" required><?php echo htmlspecialchars($edit_template['body'] ?? ''); ?></textarea>
                                            <small class="text-muted">HTML is supported. Use variables: {member_name}, {title}, {message}, {date}, {link}, etc.</small>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1"
                                                       <?php echo ($edit_template['is_active'] ?? 1) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="is_active">
                                                    Active
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" name="<?php echo $edit_template ? 'update_template' : 'add_template'; ?>" class="btn btn-primary">
                                            <?php echo $edit_template ? 'Update Template' : 'Create Template'; ?>
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <?php if ($edit_template): ?>
                    <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            var modal = new bootstrap.Modal(document.getElementById('addTemplateModal'));
                            modal.show();
                        });
                    </script>
                    <?php endif; ?>

                    <?php endif; ?>

                </div>
            </div>

            <?php include 'footer.php'; ?>
        </div>
    </div>

    <!-- Vendor js -->
    <script src="assets/js/vendor.min.js"></script>
    
    <!-- DataTables js -->
    <script src="assets/vendor/datatables.net/js/jquery.dataTables.min.js"></script>
    <script src="assets/vendor/datatables.net-bs5/js/dataTables.bootstrap5.min.js"></script>
    <script src="assets/vendor/datatables.net-responsive/js/dataTables.responsive.min.js"></script>
    <script src="assets/vendor/datatables.net-responsive-bs5/js/responsive.bootstrap5.min.js"></script>
    
    <!-- App js -->
    <script src="assets/js/app.min.js"></script>

    <script>
        $(document).ready(function() {
            $('#templatesTable').DataTable({
                responsive: true,
                order: [[5, 'desc']],
                pageLength: 25
            });
        });
    </script>

</body>
</html>

