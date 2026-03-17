<?php
// Version 1.2 - Modern Futuristic Member Header
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if member is logged in
if (!isset($_SESSION['member_id'])) {
    header("Location: member-login.php");
    exit();
}

include 'include/config.php';

// Get member basic info for header
$member_id = $_SESSION['member_id'];
$member_query = "SELECT fullname, membership_id, photo FROM registrations WHERE id = ? LIMIT 1";
$member_stmt = $conn->prepare($member_query);
$member_stmt->bind_param("i", $member_id);
$member_stmt->execute();
$member_result = $member_stmt->get_result();
$member_info = $member_result->fetch_assoc();
$member_stmt->close();

$current_page = basename($_SERVER['PHP_SELF']);

$member_pages = [
    'member-dashboard.php' => ['title' => 'Dashboard', 'icon' => 'fa-home'],
    'member-tools.php' => ['title' => 'Tools & Features', 'icon' => 'fa-tools'],
    'member-profile-edit.php' => ['title' => 'Edit Profile', 'icon' => 'fa-user-edit'],
    'member-notifications.php' => ['title' => 'Notifications', 'icon' => 'fa-bell'],
    'member-badges.php' => ['title' => 'My Badges', 'icon' => 'fa-trophy'],
    'member-directory.php' => ['title' => 'Member Directory', 'icon' => 'fa-users'],
    'member-generate-id-card.php' => ['title' => 'My ID Card', 'icon' => 'fa-id-card'],
    'resources.php' => ['title' => 'Resources', 'icon' => 'fa-download'],
    'member-research.php' => ['title' => 'Research', 'icon' => 'fa-search'],
    'member-citations.php' => ['title' => 'Citations', 'icon' => 'fa-quote-left'],
    'member-notes.php' => ['title' => 'Notes', 'icon' => 'fa-sticky-note'],
    'member-bibliography.php' => ['title' => 'Bibliography', 'icon' => 'fa-book'],
    'member-reading-progress.php' => ['title' => 'Reading Progress', 'icon' => 'fa-book-reader'],
    'news.php' => ['title' => 'News & Media', 'icon' => 'fa-newspaper'],
];
?>
<header class="member-header-v1-2 member-header-fixed">
    <!-- Mobile Side-Slide Menu -->
    <div id="memberSidebar" class="member-sidebar-v1-2">
        <div class="member-sidebar-header-v1-2">
            <div class="member-sidebar-user-v1-2">
                <?php if (!empty($member_info['photo'])): ?>
                    <img src="<?php echo htmlspecialchars($member_info['photo']); ?>" 
                         alt="Member Photo" 
                         class="member-sidebar-avatar-v1-2">
                <?php else: ?>
                    <div class="member-sidebar-avatar-v1-2 placeholder">
                        <i class="fas fa-user"></i>
                    </div>
                <?php endif; ?>
                <div class="member-sidebar-user-info-v1-2">
                    <strong><?php echo htmlspecialchars($member_info['fullname']); ?></strong>
                    <span><?php echo htmlspecialchars($member_info['membership_id'] ?? 'N/A'); ?></span>
                </div>
            </div>
            <button class="member-sidebar-close-v1-2" id="closeMemberSidebar" aria-label="Close menu">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <nav class="member-sidebar-nav-v1-2">
            <div class="member-sidebar-section-v1-2">
                <div class="member-sidebar-section-title">Main</div>
                <a href="member-dashboard.php" class="member-sidebar-item-v1-2 <?php echo $current_page == 'member-dashboard.php' ? 'active' : ''; ?>">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                    <?php if ($current_page == 'member-dashboard.php'): ?>
                        <i class="fas fa-chevron-right active-indicator"></i>
                    <?php endif; ?>
                </a>
                <a href="member-profile-edit.php" class="member-sidebar-item-v1-2 <?php echo $current_page == 'member-profile-edit.php' ? 'active' : ''; ?>">
                    <i class="fas fa-user-edit"></i>
                    <span>Edit Profile</span>
                    <?php if ($current_page == 'member-profile-edit.php'): ?>
                        <i class="fas fa-chevron-right active-indicator"></i>
                    <?php endif; ?>
                </a>
                <a href="member-notifications.php" class="member-sidebar-item-v1-2 <?php echo $current_page == 'member-notifications.php' ? 'active' : ''; ?>">
                    <i class="fas fa-bell"></i>
                    <span>Notifications</span>
                    <?php 
                    // Get unread count
                    require_once 'include/notifications_handler.php';
                    $unreadCount = getUnreadNotificationCount($member_id);
                    if ($unreadCount > 0): 
                    ?>
                        <span class="badge bg-danger ms-auto"><?php echo $unreadCount; ?></span>
                    <?php endif; ?>
                    <?php if ($current_page == 'member-notifications.php'): ?>
                        <i class="fas fa-chevron-right active-indicator"></i>
                    <?php endif; ?>
                </a>
                <a href="member-badges.php" class="member-sidebar-item-v1-2 <?php echo $current_page == 'member-badges.php' ? 'active' : ''; ?>">
                    <i class="fas fa-trophy"></i>
                    <span>My Badges</span>
                    <?php if ($current_page == 'member-badges.php'): ?>
                        <i class="fas fa-chevron-right active-indicator"></i>
                    <?php endif; ?>
                </a>
                <a href="member-directory.php" class="member-sidebar-item-v1-2 <?php echo $current_page == 'member-directory.php' ? 'active' : ''; ?>">
                    <i class="fas fa-users"></i>
                    <span>Member Directory</span>
                    <?php if ($current_page == 'member-directory.php'): ?>
                        <i class="fas fa-chevron-right active-indicator"></i>
                    <?php endif; ?>
                </a>
                <a href="member-generate-id-card.php" class="member-sidebar-item-v1-2 <?php echo in_array($current_page, ['member-generate-id-card.php', 'member-id-card.php']) ? 'active' : ''; ?>">
                    <i class="fas fa-id-card"></i>
                    <span>My ID Card</span>
                    <?php if (in_array($current_page, ['member-generate-id-card.php', 'member-id-card.php'])): ?>
                        <i class="fas fa-chevron-right active-indicator"></i>
                    <?php endif; ?>
                </a>
                <a href="resources.php" class="member-sidebar-item-v1-2 <?php echo $current_page == 'resources.php' ? 'active' : ''; ?>">
                    <i class="fas fa-download"></i>
                    <span>Resources</span>
                    <?php if ($current_page == 'resources.php'): ?>
                        <i class="fas fa-chevron-right active-indicator"></i>
                    <?php endif; ?>
                </a>
                <a href="member-research.php" class="member-sidebar-item-v1-2 <?php echo in_array($current_page, ['member-research.php', 'member-research-detail.php', 'member-create-research.php', 'member-research-library.php']) ? 'active' : ''; ?>">
                    <i class="fas fa-search"></i>
                    <span>Research</span>
                    <?php if (in_array($current_page, ['member-research.php', 'member-research-detail.php', 'member-create-research.php', 'member-research-library.php'])): ?>
                        <i class="fas fa-chevron-right active-indicator"></i>
                    <?php endif; ?>
                </a>
            </div>
            
            <!-- Tools & Features section temporarily disabled for this version
            <div class="member-sidebar-section-v1-2">
                <div class="member-sidebar-section-title">Tools & Features</div>
                <a href="member-tools.php" class="member-sidebar-item-v1-2 <?php echo in_array($current_page, ['member-tools.php', 'member-citations.php', 'member-notes.php', 'member-bibliography.php', 'member-reading-progress.php', 'member-research-library.php', 'member-create-research.php', 'member-profile-edit.php', 'member-badges.php', 'member-notifications.php', 'member-generate-id-card.php']) ? 'active' : ''; ?>">
                    <i class="fas fa-tools"></i>
                    <span>All Tools</span>
                    <?php if ($current_page == 'member-tools.php'): ?>
                        <i class="fas fa-chevron-right active-indicator"></i>
                    <?php endif; ?>
                </a>
            </div>
            -->
            
            <div class="member-sidebar-section-v1-2">
                <div class="member-sidebar-section-title">Explore</div>
                <a href="news.php" class="member-sidebar-item-v1-2 <?php echo $current_page == 'news.php' ? 'active' : ''; ?>">
                    <i class="fas fa-newspaper"></i>
                    <span>News</span>
                    <?php if ($current_page == 'news.php'): ?>
                        <i class="fas fa-chevron-right active-indicator"></i>
                    <?php endif; ?>
                </a>
            </div>
            
            <div class="member-sidebar-section-v1-2">
                <div class="member-sidebar-section-title">Explore</div>
                <a href="index.php" class="member-sidebar-item-v1-2">
                    <i class="fas fa-globe"></i>
                    <span>Public Site</span>
                </a>
                <a href="events.php" class="member-sidebar-item-v1-2">
                    <i class="fas fa-calendar"></i>
                    <span>Events</span>
                </a>
                <a href="about.php" class="member-sidebar-item-v1-2">
                    <i class="fas fa-info-circle"></i>
                    <span>About Us</span>
                </a>
            </div>
        </nav>
        
        <div class="member-sidebar-footer-v1-2">
            <div class="member-sidebar-footer-item">
                <i class="fas fa-code"></i>
                <span>Version 1.2</span>
            </div>
            <div class="member-sidebar-footer-item">
                <i class="fas fa-users"></i>
                <span>Lebawi Net Trading PLC</span>
            </div>
        </div>
        
        <div class="member-sidebar-logout-v1-2">
            <a href="member-logout.php" class="member-sidebar-item-v1-2 logout">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </div>
    </div>
    
    <!-- Sidebar Overlay -->
    <div class="member-sidebar-overlay-v1-2" id="memberSidebarOverlay"></div>

    <!-- Main Navigation Bar -->
    <nav class="member-navbar-v1-2">
        <div class="member-navbar-container-v1-2">
            <!-- Mobile Hamburger -->
            <button class="member-hamburger-v1-2 d-md-none" id="openMemberSidebar" aria-label="Open menu">
                <span></span>
                <span></span>
                <span></span>
            </button>

            <!-- Logo -->
            <a href="member-dashboard.php" class="member-navbar-logo-v1-2">
                <img src="assets/img/content/logo-light.png" alt="ESWPA" class="member-logo-img-v1-2">
            </a>

            <!-- Desktop Navigation Menu - Streamlined -->
            <div class="member-navbar-menu-v1-2 d-none d-md-flex">
                <a href="member-dashboard.php" class="member-navbar-link-v1-2 <?php echo $current_page == 'member-dashboard.php' ? 'active' : ''; ?>">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
                <a href="member-directory.php" class="member-navbar-link-v1-2 <?php echo $current_page == 'member-directory.php' ? 'active' : ''; ?>">
                    <i class="fas fa-users"></i>
                    <span>Directory</span>
                </a>
                <a href="resources.php" class="member-navbar-link-v1-2 <?php echo $current_page == 'resources.php' ? 'active' : ''; ?>">
                    <i class="fas fa-download"></i>
                    <span>Resources</span>
                </a>
                <a href="member-research.php" class="member-navbar-link-v1-2 <?php echo in_array($current_page, ['member-research.php', 'member-research-detail.php', 'member-create-research.php', 'member-research-library.php']) ? 'active' : ''; ?>">
                    <i class="fas fa-search"></i>
                    <span>Research</span>
                </a>
                <!-- Tools nav item temporarily disabled for this version
                <a href="member-tools.php" class="member-navbar-link-v1-2 <?php echo in_array($current_page, ['member-tools.php', 'member-citations.php', 'member-notes.php', 'member-bibliography.php', 'member-reading-progress.php', 'member-research-library.php', 'member-create-research.php', 'member-profile-edit.php', 'member-badges.php', 'member-notifications.php', 'member-generate-id-card.php']) ? 'active' : ''; ?>" title="Tools & Features">
                    <i class="fas fa-tools"></i>
                    <span>Tools</span>
                </a>
                -->
                <a href="news.php" class="member-navbar-link-v1-2 <?php echo $current_page == 'news.php' ? 'active' : ''; ?>">
                    <i class="fas fa-newspaper"></i>
                    <span>News</span>
                </a>
            </div>

            <!-- Profile Dropdown -->
            <div class="member-navbar-profile-v1-2">
                <div class="member-profile-dropdown-v1-2">
                    <button class="member-profile-btn-v1-2" type="button" id="memberProfileDropdownBtn" onclick="void(0)">
                        <?php if (!empty($member_info['photo'])): ?>
                            <img src="<?php echo htmlspecialchars($member_info['photo']); ?>" 
                                 alt="Member Photo" 
                                 class="member-profile-avatar-v1-2">
                        <?php else: ?>
                            <div class="member-profile-icon-v1-2">
                                <i class="fas fa-user-circle"></i>
                            </div>
                        <?php endif; ?>
                        <span class="member-profile-name-v1-2 d-none d-lg-inline"><?php echo htmlspecialchars($member_info['fullname']); ?></span>
                        <i class="fas fa-chevron-down member-profile-arrow-v1-2"></i>
                    </button>
                    <div class="member-profile-menu-v1-2" id="memberProfileDropdownMenu">
                        <div class="member-profile-menu-header-v1-2">
                            <strong><?php echo htmlspecialchars($member_info['fullname']); ?></strong>
                            <small><?php echo htmlspecialchars($member_info['membership_id'] ?? 'N/A'); ?></small>
                        </div>
                        <div class="member-profile-menu-divider-v1-2"></div>
                        <a href="member-dashboard.php" class="member-profile-menu-item-v1-2">
                            <i class="fas fa-tachometer-alt"></i>
                            <span>Dashboard</span>
                        </a>
                        <a href="member-profile-edit.php" class="member-profile-menu-item-v1-2">
                            <i class="fas fa-user-edit"></i>
                            <span>Edit Profile</span>
                        </a>
                        <?php 
                        // Get unread count for profile dropdown
                        if (!isset($unreadCount)) {
                            require_once 'include/notifications_handler.php';
                            $unreadCount = getUnreadNotificationCount($member_id);
                        }
                        ?>
                        <a href="member-notifications.php" class="member-profile-menu-item-v1-2">
                            <i class="fas fa-bell"></i>
                            <span>Notifications</span>
                            <?php if ($unreadCount > 0): ?>
                                <span class="badge bg-danger ms-auto"><?php echo $unreadCount; ?></span>
                            <?php endif; ?>
                        </a>
                        <a href="member-badges.php" class="member-profile-menu-item-v1-2">
                            <i class="fas fa-trophy"></i>
                            <span>My Badges</span>
                        </a>
                        <a href="member-generate-id-card.php" class="member-profile-menu-item-v1-2">
                            <i class="fas fa-id-card"></i>
                            <span>My ID Card</span>
                        </a>
                        <div class="member-profile-menu-divider-v1-2"></div>
                        <a href="member-dashboard.php" class="member-profile-menu-item-v1-2">
                            <i class="fas fa-tachometer-alt"></i>
                            <span>Dashboard</span>
                        </a>
                        <div class="member-profile-menu-divider-v1-2"></div>
                        <a href="index.php" class="member-profile-menu-item-v1-2">
                            <i class="fas fa-globe"></i>
                            <span>Public Site</span>
                        </a>
                        <div class="member-profile-menu-divider-v1-2"></div>
                        <a href="member-logout.php" class="member-profile-menu-item-v1-2 logout">
                            <i class="fas fa-sign-out-alt"></i>
                            <span>Logout</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </nav>
