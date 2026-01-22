<?php
// Dedicated header for member pages - Optimized for mobile
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
// Don't close connection here - other pages may need it
// $conn->close();

$current_page = basename($_SERVER['PHP_SELF']);
?>
<header class="member-header-wrapper">
    <!-- Mobile Hamburger Menu Overlay -->
    <div id="member-sidebar" class="member-sidebar">
        <div class="member-sidebar-header">
            <div class="member-sidebar-user">
                <?php if (!empty($member_info['photo'])): ?>
                    <img src="<?php echo htmlspecialchars($member_info['photo']); ?>" 
                         alt="Member Photo" 
                         class="member-sidebar-avatar">
                <?php else: ?>
                    <div class="member-sidebar-avatar-placeholder">
                        <i class="fas fa-user"></i>
                    </div>
                <?php endif; ?>
                <div class="member-sidebar-user-info">
                    <strong><?php echo htmlspecialchars($member_info['fullname']); ?></strong>
                    <small><?php echo htmlspecialchars($member_info['membership_id'] ?? 'N/A'); ?></small>
                </div>
            </div>
            <button class="member-sidebar-close" id="closeSidebar">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="member-sidebar-menu">
            <div class="member-sidebar-section">
                <div class="member-sidebar-section-title">MAIN</div>
                <a href="member-dashboard.php" class="member-sidebar-item <?php echo $current_page == 'member-dashboard.php' ? 'active' : ''; ?>">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
                <a href="member-generate-id-card.php" class="member-sidebar-item <?php echo in_array($current_page, ['member-generate-id-card.php', 'member-id-card.php']) ? 'active' : ''; ?>">
                    <i class="fas fa-id-card"></i>
                    <span>My ID Card</span>
                </a>
                <a href="resources.php" class="member-sidebar-item <?php echo $current_page == 'resources.php' ? 'active' : ''; ?>">
                    <i class="fas fa-download"></i>
                    <span>Resources</span>
                </a>
                <a href="news.php" class="member-sidebar-item <?php echo $current_page == 'news.php' ? 'active' : ''; ?>">
                    <i class="fas fa-newspaper"></i>
                    <span>News & Media</span>
                </a>
            </div>
            
            <div class="member-sidebar-section">
                <div class="member-sidebar-section-title">EXPLORE</div>
                <a href="index.php" class="member-sidebar-item">
                    <i class="fas fa-globe"></i>
                    <span>Public Site</span>
                </a>
                <a href="events.php" class="member-sidebar-item">
                    <i class="fas fa-calendar"></i>
                    <span>Events</span>
                </a>
                <a href="about.php" class="member-sidebar-item">
                    <i class="fas fa-info-circle"></i>
                    <span>About Us</span>
                </a>
            </div>
            
            <div class="member-sidebar-footer">
                <div class="member-sidebar-version">
                    <i class="fas fa-code"></i>
                    <span>Version 1.2</span>
                </div>
                <div class="member-sidebar-developer">
                    <i class="fas fa-users"></i>
                    <span>Developed by Lebawi Net Trading PLC</span>
                </div>
            </div>
            
            <div class="member-sidebar-logout">
                <a href="member-logout.php" class="member-sidebar-item logout">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </div>
    </div>
    
    <!-- Sidebar Overlay -->
    <div class="member-sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Main Navigation Bar -->
    <nav class="member-main-nav">
        <div class="member-nav-container">
            <!-- Mobile Hamburger Button -->
            <button type="button" id="openSidebar" class="member-hamburger-btn d-md-none">
                <i class="fas fa-bars"></i>
            </button>

            <!-- Logo -->
            <div class="member-nav-logo">
                <a href="member-dashboard.php">
                    <img src="assets/img/content/logo-light.png" class="member-logo-img" alt="ESWPA Logo">
                </a>
            </div>

            <!-- Desktop Navigation Menu -->
            <div class="member-nav-menu d-none d-md-flex">
                <a href="member-dashboard.php" class="member-nav-link <?php echo $current_page == 'member-dashboard.php' ? 'active' : ''; ?>">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
                <a href="member-generate-id-card.php" class="member-nav-link <?php echo in_array($current_page, ['member-generate-id-card.php', 'member-id-card.php']) ? 'active' : ''; ?>">
                    <i class="fas fa-id-card"></i>
                    <span>My ID Card</span>
                </a>
                <a href="resources.php" class="member-nav-link <?php echo $current_page == 'resources.php' ? 'active' : ''; ?>">
                    <i class="fas fa-download"></i>
                    <span>Resources</span>
                </a>
                <a href="news.php" class="member-nav-link <?php echo $current_page == 'news.php' ? 'active' : ''; ?>">
                    <i class="fas fa-newspaper"></i>
                    <span>News & Media</span>
                </a>
            </div>

            <!-- Profile Dropdown -->
            <div class="member-nav-profile">
                <div class="member-profile-dropdown">
                    <button class="member-profile-btn" type="button" id="profileDropdownBtn">
                        <?php if (!empty($member_info['photo'])): ?>
                            <img src="<?php echo htmlspecialchars($member_info['photo']); ?>" 
                                 alt="Member Photo" 
                                 class="member-profile-avatar">
                        <?php else: ?>
                            <div class="member-profile-icon">
                                <i class="fas fa-user-circle"></i>
                            </div>
                        <?php endif; ?>
                        <span class="member-profile-name d-none d-lg-inline"><?php echo htmlspecialchars($member_info['fullname']); ?></span>
                        <i class="fas fa-chevron-down member-profile-arrow"></i>
                    </button>
                    <div class="member-profile-menu" id="profileDropdownMenu">
                        <div class="member-profile-menu-header">
                            <strong><?php echo htmlspecialchars($member_info['fullname']); ?></strong>
                            <small><?php echo htmlspecialchars($member_info['membership_id'] ?? 'N/A'); ?></small>
                        </div>
                        <div class="member-profile-menu-divider"></div>
                        <a href="member-dashboard.php" class="member-profile-menu-item">
                            <i class="fas fa-tachometer-alt"></i>
                            <span>Dashboard</span>
                        </a>
                        <a href="member-generate-id-card.php" class="member-profile-menu-item">
                            <i class="fas fa-id-card"></i>
                            <span>My ID Card</span>
                        </a>
                        <a href="resources.php" class="member-profile-menu-item">
                            <i class="fas fa-download"></i>
                            <span>Resources</span>
                        </a>
                        <a href="news.php" class="member-profile-menu-item">
                            <i class="fas fa-newspaper"></i>
                            <span>News & Media</span>
                        </a>
                        <div class="member-profile-menu-divider"></div>
                        <a href="index.php" class="member-profile-menu-item">
                            <i class="fas fa-globe"></i>
                            <span>Public Site</span>
                        </a>
                        <div class="member-profile-menu-divider"></div>
                        <a href="member-logout.php" class="member-profile-menu-item logout">
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
<nav class="member-bottom-nav d-md-none">
    <a href="member-dashboard.php" class="member-bottom-nav-item <?php echo $current_page == 'member-dashboard.php' ? 'active' : ''; ?>">
        <i class="fas fa-home"></i>
        <span>Dashboard</span>
    </a>
    <a href="resources.php" class="member-bottom-nav-item <?php echo $current_page == 'resources.php' ? 'active' : ''; ?>">
        <i class="fas fa-download"></i>
        <span>Resources</span>
    </a>
    <a href="news.php" class="member-bottom-nav-item <?php echo $current_page == 'news.php' ? 'active' : ''; ?>">
        <i class="fas fa-newspaper"></i>
        <span>News</span>
    </a>
    <a href="member-dashboard.php" class="member-bottom-nav-item profile-trigger">
        <?php if (!empty($member_info['photo'])): ?>
            <img src="<?php echo htmlspecialchars($member_info['photo']); ?>" 
                 alt="Profile" 
                 class="member-bottom-nav-avatar">
        <?php else: ?>
            <i class="fas fa-user"></i>
        <?php endif; ?>
        <span>Profile</span>
    </a>
