<?php
// Version 1.2 - Modern Futuristic Public Header
// Set the current page
$current_page = basename($_SERVER['PHP_SELF']);

// Define the pages
$pages = [
    'index.php' => ['title' => 'Home', 'icon' => 'fa-home'],
    'about.php' => ['title' => 'About Us', 'icon' => 'fa-info-circle'],
    'membership.php' => ['title' => 'Membership', 'icon' => 'fa-user-plus'],
    'events.php' => ['title' => 'Events', 'icon' => 'fa-calendar'],
    'news.php' => ['title' => 'News', 'icon' => 'fa-newspaper'],
    'resources.php' => ['title' => 'Resources', 'icon' => 'fa-download'],
    'contact.php' => ['title' => 'Contact', 'icon' => 'fa-envelope'],
];

// Check if member is logged in
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$is_member_logged_in = isset($_SESSION['member_id']);

// Get member info if logged in
$member_info = null;
if ($is_member_logged_in) {
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
}
?>
<header class="header-v1-2">
    <!-- Mobile Side-Slide Menu -->
    <div id="publicSidebar" class="sidebar-v1-2">
        <div class="sidebar-header-v1-2">
            <div class="sidebar-logo-v1-2">
                <img src="assets/img/content/logo-light.png" alt="ESWPA" class="sidebar-logo-img">
                
            </div>
            <button class="sidebar-close-v1-2" id="closePublicSidebar" aria-label="Close menu">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <?php if ($is_member_logged_in && $member_info): ?>
        <div class="sidebar-user-v1-2">
            <?php if (!empty($member_info['photo'])): ?>
                <img src="<?php echo htmlspecialchars($member_info['photo']); ?>" alt="Profile" class="sidebar-avatar-v1-2">
            <?php else: ?>
                <div class="sidebar-avatar-v1-2 placeholder">
                    <i class="fas fa-user"></i>
                </div>
            <?php endif; ?>
            <div class="sidebar-user-info-v1-2">
                <strong><?php echo htmlspecialchars($member_info['fullname']); ?></strong>
                <span><?php echo htmlspecialchars($member_info['membership_id'] ?? 'N/A'); ?></span>
            </div>
            <a href="member-dashboard.php" class="sidebar-dashboard-btn">
                <i class="fas fa-tachometer-alt"></i>
                <span></span>
            </a>
        </div>
        <?php endif; ?>
        
        <nav class="sidebar-nav-v1-2">
            <div class="sidebar-section-v1-2">
                <div class="sidebar-section-title">Navigation</div>
                <?php foreach ($pages as $page => $pageData): ?>
                    <a href="<?php echo $page; ?>" class="sidebar-item-v1-2 <?php echo $current_page === $page ? 'active' : ''; ?>">
                        <i class="fas <?php echo $pageData['icon']; ?>"></i>
                        <span><?php echo $pageData['title']; ?></span>
                        <?php if ($current_page === $page): ?>
                            <i class="fas fa-chevron-right active-indicator"></i>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </div>
            
            <div class="sidebar-section-v1-2">
                <div class="sidebar-section-title">Account</div>
                <?php if ($is_member_logged_in): ?>
                    <a href="member-dashboard.php" class="sidebar-item-v1-2">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Member Dashboard</span>
                    </a>
                    <a href="member-generate-id-card.php" class="sidebar-item-v1-2">
                        <i class="fas fa-id-card"></i>
                        <span>My ID Card</span>
                    </a>
                    <a href="member-logout.php" class="sidebar-item-v1-2 logout">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </a>
                <?php else: ?>
                    <a href="member-login.php" class="sidebar-item-v1-2">
                        <i class="fas fa-sign-in-alt"></i>
                        <span>Member Login</span>
                    </a>
                    <a href="sign-up.php" class="sidebar-item-v1-2">
                        <i class="fas fa-user-plus"></i>
                        <span>Register</span>
                    </a>
                <?php endif; ?>
            </div>
        </nav>
        
        <div class="sidebar-footer-v1-2">
            <div class="sidebar-footer-item">
                <i class="fas fa-code"></i>
                <span>Version 1.2</span>
            </div>
            <div class="sidebar-footer-item">
                <i class="fas fa-users"></i>
                <span>Lebawi Net Trading PLC</span>
            </div>
        </div>
    </div>
    
    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay-v1-2" id="publicSidebarOverlay"></div>

    <!-- Main Navigation Bar -->
    <nav class="navbar-v1-2">
        <div class="navbar-container-v1-2">
            <!-- Mobile Hamburger -->
            <button class="hamburger-v1-2 d-md-none" id="openPublicSidebar" aria-label="Open menu">
                <span></span>
                <span></span>
                <span></span>
            </button>

            <!-- Logo -->
            <a href="index.php" class="navbar-logo-v1-2">
                <img src="assets/img/content/logo-light.png" alt="ESWPA" class="logo-img-v1-2">
            </a>

            <!-- Desktop Navigation -->
            <div class="navbar-menu-v1-2 d-none d-md-flex">
                <?php foreach ($pages as $page => $pageData): ?>
                    <a href="<?php echo $page; ?>" class="navbar-link-v1-2 <?php echo $current_page === $page ? 'active' : ''; ?>">
                        <span><?php echo $pageData['title']; ?></span>
                    </a>
                <?php endforeach; ?>
            </div>

            <!-- Desktop Actions -->
            <div class="navbar-actions-v1-2 d-none d-md-flex">
                <!-- Telegram Chat Button -->
                <button class="telegram-header-btn" id="telegramHeaderChatButton" aria-label="Open chat" title="Chat with Us">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.568 8.16l-1.44 6.8c-.108.48-.384.6-.78.375l-2.15-1.584-1.036.996c-.12.12-.22.22-.45.22l.16-2.274 3.984-3.6c.174-.156-.038-.24-.27-.084l-4.92 3.096-2.124-.66c-.462-.144-.474-.462.096-.684l8.316-3.204c.384-.144.72.096.6.684z" fill="currentColor"/>
                    </svg>
                    <span>Chat</span>
                </button>
                <?php if ($is_member_logged_in): ?>
                    <a href="member-dashboard.php" class="action-btn-v1-2 dashboard-btn">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                    <a href="member-logout.php" class="action-btn-v1-2 logout-btn">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </a>
                <?php else: ?>
                    <a href="member-login.php" class="action-btn-v1-2 login-btn">
                        <i class="fas fa-sign-in-alt"></i>
                        <span>Login</span>
                    </a>
                    <a href="sign-up.php" class="action-btn-v1-2 register-btn">
                        <i class="fas fa-user-plus"></i>
                        <span>Register</span>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </nav>
