<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: auth-login.php");
    exit();
}?>

<!DOCTYPE html>
<html lang="en">

<?php
include 'header.php';
?>
<!-- Quill css -->
<link href="assets/vendor/quill/quill.core.css" rel="stylesheet" type="text/css" />
<link href="assets/vendor/quill/quill.snow.css" rel="stylesheet" type="text/css" />

<body>
    <!-- Begin page -->
    <div class="wrapper">

        <?php
        include 'sidebar.php';
        ?>

        <!-- ============================================================== -->
        <!-- Start Page Content here -->
        <!-- ============================================================== -->

        <div class="content-page">
            <div class="content">

                <!-- Start Content-->
                <div class="container-fluid">

                    <!-- start page title -->
                    <div class="row">
                        <div class="col-12">
                            <div class="page-title-box">
                                <h4 class="page-title"> Add Event</h4>
                            </div>
                        </div>
                    </div>
                    <!-- end page title -->

                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Add event</h4>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-lg-6">
                                            <form action="include/send_event.php" method="POST" enctype="multipart/form-data">
                                                <div class="mb-3">
                                                    <label class="form-label">Event Type</label>
                                                    <select name="event_type" class="form-control" required>
                                                        <option value="events">Regular Event</option>
                                                        <option value="upcoming">Upcoming Event</option>
                                                    </select>
                                                </div>

                                                <div class="mb-3">
                                                    <label class="form-label">Date</label>
                                                    <input type="date" name="event_date" class="form-control" required>
                                                </div>

                                                <div class="mb-3">
                                                    <label class="form-label">Event Title</label>
                                                    <input type="text" name="event_header" class="form-control" required>
                                                </div>

                                                <div class="mb-3">
                                                    <label class="form-label">Event Description</label>
                                                    <div id="event-description-editor" style="height: 200px;"></div>
                                                    <textarea name="event_description" id="event_description" style="display: none;" required></textarea>
                                                    <small class="text-muted">Use the editor above to format your event description. HTML formatting is supported.</small>
                                                </div>

                                                <div class="mb-3">
                                                    <label class="form-label">Upload Event Images</label>
                                                    <input type="file" name="event_images[]" class="form-control" multiple accept="image/png, image/jpeg, image/jpg, image/webp" required>
                                                    <small class="text-muted">Only PNG, JPG, and WEBP formats are allowed. You can upload up to 3 images.</small>
                                                </div>

                                                <div class="mb-3 form-check">
                                                    <input type="checkbox" class="form-check-input" id="send_newsletter" name="send_newsletter" value="1">
                                                    <label class="form-check-label" for="send_newsletter">Send as Newsletter</label>
                                                </div>

                                                <button class="btn btn-primary" type="submit" name="submit">Submit Event</button>
                                            </form>

                                        </div> <!-- end col -->
                                    </div>
                                    <!-- end row-->
                                </div> <!-- end card-body -->
                            </div> <!-- end card -->
                        </div><!-- end col -->
                    </div><!-- end row -->

                </div> <!-- container -->

            </div> <!-- content -->

            <!-- Footer Start -->
            <?php
            include 'footer.php';
            ?>
            <!-- end Footer -->

        </div>

        <!-- ============================================================== -->
        <!-- End Page content -->
        <!-- ============================================================== -->

    </div>






    <!-- END wrapper -->
    <!-- Vendor js -->
    <script src="assets/js/vendor.min.js"></script>

    <!--  Select2 Plugin Js -->
    <script src="assets/vendor/select2/js/select2.min.js"></script>

    <!-- Daterangepicker Plugin js -->
    <script src="assets/vendor/daterangepicker/moment.min.js"></script>
    <script src="assets/vendor/daterangepicker/daterangepicker.js"></script>

    <!-- Bootstrap Datepicker Plugin js -->
    <script src="assets/vendor/bootstrap-datepicker/js/bootstrap-datepicker.min.js"></script>

    <!-- Bootstrap Timepicker Plugin js -->
    <script src="assets/vendor/bootstrap-timepicker/js/bootstrap-timepicker.min.js"></script>

    <!-- Input Mask Plugin js -->
    <script src="assets/vendor/jquery-mask-plugin/jquery.mask.min.js"></script>

    <!-- Bootstrap Touchspin Plugin js -->
    <script src="assets/vendor/bootstrap-touchspin/jquery.bootstrap-touchspin.min.js"></script>

    <!-- Bootstrap Maxlength Plugin js -->
    <script src="assets/vendor/bootstrap-maxlength/bootstrap-maxlength.min.js"></script>

    <!-- Typehead Plugin js -->
    <script src="assets/vendor/handlebars/handlebars.min.js"></script>
    <script src="assets/vendor/typeahead.js/typeahead.bundle.min.js"></script>

    <!-- Flatpickr Timepicker Plugin js -->
    <script src="assets/vendor/flatpickr/flatpickr.min.js"></script>

    <!-- Typehead Demo js -->
    <script src="assets/js/pages/typehead.init.js"></script>

    <!-- Timepicker Demo js -->
    <script src="assets/js/pages/timepicker.init.js"></script>

    <!-- Quill js -->
    <script src="assets/vendor/quill/quill.min.js"></script>
    <!-- App js -->
    <script src="assets/js/app.min.js"></script>

    <script>
        $(document).ready(function() {
            // Initialize Quill editor for event description
            var quill = new Quill('#event-description-editor', {
                theme: 'snow',
                modules: {
                    toolbar: [
                        [{ 'font': [] }, { 'size': [] }],
                        [ 'bold', 'italic', 'underline', 'strike' ],
                        [{ 'color': [] }, { 'background': [] }],
                        [{ 'script': 'super' }, { 'script': 'sub' }],
                        [{ 'header': [false, 1, 2, 3, 4, 5, 6] }, 'blockquote', 'code-block' ],
                        [{ 'list': 'ordered' }, { 'list': 'bullet' }, { 'indent': '-1' }, { 'indent': '+1' }],
                        [ 'direction', { 'align': [] }],
                        [ 'link', 'image', 'video' ],
                        [ 'clean' ]
                    ]
                }
            });

            // Update hidden textarea on text change
            quill.on('text-change', function() {
                $('#event_description').val(quill.root.innerHTML);
            });
            
            // Handle form submission
            $('form').on('submit', function(e) {
                // Update textarea with editor content before submit
                $('#event_description').val(quill.root.innerHTML);
                
                // Validate content is not empty
                if (quill.getText().trim().length === 0) {
                    e.preventDefault();
                    alert('Please enter an event description.');
                    return false;
                }
            });
        });
    </script>
</body>

</html>