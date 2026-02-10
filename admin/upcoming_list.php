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
                                <!-- Desktop / Tablet Table View -->
                                <table id="basic-datatable" class="table table-striped dt-responsive nowrap w-100 d-none d-md-table">
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

                                <!-- Mobile Card View -->
                                <div class="d-block d-md-none mt-3">
                                    <?php
                                    // Separate query for mobile view (simple and clear)
                                    $mobileResult = mysqli_query($conn, $query);
                                    $mobileCount = 1;

                                    while ($row = mysqli_fetch_assoc($mobileResult)) {
                                        $event_id = $row['id'];
                                        $event_title = $row['event_header'];
                                        $event_description = $row['event_description'];
                                        $event_date = $row['event_date'];
                                        $event_images = json_decode($row['event_images'], true);
                                    ?>
                                        <div class="card mb-2 mobile-event-card">
                                            <div class="card-body d-flex justify-content-between align-items-center">
                                                <div class="d-flex align-items-center gap-2">
                                                    <span class="badge bg-primary rounded-pill"><?php echo $mobileCount++; ?></span>
                                                    <span class="fw-semibold text-truncate" style="max-width: 180px;">
                                                        <?php echo htmlspecialchars($event_title); ?>
                                                    </span>
                                                </div>
                                                <button type="button"
                                                    class="btn btn-link text-muted p-0 event-more"
                                                    aria-label="View event detail">
                                                    <i class="ri-more-2-fill" style="font-size: 1.4rem;"></i>
                                                </button>
                                            </div>

                                            <!-- Hidden full detail for modal -->
                                            <div class="d-none event-detail-content">
                                                <h5 class="mb-2"><?php echo htmlspecialchars($event_title); ?></h5>
                                                <p class="mb-2" style="white-space: pre-wrap;">
                                                    <?php echo htmlspecialchars($event_description); ?>
                                                </p>

                                                <p class="mb-2">
                                                    <strong>Date:</strong>
                                                    <?php echo htmlspecialchars($event_date); ?>
                                                </p>

                                                <div class="mb-3">
                                                    <strong>Images:</strong><br>
                                                    <?php if (!empty($event_images)) : ?>
                                                        <div class="d-flex flex-wrap gap-2 mt-1">
                                                            <?php foreach ($event_images as $image) : ?>
                                                                <?php $image_path = "../uploads/" . basename($image); ?>
                                                                <img src="<?php echo $image_path; ?>" alt="Event Image"
                                                                    style="width: 90px; height: 90px; object-fit: cover; border-radius: 5px;">
                                                            <?php endforeach; ?>
                                                        </div>
                                                    <?php else : ?>
                                                        <span>No Image</span>
                                                    <?php endif; ?>
                                                </div>

                                                <button class="btn btn-danger btn-sm delete-event"
                                                    data-id="<?php echo $event_id; ?>"
                                                    data-type="upcoming"
                                                    data-page="upcoming_list.php">
                                                    Delete
                                                </button>
                                            </div>
                                        </div>
                                    <?php } ?>
                                </div>
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

    <!-- Event Detail Modal (used on mobile) -->
    <div class="modal fade" id="eventDetailModal" tabindex="-1" aria-labelledby="eventDetailModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="eventDetailModalLabel">Event Detail</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Filled dynamically -->
                </div>
            </div>
        </div>
    </div>

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

    <!-- Delete Event + Mobile Modal Script -->
    <script>
        $(document).ready(function() {
            // Delete handler (desktop + mobile)
            $(document).on("click", ".delete-event", function() {
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

            // Open modal with full details (mobile)
            $(document).on("click", ".event-more", function() {
                var card = $(this).closest(".mobile-event-card");
                var contentHtml = card.find(".event-detail-content").html();

                $("#eventDetailModal .modal-body").html(contentHtml);

                if (typeof bootstrap !== "undefined" && bootstrap.Modal) {
                    var detailModal = new bootstrap.Modal(document.getElementById("eventDetailModal"));
                    detailModal.show();
                } else {
                    $("#eventDetailModal").modal("show");
                }
            });
        });
    </script>


</body>

</html>