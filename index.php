<!DOCTYPE html>
<html lang="en">

<?php
include 'head.php';
include 'include/config.php';
include 'include/partners_functions.php';

$query = "SELECT * FROM upcoming ORDER BY event_date ASC";
$result = mysqli_query($conn, $query);
?>

<body class="hibiscus">
    <link href="assets/css/home-v2.css" rel="stylesheet" />

    <!-- Header 
    ============================================= -->
    <?php
    include 'header-v1.2.php';
    ?>
    <!-- End Header -->

    <!-- Start Hero - New modern layout
    ============================================= -->
    <section class="home-v2-hero" aria-label="ESWPA hero">
        <div class="home-v2-hero__bg"></div>
        <div class="home-v2-container home-v2-hero__inner">
            <div class="home-v2-hero__grid">
                <div class="home-v2-hero__content home-v2-anim-in-left">
                    <span class="home-v2-label">Ethiopian Social Work Professionals</span>
                    <h1 class="home-v2-hero__title">
                        Empowering social work<br />
                        across Ethiopia.
                    </h1>
                    <p class="home-v2-hero__text">
                        ESWPA connects and supports social work professionals, strengthens education and practice,
                        and advances social justice for individuals, families, and communities.
                    </p>
                    <div class="home-v2-hero__actions">
                        <a href="membership.php" class="home-v2-btn home-v2-btn--primary home-v2-btn--arrow">
                            Become a member <i class="fas fa-arrow-right"></i>
                        </a>
                        <a href="about.php" class="home-v2-btn home-v2-btn--outline">
                            Learn more
                        </a>
                    </div>
                    <div class="home-v2-hero__meta">
                        <span>Since 2016</span>
                        <span>National association</span>
                        <span>Profession-led</span>
                    </div>
                </div>
                <div class="home-v2-hero__visual home-v2-anim-in-right">
                    <div class="home-v2-hero__card">
                        <h2>Upcoming focus</h2>
                        <p>Strengthening social work standards, ethics, and education in every region.</p>
                        <a href="events.php" class="home-v2-hero__link">
                            Explore events <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                    <div class="home-v2-hero__stat">
                        <strong>ESWPA</strong>
                        <span>Professional association</span>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- End Hero -->

    <!-- ========== NEW HOME SECTIONS (below hero) ========== -->

    <!-- About / Who we are - Editorial split -->
    <section class="home-v2-about" aria-label="About ESWPA">
        <div class="home-v2-about__bg-pattern" aria-hidden="true"></div>
        <div class="home-v2-container">
            <div class="home-v2-about__grid">
                <div class="home-v2-about__media home-v2-anim-in-left">
                    <div class="home-v2-about__img-wrap">
                        <img src="assets/img/ethio social work.jpg" alt="Ethiopian Social Work">
                        <span class="home-v2-about__badge">Since 2016</span>
                    </div>
                    <div class="home-v2-about__accent"></div>
                </div>
                <div class="home-v2-about__content home-v2-anim-in-right">
                    <span class="home-v2-label">Who we are</span>
                    <h2 class="home-v2-about__title">Non-governmental,<br>Non-profit organization</h2>
                    <div class="home-v2-about__pills">
                        <span>Est. 2016</span>
                        <span>Accredited 2017</span>
                        <span>National reach</span>
                    </div>
                    <p class="home-v2-about__lead">
                        The Ethiopian Social Work Professional Association (ESWPA) is a
                        non-governmental, non-profit organization established in 2016,
                        accredited in 2017, and re-registered under the Charities and
                        Societies Proclamation No. 1133/2009. With a national reach, ESWPA
                        is based in Addis Ababa and is committed to improving the
                        wellbeing and professional excellence of social workers in Ethiopia.
                    </p>
                    <p>
                        ESWPA plays a crucial role in professionalizing social work education and practice.
                        The association works in partnership with diverse sectors to respond to crises, develop social work standards,
                        and enhance service delivery in various settings, including universities, hospitals, courts, and correctional services.
                    </p>
                    <div class="home-v2-about__actions">
                        <a href="about.php" class="home-v2-btn home-v2-btn--outline">Mission</a>
                        <a href="about.php" class="home-v2-btn home-v2-btn--outline">Vision</a>
                        <a href="about.php" class="home-v2-btn home-v2-btn--outline">Values</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA - Bold strip -->
    <section class="home-v2-cta" aria-label="Become a member">
        <div class="home-v2-cta__bg"></div>
        <div class="home-v2-cta__glow" aria-hidden="true"></div>
        <div class="home-v2-container home-v2-cta__inner">
            <div class="home-v2-cta__glass home-v2-cta__content home-v2-anim-scale">
                <span class="home-v2-cta__label">Become a Member</span>
                <h2 class="home-v2-cta__title">Let's unite and strengthen social work professionals in Ethiopia.</h2>
                <p class="home-v2-cta__text">
                    We aim to establish and uphold national standards for social work practice, education, and licensing, fostering positive social change at individual, family, and community levels.
                </p>
                <a href="membership.php" class="home-v2-btn home-v2-btn--primary home-v2-cta__btn">Join ESWPA</a>
            </div>
        </div>
    </section>

    <!-- Membership types - Bento cards -->
    <section class="home-v2-memberships" aria-label="Membership options">
        <div class="home-v2-container">
            <div class="home-v2-memberships__head home-v2-anim-in-up">
                <span class="home-v2-label">Ways to join</span>
                <h2 class="home-v2-memberships__title">Choose your path</h2>
            </div>
            <div class="home-v2-memberships__grid">
                <a href="membership.php" class="home-v2-card home-v2-card--featured home-v2-anim-in-up" style="--delay: 0">
                    <span class="home-v2-card__num">01</span>
                    <span class="home-v2-card__icon"><i class="fas fa-user-plus"></i></span>
                    <h3 class="home-v2-card__title">Individual Membership</h3>
                    <p class="home-v2-card__desc">Join as an individual and contribute to a thriving professional community.</p>
                    <span class="home-v2-card__link">Learn more <i class="fas fa-arrow-right"></i></span>
                </a>
                <a href="membership.php" class="home-v2-card home-v2-anim-in-up" style="--delay: 1">
                    <span class="home-v2-card__num">02</span>
                    <span class="home-v2-card__icon"><i class="flaticon-charity"></i></span>
                    <h3 class="home-v2-card__title">Become a Member</h3>
                    <p class="home-v2-card__desc">Join us in our mission to empower and uplift communities in need.</p>
                    <span class="home-v2-card__link">Learn more <i class="fas fa-arrow-right"></i></span>
                </a>
                <a href="membership.php" class="home-v2-card home-v2-anim-in-up" style="--delay: 2">
                    <span class="home-v2-card__num">03</span>
                    <span class="home-v2-card__icon"><i class="fas fa-handshake"></i></span>
                    <h3 class="home-v2-card__title">Organizational Membership</h3>
                    <p class="home-v2-card__desc">Collaborate to support initiatives, networking, and industry development.</p>
                    <span class="home-v2-card__link">Learn more <i class="fas fa-arrow-right"></i></span>
                </a>
            </div>
        </div>
    </section>

    <!-- Upcoming events -->
    <section class="home-v2-events" aria-label="Upcoming events">
        <div class="home-v2-container">
            <div class="home-v2-events__head">
                <div class="home-v2-events__head-left home-v2-anim-in-up">
                    <span class="home-v2-label">Upcoming Events</span>
                    <h2 class="home-v2-events__title">Stay updated with our upcoming events.</h2>
                </div>
                <div class="home-v2-events__head-right home-v2-anim-in-up">
                    <p>Join us in our upcoming events to support our cause and make a difference in the community.</p>
                    <a href="events.php" class="home-v2-btn home-v2-btn--primary home-v2-btn--arrow">View All <i class="fas fa-arrow-right"></i></a>
                </div>
            </div>
        </div>
        <?php
        $has_events = ($result && mysqli_num_rows($result) > 0);
        if ($has_events) :
        ?>
            <div class="home-v2-events__list-wrap">
                <div class="home-v2-container">
                    <?php if (mysqli_num_rows($result) > 1) : ?>
                        <div class="home-v2-events__carousel recent-causes-carousel owl-carousel owl-theme">
                    <?php else : ?>
                        <div class="home-v2-events__single recent-causes-single">
                    <?php endif; ?>
                        <?php while ($row = mysqli_fetch_assoc($result)) : ?>
                            <?php
                            $event_images = [];
                            if (!empty($row['event_images'])) {
                                $decoded = json_decode($row['event_images'], true);
                                if (is_array($decoded)) {
                                    $event_images = $decoded;
                                }
                            }
                            ?>
                            <div class="home-v2-event-card item">
                                <div class="home-v2-event-card__thumb thumb">
                                    <a href="events.php">
                                        <?php if (!empty($event_images)) : ?>
                                            <div class="home-v2-event-card__imgs">
                                                <?php foreach (array_slice($event_images, 0, 1) as $image) : ?>
                                                    <?php $image_path = "uploads/" . basename($image); ?>
                                                    <img src="<?php echo htmlspecialchars($image_path); ?>" alt="Event" />
                                                <?php endforeach; ?>
                                            </div>
                                        <?php else : ?>
                                            <img src="assets/img/default-event.jpg" alt="Event" />
                                        <?php endif; ?>
                                        <span class="home-v2-event-card__date overlay">
                                            <?php echo date("M j, Y", strtotime($row['event_date'])); ?>
                                        </span>
                                    </a>
                                </div>
                                <div class="home-v2-event-card__info info">
                                    <h4><a href="events.php"><?php echo htmlspecialchars($row['event_header']); ?></a></h4>
                                    <p><?php echo htmlspecialchars($row['event_description']); ?></p>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </section>

    <!-- Partners -->
    <section class="home-v2-partners" aria-label="Our partners">
        <div class="home-v2-partners__bg" aria-hidden="true"></div>
        <div class="home-v2-container">
            <div class="home-v2-partners__grid">
                <div class="home-v2-partners__intro home-v2-anim-in-left">
                    <span class="home-v2-label">Collaboration</span>
                    <h2 class="home-v2-partners__title">Our Partner Companies</h2>
                    <p>We collaborate with various organizations and institutions to enhance social work practice and create positive impact in Ethiopian communities.</p>
                </div>
                <div class="home-v2-partners__logos item-box home-v2-anim-in-right">
                    <div class="home-v2-partners-grid">
                        <?php
                        $partners = getPartners($conn);
                        displayHomeV2Partners($partners);
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </section>


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