</header>

<style>
/* ============================================
   Version 1.2 - Modern Futuristic Header
   ============================================ */

/* Header Container */
.header-v1-2 {
    position: relative;
    z-index: 1000;
}

/* Main Navigation Bar */
.navbar-v1-2 {
    position: sticky;
    top: 0;
    background: rgba(255, 255, 255, 0.98);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    box-shadow: 0 4px 20px rgba(15, 23, 42, 0.08);
    /* Use lighter blue from logo for subtle border glow */
    border-bottom: 1px solid rgba(14, 165, 233, 0.22);
    transition: all 0.3s ease;
}

.navbar-v1-2::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    /* Top accent bar using lighter blue gradient */
    background: linear-gradient(90deg, #0ea5e9 0%, #0284c7 50%, #0ea5e9 100%);
    background-size: 200% 100%;
    animation: gradientShift 3s ease infinite;
}

@keyframes gradientShift {
    0%, 100% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
}

.navbar-container-v1-2 {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    height: 75px;
}

/* Hamburger Menu (Mobile) */
.hamburger-v1-2 {
    display: flex;
    flex-direction: column;
    gap: 5px;
    /* Match primary brand lighter blue */
    background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%);
    border: none;
    padding: 12px;
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.3s;
    z-index: 1001;
}