</header>

<!-- Mobile Bottom Navigation -->
<nav class="member-bottom-nav-v1-2 d-md-none">
    <a href="member-dashboard.php" class="member-bottom-nav-item-v1-2 <?php echo $current_page == 'member-dashboard.php' ? 'active' : ''; ?>">
        <div class="bottom-nav-icon-wrapper">
            <i class="fas fa-home"></i>
        </div>
        <span>Dashboard</span>
    </a>
    <a href="resources.php" class="member-bottom-nav-item-v1-2 <?php echo $current_page == 'resources.php' ? 'active' : ''; ?>">
        <div class="bottom-nav-icon-wrapper">
            <i class="fas fa-download"></i>
        </div>
        <span>Resources</span>
    </a>
    <a href="member-research.php" class="member-bottom-nav-item-v1-2 <?php echo in_array($current_page, ['member-research.php', 'member-research-detail.php', 'member-create-research.php', 'member-research-library.php']) ? 'active' : ''; ?>">
        <div class="bottom-nav-icon-wrapper">
            <i class="fas fa-search"></i>
        </div>
        <span>Research</span>
    </a>
    <!-- Tools bottom-nav item temporarily disabled for this version
    <a href="member-tools.php" class="member-bottom-nav-item-v1-2 <?php echo in_array($current_page, ['member-tools.php', 'member-citations.php', 'member-notes.php', 'member-bibliography.php', 'member-reading-progress.php', 'member-research-library.php', 'member-create-research.php', 'member-profile-edit.php', 'member-badges.php', 'member-notifications.php', 'member-generate-id-card.php']) ? 'active' : ''; ?>">
        <div class="bottom-nav-icon-wrapper">
            <i class="fas fa-tools"></i>
        </div>
        <span>Tools</span>
    </a>
    -->
    <a href="news.php" class="member-bottom-nav-item-v1-2 <?php echo $current_page == 'news.php' ? 'active' : ''; ?>">
        <div class="bottom-nav-icon-wrapper">
            <i class="fas fa-newspaper"></i>
        </div>
        <span>News</span>
    </a>
    <a href="member-dashboard.php" class="member-bottom-nav-item-v1-2 profile-trigger">
        <?php if (!empty($member_info['photo'])): ?>
            <div class="bottom-nav-icon-wrapper">
                <img src="<?php echo htmlspecialchars($member_info['photo']); ?>" 
                     alt="Profile" 
                     class="member-bottom-nav-avatar-v1-2">
            </div>
        <?php else: ?>
            <div class="bottom-nav-icon-wrapper">
                <i class="fas fa-user"></i>
            </div>
        <?php endif; ?>
        <span>Profile</span>
    </a>
