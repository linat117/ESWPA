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

// Create table if not exists
$create_sql = "CREATE TABLE IF NOT EXISTS `research_categories` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `display_order` INT(11) NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
@$conn->query($create_sql);

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add') {
            $name = trim($_POST['name'] ?? '');
            if ($name === '') {
                $error_message = 'Category name cannot be empty.';
            } else {
                $stmt = @$conn->prepare("INSERT INTO research_categories (name) VALUES (?)");
                if ($stmt) {
                    $stmt->bind_param("s", $name);
                    if ($stmt->execute()) {
                        $success_message = 'Category added successfully.';
                    } else {
                        $error_message = 'Failed to add category. It may already exist.';
                    }
                    $stmt->close();
                } else {
                    $error_message = 'Database error. Try again.';
                }
            }
        } elseif ($_POST['action'] === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id > 0) {
                $stmt = @$conn->prepare("DELETE FROM research_categories WHERE id = ?");
                if ($stmt) {
                    $stmt->bind_param("i", $id);
                    if ($stmt->execute()) {
                        $success_message = 'Category removed.';
                    } else {
                        $error_message = 'Could not delete category.';
                    }
                    $stmt->close();
                }
            }
        }
    }
}

$res = @$conn->query("SELECT id, name, display_order, created_at FROM research_categories ORDER BY display_order ASC, name ASC");
$categories = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
?>

<!DOCTYPE html>
<html lang="en">

<body>
    <div class="wrapper">
        <?php include 'sidebar.php'; ?>

        <div class="content-page">
            <div class="content">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-12">
                            <div class="page-title-box d-flex justify-content-between align-items-center">
                                <h4 class="page-title">Research Categories</h4>
                                <div>
                                    <a href="add_research.php" class="btn btn-primary me-2">
                                        <i class="ri-add-line"></i> Add Research
                                    </a>
                                    <a href="research_list.php" class="btn btn-secondary">
                                        <i class="ri-arrow-left-line"></i> Back to Research
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if ($success_message): ?>
                        <div class="alert alert-success alert-dismissible fade show">
                            <?php echo htmlspecialchars($success_message); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    <?php if ($error_message): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <?php echo htmlspecialchars($error_message); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-lg-5">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Add Category</h4>
                                </div>
                                <div class="card-body">
                                    <form method="post">
                                        <input type="hidden" name="action" value="add">
                                        <div class="mb-3">
                                            <label class="form-label">Category name</label>
                                            <input type="text" name="name" class="form-control" required
                                                   placeholder="e.g. Social Work, Psychology, Education" maxlength="100">
                                        </div>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="ri-add-line"></i> Add Category
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-7">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Categories (<?php echo count($categories); ?>)</h4>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($categories)): ?>
                                        <p class="text-muted mb-0">No categories yet. Add one to use in Add Research / Edit Research.</p>
                                    <?php else: ?>
                                        <div class="table-responsive">
                                            <table class="table table-hover mb-0">
                                                <thead>
                                                    <tr>
                                                        <th>Name</th>
                                                        <th class="text-end">Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($categories as $cat): ?>
                                                        <tr>
                                                            <td><?php echo htmlspecialchars($cat['name']); ?></td>
                                                            <td class="text-end">
                                                                <form method="post" class="d-inline" onsubmit="return confirm('Remove this category? Research using it will keep their category text.');">
                                                                    <input type="hidden" name="action" value="delete">
                                                                    <input type="hidden" name="id" value="<?php echo (int)$cat['id']; ?>">
                                                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                                                        <i class="ri-delete-bin-line"></i> Remove
                                                                    </button>
                                                                </form>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php include 'footer.php'; ?>
        </div>
    </div>
    <script src="assets/js/vendor.min.js"></script>
    <script src="assets/js/app.min.js"></script>
</body>
</html>
