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

        <!-- ============================================================== -->
        <!-- Start Page Content here -->
        <!-- ============================================================== -->

        <div class="content-page">
            <div class="content">


                <div class="row" style="padding-top: 50px">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <table id="basic-datatable" class="table table-striped dt-responsive nowrap w-100">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Event Title</th>
                                            <th>Event Description</th>
                                            <th>Event Images</th>
                                            <th>Date</th>
                                            <th>Delete</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $query = "SELECT * FROM upcoming ORDER BY id DESC";
                                        $result = mysqli_query($conn, $query);
                                        $count = 1;

                                        while ($row = mysqli_fetch_assoc($result)) {
                                            $event_id = $row['id'];
                                            $event_title = $row['event_header'];
                                            $event_description = $row['event_description'];
                                            $event_date = $row['event_date'];
                                            $event_images = json_decode($row['event_images'], true); // Decode JSON image paths
                                        ?>
                                            <tr>
                                                <td><?php echo $count++; ?></td>
                                                <td style="white-space: normal; word-wrap: break-word; max-width: 200px;">
                                                    <p><?php echo nl2br(htmlspecialchars($event_title)); ?></p>
                                                </td>
                                                <td style="white-space: normal; word-wrap: break-word; max-width: 800px;">
                                                    <p><?php echo nl2br(htmlspecialchars($event_description)); ?></p>
                                                </td>
                                                <td>
                                                    <?php if (!empty($event_images)) : ?>
                                                        <div style="display: flex; flex-wrap: wrap; gap: 5px;">
                                                            <?php foreach ($event_images as $image) : ?>
                                                                <?php $image_path = "../uploads/" . basename($image); ?>
                                                                <img src="<?php echo $image_path; ?>" alt="Event Image" width="100" height="100"
                                                                    style="object-fit: cover; border-radius: 5px;">
                                                            <?php endforeach; ?>
                                                        </div>
                                                    <?php else : ?>
                                                        No Image
                                                    <?php endif; ?>
                                                </td>

                                                <td><?php echo htmlspecialchars($event_date); ?></td>
                                                <td>
                                                    <button class="btn btn-danger btn-sm delete-event"
                                                        data-id="<?php echo $event_id; ?>"
                                                        data-type="upcoming"
                                                        data-page="upcoming_list.php">
                                                        Delete
                                                    </button>

                                                </td>
                                            </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div> <!-- end card body-->
                        </div> <!-- end card -->
                    </div><!-- end col-->
                </div> <!-- end row-->

            </div> <!-- container -->
        </div> <!-- content -->

        <!-- Footer Start -->
        <?php include 'footer.php'; ?>
        <!-- end Footer -->

    </div>

    <!-- ============================================================== -->
    <!-- End Page content -->
    <!-- ============================================================== -->

    </div>
    <!-- END wrapper -->

    <script src="assets/js/vendor.min.js"></script>

    <!-- Datatables js -->
    <script src="assets/vendor/datatables.net/js/jquery.dataTables.min.js"></script>
    <script src="assets/vendor/datatables.net-bs5/js/dataTables.bootstrap5.min.js"></script>
    <script src="assets/vendor/datatables.net-responsive/js/dataTables.responsive.min.js"></script>
    <script src="assets/vendor/datatables.net-responsive-bs5/js/responsive.bootstrap5.min.js"></script>
    <script src="assets/vendor/datatables.net-fixedcolumns-bs5/js/fixedColumns.bootstrap5.min.js"></script>
    <script src="assets/vendor/datatables.net-fixedheader/js/dataTables.fixedHeader.min.js"></script>
    <script src="assets/vendor/datatables.net-buttons/js/dataTables.buttons.min.js"></script>
    <script src="assets/vendor/datatables.net-buttons-bs5/js/buttons.bootstrap5.min.js"></script>
    <script src="assets/vendor/datatables.net-buttons/js/buttons.html5.min.js"></script>
    <script src="assets/vendor/datatables.net-buttons/js/buttons.flash.min.js"></script>
    <script src="assets/vendor/datatables.net-buttons/js/buttons.print.min.js"></script>
    <script src="assets/vendor/datatables.net-keytable/js/dataTables.keyTable.min.js"></script>
    <script src="assets/vendor/datatables.net-select/js/dataTables.select.min.js"></script>

    <!-- Datatable Demo Aapp js -->
    <script src="assets/js/pages/datatable.init.js"></script>

    <!-- App js -->
    <script src="assets/js/app.min.js"></script>

    <!-- Delete Event Script -->
    <script>
        $(document).ready(function() {
            $(".delete-event").click(function() {
                var eventId = $(this).data("id"); // Get event ID
                var eventType = $(this).data("type"); // Get event type (events/upcoming)
                var pageName = $(this).data("page"); // Get page name (for reload)

                if (confirm("Are you sure you want to delete this event?")) {
                    $.ajax({
                        url: "include/delete_event.php", // Single delete file
                        type: "POST",
                        data: {
                            id: eventId,
                            type: eventType
                        }, // Send both ID and type
                        success: function(response) {
                            alert(response);
                            window.location.href = pageName; // Reload correct page
                        },
                        error: function() {
                            alert("Error deleting event.");
                        }
                    });
                }
            });
        });
    </script>


</body>

</html>