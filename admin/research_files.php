<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: auth-login.php");
    exit();
}

include 'include/conn.php';
include 'header.php';

// Get research ID (optional - if provided, filter by research)
$research_id = isset($_GET['research_id']) ? intval($_GET['research_id']) : 0;

// Check if research tables exist
$tableCheck = $conn->query("SHOW TABLES LIKE 'research_files'");
$tables_exist = $tableCheck->num_rows > 0;

if (!$tables_exist) {
    $error_message = "Research files tables have not been created yet.";
} else {
    // Build query based on filter
    if ($research_id > 0) {
        $query = "SELECT rf.*, rp.title as research_title, r.fullname as uploaded_by_name, r.email as uploaded_by_email
                  FROM research_files rf
                  LEFT JOIN research_projects rp ON rf.research_id = rp.id
                  LEFT JOIN registrations r ON rf.uploaded_by = r.id
                  WHERE rf.research_id = ?
                  ORDER BY rf.uploaded_at DESC";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $research_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $files = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    } else {
        // Get all files
        $query = "SELECT rf.*, rp.title as research_title, r.fullname as uploaded_by_name, r.email as uploaded_by_email
                  FROM research_files rf
                  LEFT JOIN research_projects rp ON rf.research_id = rp.id
                  LEFT JOIN registrations r ON rf.uploaded_by = r.id
                  ORDER BY rf.uploaded_at DESC
                  LIMIT 100";
        $result = $conn->query($query);
        $files = $result->fetch_all(MYSQLI_ASSOC);
    }

    // Get research projects for filter dropdown
    $researchQuery = "SELECT id, title FROM research_projects ORDER BY title";
    $researchResult = $conn->query($researchQuery);
    $research_projects = $researchResult->fetch_all(MYSQLI_ASSOC);

    // Calculate total file size
    $total_size = 0;
    foreach ($files as $file) {
        $total_size += $file['file_size'] ?? 0;
    }
}

// Format file size
function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}

