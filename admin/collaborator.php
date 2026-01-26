<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: auth-login.php");
    exit();
}

include 'include/conn.php';
include 'header.php';

$error_message = '';
$success_message = '';

// Check if research tables exist
$tablesOk = false;
$projects = [];
$all_collaborations = [];
$stats = ['total_collabs' => 0, 'projects_with_collabs' => 0, 'total_members' => 0];

$checkProjects = $conn->query("SHOW TABLES LIKE 'research_projects'");
$checkCollabs = $conn->query("SHOW TABLES LIKE 'research_collaborators'");

if ($checkProjects && $checkProjects->num_rows > 0 && $checkCollabs && $checkCollabs->num_rows > 0) {
    $tablesOk = true;

    // Stats
    $r = $conn->query("SELECT COUNT(*) as c FROM research_collaborators");
    if ($r) {
        $stats['total_collabs'] = (int) $r->fetch_assoc()['c'];
    }
    $r = $conn->query("SELECT COUNT(DISTINCT research_id) as c FROM research_collaborators");
    if ($r) {
        $stats['projects_with_collabs'] = (int) $r->fetch_assoc()['c'];
    }
    $r = $conn->query("SELECT COUNT(DISTINCT member_id) as c FROM research_collaborators");
    if ($r) {
        $stats['total_members'] = (int) $r->fetch_assoc()['c'];
    }

    // All research projects for selector
    $projectsResult = $conn->query("SELECT id, title, status FROM research_projects ORDER BY title");
    if ($projectsResult) {
        while ($row = $projectsResult->fetch_assoc()) {
            $projects[] = $row;
        }
    }

    // All collaborations with research title and member info
    $collabQuery = "SELECT rc.id as collab_id, rc.research_id, rc.member_id, rc.role, rc.contribution_percentage, rc.joined_at,
                    rp.title as research_title, rp.status as research_status,
                    reg.fullname as member_name, reg.email as member_email
                    FROM research_collaborators rc
                    JOIN research_projects rp ON rp.id = rc.research_id
                    JOIN registrations reg ON reg.id = rc.member_id
                    ORDER BY rp.title, rc.role, rc.joined_at";
    $collabResult = $conn->query($collabQuery);
    if ($collabResult) {
        while ($row = $collabResult->fetch_assoc()) {
            $all_collaborations[] = $row;
        }
    }
}

// Handle redirect from "Manage collaborators" form
$go_to_id = isset($_POST['research_id']) ? (int) $_POST['research_id'] : (isset($_GET['research_id']) ? (int) $_GET['research_id'] : 0);
if ($go_to_id > 0 && $tablesOk) {
    header("Location: research_collaborators.php?id=" . $go_to_id);
    exit();
}

