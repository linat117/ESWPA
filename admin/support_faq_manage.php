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

// Handle FAQ actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_faq'])) {
        $question = trim($_POST['question'] ?? '');
        $answer = trim($_POST['answer'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $display_order = intval($_POST['display_order'] ?? 0);
        $status = $_POST['status'] ?? 'published';
        
        $table_exists = false;
        $result = $conn->query("SHOW TABLES LIKE 'support_faq'");
        if ($result && $result->num_rows > 0) {
            $table_exists = true;
        }
        
        if ($table_exists && !empty($question) && !empty($answer)) {
            $stmt = $conn->prepare("INSERT INTO support_faq (question, answer, category, display_order, status, created_by, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
            $user_id = $_SESSION['user_id'];
            $stmt->bind_param("sssisi", $question, $answer, $category, $display_order, $status, $user_id);
            
            if ($stmt->execute()) {
                $success_message = "FAQ added successfully";
            } else {
                $error_message = "Failed to add FAQ";
            }
            $stmt->close();
        }
    } elseif (isset($_POST['update_faq'])) {
        $faq_id = intval($_POST['faq_id']);
        $question = trim($_POST['question'] ?? '');
        $answer = trim($_POST['answer'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $display_order = intval($_POST['display_order'] ?? 0);
        $status = $_POST['status'] ?? 'published';
        
        $table_exists = false;
        $result = $conn->query("SHOW TABLES LIKE 'support_faq'");
        if ($result && $result->num_rows > 0) {
            $table_exists = true;
        }
        
        if ($table_exists && !empty($question) && !empty($answer)) {
            $stmt = $conn->prepare("UPDATE support_faq SET question = ?, answer = ?, category = ?, display_order = ?, status = ?, updated_at = NOW() WHERE id = ?");
            $stmt->bind_param("sssisi", $question, $answer, $category, $display_order, $status, $faq_id);
            
            if ($stmt->execute()) {
                $success_message = "FAQ updated successfully";
            } else {
                $error_message = "Failed to update FAQ";
            }
            $stmt->close();
        }
    } elseif (isset($_POST['delete_faq'])) {
        $faq_id = intval($_POST['faq_id']);
        
        $table_exists = false;
        $result = $conn->query("SHOW TABLES LIKE 'support_faq'");
        if ($result && $result->num_rows > 0) {
            $table_exists = true;
        }
        
        if ($table_exists) {
            $stmt = $conn->prepare("DELETE FROM support_faq WHERE id = ?");
            $stmt->bind_param("i", $faq_id);
            
            if ($stmt->execute()) {
                $success_message = "FAQ deleted successfully";
            } else {
                $error_message = "Failed to delete FAQ";
            }
            $stmt->close();
        }
    }
}

// Check if table exists
$table_exists = false;
$result = $conn->query("SHOW TABLES LIKE 'support_faq'");
if ($result && $result->num_rows > 0) {
    $table_exists = true;
}

$faqs = [];
$categories = [];
$stats = [
    'total' => 0,
    'published' => 0,
    'draft' => 0
];

if ($table_exists) {
    // Get FAQs
    $stmt = $conn->prepare("SELECT f.*, u.username as created_by_name 
                            FROM support_faq f 
                            LEFT JOIN user u ON f.created_by = u.id 
                            ORDER BY f.display_order ASC, f.created_at DESC");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $faqs[] = $row;
    }
    $stmt->close();
    
    // Get categories
    $category_result = $conn->query("SELECT DISTINCT category FROM support_faq WHERE category IS NOT NULL AND category != '' ORDER BY category");
    while ($row = $category_result->fetch_assoc()) {
        $categories[] = $row['category'];
    }
    
    // Statistics
    $stats['total'] = count($faqs);
    $stats['published'] = $conn->query("SELECT COUNT(*) as total FROM support_faq WHERE status = 'published'")->fetch_assoc()['total'] ?? 0;
    $stats['draft'] = $conn->query("SELECT COUNT(*) as total FROM support_faq WHERE status = 'draft'")->fetch_assoc()['total'] ?? 0;
}

// Get FAQ for editing
$edit_faq = null;
if (isset($_GET['edit']) && $table_exists) {
    $edit_id = intval($_GET['edit']);
    $stmt = $conn->prepare("SELECT * FROM support_faq WHERE id = ?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $edit_faq = $result->fetch_assoc();
    }
    $stmt->close();
}
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
                                <h4 class="page-title">FAQ Management</h4>
                                <div>
                                    <a href="support_dashboard.php" class="btn btn-secondary me-2">
                                        <i class="ri-dashboard-line"></i> Dashboard
                                    </a>
                                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addFaqModal">
                                        <i class="ri-add-line"></i> Add FAQ
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
                            <h5 class="alert-heading"><i class="ri-alert-line"></i> FAQ Table Not Found</h5>
                            <p>The <code>support_faq</code> table does not exist in the database.</p>
                            <p class="mb-0"><strong>Required Table Structure:</strong></p>
                            <pre class="mt-2 mb-0"><code>CREATE TABLE `support_faq` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `question` TEXT NOT NULL,
  `answer` TEXT NOT NULL,
  `category` VARCHAR(100) NULL,
  `display_order` INT(11) NOT NULL DEFAULT 0,
  `status` ENUM('draft','published','archived') NOT NULL DEFAULT 'draft',
  `views` INT(11) NOT NULL DEFAULT 0,
  `created_by` INT(11) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_status` (`status`),
  INDEX `idx_category` (`category`),
  INDEX `idx_display_order` (`display_order`)
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
                                            <h6 class="text-muted mb-1">Total FAQs</h6>
                                            <h3 class="mb-0"><?php echo number_format($stats['total']); ?></h3>
                                        </div>
                                        <i class="ri-question-line fs-1 text-primary opacity-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-success bg-opacity-10 border-success">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-grow-1">
                                            <h6 class="text-muted mb-1">Published</h6>
                                            <h3 class="mb-0"><?php echo number_format($stats['published']); ?></h3>
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
                                            <h6 class="text-muted mb-1">Drafts</h6>
                                            <h3 class="mb-0"><?php echo number_format($stats['draft']); ?></h3>
                                        </div>
                                        <i class="ri-file-edit-line fs-1 text-warning opacity-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- FAQs List -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Frequently Asked Questions</h4>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table id="faqsTable" class="table table-striped table-bordered dt-responsive nowrap w-100">
                                            <thead>
                                                <tr>
                                                    <th width="50">Order</th>
                                                    <th>Question</th>
                                                    <th>Category</th>
                                                    <th>Status</th>
                                                    <th>Views</th>
                                                    <th>Created</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($faqs as $faq): ?>
                                                    <tr>
                                                        <td><?php echo $faq['display_order']; ?></td>
                                                        <td><strong><?php echo htmlspecialchars($faq['question']); ?></strong></td>
                                                        <td><?php echo htmlspecialchars($faq['category'] ?? 'Uncategorized'); ?></td>
                                                        <td>
                                                            <span class="badge bg-<?php 
                                                                echo $faq['status'] == 'published' ? 'success' : 
                                                                    ($faq['status'] == 'draft' ? 'warning' : 'secondary'); 
                                                            ?>">
                                                                <?php echo ucfirst($faq['status']); ?>
                                                            </span>
                                                        </td>
                                                        <td><?php echo number_format($faq['views'] ?? 0); ?></td>
                                                        <td><?php echo date('M d, Y', strtotime($faq['created_at'])); ?></td>
                                                        <td>
                                                            <a href="?edit=<?php echo $faq['id']; ?>" class="btn btn-sm btn-primary">
                                                                <i class="ri-edit-line"></i> Edit
                                                            </a>
                                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this FAQ?');">
                                                                <input type="hidden" name="faq_id" value="<?php echo $faq['id']; ?>">
                                                                <button type="submit" name="delete_faq" class="btn btn-sm btn-danger">
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

                    <!-- Add/Edit FAQ Modal -->
                    <div class="modal fade" id="addFaqModal" tabindex="-1">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <form method="POST">
                                    <div class="modal-header">
                                        <h5 class="modal-title"><?php echo $edit_faq ? 'Edit FAQ' : 'Add FAQ'; ?></h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <?php if ($edit_faq): ?>
                                            <input type="hidden" name="faq_id" value="<?php echo $edit_faq['id']; ?>">
                                        <?php endif; ?>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Question</label>
                                            <input type="text" name="question" class="form-control" value="<?php echo htmlspecialchars($edit_faq['question'] ?? ''); ?>" required>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Answer</label>
                                            <textarea name="answer" class="form-control" rows="6" required><?php echo htmlspecialchars($edit_faq['answer'] ?? ''); ?></textarea>
                                            <small class="text-muted">HTML is supported</small>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Category</label>
                                                    <input type="text" name="category" class="form-control" value="<?php echo htmlspecialchars($edit_faq['category'] ?? ''); ?>" list="categories">
                                                    <datalist id="categories">
                                                        <?php foreach ($categories as $cat): ?>
                                                            <option value="<?php echo htmlspecialchars($cat); ?>">
                                                        <?php endforeach; ?>
                                                    </datalist>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="mb-3">
                                                    <label class="form-label">Display Order</label>
                                                    <input type="number" name="display_order" class="form-control" value="<?php echo $edit_faq['display_order'] ?? 0; ?>" min="0">
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="mb-3">
                                                    <label class="form-label">Status</label>
                                                    <select name="status" class="form-select" required>
                                                        <option value="draft" <?php echo ($edit_faq['status'] ?? 'draft') == 'draft' ? 'selected' : ''; ?>>Draft</option>
                                                        <option value="published" <?php echo ($edit_faq['status'] ?? '') == 'published' ? 'selected' : ''; ?>>Published</option>
                                                        <option value="archived" <?php echo ($edit_faq['status'] ?? '') == 'archived' ? 'selected' : ''; ?>>Archived</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" name="<?php echo $edit_faq ? 'update_faq' : 'add_faq'; ?>" class="btn btn-primary">
                                            <?php echo $edit_faq ? 'Update FAQ' : 'Add FAQ'; ?>
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <?php if ($edit_faq): ?>
                    <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            var modal = new bootstrap.Modal(document.getElementById('addFaqModal'));
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
            $('#faqsTable').DataTable({
                responsive: true,
                order: [[0, 'asc']],
                pageLength: 25
            });
        });
    </script>

</body>
</html>

