<?php
session_start();

if (!isset($_SESSION['member_id'])) {
    header("Location: member-login.php");
    exit();
}

$current_page = 'member-tools.php';

// Define all tools pages with icons and categories
$tools_pages = [
    'Research Tools' => [
        [
            'title' => 'Citations',
            'description' => 'Generate and manage citations',
            'icon' => 'fa-quote-left',
            'link' => 'member-citations.php',
            'color' => 'linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%)'
        ],
        [
            'title' => 'Bibliography',
            'description' => 'Manage bibliography collections',
            'icon' => 'fa-book',
            'link' => 'member-bibliography.php',
            'color' => 'linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%)'
        ],
        [
            'title' => 'Notes',
            'description' => 'Take and organize notes',
            'icon' => 'fa-sticky-note',
            'link' => 'member-notes.php',
            'color' => 'linear-gradient(135deg, #f59e0b 0%, #d97706 100%)'
        ],
        [
            'title' => 'Reading Progress',
            'description' => 'Track your reading progress',
            'icon' => 'fa-book-reader',
            'link' => 'member-reading-progress.php',
            'color' => 'linear-gradient(135deg, #10b981 0%, #059669 100%)'
        ]
    ],
    'Research Pages' => [
        [
            'title' => 'Research Library',
            'description' => 'Browse research projects',
            'icon' => 'fa-search',
            'link' => 'member-research-library.php',
            'color' => 'linear-gradient(135deg, #2563eb 0%, #1e40af 100%)'
        ],
        [
            'title' => 'My Research',
            'description' => 'Manage your research projects',
            'icon' => 'fa-flask',
            'link' => 'member-research.php',
            'color' => 'linear-gradient(135deg, #dc2626 0%, #b91c1c 100%)'
        ],
        [
            'title' => 'Create Research',
            'description' => 'Start a new research project',
            'icon' => 'fa-plus-circle',
            'link' => 'member-create-research.php',
            'color' => 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)'
        ]
    ],
    'Member Features' => [
        [
            'title' => 'Profile Edit',
            'description' => 'Update your profile',
            'icon' => 'fa-user-edit',
            'link' => 'member-profile-edit.php',
            'color' => 'linear-gradient(135deg, #06b6d4 0%, #0891b2 100%)'
        ],
        [
            'title' => 'Member Directory',
            'description' => 'Connect with members',
            'icon' => 'fa-users',
            'link' => 'member-directory.php',
            'color' => 'linear-gradient(135deg, #3b82f6 0%, #2563eb 100%)'
        ],
        [
            'title' => 'My Badges',
            'description' => 'View your achievements',
            'icon' => 'fa-trophy',
            'link' => 'member-badges.php',
            'color' => 'linear-gradient(135deg, #f59e0b 0%, #d97706 100%)'
        ],
        [
            'title' => 'Notifications',
            'description' => 'View your notifications',
            'icon' => 'fa-bell',
            'link' => 'member-notifications.php',
            'color' => 'linear-gradient(135deg, #ec4899 0%, #db2777 100%)'
        ],
        [
            'title' => 'My ID Card',
            'description' => 'Generate your ID card',
            'icon' => 'fa-id-card',
            'link' => 'member-generate-id-card.php',
            'color' => 'linear-gradient(135deg, #14b8a6 0%, #0d9488 100%)'
        ]
    ],
    'Resources & Media' => [
        [
            'title' => 'Resources',
            'description' => 'Browse and download resources',
            'icon' => 'fa-download',
            'link' => 'resources.php',
            'color' => 'linear-gradient(135deg, #6366f1 0%, #4f46e5 100%)'
        ],
        [
            'title' => 'News & Media',
            'description' => 'Read latest news and updates',
            'icon' => 'fa-newspaper',
            'link' => 'news.php',
            'color' => 'linear-gradient(135deg, #ef4444 0%, #dc2626 100%)'
        ]
    ]
];
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
                        <h2 class="mp-page-title"><i class="fas fa-tools"></i> Tools & Features</h2>
                        <p style="color: var(--mp-gray-600); margin: var(--mp-space-xs) 0 0 0; font-size: 0.875rem;">Access all member tools and features in one place</p>
                    </div>
                </div>

                <!-- Tools Grid by Category -->
                <?php foreach ($tools_pages as $category => $pages): ?>
                    <div class="mp-card mp-mb-lg">
                        <div class="mp-card-header">
                            <h5><?php echo htmlspecialchars($category); ?></h5>
                        </div>
                        <div class="mp-card-body">
                            <div class="mp-tools-grid">
                                <?php foreach ($pages as $page): ?>
                                    <a href="<?php echo htmlspecialchars($page['link']); ?>" class="mp-tool-card">
                                        <div class="mp-tool-icon" style="background: <?php echo htmlspecialchars($page['color']); ?>;">
                                            <i class="fas <?php echo htmlspecialchars($page['icon']); ?>"></i>
                                        </div>
                                        <div class="mp-tool-content">
                                            <h6 class="mp-tool-title"><?php echo htmlspecialchars($page['title']); ?></h6>
                                            <p class="mp-tool-description"><?php echo htmlspecialchars($page['description']); ?></p>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

</body>
</html>

