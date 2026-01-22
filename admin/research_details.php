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

// Fetch research data
$query = "SELECT rp.*, r.fullname as creator_name, r.email as creator_email
          FROM research_projects rp
          LEFT JOIN registrations r ON rp.created_by = r.id
          WHERE rp.id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $research_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: research_list.php?error=Research project not found");
    exit();
}

$research = $result->fetch_assoc();
$stmt->close();

// Get collaborators
$collabQuery = "SELECT rc.*, r.fullname, r.email 
                FROM research_collaborators rc
                JOIN registrations r ON rc.member_id = r.id
                WHERE rc.research_id = ?";
$collabStmt = $conn->prepare($collabQuery);
$collabStmt->bind_param("i", $research_id);
$collabStmt->execute();
$collabResult = $collabStmt->get_result();

// Get files
$filesQuery = "SELECT rf.*, r.fullname as uploaded_by_name
               FROM research_files rf
               LEFT JOIN registrations r ON rf.uploaded_by = r.id
               WHERE rf.research_id = ?
               ORDER BY rf.uploaded_at DESC";
$filesStmt = $conn->prepare($filesQuery);
$filesStmt->bind_param("i", $research_id);
$filesStmt->execute();
$filesResult = $filesStmt->get_result();

// Get version history
$versionsQuery = "SELECT rv.*, r.fullname as changed_by_name
                  FROM research_versions rv
                  LEFT JOIN registrations r ON rv.changed_by = r.id
                  WHERE rv.research_id = ?
                  ORDER BY rv.changed_at DESC";
$versionsStmt = $conn->prepare($versionsQuery);
$versionsStmt->bind_param("i", $research_id);
$versionsStmt->execute();
$versionsResult = $versionsStmt->get_result();

// Get comments
$commentsQuery = "SELECT rc.*, r.fullname as commenter_name
                  FROM research_comments rc
                  LEFT JOIN registrations r ON rc.member_id = r.id
                  WHERE rc.research_id = ? AND rc.parent_comment_id IS NULL
                  ORDER BY rc.created_at DESC";