// Get file icon based on type
function getFileIcon($file_type) {
    $icons = [
        'pdf' => 'ri-file-pdf-line',
        'doc' => 'ri-file-word-line',
        'docx' => 'ri-file-word-line',
        'xls' => 'ri-file-excel-line',
        'xlsx' => 'ri-file-excel-line',
        'ppt' => 'ri-file-ppt-line',
        'pptx' => 'ri-file-ppt-line',
        'jpg' => 'ri-image-line',
        'jpeg' => 'ri-image-line',
        'png' => 'ri-image-line',
        'gif' => 'ri-image-line',
        'zip' => 'ri-file-zip-line',
        'rar' => 'ri-file-zip-line'
    ];
    $ext = strtolower($file_type ?? 'file');
    return $icons[$ext] ?? 'ri-file-line';
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
                                <h4 class="page-title">Research Files Management</h4>
                                <div>
                                    <a href="research_dashboard.php" class="btn btn-secondary me-2">
                                        <i class="ri-dashboard-line"></i> Dashboard
                                    </a>
                                    <a href="research_list.php" class="btn btn-secondary">
                                        <i class="ri-list-check"></i> All Research
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if (isset($error_message)): ?>
                        <div class="row">
                            <div class="col-12">
                                <div class="alert alert-warning">
                                    <strong>Note:</strong> <?php echo htmlspecialchars($error_message); ?>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>

                    <!-- Summary Cards -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <div class="card widget-flat text-bg-primary">
                                <div class="card-body">
                                    <div class="float-end">
                                        <i class="ri-file-line widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0">Total Files</h6>
                                    <h2 class="my-2"><?php echo number_format(count($files)); ?></h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card widget-flat text-bg-info">
                                <div class="card-body">
                                    <div class="float-end">
                                        <i class="ri-folder-line widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0">Total Size</h6>
                                    <h2 class="my-2"><?php echo formatFileSize($total_size); ?></h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card widget-flat text-bg-success">
                                <div class="card-body">
                                    <div class="float-end">
                                        <i class="ri-search-line widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0">Research Projects</h6>
                                    <h2 class="my-2"><?php echo number_format(count($research_projects)); ?></h2>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Filter -->
                    <div class="row mb-3">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <form method="GET" class="row g-3 align-items-end">
                                        <div class="col-md-10">
                                            <label class="form-label">Filter by Research Project</label>
                                            <select name="research_id" class="form-select" onchange="this.form.submit()">
                                                <option value="0">All Research Projects</option>
                                                <?php foreach ($research_projects as $project): ?>
                                                    <option value="<?php echo $project['id']; ?>" 
                                                            <?php echo $research_id == $project['id'] ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($project['title']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <?php if ($research_id > 0): ?>
                                                <a href="research_files.php" class="btn btn-secondary w-100">
                                                    <i class="ri-refresh-line"></i> Clear Filter
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Files List -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">
                                        Research Files
                                        <?php if ($research_id > 0): ?>
                                            <span class="text-muted small">(Filtered)</span>
                                        <?php endif; ?>
                                    </h4>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($files)): ?>
                                        <div class="alert alert-info">
                                            <i class="ri-information-line"></i> No files found.
                                        </div>
                                    <?php else: ?>
                                        <div class="table-responsive">
                                            <table class="table table-hover mb-0" id="filesTable">
                                                <thead>
                                                    <tr>
                                                        <th>File</th>
                                                        <th>Research Project</th>
                                                        <th>Type</th>
                                                        <th>Size</th>
                                                        <th>Version</th>
                                                        <th>Uploaded By</th>
                                                        <th>Date</th>
                                                        <th>Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($files as $file): ?>
                                                        <tr>
                                                            <td>
                                                                <i class="<?php echo getFileIcon($file['file_type']); ?> fs-4 text-primary me-2"></i>
                                                                <strong><?php echo htmlspecialchars($file['file_name']); ?></strong>
                                                            </td>
                                                            <td>
                                                                <a href="research_details.php?id=<?php echo $file['research_id']; ?>" class="text-dark">
                                                                    <?php echo htmlspecialchars(substr($file['research_title'] ?? 'N/A', 0, 40)); ?>
                                                                    <?php echo strlen($file['research_title'] ?? '') > 40 ? '...' : ''; ?>
                                                                </a>
                                                            </td>
                                                            <td>
                                                                <span class="badge bg-secondary">
                                                                    <?php echo strtoupper($file['file_type'] ?? 'Unknown'); ?>
                                                                </span>
                                                            </td>
                                                            <td><?php echo formatFileSize($file['file_size'] ?? 0); ?></td>
                                                            <td>
                                                                <span class="badge bg-info">v<?php echo htmlspecialchars($file['version'] ?? '1.0'); ?></span>
                                                            </td>
                                                            <td>
                                                                <?php echo htmlspecialchars($file['uploaded_by_name'] ?? 'Unknown'); ?>
                                                                <?php if ($file['uploaded_by_email']): ?>
                                                                    <br><small class="text-muted"><?php echo htmlspecialchars($file['uploaded_by_email']); ?></small>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td>
                                                                <?php echo date('M d, Y', strtotime($file['uploaded_at'])); ?>
                                                                <br><small class="text-muted"><?php echo date('H:i', strtotime($file['uploaded_at'])); ?></small>
                                                            </td>
                                                            <td>
                                                                <a href="<?php echo htmlspecialchars($file['file_path']); ?>" 
                                                                   target="_blank" 
                                                                   class="btn btn-sm btn-primary" 
                                                                   title="Download">
                                                                    <i class="ri-download-line"></i>
                                                                </a>
                                                                <a href="research_details.php?id=<?php echo $file['research_id']; ?>" 
                                                                   class="btn btn-sm btn-light" 
                                                                   title="View Research">
                                                                    <i class="ri-eye-line"></i>
                                                                </a>
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

                    <?php endif; ?>

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
        $(document).ready(function() {
            // Initialize DataTable
            $('#filesTable').DataTable({
                "order": [[6, "desc"]], // Sort by date descending
                "pageLength": 25,
                "language": {
                    "search": "Search files:",
                    "lengthMenu": "Show _MENU_ files per page",
                    "info": "Showing _START_ to _END_ of _TOTAL_ files",
                    "infoEmpty": "No files found",
                    "infoFiltered": "(filtered from _MAX_ total files)"
                }
            });
        });
    </script>

</body>
</html>

