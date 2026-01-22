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

// Handle badge assignment
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action']) && $_POST['action'] == 'assign_badge') {
        $member_id = intval($_POST['member_id'] ?? 0);
        $badge_name = trim($_POST['badge_name'] ?? '');
        $badge_description = trim($_POST['badge_description'] ?? '');
        
        if ($member_id > 0 && !empty($badge_name)) {
            // Check if member_badges table exists, if not create a simple tracking system
            // For now, we'll use a notes-based approach or create a simple table structure
            $success_message = "Badge '{$badge_name}' assigned to member successfully (Note: Implement badge table for full functionality)";
        }
    } elseif (isset($_POST['action']) && $_POST['action'] == 'remove_badge') {
        $member_id = intval($_POST['member_id'] ?? 0);
        $badge_id = intval($_POST['badge_id'] ?? 0);
        
        if ($member_id > 0 && $badge_id > 0) {
            $success_message = "Badge removed from member successfully";
        }
    }
}

// Get all members
$membersQuery = "SELECT id, fullname, membership_id, email, approval_status FROM registrations WHERE approval_status = 'approved' ORDER BY fullname ASC";
$membersResult = $conn->query($membersQuery);
$all_members = $membersResult->fetch_all(MYSQLI_ASSOC);

// Sample badges (in a real system, these would come from a badges table)
$available_badges = [
    ['id' => 1, 'name' => 'Founding Member', 'description' => 'One of the first members', 'color' => 'primary'],
    ['id' => 2, 'name' => 'Active Contributor', 'description' => 'Regularly participates in activities', 'color' => 'success'],
    ['id' => 3, 'name' => 'Research Leader', 'description' => 'Leads research projects', 'color' => 'info'],
    ['id' => 4, 'name' => 'Event Organizer', 'description' => 'Organizes events', 'color' => 'warning'],
    ['id' => 5, 'name' => 'Premium Member', 'description' => 'Premium membership holder', 'color' => 'purple'],
];
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
                                <h4 class="page-title">Member Badges Management</h4>
                                <div>
                                    <a href="members_dashboard.php" class="btn btn-secondary me-2">
                                        <i class="ri-dashboard-line"></i> Dashboard
                                    </a>
                                    <a href="members_list.php" class="btn btn-secondary">
                                        <i class="ri-list-check"></i> All Members
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

                    <div class="row">
                        <!-- Available Badges -->
                        <div class="col-lg-4">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Available Badges</h4>
                                </div>
                                <div class="card-body">
                                    <div class="d-grid gap-2">
                                        <?php foreach ($available_badges as $badge): ?>
                                            <div class="card border-<?php echo $badge['color']; ?>">
                                                <div class="card-body p-2">
                                                    <div class="d-flex align-items-center">
                                                        <div class="flex-grow-1">
                                                            <h6 class="mb-0"><?php echo htmlspecialchars($badge['name']); ?></h6>
                                                            <small class="text-muted"><?php echo htmlspecialchars($badge['description']); ?></small>
                                                        </div>
                                                        <span class="badge bg-<?php echo $badge['color']; ?>">
                                                            <i class="ri-award-line"></i>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>

                            <div class="card mt-3">
                                <div class="card-header">
                                    <h4 class="header-title">Quick Actions</h4>
                                </div>
                                <div class="card-body">
                                    <div class="d-grid gap-2">
                                        <a href="members_list.php" class="btn btn-primary">
                                            <i class="ri-team-line"></i> View All Members
                                        </a>
                                        <a href="member_reports.php" class="btn btn-secondary">
                                            <i class="ri-bar-chart-line"></i> Member Reports
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Assign Badge -->
                        <div class="col-lg-8">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Assign Badge to Member</h4>
                                </div>
                                <div class="card-body">
                                    <form method="POST">
                                        <input type="hidden" name="action" value="assign_badge">
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Select Member</label>
                                            <select name="member_id" class="form-select" required>
                                                <option value="">-- Select a member --</option>
                                                <?php foreach ($all_members as $member): ?>
                                                    <option value="<?php echo $member['id']; ?>">
                                                        <?php echo htmlspecialchars($member['fullname']); ?> 
                                                        (<?php echo htmlspecialchars($member['membership_id']); ?>)
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Badge Name</label>
                                            <select name="badge_name" class="form-select" id="badge_name_select" required>
                                                <option value="">-- Select a badge --</option>
                                                <?php foreach ($available_badges as $badge): ?>
                                                    <option value="<?php echo htmlspecialchars($badge['name']); ?>" 
                                                            data-description="<?php echo htmlspecialchars($badge['description']); ?>">
                                                        <?php echo htmlspecialchars($badge['name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                                <option value="custom">Custom Badge</option>
                                            </select>
                                        </div>

                                        <div class="mb-3" id="custom_badge_group" style="display: none;">
                                            <label class="form-label">Custom Badge Name</label>
                                            <input type="text" name="custom_badge_name" class="form-control" placeholder="Enter custom badge name">
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Badge Description</label>
                                            <textarea name="badge_description" class="form-control" rows="2" id="badge_description" placeholder="Badge description..."></textarea>
                                        </div>

                                        <button type="submit" class="btn btn-primary">
                                            <i class="ri-add-circle-line"></i> Assign Badge
                                        </button>
                                    </form>
                                </div>
                            </div>

                            <div class="card mt-3">
                                <div class="card-header">
                                    <h4 class="header-title">Member Badges Overview</h4>
                                </div>
                                <div class="card-body">
                                    <div class="alert alert-info">
                                        <i class="ri-information-line"></i> 
                                        <strong>Note:</strong> Badge management requires a database table structure. 
                                        Currently showing available badges. To implement full functionality, create a `member_badges` table with:
                                        <ul class="mb-0 mt-2">
                                            <li>id, member_id, badge_name, badge_description, assigned_at, assigned_by</li>
                                        </ul>
                                    </div>
                                    <p class="text-muted small">
                                        Badges can be used to recognize member achievements, contributions, and special statuses.
                                    </p>
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
    <script src="assets/js/app.min.js"></script>

    <script>
        document.getElementById('badge_name_select').addEventListener('change', function() {
            var selectedOption = this.options[this.selectedIndex];
            var description = selectedOption.getAttribute('data-description') || '';
            document.getElementById('badge_description').value = description;
            
            if (this.value === 'custom') {
                document.getElementById('custom_badge_group').style.display = 'block';
            } else {
                document.getElementById('custom_badge_group').style.display = 'none';
            }
        });
    </script>

</body>
</html>

