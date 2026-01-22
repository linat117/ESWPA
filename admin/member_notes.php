<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: auth-login.php");
    exit();
}

include 'header.php';
include 'include/conn.php';

$member_id = intval($_GET['member_id'] ?? 0);
$error = '';
$success = '';

if ($member_id <= 0) {
    header("Location: members_list.php?error=Invalid member ID");
    exit();
}

// Get member details
$memberQuery = "SELECT id, fullname, email, membership_id FROM registrations WHERE id = ?";
$memberStmt = $conn->prepare($memberQuery);
$memberStmt->bind_param("i", $member_id);
$memberStmt->execute();
$memberResult = $memberStmt->get_result();

if ($memberResult->num_rows === 0) {
    header("Location: members_list.php?error=Member not found");
    exit();
}

$member = $memberResult->fetch_assoc();
$memberStmt->close();

// Check if table exists
$checkTable = "SHOW TABLES LIKE 'member_admin_notes'";
$tableResult = $conn->query($checkTable);
$tableExists = $tableResult && $tableResult->num_rows > 0;

// Handle add note
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_note'])) {
    if (!$tableExists) {
        $error = "Notes table not found. Please run the migration first.";
    } else {
        $note = trim($_POST['note'] ?? '');
        $is_important = isset($_POST['is_important']) ? 1 : 0;
        
        if (empty($note)) {
            $error = "Note cannot be empty";
        } else {
            $query = "INSERT INTO member_admin_notes (member_id, admin_id, note, is_important) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($query);
            $admin_id = $_SESSION['user_id'];
            $stmt->bind_param("iisi", $member_id, $admin_id, $note, $is_important);
            
            if ($stmt->execute()) {
                $stmt->close();
                $success = "Note added successfully";
            } else {
                $error = "Failed to add note: " . $conn->error;
                $stmt->close();
            }
        }
    }
}

// Handle delete note
if (isset($_GET['delete']) && $tableExists) {
    $note_id = intval($_GET['delete']);
    $query = "DELETE FROM member_admin_notes WHERE id = ? AND member_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $note_id, $member_id);
    
    if ($stmt->execute()) {
        $stmt->close();
        $success = "Note deleted successfully";
    } else {
        $error = "Failed to delete note";
        $stmt->close();
    }
}

// Get all notes for this member
$notes = [];
if ($tableExists) {
    $notesQuery = "SELECT man.*, u.username as admin_name 
                   FROM member_admin_notes man
                   LEFT JOIN user u ON man.admin_id = u.id
                   WHERE man.member_id = ?
                   ORDER BY man.created_at DESC";
    $notesStmt = $conn->prepare($notesQuery);
    $notesStmt->bind_param("i", $member_id);
    $notesStmt->execute();
    $notesResult = $notesStmt->get_result();
    $notes = $notesResult->fetch_all(MYSQLI_ASSOC);
    $notesStmt->close();
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
                    <div class="row">
                        <div class="col-12">
                            <div class="page-title-box d-flex justify-content-between align-items-center">
                                <h4 class="page-title">Member Notes</h4>
                                <div class="page-title-right">
                                    <a href="members_list.php" class="btn btn-outline-secondary">
                                        <i class="ri-arrow-left-line"></i> Back to Members
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if ($success): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?php echo htmlspecialchars($success); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php echo htmlspecialchars($error); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Member Info -->
                    <div class="row mb-3">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <h5><?php echo htmlspecialchars($member['fullname']); ?></h5>
                                    <p class="text-muted mb-0">
                                        <strong>Email:</strong> <?php echo htmlspecialchars($member['email']); ?> | 
                                        <strong>Membership ID:</strong> <?php echo htmlspecialchars($member['membership_id'] ?? 'N/A'); ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Add Note Form -->
                        <div class="col-12 col-lg-4">
                            <div class="card">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="mb-0"><i class="ri-add-line"></i> Add Note</h5>
                                </div>
                                <div class="card-body">
                                    <?php if (!$tableExists): ?>
                                        <div class="alert alert-warning">
                                            <strong>Note:</strong> Notes table not found. Please run the migration: 
                                            <code>Sql/migration_member_admin_notes.sql</code>
                                        </div>
                                    <?php else: ?>
                                        <form method="POST">
                                            <div class="mb-3">
                                                <label for="note" class="form-label">Note <span class="text-danger">*</span></label>
                                                <textarea class="form-control" id="note" name="note" rows="5" required 
                                                          placeholder="Enter your note about this member..."></textarea>
                                            </div>
                                            <div class="mb-3">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="is_important" name="is_important">
                                                    <label class="form-check-label" for="is_important">
                                                        Mark as Important
                                                    </label>
                                                </div>
                                            </div>
                                            <button type="submit" name="add_note" class="btn btn-primary w-100">
                                                <i class="ri-save-line"></i> Add Note
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Notes List -->
                        <div class="col-12 col-lg-8">
                            <div class="card">
                                <div class="card-header bg-info text-white">
                                    <h5 class="mb-0"><i class="ri-file-list-line"></i> Notes History</h5>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($notes)): ?>
                                        <p class="text-muted text-center py-4">No notes added yet.</p>
                                    <?php else: ?>
                                        <div class="list-group">
                                            <?php foreach ($notes as $note): ?>
                                                <div class="list-group-item <?php echo $note['is_important'] ? 'border-warning' : ''; ?>">
                                                    <div class="d-flex justify-content-between align-items-start">
                                                        <div class="flex-grow-1">
                                                            <?php if ($note['is_important']): ?>
                                                                <span class="badge bg-warning mb-2">Important</span>
                                                            <?php endif; ?>
                                                            <p class="mb-2"><?php echo nl2br(htmlspecialchars($note['note'])); ?></p>
                                                            <small class="text-muted">
                                                                <i class="ri-user-line"></i> <?php echo htmlspecialchars($note['admin_name'] ?? 'Unknown'); ?> | 
                                                                <i class="ri-time-line"></i> <?php echo date('M d, Y H:i', strtotime($note['created_at'])); ?>
                                                            </small>
                                                        </div>
                                                        <div>
                                                            <a href="member_notes.php?member_id=<?php echo $member_id; ?>&delete=<?php echo $note['id']; ?>" 
                                                               class="btn btn-sm btn-outline-danger"
                                                               onclick="return confirm('Are you sure you want to delete this note?')">
                                                                <i class="ri-delete-bin-line"></i>
                                                            </a>
                                                        </div>
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

    <script src="assets/js/vendor.min.js"></script>
    <script src="assets/js/app.min.js"></script>
</body>
</html>

