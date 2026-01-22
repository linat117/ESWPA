<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: auth-login.php");
    exit();
}

include 'include/conn.php';
include 'header.php';

// Get filter parameters
$member_id = intval($_GET['member_id'] ?? 0);
$segment = $_GET['segment'] ?? 'all';
$sort_by = $_GET['sort_by'] ?? 'engagement';
$date_range = $_GET['range'] ?? '30';

$start_date = date('Y-m-d', strtotime("-{$date_range} days"));
$end_date = date('Y-m-d');

// ========== MEMBER ENGAGEMENT SCORING ==========
// Calculate engagement score for each member based on:
// - Activity count (40%)
// - Resource downloads (20%)
// - Research projects (20%)
// - Last login recency (20%)

function calculateEngagementScore($conn, $member_id, $start_date, $end_date) {
    $score = 0;
    
    // Activity count (40 points max)
    $activity_query = "SELECT COUNT(*) as count FROM member_activities 
                       WHERE member_id = ? AND created_at BETWEEN ? AND ?";
    $stmt = $conn->prepare($activity_query);
    $start_datetime = $start_date . ' 00:00:00';
    $end_datetime = $end_date . ' 23:59:59';
    $stmt->bind_param("iss", $member_id, $start_datetime, $end_datetime);
    $stmt->execute();
    $activity_count = $stmt->get_result()->fetch_assoc()['count'];
    $stmt->close();
    
    // Normalize activity score (0-40 points)
    $score += min(40, ($activity_count / 10) * 10);
    
    // Resource downloads (20 points max)
    // Check if resources table has download tracking
    $download_query = "SELECT SUM(download_count) as total FROM resources WHERE id IN (
                       SELECT DISTINCT related_id FROM member_activities 
                       WHERE member_id = ? AND related_type = 'resource')";
    $stmt = $conn->prepare($download_query);
    $stmt->bind_param("i", $member_id);
    $stmt->execute();
    $download_result = $stmt->get_result();
    if ($download_result && $download_result->num_rows > 0) {
        $download_count = $download_result->fetch_assoc()['total'] ?? 0;
        $score += min(20, ($download_count / 5) * 5);
    }
    $stmt->close();
    
    // Research projects (20 points max)
    $research_query = "SELECT COUNT(*) as count FROM research_projects WHERE member_id = ?";
    $stmt = $conn->prepare($research_query);
    $stmt->bind_param("i", $member_id);
    $stmt->execute();
    $research_count = $stmt->get_result()->fetch_assoc()['count'];
    $stmt->close();
    $score += min(20, $research_count * 5);
    
    // Last login recency (20 points max)
    $login_query = "SELECT last_login FROM member_access WHERE member_id = ?";
    $stmt = $conn->prepare($login_query);
    $stmt->bind_param("i", $member_id);
    $stmt->execute();
    $login_result = $stmt->get_result();
    if ($login_result && $login_result->num_rows > 0) {
        $last_login = $login_result->fetch_assoc()['last_login'];
        if ($last_login) {
            $days_ago = (time() - strtotime($last_login)) / (60 * 60 * 24);
            if ($days_ago <= 7) {
                $score += 20;
            } elseif ($days_ago <= 30) {
                $score += 15;
            } elseif ($days_ago <= 90) {
                $score += 10;
            } else {
                $score += 5;
            }
        }
    }
    $stmt->close();
    
    return round($score, 1);
}

// Get all members with engagement scores
$members_query = "SELECT r.id, r.fullname, r.email, r.membership_id, r.created_at, 
                  r.approval_status, r.status,
                  ma.last_login,
                  (SELECT COUNT(*) FROM member_activities WHERE member_id = r.id) as total_activities,
                  (SELECT COUNT(*) FROM research_projects WHERE member_id = r.id) as research_count
                  FROM registrations r
                  LEFT JOIN member_access ma ON r.id = ma.member_id
                  WHERE r.approval_status = 'approved'";
$members_result = $conn->query($members_query);
$members_data = [];

while ($member = $members_result->fetch_assoc()) {
    $engagement_score = calculateEngagementScore($conn, $member['id'], $start_date, $end_date);
    $member['engagement_score'] = $engagement_score;
    
    // Determine segment
    if ($engagement_score >= 70) {
        $member['segment'] = 'highly_engaged';
    } elseif ($engagement_score >= 40) {
        $member['segment'] = 'moderately_engaged';
    } elseif ($engagement_score >= 20) {
        $member['segment'] = 'low_engaged';
    } else {
        $member['segment'] = 'inactive';
    }
    
    $members_data[] = $member;
}

