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

// Handle article actions (create/update/delete)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_article'])) {
        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $status = $_POST['status'] ?? 'published';
        
        // Check if table exists
        $table_exists = false;
        $result = $conn->query("SHOW TABLES LIKE 'support_knowledge_base'");
        if ($result && $result->num_rows > 0) {
            $table_exists = true;
        }
        
        if ($table_exists && !empty($title) && !empty($content)) {
            $stmt = $conn->prepare("INSERT INTO support_knowledge_base (title, content, category, status, created_by, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
            $user_id = $_SESSION['user_id'];
            $stmt->bind_param("ssssi", $title, $content, $category, $status, $user_id);
            
            if ($stmt->execute()) {
                $success_message = "Article created successfully";
            } else {
                $error_message = "Failed to create article";
            }
            $stmt->close();
        }
    } elseif (isset($_POST['update_article'])) {
        $article_id = intval($_POST['article_id']);
        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $status = $_POST['status'] ?? 'published';
        
        $table_exists = false;
        $result = $conn->query("SHOW TABLES LIKE 'support_knowledge_base'");
        if ($result && $result->num_rows > 0) {
            $table_exists = true;
        }
        
        if ($table_exists && !empty($title) && !empty($content)) {
            $stmt = $conn->prepare("UPDATE support_knowledge_base SET title = ?, content = ?, category = ?, status = ?, updated_at = NOW() WHERE id = ?");
            $stmt->bind_param("ssssi", $title, $content, $category, $status, $article_id);
            
            if ($stmt->execute()) {
                $success_message = "Article updated successfully";
            } else {
                $error_message = "Failed to update article";
            }
            $stmt->close();
        }
    } elseif (isset($_POST['delete_article'])) {
        $article_id = intval($_POST['article_id']);
        
        $table_exists = false;
        $result = $conn->query("SHOW TABLES LIKE 'support_knowledge_base'");
        if ($result && $result->num_rows > 0) {
            $table_exists = true;
        }
        
        if ($table_exists) {
            $stmt = $conn->prepare("DELETE FROM support_knowledge_base WHERE id = ?");
            $stmt->bind_param("i", $article_id);
            
            if ($stmt->execute()) {
                $success_message = "Article deleted successfully";
            } else {
                $error_message = "Failed to delete article";
            }
            $stmt->close();
        }
    }
}

// Check if table exists
$table_exists = false;
$result = $conn->query("SHOW TABLES LIKE 'support_knowledge_base'");
if ($result && $result->num_rows > 0) {
    $table_exists = true;
}

$articles = [];
$categories = [];
$stats = [
    'total' => 0,
    'published' => 0,
    'draft' => 0,
    'by_category' => []
];

if ($table_exists) {
    // Get articles
    $stmt = $conn->prepare("SELECT kb.*, u.username as created_by_name 
                            FROM support_knowledge_base kb 
                            LEFT JOIN user u ON kb.created_by = u.id 
                            ORDER BY kb.created_at DESC");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $articles[] = $row;
    }
    $stmt->close();
    
    // Get categories
    $category_result = $conn->query("SELECT DISTINCT category FROM support_knowledge_base WHERE category IS NOT NULL AND category != '' ORDER BY category");
    while ($row = $category_result->fetch_assoc()) {
        $categories[] = $row['category'];
    }
    
    // Statistics
    $stats['total'] = count($articles);
    $stats['published'] = $conn->query("SELECT COUNT(*) as total FROM support_knowledge_base WHERE status = 'published'")->fetch_assoc()['total'] ?? 0;
    $stats['draft'] = $conn->query("SELECT COUNT(*) as total FROM support_knowledge_base WHERE status = 'draft'")->fetch_assoc()['total'] ?? 0;
    
    // By category
    $cat_stats_query = "SELECT category, COUNT(*) as count FROM support_knowledge_base WHERE category IS NOT NULL GROUP BY category ORDER BY count DESC";
    $cat_stats_result = $conn->query($cat_stats_query);
    while ($row = $cat_stats_result->fetch_assoc()) {
        $stats['by_category'][] = $row;
    }
}

