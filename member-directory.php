<?php
session_start();

// Check if member is logged in
if (!isset($_SESSION['member_id'])) {
    header("Location: member-login.php");
    exit();
}

include 'include/config.php';
require_once 'include/badge_calculator.php';

$member_id = $_SESSION['member_id'];

// Get filter parameters
$search = trim($_GET['search'] ?? '');
$qualification = $_GET['qualification'] ?? '';
$location = trim($_GET['location'] ?? '');
$sort_by = $_GET['sort_by'] ?? 'name';

// Build query
$where = ["r.approval_status = 'approved'", "r.status = 'active'"];
$params = [];
$types = '';

// Search filter
if (!empty($search)) {
    $where[] = "(r.fullname LIKE ? OR r.email LIKE ? OR r.qualification LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $types .= 'sss';
}

// Qualification filter
if (!empty($qualification)) {
    $where[] = "r.qualification LIKE ?";
    $params[] = "%$qualification%";
    $types .= 's';
}

// Location filter (address)
if (!empty($location)) {
    $where[] = "r.address LIKE ?";
    $params[] = "%$location%";
    $types .= 's';
}

$whereClause = implode(' AND ', $where);

// Sort order
$orderBy = 'r.fullname ASC';
if ($sort_by == 'recent') {
    $orderBy = 'r.created_at DESC';
} elseif ($sort_by == 'qualification') {
    $orderBy = 'r.qualification ASC';
}

$query = "SELECT r.id, r.fullname, r.email, r.phone, r.address, r.qualification, 
                 r.photo, r.membership_id, r.created_at,
                 (SELECT COUNT(*) FROM member_badges WHERE member_id = r.id AND is_active = 1) as badge_count,
                 (SELECT COUNT(*) FROM research_projects WHERE created_by = r.id) as research_count
          FROM registrations r
          WHERE $whereClause
          ORDER BY $orderBy
          LIMIT 100";

if (!empty($params)) {
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = mysqli_query($conn, $query);
}

$members = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $members[] = $row;
    }
    if (isset($stmt)) {
        $stmt->close();
    }
}

// Get unique qualifications for filter
$qualificationsQuery = "SELECT DISTINCT qualification FROM registrations 
                        WHERE approval_status = 'approved' AND qualification IS NOT NULL AND qualification != ''
                        ORDER BY qualification";