// Filter by segment
if ($segment != 'all') {
    $members_data = array_filter($members_data, function($m) use ($segment) {
        return $m['segment'] == $segment;
    });
}

// Sort members
if ($sort_by == 'engagement') {
    usort($members_data, function($a, $b) {
        return $b['engagement_score'] <=> $a['engagement_score'];
    });
} elseif ($sort_by == 'activity') {
    usort($members_data, function($a, $b) {
        return $b['total_activities'] <=> $a['total_activities'];
    });
} elseif ($sort_by == 'name') {
    usort($members_data, function($a, $b) {
        return strcmp($a['fullname'], $b['fullname']);
    });
}

// Get segment counts
$segment_counts = [
    'all' => count($members_data),
    'highly_engaged' => 0,
    'moderately_engaged' => 0,
    'low_engaged' => 0,
    'inactive' => 0
];

$all_members = $conn->query("SELECT id FROM registrations WHERE approval_status = 'approved'");
while ($m = $all_members->fetch_assoc()) {
    $score = calculateEngagementScore($conn, $m['id'], $start_date, $end_date);
    if ($score >= 70) {
        $segment_counts['highly_engaged']++;
    } elseif ($score >= 40) {
        $segment_counts['moderately_engaged']++;
    } elseif ($score >= 20) {
        $segment_counts['low_engaged']++;
    } else {
        $segment_counts['inactive']++;
    }
}

// If specific member selected, get detailed analytics
$member_details = null;
$member_activities = [];
$member_stats = [];

