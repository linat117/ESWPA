<!DOCTYPE html>
<html lang="en">
<?php
session_start();
include 'head.php';
include 'include/config.php';
?>

<body>
    <!-- Header -->
    <?php 
    if (isset($_SESSION['member_id'])) {
        include 'member-header-v1.2.php';
    } else {
        include 'header-v1.2.php';
    }
    ?>
    <!-- End Header -->

    <!-- Start Breadcrumb (Only for non-members) -->
    <?php if (!isset($_SESSION['member_id'])): ?>
    <div class="breadcrumb-area text-center shadow dark bg-fixed padding-xl text-light" style="background-image: url(assets/img/bgregister.png);">
        <div class="container">
            <div class="breadcrumb-items">
                <div class="row">
                    <div class="col-lg-12">
                        <h2>News & Media</h2>
                    </div>
                </div>
                <ul class="breadcrumb">
                    <li><a href="index.php"><i class="fas fa-home"></i> Home</a></li>
                    <li class="active">News & Media</li>
                </ul>
            </div>
        </div>
    </div>
    <?php else: ?>
    <div class="mp-content-wrapper">
        <div class="mp-container">
            <div class="mp-content">
                <h2 class="mp-page-title mp-mb-md"><i class="fas fa-newspaper"></i> News & Media</h2>
    <?php endif; ?>
    <!-- End Breadcrumb -->

    <?php if (!isset($_SESSION['member_id'])): ?>
    <div class="blog-area single full-blog full-blog default-padding">
        <div class="container">
    <?php endif; ?>
            <!-- Enhanced Horizontal Tabs -->
            <div class="<?php echo isset($_SESSION['member_id']) ? '' : 'row'; ?>">
                <div class="<?php echo isset($_SESSION['member_id']) ? '' : 'col-12 mb-4'; ?>">
                    <div class="<?php echo isset($_SESSION['member_id']) ? 'mp-news-tabs-container' : 'news-tabs-container'; ?>">
                        <ul class="nav <?php echo isset($_SESSION['member_id']) ? 'mp-news-tabs' : 'news-tabs-horizontal'; ?>" id="newsTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="all-tab" data-bs-toggle="tab" data-bs-target="#all" type="button" role="tab" aria-controls="all" aria-selected="true">
                                    <i class="fas fa-th"></i>
                                    <span>All</span>
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="news-tab" data-bs-toggle="tab" data-bs-target="#news" type="button" role="tab" aria-controls="news" aria-selected="false">
                                    <i class="fas fa-newspaper"></i>
                                    <span>News</span>
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="blog-tab" data-bs-toggle="tab" data-bs-target="#blog" type="button" role="tab" aria-controls="blog" aria-selected="false">
                                    <i class="fas fa-blog"></i>
                                    <span>Blog</span>
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="report-tab" data-bs-toggle="tab" data-bs-target="#report" type="button" role="tab" aria-controls="report" aria-selected="false">
                                    <i class="fas fa-file-alt"></i>
                                    <span>Reports</span>
                                </button>
                            </li>
                        </ul>
                    </div>
                </div>

            </div>
            
            <!-- Tab Content -->
            <div class="row">
                <div class="col-12">
                    <div class="tab-content" id="newsTabContent">
                        <?php
                        // Get all published posts
                        $allQuery = "SELECT * FROM news_media WHERE status = 'published' ORDER BY published_date DESC, created_at DESC";
                        $allResult = mysqli_query($conn, $allQuery);

                        // Get posts by type
                        $newsQuery = "SELECT * FROM news_media WHERE type = 'news' AND status = 'published' ORDER BY published_date DESC";
                        $newsResult = mysqli_query($conn, $newsQuery);

                        $blogQuery = "SELECT * FROM news_media WHERE type = 'blog' AND status = 'published' ORDER BY published_date DESC";
                        $blogResult = mysqli_query($conn, $blogQuery);

                        $reportQuery = "SELECT * FROM news_media WHERE type = 'report' AND status = 'published' ORDER BY published_date DESC";
                        $reportResult = mysqli_query($conn, $reportQuery);

                        function displayPosts($result, $emptyMessage) {
                            $isMember = isset($_SESSION['member_id']);
                            if (mysqli_num_rows($result) > 0) {
                                echo '<div class="' . ($isMember ? 'mp-grid-auto' : 'row') . '">';
                                while ($row = mysqli_fetch_assoc($result)) {
                                    $images = !empty($row['images']) ? json_decode($row['images'], true) : [];
                                    $firstImage = !empty($images) ? $images[0] : null;
                                    
                                    if ($isMember) {
                                        echo '<div class="mp-news-card">';
                                        if ($firstImage) {
                                            echo '<img src="' . htmlspecialchars($firstImage) . '" class="mp-news-card-img" alt="' . htmlspecialchars($row['title']) . '">';
                                        }
                                        echo '<div class="mp-news-card-body">';
                                        echo '<span class="mp-badge mp-badge-info mp-news-card-badge">' . ucfirst($row['type']) . '</span>';
                                        echo '<h5 class="mp-news-card-title">' . htmlspecialchars($row['title']) . '</h5>';
                                        echo '<p class="mp-news-card-text">';
                                        echo substr(strip_tags($row['content']), 0, 100) . '...';
                                        echo '</p>';
                                        echo '</div>';
                                        echo '<div class="mp-news-card-footer">';
                                        echo '<div>';
                                        echo '<small class="mp-news-card-date">';
                                        if (!empty($row['published_date'])) {
                                            echo date('M d, Y', strtotime($row['published_date']));
                                        }
                                        echo '</small>';
                                        if (!empty($row['author'])) {
                                            echo '<br><small style="color: var(--mp-gray-500); font-size: 0.7rem;">By: ' . htmlspecialchars($row['author']) . '</small>';
                                        }
                                        echo '</div>';
                                        echo '<a href="news-detail.php?id=' . $row['id'] . '" class="mp-btn mp-btn-sm mp-btn-primary">Read More</a>';
                                        echo '</div>';
                                        echo '</div>';
                                    } else {
                                        echo '<div class="col-md-6 col-lg-4 mb-4 wow fadeInUp" data-wow-delay="0.1s">';
                                        echo '<div class="card shadow-sm h-100">';
                                        if ($firstImage) {
                                            echo '<img src="' . htmlspecialchars($firstImage) . '" class="card-img-top" alt="' . htmlspecialchars($row['title']) . '" style="height: 200px; object-fit: cover;">';
                                        }
                                        echo '<div class="card-body">';
                                        echo '<span class="badge bg-info mb-2">' . ucfirst($row['type']) . '</span>';
                                        echo '<h5 class="card-title">' . htmlspecialchars($row['title']) . '</h5>';
                                        echo '<p class="card-text text-muted small">';
                                        echo substr(strip_tags($row['content']), 0, 100) . '...';
                                        echo '</p>';
                                        echo '</div>';
                                        echo '<div class="card-footer bg-white">';
                                        echo '<div class="d-flex justify-content-between align-items-center">';
                                        echo '<small class="text-muted">';
                                        if (!empty($row['published_date'])) {
                                            echo date('M d, Y', strtotime($row['published_date']));
                                        }
                                        echo '</small>';
                                        echo '<a href="news-detail.php?id=' . $row['id'] . '" class="btn btn-sm btn-primary">Read More</a>';
                                        echo '</div>';
                                        if (!empty($row['author'])) {
                                            echo '<small class="text-muted d-block mt-2">By: ' . htmlspecialchars($row['author']) . '</small>';
                                        }
                                        echo '</div>';
                                        echo '</div>';
                                        echo '</div>';
                                    }
                                }
                                echo '</div>';
                            } else {
                                $alertClass = $isMember ? 'mp-alert mp-alert-info' : 'alert alert-info';
                                echo '<div class="' . $alertClass . '">' . $emptyMessage . '</div>';
                            }
                        }
                        ?>

                        <!-- All Tab -->
                        <div class="tab-pane fade show active" id="all" role="tabpanel" aria-labelledby="all-tab">
                            <?php displayPosts($allResult, 'No posts available.'); ?>
                        </div>

                        <!-- News Tab -->
                        <div class="tab-pane fade" id="news" role="tabpanel" aria-labelledby="news-tab">
                            <?php displayPosts($newsResult, 'No news articles available.'); ?>
                        </div>

                        <!-- Blog Tab -->
                        <div class="tab-pane fade" id="blog" role="tabpanel" aria-labelledby="blog-tab">
                            <?php displayPosts($blogResult, 'No blog posts available.'); ?>
                        </div>

                        <!-- Report Tab -->
                        <div class="tab-pane fade" id="report" role="tabpanel" aria-labelledby="report-tab">
                            <?php displayPosts($reportResult, 'No reports available.'); ?>
                        </div>
                    </div>
                </div>
            </div>
    <?php if (!isset($_SESSION['member_id'])): ?>
        </div>
    </div>
    <?php else: ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Footer (Only for non-members) -->
    <?php if (!isset($_SESSION['member_id'])): ?>
        <?php include 'footer.php'; ?>
    <?php endif; ?>

    <!-- Optimized Scripts for Member Pages -->
    <script src="assets/js/jquery-1.12.4.min.js"></script>
    <script src="assets/js/bootstrap.min.js" defer></script>
    <script src="assets/js/bootsnav.js" defer></script>
    
    <?php if (isset($_SESSION['member_id'])): ?>
    <!-- Member Panel CSS -->
    <link href="assets/css/member-panel.css" rel="stylesheet">
    <?php else: ?>
    <!-- Member Optimized CSS -->
    <link href="assets/css/member-optimized.css" rel="stylesheet">
    
    <!-- News Page Enhanced Styles (Only for non-members) -->
    <style>
    /* Enhanced News Tabs */
    .news-tabs-container {
        background: white;
        border-radius: 12px;
        padding: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        margin-bottom: 30px;
    }
    
    .news-tabs-horizontal {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        border: none;
        margin: 0;
        padding: 0;
        list-style: none;
    }
    
    .news-tabs-horizontal .nav-item {
        flex: 1;
        min-width: 120px;
    }
    
    .news-tabs-horizontal .nav-link {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 6px;
        padding: 15px 20px;
        border: 2px solid transparent;
        border-radius: 8px;
        background: #f8f9fa;
        color: #666;
        text-decoration: none;
        transition: all 0.3s;
        font-weight: 500;
        font-size: 14px;
        min-height: 80px;
        width: 100%;
        border: none;
        cursor: pointer;
    }
    
    .news-tabs-horizontal .nav-link i {
        font-size: 20px;
        margin-bottom: 4px;
    }
    
    .news-tabs-horizontal .nav-link:hover {
        background: #e9ecef;
        color: #2563eb;
        border-color: #2563eb;
    }
    
    .news-tabs-horizontal .nav-link.active {
        background: linear-gradient(135deg, #0ea5e9 0%, #2563eb 100%);
        color: white !important;
        border-color: #0ea5e9;
        box-shadow: 0 4px 15px rgba(37, 99, 235, 0.3);
    }
    
    .news-tabs-horizontal .nav-link.active i {
        color: white;
    }
    
    .news-tabs-horizontal .nav-link:focus {
        outline: none;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.2);
    }
    
    /* Tab Content */
    .tab-content {
        margin-top: 20px;
    }
    
    .tab-pane {
        animation: fadeIn 0.3s;
    }
    
    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    /* Responsive Tabs */
    @media (max-width: 767px) {
        .news-tabs-horizontal .nav-item {
            flex: 1 1 calc(50% - 4px);
            min-width: calc(50% - 4px);
        }
        
        .news-tabs-horizontal .nav-link {
            padding: 12px 10px;
            min-height: 70px;
            font-size: 12px;
        }
        
        .news-tabs-horizontal .nav-link i {
            font-size: 18px;
        }
    }
    
    @media (max-width: 480px) {
        .news-tabs-horizontal .nav-item {
            flex: 1 1 100%;
            min-width: 100%;
        }
        
        .news-tabs-container {
            padding: 5px;
        }
    }
    </style>
    <?php endif; ?>
    
    <?php
    // Close database connection if it exists
    if (isset($conn) && $conn) {
        mysqli_close($conn);
    }
    ?>

</body>

</html>

