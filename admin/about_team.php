<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: auth-login.php");
    exit();
}

include 'include/conn.php';
include 'header.php';

// Ensure table exists
$createSql = "
    CREATE TABLE IF NOT EXISTS about_team_members (
        id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        role VARCHAR(255) NULL,
        bio TEXT NULL,
        photo VARCHAR(255) NULL,
        sort_order INT(11) DEFAULT 0,
        status ENUM('active','inactive') NOT NULL DEFAULT 'active',
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";
$conn->query($createSql);

// Handle form submissions directly on this page to avoid permission issues
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'create') {
        $name  = trim($_POST['name'] ?? '');
        $role  = trim($_POST['role'] ?? '');
        $bio   = trim($_POST['bio'] ?? '');
        $order = intval($_POST['sort_order'] ?? 0);
        $status = $_POST['status'] === 'inactive' ? 'inactive' : 'active';

        if ($name === '') {
            header('Location: about_team.php?error=' . urlencode('Please provide a name'));
            exit();
        }

        $photoPath = null;
        if (!empty($_FILES['photo']['name'])) {
            $uploadDir = '../uploads/team/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
            $maxSize = 2 * 1024 * 1024; // 2MB

            if ($_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                $fileType = $_FILES['photo']['type'];
                $fileSize = $_FILES['photo']['size'];

                if (in_array($fileType, $allowedTypes) && $fileSize <= $maxSize) {
                    $fileName = time() . '_' . uniqid() . '_' . basename($_FILES['photo']['name']);
                    $filePath = $uploadDir . $fileName;

                    if (move_uploaded_file($_FILES['photo']['tmp_name'], $filePath)) {
                        // Frontend uses path relative to web root
                        $photoPath = 'uploads/team/' . $fileName;
                    }
                }
            }
        }

        $stmt = $conn->prepare("
            INSERT INTO about_team_members (name, role, bio, photo, sort_order, status)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param('ssssis', $name, $role, $bio, $photoPath, $order, $status);

        if ($stmt->execute()) {
            $stmt->close();
            header('Location: about_team.php?success=' . urlencode('Team member added'));
            exit();
        } else {
            $error = 'Failed to add team member: ' . $conn->error;
            $stmt->close();
            header('Location: about_team.php?error=' . urlencode($error));
            exit();
        }
    }

    if ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        if ($id <= 0) {
            header('Location: about_team.php?error=' . urlencode('Invalid ID'));
            exit();
        }

        $stmt = $conn->prepare('DELETE FROM about_team_members WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();

        header('Location: about_team.php?success=' . urlencode('Team member deleted'));
        exit();
    }

    if ($action === 'update_order') {
        if (!empty($_POST['order']) && is_array($_POST['order'])) {
            foreach ($_POST['order'] as $id => $order) {
                $id = intval($id);
                $order = intval($order);
                $stmt = $conn->prepare('UPDATE about_team_members SET sort_order = ? WHERE id = ?');
                $stmt->bind_param('ii', $order, $id);
                $stmt->execute();
                $stmt->close();
            }
        }

        header('Location: about_team.php?success=' . urlencode('Order updated'));
        exit();
    }
}

// Fetch existing team members
$teamMembers = [];
$result = $conn->query("SELECT * FROM about_team_members ORDER BY sort_order ASC, id ASC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $teamMembers[] = $row;
    }
    $result->free();
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
                            <div class="page-title-box">
                                <h4 class="page-title">Manage About Page &mdash; Team Members</h4>
                                <p class="text-muted mb-0">
                                    Update the "Our Team Members" section shown on the public About Us page.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-lg-5">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Add Team Member</h4>
                                </div>
                                <div class="card-body">
                                    <?php if (isset($_GET['success'])): ?>
                                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                                            <?php echo htmlspecialchars($_GET['success']); ?>
                                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (isset($_GET['error'])): ?>
                                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                            <?php echo htmlspecialchars($_GET['error']); ?>
                                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                        </div>
                                    <?php endif; ?>

                                    <form action="about_team.php" method="post" enctype="multipart/form-data">
                                        <input type="hidden" name="action" value="create">
                                        <div class="mb-3">
                                            <label for="name" class="form-label">Name *</label>
                                            <input type="text" class="form-control" id="name" name="name" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="role" class="form-label">Role / Title</label>
                                            <input type="text" class="form-control" id="role" name="role">
                                        </div>
                                        <div class="mb-3">
                                            <label for="bio" class="form-label">Short Bio (optional)</label>
                                            <textarea class="form-control" id="bio" name="bio" rows="3"></textarea>
                                        </div>
                                        <div class="mb-3">
                                            <label for="photo" class="form-label">Photo</label>
                                            <input type="file" class="form-control" id="photo" name="photo" accept="image/*">
                                            <small class="text-muted">Recommended square image, max 2MB.</small>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="sort_order" class="form-label">Sort Order</label>
                                                <input type="number" class="form-control" id="sort_order" name="sort_order" value="0">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="status" class="form-label">Status</label>
                                                <select class="form-select" id="status" name="status">
                                                    <option value="active" selected>Active</option>
                                                    <option value="inactive">Inactive</option>
                                                </select>
                                            </div>
                                        </div>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="ri-save-line"></i> Save Member
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-7">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h4 class="header-title mb-0">Current Team Members</h4>
                                </div>
                                <div class="card-body table-responsive">
                                    <?php if (empty($teamMembers)): ?>
                                        <p class="text-muted mb-0">No team members added yet.</p>
                                    <?php else: ?>
                                        <form action="include/manage_about_team.php" method="post">
                                            <input type="hidden" name="action" value="update_order">
                                            <table class="table table-sm align-middle">
                                                <thead>
                                                    <tr>
                                                        <th style="width: 60px;">Photo</th>
                                                        <th>Name</th>
                                                        <th>Role</th>
                                                        <th style="width: 100px;">Order</th>
                                                        <th style="width: 80px;">Status</th>
                                                        <th style="width: 80px;">Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($teamMembers as $member): ?>
                                                        <tr>
                                                            <td>
                                                                <?php if (!empty($member['photo'])): ?>
                                                                    <img src="../<?php echo htmlspecialchars($member['photo']); ?>" alt="" style="width: 40px; height: 40px; object-fit: cover; border-radius: 50%;">
                                                                <?php else: ?>
                                                                    <div style="width: 40px; height: 40px; border-radius: 50%; background: #e5e7eb; display: flex; align-items: center; justify-content: center; font-size: 18px;">
                                                                        <i class="ri-user-line"></i>
                                                                    </div>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td><?php echo htmlspecialchars($member['name']); ?></td>
                                                            <td><?php echo htmlspecialchars($member['role']); ?></td>
                                                            <td>
                                                                <input type="number" name="order[<?php echo (int)$member['id']; ?>]" class="form-control form-control-sm" value="<?php echo (int)$member['sort_order']; ?>">
                                                            </td>
                                                            <td>
                                                                <span class="badge bg-<?php echo $member['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                                                    <?php echo ucfirst($member['status']); ?>
                                                                </span>
                                                            </td>
                                                            <td>
                                                                <form action="include/manage_about_team.php" method="post" onsubmit="return confirm('Delete this member?');" style="display:inline;">
                                                                    <input type="hidden" name="action" value="delete">
                                                                    <input type="hidden" name="id" value="<?php echo (int)$member['id']; ?>">
                                                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                                                        <i class="ri-delete-bin-line"></i>
                                                                    </button>
                                                                </form>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                            <button type="submit" class="btn btn-outline-primary btn-sm">
                                                <i class="ri-sort-number-desc"></i> Update Order
                                            </button>
                                        </form>
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

