<!DOCTYPE html>
<html lang="en">
<?php
include 'head.php';
include 'include/config.php';
include 'include/partners_functions.php';

// Load dynamic team members for "Our Team Members" section
$teamMembers = [];
$teamQuery = "SELECT name, role, bio, photo FROM about_team_members WHERE status = 'active' ORDER BY sort_order ASC, id ASC";
if (isset($conn) && $conn && ($result = mysqli_query($conn, $teamQuery))) {
  while ($row = mysqli_fetch_assoc($result)) {
    $teamMembers[] = $row;
  }
  mysqli_free_result($result);
}
?>

<body>
  <?php include 'header-v1.2.php'; ?>

  <!-- End Header -->

  <!-- About page intro / breadcrumb -->
  <section class="about-hero-intro" aria-label="About ESWPA">
    <div class="about-hero-intro__bg"></div>
    <div class="container about-hero-intro__inner">
      <div class="row align-center">
        <div class="col-lg-7">
          <div class="about-hero-intro__content">
            <span class="about-hero-intro__eyebrow">About ESWPA</span>
            <h1>Professional home for social workers in Ethiopia.</h1>
            <p>
              The Ethiopian Social Work Professional Association (ESWPA) brings together practitioners,
              educators, and students to strengthen social work education, ethics, and practice nationwide.
            </p>
            <ul class="about-hero-intro__highlights">
              <li>Established in 2016 &amp; accredited in 2017</li>
              <li>Registered national professional association</li>
              <li>Working across universities, hospitals, courts &amp; communities</li>
            </ul>
            <div class="about-hero-intro__breadcrumbs">
              <a href="index.php"><i class="fas fa-home"></i> Home</a>
              <span>/</span>
              <span>About</span>
            </div>
          </div>
        </div>
        <div class="col-lg-5 d-none d-lg-block">
          <div class="about-hero-intro__badge">
            <span>ESWPA</span>
            <p>Uniting and elevating social work professionals across Ethiopia.</p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Star About Area
    ============================================= -->
  <div class="about-area default-padding about-page-hero">
    <div class="container">
      <div class="row align-center wow fadeInUp" data-wow-delay="0.1s">
        <div class="col-lg-6">
          <div class="thumb">
            <img src="assets/img/content/homemission.jpg" alt="ESWPA work in the community">
            <div class="overlay">
              <h4>Established 2016 · Accredited 2017</h4>
            </div>
          </div>
        </div>

        <div class="col-lg-6 info">
          <h5>Who we are</h5>
          <h2 class="text-blur">About ESWPA</h2>
          <h3 class="area-title">
            A professional home for social workers across Ethiopia.
          </h3>
          <p>
            The Ethiopian Social Work Professional Association (ESWPA) was born from the vision of Addis Ababa University
            social work students and pioneers such as Guled Abdi, the association&apos;s first president, together with
            other devoted social workers.
          </p>
          <p>
            Today ESWPA unites professionals, educators, and students to strengthen ethical, evidence-informed practice,
            and to give social workers a strong, independent voice in policy and public life.
          </p>
          <div class="about-page-hero-badges">
            <span>National association</span>
            <span>Multi-sector collaboration</span>
            <span>Profession-led</span>
          </div>
          <a class="btn circle btn-theme border btn-md" href="sign-up.php">Become a Member</a>
        </div>
      </div>
    </div>
  </div>
  <!-- End About Area -->

  <!-- Start What We Do / Core Values
    ============================================= -->
  <div class="we-do-area half-bg defaultA-padding bg-gray about-core-values">
    <div class="container">
      <div class="row">
        <div class="col-lg-8 offset-lg-2 wow fadeInUp" data-wow-delay="0.1s">
          <div class="site-heading text-center">
            <h5>Core Values</h5>
            <h2>
              What guides social work practice at ESWPA.
            </h2>
            <div class="heading-divider"></div>
          </div>
        </div>
      </div>
    </div>
    <div class="container">
      <div class="wedo-items text-center">
        <div class="row">
          <!-- Single Item -->
          <div class="single-item col-lg-3 col-md-6 wow fadeInUp" data-wow-delay="0.1s">
            <div class="item about-core-card">
              <div class="about-core-card__icon">
                <i class="flaticon-charity"></i>
              </div>
              <h4>Service to Community</h4>
              <p>
                We stand with individuals and communities, especially those who are marginalized or vulnerable.
              </p>
            </div>
          </div>

          <div class="single-item col-lg-3 col-md-6 wow fadeInUp" data-wow-delay="0.16s">
            <div class="item about-core-card">
              <div class="about-core-card__icon">
                <i class="fas fa-user-shield"></i>
              </div>
              <h4>Respect &amp; Dignity</h4>
              <p>
                Every person has inherent worth, and social workers commit to treating all with respect and care.
              </p>
            </div>
          </div>

          <div class="single-item col-lg-3 col-md-6 wow fadeInUp" data-wow-delay="0.22s">
            <div class="item about-core-card">
              <div class="about-core-card__icon">
                <i class="fas fa-users"></i>
              </div>
              <h4>Professional Dedication</h4>
              <p>
                ESWPA encourages active engagement, continuous learning, and strong ethical commitment to the profession.
              </p>
            </div>
          </div>

          <div class="single-item col-lg-3 col-md-6 wow fadeInUp" data-wow-delay="0.28s">
            <div class="item about-core-card">
              <div class="about-core-card__icon">
                <i class="fas fa-award"></i>
              </div>
              <h4>Competence &amp; Quality</h4>
              <p>
                Competent practice is essential to ensure individuals, families, and communities receive high-quality support.
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <!-- End What We Do -->

  <!-- Start Our Mission 
    ============================================= -->
  <div class="mission-area half-bg bg-gray about-mission">
    <div class="container">
      <div class="row align-center wow fadeInUp" data-wow-delay="0.15s">
        <div class="col-lg-6 thumb-box">
          <div class="thumb">
            <div class="thumb-1">
              <img src="assets/img/mission.png" alt="Thumb">
            </div>
          </div>
        </div>
        <div class="col-lg-6 info">
          <h5>Our Mission</h5>
          <h2 class="text-blur">Mission</h2>
          <p class="missionP">
            ESWPA's mission is to unite and strengthen social work professionals in Ethiopia, support their development, and promote standardized social work practices across the country. As a national association, ESWPA advocates for inclusive social policies that advance equality, social justice, and the overall well-being of Ethiopian society.
          </p>

        </div>
      </div>
    </div>
  </div>
  <!-- End Our Mission -->

  <div class="mission-area half-bg bg-gray about-vision">
    <div class="container">
      <div class="row align-center wow fadeInUp" data-wow-delay="0.2s">
        <div class="col-lg-6 info">
          <h5>Our vision</h5>
          <h2 class="text-blur">Vison</h2>
          <p class="missionP">
            ESWPA envisions becoming the leading voice for social workers in Ethiopia,
            recognized for its commitment to professional excellence. <br>
            We aim to
            establish and uphold national standards for social work practice,
            education, and licensing, fostering positive social change at individual,
            family, and community levels. Through mobilization, education, and advocacy,
            ESWPA strives to contribute effectively to Ethiopia's socio-economic development. </p>
        </div>
        <div class="col-lg-6 thumb-box">
          <div class="thumb">
            <div class="thumb-1">
              <img src="assets/img/vison.jpg" alt="Thumb">
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Start Volunteer Area / Team
    ============================================= -->
  <div class="volunteer-area default-padding bottom-less about-team">
    <div class="container">
      <div class="site-heading text-center">
        <h2>Our Team Members</h2>
        <div class="heading-divider"></div>
      </div>
      <div class="volunteer-items text-center">
        <div class="row">
          <?php if (!empty($teamMembers)): ?>
            <?php foreach ($teamMembers as $index => $member): 
              $delay = 0.1 + ($index * 0.05);
              $photo = !empty($member['photo']) ? $member['photo'] : 'assets/img/members/default.jpg';
            ?>
              <div class="single-item col-lg-3 col-md-6 col-6 wow fadeInUp" data-wow-delay="<?php echo number_format($delay, 2); ?>s">
                <div class="item">
                  <div class="thumb">
                    <img src="<?php echo htmlspecialchars($photo); ?>" alt="Team member">
                  </div>
                  <div class="info">
                    <h4><?php echo htmlspecialchars($member['name']); ?></h4>
                    <?php if (!empty($member['role'])): ?>
                      <span><?php echo htmlspecialchars($member['role']); ?></span>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          <?php else: ?>
            <p class="text-muted">Team members will be updated soon.</p>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
  <!-- End Volunteer Area -->
  <!-- Start Clients Area 
    ============================================= -->
  <div class="clients-area default-padding about-partners">
    <div class="container">
      <div class="row align-center">
        <div class="col-lg-5 info">
          <h2 class="about-partners__title">
            Our Partner Companies
          </h2>
          <p>
            We collaborate with various organizations and institutions to enhance social work practice and
            create positive impact in Ethiopian communities.
          </p>
        </div>
        <div class="col-lg-7 item-box">
          <div class="partners-grid">
            <?php
            $partners = getPartners($conn);
            displayPartnersGrid($partners);
            ?>
          </div>
        </div>
      </div>
    </div>
  </div>
  <!-- End Clients Area -->

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

  <style>
    .volunteer-items .thumb img {
      width: 100%;
      height: 300px;
      object-fit: contain;
    }
  </style>

</body>

</html>