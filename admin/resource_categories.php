<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: auth-login.php");
    exit();
}

include 'include/conn.php';
include 'header.php';

// Handle category rename/update
$success_message = '';
$error_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action']) && $_POST['action'] == 'rename') {
        $old_section = trim($_POST['old_section'] ?? '');
        $new_section = trim($_POST['new_section'] ?? '');
        
        if (!empty($old_section) && !empty($new_section) && $old_section != $new_section) {
            $stmt = $conn->prepare("UPDATE resources SET section = ? WHERE section = ?");
            $stmt->bind_param("ss", $new_section, $old_section);
            if ($stmt->execute()) {
                $success_message = "Category renamed successfully from '{$old_section}' to '{$new_section}'";
            } else {
                $error_message = "Failed to rename category";
            }
            $stmt->close();
        }
    } elseif (isset($_POST['action']) && $_POST['action'] == 'merge') {
        $source_section = trim($_POST['source_section'] ?? '');
        $target_section = trim($_POST['target_section'] ?? '');
        
        if (!empty($source_section) && !empty($target_section) && $source_section != $target_section) {
            $stmt = $conn->prepare("UPDATE resources SET section = ? WHERE section = ?");
            $stmt->bind_param("ss", $target_section, $source_section);
            if ($stmt->execute()) {
                $success_message = "Category '{$source_section}' merged into '{$target_section}' successfully";
            } else {
                $error_message = "Failed to merge categories";
            }
            $stmt->close();
        }
    }
}

// Fetch all unique sections with statistics
$query = "SELECT section, 
                 COUNT(*) as resource_count,
                 SUM(download_count) as total_downloads,
                 SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_count,
                 SUM(CASE WHEN featured = 1 THEN 1 ELSE 0 END) as featured_count
          FROM resources 
          WHERE section IS NOT NULL AND section != ''
          GROUP BY section 
          ORDER BY resource_count DESC";
$result = $conn->query($query);
$categories = $result->fetch_all(MYSQLI_ASSOC);

// Get total categories count
$total_categories = count($categories);