$commentsStmt = $conn->prepare($commentsQuery);
$commentsStmt->bind_param("i", $research_id);
$commentsStmt->execute();
$commentsResult = $commentsStmt->get_result();
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
                                <h4 class="page-title">Research Project Details</h4>
                                <div>
                                    <a href="edit_research.php?id=<?php echo $research_id; ?>" class="btn btn-primary">
                                        <i class="ri-edit-line"></i> Edit
                                    </a>
                                    <a href="research_list.php" class="btn btn-secondary">
                                        <i class="ri-arrow-left-line"></i> Back to List
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Main Information -->
                        <div class="col-lg-8">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title"><?php echo htmlspecialchars($research['title']); ?></h4>
                                </div>
                                <div class="card-body">
                                    <?php
                                    $statusColors = [
                                        'draft' => 'secondary',
                                        'in_progress' => 'warning',
                                        'completed' => 'success',
                                        'published' => 'primary',
                                        'archived' => 'dark'
                                    ];
                                    $statusColor = $statusColors[$research['status']] ?? 'secondary';
                                    $statusLabel = ucfirst(str_replace('_', ' ', $research['status']));
                                    ?>
                                    
                                    <div class="mb-3">
                                        <span class="badge bg-<?php echo $statusColor; ?> me-2"><?php echo $statusLabel; ?></span>
                                        <?php if ($research['research_type']): ?>
                                            <span class="badge bg-info"><?php echo ucfirst(str_replace('_', ' ', $research['research_type'])); ?></span>
                                        <?php endif; ?>
                                    </div>

                                    <div class="mb-3">
                                        <h6>Description</h6>
                                        <p><?php echo nl2br(htmlspecialchars($research['description'])); ?></p>
                                    </div>

                                    <?php if (!empty($research['abstract'])): ?>
                                    <div class="mb-3">
                                        <h6>Abstract</h6>
                                        <p class="text-muted"><?php echo nl2br(htmlspecialchars($research['abstract'])); ?></p>
                                    </div>
                                    <?php endif; ?>

                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <strong>Category:</strong> <?php echo htmlspecialchars($research['category'] ?? 'N/A'); ?>
                                        </div>
                                        <div class="col-md-6">
                                            <strong>Created By:</strong> <?php echo htmlspecialchars($research['creator_name'] ?? 'Unknown'); ?>
                                        </div>
                                        <?php if ($research['start_date']): ?>
                                        <div class="col-md-6 mt-2">
                                            <strong>Start Date:</strong> <?php echo date('M d, Y', strtotime($research['start_date'])); ?>
                                        </div>
                                        <?php endif; ?>
                                        <?php if ($research['end_date']): ?>
                                        <div class="col-md-6 mt-2">
                                            <strong>End Date:</strong> <?php echo date('M d, Y', strtotime($research['end_date'])); ?>
                                        </div>
                                        <?php endif; ?>
                                        <?php if ($research['publication_date']): ?>
                                        <div class="col-md-6 mt-2">
                                            <strong>Publication Date:</strong> <?php echo date('M d, Y', strtotime($research['publication_date'])); ?>
                                        </div>
                                        <?php endif; ?>
                                        <?php if ($research['doi']): ?>
                                        <div class="col-md-6 mt-2">
                                            <strong>DOI:</strong> <?php echo htmlspecialchars($research['doi']); ?>
                                        </div>
                                        <?php endif; ?>
                                        <?php if ($research['keywords']): ?>
                                        <div class="col-md-12 mt-2">
                                            <strong>Keywords:</strong> <?php echo htmlspecialchars($research['keywords']); ?>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Files Section -->
                            <div class="card mt-3">
                                <div class="card-header">
                                    <h4 class="header-title">Research Files</h4>
                                </div>
                                <div class="card-body">
                                    <?php
                                    if ($filesResult->num_rows > 0) {
                                        echo '<div class="table-responsive">';
                                        echo '<table class="table table-hover">';
                                        echo '<thead>';
                                        echo '<tr>';
                                        echo '<th>File Name</th>';
                                        echo '<th>Type</th>';
                                        echo '<th>Size</th>';
                                        echo '<th>Uploaded By</th>';
                                        echo '<th>Date</th>';
                                        echo '<th>Action</th>';
                                        echo '</tr>';
                                        echo '</thead>';
                                        echo '<tbody>';
                                        
                                        while ($file = $filesResult->fetch_assoc()) {
                                            echo '<tr>';
                                            echo '<td>' . htmlspecialchars($file['file_name']) . '</td>';
                                            echo '<td><span class="badge bg-secondary">' . htmlspecialchars($file['file_type'] ?? 'N/A') . '</span></td>';
                                            echo '<td>' . number_format($file['file_size'] / 1024, 2) . ' KB</td>';
                                            echo '<td>' . htmlspecialchars($file['uploaded_by_name'] ?? 'Unknown') . '</td>';
                                            echo '<td>' . date('M d, Y', strtotime($file['uploaded_at'])) . '</td>';
                                            echo '<td>';
                                            echo '<a href="../' . htmlspecialchars($file['file_path']) . '" target="_blank" class="btn btn-sm btn-info">';
                                            echo '<i class="ri-download-line"></i> Download';
                                            echo '</a>';
                                            echo '</td>';
                                            echo '</tr>';
                                        }
                                        
                                        echo '</tbody>';
                                        echo '</table>';
                                        echo '</div>';
                                    } else {
                                        echo '<p class="text-muted">No files uploaded yet.</p>';
                                    }
                                    $filesStmt->close();
                                    ?>
                                </div>
                            </div>

                            <!-- Version History -->
                            <div class="card mt-3">
                                <div class="card-header">
                                    <h4 class="header-title">Version History</h4>
                                </div>
                                <div class="card-body">
                                    <?php
                                    if ($versionsResult->num_rows > 0) {
                                        echo '<div class="timeline">';
                                        while ($version = $versionsResult->fetch_assoc()) {
                                            echo '<div class="mb-3 pb-3 border-bottom">';
                                            echo '<div class="d-flex justify-content-between">';
                                            echo '<div>';
                                            echo '<strong>Version ' . htmlspecialchars($version['version_number']) . '</strong>';
                                            echo '<br><small class="text-muted">Changed by: ' . htmlspecialchars($version['changed_by_name'] ?? 'Unknown') . '</small>';
                                            echo '<br><small class="text-muted">Date: ' . date('M d, Y H:i', strtotime($version['changed_at'])) . '</small>';
                                            echo '</div>';
                                            echo '</div>';
                                            if (!empty($version['changes_summary'])) {
                                                echo '<p class="mt-2"><strong>Changes:</strong> ' . htmlspecialchars($version['changes_summary']) . '</p>';
                                            }
                                            echo '</div>';
                                        }
                                        echo '</div>';
                                    } else {
                                        echo '<p class="text-muted">No version history available.</p>';
                                    }
                                    $versionsStmt->close();
                                    ?>
                                </div>
                            </div>
                        </div>

                        <!-- Sidebar -->
                        <div class="col-lg-4">
                            <!-- Collaborators -->
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Collaborators</h4>
                                </div>
                                <div class="card-body">
                                    <?php
                                    if ($collabResult->num_rows > 0) {
                                        echo '<div class="list-group">';
                                        while ($collab = $collabResult->fetch_assoc()) {
                                            $roleColors = [
                                                'lead' => 'primary',
                                                'co_author' => 'success',
                                                'contributor' => 'info',
                                                'advisor' => 'warning',
                                                'reviewer' => 'secondary'
                                            ];
                                            $roleColor = $roleColors[$collab['role']] ?? 'secondary';
                                            
                                            echo '<div class="list-group-item">';
                                            echo '<div class="d-flex justify-content-between align-items-center">';
                                            echo '<div>';
                                            echo '<strong>' . htmlspecialchars($collab['fullname']) . '</strong>';
                                            echo '<br><small class="text-muted">' . htmlspecialchars($collab['email']) . '</small>';
                                            echo '</div>';
                                            echo '<span class="badge bg-' . $roleColor . '">' . ucfirst(str_replace('_', ' ', $collab['role'])) . '</span>';
                                            echo '</div>';
                                            if ($collab['contribution_percentage']) {
                                                echo '<small class="text-muted">Contribution: ' . $collab['contribution_percentage'] . '%</small>';
                                            }
                                            echo '</div>';
                                        }
                                        echo '</div>';
                                    } else {
                                        echo '<p class="text-muted">No collaborators yet.</p>';
                                    }
                                    $collabStmt->close();
                                    ?>
                                    <div class="mt-3">
                                        <a href="research_collaborators.php?id=<?php echo $research_id; ?>" class="btn btn-sm btn-primary">
                                            <i class="ri-user-add-line"></i> Manage Collaborators
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Statistics -->
                            <div class="card mt-3">
                                <div class="card-header">
                                    <h4 class="header-title">Statistics</h4>
                                </div>
                                <div class="card-body">
                                    <div class="mb-2">
                                        <strong>Created:</strong> <?php echo date('M d, Y', strtotime($research['created_at'])); ?>
                                    </div>
                                    <div class="mb-2">
                                        <strong>Last Updated:</strong> <?php echo date('M d, Y', strtotime($research['updated_at'])); ?>
                                    </div>
                                    <div class="mb-2">
                                        <strong>Files:</strong> <?php echo $filesResult->num_rows; ?>
                                    </div>
                                    <div class="mb-2">
                                        <strong>Collaborators:</strong> <?php echo $collabResult->num_rows; ?>
                                    </div>
                                    <div class="mb-2">
                                        <strong>Versions:</strong> <?php echo $versionsResult->num_rows; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Comments -->
                            <div class="card mt-3">
                                <div class="card-header">
                                    <h4 class="header-title">Comments</h4>
                                </div>
                                <div class="card-body">
                                    <?php
                                    if ($commentsResult->num_rows > 0) {
                                        while ($comment = $commentsResult->fetch_assoc()) {
                                            echo '<div class="mb-3 pb-3 border-bottom">';
                                            echo '<strong>' . htmlspecialchars($comment['commenter_name'] ?? 'Unknown') . '</strong>';
                                            echo '<br><small class="text-muted">' . date('M d, Y H:i', strtotime($comment['created_at'])) . '</small>';
                                            echo '<p class="mt-2 mb-0">' . nl2br(htmlspecialchars($comment['comment'])) . '</p>';
                                            echo '</div>';
                                        }
                                    } else {
                                        echo '<p class="text-muted">No comments yet.</p>';
                                    }
                                    $commentsStmt->close();
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

