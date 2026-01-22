<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: auth-login.php");
    exit();
}

include 'include/conn.php';
include 'header.php';

// Get research ID
$research_id = intval($_GET['id'] ?? 0);

if ($research_id <= 0) {
    header("Location: research_list.php?error=Invalid research ID");
    exit();
}

// Get research details
$researchQuery = "SELECT * FROM research_projects WHERE id = ?";
$researchStmt = $conn->prepare($researchQuery);
$researchStmt->bind_param("i", $research_id);
$researchStmt->execute();
$researchResult = $researchStmt->get_result();

if ($researchResult->num_rows === 0) {
    header("Location: research_list.php?error=Research project not found");
    exit();
}

$research = $researchResult->fetch_assoc();
$researchStmt->close();

// Get current collaborators
$collabQuery = "SELECT rc.*, r.fullname, r.email 
                FROM research_collaborators rc
                JOIN registrations r ON rc.member_id = r.id
                WHERE rc.research_id = ?
                ORDER BY rc.role, rc.joined_at";
$collabStmt = $conn->prepare($collabQuery);
$collabStmt->bind_param("i", $research_id);
$collabStmt->execute();
$collabResult = $collabStmt->get_result();

// Get all members for adding collaborators
$membersQuery = "SELECT id, fullname, email FROM registrations ORDER BY fullname";
$membersResult = mysqli_query($conn, $membersQuery);
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
                                <h4 class="page-title">Manage Collaborators</h4>
                                <div>
                                    <a href="research_details.php?id=<?php echo $research_id; ?>" class="btn btn-info">
                                        <i class="ri-eye-line"></i> View Research
                                    </a>
                                    <a href="research_list.php" class="btn btn-secondary">
                                        <i class="ri-arrow-left-line"></i> Back to List
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Research: <?php echo htmlspecialchars($research['title']); ?></h4>
                                    <p class="text-muted mb-0">Manage collaborators for this research project</p>
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
                                    ?>

                                    <!-- Add Collaborator Form -->
                                    <div class="card bg-light mb-4">
                                        <div class="card-body">
                                            <h5 class="card-title">Add New Collaborator</h5>
                                            <form action="include/collaborator_handler.php" method="post">
                                                <input type="hidden" name="research_id" value="<?php echo $research_id; ?>">
                                                <input type="hidden" name="action" value="add">
                                                
                                                <div class="row g-3">
                                                    <div class="col-md-5">
                                                        <label class="form-label">Member *</label>
                                                        <select name="member_id" class="form-select" required>
                                                            <option value="">Select Member</option>
                                                            <?php
                                                            mysqli_data_seek($membersResult, 0);
                                                            while ($member = mysqli_fetch_assoc($membersResult)) {
                                                                // Check if already a collaborator
                                                                $checkQuery = "SELECT id FROM research_collaborators WHERE research_id = ? AND member_id = ?";
                                                                $checkStmt = $conn->prepare($checkQuery);
                                                                $checkStmt->bind_param("ii", $research_id, $member['id']);
                                                                $checkStmt->execute();
                                                                $checkResult = $checkStmt->get_result();
                                                                $isCollaborator = $checkResult->num_rows > 0;
                                                                $checkStmt->close();
                                                                
                                                                if (!$isCollaborator) {
                                                                    echo '<option value="' . $member['id'] . '">' . 
                                                                         htmlspecialchars($member['fullname'] . ' (' . $member['email'] . ')') . 
                                                                         '</option>';
                                                                }
                                                            }
                                                            ?>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <label class="form-label">Role *</label>
                                                        <select name="role" class="form-select" required>
                                                            <option value="contributor" selected>Contributor</option>
                                                            <option value="lead">Lead</option>
                                                            <option value="co_author">Co-Author</option>
                                                            <option value="advisor">Advisor</option>
                                                            <option value="reviewer">Reviewer</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-2">
                                                        <label class="form-label">Contribution %</label>
                                                        <input type="number" name="contribution_percentage" class="form-control" 
                                                               min="0" max="100" step="0.01" placeholder="0-100">
                                                    </div>
                                                    <div class="col-md-2 d-flex align-items-end">
                                                        <button type="submit" class="btn btn-primary w-100">
                                                            <i class="ri-user-add-line"></i> Add
                                                        </button>
                                                    </div>
                                                </div>
                                            </form>
                                        </div>
                                    </div>

                                    <!-- Current Collaborators -->
                                    <h5 class="mb-3">Current Collaborators</h5>
                                    <?php
                                    if ($collabResult->num_rows > 0) {
                                        echo '<div class="table-responsive">';
                                        echo '<table class="table table-striped table-hover">';
                                        echo '<thead>';
                                        echo '<tr>';
                                        echo '<th>Member</th>';
                                        echo '<th>Email</th>';
                                        echo '<th>Role</th>';
                                        echo '<th>Contribution</th>';
                                        echo '<th>Joined</th>';
                                        echo '<th>Action</th>';
                                        echo '</tr>';
                                        echo '</thead>';
                                        echo '<tbody>';
                                        
                                        while ($collab = $collabResult->fetch_assoc()) {
                                            $roleColors = [
                                                'lead' => 'primary',
                                                'co_author' => 'success',
                                                'contributor' => 'info',
                                                'advisor' => 'warning',
                                                'reviewer' => 'secondary'
                                            ];
                                            $roleColor = $roleColors[$collab['role']] ?? 'secondary';
                                            
                                            echo '<tr>';
                                            echo '<td><strong>' . htmlspecialchars($collab['fullname']) . '</strong></td>';
                                            echo '<td>' . htmlspecialchars($collab['email']) . '</td>';
                                            echo '<td><span class="badge bg-' . $roleColor . '">' . ucfirst(str_replace('_', ' ', $collab['role'])) . '</span></td>';
                                            echo '<td>' . ($collab['contribution_percentage'] ? $collab['contribution_percentage'] . '%' : 'N/A') . '</td>';
                                            echo '<td>' . date('M d, Y', strtotime($collab['joined_at'])) . '</td>';
                                            echo '<td>';
                                            echo '<div class="btn-group" role="group">';
                                            echo '<a href="include/collaborator_handler.php?action=remove&research_id=' . $research_id . '&collab_id=' . $collab['id'] . '" ';
                                            echo 'class="btn btn-sm btn-danger" onclick="return confirm(\'Are you sure you want to remove this collaborator?\');">';
                                            echo '<i class="ri-user-unfollow-line"></i> Remove';
                                            echo '</a>';
                                            echo '</div>';
                                            echo '</td>';
                                            echo '</tr>';
                                        }
                                        
                                        echo '</tbody>';
                                        echo '</table>';
                                        echo '</div>';
                                    } else {
                                        echo '<div class="alert alert-info">No collaborators yet. Add one using the form above.</div>';
                                    }
                                    $collabStmt->close();
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
    <script src="assets/js/app.min.js"></script>
</body>
</html>

<?php
mysqli_close($conn);
?>