.hamburger-v1-2 span {
    width: 24px;
    height: 3px;
    background: white;
    border-radius: 3px;
    transition: all 0.3s;
    display: block;
}

.hamburger-v1-2:hover {
    transform: scale(1.05);
    box-shadow: 0 4px 15px rgba(37, 99, 235, 0.4);
}

.hamburger-v1-2.active span:nth-child(1) {
    transform: rotate(45deg) translate(8px, 8px);
}

.hamburger-v1-2.active span:nth-child(2) {
    opacity: 0;
}

.hamburger-v1-2.active span:nth-child(3) {
    transform: rotate(-45deg) translate(7px, -7px);
}

/* Logo */
.navbar-logo-v1-2 {
    display: flex;
    align-items: center;
    text-decoration: none;
    margin-left: 15px;
}

.logo-img-v1-2 {
    height: 55px;
    width: auto;
    object-fit: contain;
    transition: transform 0.3s;
}

.navbar-logo-v1-2:hover .logo-img-v1-2 {
    transform: scale(1.05);
}

/* Desktop Navigation Menu */
.navbar-menu-v1-2 {
    display: flex;
    align-items: center;
    gap: 5px;
    flex: 1;
    justify-content: center;
    margin: 0 30px;
}

.navbar-link-v1-2 {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px 20px;
    color: #333;
    text-decoration: none;
    border-radius: 10px;
    transition: all 0.3s;
    font-weight: 500;
    font-size: 14px;
    position: relative;
    overflow: hidden;
}

.navbar-link-v1-2::before {
    content: '';
    position: absolute;
    bottom: 0;
    left: 50%;
    width: 0;
    height: 3px;
    background: linear-gradient(90deg, #0ea5e9, #2563eb);
    transition: all 0.3s;
    transform: translateX(-50%);
    border-radius: 3px 3px 0 0;
}

.navbar-link-v1-2:hover {
    background: rgba(37, 99, 235, 0.08);
    color: #2563eb;
    text-decoration: none;
}

.navbar-link-v1-2:hover::before {
    width: 80%;
}

.navbar-link-v1-2.active {
    background: linear-gradient(135deg, rgba(14, 165, 233, 0.12) 0%, rgba(37, 99, 235, 0.12) 100%);
    color: #2563eb;
}

.navbar-link-v1-2.active::before {
    width: 100%;
}

/* Hide icons in desktop navigation (web view only) */
.navbar-menu-v1-2 .navbar-link-v1-2 i {
    display: none;
}

.navbar-menu-v1-2 .navbar-link-v1-2 {
    gap: 0;
}

/* Action Buttons */
.navbar-actions-v1-2 {
    display: flex;
    align-items: center;
    gap: 10px;
}

/* Telegram Header Button */
.telegram-header-btn {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 10px 16px;
    background: linear-gradient(135deg, #0ea5e9 0%, #2563eb 100%);
    color: white;
    border: none;
    border-radius: 25px;
    font-weight: 500;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.3s;
    white-space: nowrap;
    box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
}

.telegram-header-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.4);
    color: white;
}

.telegram-header-btn:active {
    transform: translateY(0);
}

.telegram-header-btn svg {
    width: 18px;
    height: 18px;
    flex-shrink: 0;
}

.action-btn-v1-2 {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    border-radius: 25px;
    text-decoration: none;
    font-weight: 500;
    font-size: 14px;
    transition: all 0.3s;
    white-space: nowrap;
}

.login-btn {
    background: rgba(37, 99, 235, 0.1);
    color: #2563eb;
    border: 2px solid transparent;
}

.login-btn:hover {
    background: #2563eb;
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(37, 99, 235, 0.3);
    text-decoration: none;
}

.register-btn {
    background: linear-gradient(135deg, #0ea5e9 0%, #2563eb 100%);
    color: white;
    border: 2px solid transparent;
}

.register-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
    text-decoration: none;
    color: white;
}

