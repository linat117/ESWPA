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
                    ?>
                        <div class="col-sm-6 col-lg-4 col-xl-3">
                            <div class="event-card-upcoming wow fadeInUp" data-wow-delay="0.1s">
                                <div class="thumb">
                                    <a href="events.php">
                                        <?php if ($image_path): ?>
                                            <img src="<?php echo htmlspecialchars($image_path); ?>" alt="Event Image" style="width: 100%; height: 220px; object-fit: cover;">
                                        <?php else: ?>
                                            <img src="assets/img/default-event.jpg" alt="No Image Available" style="width: 100%; height: 220px; object-fit: cover;">
                                        <?php endif; ?>
                                        <span class="overlay">
                                            <?php echo date("M j, Y", strtotime($row['event_date'])); ?>
                                        </span>
                                    </a>
                                </div>
                                <div class="info">
                                    <h4>
                                        <a href="events.php"><?php echo htmlspecialchars($row['event_header']); ?></a>
                                    </h4>
                                    <p><?php echo strip_outer_p($row['event_description']); ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <p class="text-center text-muted mb-0">There are no upcoming events at the moment. Please check back soon.</p>
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
            <div class="causes-items">
                <div class="row">
                    <div class="col-lg-12">
                        <?php while ($row = $result->fetch_assoc()) {
                            $event_images = json_decode($row['event_images'], true);
                        ?>
                            <!-- Single Item -->
                            <div class="grid-item wow fadeInUp" data-wow-delay="0.15s">
                                <div class="row">
                                    <div class="thumb col-lg-5">
                                        <?php
                                        $image_path = null;
                                        if (!empty($event_images) && is_array($event_images)) {
                                            $first = reset($event_images);
                                            $image_path = "uploads/" . basename($first);
                                        }
                                        ?>
                                        <?php if ($image_path): ?>
                                            <img src="<?php echo htmlspecialchars($image_path); ?>" alt="Event Image"
                                                style="width: 100%; height: 260px; object-fit: cover; border-radius: 8px;">
                                        <?php else : ?>
                                            <img src="assets/img/default-event.jpg" alt="No Image Available"
                                                style="width: 100%; height: 260px; object-fit: cover; border-radius: 8px;">
                                        <?php endif; ?>
                                    </div>

                                    <div class="info col-lg-7">
                                        <h3>
                                            <a href="#"> <?php echo htmlspecialchars($row['event_header']); ?> </a>
                                        </h3>
                                        <p> <?php echo strip_outer_p($row['event_description']); ?> </p>
                                        <div class="top-entry">
                                            <div class="date">
                                                <i class="fas fa-clock"></i> <?php echo date("M d, Y", strtotime($row['event_date'])); ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- End Single Item -->
                        <?php } ?>
                    </div>
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