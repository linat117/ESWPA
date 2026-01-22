<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: auth-login.php");
    exit();
}

include 'header.php';
include 'include/conn.php';
?>

<body>
    <div class="wrapper">
        <?php include 'sidebar.php'; ?>

        <div class="content-page">
            <div class="content">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-12">
                            <div class="page-title-box d-flex justify-content-between align-items-center">
                                <h4 class="page-title">Research Projects</h4>
                                <a href="add_research.php" class="btn btn-primary">
                                    <i class="ri-add-circle-line"></i> Add Research
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">All Research Projects</h4>
                                    <p class="text-muted mb-0">Manage research projects and collaborations</p>
                                </div>
                                <div class="card-body">
                                    <?php
                                    if (isset($_GET['success'])) {
                                        echo '<div class="alert alert-success alert-dismissible fade show" role="alert">';
                                        echo htmlspecialchars($_GET['success']);
                                        echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
                                        echo '</div>';
                                    }
                                    if (isset($_GET['error'])) {
                                        echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">';
                                        echo htmlspecialchars($_GET['error']);
                                        echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
                                        echo '</div>';
                                    }
                                    
                                    // Check if table exists
                                    $tableCheck = mysqli_query($conn, "SHOW TABLES LIKE 'research_projects'");
                                    if (mysqli_num_rows($tableCheck) == 0) {
                                        echo '<div class="alert alert-warning">';
                                        echo '<strong>Note:</strong> Research tables have not been created yet. Please run the migration: <code>Sql/migration_research_tables.sql</code>';
                                        echo '</div>';
                                    } else {
                                        // Get filter parameters
                                        $filter_status = $_GET['status'] ?? '';
                                        $filter_category = $_GET['category'] ?? '';
                                        $filter_type = $_GET['type'] ?? '';
                                        
                                        $where = [];
                                        $params = [];
                                        $types = '';
                                        
                                        if ($filter_status) {
                                            $where[] = "rp.status = ?";
                                            $params[] = $filter_status;
                                            $types .= 's';
                                        }
                                        if ($filter_category) {
                                            $where[] = "rp.category = ?";
                                            $params[] = $filter_category;
                                            $types .= 's';
                                        }
                                        if ($filter_type) {
                                            $where[] = "rp.research_type = ?";
                                            $params[] = $filter_type;
                                            $types .= 's';
                                        }
                                        
                                        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
                                        
                                        $query = "SELECT rp.*, r.fullname as creator_name,
                                                 (SELECT COUNT(*) FROM research_collaborators WHERE research_id = rp.id) as collaborator_count,
                                                 (SELECT COUNT(*) FROM research_files WHERE research_id = rp.id) as file_count
                                                 FROM research_projects rp
                                                 LEFT JOIN registrations r ON rp.created_by = r.id
                                                 $whereClause
                                                 ORDER BY rp.created_at DESC";
                                        
                                        if (!empty($params)) {
                                            $stmt = $conn->prepare($query);
                                            $stmt->bind_param($types, ...$params);
                                            $stmt->execute();
                                            $result = $stmt->get_result();
                                        } else {
                                            $result = mysqli_query($conn, $query);
                                        }
                                        
                                        // Get unique categories and types for filters
                                        $categoriesQuery = "SELECT DISTINCT category FROM research_projects WHERE category IS NOT NULL AND category != '' ORDER BY category";
                                        $categoriesResult = mysqli_query($conn, $categoriesQuery);
                                        ?>
                                        
                                        <!-- Filters -->
                                        <div class="row mb-3">
                                            <div class="col-md-12">
                                                <div class="card bg-light">
                                                    <div class="card-body">
                                                        <h6 class="card-title mb-3">
                                                            <i class="ri-filter-line"></i> Filters
                                                            <button class="btn btn-sm btn-link float-end" type="button" data-bs-toggle="collapse" data-bs-target="#filterCollapse">
                                                                <i class="ri-arrow-down-s-line"></i> Toggle
                                                            </button>
                                                        </h6>
                                                        <div class="collapse show" id="filterCollapse">
                                                            <form method="GET" class="row g-3">
                                                                <div class="col-md-3">
                                                                    <label class="form-label small">Status</label>
                                                                    <select name="status" class="form-select form-select-sm">
                                                                        <option value="">All Status</option>
                                                                        <option value="draft" <?php echo $filter_status === 'draft' ? 'selected' : ''; ?>>Draft</option>
                                                                        <option value="in_progress" <?php echo $filter_status === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                                                                        <option value="completed" <?php echo $filter_status === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                                                        <option value="published" <?php echo $filter_status === 'published' ? 'selected' : ''; ?>>Published</option>
                                                                        <option value="archived" <?php echo $filter_status === 'archived' ? 'selected' : ''; ?>>Archived</option>
                                                                    </select>
                                                                </div>
                                                                <div class="col-md-3">
                                                                    <label class="form-label small">Category</label>
                                                                    <select name="category" class="form-select form-select-sm">
                                                                        <option value="">All Categories</option>
                                                                        <?php
                                                                        while ($catRow = mysqli_fetch_assoc($categoriesResult)) {
                                                                            echo '<option value="' . htmlspecialchars($catRow['category']) . '" ' . 
                                                                                 ($filter_category === $catRow['category'] ? 'selected' : '') . '>' . 
                                                                                 htmlspecialchars($catRow['category']) . '</option>';
                                                                        }
                                                                        ?>
                                                                    </select>
                                                                </div>
                                                                <div class="col-md-3">
                                                                    <label class="form-label small">Research Type</label>
                                                                    <select name="type" class="form-select form-select-sm">
                                                                        <option value="">All Types</option>
                                                                        <option value="thesis" <?php echo $filter_type === 'thesis' ? 'selected' : ''; ?>>Thesis</option>
                                                                        <option value="journal_article" <?php echo $filter_type === 'journal_article' ? 'selected' : ''; ?>>Journal Article</option>
                                                                        <option value="case_study" <?php echo $filter_type === 'case_study' ? 'selected' : ''; ?>>Case Study</option>
                                                                        <option value="survey" <?php echo $filter_type === 'survey' ? 'selected' : ''; ?>>Survey</option>
                                                                        <option value="experiment" <?php echo $filter_type === 'experiment' ? 'selected' : ''; ?>>Experiment</option>
                                                                        <option value="review" <?php echo $filter_type === 'review' ? 'selected' : ''; ?>>Review</option>
                                                                        <option value="other" <?php echo $filter_type === 'other' ? 'selected' : ''; ?>>Other</option>
                                                                    </select>
                                                                </div>
                                                                <div class="col-md-3 d-flex align-items-end">
                                                                    <button type="submit" class="btn btn-sm btn-primary">Filter</button>
                                                                    <a href="research_list.php" class="btn btn-sm btn-secondary ms-2">Reset</a>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <?php
                                        if (mysqli_num_rows($result) > 0) {
                                            echo '<div class="table-responsive">';
                                            echo '<table id="research-datatable" class="table table-striped dt-responsive nowrap w-100">';
                                            echo '<thead>';
                                            echo '<tr>';
                                            echo '<th>Title</th>';
                                            echo '<th>Category</th>';
                                            echo '<th>Type</th>';
                                            echo '<th>Status</th>';
                                            echo '<th>Created By</th>';
                                            echo '<th>Collaborators</th>';
                                            echo '<th>Files</th>';
                                            echo '<th>Created</th>';
                                            echo '<th>Action</th>';
                                            echo '</tr>';
                                            echo '</thead>';
                                            echo '<tbody>';
                                            
                                            while ($row = mysqli_fetch_assoc($result)) {
                                                $statusColors = [
                                                    'draft' => 'secondary',
                                                    'in_progress' => 'warning',
                                                    'completed' => 'success',
                                                    'published' => 'primary',
                                                    'archived' => 'dark'
                                                ];
                                                $statusColor = $statusColors[$row['status']] ?? 'secondary';
                                                $statusLabel = ucfirst(str_replace('_', ' ', $row['status']));
                                                
                                                $typeLabels = [
                                                    'thesis' => 'Thesis',
                                                    'journal_article' => 'Journal Article',
                                                    'case_study' => 'Case Study',
                                                    'survey' => 'Survey',
                                                    'experiment' => 'Experiment',
                                                    'review' => 'Review',
                                                    'other' => 'Other'
                                                ];
                                                $typeLabel = $typeLabels[$row['research_type']] ?? $row['research_type'];
                                                
                                                echo '<tr>';
                                                echo '<td><strong>' . htmlspecialchars($row['title']) . '</strong>';
                                                if (!empty($row['abstract'])) {
                                                    echo '<br><small class="text-muted">' . htmlspecialchars(substr($row['abstract'], 0, 100)) . '...</small>';
                                                }
                                                echo '</td>';
                                                echo '<td>' . htmlspecialchars($row['category'] ?? 'N/A') . '</td>';
                                                echo '<td>' . htmlspecialchars($typeLabel) . '</td>';
                                                echo '<td><span class="badge bg-' . $statusColor . '">' . $statusLabel . '</span></td>';
                                                echo '<td>' . htmlspecialchars($row['creator_name'] ?? 'Unknown') . '</td>';
                                                echo '<td><span class="badge bg-info">' . $row['collaborator_count'] . '</span></td>';
                                                echo '<td><span class="badge bg-secondary">' . $row['file_count'] . '</span></td>';
                                                echo '<td>' . date('M d, Y', strtotime($row['created_at'])) . '</td>';
                                                echo '<td>';
                                                echo '<div class="btn-group" role="group">';
                                                echo '<a href="research_details.php?id=' . $row['id'] . '" class="btn btn-sm btn-info" title="View Details">';
                                                echo '<i class="ri-eye-line"></i> View';
                                                echo '</a>';
                                                echo '<a href="edit_research.php?id=' . $row['id'] . '" class="btn btn-sm btn-primary" title="Edit Research">';
                                                echo '<i class="ri-edit-line"></i> Edit';
                                                echo '</a>';
                                                echo '</div>';
                                                echo '</td>';
                                                echo '</tr>';
                                            }
                                            
                                            echo '</tbody>';
                                            echo '</table>';
                                            echo '</div>';
                                        } else {
                                            echo '<div class="alert alert-info">No research projects found. <a href="add_research.php">Create your first research project</a></div>';
                                        }
                                        
                                        if (isset($stmt)) {
                                            $stmt->close();
                                        }
                                    }
                                    mysqli_close($conn);
                                    ?>
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
    <script src="assets/vendor/datatables.net/js/jquery.dataTables.min.js"></script>
    <script src="assets/vendor/datatables.net-bs5/js/dataTables.bootstrap5.min.js"></script>
    <script src="assets/vendor/datatables.net-responsive/js/dataTables.responsive.min.js"></script>
    <script src="assets/vendor/datatables.net-responsive-bs5/js/responsive.bootstrap5.min.js"></script>
    <script src="assets/js/app.min.js"></script>

    <script>
        $(document).ready(function() {
            $('#research-datatable').DataTable({
                responsive: true,
                order: [[7, 'desc']], // Sort by created date descending
                columnDefs: [
                    { orderable: false, targets: [8] } // Disable sorting on action column
                ],
                pageLength: 25
            });
        });
    </script>
</body>
</html>

