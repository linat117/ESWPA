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

// Handle access control updates
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action']) && $_POST['action'] == 'update_access') {
        $resource_id = intval($_POST['resource_id'] ?? 0);
        $access_level = trim($_POST['access_level'] ?? '');
        
        if ($resource_id > 0 && in_array($access_level, ['public', 'member', 'premium', 'restricted'])) {
            $stmt = $conn->prepare("UPDATE resources SET access_level = ? WHERE id = ?");
            $stmt->bind_param("si", $access_level, $resource_id);
            if ($stmt->execute()) {
                $success_message = "Access level updated successfully";
            } else {
                $error_message = "Failed to update access level";
            }
            $stmt->close();
        }
    } elseif (isset($_POST['action']) && $_POST['action'] == 'bulk_update') {
        $resource_ids = $_POST['resource_ids'] ?? [];
        $access_level = trim($_POST['access_level'] ?? '');
        
        if (!empty($resource_ids) && is_array($resource_ids) && in_array($access_level, ['public', 'member', 'premium', 'restricted'])) {
            $ids = array_map('intval', $resource_ids);
            $ids = array_filter($ids, function($id) { return $id > 0; });
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            
            if (!empty($ids)) {
                $stmt = $conn->prepare("UPDATE resources SET access_level = ? WHERE id IN ($placeholders)");
                $types = 's' . str_repeat('i', count($ids));
                $params = array_merge([$access_level], $ids);
                $stmt->bind_param($types, ...$params);
                if ($stmt->execute()) {
                    $success_message = count($ids) . " resource(s) access level updated to " . $access_level;
                } else {
                    $error_message = "Failed to update access levels";
                }
                $stmt->close();
            }
        }
    }
}

// Fetch access level statistics
$access_stats = [];
$stmt = $conn->prepare("SELECT access_level, COUNT(*) as count FROM resources GROUP BY access_level");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $access_stats[$row['access_level']] = $row['count'];
}
$stmt->close();

// Fetch resources with access levels
$query = "SELECT id, title, section, access_level, status, download_count, created_at 
          FROM resources 
          ORDER BY created_at DESC";
$result = $conn->query($query);
$resources = $result->fetch_all(MYSQLI_ASSOC);

