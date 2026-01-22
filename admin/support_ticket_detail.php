<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: auth-login.php");
    exit();
}

include 'include/conn.php';
include 'header.php';

// Handle status update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $ticket_id = intval($_POST['ticket_id']);
    $new_status = $_POST['status'];
    $new_priority = $_POST['priority'] ?? null;
    
    // Check if table exists
    $table_exists = false;
    $result = $conn->query("SHOW TABLES LIKE 'support_tickets'");
    if ($result && $result->num_rows > 0) {
        $table_exists = true;
    }
    
    if ($table_exists) {
        if ($new_priority) {
            $stmt = $conn->prepare("UPDATE support_tickets SET status = ?, priority = ?, updated_at = NOW() WHERE id = ?");
            $stmt->bind_param("ssi", $new_status, $new_priority, $ticket_id);
        } else {
            $stmt = $conn->prepare("UPDATE support_tickets SET status = ?, updated_at = NOW() WHERE id = ?");
            $stmt->bind_param("si", $new_status, $ticket_id);
        }
        $stmt->execute();
        $stmt->close();
        
        // If resolved or closed, update resolved_at
        if ($new_status == 'resolved' || $new_status == 'closed') {
            $stmt = $conn->prepare("UPDATE support_tickets SET resolved_at = NOW() WHERE id = ? AND resolved_at IS NULL");
            $stmt->bind_param("i", $ticket_id);
            $stmt->execute();
            $stmt->close();
        }
        
        header("Location: support_ticket_detail.php?id=$ticket_id&updated=1");
        exit();
    }
}

// Handle reply/comment
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_reply'])) {
    $ticket_id = intval($_POST['ticket_id']);
    $reply_message = $_POST['reply_message'] ?? '';
    
    // Check if support_ticket_replies table exists, if not, we'll note it
    $table_exists = false;
    $result = $conn->query("SHOW TABLES LIKE 'support_ticket_replies'");
    if ($result && $result->num_rows > 0) {
        $table_exists = true;
    }
    
    if ($table_exists && !empty($reply_message)) {
        $admin_id = $_SESSION['user_id'];
        $stmt = $conn->prepare("INSERT INTO support_ticket_replies (ticket_id, replied_by, reply_message, is_admin, created_at) VALUES (?, ?, ?, 1, NOW())");
        $stmt->bind_param("iis", $ticket_id, $admin_id, $reply_message);
        $stmt->execute();
        $stmt->close();
        
        // Update ticket updated_at
        $stmt = $conn->prepare("UPDATE support_tickets SET updated_at = NOW() WHERE id = ?");
        $stmt->bind_param("i", $ticket_id);
        $stmt->execute();
        $stmt->close();
        
        header("Location: support_ticket_detail.php?id=$ticket_id&replied=1");
        exit();
    }
}

$ticket_id = intval($_GET['id'] ?? 0);

if ($ticket_id <= 0) {
    header("Location: support_tickets_list.php?error=Invalid ticket ID");
    exit();
}

// Check if support_tickets table exists
$table_exists = false;
$result = $conn->query("SHOW TABLES LIKE 'support_tickets'");
if ($result && $result->num_rows > 0) {
    $table_exists = true;
}

$ticket = null;
$member = null;
$replies = [];