.dashboard-btn {
    background: linear-gradient(135deg, #0ea5e9 0%, #2563eb 100%);
    color: white;
}

.dashboard-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
    text-decoration: none;
    color: white;
}

.logout-btn {
    background: rgba(220, 53, 69, 0.1);
    color: #dc3545;
    border: 2px solid rgba(220, 53, 69, 0.2);
}

.logout-btn:hover {
    background: #dc3545;
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(220, 53, 69, 0.3);
    text-decoration: none;
}

/* Sidebar Styles */
.sidebar-v1-2 {
    position: fixed;
    top: 0;
    right: -100%;
    width: 320px;
    max-width: 85vw;
    height: 100vh;
    background: radial-gradient(circle at top left, #1d4ed8 0%, #020617 55%, #020617 100%);
    z-index: 10000;
    transition: right 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    overflow-y: auto;
    box-shadow: -5px 0 30px rgba(0, 0, 0, 0.5);
    display: flex;
    flex-direction: column;
}

.sidebar-v1-2.active {
    right: 0;
}

.sidebar-header-v1-2 {
    background: linear-gradient(135deg, #0ea5e9 0%, #2563eb 100%);
    padding: 25px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    position: relative;
    overflow: hidden;
}

.sidebar-header-v1-2::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
    animation: rotate 20s linear infinite;
}

@keyframes rotate {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

.sidebar-logo-v1-2 {
    display: flex;
    align-items: center;
    gap: 12px;
    position: relative;
    z-index: 1;
}

.sidebar-logo-img {
    height: 45px;
    width: auto;
    filter: brightness(0) invert(1);
}

.sidebar-logo-text {
    color: white;
}

.sidebar-logo-text strong {
    display: block;
    font-size: 18px;
    font-weight: 700;
    margin-bottom: 2px;
}

.sidebar-logo-text small {
    display: block;
    font-size: 10px;
    opacity: 0.9;
    line-height: 1.2;
}

.sidebar-close-v1-2 {
    background: rgba(255, 255, 255, 0.2);
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

.sidebar-close-v1-2:hover {
    background: rgba(255, 255, 255, 0.3);
    transform: rotate(90deg);
}

/* User Section */
.sidebar-user-v1-2 {
    padding: 20px;
    background: rgba(37, 99, 235, 0.15);
    display: flex;
    align-items: center;
    gap: 15px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.sidebar-avatar-v1-2 {
    width: 55px;
    height: 55px;
    border-radius: 50%;
    border: 3px solid rgba(37, 99, 235, 0.5);
    object-fit: cover;
}

.sidebar-avatar-v1-2.placeholder {
    background: rgba(37, 99, 235, 0.3);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 24px;
}

.sidebar-user-info-v1-2 {
    flex: 1;
    color: white;
}

.sidebar-user-info-v1-2 strong {
    display: block;
    font-size: 15px;
    margin-bottom: 4px;
    font-weight: 600;
}

.sidebar-user-info-v1-2 span {
    display: block;
    font-size: 12px;
    opacity: 0.8;
    color: #0ea5e9;
}

.sidebar-dashboard-btn {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 15px;
    background: linear-gradient(135deg, #0ea5e9 0%, #2563eb 100%);
    color: white;
    border-radius: 8px;
    text-decoration: none;
    font-size: 13px;
    font-weight: 500;
    transition: all 0.3s;
}

.sidebar-dashboard-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
    text-decoration: none;
    color: white;
}

/* Sidebar Navigation */
.sidebar-nav-v1-2 {
    flex: 1;
    padding: 20px 0;
    overflow-y: auto;
}

.sidebar-section-v1-2 {
    margin-bottom: 25px;
}

.sidebar-section-title {
    color: rgba(255, 255, 255, 0.5);
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 2px;
    padding: 0 20px;
    margin-bottom: 12px;
    font-weight: 600;
}

.sidebar-item-v1-2 {
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

.sidebar-item-v1-2::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 0;
    background: linear-gradient(90deg, rgba(102, 126, 234, 0.2), transparent);
    transition: width 0.3s;
}

.sidebar-item-v1-2:hover::before,
.sidebar-item-v1-2.active::before {
    width: 100%;
}

.sidebar-item-v1-2:hover,
.sidebar-item-v1-2.active {
    color: white;
    border-left-color: #2563eb;
    background: rgba(37, 99, 235, 0.1);
    text-decoration: none;
}

.sidebar-item-v1-2 i:first-child {
    width: 22px;
    text-align: center;
    font-size: 18px;
    color: #0ea5e9;
    transition: all 0.3s;
}

.sidebar-item-v1-2:hover i:first-child,
.sidebar-item-v1-2.active i:first-child {
    color: #0ea5e9;
    transform: scale(1.1);
}

.sidebar-item-v1-2 span {
    flex: 1;
    font-weight: 500;
}

.active-indicator {
    color: #0ea5e9;
    font-size: 12px;
    animation: slideIn 0.3s;
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateX(-10px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

.sidebar-item-v1-2.logout {
    color: #ff6b6b;
}

.sidebar-item-v1-2.logout:hover {
    background: rgba(255, 107, 107, 0.1);
    border-left-color: #ff6b6b;
}

.sidebar-item-v1-2.logout i:first-child {
    color: #ff6b6b;
}

/* Sidebar Footer */
.sidebar-footer-v1-2 {
    padding: 20px;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    margin-top: auto;
}

.sidebar-footer-item {
    display: flex;
    align-items: center;
    gap: 10px;
    color: rgba(255, 255, 255, 0.5);
    font-size: 11px;
    margin-bottom: 8px;
}

.sidebar-footer-item:last-child {
    margin-bottom: 0;
}

.sidebar-footer-item i {
    width: 16px;
    text-align: center;
    color: #0ea5e9;
}

/* Overlay */
.sidebar-overlay-v1-2 {
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

.sidebar-overlay-v1-2.active {
    display: block;
    opacity: 1;
}

/* Responsive */
@media (max-width: 767px) {
    .navbar-container-v1-2 {
        padding: 0 15px;
        height: 65px;
    }
    
    .logo-img-v1-2 {
        height: 45px;
    }
    
    .navbar-logo-v1-2 {
        margin-left: 10px;
    }
    
    .sidebar-v1-2 {
        width: 300px;
    }
}

@media (max-width: 480px) {
    .sidebar-v1-2 {
        width: 280px;
        max-width: 90vw;
    }
    
    .navbar-container-v1-2 {
        height: 60px;
    }
}

/* Scrollbar Styling for Sidebar */
.sidebar-v1-2::-webkit-scrollbar {
    width: 6px;
}

.sidebar-v1-2::-webkit-scrollbar-track {
    background: rgba(255, 255, 255, 0.05);
}

.sidebar-v1-2::-webkit-scrollbar-thumb {
    background: rgba(37, 99, 235, 0.5);
    border-radius: 3px;
}

.sidebar-v1-2::-webkit-scrollbar-thumb:hover {
    background: rgba(37, 99, 235, 0.7);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const openBtn = document.getElementById('openPublicSidebar');
    const closeBtn = document.getElementById('closePublicSidebar');
    const sidebar = document.getElementById('publicSidebar');
    const overlay = document.getElementById('publicSidebarOverlay');
    const hamburger = openBtn;
    
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
    
    // Telegram Header Button - Connect to chat widget
    const telegramHeaderBtn = document.getElementById('telegramHeaderChatButton');
    if (telegramHeaderBtn) {
        telegramHeaderBtn.addEventListener('click', function() {
            // Wait for chat widget to load, then trigger it
            setTimeout(function() {
                if (typeof window.showTelegramChat !== 'undefined') {
                    window.showTelegramChat();
                } else {
                    // Fallback: trigger the chat button click
                    const chatButton = document.getElementById('telegramChatButton');
                    if (chatButton) {
                        chatButton.click();
                    }
                }
            }, 100);
        });
    }
});
</script>