// Get article for editing
$edit_article = null;
if (isset($_GET['edit']) && $table_exists) {
    $edit_id = intval($_GET['edit']);
    $stmt = $conn->prepare("SELECT * FROM support_knowledge_base WHERE id = ?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $edit_article = $result->fetch_assoc();
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
                                <h4 class="page-title">Knowledge Base</h4>
                                <div>
                                    <a href="support_dashboard.php" class="btn btn-secondary me-2">
                                        <i class="ri-dashboard-line"></i> Dashboard
                                    </a>
                                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addArticleModal">
                                        <i class="ri-add-line"></i> Add Article
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
                            <h5 class="alert-heading"><i class="ri-alert-line"></i> Knowledge Base Table Not Found</h5>
                            <p>The <code>support_knowledge_base</code> table does not exist in the database.</p>
                            <p class="mb-0"><strong>Required Table Structure:</strong></p>
                            <pre class="mt-2 mb-0"><code>CREATE TABLE `support_knowledge_base` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(255) NOT NULL,
  `content` TEXT NOT NULL,
  `category` VARCHAR(100) NULL,
  `status` ENUM('draft','published','archived') NOT NULL DEFAULT 'draft',
  `views` INT(11) NOT NULL DEFAULT 0,
  `created_by` INT(11) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_status` (`status`),
  INDEX `idx_category` (`category`),
  INDEX `idx_created_at` (`created_at`)
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
                                            <h6 class="text-muted mb-1">Total Articles</h6>
                                            <h3 class="mb-0"><?php echo number_format($stats['total']); ?></h3>
                                        </div>
                                        <i class="ri-article-line fs-1 text-primary opacity-50"></i>
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

                    <!-- Articles List -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Knowledge Base Articles</h4>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table id="articlesTable" class="table table-striped table-bordered dt-responsive nowrap w-100">
                                            <thead>
                                                <tr>
                                                    <th>Title</th>
                                                    <th>Category</th>
                                                    <th>Status</th>
                                                    <th>Views</th>
                                                    <th>Created By</th>
                                                    <th>Created</th>
                                                    <th>Updated</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($articles as $article): ?>
                                                    <tr>
                                                        <td><strong><?php echo htmlspecialchars($article['title']); ?></strong></td>
                                                        <td><?php echo htmlspecialchars($article['category'] ?? 'Uncategorized'); ?></td>
                                                        <td>
                                                            <span class="badge bg-<?php 
                                                                echo $article['status'] == 'published' ? 'success' : 
                                                                    ($article['status'] == 'draft' ? 'warning' : 'secondary'); 
                                                            ?>">
                                                                <?php echo ucfirst($article['status']); ?>
                                                            </span>
                                                        </td>
                                                        <td><?php echo number_format($article['views'] ?? 0); ?></td>
                                                        <td><?php echo htmlspecialchars($article['created_by_name'] ?? 'Unknown'); ?></td>
                                                        <td><?php echo date('M d, Y', strtotime($article['created_at'])); ?></td>
                                                        <td><?php echo $article['updated_at'] ? date('M d, Y', strtotime($article['updated_at'])) : '-'; ?></td>
                                                        <td>
                                                            <a href="?edit=<?php echo $article['id']; ?>" class="btn btn-sm btn-primary">
                                                                <i class="ri-edit-line"></i> Edit
                                                            </a>
                                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this article?');">
                                                                <input type="hidden" name="article_id" value="<?php echo $article['id']; ?>">
                                                                <button type="submit" name="delete_article" class="btn btn-sm btn-danger">
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

                    <!-- Add/Edit Article Modal -->
                    <div class="modal fade" id="addArticleModal" tabindex="-1">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <form method="POST">
                                    <div class="modal-header">
                                        <h5 class="modal-title"><?php echo $edit_article ? 'Edit Article' : 'Add Article'; ?></h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <?php if ($edit_article): ?>
                                            <input type="hidden" name="article_id" value="<?php echo $edit_article['id']; ?>">
                                        <?php endif; ?>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Title</label>
                                            <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($edit_article['title'] ?? ''); ?>" required>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Category</label>
                                            <input type="text" name="category" class="form-control" value="<?php echo htmlspecialchars($edit_article['category'] ?? ''); ?>" list="categories">
                                            <datalist id="categories">
                                                <?php foreach ($categories as $cat): ?>
                                                    <option value="<?php echo htmlspecialchars($cat); ?>">
                                                <?php endforeach; ?>
                                            </datalist>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Status</label>
                                            <select name="status" class="form-select" required>
                                                <option value="draft" <?php echo ($edit_article['status'] ?? 'draft') == 'draft' ? 'selected' : ''; ?>>Draft</option>
                                                <option value="published" <?php echo ($edit_article['status'] ?? '') == 'published' ? 'selected' : ''; ?>>Published</option>
                                                <option value="archived" <?php echo ($edit_article['status'] ?? '') == 'archived' ? 'selected' : ''; ?>>Archived</option>
                                            </select>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Content</label>
                                            <textarea name="content" class="form-control" rows="10" required><?php echo htmlspecialchars($edit_article['content'] ?? ''); ?></textarea>
                                            <small class="text-muted">HTML is supported</small>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" name="<?php echo $edit_article ? 'update_article' : 'add_article'; ?>" class="btn btn-primary">
                                            <?php echo $edit_article ? 'Update Article' : 'Create Article'; ?>
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <?php if ($edit_article): ?>
                    <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            var modal = new bootstrap.Modal(document.getElementById('addArticleModal'));
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
            $('#articlesTable').DataTable({
                responsive: true,
                order: [[5, 'desc']],
                pageLength: 25
            });
        });
    </script>

</body>
</html>

