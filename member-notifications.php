<?php
session_start();

// Check if member is logged in
if (!isset($_SESSION['member_id'])) {
    header("Location: member-login.php");
    exit();
}

include 'include/config.php';

$member_id = $_SESSION['member_id'];

// Handle mark as read action
if (isset($_GET['action']) && $_GET['action'] == 'mark_read' && isset($_GET['id'])) {
    $notification_id = intval($_GET['id']);
    $query = "UPDATE notifications SET is_read = 1, read_at = NOW() WHERE id = ? AND member_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $notification_id, $member_id);
    $stmt->execute();
    $stmt->close();
    header("Location: member-notifications.php?success=marked_read");
    exit();
}

// Handle mark all as read
if (isset($_GET['action']) && $_GET['action'] == 'mark_all_read') {
    $query = "UPDATE notifications SET is_read = 1, read_at = NOW() WHERE member_id = ? AND is_read = 0";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $member_id);
    $stmt->execute();
    $stmt->close();
    header("Location: member-notifications.php?success=all_marked_read");
    exit();
}

// Handle delete notification
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $notification_id = intval($_GET['id']);
    $query = "DELETE FROM notifications WHERE id = ? AND member_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $notification_id, $member_id);
    $stmt->execute();
    $stmt->close();
    header("Location: member-notifications.php?success=deleted");
    exit();
}

// Get filter
$filter = $_GET['filter'] ?? 'all'; // all, unread, read

// Build query
$where = "member_id = ?";
$params = [$member_id];
$types = "i";

if ($filter == 'unread') {
    $where .= " AND is_read = 0";
} elseif ($filter == 'read') {
    $where .= " AND is_read = 1";
}

$query = "SELECT * FROM notifications WHERE $where ORDER BY created_at DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$notifications = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get unread count
$unreadQuery = "SELECT COUNT(*) as count FROM notifications WHERE member_id = ? AND is_read = 0";
$unreadStmt = $conn->prepare($unreadQuery);
$unreadStmt->bind_param("i", $member_id);
$unreadStmt->execute();
$unreadResult = $unreadStmt->get_result();
$unreadCount = $unreadResult->fetch_assoc()['count'];
$unreadStmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<?php include 'head.php'; ?>
<link href="assets/css/member-panel.css" rel="stylesheet">
<style>
        .notification-item {
        border-left: 4px solid var(--mp-primary);
        transition: var(--mp-transition);
    }
    
    .notification-item.unread {
        background-color: rgba(37, 99, 235, 0.05);
        border-left-color: var(--mp-primary);
    }
    
    .notification-item.read {
        background-color: var(--mp-gray-50);
        border-left-color: var(--mp-gray-400);
        opacity: 0.8;
    }
    
    .notification-item:hover {
        box-shadow: var(--mp-shadow-md);
        transform: translateX(4px);
    }
    
    .notification-icon {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
    }
    
    .notification-icon.info {
        background-color: rgba(14, 165, 233, 0.15);
        color: var(--mp-info);
    }
    
    .notification-icon.success {
        background-color: rgba(16, 185, 129, 0.15);
        color: var(--mp-success);
    }
    
    .notification-icon.warning {
        background-color: rgba(245, 158, 11, 0.15);
        color: var(--mp-warning);
    }
    
    .notification-icon.danger {
        background-color: rgba(239, 68, 68, 0.15);
        color: var(--mp-danger);
    }
