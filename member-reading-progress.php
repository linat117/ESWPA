<?php
session_start();

if (!isset($_SESSION['member_id'])) {
    header("Location: member-login.php");
    exit();
}

include 'include/config.php';
require_once __DIR__ . '/include/reading_tracker.php';

$member_id = $_SESSION['member_id'];

// Get reading progress
$progress = getMemberReadingProgress($member_id, 50);

// Get statistics
$stats = getReadingStatistics($member_id);

// Calculate completion percentage
$completion_rate = $stats['total_items'] > 0 
    ? round(($stats['completed_count'] / $stats['total_items']) * 100, 1) 
    : 0;

// Calculate total hours
$total_hours = round($stats['total_minutes'] / 60, 1);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reading Progress - Member Dashboard</title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-9ndCyUaIbzAi2FUVXJi0CjmCapSmO7SnpJef0486qhLnuZ2cdeRhO02iuK6FUUVM" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="assets/css/member-panel.css" rel="stylesheet">
</head>
<body>
    <?php include 'member-header-v1.2.php'; ?>
    
    <div class="mp-content-wrapper">
        <div class="mp-container">
            <div class="mp-content">
                <div class="mp-flex-between mp-mb-md">
                    <h2 class="mp-page-title"><i class="fas fa-book-reader"></i> Reading Progress</h2>
                </div>

                <!-- Statistics Cards -->
                <div class="mp-grid-4 mp-mb-lg">
                    <div class="mp-stat-card">
                        <i class="fas fa-book"></i>
                        <h3><?php echo $stats['total_items']; ?></h3>
                        <p>Items Read</p>
                    </div>
                    <div class="mp-stat-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                        <i class="fas fa-check-circle"></i>
                        <h3><?php echo $stats['completed_count']; ?></h3>
                        <p>Completed</p>
                    </div>
                    <div class="mp-stat-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                        <i class="fas fa-clock"></i>
                        <h3><?php echo $total_hours; ?></h3>
                        <p>Hours Read</p>
                    </div>
                    <div class="mp-stat-card" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                        <i class="fas fa-percentage"></i>
                        <h3><?php echo $completion_rate; ?>%</h3>
                        <p>Completion Rate</p>
                    </div>
                </div>

                <!-- Reading Progress List -->
                <div class="mp-card">
                    <div class="mp-card-header">
                        <h5><i class="fas fa-list"></i> My Reading Progress</h5>
                    </div>
                    <div class="mp-card-body">
                        <?php if (count($progress) > 0): ?>
                            <?php foreach ($progress as $item): 
                                $title = $item['resource_title'] ?? $item['research_title'] ?? 'Unknown';
                                $type = $item['resource_id'] ? 'Resource' : 'Research';
                                $progress_percent = $item['total_pages'] > 0 
                                    ? round(($item['page_number'] / $item['total_pages']) * 100, 1) 
                                    : 0;
                            ?>
                                <div class="mp-progress-item">
                                    <div class="mp-flex-between mp-mb-sm">
                                        <div style="flex: 1;">
                                            <h6 style="font-size: 0.875rem; font-weight: 600; margin-bottom: var(--mp-space-xs);">
                                                <?php echo htmlspecialchars($title); ?>
                                                <span class="mp-badge mp-badge-secondary" style="margin-left: var(--mp-space-xs);"><?php echo $type; ?></span>
                                                <?php if ($item['completed']): ?>
                                                    <span class="mp-badge mp-badge-success" style="margin-left: var(--mp-space-xs);">Completed</span>
                                                <?php endif; ?>
                                            </h6>
                                            <small style="color: var(--mp-gray-500); font-size: 0.75rem;">
                                                Page <?php echo $item['page_number']; ?>
                                                <?php if ($item['total_pages']): ?>
                                                    of <?php echo $item['total_pages']; ?>
                                                <?php endif; ?>
                                                | <?php echo round($item['time_spent_minutes'] / 60, 1); ?> hours read
                                                | Last read: <?php echo date('M d, Y', strtotime($item['last_read_at'])); ?>
                                            </small>
                                        </div>
                                        <?php if ($item['resource_file']): ?>
                                            <a href="../<?php echo htmlspecialchars($item['resource_file']); ?>" 
                                               class="mp-btn mp-btn-sm mp-btn-primary" target="_blank">
                                                <i class="fas fa-external-link-alt"></i> Continue Reading
                                            </a>
                                        <?php elseif ($item['research_id']): ?>
                                            <a href="member-research-detail.php?id=<?php echo $item['research_id']; ?>" 
                                               class="mp-btn mp-btn-sm mp-btn-primary">
                                                <i class="fas fa-external-link-alt"></i> View Research
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($item['total_pages'] > 0): ?>
                                        <div class="mp-progress-bar-custom">
                                            <div class="mp-progress-fill" style="width: <?php echo $progress_percent; ?>%"></div>
                                        </div>
                                        <small style="color: var(--mp-gray-500); font-size: 0.75rem; margin-top: var(--mp-space-xs); display: block;"><?php echo $progress_percent; ?>% complete</small>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="mp-alert mp-alert-info">
                                <i class="fas fa-info-circle"></i> No reading progress tracked yet. Start reading resources or research projects to track your progress!
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- jQuery (required for some Bootstrap 5 features) -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
    <!-- Bootstrap 5.3.0 Bundle (includes Popper) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" integrity="sha384-geWF76RCwLtnZ8qwWowPQNguL3RmwHVBC9FhGdlKrxdiJJigb/j/68SIy3Te4Bkz" crossorigin="anonymous"></script>
</body>
</html>

<?php
mysqli_close($conn);
?>

