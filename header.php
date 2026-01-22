<?php
// Set the current page
$current_page = basename($_SERVER['PHP_SELF']); // Get the current script name

// Define the pages
$pages = [
    'index.php' => 'Home',
    'about.php' => 'About us',
    'membership.php' => 'Membership',
    'events.php' => 'Events',
    'news.php' => 'News & Media',
    'resources.php' => 'Resources',
    'contact.php' => 'Contact',
];

// Check if member is logged in
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$is_member_logged_in = isset($_SESSION['member_id']);

// Get member info if logged in
$member_info = null;
if ($is_member_logged_in) {
    // Check if config is already included to avoid duplicate includes
    if (!isset($conn)) {
        include 'include/config.php';
    }
    $member_id = $_SESSION['member_id'];
    $member_query = "SELECT fullname, membership_id, photo FROM registrations WHERE id = ? LIMIT 1";
    $member_stmt = $conn->prepare($member_query);
    $member_stmt->bind_param("i", $member_id);
    $member_stmt->execute();
    $member_result = $member_stmt->get_result();
    $member_info = $member_result->fetch_assoc();
    $member_stmt->close();
    // Don't close connection - other pages may need it
}
?>
<header class="public-header-wrapper">
    <!-- Mobile Side-Slide Menu -->
    <div id="public-sidebar" class="public-sidebar">
        <div class="public-sidebar-header">
            <div class="public-sidebar-logo">
                <img src="assets/img/content/logo-light.png" alt="ESWPA Logo" style="height: 40px;">
            </div>
            <button class="public-sidebar-close" id="closePublicSidebar">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="public-sidebar-menu">
            <?php if ($is_member_logged_in && $member_info): ?>
                <div class="public-sidebar-user-section">
                    <?php if (!empty($member_info['photo'])): ?>
                        <img src="<?php echo htmlspecialchars($member_info['photo']); ?>" 
                             alt="Member Photo" 
                             class="public-sidebar-avatar">
                    <?php else: ?>
                        <div class="public-sidebar-avatar-placeholder">
                            <i class="fas fa-user"></i>
                        </div>
                    <?php endif; ?>
                    <div class="public-sidebar-user-info">
                        <strong><?php echo htmlspecialchars($member_info['fullname']); ?></strong>
                        <small><?php echo htmlspecialchars($member_info['membership_id'] ?? 'N/A'); ?></small>
                    </div>
                </div>
                <div class="public-sidebar-divider"></div>
                <a href="member-dashboard.php" class="public-sidebar-item member-dashboard-link">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Member Dashboard</span>
                </a>
            <?php endif; ?>
            
            <div class="public-sidebar-section">
                <div class="public-sidebar-section-title">NAVIGATION</div>
                <?php foreach ($pages as $page => $title): ?>
                    <a href="<?php echo $page; ?>" class="public-sidebar-item <?php echo $current_page === $page ? 'active' : ''; ?>">
                        <i class="fas fa-<?php 
                            echo $page == 'index.php' ? 'home' : 
                                ($page == 'about.php' ? 'info-circle' : 
                                ($page == 'membership.php' ? 'user-plus' : 
                                ($page == 'events.php' ? 'calendar' : 
                                ($page == 'news.php' ? 'newspaper' : 
                                ($page == 'resources.php' ? 'download' : 'envelope'))))); 
                        ?>"></i>
                        <span><?php echo $title; ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
            
            <div class="public-sidebar-section">
                <div class="public-sidebar-section-title">ACCOUNT</div>
                <?php if ($is_member_logged_in): ?>
                    <a href="member-dashboard.php" class="public-sidebar-item">
                        <i class="fas fa-user-circle"></i>
                        <span>Dashboard</span>
                    </a>
                    <a href="member-generate-id-card.php" class="public-sidebar-item">
                        <i class="fas fa-id-card"></i>
                        <span>My ID Card</span>
                    </a>
                    <a href="member-logout.php" class="public-sidebar-item logout">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </a>
                <?php else: ?>
                    <a href="member-login.php" class="public-sidebar-item">
                        <i class="fas fa-sign-in-alt"></i>
                        <span>Member Login</span>
                    </a>
                    <a href="sign-up.php" class="public-sidebar-item">
                        <i class="fas fa-user-plus"></i>
                        <span>Register</span>
                    </a>
                <?php endif; ?>
            </div>
            
            <div class="public-sidebar-footer">
                <div class="public-sidebar-version">
                    <i class="fas fa-code"></i>
                    <span>Version 1.2</span>
                </div>
                <div class="public-sidebar-developer">
                    <i class="fas fa-users"></i>
                    <span>Developed by Lebawi Net Trading PLC</span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Sidebar Overlay -->
    <div class="public-sidebar-overlay" id="publicSidebarOverlay"></div>

    <!-- Main Navigation Bar -->
    <nav class="navbar navbar-default navbar-fixed navbar-transparent white bootsnav public-main-nav">
        <div class="container-full">
            <!-- Mobile Hamburger Button -->
            <button type="button" id="openPublicSidebar" class="public-hamburger-btn d-md-none">
                <i class="fas fa-bars"></i>
            </button>

            <!-- Logo -->
            <div class="navbar-header">
                <a class="navbar-brand" href="index.php">
                    <img src="assets/img/content/logo-light.png" class="logo logo-display" alt="Logo" style="width: 100%; height: 60px;">
                    <img src="assets/img/content/logo-dark.png" class="logo logo-scrolled" alt="Logo" style="width: 100%; height: 60px;">
                </a>
            </div>

            <!-- Desktop Navigation -->
            <div class="collapse navbar-collapse">
                <ul class="nav navbar-nav navbar-right" data-in="fadeInDown" data-out="fadeOutUp">
                    <?php foreach ($pages as $page => $title): ?>
                        <li>
                            <a href="<?php echo $page; ?>" class="<?php echo $current_page === $page ? 'active' : ''; ?>">
                                <?php echo $title; ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- Desktop Member Actions -->
            <div class="attr-nav d-none d-md-block">
                <ul>
                    <?php if ($is_member_logged_in): ?>
                        <li class="button">
                            <a href="member-dashboard.php" style="margin-right: 10px; padding: 8px 15px; background: rgba(255,255,255,0.2); border-radius: 5px;">
                                <i class="fas fa-tachometer-alt"></i> <span style="margin-left: 5px;">Dashboard</span>
                            </a>
                        </li>
                        <li class="button">
                            <a href="member-logout.php" style="padding: 8px 15px; background: rgba(255,255,255,0.2); border-radius: 5px;">
                                <i class="fas fa-sign-out-alt"></i> <span style="margin-left: 5px;">Logout</span>
                            </a>
                        </li>
                    <?php else: ?>
                        <li class="button">
                            <a href="member-login.php" style="margin-right: 10px; padding: 8px 15px; background: rgba(255,255,255,0.2); border-radius: 5px;">
                                <i class="fas fa-sign-in-alt"></i> <span style="margin-left: 5px;">Member Login</span>
                            </a>
                        </li>
                        <li class="button">
                            <a href="sign-up.php" style="padding: 8px 15px; background: rgba(255,255,255,0.2); border-radius: 5px;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="#ffffff" viewBox="0 0 256 256" style="vertical-align: middle; margin-right: 5px;">
                                    <path d="M168,56a8,8,0,0,1,8-8h16V32a8,8,0,0,1,16,0V48h16a8,8,0,0,1,0,16H208V80a8,8,0,0,1-16,0V64H176A8,8,0,0,1,168,56Zm62.56,54.68a103.92,103.92,0,1,1-85.24-85.24,8,8,0,0,1-2.64,15.78A88.07,88.07,0,0,0,40,128a87.62,87.62,0,0,0,22.24,58.41A79.66,79.66,0,0,1,98.3,157.66a48,48,0,1,1,59.4,0,79.66,79.66,0,0,1,36.06,28.75A87.62,87.62,0,0,0,216,128a88.85,88.85,0,0,0-1.22-14.68,8,8,0,1,1,15.78-2.64ZM128,152a32,32,0,1,0-32-32A32,32,0,0,0,128,152Zm0,64a87.57,87.57,0,0,0,53.92-18.5,64,64,0,0,0-107.84,0A87.57,87.57,0,0,0,128,216Z"></path>
                                </svg>
                                <span style="vertical-align: middle;">Register</span>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