</nav>

<style>
/* Member Header Wrapper */
.member-header-wrapper {
    position: relative;
    z-index: 1000;
}

/* Main Navigation Bar */
.member-main-nav {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    position: sticky;
    top: 0;
    z-index: 999;
    padding: 0;
    border-bottom: 1px solid rgba(0, 0, 0, 0.05);
}

.member-nav-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    height: 70px;
}

/* Logo */
.member-nav-logo {
    flex-shrink: 0;
}

.member-logo-img {
    height: 50px;
    width: auto;
    object-fit: contain;
}

/* Hamburger Button (Mobile) */
.member-hamburger-btn {
    background: #667eea;
    border: none;
    color: white;
    width: 45px;
    height: 45px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    cursor: pointer;
    transition: all 0.3s;
    margin-right: 15px;
}

.member-hamburger-btn:hover {
    background: #5568d3;
    transform: scale(1.05);
}

/* Desktop Navigation Menu */
.member-nav-menu {
    display: flex;
    align-items: center;
    gap: 5px;
    flex: 1;
    justify-content: center;
    margin: 0 20px;
}

.member-nav-link {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 18px;
    color: #333;
    text-decoration: none;
    border-radius: 8px;
    transition: all 0.3s;
    font-weight: 500;
    font-size: 14px;
}

