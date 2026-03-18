<!DOCTYPE html>
<html lang="en">

<?php
include 'head.php';
include 'include/config.php';

// Helper to remove a single outer <p>...</p> wrapper while keeping inner content
function strip_outer_p($html) {
    $html = trim($html);
    if (preg_match('/^<p[^>]*>(.*)<\/p>$/is', $html, $matches)) {
        return $matches[1];
    }
    return $html;
}
?>

<style>
/* Modern Event Card Styles */
.event-card-modern {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    border: none;
    border-radius: 12px;
    overflow: hidden;
}

.event-card-modern:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.15);
}

.event-card-modern .card-img-wrapper {
    overflow: hidden;
}

.event-card-modern .card-img-top {
    transition: transform 0.3s ease;
}

.event-card-modern:hover .card-img-top {
    transform: scale(1.05);
}

.event-date-badge {
    position: absolute;
    top: 15px;
    right: 15px;
    z-index: 10;
}

.event-date-badge .badge {
    font-size: 0.8rem;
    padding: 6px 12px;
    border-radius: 20px;
    font-weight: 600;
}

.event-card-modern .card-title {
    font-size: 1.1rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
    line-height: 1.4;
}

.event-card-modern .card-title a {
    transition: color 0.3s ease;
}

.event-card-modern .card-title a:hover {
    color: #007bff !important;
}

.event-card-modern .card-text {
    font-size: 0.9rem;
    line-height: 1.6;
    color: #6c757d;
}

.event-card-modern .event-meta {
    font-size: 0.85rem;
}

.event-card-modern .btn {
    border-radius: 25px;
    font-weight: 500;
    transition: all 0.3s ease;
}

.event-card-modern .btn:hover {
    transform: translateX(3px);
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .event-card-modern .card-title {
        font-size: 1rem;
    }
    
    .event-date-badge .badge {
        font-size: 0.75rem;
        padding: 4px 8px;
    }
}

/* Empty state styling */
.empty-state {
    padding: 60px 20px;
    text-align: center;
}

.empty-state i {
    color: #dee2e6;
    margin-bottom: 20px;
}
</style>