// Flash messages
if (isset($_GET['success'])) {
    $success_message = $_GET['success'];
}
if (isset($_GET['error'])) {
    $error_message = $_GET['error'];
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
                            <div class="page-title-box d-flex justify-content-between align-items-center">
                                <h4 class="page-title">Research Collaborators</h4>
                                <div>
                                    <a href="research_list.php" class="btn btn-secondary me-2">
                                        <i class="ri-arrow-left-line"></i> All Research
                                    </a>
                                    <a href="add_research.php" class="btn btn-primary">
                                        <i class="ri-add-circle-line"></i> Add Research
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if ($success_message): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?php echo htmlspecialchars($success_message); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    <?php if ($error_message): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php echo htmlspecialchars($error_message); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if (!$tablesOk): ?>
                        <div class="row">
                            <div class="col-12">
                                <div class="alert alert-warning">
                                    <strong>Note:</strong> Research tables are required. Run the migration
                                    <code>Sql/migration_research_tables.sql</code> to create
                                    <code>research_projects</code> and <code>research_collaborators</code>.
                                </div>
                            </div>
                        </div>
                    <?php else: ?>

                        <!-- Stats -->
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <div class="card widget-flat text-bg-primary">
                                    <div class="card-body">
                                        <div class="float-end"><i class="ri-group-line widget-icon"></i></div>
                                        <h6 class="text-uppercase mt-0">Total Collaborations</h6>
                                        <h2 class="my-2"><?php echo number_format($stats['total_collabs']); ?></h2>
                                        <p class="mb-0 text-white-50">Across all research projects</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card widget-flat text-bg-success">
                                    <div class="card-body">
                                        <div class="float-end"><i class="ri-file-list-3-line widget-icon"></i></div>
                                        <h6 class="text-uppercase mt-0">Projects with Collaborators</h6>
                                        <h2 class="my-2"><?php echo number_format($stats['projects_with_collabs']); ?></h2>
                                        <p class="mb-0 text-white-50">Research projects</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card widget-flat text-bg-info">
                                    <div class="card-body">
                                        <div class="float-end"><i class="ri-user-star-line widget-icon"></i></div>
                                        <h6 class="text-uppercase mt-0">Unique Collaborators</h6>
                                        <h2 class="my-2"><?php echo number_format($stats['total_members']); ?></h2>
                                        <p class="mb-0 text-white-50">Members involved</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Quick: Select research & manage collaborators -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h4 class="header-title">Manage Collaborators for a Project</h4>
                                        <p class="text-muted mb-0">Select a research project to add or remove collaborators.</p>
                                    </div>
                                    <div class="card-body">
                                        <form method="post" action="collaborator.php" class="row g-3 align-items-end">
                                            <div class="col-md-8">
                                                <label class="form-label">Research Project</label>
                                                <select name="research_id" class="form-select" required>
                                                    <option value="">— Select a project —</option>
                                                    <?php foreach ($projects as $p): ?>
                                                        <option value="<?php echo (int) $p['id']; ?>">
                                                            <?php echo htmlspecialchars($p['title']); ?>
                                                            (<?php echo htmlspecialchars($p['status'] ?? 'draft'); ?>)
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="col-md-4">
                                                <button type="submit" class="btn btn-primary">
                                                    <i class="ri-user-add-line"></i> Manage Collaborators
                                                </button>
                                            </div>
                                        </form>
                                        <?php if (empty($projects)): ?>
                                            <p class="text-muted mt-2 mb-0">No research projects yet. <a href="add_research.php">Add one</a> first.</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- All collaborations table -->
                        <div class="row">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h4 class="header-title">All Collaborations</h4>
                                        <p class="text-muted mb-0">View and manage collaborators across all research projects.</p>
                                    </div>
                                    <div class="card-body">
                                        <?php if (empty($all_collaborations)): ?>
                                            <div class="alert alert-info mb-0">
                                                No collaborators yet. Use <strong>Manage Collaborators for a Project</strong> above to add members to a research project.
                                            </div>
                                        <?php else: ?>
                                            <div class="table-responsive">
                                                <table id="collaborators-datatable" class="table table-striped dt-responsive nowrap w-100">
                                                    <thead>
                                                        <tr>
                                                            <th>Research Project</th>
                                                            <th>Member</th>
                                                            <th>Email</th>
                                                            <th>Role</th>
                                                            <th>Contribution</th>
                                                            <th>Joined</th>
                                                            <th>Action</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php
                                                        $roleColors = [
                                                            'lead' => 'primary',
                                                            'co_author' => 'success',
                                                            'contributor' => 'info',
                                                            'advisor' => 'warning',
                                                            'reviewer' => 'secondary',
                                                        ];
                                                        foreach ($all_collaborations as $c):
                                                            $roleColor = $roleColors[$c['role']] ?? 'secondary';
                                                        ?>
                                                            <tr>
                                                                <td>
                                                                    <a href="research_details.php?id=<?php echo (int) $c['research_id']; ?>"><?php echo htmlspecialchars($c['research_title']); ?></a>
                                                                    <br><span class="badge bg-secondary"><?php echo htmlspecialchars($c['research_status'] ?? 'draft'); ?></span>
                                                                </td>
                                                                <td><strong><?php echo htmlspecialchars($c['member_name']); ?></strong></td>
                                                                <td><?php echo htmlspecialchars($c['member_email']); ?></td>
                                                                <td><span class="badge bg-<?php echo $roleColor; ?>"><?php echo ucfirst(str_replace('_', ' ', $c['role'])); ?></span></td>
                                                                <td><?php echo $c['contribution_percentage'] !== null ? $c['contribution_percentage'] . '%' : '—'; ?></td>
                                                                <td><?php echo date('M d, Y', strtotime($c['joined_at'])); ?></td>
                                                                <td>
                                                                    <a href="research_collaborators.php?id=<?php echo (int) $c['research_id']; ?>" class="btn btn-sm btn-primary" title="Manage collaborators">
                                                                        <i class="ri-settings-3-line"></i> Manage
                                                                    </a>
                                                                    <a href="include/collaborator_handler.php?action=remove&research_id=<?php echo (int) $c['research_id']; ?>&collab_id=<?php echo (int) $c['collab_id']; ?>&return=collaborator" 
                                                                       class="btn btn-sm btn-danger" 
                                                                       onclick="return confirm('Remove this collaborator from the project?');" 
                                                                       title="Remove">
                                                                        <i class="ri-user-unfollow-line"></i> Remove
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

    <script src="assets/js/vendor.min.js"></script>
    <script src="assets/vendor/datatables.net/js/jquery.dataTables.min.js"></script>
    <script src="assets/vendor/datatables.net-bs5/js/dataTables.bootstrap5.min.js"></script>
    <script src="assets/vendor/datatables.net-responsive/js/dataTables.responsive.min.js"></script>
    <script src="assets/vendor/datatables.net-responsive-bs5/js/responsive.bootstrap5.min.js"></script>
    <script src="assets/js/app.min.js"></script>
    <?php if ($tablesOk && !empty($all_collaborations)): ?>
    <script>
        $(document).ready(function() {
            $('#collaborators-datatable').DataTable({
                responsive: true,
                order: [[5, 'desc']],
                columnDefs: [{ orderable: false, targets: [6] }],
                pageLength: 25
            });
        });
    </script>
    <?php endif; ?>
</body>
</html>
