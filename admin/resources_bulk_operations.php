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

// Handle bulk operations
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && isset($_POST['resource_ids'])) {
    $action = $_POST['action'];
    $resource_ids = $_POST['resource_ids'];
    
    if (!empty($resource_ids) && is_array($resource_ids)) {
        // Sanitize IDs
        $ids = array_map('intval', $resource_ids);
        $ids = array_filter($ids, function($id) { return $id > 0; });
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        
        if (!empty($ids)) {
            switch ($action) {
                case 'activate':
                    $stmt = $conn->prepare("UPDATE resources SET status = 'active' WHERE id IN ($placeholders)");
                    $types = str_repeat('i', count($ids));
                    $stmt->bind_param($types, ...$ids);
                    if ($stmt->execute()) {
                        $success_message = count($ids) . " resource(s) activated successfully";
                    }
                    $stmt->close();
                    break;
                    
                case 'deactivate':
                    $stmt = $conn->prepare("UPDATE resources SET status = 'inactive' WHERE id IN ($placeholders)");
                    $types = str_repeat('i', count($ids));
                    $stmt->bind_param($types, ...$ids);
                    if ($stmt->execute()) {
                        $success_message = count($ids) . " resource(s) deactivated successfully";
                    }
                    $stmt->close();
                    break;
                    
                case 'archive':
                    $stmt = $conn->prepare("UPDATE resources SET status = 'archived' WHERE id IN ($placeholders)");
                    $types = str_repeat('i', count($ids));
                    $stmt->bind_param($types, ...$ids);
                    if ($stmt->execute()) {
                        $success_message = count($ids) . " resource(s) archived successfully";
                    }
                    $stmt->close();
                    break;
                    
                case 'delete':
                    // First, get file paths to delete files
                    $stmt = $conn->prepare("SELECT pdf_file FROM resources WHERE id IN ($placeholders)");
                    $types = str_repeat('i', count($ids));
                    $stmt->bind_param($types, ...$ids);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $files_to_delete = [];
                    while ($row = $result->fetch_assoc()) {
                        if (!empty($row['pdf_file'])) {
                            $files_to_delete[] = '../uploads/resources/' . $row['pdf_file'];
                        }
                    }
                    $stmt->close();
                    
                    // Delete database records
                    $stmt = $conn->prepare("DELETE FROM resources WHERE id IN ($placeholders)");
                    $types = str_repeat('i', count($ids));
                    $stmt->bind_param($types, ...$ids);
                    if ($stmt->execute()) {
                        // Delete files
                        foreach ($files_to_delete as $file) {
                            if (file_exists($file)) {
                                @unlink($file);
                            }
                        }
                        $success_message = count($ids) . " resource(s) deleted successfully";
                    }
                    $stmt->close();
                    break;
                    
                case 'feature':
                    $stmt = $conn->prepare("UPDATE resources SET featured = 1 WHERE id IN ($placeholders)");
                    $types = str_repeat('i', count($ids));
                    $stmt->bind_param($types, ...$ids);
                    if ($stmt->execute()) {
                        $success_message = count($ids) . " resource(s) marked as featured";
                    }
                    $stmt->close();
                    break;
                    
                case 'unfeature':
                    $stmt = $conn->prepare("UPDATE resources SET featured = 0 WHERE id IN ($placeholders)");
                    $types = str_repeat('i', count($ids));
                    $stmt->bind_param($types, ...$ids);
                    if ($stmt->execute()) {
                        $success_message = count($ids) . " resource(s) unfeatured";
                    }
                    $stmt->close();
                    break;
                    
                case 'change_access':
                    $new_access = $_POST['new_access_level'] ?? 'member';
                    if (in_array($new_access, ['public', 'member', 'premium', 'restricted'])) {
                        $stmt = $conn->prepare("UPDATE resources SET access_level = ? WHERE id IN ($placeholders)");
                        $types = 's' . str_repeat('i', count($ids));
                        $params = array_merge([$new_access], $ids);
                        $stmt->bind_param($types, ...$params);
                        if ($stmt->execute()) {
                            $success_message = count($ids) . " resource(s) access level changed to " . $new_access;
                        }
                        $stmt->close();
                    }
                    break;
                    
                case 'change_section':
                    $new_section = trim($_POST['new_section'] ?? '');
                    if (!empty($new_section)) {
                        $stmt = $conn->prepare("UPDATE resources SET section = ? WHERE id IN ($placeholders)");
                        $types = 's' . str_repeat('i', count($ids));
                        $params = array_merge([$new_section], $ids);
                        $stmt->bind_param($types, ...$params);
                        if ($stmt->execute()) {
                            $success_message = count($ids) . " resource(s) moved to section: " . $new_section;
                        }
                        $stmt->close();
                    }
                    break;
            }
        }
    }
}

