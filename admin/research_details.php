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

// Check research_comments table and build "Add comment" authors (created_by + collaborators)
$comments_table_exists = false;
$comments = [];
$comment_authors = [];
$add_comment_error = '';
$add_comment_success = '';

$tc = @$conn->query("SHOW TABLES LIKE 'research_comments'");
if ($tc && $tc->num_rows > 0) {
    $comments_table_exists = true;
    $comment_authors[(int)$research['created_by']] = $research['creator_name'] ?? 'Project owner';
    while ($row = $collabResult->fetch_assoc()) {
        $comment_authors[(int)$row['member_id']] = $row['fullname'] ?? ('Member #' . $row['member_id']);
    }
    $collabResult->data_seek(0);

    // Handle add comment
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_comment'])) {
        $member_id = (int)($_POST['member_id'] ?? 0);
        $comment_text = trim($_POST['comment'] ?? '');
        if (empty($comment_text)) {
            $add_comment_error = 'Comment cannot be empty.';
        } elseif (!$member_id || !isset($comment_authors[$member_id])) {
            $add_comment_error = 'Please select a valid author.';
        } else {
            $ins = $conn->prepare("INSERT INTO research_comments (research_id, member_id, comment) VALUES (?, ?, ?)");
            if ($ins) {
                $ins->bind_param("iis", $research_id, $member_id, $comment_text);
                if ($ins->execute()) {
                    $ins->close();
                    header("Location: research_details.php?id=" . $research_id . "&comment_added=1");
                    exit;
                }
                $ins->close();
            }
            $add_comment_error = 'Failed to save comment. Please try again.';
        }
    }
    if (isset($_GET['comment_added'])) {
        $add_comment_success = 'Comment added successfully.';
    }

    $commentsStmt = @$conn->prepare("SELECT rc.*, r.fullname as commenter_name
                  FROM research_comments rc
                  LEFT JOIN registrations r ON rc.member_id = r.id
                  WHERE rc.research_id = ? AND rc.parent_comment_id IS NULL
                  ORDER BY rc.created_at DESC");
    if ($commentsStmt) {
        $commentsStmt->bind_param("i", $research_id);
        $commentsStmt->execute();
        $res = $commentsStmt->get_result();
        if ($res) {
            $comments = $res->fetch_all(MYSQLI_ASSOC);
        }
        $commentsStmt->close();
    }
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
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h4 class="header-title mb-0">Comments</h4>
                                    <a href="research_comments.php?research_id=<?php echo $research_id; ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="ri-chat-3-line"></i> Manage All Comments
                                    </a>
                                </div>
                                <div class="card-body">
                                    <?php if (!$comments_table_exists): ?>
                                        <p class="text-muted mb-0">Comments are not available. The <code>research_comments</code> table has not been created.</p>
                                    <?php else: ?>
                                        <?php if ($add_comment_success): ?>
                                            <div class="alert alert-success alert-dismissible fade show py-2">
                                                <?php echo htmlspecialchars($add_comment_success); ?>
                                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                            </div>
                                        <?php endif; ?>
                                        <?php if ($add_comment_error): ?>
                                            <div class="alert alert-danger alert-dismissible fade show py-2">
                                                <?php echo htmlspecialchars($add_comment_error); ?>
                                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                            </div>
                                        <?php endif; ?>

                                        <form method="post" class="mb-4">
                                            <input type="hidden" name="add_comment" value="1">
                                            <h6 class="text-muted mb-2"><i class="ri-add-line"></i> Add comment</h6>
                                            <div class="row g-2">
                                                <div class="col-12">
                                                    <label class="form-label small">Post as</label>
                                                    <select name="member_id" class="form-select form-select-sm" required>
                                                        <?php foreach ($comment_authors as $mid => $name): ?>
                                                            <option value="<?php echo $mid; ?>"><?php echo htmlspecialchars($name); ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="col-12">
                                                    <label class="form-label small">Comment</label>
                                                    <textarea name="comment" class="form-control" rows="3" required placeholder="Write a comment..."></textarea>
                                                </div>
                                                <div class="col-12">
                                                    <button type="submit" class="btn btn-primary btn-sm">
                                                        <i class="ri-send-plane-line"></i> Add comment
                                                    </button>
                                                </div>
                                            </div>
                                        </form>
                                        <hr class="my-3">

                                        <?php if (!empty($comments)): ?>
                                            <?php foreach ($comments as $c): ?>
                                                <div class="mb-3 pb-3 border-bottom">
                                                    <strong><?php echo htmlspecialchars($c['commenter_name'] ?? 'Unknown'); ?></strong>
                                                    <br><small class="text-muted"><?php echo date('M d, Y H:i', strtotime($c['created_at'])); ?></small>
                                                    <p class="mt-2 mb-0"><?php echo nl2br(htmlspecialchars($c['comment'])); ?></p>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <p class="text-muted mb-0">No comments yet. Use the form above to add one.</p>
                                        <?php endif; ?>
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

    <script src="assets/js/vendor.min.js"></script>
    <script src="assets/js/app.min.js"></script>
</body>
</html>

<?php
mysqli_close($conn);
?>

