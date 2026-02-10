<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: auth-login.php");
    exit();
}

try {
    include 'include/conn.php';
    include 'header.php';
} catch (Exception $e) {
    die("Error loading includes: " . $e->getMessage());
}

$success_message = '';
$error_message = '';

// Ensure member_badges table exists
$check_table = "SHOW TABLES LIKE 'member_badges'";
$table_exists = $conn->query($check_table)->num_rows > 0;

if (!$table_exists) {
    // Create member_badges table
    $create_table = "CREATE TABLE IF NOT EXISTS `member_badges` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `member_id` INT(11) NOT NULL,
        `badge_name` VARCHAR(100) NOT NULL,
        `badge_description` TEXT NULL,
        `assigned_by` INT(11) NULL,
        `assigned_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `is_active` TINYINT(1) DEFAULT 1,
        PRIMARY KEY (`id`),
        INDEX `idx_member_id` (`member_id`),
        INDEX `idx_badge_name` (`badge_name`),
        INDEX `idx_is_active` (`is_active`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    if (!$conn->query($create_table)) {
        $error_message = "Error creating member_badges table: " . $conn->error;
    } else {
        // Table created successfully, update flag
        $table_exists = true;
    }
} else {
    // Check and add missing columns
    $columns_to_add = [];
    
    // Check badge_description
    $check_desc = "SHOW COLUMNS FROM member_badges LIKE 'badge_description'";
    $desc_result = $conn->query($check_desc);
    if ($desc_result && $desc_result->num_rows == 0) {
        $columns_to_add[] = "ADD COLUMN badge_description TEXT NULL AFTER badge_name";
    }
    
    // Check assigned_by
    $check_assigned = "SHOW COLUMNS FROM member_badges LIKE 'assigned_by'";
    $assigned_result = $conn->query($check_assigned);
    if ($assigned_result && $assigned_result->num_rows == 0) {
        if (empty($columns_to_add)) {
            $columns_to_add[] = "ADD COLUMN assigned_by INT(11) NULL AFTER badge_name";
        } else {
            $columns_to_add[] = "ADD COLUMN assigned_by INT(11) NULL AFTER badge_description";
        }
    }
    
    // Check assigned_at
    $check_assigned_at = "SHOW COLUMNS FROM member_badges LIKE 'assigned_at'";
    $assigned_at_result = $conn->query($check_assigned_at);
    if ($assigned_at_result && $assigned_at_result->num_rows == 0) {
        $columns_to_add[] = "ADD COLUMN assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER assigned_by";
    }
    
    // Check is_active
    $check_active = "SHOW COLUMNS FROM member_badges LIKE 'is_active'";
    $active_result = $conn->query($check_active);
    if ($active_result && $active_result->num_rows == 0) {
        $columns_to_add[] = "ADD COLUMN is_active TINYINT(1) DEFAULT 1 AFTER assigned_at";
    }
    
    // Add missing columns one by one to avoid conflicts
    foreach ($columns_to_add as $alter_sql) {
        $alter_table = "ALTER TABLE member_badges " . $alter_sql;
        if (!$conn->query($alter_table)) {
            // Don't show error for duplicate column attempts
            if (strpos($conn->error, 'Duplicate column name') === false) {
                $error_message = "Error adding columns: " . $conn->error;
            }
        }
    }
}

// Handle badge assignment
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action']) && $_POST['action'] == 'assign_badge') {
        $member_id = intval($_POST['member_id'] ?? 0);
        $badge_name = trim($_POST['badge_name'] ?? '');
        
        // Handle custom badge
        if ($badge_name === 'custom') {
            $badge_name = trim($_POST['custom_badge_name'] ?? '');
        }
        
        $badge_description = trim($_POST['badge_description'] ?? '');
        $assigned_by = $_SESSION['user_id'];
        
        if ($member_id > 0 && !empty($badge_name)) {
            if (!$table_exists) {
                $error_message = "Badge table does not exist. Please refresh the page.";
            } else {
                // Check if is_active column exists
                $check_active = "SHOW COLUMNS FROM member_badges LIKE 'is_active'";
                $active_check = $conn->query($check_active);
                $has_is_active = ($active_check && $active_check->num_rows > 0);
                
                // Check if member already has this badge (active)
                if ($has_is_active) {
                    $check_query = "SELECT id FROM member_badges WHERE member_id = ? AND badge_name = ? AND is_active = 1";
                } else {
                    $check_query = "SELECT id FROM member_badges WHERE member_id = ? AND badge_name = ?";
                }
                $check_stmt = $conn->prepare($check_query);
                if ($check_stmt) {
                    $check_stmt->bind_param("is", $member_id, $badge_name);
                    $check_stmt->execute();
                    $existing = $check_stmt->get_result();
                    
                    if ($existing && $existing->num_rows > 0) {
                        $error_message = "This badge is already assigned to this member!";
                    } else {
                        // Check which columns exist
                        $check_desc = "SHOW COLUMNS FROM member_badges LIKE 'badge_description'";
                        $desc_check = $conn->query($check_desc);
                        $has_description = ($desc_check && $desc_check->num_rows > 0);
                        
                        $check_assigned = "SHOW COLUMNS FROM member_badges LIKE 'assigned_by'";
                        $assigned_check = $conn->query($check_assigned);
                        $has_assigned_by = ($assigned_check && $assigned_check->num_rows > 0);
                        
                        // Build INSERT query based on available columns
                        $assign_stmt = null;
                        if ($has_description && $has_assigned_by) {
                            $assign_query = "INSERT INTO member_badges (member_id, badge_name, badge_description, assigned_by) VALUES (?, ?, ?, ?)";
                            $assign_stmt = $conn->prepare($assign_query);
                            if ($assign_stmt) {
                                $assign_stmt->bind_param("issi", $member_id, $badge_name, $badge_description, $assigned_by);
                            }
                        } elseif ($has_description) {
                            $assign_query = "INSERT INTO member_badges (member_id, badge_name, badge_description) VALUES (?, ?, ?)";
                            $assign_stmt = $conn->prepare($assign_query);
                            if ($assign_stmt) {
                                $assign_stmt->bind_param("iss", $member_id, $badge_name, $badge_description);
                            }
                        } elseif ($has_assigned_by) {
                            $assign_query = "INSERT INTO member_badges (member_id, badge_name, assigned_by) VALUES (?, ?, ?)";
                            $assign_stmt = $conn->prepare($assign_query);
                            if ($assign_stmt) {
                                $assign_stmt->bind_param("isi", $member_id, $badge_name, $assigned_by);
                            }
                        } else {
                            $assign_query = "INSERT INTO member_badges (member_id, badge_name) VALUES (?, ?)";
                            $assign_stmt = $conn->prepare($assign_query);
                            if ($assign_stmt) {
                                $assign_stmt->bind_param("is", $member_id, $badge_name);
                            }
                        }
                        
                        if ($assign_stmt) {
                            if ($assign_stmt->execute()) {
                                $success_message = "Badge '{$badge_name}' assigned to member successfully!";
                            } else {
                                $error_message = "Error assigning badge: " . $assign_stmt->error;
                            }
                            $assign_stmt->close();
                        } else {
                            $error_message = "Error preparing query: " . $conn->error;
                        }
                    }
                    $check_stmt->close();
                } else {
                    $error_message = "Error preparing check query: " . $conn->error;
                }
            }
        } else {
            $error_message = "Please select a member and badge name.";
        }
    } elseif (isset($_POST['action']) && $_POST['action'] == 'remove_badge') {
        $badge_id = intval($_POST['badge_id'] ?? 0);
        
        if ($badge_id > 0) {
            if (!$table_exists) {
                $error_message = "Badge table does not exist.";
            } else {
                // Check if is_active column exists
                $check_active = "SHOW COLUMNS FROM member_badges LIKE 'is_active'";
                $active_check = $conn->query($check_active);
                $has_is_active = ($active_check && $active_check->num_rows > 0);
                
                if ($has_is_active) {
                    // Soft delete - set is_active to 0
                    $remove_query = "UPDATE member_badges SET is_active = 0 WHERE id = ?";
                } else {
                    // Hard delete if is_active column doesn't exist
                    $remove_query = "DELETE FROM member_badges WHERE id = ?";
                }
                
                $remove_stmt = $conn->prepare($remove_query);
                if ($remove_stmt) {
                    $remove_stmt->bind_param("i", $badge_id);
                    
                    if ($remove_stmt->execute()) {
                        $success_message = "Badge removed from member successfully!";
                    } else {
                        $error_message = "Error removing badge: " . $remove_stmt->error;
                    }
                    $remove_stmt->close();
                } else {
                    $error_message = "Error preparing remove query: " . $conn->error;
                }
            }
        }
    }
}

// Get all members
$all_members = [];
$membersQuery = "SELECT id, fullname, membership_id, email, approval_status FROM registrations WHERE approval_status = 'approved' ORDER BY fullname ASC";
$membersResult = $conn->query($membersQuery);
if ($membersResult) {
    $all_members = $membersResult->fetch_all(MYSQLI_ASSOC);
} else {
    $error_message = "Error loading members: " . $conn->error;
}

// Get all assigned badges with member info
$assigned_badges = [];
if ($table_exists) {
    // Check which columns exist
    $check_assigned = "SHOW COLUMNS FROM member_badges LIKE 'assigned_by'";
    $assigned_check = $conn->query($check_assigned);
    $has_assigned_by = ($assigned_check && $assigned_check->num_rows > 0);
    
    $check_desc = "SHOW COLUMNS FROM member_badges LIKE 'badge_description'";
    $desc_check = $conn->query($check_desc);
    $has_description = ($desc_check && $desc_check->num_rows > 0);
    
    $check_assigned_at = "SHOW COLUMNS FROM member_badges LIKE 'assigned_at'";
    $assigned_at_check = $conn->query($check_assigned_at);
    $has_assigned_at = ($assigned_at_check && $assigned_at_check->num_rows > 0);
    
    $check_active = "SHOW COLUMNS FROM member_badges LIKE 'is_active'";
    $active_check = $conn->query($check_active);
    $has_is_active = ($active_check && $active_check->num_rows > 0);
    
    // Build WHERE clause
    $where_clause = "";
    if ($has_is_active) {
        $where_clause = "WHERE mb.is_active = 1";
    }
    
    // Build ORDER BY clause
    $order_clause = "";
    if ($has_assigned_at) {
        $order_clause = "ORDER BY mb.assigned_at DESC";
    } else {
        $order_clause = "ORDER BY mb.id DESC";
    }
    
    // Build query based on available columns
    if ($has_assigned_by) {
        $assigned_badges_query = "SELECT mb.*, r.fullname, r.membership_id, r.email, u.username as assigned_by_name
                                 FROM member_badges mb
                                 JOIN registrations r ON mb.member_id = r.id
                                 LEFT JOIN user u ON mb.assigned_by = u.id
                                 $where_clause
                                 $order_clause";
    } else {
        $assigned_badges_query = "SELECT mb.*, r.fullname, r.membership_id, r.email, 'System' as assigned_by_name
                                 FROM member_badges mb
                                 JOIN registrations r ON mb.member_id = r.id
                                 $where_clause
                                 $order_clause";
    }
    
    $assigned_badges_result = $conn->query($assigned_badges_query);
    if ($assigned_badges_result) {
        $assigned_badges = $assigned_badges_result->fetch_all(MYSQLI_ASSOC);
        // Add missing fields as empty if columns don't exist
        foreach ($assigned_badges as &$badge) {
            if (!$has_description && !isset($badge['badge_description'])) {
                $badge['badge_description'] = '';
            }
            if (!$has_assigned_at && !isset($badge['assigned_at'])) {
                $badge['assigned_at'] = date('Y-m-d H:i:s');
            }
        }
    } else {
        $error_message = "Error loading badges: " . $conn->error;
    }
} else {
    $assigned_badges = [];
}

// Get badge statistics
$badge_stats = [];
if ($table_exists) {
    $badge_stats_query = "SELECT badge_name, COUNT(*) as count FROM member_badges WHERE is_active = 1 GROUP BY badge_name ORDER BY count DESC";
    $badge_stats_result = $conn->query($badge_stats_query);
    if ($badge_stats_result) {
        $badge_stats = $badge_stats_result->fetch_all(MYSQLI_ASSOC);
    }
} else {
    $badge_stats = [];
}

// Sample badges (in a real system, these would come from a badges table)
$available_badges = [
    ['id' => 1, 'name' => 'Founding Member', 'description' => 'One of the first members', 'color' => 'primary'],
    ['id' => 2, 'name' => 'Active Contributor', 'description' => 'Regularly participates in activities', 'color' => 'success'],
    ['id' => 3, 'name' => 'Research Leader', 'description' => 'Leads research projects', 'color' => 'info'],
    ['id' => 4, 'name' => 'Event Organizer', 'description' => 'Organizes events', 'color' => 'warning'],
    ['id' => 5, 'name' => 'Premium Member', 'description' => 'Premium membership holder', 'color' => 'purple'],
    ['id' => 6, 'name' => 'Community Champion', 'description' => 'Outstanding community contribution', 'color' => 'danger'],
    ['id' => 7, 'name' => 'Resource Expert', 'description' => 'Expert in resource management', 'color' => 'secondary'],
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
                            <div class="page-title-box mb-3">
                                <h4 class="page-title mb-1">Member Badges Management</h4>
                                <p class="page-subtitle mb-2">Assign and manage badges for members</p>
                                <div class="d-inline-flex flex-row flex-nowrap gap-2">
                                    <a href="members_dashboard.php" class="btn btn-secondary btn-sm">
                                        <i class="ri-dashboard-line"></i> Dashboard
                                    </a>
                                    <a href="members_list.php" class="btn btn-secondary btn-sm">
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

                    <!-- Statistics Cards -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <div class="card widget-flat text-bg-primary">
                                <div class="card-body">
                                    <div class="float-end">
                                        <i class="ri-award-line widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0">Total Badges Assigned</h6>
                                    <h2 class="my-2"><?php echo number_format(count($assigned_badges)); ?></h2>
                                    <span class="text-white-50 small">Active badges</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card widget-flat text-bg-success">
                                <div class="card-body">
                                    <div class="float-end">
                                        <i class="ri-user-star-line widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0">Unique Badge Types</h6>
                                    <h2 class="my-2"><?php echo number_format(count($badge_stats)); ?></h2>
                                    <span class="text-white-50 small">Different badges</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card widget-flat text-bg-info">
                                <div class="card-body">
                                    <div class="float-end">
                                        <i class="ri-team-line widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0">Members with Badges</h6>
                                    <h2 class="my-2">
                                        <?php 
                                        $unique_members = count(array_unique(array_column($assigned_badges, 'member_id')));
                                        echo number_format($unique_members); 
                                        ?>
                                    </h2>
                                    <span class="text-white-50 small">Badge holders</span>
                                </div>
                            </div>
                        </div>
                    </div>

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
                                    <h4 class="header-title">Badge Statistics</h4>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($badge_stats)): ?>
                                        <p class="text-muted mb-0">No badges assigned yet.</p>
                                    <?php else: ?>
                                        <div class="list-group list-group-flush">
                                            <?php foreach ($badge_stats as $stat): ?>
                                                <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                                    <span><?php echo htmlspecialchars($stat['badge_name']); ?></span>
                                                    <span class="badge bg-primary rounded-pill"><?php echo $stat['count']; ?></span>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
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
                                            <label class="form-label">Custom Badge Name *</label>
                                            <input type="text" name="custom_badge_name" class="form-control" placeholder="Enter custom badge name">
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Badge Description (Optional)</label>
                                            <textarea name="badge_description" class="form-control" rows="2" id="badge_description" placeholder="Badge description..."></textarea>
                                        </div>

                                        <button type="submit" class="btn btn-primary">
                                            <i class="ri-add-circle-line"></i> Assign Badge
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Assigned Badges Table -->
                    <div class="row mt-3">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h4 class="header-title">All Assigned Badges</h4>
                                    <span class="badge bg-primary"><?php echo count($assigned_badges); ?> badges</span>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($assigned_badges)): ?>
                                        <div class="alert alert-info">
                                            <i class="ri-information-line"></i> No badges have been assigned yet. Use the form above to assign badges to members.
                                        </div>
                                    <?php else: ?>
                                        <!-- Desktop table -->
                                        <div class="table-responsive d-none d-md-block">
                                            <table class="table table-hover mb-0" id="badgesTable">
                                                <thead>
                                                    <tr>
                                                        <th>Member</th>
                                                        <th>Membership ID</th>
                                                        <th>Badge Name</th>
                                                        <th>Description</th>
                                                        <th>Assigned By</th>
                                                        <th>Assigned Date</th>
                                                        <th>Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($assigned_badges as $badge): ?>
                                                        <tr>
                                                            <td>
                                                                <strong><?php echo htmlspecialchars($badge['fullname']); ?></strong><br>
                                                                <small class="text-muted"><?php echo htmlspecialchars($badge['email']); ?></small>
                                                            </td>
                                                            <td><?php echo htmlspecialchars($badge['membership_id']); ?></td>
                                                            <td>
                                                                <span class="badge bg-primary">
                                                                    <i class="ri-award-line"></i> <?php echo htmlspecialchars($badge['badge_name']); ?>
                                                                </span>
                                                            </td>
                                                            <td><?php echo htmlspecialchars($badge['badge_description'] ?: 'N/A'); ?></td>
                                                            <td><?php echo htmlspecialchars($badge['assigned_by_name'] ?: 'System'); ?></td>
                                                            <td><?php echo date('M d, Y', strtotime($badge['assigned_at'])); ?></td>
                                                            <td>
                                                                <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to remove this badge?');">
                                                                    <input type="hidden" name="action" value="remove_badge">
                                                                    <input type="hidden" name="badge_id" value="<?php echo $badge['id']; ?>">
                                                                    <button type="submit" class="btn btn-sm btn-danger">
                                                                        <i class="ri-delete-bin-line"></i> Remove
                                                                    </button>
                                                                </form>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>

                                        <!-- Mobile card list -->
                                        <div class="d-block d-md-none">
                                            <?php $badgeIndex = 1; ?>
                                            <?php foreach ($assigned_badges as $badge): ?>
                                                <div class="card mb-2 mobile-badge-card">
                                                    <div class="card-body d-flex justify-content-between align-items-center">
                                                        <div class="d-flex align-items-center gap-2">
                                                            <span class="badge bg-primary rounded-pill"><?php echo $badgeIndex++; ?></span>
                                                            <div class="d-flex flex-column">
                                                                <span class="fw-semibold text-truncate" style="max-width: 160px;">
                                                                    <?php echo htmlspecialchars($badge['fullname']); ?>
                                                                </span>
                                                                <small class="text-muted">
                                                                    ID: <?php echo htmlspecialchars($badge['membership_id']); ?>
                                                                </small>
                                                                <small>
                                                                    <span class="badge bg-primary mt-1">
                                                                        <i class="ri-award-line"></i> <?php echo htmlspecialchars($badge['badge_name']); ?>
                                                                    </span>
                                                                </small>
                                                            </div>
                                                        </div>
                                                        <button type="button"
                                                            class="btn btn-link text-muted p-0 mobile-badge-more"
                                                            aria-label="View badge detail">
                                                            <i class="ri-more-2-fill" style="font-size: 1.4rem;"></i>
                                                        </button>
                                                    </div>

                                                    <!-- Hidden detail for modal -->
                                                    <div class="d-none mobile-badge-detail-content">
                                                        <h5 class="mb-1"><?php echo htmlspecialchars($badge['fullname']); ?></h5>
                                                        <p class="mb-1">
                                                            <strong>Membership ID:</strong>
                                                            <code><?php echo htmlspecialchars($badge['membership_id']); ?></code>
                                                        </p>
                                                        <p class="mb-1">
                                                            <strong>Badge:</strong>
                                                            <span class="badge bg-primary">
                                                                <i class="ri-award-line"></i> <?php echo htmlspecialchars($badge['badge_name']); ?>
                                                            </span>
                                                        </p>
                                                        <p class="mb-1">
                                                            <strong>Description:</strong>
                                                            <?php echo htmlspecialchars($badge['badge_description'] ?: 'N/A'); ?>
                                                        </p>
                                                        <p class="mb-1">
                                                            <strong>Assigned By:</strong>
                                                            <?php echo htmlspecialchars($badge['assigned_by_name'] ?: 'System'); ?>
                                                        </p>
                                                        <p class="mb-3">
                                                            <strong>Assigned Date:</strong>
                                                            <?php echo date('M d, Y', strtotime($badge['assigned_at'])); ?>
                                                        </p>
                                                        <form method="POST" onsubmit="return confirm('Are you sure you want to remove this badge?');">
                                                            <input type="hidden" name="action" value="remove_badge">
                                                            <input type="hidden" name="badge_id" value="<?php echo $badge['id']; ?>">
                                                            <button type="submit" class="btn btn-danger btn-sm">
                                                                <i class="ri-delete-bin-line"></i> Remove Badge
                                                            </button>
                                                        </form>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
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

    <!-- Mobile badge detail modal -->
    <div class="modal fade" id="badgeDetailModal" tabindex="-1" aria-labelledby="badgeDetailModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="badgeDetailModalLabel">Badge Detail</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Filled dynamically -->
                </div>
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
            // Initialize DataTable (desktop)
            if ($.fn.DataTable) {
                $('#badgesTable').DataTable({
                    "pageLength": 25,
                    "order": [[5, "desc"]], // Sort by assigned date
                    "language": {
                        "search": "Search:",
                        "lengthMenu": "Show _MENU_ entries per page",
                        "info": "Showing _START_ to _END_ of _TOTAL_ entries"
                    }
                });
            }

            // Badge name select handler
            document.getElementById('badge_name_select').addEventListener('change', function() {
                var selectedOption = this.options[this.selectedIndex];
                var description = selectedOption.getAttribute('data-description') || '';
                document.getElementById('badge_description').value = description;
                
                if (this.value === 'custom') {
                    document.getElementById('custom_badge_group').style.display = 'block';
                    document.querySelector('input[name="custom_badge_name"]').required = true;
                } else {
                    document.getElementById('custom_badge_group').style.display = 'none';
                    document.querySelector('input[name="custom_badge_name"]').required = false;
                }
            });

            // Mobile badge detail modal
            $(document).on('click', '.mobile-badge-more', function () {
                var card = $(this).closest('.mobile-badge-card');
                var contentHtml = card.find('.mobile-badge-detail-content').html();

                $('#badgeDetailModal .modal-body').html(contentHtml);

                if (typeof bootstrap !== "undefined" && bootstrap.Modal) {
                    var detailModal = new bootstrap.Modal(document.getElementById('badgeDetailModal'));
                    detailModal.show();
                } else {
                    $('#badgeDetailModal').modal('show');
                }
            });
        });
    </script>

</body>
</html>