// Get total resources in all categories
$total_resources = 0;
$total_downloads = 0;
foreach ($categories as $cat) {
    $total_resources += $cat['resource_count'];
    $total_downloads += $cat['total_downloads'];
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
                                <h4 class="page-title">Resource Categories</h4>
                                <div>
                                    <a href="resources_dashboard.php" class="btn btn-secondary me-2">
                                        <i class="ri-dashboard-line"></i> Dashboard
                                    </a>
                                    <a href="resources_list.php" class="btn btn-secondary">
                                        <i class="ri-list-check"></i> All Resources
                                    </a>
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

                    <!-- Summary Cards -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <div class="card widget-flat text-bg-primary">
                                <div class="card-body">
                                    <div class="float-end">
                                        <i class="ri-folder-line widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0">Total Categories</h6>
                                    <h2 class="my-2"><?php echo number_format($total_categories); ?></h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card widget-flat text-bg-info">
                                <div class="card-body">
                                    <div class="float-end">
                                        <i class="ri-file-paper-line widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0">Total Resources</h6>
                                    <h2 class="my-2"><?php echo number_format($total_resources); ?></h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card widget-flat text-bg-success">
                                <div class="card-body">
                                    <div class="float-end">
                                        <i class="ri-download-line widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0">Total Downloads</h6>
                                    <h2 class="my-2"><?php echo number_format($total_downloads); ?></h2>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Categories List -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Resource Categories (Sections)</h4>
                                    <p class="text-muted mb-0">Manage resource categories and sections</p>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-hover mb-0" id="categoriesTable">
                                            <thead>
                                                <tr>
                                                    <th>Category Name</th>
                                                    <th>Resources</th>
                                                    <th>Active</th>
                                                    <th>Featured</th>
                                                    <th>Downloads</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (empty($categories)): ?>
                                                    <tr>
                                                        <td colspan="6" class="text-center text-muted">No categories found</td>
                                                    </tr>
                                                <?php else: ?>
                                                    <?php foreach ($categories as $category): ?>
                                                        <tr>
                                                            <td>
                                                                <strong><?php echo htmlspecialchars($category['section']); ?></strong>
                                                            </td>
                                                            <td>
                                                                <span class="badge bg-primary"><?php echo number_format($category['resource_count']); ?></span>
                                                            </td>
                                                            <td>
                                                                <span class="badge bg-success"><?php echo number_format($category['active_count']); ?></span>
                                                            </td>
                                                            <td>
                                                                <span class="badge bg-warning"><?php echo number_format($category['featured_count']); ?></span>
                                                            </td>
                                                            <td>
                                                                <span class="badge bg-info"><?php echo number_format($category['total_downloads']); ?></span>
                                                            </td>
                                                            <td>
                                                                <button type="button" 
                                                                        class="btn btn-sm btn-primary" 
                                                                        data-bs-toggle="modal" 
                                                                        data-bs-target="#renameModal"
                                                                        data-section="<?php echo htmlspecialchars($category['section']); ?>"
                                                                        onclick="setRenameSection('<?php echo htmlspecialchars($category['section']); ?>')">
                                                                    <i class="ri-edit-line"></i> Rename
                                                                </button>
                                                                <button type="button" 
                                                                        class="btn btn-sm btn-warning" 
                                                                        data-bs-toggle="modal" 
                                                                        data-bs-target="#mergeModal"
                                                                        data-section="<?php echo htmlspecialchars($category['section']); ?>"
                                                                        onclick="setMergeSource('<?php echo htmlspecialchars($category['section']); ?>')">
                                                                    <i class="ri-merge-cells-line"></i> Merge
                                                                </button>
                                                                <a href="resources_list.php?section=<?php echo urlencode($category['section']); ?>" 
                                                                   class="btn btn-sm btn-light">
                                                                    <i class="ri-eye-line"></i> View
                                                                </a>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <?php include 'footer.php'; ?>
        </div>
    </div>

    <!-- Rename Modal -->
    <div class="modal fade" id="renameModal" tabindex="-1" aria-labelledby="renameModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="action" value="rename">
                    <input type="hidden" name="old_section" id="rename_old_section">
                    <div class="modal-header">
                        <h5 class="modal-title" id="renameModalLabel">Rename Category</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="rename_new_section" class="form-label">New Category Name</label>
                            <input type="text" class="form-control" id="rename_new_section" name="new_section" required>
                            <small class="text-muted">Current name: <span id="rename_current_name"></span></small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Rename</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Merge Modal -->
    <div class="modal fade" id="mergeModal" tabindex="-1" aria-labelledby="mergeModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="action" value="merge">
                    <input type="hidden" name="source_section" id="merge_source_section">
                    <div class="modal-header">
                        <h5 class="modal-title" id="mergeModalLabel">Merge Category</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-warning">
                            <i class="ri-alert-line"></i> This will move all resources from the source category to the target category.
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Source Category (will be removed)</label>
                            <input type="text" class="form-control" id="merge_source_display" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="merge_target_section" class="form-label">Target Category (will receive resources)</label>
                            <select class="form-select" id="merge_target_section" name="target_section" required>
                                <option value="">Select target category...</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo htmlspecialchars($cat['section']); ?>">
                                        <?php echo htmlspecialchars($cat['section']); ?> (<?php echo $cat['resource_count']; ?> resources)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-warning">Merge Categories</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Vendor js -->
    <script src="assets/js/vendor.min.js"></script>
    
    <!-- Datatables js -->
    <link href="assets/vendor/datatables.net-bs5/css/dataTables.bootstrap5.min.css" rel="stylesheet" type="text/css" />
    <script src="assets/vendor/datatables.net/js/jquery.dataTables.min.js"></script>
    <script src="assets/vendor/datatables.net-bs5/js/dataTables.bootstrap5.min.js"></script>
    
    <!-- App js -->
    <script src="assets/js/app.min.js"></script>

    <script>
        $(document).ready(function() {
            // Initialize DataTable
            $('#categoriesTable').DataTable({
                "order": [[1, "desc"]], // Sort by resource count descending
                "pageLength": 25,
                "language": {
                    "search": "Search categories:",
                    "lengthMenu": "Show _MENU_ categories per page",
                    "info": "Showing _START_ to _END_ of _TOTAL_ categories",
                    "infoEmpty": "No categories found",
                    "infoFiltered": "(filtered from _MAX_ total categories)"
                }
            });
        });

        function setRenameSection(section) {
            document.getElementById('rename_old_section').value = section;
            document.getElementById('rename_current_name').textContent = section;
            document.getElementById('rename_new_section').value = section;
        }

        function setMergeSource(section) {
            document.getElementById('merge_source_section').value = section;
            document.getElementById('merge_source_display').value = section;
            // Remove the source from target dropdown
            var targetSelect = document.getElementById('merge_target_section');
            for (var i = 0; i < targetSelect.options.length; i++) {
                if (targetSelect.options[i].value === section) {
                    targetSelect.remove(i);
                    break;
                }
            }
        }
    </script>

</body>
</html>

