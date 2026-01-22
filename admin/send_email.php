<?php
session_start();
include 'include/conn.php'; 

if (!isset($_SESSION['user_id'])) {
    header("Location: auth-login.php");
    exit();
}

// Fetch emails for the multi-select dropdown
$emails = [];
$sql = "SELECT email FROM registrations WHERE email IS NOT NULL AND email != ''";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $emails[] = $row['email'];
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<?php include 'header.php'; ?>

<!-- Select2 css -->
<link href="assets/vendor/select2/css/select2.min.css" rel="stylesheet" type="text/css" />
<!-- Quill css -->
<link href="assets/vendor/quill/quill.core.css" rel="stylesheet" type="text/css" />
<link href="assets/vendor/quill/quill.snow.css" rel="stylesheet" type="text/css" />
<!-- Jquery Toast css -->
<link href="assets/vendor/jquery-toast-plugin/jquery.toast.min.css" rel="stylesheet" type="text/css" />


<body>
    <!-- Begin page -->
    <div class="wrapper">

        <?php include 'sidebar.php'; ?>

        <div class="content-page">
            <div class="content">

                <!-- Start Content-->
                <div class="container-fluid">

                    <!-- start page title -->
                    <div class="row">
                        <div class="col-12">
                            <div class="page-title-box">
                                <h4 class="page-title">Send New Email</h4>
                            </div>
                        </div>
                    </div>
                    <!-- end page title -->

                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Compose Email</h4>
                                </div>
                                <div class="card-body">
                                    <form action="include/bulk_email_sender.php" method="POST" enctype="multipart/form-data">
                                        <div class="mb-3">
                                            <label for="select-emails" class="form-label">Recipients</label>
                                            <select class="form-control select2" id="select-emails" name="recipients[]" multiple="multiple" data-toggle="select2" required>
                                                <?php foreach ($emails as $email): ?>
                                                    <option value="<?php echo htmlspecialchars($email); ?>"><?php echo htmlspecialchars($email); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="mb-3">
                                            <label for="subject" class="form-label">Subject</label>
                                            <input type="text" id="subject" name="subject" class="form-control" required>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Body</label>
                                            <div id="snow-editor" style="height: 300px;"></div>
                                            <input type="hidden" name="email_body" id="email_body">
                                        </div>

                                        <div class="mb-3">
                                            <label for="attachment" class="form-label">Attachment</label>
                                            <input type="file" id="attachment" name="attachment" class="form-control">
                                        </div>

                                        <button type="submit" name="submit" class="btn btn-primary">Send Email</button>
                                    </form>
                                </div> <!-- end card-body -->
                            </div> <!-- end card -->
                        </div><!-- end col -->
                    </div><!-- end row -->

                </div> <!-- container -->

            </div> <!-- content -->

            <!-- Footer Start -->
            <?php include 'footer.php'; ?>
            <!-- end Footer -->

        </div>
    </div>
    <!-- END wrapper -->

    <!-- Vendor js -->
    <script src="assets/js/vendor.min.js"></script>
    <!-- Select2 js -->
    <script src="assets/vendor/select2/js/select2.min.js"></script>
    <!-- Quill js -->
    <script src="assets/vendor/quill/quill.min.js"></script>
    <!-- Jquery Toast js -->
    <script src="assets/vendor/jquery-toast-plugin/jquery.toast.min.js"></script>
    <!-- Toastr init js-->
    <script src="assets/js/pages/toastr.init.js"></script>
    <!-- App js -->
    <script src="assets/js/app.min.js"></script>

    <script>
        $(document).ready(function() {
            var quill = new Quill('#snow-editor', {
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

            quill.on('text-change', function() {
                $('#email_body').val(quill.root.innerHTML);
            });
            
            // Handle form submission
            $('form').on('submit', function() {
                $('#email_body').val(quill.root.innerHTML);
            });

            <?php if (isset($_SESSION['toast_message'])): ?>
                $.NotificationApp.send("Success!", "<?php echo $_SESSION['toast_message']; ?>", "top-right", "rgba(0,0,0,0.2)", "success");
                <?php unset($_SESSION['toast_message']); ?>
            <?php endif; ?>
        });
    </script>

</body>
</html> 