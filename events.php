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

    <!-- Start Breadcrumb 
    ============================================= -->
    <div class="breadcrumb-area text-center shadow dark bg-fixed padding-xl text-light" style="background-image: url(assets/img/2240.png);">
        <div class="container">
            <div class="breadcrumb-items">
                <div class="row">
                    <div class="col-lg-12">
                        <h2>Latest Events</h2>
                    </div>
                </div>
                <ul class="breadcrumb">
                    <li><a href="#"><i class="fas fa-home"></i> Home</a></li>
                    <li class="active">Events</li>
                </ul>
            </div>
        </div>
    </div>
    <!-- End Breadcrumb -->

    <!-- Star Recent events Area
    ============================================= -->
    <?php 
$query = "SELECT * FROM upcoming ORDER BY event_date ASC";
$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) > 0) : 
?>
    <div class="recent-causes-area carousel-shadow causes-area default-padding home-events events-page-upcoming wow fadeInUp" data-wow-delay="0.1s">
        <div class="site-heading text-center">
            <h2>Upcoming Events</h2>
            <div class="heading-divider"></div>
        </div>

    <div class="container-full">
        <div class="row">
            <div class="col-lg-12 causes-items">
                <?php if (mysqli_num_rows($result) > 1) : ?>
                    <div class="recent-causes-carousel owl-carousel owl-theme">
                <?php else : ?>
                    <div class="recent-causes-single">
                <?php endif; ?>

                <?php while ($row = mysqli_fetch_assoc($result)) : ?>
                    <?php $event_images = json_decode($row['event_images'], true); ?>
                    <div class="item event-card-upcoming wow fadeInUp" data-wow-delay="0.15s">
                        <div class="thumb">
                            <a href="#">
                                <?php if (!empty($event_images)) : ?>
                                    <div style="display: flex; flex-wrap: wrap; gap: 5px;">
                                        <?php foreach ($event_images as $image) : ?>
                                            <?php $image_path = "uploads/" . basename($image); ?>
                                            <img src="<?php echo htmlspecialchars($image_path); ?>" alt="Event Image" width="400" height="400"
                                                style="object-fit: cover; border-radius: 5px;">
                                        <?php endforeach; ?>
                                    </div>
                                <?php else : ?>
                                    <img src="assets/img/default-event.jpg" alt="No Image Available" width="400" height="400"
                                        style="object-fit: cover; border-radius: 5px;">
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
                <?php endwhile; ?>

                </div> <!-- Close either carousel or single container -->
            </div>
        </div>
    </div>
<?php endif; ?> <!-- Completely hide section if no events -->
            </div>

    </div>

    <!-- End Recent Causes Area -->

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
    <?php if (!empty($event_images)) : ?>
        <div class="event-carousel owl-carousel owl-theme">
            <?php foreach ($event_images as $image) : ?>
                <?php $image_path = "uploads/" . basename($image); ?>
                <img src="<?php echo $image_path; ?>" alt="Event Image"
                    style="width: 400px; height: 300px; object-fit: cover; border-radius: 8px;">
            <?php endforeach; ?>
        </div>
    <?php else : ?>
        <img src="assets/img/default-event.jpg" alt="No Image Available"
            style="width: 300px; height: 300px; object-fit: cover; border-radius: 8px;">
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
    <!-- Ensure Owl Carousel is only initialized when more than one event exists -->
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            let eventCount = <?php echo $eventCount ?? 0; ?>;
            if (eventCount > 1) {
                $(".recent-causes-carousel").owlCarousel({
                    loop: true,
                    margin: 10,
                    nav: true,
                    dots: true,
                    autoplay: true,
                    responsive: {
                        0: {
                            items: 1
                        },
                        600: {
                            items: 2
                        },
                        1000: {
                            items: 3
                        }
                    }
                });
            }

            $(".event-carousel").owlCarousel({
                loop: true,
                margin: 10,
                autoplay: true,
                items: 1
            });
        });
    </script>
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