<body>

    <?php
    include 'header-v1.2.php';
    ?>

    <!-- Events hero / breadcrumb -->
    <section class="membership-hero-intro events-hero-intro" aria-label="Events">
        <div class="membership-hero-intro__bg"></div>
        <div class="container membership-hero-intro__inner">
            <div class="row align-center">
                <div class="col-lg-7">
                    <div class="membership-hero-intro__content">
                        <span class="membership-hero-intro__eyebrow">Events</span>
                        <h1>Stay connected with ESWPA events.</h1>
                        <p>
                            Explore upcoming and past events that bring together social work professionals,
                            educators, and students across Ethiopia.
                        </p>
                        <ul class="membership-hero-intro__highlights">
                            <li>Professional development and learning</li>
                            <li>Networking and collaboration opportunities</li>
                            <li>National and regional engagements</li>
                        </ul>
                        <div class="membership-hero-intro__breadcrumbs">
                            <a href="index.php"><i class="fas fa-home"></i> Home</a>
                            <span>/</span>
                            <span>Events</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Upcoming events
    ============================================= -->
    <?php 
    $query = "SELECT * FROM upcoming ORDER BY event_date ASC";
    $result = mysqli_query($conn, $query);
    $has_upcoming = ($result && mysqli_num_rows($result) > 0);
    ?>

    <div class="causes-area bg-gray default-padding events-page-upcoming">
        <div class="site-heading text-center">
            <h2>Upcoming Events</h2>
            <div class="heading-divider"></div>
        </div>

        <div class="container-full">
            <?php if ($has_upcoming): ?>
                <div class="row">
                    <?php while ($row = mysqli_fetch_assoc($result)) : 
                        $event_images = json_decode($row['event_images'], true);
                        $image_path = null;
                        if (!empty($event_images) && is_array($event_images)) {
                            $first = reset($event_images);
                            $image_path = "uploads/" . basename($first);
                        }
                        
                        // Create excerpt from description
                        $description = strip_outer_p($row['event_description']);
                        $excerpt = strlen($description) > 120 ? substr($description, 0, 120) . '...' : $description;
                    ?>
                        <div class="col-sm-6 col-lg-4 col-xl-3 mb-4">
                            <div class="event-card-modern card h-100 shadow-sm">
                                <div class="card-img-wrapper position-relative">
                                    <?php if ($image_path): ?>
                                        <img src="<?php echo htmlspecialchars($image_path); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($row['event_header']); ?>" style="height: 200px; object-fit: cover;">
                                    <?php else: ?>
                                        <img src="assets/img/default-event.jpg" class="card-img-top" alt="No Image Available" style="height: 200px; object-fit: cover;">
                                    <?php endif; ?>
                                    <div class="event-date-badge">
                                        <span class="badge bg-primary text-white">
                                            <?php echo date("M j", strtotime($row['event_date'])); ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="card-body d-flex flex-column">
                                    <h5 class="card-title">
                                        <a href="event-details.php?id=<?php echo $row['id']; ?>&type=upcoming" class="text-decoration-none text-dark">
                                            <?php echo htmlspecialchars($row['event_header']); ?>
                                        </a>
                                    </h5>
                                    <div class="event-meta text-muted small mb-2">
                                        <i class="fas fa-calendar-alt me-1"></i>
                                        <?php echo date("F j, Y", strtotime($row['event_date'])); ?>
                                    </div>
                                    <p class="card-text flex-grow-1">
                                        <?php echo htmlspecialchars($excerpt); ?>
                                    </p>
                                    <div class="mt-auto">
                                        <a href="event-details.php?id=<?php echo $row['id']; ?>&type=upcoming" class="btn btn-primary btn-sm">
                                            View Details <i class="fas fa-arrow-right ms-1"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                    <p class="text-muted mb-0">There are no upcoming events at the moment. Please check back soon.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Start Causes 
    ============================================= -->
    <?php
    // Pagination settings
    $limit = 4;
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $offset = ($page - 1) * $limit;

    // Fetch events
    $sql = "SELECT id, event_date, event_header, event_description, event_images FROM events ORDER BY event_date DESC LIMIT $limit OFFSET $offset";
    $result = $conn->query($sql);

    // Fetch total records
    $total_sql = "SELECT COUNT(*) AS total FROM events";
    $total_result = $conn->query($total_sql);
    $total_row = $total_result->fetch_assoc();
    $total_pages = ceil($total_row['total'] / $limit);
    ?>

    <div class="causes-area bg-gray default-padding-bottom events-page-latest">
        <div class="site-heading text-center">
            <h2>Latest Events</h2>
            <div class="heading-divider"></div>
        </div>
        <div class="container">
            <div class="row">
                <?php while ($row = $result->fetch_assoc()) {
                    $event_images = json_decode($row['event_images'], true);
                    $image_path = null;
                    if (!empty($event_images) && is_array($event_images)) {
                        $first = reset($event_images);
                        $image_path = "uploads/" . basename($first);
                    }
                    
                    // Create excerpt from description
                    $description = strip_outer_p($row['event_description']);
                    $excerpt = strlen($description) > 150 ? substr($description, 0, 150) . '...' : $description;
                ?>
                    <div class="col-lg-6 col-xl-4 mb-4">
                        <div class="event-card-modern card h-100 shadow-sm">
                            <div class="card-img-wrapper position-relative">
                                <?php if ($image_path): ?>
                                    <img src="<?php echo htmlspecialchars($image_path); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($row['event_header']); ?>" style="height: 200px; object-fit: cover;">
                                <?php else: ?>
                                    <img src="assets/img/default-event.jpg" class="card-img-top" alt="No Image Available" style="height: 200px; object-fit: cover;">
                                <?php endif; ?>
                                <div class="event-date-badge">
                                    <span class="badge bg-secondary text-white">
                                        <?php echo date("M j", strtotime($row['event_date'])); ?>
                                    </span>
                                </div>
                            </div>
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title">
                                    <a href="event-details.php?id=<?php echo $row['id']; ?>&type=past" class="text-decoration-none text-dark">
                                        <?php echo htmlspecialchars($row['event_header']); ?>
                                    </a>
                                </h5>
                                <div class="event-meta text-muted small mb-2">
                                    <i class="fas fa-calendar-alt me-1"></i>
                                    <?php echo date("F j, Y", strtotime($row['event_date'])); ?>
                                    <span class="ms-3"><i class="fas fa-history me-1"></i> Past Event</span>
                                </div>
                                <p class="card-text flex-grow-1">
                                    <?php echo htmlspecialchars($excerpt); ?>
                                </p>
                                <div class="mt-auto">
                                    <a href="event-details.php?id=<?php echo $row['id']; ?>&type=past" class="btn btn-outline-secondary btn-sm">
                                        View Details <i class="fas fa-arrow-right ms-1"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php } ?>
            </div>
                <!-- Pagination -->
                <div class="donation-pagi text-center col-lg-12">
                    <nav aria-label="navigation">
                        <ul class="pagination">
                            <?php if ($page > 1) { ?>
                                <li class="page-item"><a class="page-link" href="?page=<?php echo ($page - 1); ?>"><i class="fas fa-angle-double-left"></i></a></li>
                            <?php } ?>
                            <?php for ($i = 1; $i <= $total_pages; $i++) { ?>
                                <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>"> <?php echo $i; ?> </a>
                                </li>
                            <?php } ?>
                            <?php if ($page < $total_pages) { ?>
                                <li class="page-item"><a class="page-link" href="?page=<?php echo ($page + 1); ?>"><i class="fas fa-angle-double-right"></i></a></li>
                            <?php } ?>
                        </ul>
                    </nav>
                </div>
                <!-- End Pagination -->
            </div>
        </div>
    </div>

    <?php $conn->close(); ?>


    <!-- End Causes -->

    <!-- Start Footer 
    ============================================= -->
    <?php
    include 'footer.php';
    ?>
    <!-- End Footer -->
    <!-- jQuery Frameworks
    ============================================= -->
    <script src="assets/js/jquery-1.12.4.min.js"></script>
    <script src="assets/js/popper.min.js"></script>
    <script src="assets/js/bootstrap.min.js"></script>
    <script src="assets/js/equal-height.min.js"></script>
    <script src="assets/js/jquery.appear.js"></script>
    <script src="assets/js/jquery.easing.min.js"></script>
    <script src="assets/js/jquery.magnific-popup.min.js"></script>
    <script src="assets/js/modernizr.custom.13711.js"></script>
    <script src="assets/js/owl.carousel.min.js"></script>
    <script src="assets/js/wow.min.js"></script>
    <script src="assets/js/progress-bar.min.js"></script>
    <script src="assets/js/isotope.pkgd.min.js"></script>
    <script src="assets/js/imagesloaded.pkgd.min.js"></script>
    <script src="assets/js/count-to.js"></script>
    <script src="assets/js/YTPlayer.min.js"></script>
    <script src="assets/js/jquery.nice-select.min.js"></script>
    <script src="assets/js/bootsnav.js"></script>
    <script src="assets/js/main.js"></script>

</body>

</html>