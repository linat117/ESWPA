<?php
include 'head.php';
include 'include/config.php';

$post_id = intval($_GET['id'] ?? 0);

if ($post_id <= 0) {
    header("Location: news.php");
    exit();
}

$query = "SELECT * FROM news_media WHERE id = ? AND status = 'published'";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $post_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: news.php?error=Post not found");
    exit();
}

$post = $result->fetch_assoc();
$stmt->close();

$images = !empty($post['images']) ? json_decode($post['images'], true) : [];
?>
<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<?php include 'head.php'; ?>

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
                        <h2><?php echo htmlspecialchars($post['title']); ?></h2>
                    </div>
                </div>
                <ul class="breadcrumb">
                    <li><a href="index.php"><i class="fas fa-home"></i> Home</a></li>
                    <li><a href="news.php">News & Media</a></li>
                    <li class="active"><?php echo htmlspecialchars($post['title']); ?></li>
                </ul>
            </div>
        </div>
    </div>
    <?php else: ?>
    <div class="mp-content-wrapper">
        <div class="mp-container">
            <div class="mp-content" style="max-width: 900px;">
    <?php endif; ?>
    <!-- End Breadcrumb -->

    <?php if (!isset($_SESSION['member_id'])): ?>
    <div class="blog-area single full-blog full-blog default-padding">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 offset-lg-2">
                    <div class="blog-items">
                        <div class="item">
                            <div class="blog-item-box">
                                <div class="info">
                                    <div class="meta">
                                        <ul>
                                            <li>
                                                <span class="badge bg-info"><?php echo ucfirst($post['type']); ?></span>
                                            </li>
                                            <li>
                                                <i class="fas fa-calendar"></i> 
                                                <?php echo !empty($post['published_date']) ? date('F d, Y', strtotime($post['published_date'])) : date('F d, Y', strtotime($post['created_at'])); ?>
                                            </li>
                                            <?php if (!empty($post['author'])): ?>
                                                <li>
                                                    <i class="fas fa-user"></i> <?php echo htmlspecialchars($post['author']); ?>
                                                </li>
                                            <?php endif; ?>
                                        </ul>
                                    </div>

                                    <h2><?php echo htmlspecialchars($post['title']); ?></h2>

                                    <?php if (!empty($images)): ?>
                                        <div class="thumb mb-4">
                                            <div id="postCarousel" class="carousel slide" data-bs-ride="carousel">
                                                <div class="carousel-inner">
                                                    <?php foreach ($images as $index => $image): ?>
                                                        <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>">
                                                            <img src="<?php echo htmlspecialchars($image); ?>" 
                                                                 alt="<?php echo htmlspecialchars($post['title']); ?>" 
                                                                 class="d-block w-100" 
                                                                 style="max-height: 500px; object-fit: cover;">
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                                <?php if (count($images) > 1): ?>
                                                    <button class="carousel-control-prev" type="button" data-bs-target="#postCarousel" data-bs-slide="prev">
                                                        <span class="carousel-control-prev-icon"></span>
                                                    </button>
                                                    <button class="carousel-control-next" type="button" data-bs-target="#postCarousel" data-bs-slide="next">
                                                        <span class="carousel-control-next-icon"></span>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <div class="content">
                                        <?php echo nl2br(htmlspecialchars($post['content'])); ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="post-navigation mt-4">
                            <a href="news.php" class="btn btn-primary">
                                <i class="fas fa-arrow-left"></i> Back to News & Media
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php else: ?>
        <!-- Member View -->
        <div class="mp-card">
            <div class="mp-card-body">
                <div class="mp-flex-between mp-mb-md">
                    <div>
                        <span class="mp-badge mp-badge-info mp-mb-sm"><?php echo ucfirst($post['type']); ?></span>
                        <h1 class="mp-page-title" style="margin-top: var(--mp-space-sm);"><?php echo htmlspecialchars($post['title']); ?></h1>
                        <div style="color: var(--mp-gray-600); font-size: 0.875rem; margin-top: var(--mp-space-sm);">
                            <span><i class="fas fa-calendar"></i> <?php echo !empty($post['published_date']) ? date('F d, Y', strtotime($post['published_date'])) : date('F d, Y', strtotime($post['created_at'])); ?></span>
                            <?php if (!empty($post['author'])): ?>
                                <span style="margin-left: var(--mp-space-md);"><i class="fas fa-user"></i> <?php echo htmlspecialchars($post['author']); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <?php if (!empty($images)): ?>
                    <div class="mp-mb-md">
                        <div id="postCarousel" class="carousel slide" data-bs-ride="carousel">
                            <div class="carousel-inner">
                                <?php foreach ($images as $index => $image): ?>
                                    <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>">
                                        <img src="<?php echo htmlspecialchars($image); ?>" 
                                             alt="<?php echo htmlspecialchars($post['title']); ?>" 
                                             class="d-block w-100" 
                                             style="max-height: 400px; object-fit: cover; border-radius: var(--mp-radius-md);">
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <?php if (count($images) > 1): ?>
                                <button class="carousel-control-prev" type="button" data-bs-target="#postCarousel" data-bs-slide="prev">
                                    <span class="carousel-control-prev-icon"></span>
                                </button>
                                <button class="carousel-control-next" type="button" data-bs-target="#postCarousel" data-bs-slide="next">
                                    <span class="carousel-control-next-icon"></span>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <div style="color: var(--mp-gray-800); line-height: 1.7; font-size: 0.9375rem;">
                    <?php echo nl2br(htmlspecialchars($post['content'])); ?>
                </div>

                <div class="mp-mt-lg">
                    <a href="news.php" class="mp-btn mp-btn-primary">
                        <i class="fas fa-arrow-left"></i> Back to News & Media
                    </a>
                </div>
            </div>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['member_id'])): ?>
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
    <?php endif; ?>
    
    <?php
    // Close database connection if it exists
    if (isset($conn) && $conn) {
        mysqli_close($conn);
    }
    ?>

</body>

</html>