// Calculate totals
$total_resources = count($resources);
$public_count = $access_stats['public'] ?? 0;
$member_count = $access_stats['member'] ?? 0;
$premium_count = $access_stats['premium'] ?? 0;
$restricted_count = $access_stats['restricted'] ?? 0;
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
                                <h4 class="page-title">Resource Access Control</h4>
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

                    <!-- Access Level Statistics -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-3">
                            <div class="card widget-flat text-bg-primary">
                                <div class="card-body">
                                    <div class="float-end">
                                        <i class="ri-global-line widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0">Public</h6>
                                    <h2 class="my-2"><?php echo number_format($public_count); ?></h2>
                                    <p class="mb-0">
                                        <span class="text-white-50"><?php echo $total_resources > 0 ? number_format(($public_count / $total_resources) * 100, 1) : 0; ?>%</span>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card widget-flat text-bg-info">
                                <div class="card-body">
                                    <div class="float-end">
                                        <i class="ri-user-line widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0">Member</h6>
                                    <h2 class="my-2"><?php echo number_format($member_count); ?></h2>
                                    <p class="mb-0">
                                        <span class="text-white-50"><?php echo $total_resources > 0 ? number_format(($member_count / $total_resources) * 100, 1) : 0; ?>%</span>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card widget-flat text-bg-warning">
                                <div class="card-body">
                                    <div class="float-end">
                                        <i class="ri-vip-crown-line widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0">Premium</h6>
                                    <h2 class="my-2"><?php echo number_format($premium_count); ?></h2>
                                    <p class="mb-0">
                                        <span class="text-white-50"><?php echo $total_resources > 0 ? number_format(($premium_count / $total_resources) * 100, 1) : 0; ?>%</span>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card widget-flat text-bg-danger">
                                <div class="card-body">
                                    <div class="float-end">
                                        <i class="ri-lock-line widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0">Restricted</h6>
                                    <h2 class="my-2"><?php echo number_format($restricted_count); ?></h2>
                                    <p class="mb-0">
                                        <span class="text-white-50"><?php echo $total_resources > 0 ? number_format(($restricted_count / $total_resources) * 100, 1) : 0; ?>%</span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Bulk Update Panel -->
                    <div class="row mb-3">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Bulk Access Control Update</h4>
                                    <p class="text-muted mb-0">Select resources and update their access level</p>
                                </div>
                                <div class="card-body">
                                    <form method="POST" id="bulkForm">
                                        <input type="hidden" name="action" value="bulk_update">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <label class="form-label">New Access Level</label>
                                                <select class="form-select" name="access_level" id="bulk_access_level" required>
                                                    <option value="">Select access level...</option>
                                                    <option value="public">Public - Accessible to everyone</option>
                                                    <option value="member">Member - Requires membership</option>
                                                    <option value="premium">Premium - Requires premium membership</option>
                                                    <option value="restricted">Restricted - Admin approval required</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6 d-flex align-items-end">
                                                <div class="w-100">
                                                    <div class="alert alert-info mb-0">
                                                        <strong>Selected:</strong> <span id="selectedCount">0</span> resource(s)
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row mt-3">
                                            <div class="col-md-12">
                                                <button type="submit" class="btn btn-primary" id="bulkUpdateBtn" disabled>
                                                    <i class="ri-save-line"></i> Update Selected Resources
                                                </button>
                                                <button type="button" class="btn btn-secondary" onclick="clearSelection()">
                                                    <i class="ri-refresh-line"></i> Clear Selection
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Resources List -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h4 class="header-title">All Resources</h4>
                                    <div>
                                        <button type="button" class="btn btn-sm btn-primary" onclick="selectAll()">
                                            <i class="ri-checkbox-line"></i> Select All
                                        </button>
                                        <button type="button" class="btn btn-sm btn-secondary" onclick="clearSelection()">
                                            <i class="ri-checkbox-blank-line"></i> Clear All
                                        </button>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-hover mb-0" id="resourcesTable">
                                            <thead>
                                                <tr>
                                                    <th width="50">
                                                        <input type="checkbox" id="selectAllCheckbox" onchange="toggleAll(this)">
                                                    </th>
                                                    <th>Title</th>
                                                    <th>Section</th>
                                                    <th>Current Access</th>
                                                    <th>Status</th>
                                                    <th>Downloads</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (empty($resources)): ?>
                                                    <tr>
                                                        <td colspan="7" class="text-center text-muted">No resources found</td>
                                                    </tr>
                                                <?php else: ?>
                                                    <?php foreach ($resources as $resource): ?>
                                                        <tr>
                                                            <td>
                                                                <input type="checkbox" 
                                                                       class="resource-checkbox" 
                                                                       name="resource_ids[]" 
                                                                       value="<?php echo $resource['id']; ?>"
                                                                       onchange="updateSelection()">
                                                            </td>
                                                            <td>
                                                                <a href="resource_details.php?id=<?php echo $resource['id']; ?>" class="text-dark">
                                                                    <?php echo htmlspecialchars(substr($resource['title'], 0, 50)); ?>
                                                                    <?php echo strlen($resource['title']) > 50 ? '...' : ''; ?>
                                                                </a>
                                                            </td>
                                                            <td><?php echo htmlspecialchars($resource['section']); ?></td>
                                                            <td>
                                                                <span class="badge bg-<?php 
                                                                    echo $resource['access_level'] == 'public' ? 'primary' : 
                                                                        ($resource['access_level'] == 'member' ? 'info' : 
                                                                        ($resource['access_level'] == 'premium' ? 'warning' : 'danger')); 
                                                                ?>">
                                                                    <?php echo ucfirst($resource['access_level']); ?>
                                                                </span>
                                                            </td>
                                                            <td>
                                                                <span class="badge bg-<?php 
                                                                    echo $resource['status'] == 'active' ? 'success' : 
                                                                        ($resource['status'] == 'inactive' ? 'warning' : 'secondary'); 
                                                                ?>">
                                                                    <?php echo ucfirst($resource['status']); ?>
                                                                </span>
                                                            </td>
                                                            <td><?php echo number_format($resource['download_count']); ?></td>
                                                            <td>
                                                                <button type="button" 
                                                                        class="btn btn-sm btn-primary" 
                                                                        data-bs-toggle="modal" 
                                                                        data-bs-target="#editAccessModal"
                                                                        onclick="setEditAccess(<?php echo $resource['id']; ?>, '<?php echo htmlspecialchars($resource['title']); ?>', '<?php echo $resource['access_level']; ?>')">
                                                                    <i class="ri-edit-line"></i> Edit
                                                                </button>
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

    <!-- Edit Access Modal -->
    <div class="modal fade" id="editAccessModal" tabindex="-1" aria-labelledby="editAccessModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="action" value="update_access">
                    <input type="hidden" name="resource_id" id="edit_resource_id">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editAccessModalLabel">Edit Access Level</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Resource</label>
                            <input type="text" class="form-control" id="edit_resource_title" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="edit_access_level" class="form-label">Access Level</label>
                            <select class="form-select" id="edit_access_level" name="access_level" required>
                                <option value="public">Public - Accessible to everyone</option>
                                <option value="member">Member - Requires membership</option>
                                <option value="premium">Premium - Requires premium membership</option>
                                <option value="restricted">Restricted - Admin approval required</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Access</button>
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
        var table;
        $(document).ready(function() {
            // Initialize DataTable
            table = $('#resourcesTable').DataTable({
                "order": [[5, "desc"]], // Sort by downloads
                "pageLength": 25,
                "language": {
                    "search": "Search resources:",
                    "lengthMenu": "Show _MENU_ resources per page",
                    "info": "Showing _START_ to _END_ of _TOTAL_ resources",
                    "infoEmpty": "No resources found",
                    "infoFiltered": "(filtered from _MAX_ total resources)"
                }
            });

            // Update bulk button state
            $('#bulk_access_level').on('change', function() {
                updateBulkButton();
            });
        });

        function updateSelection() {
            var selected = $('.resource-checkbox:checked').length;
            $('#selectedCount').text(selected);
            updateBulkButton();
        }

        function updateBulkButton() {
            var selected = $('.resource-checkbox:checked').length;
            var accessLevel = $('#bulk_access_level').val();
            $('#bulkUpdateBtn').prop('disabled', !(selected > 0 && accessLevel));
        }

        function toggleAll(checkbox) {
            $('.resource-checkbox').prop('checked', checkbox.checked);
            updateSelection();
        }

        function selectAll() {
            $('.resource-checkbox').prop('checked', true);
            $('#selectAllCheckbox').prop('checked', true);
            updateSelection();
        }

        function clearSelection() {
            $('.resource-checkbox').prop('checked', false);
            $('#selectAllCheckbox').prop('checked', false);
            updateSelection();
        }

        function setEditAccess(id, title, currentAccess) {
            document.getElementById('edit_resource_id').value = id;
            document.getElementById('edit_resource_title').value = title;
            document.getElementById('edit_access_level').value = currentAccess;
        }
    </script>

</body>
</html>

