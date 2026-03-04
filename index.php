<!DOCTYPE html>
<html lang="en">

<?php
include 'head.php';
include 'include/config.php';

$query = "SELECT * FROM upcoming ORDER BY event_date ASC";
$result = mysqli_query($conn, $query);
?>

<body class="hibiscus">

    <!-- Header 
    ============================================= -->
    <?php
    include 'header-v1.2.php';
    ?>
    <!-- End Header -->

    <!-- Start Banner 
    ============================================= -->
    <div class="banner-area top-pad-80 text-center content-less text-large">
        <div id="bootcarousel" class="carousel text-light slide carousel-fade animate_text" data-ride="carousel" data-interval="4000">

            <!-- Wrapper for slides -->
            <div class="carousel-inner carousel-zoom">
                <div class="carousel-item active">
                    <div class="slider-thumb bg-cover" style="background-image: url(assets/img/content/bg.jpg);"></div>
                    <div class="box-table">
                        <div class="box-cell shadow dark">
                            <div class="container">
                                <div class="row">
                                    <div class="col-lg-8 offset-lg-2">
                                        <div class="content">
                                            <h3 class="less">Ethiopia</h3>
                                            <h2 data-animation="animated slideInRight">Empowering Change<strong>Inspiring Progress</strong></h2>
                                            <a data-animation="animated fadeInUp" class="btn circle btn-light border btn-md" href="about.php">Discover More</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="carousel-item">
                    <div class="slider-thumb bg-cover" style="background-image: url(assets/img/content/donationBG.jpg);"></div>
                    <div class="box-table">
                        <div class="box-cell shadow dark">
                            <div class="container">
                                <div class="row">
                                    <div class="col-lg-8 offset-lg-2">
                                        <div class="content">
                                            <h3>Ethiopia</h3>
                                            <h2 data-animation="animated slideInRight">Shaping a stronger<strong>More inclusive tomorrow</strong></h2>
                                            <a data-animation="animated fadeInUp" class="btn circle btn-light border btn-md" href="about.php">Discover More</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- End Wrapper for slides -->

            <!-- Left and right controls -->
            <a class="left carousel-control light" href="#bootcarousel" data-slide="prev">
                <i class="fa fa-angle-left"></i>
                <span class="sr-only">Previous</span>
            </a>
            <a class="right carousel-control light" href="#bootcarousel" data-slide="next">
                <i class="fa fa-angle-right"></i>
                <span class="sr-only">Next</span>
            </a>


        </div>
    </div>
    <!-- End Banner -->

    <!-- Start Target Area (keep hero, modernize below)
    ============================================= -->
    <div class="about-area default-padding home-about">
        <div class="container">
            <div class="row align-center wow fadeInUp" data-wow-delay="0.1s">
                <div class="col-lg-6">
                    <div class="thumb">
                        <img src="assets/img/ethio social work.jpg" alt="Thumb">
                        <!-- <div class="overlay">
                <h4>Established in 2016 & accredited in 2017</h4>
              </div> -->
                    </div>
                </div>

                <div class="col-lg-6 info">
                    <h2 class="area-title">
                        Non-governmental,<br />
                        Non-profit organization
                    </h2>
                    <p>
                        The Ethiopian Social Work Professional Association (ESWPA) is a
                        non-governmental, non-profit organization established in 2016,
                        accredited in 2017, and re-registered under the Charities and
                        Societies Proclamation No. 1133/2009. With a national reach, ESWPA
                        is based in Addis Ababa and is committed to improving the
                        wellbeing and professional excellence of social workers in
                        Ethiopia.
                        <br> <br>
                        ESWPA plays a crucial role in professionalizing social work education and practice.
                        The association works in partnership with diverse sectors to respond to crises, develop social work standards,
                        and enhance service delivery in various settings, including universities, hospitals, courts, and correctional services.
                    </p>
                    <div class="home-about-actions">
                        <a href="about.php" class="btn circle btn-theme border btn-sm">Mission</a>
                        <a href="about.php" class="btn circle btn-theme border btn-sm">Vision</a>
                        <a href="about.php" class="btn circle btn-theme border btn-sm">Values</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Target Area -->
    <!-- Start Volunteer / CTA
    ============================================= -->
    <div class="volunteer-area text-center home-cta wow fadeInUp" data-wow-delay="0.15s">
        <!-- Fixed Shape -->
        <div class="shape-bottom">
            <img src="assets/img/shape/7.png" alt="Shape">
        </div>
        <!-- Fixed Shape -->
        <div class="container">
            <div class="row">
                <div class="col-lg-8 offset-lg-2">
                    <h5>Become a Member</h5>
                    <h2 class="text-blur">Member</h2>
                    <h2 class="area-title"> Let's unite and strengthen social work professionals in Ethiopia.</h2>
                    <p>
                        We aim to establish and uphold national standards for social work practice, education, and licensing, fostering positive social change at individual, family, and community levels.
                    </p>


                </div>
            </div>
        </div>
    </div>
    <!-- End Volunteer -->
    <!-- Start Stay With Us Area / Membership types
    ============================================= -->
    <div class="stay-us-area bottom-less home-memberships">
        <div class="container">
            <div class="box-items text-center wow fadeInUp" data-wow-delay="0.2s">
                <div class="row">
                    <!-- Single Item -->
                    <div class="single-item col-lg-4 col-md-6 wow fadeInUp" data-wow-delay="0.25s">
                        <div class="item">
                            <i class="fas fa-user-plus"></i>
                            <h4><a href="membership.php">Individual Membership</a></h4>
                            <p>
                                Join as an individual and contribute to a thriving professional community.
                            </p>
                            <a href="membership.php"><i class="fas fa-angle-right"></i></a>
                        </div>
                    </div>
                    <!-- End Single Item -->
                    <!-- Single Item -->
                    <div class="single-item col-lg-4 col-md-6 wow fadeInUp" data-wow-delay="0.35s">
                        <div class="item">
                            <i class="flaticon-charity"></i>
                            <h4><a href="membership.php">Become a Member</a></h4>
                            <p>
                                Join us in our mission to empower and uplift communities in need.
                            </p>
                            <a href="membership.php"><i class="fas fa-angle-right"></i></a>
                        </div>
                    </div>
                    <!-- End Single Item -->
                    <!-- Single Item -->
                    <div class="single-item col-lg-4 col-md-6 wow fadeInUp" data-wow-delay="0.45s">
                        <div class="item">
                            <i class="fas fa-handshake"></i>
                            <h4><a href="membership.php">Organizational Membership</a></h4>
                            <p>
                                Collaborate as to support initiatives, networking, and industry development.
                            </p>
                            <a href="membership.php"><i class="fas fa-angle-right"></i></a>
                        </div>
                    </div>
                    <!-- End Single Item -->
                </div>
            </div>
        </div>
    </div>
    <!-- End Stay With Us Area -->

    <!-- Star Recent events Area
    ============================================= -->
    <div class="recent-causes-area carousel-shadow causes-area default-padding home-events wow fadeInUp" data-wow-delay="0.2s">
        <div class="container">
            <div class="heading-left">
                <div class="row">
                    <div class="col-lg-6 left-info">
                        <h5>Upcoming Events</h5>
                        <h2>Stay updated with our upcoming events.</h2>
                    </div>
                    <div class="col-lg-6 right-info">
                        <p>Join us in our upcoming events to support our cause and make a difference in the community.</p>
                        <a class="btn circle btn-md btn-gradient wow fadeInUp" href="events.php">View All <i class="fas fa-angle-right"></i></a>
                    </div>
                </div>
            </div>
        </div>
        <?php

        if (mysqli_num_rows($result) > 0) :
        ?>
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
                                    <div class="item">
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
                                            <p><?php echo htmlspecialchars($row['event_description']); ?></p>
                                        </div>
                                    </div>
                                <?php endwhile; ?>

                                </div> <!-- Close either carousel or single container -->
                            </div>
                    </div>
                </div>
            <?php endif; ?> <!-- Completely hide section if no events -->


            </div>


            <!-- End Recent Causes Area -->

            <!-- Start Clients Area 
    ============================================= -->
            <div class="clients-area default-padding-bottom home-partners">
                <div class="container">
                    <div class="row align-center">
                        <div class="col-lg-5 info">
                            <h2 style="color: #6B87B5; font-size: 30px;">
                                Our Partner Companies<br />
                            </h2>
                            <p>
                                We collaborate with various organizations and institutions to enhance social work practice and
                                create positive impact in Ethiopian communities.
                            </p>
                        </div>
                        <div class="col-lg-7 item-box wow fadeInUp" data-wow-delay="0.25s">
                            <div class="client-items client-carousel owl-carousel owl-theme">
                                <div class="item">
                                    <img src="assets/img/partner2.png" alt="Thumb" />
                                </div>
                                <div class="item">
                                    <img src="assets/img/partner1.png" alt="Thumb" />
                                </div>
                                <div class="item"style="padding-top:30px;">
                                    <img src="assets/img/ehrc.png" alt="Thumb" />
                                </div>
                                <div class="item"style="padding-top:10px;">
                                    <img src="assets/img/aau.jpeg" alt="Thumb" style="height: 100px;">
                                </div>
                                <div class="item"style="padding-top:30px;">
                                    <img src="assets/img/ephi.png" alt="Thumb" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- End Clients Area -->


            <?php
            include 'footer.php';
            ?>
            <!-- End Footer -->

            <!-- Email Subscription Popup -->
            <?php include 'include/subscription_popup.php'; ?>
            <!-- End Email Subscription Popup -->

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