</header>

<style>
/* Public Sidebar Styles */
.public-sidebar {
    position: fixed;
    top: 0;
    right: -100%;
    width: 300px;
    height: 100vh;
    background: #1a1a2e;
    z-index: 10000;
    transition: right 0.3s ease;
    overflow-y: auto;
    box-shadow: -2px 0 10px rgba(0,0,0,0.3);
}

.public-sidebar.active {
    right: 0;
}

.public-sidebar-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.public-sidebar-logo {
    display: flex;
    align-items: center;
}

.public-sidebar-close {
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

.public-sidebar-close:hover {
    background: rgba(255,255,255,0.3);
}

.public-sidebar-user-section {
    padding: 20px;
    background: rgba(102, 126, 234, 0.1);
    display: flex;
    align-items: center;
    gap: 12px;
}

.public-sidebar-avatar {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    border: 3px solid rgba(255,255,255,0.3);
    object-fit: cover;
}

.public-sidebar-avatar-placeholder {
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

.public-sidebar-user-info {
    color: white;
    flex: 1;
}

.public-sidebar-user-info strong {
    display: block;
    font-size: 14px;
    margin-bottom: 4px;
}

.public-sidebar-user-info small {
    display: block;
    font-size: 11px;
    opacity: 0.9;
}

.public-sidebar-menu {
    padding: 20px 0;
}

.public-sidebar-section {
    margin-bottom: 20px;
}

.public-sidebar-section-title {
    color: #888;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 1px;
    padding: 0 20px;
    margin-bottom: 10px;
}

.public-sidebar-item {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 12px 20px;
    color: #e0e0e0;
    text-decoration: none;
    transition: all 0.3s;
    border-left: 3px solid transparent;
}

.public-sidebar-item:hover,
.public-sidebar-item.active {
    background: rgba(102, 126, 234, 0.1);
    color: #667eea;
    border-left-color: #667eea;
}

.public-sidebar-item.member-dashboard-link {
    background: rgba(102, 126, 234, 0.15);
    color: #667eea;
    border-left-color: #667eea;
    font-weight: 600;
}

.public-sidebar-item i {
    width: 20px;
    text-align: center;
    font-size: 18px;
}

.public-sidebar-item.logout {
    color: #ff6b6b;
}

.public-sidebar-item.logout:hover {
    background: rgba(255, 107, 107, 0.1);
    border-left-color: #ff6b6b;
}

.public-sidebar-divider {
    height: 1px;
    background: #333;
    margin: 15px 20px;
}

.public-sidebar-footer {
    margin-top: auto;
    padding: 20px;
    border-top: 1px solid #333;
    margin-top: 20px;
}

.public-sidebar-version,
.public-sidebar-developer {
    display: flex;
    align-items: center;
    gap: 10px;
    color: #888;
    font-size: 11px;
    margin-bottom: 8px;
}

.public-sidebar-version i,
.public-sidebar-developer i {
    width: 16px;
    text-align: center;
}

.public-sidebar-overlay {
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

.public-sidebar-overlay.active {
    display: block;
    opacity: 1;
}

/* Hamburger Button */
.public-hamburger-btn {
    position: absolute;
    left: 15px;
    top: 50%;
    transform: translateY(-50%);
    background: rgba(255,255,255,0.2);
    border: none;
    color: white;
    width: 40px;
    height: 40px;
    border-radius: 5px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    cursor: pointer;
    transition: background 0.3s;
    z-index: 1000;
}

.public-hamburger-btn:hover {
    background: rgba(255,255,255,0.3);
}

/* Public Header Wrapper */
.public-header-wrapper {
    position: relative;
    z-index: 1000;
}

/* Adjust navbar header for mobile */
@media (max-width: 767px) {
    .public-main-nav .navbar-header {
        margin-left: 60px;
    }
    
    .public-main-nav .attr-nav {
        display: none;
    }
}
</style>

<script>
// Public Sidebar Toggle
document.addEventListener('DOMContentLoaded', function() {
    const openBtn = document.getElementById('openPublicSidebar');
    const closeBtn = document.getElementById('closePublicSidebar');
    const sidebar = document.getElementById('public-sidebar');
    const overlay = document.getElementById('publicSidebarOverlay');
    
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
});
</script>
