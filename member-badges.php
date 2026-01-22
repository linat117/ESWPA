<?php
session_start();

// Check if member is logged in
if (!isset($_SESSION['member_id'])) {
    header("Location: member-login.php");
    exit();
}

include 'include/config.php';
require_once 'include/badge_calculator.php';

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

// Recalculate badges
calculateAllBadges($member_id);

// Get all member badges
$memberBadges = getMemberBadges($member_id);
$badgeCount = count($memberBadges);

// Group badges by category
$badgeCategories = [
    'Membership' => [],
    'Research' => [],
    'Activity' => [],
    'Resources' => [],
    'Events' => [],
    'Other' => []
];

foreach ($memberBadges as $badge) {
    $badgeName = $badge['badge_name'];
    if (stripos($badgeName, 'Member') !== false || stripos($badgeName, 'Bronze') !== false || 
        stripos($badgeName, 'Silver') !== false || stripos($badgeName, 'Gold') !== false || 
        stripos($badgeName, 'Platinum') !== false) {
        $badgeCategories['Membership'][] = $badge;
    } elseif (stripos($badgeName, 'Research') !== false) {
        $badgeCategories['Research'][] = $badge;
    } elseif (stripos($badgeName, 'Active') !== false || stripos($badgeName, 'Champion') !== false || 
              stripos($badgeName, 'Community') !== false) {
        $badgeCategories['Activity'][] = $badge;
    } elseif (stripos($badgeName, 'Resource') !== false) {
        $badgeCategories['Resources'][] = $badge;
    } elseif (stripos($badgeName, 'Event') !== false) {
        $badgeCategories['Events'][] = $badge;
    } else {
        $badgeCategories['Other'][] = $badge;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<?php include 'head.php'; ?>
<link href="assets/css/member-panel.css" rel="stylesheet">
<body>
    <?php include 'member-header-v1.2.php'; ?>

    <div class="mp-content-wrapper">
        <div class="mp-container">
            <div class="mp-content">
                <div class="mp-flex-between mp-mb-md">
                        <div>
                        <h2 class="mp-page-title"><i class="fas fa-trophy"></i> My Badges</h2>
                        <p style="color: var(--mp-gray-600); margin: var(--mp-space-xs) 0 0 0; font-size: 0.875rem;">View all your earned badges and achievements</p>
                        </div>
                    <span class="mp-badge mp-badge-primary"><?php echo $badgeCount; ?> Badge<?php echo $badgeCount != 1 ? 's' : ''; ?></span>
            </div>

            <?php if ($badgeCount > 0): ?>
                <?php foreach ($badgeCategories as $category => $badges): ?>
                    <?php if (!empty($badges)): ?>
                            <div class="mp-card mp-mb-lg">
                                <div class="mp-card-header">
                                    <h5><i class="fas fa-trophy"></i> <?php echo $category; ?> Badges</h5>
                                    <span class="mp-badge mp-badge-primary"><?php echo count($badges); ?></span>
                                    </div>
                                <div class="mp-card-body">
                                    <div class="mp-badge-grid">
                                            <?php foreach ($badges as $badge): ?>
                                                <?php
                                            // Get badge color scheme based on name
                                            $badgeColorScheme = 'default';
                                                $iconClass = 'fa-award';
                                            $badgeGradient = 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)';
                                                
                                                if (stripos($badge['badge_name'], 'Platinum') !== false) {
                                                $badgeColorScheme = 'platinum';
                                                    $iconClass = 'fa-gem';
                                                $badgeGradient = 'linear-gradient(135deg, #1e293b 0%, #334155 100%)';
                                                } elseif (stripos($badge['badge_name'], 'Gold') !== false) {
                                                $badgeColorScheme = 'gold';
                                                    $iconClass = 'fa-medal';
                                                $badgeGradient = 'linear-gradient(135deg, #f59e0b 0%, #d97706 100%)';
                                                } elseif (stripos($badge['badge_name'], 'Silver') !== false) {
                                                $badgeColorScheme = 'silver';
                                                    $iconClass = 'fa-medal';
                                                $badgeGradient = 'linear-gradient(135deg, #64748b 0%, #475569 100%)';
                                                } elseif (stripos($badge['badge_name'], 'Bronze') !== false) {
                                                $badgeColorScheme = 'bronze';
                                                    $iconClass = 'fa-medal';
                                                $badgeGradient = 'linear-gradient(135deg, #dc2626 0%, #b91c1c 100%)';
                                                } elseif (stripos($badge['badge_name'], 'Research') !== false) {
                                                $badgeColorScheme = 'research';
                                                    $iconClass = 'fa-microscope';
                                                $badgeGradient = 'linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%)';
                                                } elseif (stripos($badge['badge_name'], 'Active') !== false || stripos($badge['badge_name'], 'Champion') !== false) {
                                                $badgeColorScheme = 'active';
                                                    $iconClass = 'fa-star';
                                                $badgeGradient = 'linear-gradient(135deg, #10b981 0%, #059669 100%)';
                                                } elseif (stripos($badge['badge_name'], 'Resource') !== false) {
                                                $badgeColorScheme = 'resource';
                                                    $iconClass = 'fa-file-alt';
                                                $badgeGradient = 'linear-gradient(135deg, #2563eb 0%, #1e40af 100%)';
                                                } elseif (stripos($badge['badge_name'], 'Event') !== false) {
                                                $badgeColorScheme = 'event';
                                                    $iconClass = 'fa-calendar-check';
                                                $badgeGradient = 'linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%)';
                                                }
                                                ?>
                                            <div class="mp-badge-card">
                                                <div class="mp-badge-icon" style="background: <?php echo $badgeGradient; ?>;">
                                                    <i class="fas <?php echo $iconClass; ?>"></i>
                                                            </div>
                                                <div class="mp-badge-content">
                                                    <h6 class="mp-badge-title"><?php echo htmlspecialchars($badge['badge_name']); ?></h6>
                                                            <?php if (!empty($badge['description'])): ?>
                                                        <p class="mp-badge-description"><?php echo htmlspecialchars($badge['description']); ?></p>
                                                            <?php endif; ?>
                                                    <div class="mp-badge-date">
                                                                    <i class="fas fa-calendar"></i> 
                                                                    <?php echo date('M d, Y', strtotime($badge['earned_at'])); ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php else: ?>
                    <div class="mp-empty-state">
                        <i class="fas fa-trophy"></i>
                        <p>No Badges Yet</p>
                        <small>Start participating in activities to earn badges!</small>
                        <div style="margin-top: var(--mp-space-md);">
                            <a href="member-dashboard.php" class="mp-btn mp-btn-primary">
                                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                                    </a>
                                </div>
                            </div>
                <?php endif; ?>
                        </div>
                    </div>
                </div>

</body>
</html>