</nav>

<style>
/* ============================================
   Version 1.2 - Modern Futuristic Member Header
   ============================================ */

/* Header Container - Fixed */
.member-header-v1-2 {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    z-index: 1000;
    width: 100%;
}

/* Main Navigation Bar - Fixed */
.member-navbar-v1-2 {
    position: relative;
    background: #ffffff;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    border-bottom: 1px solid #e2e8f0;
    transition: all 0.3s ease;
}

.member-navbar-v1-2::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, #667eea 0%, #764ba2 50%, #667eea 100%);
    background-size: 200% 100%;
    animation: gradientShift 3s ease infinite;
}

@keyframes gradientShift {
    0%, 100% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
}

.member-navbar-container-v1-2 {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 16px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    height: 64px;
}

/* Hamburger Menu (Mobile) - Consistent Design */
.member-hamburger-v1-2 {
    display: flex;
    flex-direction: column;
    gap: 4px;
    background: #0284c7;
    border: none;
    padding: 10px;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.2s ease;
    z-index: 1001;
}

.member-hamburger-v1-2 span {
    width: 22px;
    height: 2.5px;
    background: white;
    border-radius: 2px;
    transition: all 0.2s ease;
    display: block;
}

.member-hamburger-v1-2:hover {
    background: #0369a1;
    box-shadow: 0 2px 8px rgba(14, 165, 233, 0.3);
}