.member-nav-link i {
    font-size: 16px;
}

.member-nav-link:hover {
    background: #f0f0f0;
    color: #667eea;
    text-decoration: none;
}

.member-nav-link.active {
    background: #667eea;
    color: white;
}

.member-nav-link.active:hover {
    background: #5568d3;
    color: white;
}

/* Profile Dropdown */
.member-nav-profile {
    flex-shrink: 0;
}

.member-profile-dropdown {
    position: relative;
}

.member-profile-btn {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 15px;
    background: #f8f9fa;
    border: 1px solid #e0e0e0;
    border-radius: 25px;
    cursor: pointer;
    transition: all 0.3s;
    text-decoration: none;
    color: #333;
}

.member-profile-btn:hover {
    background: #e9ecef;
    border-color: #667eea;
    text-decoration: none;
    color: #333;
}

.member-profile-avatar {
    width: 35px;
    height: 35px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid #667eea;
}

.member-profile-icon {
    width: 35px;
    height: 35px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #667eea;
    font-size: 24px;
}

.member-profile-name {
    font-weight: 500;
    font-size: 14px;
    max-width: 150px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.member-profile-arrow {
    font-size: 12px;
    color: #666;
    transition: transform 0.3s;
}

.member-profile-dropdown.active .member-profile-arrow {
    transform: rotate(180deg);
}

/* Profile Dropdown Menu */
.member-profile-menu {
    position: absolute;
    top: calc(100% + 10px);
    right: 0;
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
    min-width: 250px;
    opacity: 0;
    visibility: hidden;
    transform: translateY(-10px);
    transition: all 0.3s;
    z-index: 1000;
    overflow: hidden;
}

.member-profile-dropdown.active .member-profile-menu {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

.member-profile-menu-header {
    padding: 15px 20px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.member-profile-menu-header strong {
    display: block;
    font-size: 15px;
    margin-bottom: 5px;
}

.member-profile-menu-header small {
    display: block;
    font-size: 12px;
    opacity: 0.9;
}

.member-profile-menu-divider {
    height: 1px;
    background: #e0e0e0;
    margin: 5px 0;
}

.member-profile-menu-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 20px;
    color: #333;
    text-decoration: none;
    transition: all 0.3s;
}

.member-profile-menu-item i {
    width: 20px;
    text-align: center;
    color: #667eea;
}

.member-profile-menu-item:hover {
    background: #f8f9fa;
    color: #667eea;
    text-decoration: none;
}

.member-profile-menu-item.logout {
    color: #dc3545;
}

.member-profile-menu-item.logout i {
    color: #dc3545;
}

.member-profile-menu-item.logout:hover {
    background: #fff5f5;
    color: #dc3545;
}

/* Member Sidebar Styles */
.member-sidebar {
    position: fixed;
    top: 0;
    right: -100%;
    width: 280px;
    height: 100vh;
    background: #1a1a2e;
    z-index: 10000;
    transition: right 0.3s ease;
    overflow-y: auto;
    box-shadow: -2px 0 10px rgba(0,0,0,0.3);
}

.member-sidebar.active {
    right: 0;
}

.member-sidebar-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.member-sidebar-user {
    display: flex;
    align-items: center;
    gap: 12px;
}

.member-sidebar-avatar {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    border: 3px solid rgba(255,255,255,0.3);
    object-fit: cover;
}

.member-sidebar-avatar-placeholder {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: rgba(255,255,255,0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 24px;
}

.member-sidebar-user-info {
    color: white;
}

.member-sidebar-user-info strong {
    display: block;
    font-size: 14px;
    margin-bottom: 4px;
}

.member-sidebar-user-info small {
    display: block;
    font-size: 11px;
    opacity: 0.9;
}

.member-sidebar-close {
    background: rgba(255,255,255,0.2);
    border: none;
    color: white;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background 0.3s;
}

.member-sidebar-close:hover {
    background: rgba(255,255,255,0.3);
}

.member-sidebar-menu {
    padding: 20px 0;
}

.member-sidebar-section {
    margin-bottom: 20px;
}

.member-sidebar-section-title {
    color: #888;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 1px;
    padding: 0 20px;
    margin-bottom: 10px;
}

.member-sidebar-item {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 12px 20px;
    color: #e0e0e0;
    text-decoration: none;
    transition: all 0.3s;
    border-left: 3px solid transparent;
}

.member-sidebar-item:hover,
.member-sidebar-item.active {
    background: rgba(102, 126, 234, 0.1);
    color: #667eea;
    border-left-color: #667eea;
}

.member-sidebar-item i {
    width: 20px;
    text-align: center;
    font-size: 18px;
}

.member-sidebar-item.logout {
    color: #ff6b6b;
}

.member-sidebar-item.logout:hover {
    background: rgba(255, 107, 107, 0.1);
    border-left-color: #ff6b6b;
}

.member-sidebar-footer {
    margin-top: auto;
    padding: 20px;
    border-top: 1px solid #333;
    margin-top: 20px;
}

.member-sidebar-version,
.member-sidebar-developer {
    display: flex;
    align-items: center;
    gap: 10px;
    color: #888;
    font-size: 11px;
    margin-bottom: 8px;
}

.member-sidebar-version i,
.member-sidebar-developer i {
    width: 16px;
    text-align: center;
}

.member-sidebar-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 9999;
    display: none;
    opacity: 0;
    transition: opacity 0.3s;
}

.member-sidebar-overlay.active {
    display: block;
    opacity: 1;
}

/* Mobile Bottom Navigation */
.member-bottom-nav {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    background: #fff;
    border-top: 1px solid #e0e0e0;
    display: flex;
    justify-content: space-around;
    align-items: center;
    padding: 8px 0;
    z-index: 9998;
    box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
}

.member-bottom-nav-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    color: #666;
    font-size: 11px;
    padding: 5px 10px;
    transition: color 0.3s;
    min-width: 60px;
}

.member-bottom-nav-item i {
    font-size: 20px;
    margin-bottom: 4px;
}

.member-bottom-nav-item.active {
    color: #667eea;
}

.member-bottom-nav-item.active i {
    color: #667eea;
}

.member-bottom-nav-avatar {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    object-fit: cover;
    margin-bottom: 4px;
    border: 2px solid #667eea;
}

/* Responsive Adjustments */
@media (max-width: 767px) {
    .member-nav-container {
        padding: 0 15px;
        height: 60px;
    }
    
    .member-logo-img {
        height: 40px;
    }
    
    body {
        padding-bottom: 60px;
    }
}

/* Page Content Spacing */
.member-content-wrapper {
    padding-top: 20px;
}

@media (min-width: 768px) {
    .member-content-wrapper {
        padding-top: 30px;
    }
}
</style>

<script>
// Sidebar Toggle
document.addEventListener('DOMContentLoaded', function() {
    const openBtn = document.getElementById('openSidebar');
    const closeBtn = document.getElementById('closeSidebar');
    const sidebar = document.getElementById('member-sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const profileBtn = document.getElementById('profileDropdownBtn');
    const profileMenu = document.getElementById('profileDropdownMenu');
    const profileDropdown = profileBtn ? profileBtn.closest('.member-profile-dropdown') : null;
    
    // Sidebar functions
    if (openBtn) {
        openBtn.addEventListener('click', function() {
            sidebar.classList.add('active');
            overlay.classList.add('active');
            document.body.style.overflow = 'hidden';
        });
    }
    
    function closeSidebar() {
        sidebar.classList.remove('active');
        overlay.classList.remove('active');
        document.body.style.overflow = '';
    }
    
    if (closeBtn) {
        closeBtn.addEventListener('click', closeSidebar);
    }
    
    if (overlay) {
        overlay.addEventListener('click', closeSidebar);
    }
    
    // Close on escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && sidebar.classList.contains('active')) {
            closeSidebar();
        }
    });
    
    // Profile Dropdown Toggle
    if (profileBtn && profileDropdown) {
        profileBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            profileDropdown.classList.toggle('active');
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!profileDropdown.contains(e.target)) {
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
