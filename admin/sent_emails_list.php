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
include 'include/conn.php';
?>

<body>
    <!-- Begin page -->
    <div class="wrapper">

        <?php include 'sidebar.php'; ?>

        <div class="content-page">
            <div class="content">
                <div class="row" style="padding-top: 50px">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="header-title">Sent Emails</h4>
                                <a href="send_email.php" align="right" class="btn btn-primary">Send New Email</a>
                            </div>
                            <div class="card-body">
                                <table id="basic-datatable" class="table table-striped dt-responsive nowrap w-100">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Recipients</th>
                                            <th>Subject</th>
                                            <th>Body</th>
                                            <th>Attachment</th>
                                            <th>Date Sent</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $query = "SELECT * FROM sent_emails ORDER BY id DESC";
                                        $result = mysqli_query($conn, $query);
                                        $count = 1;

                                        while ($row = mysqli_fetch_assoc($result)) {
                                        ?>
                                            <tr>
                                                <td><?php echo $count++; ?></td>
                                                <td style="white-space: normal; word-wrap: break-word; max-width: 200px;"><?php echo htmlspecialchars($row['recipients']); ?></td>
                                                <td style="white-space: normal; word-wrap: break-word; max-width: 200px;"><?php echo htmlspecialchars($row['subject']); ?></td>
                                                <td style="white-space: normal; word-wrap: break-word; max-width: 400px;"><?php echo $row['body']; ?></td>
                                                <td><?php echo htmlspecialchars($row['attachment'] ? $row['attachment'] : 'None'); ?></td>
                                                <td><?php echo htmlspecialchars($row['sent_at']); ?></td>
                                            </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div> <!-- end card body-->
                        </div> <!-- end card -->
                    </div><!-- end col-->
                </div> <!-- end row-->
            </div> <!-- content -->

            <!-- Footer Start -->
            <?php include 'footer.php'; ?>
            <!-- end Footer -->
        </div>
    </div>
    <!-- END wrapper -->

    <script src="assets/js/vendor.min.js"></script>
    <script src="assets/vendor/datatables.net/js/jquery.dataTables.min.js"></script>
    <script src="assets/vendor/datatables.net-bs5/js/dataTables.bootstrap5.min.js"></script>
    <script src="assets/vendor/datatables.net-responsive/js/dataTables.responsive.min.js"></script>
    <script src="assets/vendor/datatables.net-responsive-bs5/js/responsive.bootstrap5.min.js"></script>
    <script src="assets/js/pages/datatable.init.js"></script>
    <script src="assets/js/app.min.js"></script>
</body>
</html> 