.member-hamburger-v1-2.active span:nth-child(1) {
    transform: rotate(45deg) translate(8px, 8px);
}

.member-hamburger-v1-2.active span:nth-child(2) {
    opacity: 0;
}

.member-hamburger-v1-2.active span:nth-child(3) {
    transform: rotate(-45deg) translate(7px, -7px);
}

/* Logo */
.member-navbar-logo-v1-2 {
    display: flex;
    align-items: center;
    text-decoration: none;
    margin-left: 15px;
}

.member-logo-img-v1-2 {
    height: 55px;
    width: auto;
    object-fit: contain;
    transition: transform 0.3s;
}

.member-navbar-logo-v1-2:hover .member-logo-img-v1-2 {
    transform: scale(1.05);
}

/* Desktop Navigation Menu */
.member-navbar-menu-v1-2 {
    display: flex;
    align-items: center;
    gap: 5px;
    flex: 1;
    justify-content: center;
    margin: 0 30px;
}

.member-navbar-link-v1-2 {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    color: #1e293b;
    text-decoration: none;
    border-radius: 6px;
    transition: all 0.2s ease;
    font-weight: 500;
    font-size: 0.875rem;
    position: relative;
}

.member-navbar-link-v1-2:hover {
    background: rgba(14, 165, 233, 0.08);
    color: #0284c7;
    text-decoration: none;
}