// Fetch all resources for selection
$query = "SELECT id, title, section, status, access_level, featured, download_count, created_at 
          FROM resources 
          ORDER BY created_at DESC";
$result = $conn->query($query);
$all_resources = $result->fetch_all(MYSQLI_ASSOC);

// Get unique sections for dropdown
$sectionsQuery = "SELECT DISTINCT section FROM resources WHERE section IS NOT NULL AND section != '' ORDER BY section";
$sectionsResult = $conn->query($sectionsQuery);
$sections = $sectionsResult->fetch_all(MYSQLI_ASSOC);
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
                                <h4 class="page-title">Bulk Resource Operations</h4>
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

                    <!-- Bulk Operations Panel -->
                    <div class="row mb-3">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Bulk Operations</h4>
                                    <p class="text-muted mb-0">Select resources and choose an operation</p>
                                </div>
                                <div class="card-body">
                                    <form method="POST" id="bulkForm">
                                        <input type="hidden" name="action" id="bulk_action">
                                        
                                        <!-- Action Selection -->
                                        <div class="row mb-3">
                                            <div class="col-md-12">
                                                <label class="form-label">Select Operation</label>
                                                <select class="form-select" id="operationSelect" required>
                                                    <option value="">Choose an operation...</option>
                                                    <option value="activate">Activate Selected</option>
                                                    <option value="deactivate">Deactivate Selected</option>
                                                    <option value="archive">Archive Selected</option>
                                                    <option value="delete">Delete Selected</option>
                                                    <option value="feature">Mark as Featured</option>
                                                    <option value="unfeature">Remove Featured</option>
                                                    <option value="change_access">Change Access Level</option>
                                                    <option value="change_section">Change Section</option>
                                                </select>
                                            </div>
                                        </div>

                                        <!-- Additional Options (for change_access and change_section) -->
                                        <div class="row mb-3" id="accessLevelOptions" style="display: none;">
                                            <div class="col-md-12">
                                                <label class="form-label">New Access Level</label>
                                                <select class="form-select" name="new_access_level" id="new_access_level">
                                                    <option value="public">Public</option>
                                                    <option value="member">Member</option>
                                                    <option value="premium">Premium</option>
                                                    <option value="restricted">Restricted</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="row mb-3" id="sectionOptions" style="display: none;">
                                            <div class="col-md-12">
                                                <label class="form-label">New Section</label>
                                                <select class="form-select" name="new_section" id="new_section">
                                                    <option value="">Select section...</option>
                                                    <?php foreach ($sections as $section): ?>
                                                        <option value="<?php echo htmlspecialchars($section['section']); ?>">
                                                            <?php echo htmlspecialchars($section['section']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <small class="text-muted">Or enter a new section name</small>
                                                <input type="text" class="form-control mt-2" name="new_section_custom" placeholder="New section name (optional)">
                                            </div>
                                        </div>

                                        <!-- Selected Count -->
                                        <div class="row mb-3">
                                            <div class="col-md-12">
                                                <div class="alert alert-info">
                                                    <strong>Selected:</strong> <span id="selectedCount">0</span> resource(s)
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Execute Button -->
                                        <div class="row">
                                            <div class="col-md-12">
                                                <button type="submit" class="btn btn-primary" id="executeBtn" disabled>
                                                    <i class="ri-check-line"></i> Execute Operation
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
                                                    <th>Status</th>
                                                    <th>Access</th>
                                                    <th>Featured</th>
                                                    <th>Downloads</th>
                                                    <th>Created</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (empty($all_resources)): ?>
                                                    <tr>
                                                        <td colspan="9" class="text-center text-muted">No resources found</td>
                                                    </tr>
                                                <?php else: ?>
                                                    <?php foreach ($all_resources as $resource): ?>
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
                                                                    echo $resource['status'] == 'active' ? 'success' : 
                                                                        ($resource['status'] == 'inactive' ? 'warning' : 'secondary'); 
                                                                ?>">
                                                                    <?php echo ucfirst($resource['status']); ?>
                                                                </span>
                                                            </td>
                                                            <td>
                                                                <span class="badge bg-info"><?php echo ucfirst($resource['access_level']); ?></span>
                                                            </td>
                                                            <td>
                                                                <?php if ($resource['featured'] == 1): ?>
                                                                    <i class="ri-star-fill text-warning"></i>
                                                                <?php else: ?>
                                                                    <i class="ri-star-line text-muted"></i>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td><?php echo number_format($resource['download_count']); ?></td>
                                                            <td><?php echo date('M d, Y', strtotime($resource['created_at'])); ?></td>
                                                            <td>
                                                                <a href="edit_resource.php?id=<?php echo $resource['id']; ?>" class="btn btn-sm btn-light">
                                                                    <i class="ri-edit-line"></i>
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
                "order": [[7, "desc"]], // Sort by created date
                "pageLength": 25,
                "language": {
                    "search": "Search resources:",
                    "lengthMenu": "Show _MENU_ resources per page",
                    "info": "Showing _START_ to _END_ of _TOTAL_ resources",
                    "infoEmpty": "No resources found",
                    "infoFiltered": "(filtered from _MAX_ total resources)"
                }
            });

            // Handle operation selection
            $('#operationSelect').on('change', function() {
                var action = $(this).val();
                $('#bulk_action').val(action);
                
                // Show/hide additional options
                if (action === 'change_access') {
                    $('#accessLevelOptions').show();
                    $('#sectionOptions').hide();
                } else if (action === 'change_section') {
                    $('#sectionOptions').show();
                    $('#accessLevelOptions').hide();
                } else {
                    $('#accessLevelOptions').hide();
                    $('#sectionOptions').hide();
                }
                
                updateExecuteButton();
            });

            // Handle custom section input
            $('input[name="new_section_custom"]').on('input', function() {
                if ($(this).val().trim() !== '') {
                    $('#new_section').val('');
                }
            });

            $('#new_section').on('change', function() {
                if ($(this).val() !== '') {
                    $('input[name="new_section_custom"]').val('');
                }
            });
        });

        function updateSelection() {
            var selected = $('.resource-checkbox:checked').length;
            $('#selectedCount').text(selected);
            updateExecuteButton();
        }

        function updateExecuteButton() {
            var selected = $('.resource-checkbox:checked').length;
            var action = $('#operationSelect').val();
            
            if (selected > 0 && action) {
                // Additional validation for change_section
                if (action === 'change_section') {
                    var section = $('#new_section').val() || $('input[name="new_section_custom"]').val();
                    $('#executeBtn').prop('disabled', !section.trim());
                } else {
                    $('#executeBtn').prop('disabled', false);
                }
            } else {
                $('#executeBtn').prop('disabled', true);
            }
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

        // Form submission confirmation
        $('#bulkForm').on('submit', function(e) {
            var action = $('#operationSelect').val();
            var selected = $('.resource-checkbox:checked').length;
            var message = '';
            
            if (action === 'delete') {
                message = 'Are you sure you want to DELETE ' + selected + ' resource(s)? This action cannot be undone!';
            } else {
                message = 'Are you sure you want to ' + action.toUpperCase() + ' ' + selected + ' resource(s)?';
            }
            
            if (!confirm(message)) {
                e.preventDefault();
                return false;
            }
        });
    </script>

</body>
</html>