if ($member_id > 0) {
    $member_query = "SELECT r.*, ma.last_login, ma.status as access_status
                     FROM registrations r
                     LEFT JOIN member_access ma ON r.id = ma.member_id
                     WHERE r.id = ?";
    $stmt = $conn->prepare($member_query);
    $stmt->bind_param("i", $member_id);
    $stmt->execute();
    $member_result = $stmt->get_result();
    if ($member_result->num_rows > 0) {
        $member_details = $member_result->fetch_assoc();
        $member_details['engagement_score'] = calculateEngagementScore($conn, $member_id, $start_date, $end_date);
        
        // Get recent activities
        $activities_query = "SELECT * FROM member_activities 
                            WHERE member_id = ? 
                            ORDER BY created_at DESC 
                            LIMIT 50";
        $stmt = $conn->prepare($activities_query);
        $stmt->bind_param("i", $member_id);
        $stmt->execute();
        $member_activities = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        // Get member statistics
        $member_stats = [
            'total_activities' => count($member_activities),
            'activities_this_month' => 0,
            'research_projects' => 0,
            'resource_downloads' => 0,
            'last_activity' => null
        ];
        
        $month_start = date('Y-m-01');
        foreach ($member_activities as $activity) {
            if (strtotime($activity['created_at']) >= strtotime($month_start)) {
                $member_stats['activities_this_month']++;
            }
        }
        
        if (!empty($member_activities)) {
            $member_stats['last_activity'] = $member_activities[0]['created_at'];
        }
        
        // Research projects count
        $research_query = "SELECT COUNT(*) as count FROM research_projects WHERE member_id = ?";
        $stmt = $conn->prepare($research_query);
        $stmt->bind_param("i", $member_id);
        $stmt->execute();
        $member_stats['research_projects'] = $stmt->get_result()->fetch_assoc()['count'];
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
                            <div class="page-title-box">
                                <h4 class="page-title">Member Analytics</h4>
                                <div class="page-title-right">
                                    <form method="GET" class="d-inline-flex gap-2">
                                        <input type="hidden" name="segment" value="<?php echo htmlspecialchars($segment); ?>">
                                        <input type="hidden" name="sort_by" value="<?php echo htmlspecialchars($sort_by); ?>">
                                        <select name="range" class="form-select form-select-sm" onchange="this.form.submit()">
                                            <option value="7" <?php echo $date_range == '7' ? 'selected' : ''; ?>>Last 7 Days</option>
                                            <option value="30" <?php echo $date_range == '30' ? 'selected' : ''; ?>>Last 30 Days</option>
                                            <option value="90" <?php echo $date_range == '90' ? 'selected' : ''; ?>>Last 90 Days</option>
                                            <option value="365" <?php echo $date_range == '365' ? 'selected' : ''; ?>>Last Year</option>
                                        </select>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Segment Overview Cards -->
                    <div class="row">
                        <div class="col-lg-3 col-md-6">
                            <div class="card widget-flat text-bg-success">
                                <div class="card-body">
                                    <div class="float-end">
                                        <i class="ri-star-line widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0">Highly Engaged</h6>
                                    <h2 class="my-2"><?php echo number_format($segment_counts['highly_engaged']); ?></h2>
                                    <p class="mb-0 text-muted">Score ≥ 70</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <div class="card widget-flat text-bg-info">
                                <div class="card-body">
                                    <div class="float-end">
                                        <i class="ri-user-line widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0">Moderately Engaged</h6>
                                    <h2 class="my-2"><?php echo number_format($segment_counts['moderately_engaged']); ?></h2>
                                    <p class="mb-0 text-muted">Score 40-69</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <div class="card widget-flat text-bg-warning">
                                <div class="card-body">
                                    <div class="float-end">
                                        <i class="ri-user-3-line widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0">Low Engaged</h6>
                                    <h2 class="my-2"><?php echo number_format($segment_counts['low_engaged']); ?></h2>
                                    <p class="mb-0 text-muted">Score 20-39</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <div class="card widget-flat text-bg-danger">
                                <div class="card-body">
                                    <div class="float-end">
                                        <i class="ri-user-unfollow-line widget-icon"></i>
                                    </div>
                                    <h6 class="text-uppercase mt-0">Inactive</h6>
                                    <h2 class="my-2"><?php echo number_format($segment_counts['inactive']); ?></h2>
                                    <p class="mb-0 text-muted">Score < 20</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Filters and Member List -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <div class="d-flex justify-content-between align-items-center flex-wrap">
                                        <h4 class="header-title">Member Engagement Analysis</h4>
                                        <div class="d-flex gap-2">
                                            <!-- Segment Filter -->
                                            <select name="segment" class="form-select form-select-sm" onchange="window.location.href='?segment='+this.value+'&sort_by=<?php echo $sort_by; ?>&range=<?php echo $date_range; ?>'">
                                                <option value="all" <?php echo $segment == 'all' ? 'selected' : ''; ?>>All Segments</option>
                                                <option value="highly_engaged" <?php echo $segment == 'highly_engaged' ? 'selected' : ''; ?>>Highly Engaged</option>
                                                <option value="moderately_engaged" <?php echo $segment == 'moderately_engaged' ? 'selected' : ''; ?>>Moderately Engaged</option>
                                                <option value="low_engaged" <?php echo $segment == 'low_engaged' ? 'selected' : ''; ?>>Low Engaged</option>
                                                <option value="inactive" <?php echo $segment == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                            </select>
                                            <!-- Sort By -->
                                            <select name="sort_by" class="form-select form-select-sm" onchange="window.location.href='?segment=<?php echo $segment; ?>&sort_by='+this.value+'&range=<?php echo $date_range; ?>'">
                                                <option value="engagement" <?php echo $sort_by == 'engagement' ? 'selected' : ''; ?>>Sort by Engagement</option>
                                                <option value="activity" <?php echo $sort_by == 'activity' ? 'selected' : ''; ?>>Sort by Activity</option>
                                                <option value="name" <?php echo $sort_by == 'name' ? 'selected' : ''; ?>>Sort by Name</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Member</th>
                                                    <th>Engagement Score</th>
                                                    <th>Segment</th>
                                                    <th>Total Activities</th>
                                                    <th>Research Projects</th>
                                                    <th>Last Login</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($members_data as $member): ?>
                                                <tr>
                                                    <td>
                                                        <div>
                                                            <strong><?php echo htmlspecialchars($member['fullname']); ?></strong><br>
                                                            <small class="text-muted"><?php echo htmlspecialchars($member['email']); ?></small>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <div class="progress flex-grow-1" style="height: 20px; width: 100px;">
                                                                <div class="progress-bar 
                                                                    <?php 
                                                                    if ($member['engagement_score'] >= 70) echo 'bg-success';
                                                                    elseif ($member['engagement_score'] >= 40) echo 'bg-info';
                                                                    elseif ($member['engagement_score'] >= 20) echo 'bg-warning';
                                                                    else echo 'bg-danger';
                                                                    ?>" 
                                                                    role="progressbar" 
                                                                    style="width: <?php echo min(100, $member['engagement_score']); ?>%">
                                                                </div>
                                                            </div>
                                                            <span class="ms-2"><strong><?php echo number_format($member['engagement_score'], 1); ?></strong></span>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <?php
                                                        $badge_class = 'bg-secondary';
                                                        $badge_text = 'Unknown';
                                                        if ($member['segment'] == 'highly_engaged') {
                                                            $badge_class = 'bg-success';
                                                            $badge_text = 'Highly Engaged';
                                                        } elseif ($member['segment'] == 'moderately_engaged') {
                                                            $badge_class = 'bg-info';
                                                            $badge_text = 'Moderately Engaged';
                                                        } elseif ($member['segment'] == 'low_engaged') {
                                                            $badge_class = 'bg-warning';
                                                            $badge_text = 'Low Engaged';
                                                        } elseif ($member['segment'] == 'inactive') {
                                                            $badge_class = 'bg-danger';
                                                            $badge_text = 'Inactive';
                                                        }
                                                        ?>
                                                        <span class="badge <?php echo $badge_class; ?>"><?php echo $badge_text; ?></span>
                                                    </td>
                                                    <td><?php echo number_format($member['total_activities']); ?></td>
                                                    <td><?php echo number_format($member['research_count']); ?></td>
                                                    <td>
                                                        <?php 
                                                        if ($member['last_login']) {
                                                            echo date('M d, Y', strtotime($member['last_login']));
                                                        } else {
                                                            echo '<span class="text-muted">Never</span>';
                                                        }
                                                        ?>
                                                    </td>
                                                    <td>
                                                        <a href="?member_id=<?php echo $member['id']; ?>&segment=<?php echo $segment; ?>&sort_by=<?php echo $sort_by; ?>&range=<?php echo $date_range; ?>" 
                                                           class="btn btn-sm btn-primary">View Details</a>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                                <?php if (empty($members_data)): ?>
                                                <tr>
                                                    <td colspan="7" class="text-center text-muted">No members found in this segment</td>
                                                </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Member Details Section -->
                    <?php if ($member_details): ?>
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Member Details: <?php echo htmlspecialchars($member_details['fullname']); ?></h4>
                                    <a href="member_analytics.php?segment=<?php echo $segment; ?>&sort_by=<?php echo $sort_by; ?>&range=<?php echo $date_range; ?>" class="btn btn-sm btn-light">Back to List</a>
                                </div>
                                <div class="card-body">
                                    <div class="row mb-4">
                                        <div class="col-md-3">
                                            <div class="text-center p-3 border rounded">
                                                <h3 class="mb-0 text-primary"><?php echo number_format($member_details['engagement_score'], 1); ?></h3>
                                                <p class="mb-0 text-muted">Engagement Score</p>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="text-center p-3 border rounded">
                                                <h3 class="mb-0 text-info"><?php echo number_format($member_stats['total_activities']); ?></h3>
                                                <p class="mb-0 text-muted">Total Activities</p>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="text-center p-3 border rounded">
                                                <h3 class="mb-0 text-success"><?php echo number_format($member_stats['activities_this_month']); ?></h3>
                                                <p class="mb-0 text-muted">This Month</p>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="text-center p-3 border rounded">
                                                <h3 class="mb-0 text-warning"><?php echo number_format($member_stats['research_projects']); ?></h3>
                                                <p class="mb-0 text-muted">Research Projects</p>
                                            </div>
                                        </div>
                                    </div>

                                    <h5>Recent Activities</h5>
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Date</th>
                                                    <th>Activity Type</th>
                                                    <th>Description</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($member_activities as $activity): ?>
                                                <tr>
                                                    <td><?php echo date('M d, Y H:i', strtotime($activity['created_at'])); ?></td>
                                                    <td><span class="badge bg-secondary"><?php echo htmlspecialchars($activity['activity_type']); ?></span></td>
                                                    <td><?php echo htmlspecialchars($activity['activity_description']); ?></td>
                                                </tr>
                                                <?php endforeach; ?>
                                                <?php if (empty($member_activities)): ?>
                                                <tr>
                                                    <td colspan="3" class="text-center text-muted">No activities found</td>
                                                </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
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
    <script src="assets/js/app.min.js"></script>
</body>
</html>