.member-navbar-link-v1-2.active {
    background: rgba(14, 165, 233, 0.12);
    color: #0284c7;
}

.member-navbar-link-v1-2 i {
    font-size: 16px;
}

/* Profile Dropdown */
.member-navbar-profile-v1-2 {
    flex-shrink: 0;
}

.member-profile-dropdown-v1-2 {
    position: relative;
}

.member-profile-btn-v1-2 {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 6px 12px;
    background: rgba(14, 165, 233, 0.08);
    border: 1px solid rgba(14, 165, 233, 0.2);
    border-radius: 20px;
    cursor: pointer;
    transition: all 0.2s ease;
    text-decoration: none;
    color: #1e293b;
}

.member-profile-btn-v1-2:hover {
    background: rgba(37, 99, 235, 0.15);
    border-color: #2563eb;
    text-decoration: none;
    color: #1e293b;
}

.member-profile-avatar-v1-2 {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid #667eea;
}

.member-profile-icon-v1-2 {
    width: 38px;
    height: 38px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #667eea;
    font-size: 26px;
}

.member-profile-name-v1-2 {
    font-weight: 600;
    font-size: 14px;
    max-width: 150px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.member-profile-arrow-v1-2 {
    font-size: 12px;
    color: #667eea;
    transition: transform 0.3s;
}

.member-profile-dropdown-v1-2.active .member-profile-arrow-v1-2 {
    transform: rotate(180deg);
}

/* Profile Dropdown Menu */
.member-profile-menu-v1-2 {
    position: absolute;
    top: calc(100% + 10px);
    right: 0;
    background: white;
    border-radius: 15px;
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15);
    min-width: 260px;
    opacity: 0;
    visibility: hidden;
    transform: translateY(-10px);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    z-index: 1000;
    overflow: hidden;
}

