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
$tableCheck = $conn->query("SHOW TABLES LIKE 'research_versions'");
$tables_exist = $tableCheck->num_rows > 0;

if (!$tables_exist) {
    $error_message = "Research version tables have not been created yet.";
} else {
    // Build query based on filter
    if ($research_id > 0) {
        $query = "SELECT rv.*, rp.title as research_title, r.fullname as changed_by_name, r.email as changed_by_email
                  FROM research_versions rv
                  LEFT JOIN research_projects rp ON rv.research_id = rp.id
                  LEFT JOIN registrations r ON rv.changed_by = r.id
                  WHERE rv.research_id = ?
                  ORDER BY rv.changed_at DESC";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $research_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $versions = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    } else {
        // Get all versions
        $query = "SELECT rv.*, rp.title as research_title, r.fullname as changed_by_name, r.email as changed_by_email
                  FROM research_versions rv
                  LEFT JOIN research_projects rp ON rv.research_id = rp.id
                  LEFT JOIN registrations r ON rv.changed_by = r.id
                  ORDER BY rv.changed_at DESC
                  LIMIT 100";
        $result = $conn->query($query);
        $versions = $result->fetch_all(MYSQLI_ASSOC);
    }

    // Get research projects for filter dropdown
    $researchQuery = "SELECT id, title FROM research_projects ORDER BY title";
    $researchResult = $conn->query($researchQuery);
    $research_projects = $researchResult->fetch_all(MYSQLI_ASSOC);
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
                                <h4 class="page-title">Research Version History</h4>
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
                                                <a href="research_versions.php" class="btn btn-secondary w-100">
                                                    <i class="ri-refresh-line"></i> Clear Filter
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Version History List -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">
                                        Version History
                                        <?php if ($research_id > 0): ?>
                                            <span class="text-muted small">(Filtered)</span>
                                        <?php endif; ?>
                                    </h4>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($versions)): ?>
                                        <div class="alert alert-info">
                                            <i class="ri-information-line"></i> No version history found.
                                        </div>
                                    <?php else: ?>
                                        <div class="table-responsive">
                                            <table class="table table-hover mb-0" id="versionsTable">
                                                <thead>
                                                    <tr>
                                                        <th>Version</th>
                                                        <th>Research Project</th>
                                                        <th>Title</th>
                                                        <th>Changed By</th>
                                                        <th>Date</th>
                                                        <th>Changes Summary</th>
                                                        <th>Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($versions as $version): ?>
                                                        <tr>
                                                            <td>
                                                                <span class="badge bg-primary">v<?php echo htmlspecialchars($version['version_number']); ?></span>
                                                            </td>
                                                            <td>
                                                                <a href="research_details.php?id=<?php echo $version['research_id']; ?>" class="text-dark">
                                                                    <?php echo htmlspecialchars(substr($version['research_title'] ?? 'N/A', 0, 40)); ?>
                                                                    <?php echo strlen($version['research_title'] ?? '') > 40 ? '...' : ''; ?>
                                                                </a>
                                                            </td>
                                                            <td>
                                                                <?php echo htmlspecialchars(substr($version['title'], 0, 50)); ?>
                                                                <?php echo strlen($version['title']) > 50 ? '...' : ''; ?>
                                                            </td>
                                                            <td>
                                                                <?php echo htmlspecialchars($version['changed_by_name'] ?? 'Unknown'); ?>
                                                                <?php if ($version['changed_by_email']): ?>
                                                                    <br><small class="text-muted"><?php echo htmlspecialchars($version['changed_by_email']); ?></small>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td>
                                                                <?php echo date('M d, Y', strtotime($version['changed_at'])); ?>
                                                                <br><small class="text-muted"><?php echo date('H:i', strtotime($version['changed_at'])); ?></small>
                                                            </td>
                                                            <td>
                                                                <?php if (!empty($version['changes_summary'])): ?>
                                                                    <?php echo htmlspecialchars(substr($version['changes_summary'], 0, 100)); ?>
                                                                    <?php echo strlen($version['changes_summary']) > 100 ? '...' : ''; ?>
                                                                <?php else: ?>
                                                                    <span class="text-muted">No summary</span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td>
                                                                <a href="research_details.php?id=<?php echo $version['research_id']; ?>" 
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
            $('#versionsTable').DataTable({
                "order": [[4, "desc"]], // Sort by date descending
                "pageLength": 25,
                "language": {
                    "search": "Search versions:",
                    "lengthMenu": "Show _MENU_ versions per page",
                    "info": "Showing _START_ to _END_ of _TOTAL_ versions",
                    "infoEmpty": "No versions found",
                    "infoFiltered": "(filtered from _MAX_ total versions)"
                }
            });
        });
    </script>

</body>
</html>