</style>
<body>
    <!-- Header -->
    <?php include 'member-header-v1.2.php'; ?>
    <!-- End Header -->

    <div class="mp-content-wrapper">
        <div class="mp-container">
            <div class="mp-content">
                <div class="mp-flex-between mp-mb-md">
                    <h2 class="mp-page-title"><i class="fas fa-bell"></i> Notifications</h2>
                    <div class="mp-flex mp-gap-sm">
                        <a href="member-dashboard.php" class="mp-btn mp-btn-outline mp-btn-sm">
                            <i class="fas fa-arrow-left"></i> Back
                                </a>
                                <?php if ($unreadCount > 0): ?>
                            <a href="member-notifications.php?action=mark_all_read" class="mp-btn mp-btn-primary mp-btn-sm">
                                        <i class="fas fa-check-double"></i> Mark All Read
                                    </a>
                                <?php endif; ?>
                    </div>
                </div>

                <?php if (isset($_GET['success'])): ?>
                    <div class="mp-alert mp-alert-success">
                        <?php
                        if ($_GET['success'] == 'marked_read') {
                            echo 'Notification marked as read.';
                        } elseif ($_GET['success'] == 'all_marked_read') {
                            echo 'All notifications marked as read.';
                        } elseif ($_GET['success'] == 'deleted') {
                            echo 'Notification deleted.';
                        }
                        ?>
                    </div>
                <?php endif; ?>

                <!-- Filter Tabs -->
                <div class="mp-card mp-mb-lg">
                    <div class="mp-card-body">
                        <ul class="nav nav-pills" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link <?php echo $filter == 'all' ? 'active' : ''; ?>" 
                                   href="member-notifications.php?filter=all">
                                    All
                                    <?php if ($filter == 'all'): ?>
                                        <span class="badge bg-secondary"><?php echo count($notifications); ?></span>
                                    <?php endif; ?>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo $filter == 'unread' ? 'active' : ''; ?>" 
                                   href="member-notifications.php?filter=unread">
                                    Unread
                                    <?php if ($unreadCount > 0): ?>
                                        <span class="badge bg-danger"><?php echo $unreadCount; ?></span>
                                    <?php endif; ?>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo $filter == 'read' ? 'active' : ''; ?>" 
                                   href="member-notifications.php?filter=read">
                                    Read
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Notifications List -->
                <div class="mp-card">
                    <div class="mp-card-body">
                        <?php if (!empty($notifications)): ?>
                            <div class="mp-activity-list">
                                <?php foreach ($notifications as $notification): ?>
                                    <div class="mp-activity-item notification-item <?php echo $notification['is_read'] ? 'read' : 'unread'; ?>">
                                        <div class="d-flex align-items-start">
                                            <div class="flex-shrink-0 me-3">
                                                <?php
                                                $iconClass = 'info';
                                                $icon = 'fa-info-circle';
                                                switch($notification['type']) {
                                                    case 'success':
                                                    case 'approval':
                                                        $iconClass = 'success';
                                                        $icon = 'fa-check-circle';
                                                        break;
                                                    case 'warning':
                                                    case 'expiry':
                                                        $iconClass = 'warning';
                                                        $icon = 'fa-exclamation-triangle';
                                                        break;
                                                    case 'error':
                                                    case 'rejection':
                                                        $iconClass = 'danger';
                                                        $icon = 'fa-times-circle';
                                                        break;
                                                }
                                                ?>
                                                <div class="notification-icon <?php echo $iconClass; ?>">
                                                    <i class="fas <?php echo $icon; ?>"></i>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1">
                                                <div class="d-flex justify-content-between align-items-start mb-2">
                                                    <div>
                                                        <h6 class="mb-1 <?php echo $notification['is_read'] ? 'text-muted' : 'fw-bold'; ?>">
                                                            <?php echo htmlspecialchars($notification['title']); ?>
                                                            <?php if (!$notification['is_read']): ?>
                                                                <span class="mp-badge mp-badge-primary" style="margin-left: var(--mp-space-sm);">New</span>
                                                            <?php endif; ?>
                                                        </h6>
                                                        <p class="mb-2 text-muted"><?php echo nl2br(htmlspecialchars($notification['message'])); ?></p>
                                                        <small class="text-muted">
                                                            <i class="fas fa-clock"></i> <?php echo date('M d, Y H:i', strtotime($notification['created_at'])); ?>
                                                        </small>
                                                    </div>
                                                    <div class="dropdown">
                                                        <button class="mp-btn mp-btn-sm mp-btn-outline" type="button" data-bs-toggle="dropdown">
                                                            <i class="fas fa-ellipsis-v"></i>
                                                        </button>
                                                        <ul class="dropdown-menu dropdown-menu-end">
                                                            <?php if (!$notification['is_read']): ?>
                                                                <li>
                                                                    <a class="dropdown-item" href="member-notifications.php?action=mark_read&id=<?php echo $notification['id']; ?>&filter=<?php echo $filter; ?>">
                                                                        <i class="fas fa-check"></i> Mark as Read
                                                                    </a>
                                                                </li>
                                                            <?php endif; ?>
                                                            <li>
                                                                <a class="dropdown-item text-danger" href="member-notifications.php?action=delete&id=<?php echo $notification['id']; ?>&filter=<?php echo $filter; ?>" 
                                                                   onclick="return confirm('Are you sure you want to delete this notification?')">
                                                                    <i class="fas fa-trash"></i> Delete
                                                                </a>
                                                            </li>
                                                        </ul>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="mp-empty-state">
                                <i class="fas fa-bell-slash"></i>
                                <p>No notifications found.</p>
                                <?php if ($filter != 'all'): ?>
                                    <a href="member-notifications.php?filter=all" class="mp-btn mp-btn-outline-primary mp-btn-sm">View All Notifications</a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="assets/js/jquery-1.12.4.min.js"></script>
    <script src="assets/js/bootstrap.min.js"></script>
    
</body>
</html>

