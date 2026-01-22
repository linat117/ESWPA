<?php
session_start();

// Check if member is logged in
if (!isset($_SESSION['member_id'])) {
    header("Location: member-login.php");
    exit();
}

include 'include/config.php';
require_once 'include/member_dashboard_stats.php';
require_once 'include/badge_calculator.php';

// Calculate badges for member (runs on dashboard load)
calculateAllBadges($member_id);

// Get member details
$member_id = $_SESSION['member_id'];
$query = "SELECT r.*, ma.last_login 
          FROM registrations r 
          LEFT JOIN member_access ma ON r.id = ma.member_id 
          WHERE r.id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $member_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    session_destroy();
    header("Location: member-login.php?error=Member not found");
    exit();
}

$member = $result->fetch_assoc();
$stmt->close();

// Get dashboard statistics
$stats = getMemberDashboardStats($member_id);
$recentNews = getRecentNews(3);
$upcomingEvents = getUpcomingEvents(3);
$recommendedResources = getRecommendedResources($member_id, 3);

// Get member badges
$memberBadges = getMemberBadges($member_id);
$badgeCount = count($memberBadges);

// Check if membership is expired
$isExpired = false;
if (!empty($member['expiry_date'])) {
    $expiryDate = new DateTime($member['expiry_date']);
    $today = new DateTime();
    $isExpired = $expiryDate < $today;
    
    if ($isExpired) {
        // Update status to expired
        $updateQuery = "UPDATE registrations SET status = 'expired' WHERE id = ?";
        $updateStmt = $conn->prepare($updateQuery);
        $updateStmt->bind_param("i", $member_id);
        $updateStmt->execute();
        $updateStmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<?php include 'head.php'; ?>
<link href="assets/css/member-panel.css" rel="stylesheet">
        
<body>
    <!-- Header -->
    <?php include 'member-header-v1.2.php'; ?>
    <!-- End Header -->

    <div class="mp-content-wrapper">
        <div class="mp-container">
        <div class="mp-content">
            <?php if (isset($_GET['success'])): ?>
                <div class="mp-alert mp-alert-success">
                    <?php
                    if ($_GET['success'] == 'password_set') {
                        echo 'Password set successfully! Welcome to your dashboard.';
                    }
                    ?>
                </div>
            <?php endif; ?>

            <?php if ($isExpired): ?>
                <div class="mp-alert mp-alert-warning">
                    <strong>Membership Expired!</strong> Your membership expired on <?php echo date('F d, Y', strtotime($member['expiry_date'])); ?>. 
                    Please renew your membership to continue accessing member features.
                </div>
            <?php endif; ?>

            <!-- Welcome Banner - Compact -->
            <div class="mp-welcome-banner">
                <div style="display: flex; align-items: center; justify-content: space-between; gap: 16px;">
                    <div style="flex: 1;">
                                    <h2>Welcome back, <?php echo htmlspecialchars($member['fullname']); ?>!</h2>
                        <p style="display: flex; align-items: center; gap: 8px; margin: 0;">
                            <i class="fas fa-id-card"></i> 
                            <strong><?php echo htmlspecialchars($member['membership_id'] ?? 'N/A'); ?></strong>
                                    </p>
                                </div>
                                    <?php if (!empty($member['photo'])): ?>
                                        <img src="<?php echo htmlspecialchars($member['photo']); ?>" 
                                             alt="Member Photo" 
                             style="width: 60px; height: 60px; border-radius: 50%; border: 3px solid rgba(255,255,255,0.3); object-fit: cover;">
                                    <?php else: ?>
                        <div style="width: 60px; height: 60px; border-radius: 50%; background: rgba(255,255,255,0.2); display: flex; align-items: center; justify-content: center; border: 3px solid rgba(255,255,255,0.3);">
                            <i class="fas fa-user fa-2x text-white"></i>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Stats Grid - 4 columns -->
            <div class="mp-stat-grid">
                <div class="mp-stat-card mp-stat-primary">
                    <div class="mp-stat-value"><?php echo $stats['research_count']; ?></div>
                    <div class="mp-stat-label">Research</div>
                </div>
                <div class="mp-stat-card mp-stat-success">
                    <div class="mp-stat-value"><?php echo $stats['citations_count']; ?></div>
                    <div class="mp-stat-label">Citations</div>
                </div>
                <div class="mp-stat-card mp-stat-warning">
                    <div class="mp-stat-value"><?php echo $stats['notes_count']; ?></div>
                    <div class="mp-stat-label">Notes</div>
                </div>
                <div class="mp-stat-card mp-stat-info">
                    <div class="mp-stat-value"><?php echo $stats['bibliography_count']; ?></div>
                    <div class="mp-stat-label">Bibliography</div>
                </div>
            </div>

            <!-- Main Cards Grid - 4 columns -->
            <div class="mp-grid-4">

                <!-- Member Info Card -->
                <div class="mp-card">
                    <div class="mp-card-header mp-card-header-primary">
                            <h5><i class="fas fa-user"></i> Member Info</h5>
                    </div>
                    <div class="mp-card-body">
                        <div class="mp-info-row">
                            <span class="mp-info-label">Name</span>
                            <span class="mp-info-value"><?php echo htmlspecialchars($member['fullname']); ?></span>
                        </div>
                        <div class="mp-info-row">
                            <span class="mp-info-label">Email</span>
                            <span class="mp-info-value"><?php echo htmlspecialchars($member['email']); ?></span>
                            </div>
                        <div class="mp-info-row">
                            <span class="mp-info-label">Phone</span>
                            <span class="mp-info-value"><?php echo htmlspecialchars($member['phone']); ?></span>
                            </div>
                        <div class="mp-info-row">
                            <span class="mp-info-label">Qualification</span>
                            <span class="mp-info-value"><?php echo htmlspecialchars($member['qualification']); ?></span>
                            </div>
                            <?php if (!empty($member['last_login'])): ?>
                        <div class="mp-info-row">
                            <span class="mp-info-label">Last Login</span>
                            <span class="mp-info-value"><?php echo date('M d, H:i', strtotime($member['last_login'])); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Membership Status Card -->
                <div class="mp-card">
                    <div class="mp-card-header mp-card-header-success">
                            <h5><i class="fas fa-id-card"></i> Membership</h5>
                        </div>
                    <div class="mp-card-body">
                        <div class="mp-info-row">
                            <span class="mp-info-label">Status</span>
                                <?php
                                $status = $member['status'];
                            $badgeClass = $status == 'active' ? 'mp-badge-success' : ($status == 'expired' ? 'mp-badge-danger' : 'mp-badge-warning');
                                ?>
                            <span class="mp-badge <?php echo $badgeClass; ?>"><?php echo ucfirst($status); ?></span>
                            </div>
                        <div class="mp-info-row">
                            <span class="mp-info-label">Approval</span>
                                <?php
                                $approvalStatus = $member['approval_status'];
                            $approvalBadgeClass = $approvalStatus == 'approved' ? 'mp-badge-success' : ($approvalStatus == 'rejected' ? 'mp-badge-danger' : 'mp-badge-warning');
                                ?>
                            <span class="mp-badge <?php echo $approvalBadgeClass; ?>"><?php echo ucfirst($approvalStatus); ?></span>
                            </div>
                            <?php if (!empty($member['expiry_date'])): ?>
                        <div class="mp-info-row">
                            <span class="mp-info-label">Expires</span>
                                <?php 
                                $expiryDate = new DateTime($member['expiry_date']);
                                $today = new DateTime();
                                $daysLeft = $today->diff($expiryDate)->days;
                                
                                if ($expiryDate < $today) {
                                echo '<span class="mp-info-value" style="color: var(--mp-danger);">Expired</span>';
                                } else {
                                echo '<span class="mp-info-value" style="color: var(--mp-success);">' . $daysLeft . ' days</span>';
                                }
                                ?>
                            </div>
                            <?php endif; ?>
                            <?php if (!empty($member['created_at'])): ?>
                        <div class="mp-info-row">
                            <span class="mp-info-label">Member Since</span>
                            <span class="mp-info-value"><?php echo date('M Y', strtotime($member['created_at'])); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Badges Card -->
                <div class="mp-card">
                    <div class="mp-card-header mp-card-header-warning">
                            <h5><i class="fas fa-trophy"></i> Badges</h5>
                        <span class="mp-badge"><?php echo $badgeCount; ?></span>
                        </div>
                    <div class="mp-card-body">
                            <?php if ($badgeCount > 0): ?>
                            <div style="display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 12px;">
                                    <?php 
                                    // Show first 4 badges
                                    $displayBadges = array_slice($memberBadges, 0, 4);
                                    foreach ($displayBadges as $badge): 
                                        // Get badge color based on name
                                    $badgeClass = 'mp-badge';
                                    if (stripos($badge['badge_name'], 'Platinum') !== false) $badgeClass = 'mp-badge mp-badge-primary';
                                    elseif (stripos($badge['badge_name'], 'Gold') !== false) $badgeClass = 'mp-badge mp-badge-warning';
                                    elseif (stripos($badge['badge_name'], 'Silver') !== false) $badgeClass = 'mp-badge';
                                    elseif (stripos($badge['badge_name'], 'Bronze') !== false) $badgeClass = 'mp-badge mp-badge-danger';
                                    elseif (stripos($badge['badge_name'], 'Research') !== false) $badgeClass = 'mp-badge mp-badge-info';
                                    elseif (stripos($badge['badge_name'], 'Active') !== false || stripos($badge['badge_name'], 'Champion') !== false) $badgeClass = 'mp-badge mp-badge-success';
                                    ?>
                                    <span class="<?php echo $badgeClass; ?>" title="<?php echo htmlspecialchars($badge['badge_name']); ?>">
                                            <i class="fas fa-award"></i> <?php echo htmlspecialchars($badge['badge_name']); ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            <a href="member-badges.php" class="mp-btn mp-btn-outline-primary mp-btn-full mp-btn-sm">
                                    View All <?php echo $badgeCount > 4 ? '(' . $badgeCount . ')' : ''; ?>
                                </a>
                            <?php else: ?>
                            <div class="mp-empty-state">
                                    <i class="fas fa-trophy"></i>
                                    <p>No badges yet</p>
                                    <small>Keep participating!</small>
                                </div>
                            <?php endif; ?>
                    </div>
                </div>

                <!-- Quick Actions Card - 2 column grid for buttons -->
                <div class="mp-card">
                    <div class="mp-card-header mp-card-header-success">
                            <h5><i class="fas fa-bolt"></i> Quick Actions</h5>
                        </div>
                    <div class="mp-card-body">
                        <div class="mp-btn-grid-2">
                            <?php if ($member['approval_status'] == 'approved' && !$isExpired): ?>
                                <?php if ($member['id_card_generated'] == 0): ?>
                                    <a href="member-generate-id-card.php" class="mp-btn mp-btn-primary mp-btn-sm">
                                        <i class="fas fa-id-card"></i> Generate ID Card
                                    </a>
                                <?php else: ?>
                                    <a href="member-generate-id-card.php" class="mp-btn mp-btn-success mp-btn-sm">
                                        <i class="fas fa-id-card"></i> View ID Card
                                    </a>
                                <?php endif; ?>
                            <?php endif; ?>
                            <a href="member-research.php" class="mp-btn mp-btn-outline-primary mp-btn-sm">
                                <i class="fas fa-search"></i> My Research
                            </a>
                            <a href="resources.php" class="mp-btn mp-btn-outline-primary mp-btn-sm">
                                <i class="fas fa-download"></i> Resources
                            </a>
                            <a href="member-profile-edit.php" class="mp-btn mp-btn-outline-primary mp-btn-sm">
                                <i class="fas fa-user-edit"></i> Edit Profile
                            </a>
                            <a href="member-notifications.php" class="mp-btn mp-btn-outline-primary mp-btn-sm" style="position: relative;">
                                <i class="fas fa-bell"></i> Notifications
                                <?php if ($stats['unread_notifications'] > 0): ?>
                                    <span style="position: absolute; top: -4px; right: -4px; background: var(--mp-danger); color: white; border-radius: 50%; width: 18px; height: 18px; display: flex; align-items: center; justify-content: center; font-size: 10px; font-weight: 600;"><?php echo $stats['unread_notifications']; ?></span>
                                <?php endif; ?>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Activity Feed & Content - 2 column grid -->
            <div class="mp-grid-2">
                <!-- Activity Feed -->
                <div class="mp-card">
                    <div class="mp-card-header mp-card-header-primary">
                            <h5><i class="fas fa-history"></i> Recent Activity</h5>
                        </div>
                    <div class="mp-card-body mp-scrollable" style="max-height: 300px; overflow-y: auto;">
                            <?php if (!empty($stats['recent_activities'])): ?>
                            <div class="mp-activity-list">
                                <?php foreach ($stats['recent_activities'] as $index => $activity): 
                                    $iconBgClass = 'mp-badge-primary';
                                    if (stripos($activity['activity_type'], 'badge') !== false) $iconBgClass = 'mp-badge-warning';
                                    elseif (stripos($activity['activity_type'], 'profile') !== false) $iconBgClass = 'mp-badge-info';
                                    elseif (stripos($activity['activity_type'], 'research') !== false) $iconBgClass = 'mp-badge-success';
                                ?>
                                    <div class="mp-activity-item">
                                        <div class="mp-activity-icon <?php echo $iconBgClass; ?>">
                                            <i class="fas fa-circle" style="font-size: 6px;"></i>
                                        </div>
                                        <div class="mp-activity-content">
                                            <div class="mp-activity-text"><?php echo htmlspecialchars($activity['activity_description']); ?></div>
                                            <div class="mp-activity-time"><?php echo date('M d, H:i', strtotime($activity['created_at'])); ?></div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <?php else: ?>
                            <div class="mp-empty-state">
                                    <i class="fas fa-history"></i>
                                    <p>No recent activity</p>
                                </div>
                            <?php endif; ?>
                    </div>
                </div>

                <!-- Recent News -->
                <?php if (!empty($recentNews)): ?>
                <div class="mp-card">
                    <div class="mp-card-header mp-card-header-info">
                            <h5><i class="fas fa-newspaper"></i> Recent News</h5>
                        </div>
                    <div class="mp-card-body">
                        <div class="mp-content-list">
                            <?php foreach ($recentNews as $news): ?>
                                <div class="mp-content-item">
                                    <div class="mp-content-title">
                                        <a href="news-detail.php?id=<?php echo $news['id']; ?>">
                                            <?php echo htmlspecialchars($news['title']); ?>
                                        </a>
                                    </div>
                                    <div class="mp-content-date">
                                        <i class="fas fa-calendar"></i> <?php echo date('M d, Y', strtotime($news['created_at'])); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <a href="news.php" class="mp-btn mp-btn-outline-primary mp-btn-full mp-btn-sm mp-mt-md">View All News</a>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Upcoming Events -->
                <?php if (!empty($upcomingEvents)): ?>
                <div class="mp-card">
                    <div class="mp-card-header mp-card-header-warning">
                            <h5><i class="fas fa-calendar-alt"></i> Upcoming Events</h5>
                        </div>
                    <div class="mp-card-body">
                        <div class="mp-content-list">
                            <?php foreach ($upcomingEvents as $event): ?>
                                <div class="mp-content-item">
                                    <div class="mp-content-title"><?php echo htmlspecialchars($event['event_header']); ?></div>
                                    <div class="mp-content-date">
                                        <i class="fas fa-calendar"></i> <?php echo date('M d, Y', strtotime($event['event_date'])); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <a href="events.php" class="mp-btn mp-btn-outline-primary mp-btn-full mp-btn-sm mp-mt-md">View All Events</a>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    </div>

    <!-- Optimized Scripts (Lazy Load) -->
    <script src="assets/js/jquery-1.12.4.min.js"></script>
    <script src="assets/js/bootstrap.min.js" defer></script>
    <script src="assets/js/bootsnav.js" defer></script>
    
    <!-- Performance Optimization Script -->
    <script>
        // Lazy load images
        if ('loading' in HTMLImageElement.prototype) {
            const images = document.querySelectorAll('img[loading="lazy"]');
            images.forEach(img => {
                img.src = img.dataset.src || img.src;
            });
        }
        
        // Smooth scroll for activity feed
        document.addEventListener('DOMContentLoaded', function() {
            const activityFeed = document.querySelector('.mp-card-body[style*="overflow-y"]');
            if (activityFeed) {
                activityFeed.style.scrollBehavior = 'smooth';
            }
            
            // Add fade-in animation on load
            const cards = document.querySelectorAll('.mp-card');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(10px)';
                setTimeout(() => {
                    card.style.transition = 'all 0.3s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 30);
            });
        });
    </script>

</body>

</html>

