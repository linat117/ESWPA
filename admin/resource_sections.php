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

$create_sql = "CREATE TABLE IF NOT EXISTS `resource_sections` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `display_order` INT(11) NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
@$conn->query($create_sql);

// Seed from existing resources.section if table is empty
$check = @$conn->query("SELECT 1 FROM resource_sections LIMIT 1");
if ($check && $check->num_rows === 0) {
    @$conn->query("INSERT IGNORE INTO resource_sections (name) 
                   SELECT DISTINCT section FROM resources 
                   WHERE section IS NOT NULL AND section != '' 
                   ORDER BY section");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add') {
            $name = trim($_POST['name'] ?? '');
            if ($name === '') {
                $error_message = 'Section name cannot be empty.';
            } else {
                $stmt = @$conn->prepare("INSERT INTO resource_sections (name) VALUES (?)");
                if ($stmt) {
                    $stmt->bind_param("s", $name);
                    if ($stmt->execute()) {
                        $success_message = 'Section added successfully.';
                    } else {
                        $error_message = 'Failed to add section. It may already exist.';
                    }
                    $stmt->close();
                } else {
                    $error_message = 'Database error. Try again.';
                }
            }
        } elseif ($_POST['action'] === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id > 0) {
                $stmt = @$conn->prepare("DELETE FROM resource_sections WHERE id = ?");
                if ($stmt) {
                    $stmt->bind_param("i", $id);
                    if ($stmt->execute()) {
                        $success_message = 'Section removed.';
                    } else {
                        $error_message = 'Could not delete section.';
                    }
                    $stmt->close();
                }
            }
        }
    }
}

$res = @$conn->query("SELECT id, name, display_order, created_at FROM resource_sections ORDER BY display_order ASC, name ASC");
$sections = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
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
                                <h4 class="page-title">Resource Sections</h4>
                                <div>
                                    <a href="add_resource.php" class="btn btn-primary me-2">
                                        <i class="ri-add-line"></i> Add Resource
                                    </a>
                                    <a href="resources_list.php" class="btn btn-secondary">
                                        <i class="ri-arrow-left-line"></i> Back to Resources
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
                                    <h4 class="header-title">Add Section</h4>
                                </div>
                                <div class="card-body">
                                    <form method="post">
                                        <input type="hidden" name="action" value="add">
                                        <div class="mb-3">
                                            <label class="form-label">Section name</label>
                                            <input type="text" name="name" class="form-control" required
                                                   placeholder="e.g. Guidelines, Reports, Manuals" maxlength="100">
                                        </div>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="ri-add-line"></i> Add Section
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-7">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Sections (<?php echo count($sections); ?>)</h4>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($sections)): ?>
                                        <p class="text-muted mb-0">No sections yet. Add one to use in Add Resource / Edit Resource.</p>
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
                                                    <?php foreach ($sections as $sec): ?>
                                                        <tr>
                                                            <td><?php echo htmlspecialchars($sec['name']); ?></td>
                                                            <td class="text-end">
                                                                <form method="post" class="d-inline" onsubmit="return confirm('Remove this section? Resources using it will keep their section value.');">
                                                                    <input type="hidden" name="action" value="delete">
                                                                    <input type="hidden" name="id" value="<?php echo (int)$sec['id']; ?>">
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