.member-profile-dropdown-v1-2.active .member-profile-menu-v1-2 {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

.member-profile-menu-header-v1-2 {
    padding: 18px 20px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.member-profile-menu-header-v1-2 strong {
    display: block;
    font-size: 16px;
    margin-bottom: 5px;
    font-weight: 600;
}

.member-profile-menu-header-v1-2 small {
    display: block;
    font-size: 12px;
    opacity: 0.9;
}

.member-profile-menu-divider-v1-2 {
    height: 1px;
    background: linear-gradient(90deg, transparent, #e0e0e0, transparent);
    margin: 8px 0;
}

.member-profile-menu-item-v1-2 {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 20px;
    color: #333;
    text-decoration: none;
    transition: all 0.3s;
    position: relative;
}

.member-profile-menu-item-v1-2::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 0;
    background: linear-gradient(90deg, rgba(102, 126, 234, 0.1), transparent);
    transition: width 0.3s;
}

.member-profile-menu-item-v1-2:hover::before {
    width: 100%;
}

.member-profile-menu-item-v1-2 i {
    width: 22px;
    text-align: center;
    color: #667eea;
    font-size: 16px;
    position: relative;
    z-index: 1;
}

.member-profile-menu-item-v1-2 span {
    position: relative;
    z-index: 1;
}

.member-profile-menu-item-v1-2:hover {
    background: rgba(102, 126, 234, 0.05);
    color: #667eea;
    text-decoration: none;
}

.member-profile-menu-item-v1-2.logout {
    color: #dc3545;
}

.member-profile-menu-item-v1-2.logout i {
    color: #dc3545;
}

.member-profile-menu-item-v1-2.logout:hover {
    background: rgba(220, 53, 69, 0.05);
    color: #dc3545;
}

/* Member Sidebar Styles */
.member-sidebar-v1-2 {
    position: fixed;
    top: 0;
    right: -100%;
    width: 320px;
    max-width: 85vw;
    height: 100vh;
    background: linear-gradient(180deg, #1a1a2e 0%, #16213e 100%);
    z-index: 10000;
    transition: right 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    overflow-y: auto;
    box-shadow: -5px 0 30px rgba(0, 0, 0, 0.5);
    display: flex;
    flex-direction: column;
}

.member-sidebar-v1-2.active {
    right: 0;
}

.member-sidebar-header-v1-2 {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 25px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    position: relative;
    overflow: hidden;
}

.member-sidebar-header-v1-2::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
    animation: rotate 20s linear infinite;
}

.member-sidebar-user-v1-2 {
    display: flex;
    align-items: center;
    gap: 12px;
    position: relative;
    z-index: 1;
    flex: 1;
}

.member-sidebar-avatar-v1-2 {
    width: 55px;
    height: 55px;
    border-radius: 50%;
    border: 3px solid rgba(255,255,255,0.3);
    object-fit: cover;
}

.member-sidebar-avatar-v1-2.placeholder {
    background: rgba(255,255,255,0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 24px;
}

.member-sidebar-user-info-v1-2 {
    color: white;
    flex: 1;
}

.member-sidebar-user-info-v1-2 strong {
    display: block;
    font-size: 15px;
    margin-bottom: 4px;
    font-weight: 600;
}

.member-sidebar-user-info-v1-2 span {
    display: block;
    font-size: 12px;
    opacity: 0.9;
    color: rgba(255,255,255,0.9);
}

.member-sidebar-close-v1-2 {
    background: rgba(255,255,255,0.2);
    border: none;
    color: white;
    width: 38px;
    height: 38px;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s;
    position: relative;
    z-index: 1;
}

.member-sidebar-close-v1-2:hover {
    background: rgba(255,255,255,0.3);
    transform: rotate(90deg);
}

.member-sidebar-nav-v1-2 {
    flex: 1;
    padding: 20px 0;
    overflow-y: auto;
}

.member-sidebar-section-v1-2 {
    margin-bottom: 25px;
}

.member-sidebar-section-title {
    color: rgba(255, 255, 255, 0.5);
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 2px;
    padding: 0 20px;
    margin-bottom: 12px;
    font-weight: 600;
}

.member-sidebar-item-v1-2 {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 14px 20px;
    color: rgba(255, 255, 255, 0.8);
    text-decoration: none;
    transition: all 0.3s;
    border-left: 4px solid transparent;
    position: relative;
}

.member-sidebar-item-v1-2::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 0;
    background: linear-gradient(90deg, rgba(102, 126, 234, 0.2), transparent);
    transition: width 0.3s;
}

.member-sidebar-item-v1-2:hover::before,
.member-sidebar-item-v1-2.active::before {
    width: 100%;
}

.member-sidebar-item-v1-2:hover,
.member-sidebar-item-v1-2.active {
    color: white;
    border-left-color: #667eea;
    background: rgba(102, 126, 234, 0.1);
    text-decoration: none;
}

.member-sidebar-item-v1-2 i:first-child {
    width: 22px;
    text-align: center;
    font-size: 18px;
    color: #667eea;
    transition: all 0.3s;
}

.member-sidebar-item-v1-2:hover i:first-child,
.member-sidebar-item-v1-2.active i:first-child {
    color: #667eea;
    transform: scale(1.1);
}

.member-sidebar-item-v1-2 span {
    flex: 1;
    font-weight: 500;
}

.member-sidebar-item-v1-2.logout {
    color: #ff6b6b;
}

.member-sidebar-item-v1-2.logout:hover {
    background: rgba(255, 107, 107, 0.1);
    border-left-color: #ff6b6b;
}

.member-sidebar-item-v1-2.logout i:first-child {
    color: #ff6b6b;
}

.member-sidebar-footer-v1-2 {
    padding: 20px;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    margin-top: auto;
}

.member-sidebar-footer-item {
    display: flex;
    align-items: center;
    gap: 10px;
    color: rgba(255, 255, 255, 0.5);
    font-size: 11px;
    margin-bottom: 8px;
}

.member-sidebar-footer-item:last-child {
    margin-bottom: 0;
}

.member-sidebar-footer-item i {
    width: 16px;
    text-align: center;
    color: #667eea;
}

.member-sidebar-logout-v1-2 {
    padding: 0 20px 20px;
}

.member-sidebar-overlay-v1-2 {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.6);
    z-index: 9999;
    display: none;
    opacity: 0;
    transition: opacity 0.3s;
    backdrop-filter: blur(2px);
}

.member-sidebar-overlay-v1-2.active {
    display: block;
    opacity: 1;
}

/* Mobile Bottom Navigation - Consistent Design */
.member-bottom-nav-v1-2 {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    background: #ffffff;
    border-top: 1px solid #e2e8f0;
    display: flex;
    justify-content: space-around;
    align-items: center;
    padding: 8px 0;
    z-index: 9998;
    box-shadow: 0 -2px 8px rgba(0, 0, 0, 0.1);
}

.member-bottom-nav-item-v1-2 {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    color: #64748b;
    font-size: 0.6875rem;
    padding: 4px 8px;
    transition: all 0.2s ease;
    min-width: 56px;
    position: relative;
}

.bottom-nav-icon-wrapper {
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    background: transparent;
    transition: all 0.2s ease;
    margin-bottom: 4px;
}

.member-bottom-nav-item-v1-2 i {
    font-size: 18px;
    color: #64748b;
    transition: all 0.2s ease;
}

.member-bottom-nav-item-v1-2.active {
    color: #0284c7;
}

.member-bottom-nav-item-v1-2.active .bottom-nav-icon-wrapper {
    background: rgba(14, 165, 233, 0.1);
}

.member-bottom-nav-item-v1-2.active i {
    color: #0284c7;
}

.member-bottom-nav-avatar-v1-2 {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid #0284c7;
}

/* Responsive */
@media (max-width: 767px) {
    .member-navbar-container-v1-2 {
        padding: 0 12px;
        height: 60px;
    }
    
    .member-logo-img-v1-2 {
        height: 42px;
    }
    
    .member-navbar-logo-v1-2 {
        margin-left: 8px;
    }
    
    .member-sidebar-v1-2 {
        width: 300px;
    }
    
    body {
        padding-bottom: 64px;
    }
}

@media (max-width: 480px) {
    .member-sidebar-v1-2 {
        width: 280px;
        max-width: 90vw;
    }
    
    .member-navbar-container-v1-2 {
        height: 60px;
    }
}

/* Scrollbar Styling */
.member-sidebar-v1-2::-webkit-scrollbar {
    width: 6px;
}

.member-sidebar-v1-2::-webkit-scrollbar-track {
    background: rgba(255, 255, 255, 0.05);
}

.member-sidebar-v1-2::-webkit-scrollbar-thumb {
    background: rgba(102, 126, 234, 0.5);
    border-radius: 3px;
}

.member-sidebar-v1-2::-webkit-scrollbar-thumb:hover {
    background: rgba(102, 126, 234, 0.7);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const openBtn = document.getElementById('openMemberSidebar');
    const closeBtn = document.getElementById('closeMemberSidebar');
    const sidebar = document.getElementById('memberSidebar');
    const overlay = document.getElementById('memberSidebarOverlay');
    const hamburger = openBtn;
    const profileBtn = document.getElementById('memberProfileDropdownBtn');
    const profileMenu = document.getElementById('memberProfileDropdownMenu');
    const profileDropdown = profileBtn ? profileBtn.closest('.member-profile-dropdown-v1-2') : null;
    
    function openSidebar() {
        sidebar.classList.add('active');
        overlay.classList.add('active');
        if (hamburger) hamburger.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
    
    function closeSidebar() {
        sidebar.classList.remove('active');
        overlay.classList.remove('active');
        if (hamburger) hamburger.classList.remove('active');
        document.body.style.overflow = '';
    }
    
    if (openBtn) {
        openBtn.addEventListener('click', openSidebar);
    }
    
    if (closeBtn) {
        closeBtn.addEventListener('click', closeSidebar);
    }
    
    if (overlay) {
        overlay.addEventListener('click', closeSidebar);
    }
    
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && sidebar.classList.contains('active')) {
            closeSidebar();
        }
    });
    
    // Profile Dropdown Toggle
    if (profileBtn && profileDropdown) {
        profileBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            profileDropdown.classList.toggle('active');
        });
        
        document.addEventListener('click', function(e) {
            if (profileDropdown && !profileDropdown.contains(e.target) && !profileBtn.contains(e.target)) {
                profileDropdown.classList.remove('active');
            }
        });
    }
    
    // Profile trigger on mobile bottom nav
    const profileTrigger = document.querySelector('.profile-trigger');
    if (profileTrigger) {
        profileTrigger.addEventListener('click', function(e) {
            e.preventDefault();
            if (openBtn) {
                openBtn.click();
            }
        });
    }
});
</script>

