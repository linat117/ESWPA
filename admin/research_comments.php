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

// Handle comment deletion
if (isset($_GET['delete']) && isset($_SESSION['user_id'])) {
    $comment_id = intval($_GET['delete']);
    $deleteQuery = "DELETE FROM research_comments WHERE id = ?";
    $stmt = $conn->prepare($deleteQuery);
    $stmt->bind_param("i", $comment_id);
    if ($stmt->execute()) {
        header("Location: research_comments.php?success=Comment deleted successfully&research_id=" . $research_id);
        exit();
    }
    $stmt->close();
}

$comments = [];
$research_projects = [];
$error_message = null;

$tableCheck = @$conn->query("SHOW TABLES LIKE 'research_comments'");
$tables_exist = $tableCheck && $tableCheck->num_rows > 0;

if (!$tables_exist) {
    $error_message = "The research_comments table has not been created yet. Run the migration SQL to create it.";
} else {
    $stmt = null;
    if ($research_id > 0) {
        $stmt = @$conn->prepare("SELECT rc.*, rp.title as research_title, r.fullname as commenter_name, r.email as commenter_email,
                         parent.fullname as parent_commenter_name
                  FROM research_comments rc
                  LEFT JOIN research_projects rp ON rc.research_id = rp.id
                  LEFT JOIN registrations r ON rc.member_id = r.id
                  LEFT JOIN research_comments parent_comment ON rc.parent_comment_id = parent_comment.id
                  LEFT JOIN registrations parent ON parent_comment.member_id = parent.id
                  WHERE rc.research_id = ?
                  ORDER BY rc.created_at DESC");
        if ($stmt) {
            $stmt->bind_param("i", $research_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $comments = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
            $stmt->close();
        }
    } else {
        $result = @$conn->query("SELECT rc.*, rp.title as research_title, r.fullname as commenter_name, r.email as commenter_email,
                         parent.fullname as parent_commenter_name
                  FROM research_comments rc
                  LEFT JOIN research_projects rp ON rc.research_id = rp.id
                  LEFT JOIN registrations r ON rc.member_id = r.id
                  LEFT JOIN research_comments parent_comment ON rc.parent_comment_id = parent_comment.id
                  LEFT JOIN registrations parent ON parent_comment.member_id = parent.id
                  ORDER BY rc.created_at DESC
                  LIMIT 100");
        $comments = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    $researchResult = @$conn->query("SELECT id, title FROM research_projects ORDER BY title");
    $research_projects = ($researchResult && $researchResult->num_rows > 0) ? $researchResult->fetch_all(MYSQLI_ASSOC) : [];
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
                                <h4 class="page-title">Research Comments Management</h4>
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

                    <?php
                    if (isset($_GET['success'])) {
                        echo '<div class="alert alert-success alert-dismissible fade show" role="alert">';
                        echo htmlspecialchars($_GET['success']);
                        echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
                        echo '</div>';
                    }
                    ?>

                    <?php if (isset($error_message)): ?>
                        <div class="row">
                            <div class="col-12">
                                <div class="alert alert-warning">
                                    <strong>Note:</strong> <?php echo htmlspecialchars($error_message); ?>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>

                    <!-- Summary Card -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <div class="card widget-flat text-bg-primary">
                                <div class="card-body">
                                    <div class="float-end">
                                        <i class="ri-chat-3-line widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0">Total Comments</h6>
                                    <h2 class="my-2"><?php echo number_format(count($comments)); ?></h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card widget-flat text-bg-info">
                                <div class="card-body">
                                    <div class="float-end">
                                        <i class="ri-search-line widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0">Research Projects</h6>
                                    <h2 class="my-2"><?php echo number_format(count($research_projects)); ?></h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card widget-flat text-bg-success">
                                <div class="card-body">
                                    <div class="float-end">
                                        <i class="ri-user-line widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0">Commenters</h6>
                                    <h2 class="my-2"><?php echo number_format(count(array_unique(array_column($comments, 'member_id')))); ?></h2>
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
                                                <a href="research_comments.php" class="btn btn-secondary w-100">
                                                    <i class="ri-refresh-line"></i> Clear Filter
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Comments List -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">
                                        Research Comments
                                        <?php if ($research_id > 0): ?>
                                            <span class="text-muted small">(Filtered)</span>
                                        <?php endif; ?>
                                    </h4>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($comments)): ?>
                                        <div class="alert alert-info">
                                            <i class="ri-information-line"></i> <strong>No comments found.</strong>
                                            <p class="mb-0 mt-2 small">Add comments from a research project page: <strong>Research → All Research</strong> → open a project → <strong>Comments</strong> section → use &quot;Add comment&quot;.
                                            Or filter by project above if you have comments already.</p>
                                        </div>
                                    <?php else: ?>
                                        <div class="table-responsive">
                                            <table class="table table-hover mb-0" id="commentsTable">
                                                <thead>
                                                    <tr>
                                                        <th>Comment</th>
                                                        <th>Research Project</th>
                                                        <th>Commenter</th>
                                                        <th>Type</th>
                                                        <th>Date</th>
                                                        <th>Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($comments as $comment): ?>
                                                        <tr>
                                                            <td>
                                                                <?php if ($comment['parent_comment_id']): ?>
                                                                    <i class="ri-reply-line text-muted me-1"></i>
                                                                    <small class="text-muted">Reply to: <?php echo htmlspecialchars($comment['parent_commenter_name'] ?? 'Unknown'); ?></small><br>
                                                                <?php endif; ?>
                                                                <?php echo htmlspecialchars(substr($comment['comment'], 0, 100)); ?>
                                                                <?php echo strlen($comment['comment']) > 100 ? '...' : ''; ?>
                                                            </td>
                                                            <td>
                                                                <a href="research_details.php?id=<?php echo $comment['research_id']; ?>" class="text-dark">
                                                                    <?php echo htmlspecialchars(substr($comment['research_title'] ?? 'N/A', 0, 40)); ?>
                                                                    <?php echo strlen($comment['research_title'] ?? '') > 40 ? '...' : ''; ?>
                                                                </a>
                                                            </td>
                                                            <td>
                                                                <?php echo htmlspecialchars($comment['commenter_name'] ?? 'Unknown'); ?>
                                                                <?php if ($comment['commenter_email']): ?>
                                                                    <br><small class="text-muted"><?php echo htmlspecialchars($comment['commenter_email']); ?></small>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td>
                                                                <?php if ($comment['parent_comment_id']): ?>
                                                                    <span class="badge bg-info">Reply</span>
                                                                <?php else: ?>
                                                                    <span class="badge bg-primary">Comment</span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td>
                                                                <?php echo date('M d, Y', strtotime($comment['created_at'])); ?>
                                                                <br><small class="text-muted"><?php echo date('H:i', strtotime($comment['created_at'])); ?></small>
                                                            </td>
                                                            <td>
                                                                <a href="research_details.php?id=<?php echo $comment['research_id']; ?>" 
                                                                   class="btn btn-sm btn-light me-1" 
                                                                   title="View Research">
                                                                    <i class="ri-eye-line"></i>
                                                                </a>
                                                                <a href="research_comments.php?delete=<?php echo $comment['id']; ?>&research_id=<?php echo $research_id; ?>" 
                                                                   class="btn btn-sm btn-danger" 
                                                                   title="Delete Comment"
                                                                   onclick="return confirm('Are you sure you want to delete this comment?');">
                                                                    <i class="ri-delete-bin-line"></i>
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
            var $tbl = $('#commentsTable');
            if ($tbl.length && $.fn.DataTable) {
                $tbl.DataTable({
                    order: [[4, 'desc']],
                    pageLength: 25,
                    language: {
                        search: 'Search comments:',
                        lengthMenu: 'Show _MENU_ comments per page',
                        info: 'Showing _START_ to _END_ of _TOTAL_ comments',
                        infoEmpty: 'No comments found',
                        infoFiltered: '(filtered from _MAX_ total comments)'
                    }
                });
            }
        });
    </script>

</body>
</html>