$qualificationsResult = mysqli_query($conn, $qualificationsQuery);
$qualifications = [];
while ($row = $qualificationsResult->fetch_assoc()) {
    $qualifications[] = $row['qualification'];
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
                        <h2 class="mp-page-title"><i class="fas fa-users"></i> Member Directory</h2>
                        <p style="color: var(--mp-gray-600); margin: var(--mp-space-xs) 0 0 0; font-size: 0.875rem;">Connect with fellow members and build your professional network</p>
                        </div>
                    <span class="mp-badge mp-badge-primary"><?php echo count($members); ?> Member<?php echo count($members) != 1 ? 's' : ''; ?></span>
            </div>

            <!-- Search and Filters -->
                <div class="mp-card mp-mb-lg">
                    <div class="mp-card-body">
                        <form method="GET" class="mp-directory-search-form">
                            <div class="mp-search-main">
                                <div class="mp-search-input-wrapper">
                                    <i class="fas fa-search mp-search-icon"></i>
                                    <input type="text" name="search" class="mp-form-control mp-search-input" 
                                           placeholder="Search by name, email, or qualification..." 
                                           value="<?php echo htmlspecialchars($search); ?>">
                                    <?php if (!empty($search)): ?>
                                        <button type="button" class="mp-search-clear" onclick="this.previousElementSibling.value=''; this.form.submit();">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                                </div>
                            <div class="mp-filter-row">
                                <div class="mp-filter-group">
                                    <label class="mp-filter-label">
                                        <i class="fas fa-graduation-cap"></i> Qualification
                                    </label>
                                    <select name="qualification" class="mp-form-control mp-filter-select">
                                        <option value="">All</option>
                                        <?php foreach ($qualifications as $qual): ?>
                                            <option value="<?php echo htmlspecialchars($qual); ?>" 
                                                    <?php echo $qualification == $qual ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($qual); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mp-filter-group">
                                    <label class="mp-filter-label">
                                        <i class="fas fa-map-marker-alt"></i> Location
                                    </label>
                                    <input type="text" name="location" class="mp-form-control mp-filter-input" 
                                           placeholder="Location..." 
                                           value="<?php echo htmlspecialchars($location); ?>">
                                </div>
                                <div class="mp-filter-group">
                                    <label class="mp-filter-label">
                                        <i class="fas fa-sort"></i> Sort By
                                    </label>
                                    <select name="sort_by" class="mp-form-control mp-filter-select">
                                        <option value="name" <?php echo $sort_by == 'name' ? 'selected' : ''; ?>>Name</option>
                                        <option value="recent" <?php echo $sort_by == 'recent' ? 'selected' : ''; ?>>Recent</option>
                                        <option value="qualification" <?php echo $sort_by == 'qualification' ? 'selected' : ''; ?>>Qualification</option>
                                    </select>
                                </div>
                                <div class="mp-filter-actions">
                                    <button type="submit" class="mp-btn mp-btn-primary">
                                        <i class="fas fa-search"></i> <span class="mp-btn-text">Search</span>
                                    </button>
                                    <?php if (!empty($search) || !empty($qualification) || !empty($location)): ?>
                                        <a href="member-directory.php" class="mp-btn mp-btn-outline">
                                            <i class="fas fa-times"></i> <span class="mp-btn-text">Clear</span>
                                        </a>
                                    <?php endif; ?>
                                </div>
                                </div>
                            </form>
                </div>
            </div>

            <!-- Members Grid -->
                <?php if (empty($members)): ?>
                    <div class="mp-empty-state">
                        <i class="fas fa-users"></i>
                        <p>No Members Found</p>
                        <small>Try adjusting your search criteria or filters.</small>
                    </div>
                <?php else: ?>
                    <div class="mp-directory-grid">
                    <?php foreach ($members as $member): ?>
                            <div class="mp-card mp-member-card">
                                <div class="mp-card-body" style="text-align: center;">
                                    <!-- Member Photo -->
                                    <div style="margin-bottom: var(--mp-space-md);">
                                        <?php if (!empty($member['photo'])): ?>
                                            <img src="<?php echo htmlspecialchars($member['photo']); ?>" 
                                                 alt="<?php echo htmlspecialchars($member['fullname']); ?>"
                                                 style="width: 80px; height: 80px; border-radius: 50%; border: 3px solid var(--mp-primary); object-fit: cover;">
                                        <?php else: ?>
                                            <div style="width: 80px; height: 80px; border-radius: 50%; border: 3px solid var(--mp-primary); background: var(--mp-gray-100); display: inline-flex; align-items: center; justify-content: center;">
                                                <i class="fas fa-user" style="font-size: 2rem; color: var(--mp-gray-400);"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Member Name -->
                                    <h6 style="font-size: 0.9375rem; font-weight: 600; margin-bottom: var(--mp-space-xs); color: var(--mp-gray-900);"><?php echo htmlspecialchars($member['fullname']); ?></h6>

                                    <!-- Qualification -->
                                    <?php if (!empty($member['qualification'])): ?>
                                        <p style="margin-bottom: var(--mp-space-sm);">
                                            <span class="mp-badge mp-badge-info" style="font-size: 0.75rem;"><?php echo htmlspecialchars($member['qualification']); ?></span>
                                        </p>
                                    <?php endif; ?>

                                    <!-- Stats -->
                                    <div style="display: flex; justify-content: center; gap: var(--mp-space-md); margin-bottom: var(--mp-space-md);">
                                        <?php if ($member['badge_count'] > 0): ?>
                                            <div>
                                                <small style="color: var(--mp-gray-600); display: block; font-size: 0.6875rem;">Badges</small>
                                                <strong style="color: var(--mp-warning); font-size: 1rem;"><?php echo $member['badge_count']; ?></strong>
                                            </div>
                                        <?php endif; ?>
                                        <?php if ($member['research_count'] > 0): ?>
                                            <div>
                                                <small style="color: var(--mp-gray-600); display: block; font-size: 0.6875rem;">Research</small>
                                                <strong style="color: var(--mp-info); font-size: 1rem;"><?php echo $member['research_count']; ?></strong>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <!-- See Detail Button -->
                                    <button type="button" class="mp-btn mp-btn-sm mp-btn-primary mp-btn-full" onclick="showMemberDetail(<?php echo htmlspecialchars(json_encode($member)); ?>)">
                                        <i class="fas fa-eye"></i> See Detail
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

    <!-- Member Detail Modal -->
    <div id="memberDetailModal" class="mp-modal-overlay" onclick="closeMemberDetail(event)">
        <div class="mp-modal-content" onclick="event.stopPropagation();">
            <div class="mp-modal-header">
                <h5>Member Details</h5>
                <button type="button" class="mp-modal-close" onclick="closeMemberDetail()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="mp-modal-body" id="memberDetailContent">
                <!-- Content will be populated by JavaScript -->
            </div>
        </div>
    </div>

    <script>
        function showMemberDetail(member) {
            const modal = document.getElementById('memberDetailModal');
            const content = document.getElementById('memberDetailContent');
            
            let html = `
                <div style="text-align: center; margin-bottom: var(--mp-space-lg);">
                    ${member.photo ? 
                        `<img src="${member.photo}" alt="${member.fullname}" style="width: 100px; height: 100px; border-radius: 50%; border: 3px solid var(--mp-primary); object-fit: cover; margin-bottom: var(--mp-space-md);">` :
                        `<div style="width: 100px; height: 100px; border-radius: 50%; border: 3px solid var(--mp-primary); background: var(--mp-gray-100); display: inline-flex; align-items: center; justify-content: center; margin-bottom: var(--mp-space-md);">
                            <i class="fas fa-user" style="font-size: 3rem; color: var(--mp-gray-400);"></i>
                        </div>`
                    }
                    <h4 style="margin-bottom: var(--mp-space-xs); color: var(--mp-gray-900);">${member.fullname || 'N/A'}</h4>
                </div>
                
                <div class="mp-info-list">
                    ${member.membership_id ? `
                        <div class="mp-info-item">
                            <div class="mp-info-item-icon" style="background: rgba(37, 99, 235, 0.1); color: var(--mp-primary);">
                                <i class="fas fa-id-card"></i>
                            </div>
                            <div class="mp-info-item-content">
                                <div class="mp-info-item-label">Membership ID</div>
                                <div class="mp-info-item-value">${member.membership_id}</div>
                            </div>
                        </div>
                    ` : ''}
                    
                    ${member.email ? `
                        <div class="mp-info-item">
                            <div class="mp-info-item-icon" style="background: rgba(14, 165, 233, 0.1); color: var(--mp-info);">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <div class="mp-info-item-content">
                                <div class="mp-info-item-label">Email</div>
                                <div class="mp-info-item-value">
                                    <a href="mailto:${member.email}" style="color: var(--mp-primary); text-decoration: none;">${member.email}</a>
                                </div>
                            </div>
                        </div>
                    ` : ''}
                    
                    ${member.phone ? `
                        <div class="mp-info-item">
                            <div class="mp-info-item-icon" style="background: rgba(16, 185, 129, 0.1); color: var(--mp-success);">
                                <i class="fas fa-phone"></i>
                            </div>
                            <div class="mp-info-item-content">
                                <div class="mp-info-item-label">Phone</div>
                                <div class="mp-info-item-value">
                                    <a href="tel:${member.phone}" style="color: var(--mp-gray-700); text-decoration: none;">${member.phone}</a>
                                </div>
                            </div>
                        </div>
                    ` : ''}
                    
                    ${member.qualification ? `
                        <div class="mp-info-item">
                            <div class="mp-info-item-icon" style="background: rgba(245, 158, 11, 0.1); color: var(--mp-warning);">
                                <i class="fas fa-graduation-cap"></i>
                            </div>
                            <div class="mp-info-item-content">
                                <div class="mp-info-item-label">Qualification</div>
                                <div class="mp-info-item-value">${member.qualification}</div>
                            </div>
                        </div>
                    ` : ''}
                    
                    ${member.address ? `
                        <div class="mp-info-item">
                            <div class="mp-info-item-icon" style="background: rgba(239, 68, 68, 0.1); color: var(--mp-danger);">
                                <i class="fas fa-map-marker-alt"></i>
                            </div>
                            <div class="mp-info-item-content">
                                <div class="mp-info-item-label">Address</div>
                                <div class="mp-info-item-value">${member.address}</div>
                            </div>
                        </div>
                    ` : ''}
                    
                    <div class="mp-info-item">
                        <div class="mp-info-item-icon" style="background: rgba(139, 92, 246, 0.1); color: #8b5cf6;">
                            <i class="fas fa-calendar"></i>
                        </div>
                        <div class="mp-info-item-content">
                            <div class="mp-info-item-label">Member Since</div>
                            <div class="mp-info-item-value">${member.created_at ? new Date(member.created_at).toLocaleDateString('en-US', { year: 'numeric', month: 'short' }) : 'N/A'}</div>
                        </div>
                    </div>
                    
                    <div class="mp-info-item">
                        <div class="mp-info-item-icon" style="background: rgba(102, 126, 234, 0.1); color: #667eea;">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="mp-info-item-content">
                            <div class="mp-info-item-label">Statistics</div>
                            <div class="mp-info-item-value" style="display: flex; gap: var(--mp-space-md);">
                                <span><strong style="color: var(--mp-warning);">${member.badge_count || 0}</strong> Badges</span>
                                <span><strong style="color: var(--mp-info);">${member.research_count || 0}</strong> Research</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div style="margin-top: var(--mp-space-xl); padding-top: var(--mp-space-lg); border-top: 1px solid var(--mp-gray-200);">
                    <div class="mp-btn-grid-2">
                        ${member.email ? `
                            <a href="mailto:${member.email}" class="mp-btn mp-btn-primary">
                                <i class="fas fa-envelope"></i> Send Email
                            </a>
                        ` : ''}
                        ${member.phone ? `
                            <a href="tel:${member.phone}" class="mp-btn mp-btn-outline-primary">
                                <i class="fas fa-phone"></i> Call Now
                            </a>
                        ` : ''}
            </div>
        </div>
            `;
            
            content.innerHTML = html;
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }
        
        function closeMemberDetail(e) {
            if (e && e.target !== e.currentTarget) return;
            const modal = document.getElementById('memberDetailModal');
            modal.style.display = 'none';
            document.body.style.overflow = '';
        }
        
        // Close modal on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeMemberDetail();
            }
        });
    </script>

</body>
</html>