if ($table_exists) {
    // Get ticket details
    $stmt = $conn->prepare("SELECT st.*, r.fullname, r.email, r.phone, u.username as assigned_to_name
                           FROM support_tickets st 
                           LEFT JOIN registrations r ON st.member_id = r.id 
                           LEFT JOIN user u ON st.assigned_to = u.id
                           WHERE st.id = ?");
    $stmt->bind_param("i", $ticket_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        header("Location: support_tickets_list.php?error=Ticket not found");
        exit();
    }
    
    $ticket = $result->fetch_assoc();
    $stmt->close();
    
    // Get replies if table exists
    $replies_table_exists = false;
    $result = $conn->query("SHOW TABLES LIKE 'support_ticket_replies'");
    if ($result && $result->num_rows > 0) {
        $replies_table_exists = true;
        
        $stmt = $conn->prepare("SELECT str.*, u.username as admin_name, r.fullname as member_name
                               FROM support_ticket_replies str
                               LEFT JOIN user u ON str.replied_by = u.id AND str.is_admin = 1
                               LEFT JOIN registrations r ON str.replied_by = r.id AND str.is_admin = 0
                               WHERE str.ticket_id = ?
                               ORDER BY str.created_at ASC");
        $stmt->bind_param("i", $ticket_id);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $replies[] = $row;
        }
        $stmt->close();
    }
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
                                <h4 class="page-title">Support Ticket Details</h4>
                                <div>
                                    <a href="support_tickets_list.php" class="btn btn-secondary me-2">
                                        <i class="ri-arrow-left-line"></i> Back to List
                                    </a>
                                    <?php if ($table_exists && $ticket): ?>
                                        <a href="support_dashboard.php" class="btn btn-info">
                                            <i class="ri-dashboard-line"></i> Dashboard
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if (isset($_GET['updated']) || isset($_GET['replied'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="ri-check-line"></i> 
                            <?php echo isset($_GET['updated']) ? 'Ticket updated successfully!' : 'Reply added successfully!'; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <?php if (!$table_exists || !$ticket): ?>
                        <!-- Table Not Found Alert -->
                        <div class="alert alert-warning" role="alert">
                            <h5 class="alert-heading"><i class="ri-alert-line"></i> Ticket Not Found</h5>
                            <p>The support_tickets table does not exist or the ticket was not found.</p>
                            <p class="mb-0">See <a href="support_dashboard.php">Support Dashboard</a> for the required table structure.</p>
                        </div>
                    <?php else: ?>

                    <div class="row">
                        <!-- Left Column - Ticket Details -->
                        <div class="col-lg-8">
                            <!-- Ticket Information -->
                            <div class="card mb-3">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h4 class="header-title mb-0">Ticket Information</h4>
                                    <div>
                                        <span class="badge bg-<?php 
                                            echo $ticket['status'] == 'open' ? 'warning' : 
                                                ($ticket['status'] == 'resolved' || $ticket['status'] == 'closed' ? 'success' : 
                                                ($ticket['status'] == 'pending' ? 'info' : 'primary')); 
                                        ?> me-2">
                                            <?php echo ucfirst(str_replace('_', ' ', $ticket['status'])); ?>
                                        </span>
                                        <span class="badge bg-<?php 
                                            echo $ticket['priority'] == 'urgent' ? 'danger' : 
                                                ($ticket['priority'] == 'high' ? 'warning' : 
                                                ($ticket['priority'] == 'medium' ? 'info' : 'secondary')); 
                                        ?>">
                                            <?php echo ucfirst($ticket['priority']); ?> Priority
                                        </span>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <h3><?php echo htmlspecialchars($ticket['subject']); ?></h3>
                                    <p class="text-muted mb-3">
                                        <strong>Ticket #:</strong> <code><?php echo htmlspecialchars($ticket['ticket_number']); ?></code>
                                        <?php if ($ticket['category']): ?>
                                            | <strong>Category:</strong> <?php echo htmlspecialchars($ticket['category']); ?>
                                        <?php endif; ?>
                                    </p>
                                    
                                    <div class="mb-3">
                                        <strong>Message:</strong>
                                        <div class="mt-2 p-3 bg-light rounded">
                                            <?php echo nl2br(htmlspecialchars($ticket['message'])); ?>
                                        </div>
                                    </div>
                                    
                                    <div class="row mt-3">
                                        <div class="col-md-6">
                                            <p><strong>Created:</strong> <?php echo date('M d, Y H:i', strtotime($ticket['created_at'])); ?></p>
                                        </div>
                                        <div class="col-md-6">
                                            <p><strong>Last Updated:</strong> <?php echo $ticket['updated_at'] ? date('M d, Y H:i', strtotime($ticket['updated_at'])) : 'Never'; ?></p>
                                        </div>
                                        <?php if ($ticket['resolved_at']): ?>
                                        <div class="col-md-6">
                                            <p><strong>Resolved:</strong> <?php echo date('M d, Y H:i', strtotime($ticket['resolved_at'])); ?></p>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Ticket Replies/Conversation -->
                            <div class="card mb-3">
                                <div class="card-header">
                                    <h4 class="header-title">Conversation</h4>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($replies)): ?>
                                        <p class="text-muted text-center py-4">No replies yet. Be the first to respond!</p>
                                        <div class="alert alert-info">
                                            <i class="ri-information-line"></i> 
                                            <strong>Note:</strong> To enable replies functionality, create the <code>support_ticket_replies</code> table with the following structure:
                                            <pre class="mt-2 mb-0"><code>CREATE TABLE `support_ticket_replies` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `ticket_id` INT(11) NOT NULL,
  `replied_by` INT(11) NOT NULL,
  `reply_message` TEXT NOT NULL,
  `is_admin` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_ticket_id` (`ticket_id`),
  INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;</code></pre>
                                        </div>
                                    <?php else: ?>
                                        <div class="timeline">
                                            <?php foreach ($replies as $reply): ?>
                                                <div class="d-flex mb-4">
                                                    <div class="flex-shrink-0">
                                                        <div class="avatar-sm rounded-circle bg-<?php echo $reply['is_admin'] ? 'primary' : 'secondary'; ?> text-white d-flex align-items-center justify-content-center">
                                                            <i class="ri-<?php echo $reply['is_admin'] ? 'admin' : 'user'; ?>-line"></i>
                                                        </div>
                                                    </div>
                                                    <div class="flex-grow-1 ms-3">
                                                        <div class="card">
                                                            <div class="card-body">
                                                                <div class="d-flex justify-content-between mb-2">
                                                                    <strong><?php echo htmlspecialchars($reply['admin_name'] ?? $reply['member_name'] ?? 'Unknown'); ?></strong>
                                                                    <small class="text-muted"><?php echo date('M d, Y H:i', strtotime($reply['created_at'])); ?></small>
                                                                </div>
                                                                <p class="mb-0"><?php echo nl2br(htmlspecialchars($reply['reply_message'])); ?></p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <!-- Reply Form -->
                                    <div class="mt-4">
                                        <h5>Add Reply</h5>
                                        <form method="POST" action="">
                                            <input type="hidden" name="ticket_id" value="<?php echo $ticket_id; ?>">
                                            <div class="mb-3">
                                                <textarea name="reply_message" class="form-control" rows="4" placeholder="Type your reply here..." required></textarea>
                                            </div>
                                            <button type="submit" name="add_reply" class="btn btn-primary">
                                                <i class="ri-reply-line"></i> Send Reply
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right Column - Actions & Info -->
                        <div class="col-lg-4">
                            <!-- Update Status -->
                            <div class="card mb-3">
                                <div class="card-header">
                                    <h4 class="header-title">Update Ticket</h4>
                                </div>
                                <div class="card-body">
                                    <form method="POST" action="">
                                        <input type="hidden" name="ticket_id" value="<?php echo $ticket_id; ?>">
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Status</label>
                                            <select name="status" class="form-select" required>
                                                <option value="open" <?php echo $ticket['status'] == 'open' ? 'selected' : ''; ?>>Open</option>
                                                <option value="pending" <?php echo $ticket['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                <option value="in_progress" <?php echo $ticket['status'] == 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                                                <option value="resolved" <?php echo $ticket['status'] == 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                                                <option value="closed" <?php echo $ticket['status'] == 'closed' ? 'selected' : ''; ?>>Closed</option>
                                            </select>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Priority</label>
                                            <select name="priority" class="form-select" required>
                                                <option value="low" <?php echo $ticket['priority'] == 'low' ? 'selected' : ''; ?>>Low</option>
                                                <option value="medium" <?php echo $ticket['priority'] == 'medium' ? 'selected' : ''; ?>>Medium</option>
                                                <option value="high" <?php echo $ticket['priority'] == 'high' ? 'selected' : ''; ?>>High</option>
                                                <option value="urgent" <?php echo $ticket['priority'] == 'urgent' ? 'selected' : ''; ?>>Urgent</option>
                                            </select>
                                        </div>
                                        
                                        <button type="submit" name="update_status" class="btn btn-primary w-100">
                                            <i class="ri-save-line"></i> Update Ticket
                                        </button>
                                    </form>
                                </div>
                            </div>

                            <!-- Member Information -->
                            <?php if ($ticket['fullname']): ?>
                            <div class="card mb-3">
                                <div class="card-header">
                                    <h4 class="header-title">Member Information</h4>
                                </div>
                                <div class="card-body">
                                    <p><strong>Name:</strong> <?php echo htmlspecialchars($ticket['fullname']); ?></p>
                                    <p><strong>Email:</strong> <?php echo htmlspecialchars($ticket['email']); ?></p>
                                    <?php if ($ticket['phone']): ?>
                                        <p><strong>Phone:</strong> <?php echo htmlspecialchars($ticket['phone']); ?></p>
                                    <?php endif; ?>
                                    <?php if ($ticket['member_id']): ?>
                                        <a href="member_profile.php?id=<?php echo $ticket['member_id']; ?>" class="btn btn-sm btn-primary mt-2">
                                            <i class="ri-user-line"></i> View Profile
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endif; ?>

                            <!-- Ticket Statistics -->
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Ticket Statistics</h4>
                                </div>
                                <div class="card-body">
                                    <p><strong>Created:</strong><br><?php echo date('M d, Y H:i', strtotime($ticket['created_at'])); ?></p>
                                    <?php if ($ticket['updated_at']): ?>
                                        <p><strong>Last Updated:</strong><br><?php echo date('M d, Y H:i', strtotime($ticket['updated_at'])); ?></p>
                                    <?php endif; ?>
                                    <?php if ($ticket['resolved_at']): ?>
                                        <p><strong>Resolved:</strong><br><?php echo date('M d, Y H:i', strtotime($ticket['resolved_at'])); ?></p>
                                        <?php
                                        $created = strtotime($ticket['created_at']);
                                        $resolved = strtotime($ticket['resolved_at']);
                                        $hours = round(($resolved - $created) / 3600, 1);
                                        ?>
                                        <p><strong>Resolution Time:</strong><br><?php echo $hours; ?> hours</p>
                                    <?php endif; ?>
                                    <?php if ($ticket['assigned_to_name']): ?>
                                        <p><strong>Assigned To:</strong><br><?php echo htmlspecialchars($ticket['assigned_to_name']); ?></p>
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
    
    <!-- App js -->
    <script src="assets/js/app.min.js"></script>

</body>
</html>

