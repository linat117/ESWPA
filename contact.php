<!DOCTYPE html>
<html lang="en">

<?php
include 'head.php'?>

<body>



    <!-- Start Header Top 
    ============================================= -->
   <?php 
   include 'header-v1.2.php';
   ?>
    <!-- End Header -->

    <!-- Contact hero / breadcrumb -->
    <section class="membership-hero-intro" aria-label="Contact ESWPA">
        <div class="membership-hero-intro__bg"></div>
        <div class="container membership-hero-intro__inner">
            <div class="row align-center">
                <div class="col-lg-7">
                    <div class="membership-hero-intro__content">
                        <span class="membership-hero-intro__eyebrow">Contact</span>
                        <h1>Get in touch with ESWPA.</h1>
                        <p>
                            Reach out with questions, partnership ideas, or feedback about social work practice,
                            education, and membership in Ethiopia.
                        </p>
                        <ul class="membership-hero-intro__highlights">
                            <li>Located at Addis Ababa, Stadium, Amhara Bank Building</li>
                            <li>Contact via phone, email, or the form below</li>
                        </ul>
                        <div class="membership-hero-intro__breadcrumbs">
                            <a href="index.php"><i class="fas fa-home"></i> Home</a>
                            <span>/</span>
                            <span>Contact</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Start Contact 
    ============================================= -->
    <div class="contact-form-area default-padding">
        <div class="container">
            <div class="row align-center">
                <!-- Start Contact Form -->
                <div class="col-lg-7 contact-forms">
                    <div class="content">
                        <div class="heading">
                            <h3>Let’s make the world better, together</h3>
                        </div>
                        <form action="assets/mail/contact.php" method="POST" class="contact-form">
                            <div class="row">
                                <div class="col-lg-12">
                                    <div class="form-group">
                                        <input class="form-control" id="name" name="name" placeholder="Name" type="text">
                                        <span class="alert-error"></span>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-lg-12">
                                    <div class="form-group">
                                        <input class="form-control" id="email" name="email" placeholder="Email*" type="email">
                                        <span class="alert-error"></span>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-lg-12">
                                    <div class="form-group">
                                        <input class="form-control" id="phone" name="phone" placeholder="Phone" type="text">
                                        <span class="alert-error"></span>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-lg-12">
                                    <div class="form-group comments">
                                        <textarea class="form-control" id="comments" name="comments" placeholder="Tell Us About Project *"></textarea>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-lg-12">
                                    <button type="submit" name="submit" id="submit">
                                        Send Message <i class="fa fa-paper-plane"></i>
                                    </button>
                                </div>
                            </div>
                            <!-- Alert Message -->
                            <div class="col-md-12 alert-notification">
                                <div id="message" class="alert-msg"></div>
                            </div>
                        </form>
                    </div>
                </div>
                <!-- End Contact Form -->
                <div class="col-lg-5 address-info">
                    <div class="address-items">
                        <ul>
                            <li>
                                <i class="fas fa-map-marker-alt"></i>
                                <p>
                                    Our Location
                                    <span>Addis ababa, Stadium, Amhara Bank Building</span>
                                </p>
                            </li>
                            <li>
                                <i class="fas fa-envelope-open"></i>
                                <p>
                                    Send Us Mail
                                    <span>info@ethiosocialworker.org</span>
                                </p>
                            </li>
                            <li>
                                <i class="fas fa-mobile-alt"></i>
                                <p>
                                    Call Us
                                    <span>0951671067</span>
                                    <span>0951572057</span>
                                </p>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Contact -->

    <!-- Start Google Maps 
    ============================================= -->
    <div class="maps-area">
        <div class="google-maps">
        <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d12026.947279767719!2d38.73551867084327!3d9.011676540239895!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x164b855234891dad%3A0xaeb993c09217fc14!2zQW1oYXJhIEJhbmsgUy5DIEhRL-GLqOGKoOGIm-GIqyDhiaPhipXhiq0g4YqgLuGImyDhi4vhipMg4YiY4Yi14Yiq4Yur4Ymk4Ym1!5e1!3m2!1sen!2set!4v1739257580476!5m2!1sen!2set" " width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
        </div>
    </div>
    <!-- End Google Maps -->

    <!-- Start Footer 
    ============================================= -->
   <?php
    include 'footer.php'; 
    ?> <!-- End Footer -->

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