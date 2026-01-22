<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: auth-login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<?php
include 'header.php';
include 'include/conn.php';

// Get statistics
$totalQuery = "SELECT COUNT(*) as total FROM email_subscribers";
$totalResult = mysqli_query($conn, $totalQuery);
$total = mysqli_fetch_assoc($totalResult)['total'];

$activeQuery = "SELECT COUNT(*) as active FROM email_subscribers WHERE status = 'active'";
$activeResult = mysqli_query($conn, $activeQuery);
$active = mysqli_fetch_assoc($activeResult)['active'];

$unsubscribedQuery = "SELECT COUNT(*) as unsubscribed FROM email_subscribers WHERE status = 'unsubscribed'";
$unsubscribedResult = mysqli_query($conn, $unsubscribedQuery);
$unsubscribed = mysqli_fetch_assoc($unsubscribedResult)['unsubscribed'];
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

                <!-- Start Content -->
                <div class="container-fluid">

                    <!-- start page title -->
                    <div class="row">
                        <div class="col-12">
                            <div class="page-title-box">
                                <div class="page-title-right">
                                    <ol class="breadcrumb m-0">
                                        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                                        <li class="breadcrumb-item active">Email Subscribers</li>
                                    </ol>
                                </div>
                                <h4 class="page-title">Email Subscribers</h4>
                            </div>
                        </div>
                    </div>
                    <!-- end page title -->

                    <!-- Statistics Cards -->
                    <div class="row">
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title">Total Subscribers</h5>
                                    <h2 class="text-primary"><?php echo number_format($total); ?></h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title">Active</h5>
                                    <h2 class="text-success"><?php echo number_format($active); ?></h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title">Unsubscribed</h5>
                                    <h2 class="text-danger"><?php echo number_format($unsubscribed); ?></h2>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Subscribers List</h4>
                                    <p class="text-muted mb-0">
                                        Manage email newsletter subscribers.
                                    </p>
                                    <div class="mt-3">
                                        <a href="include/export_subscribers.php" class="btn btn-primary btn-sm">
                                            <i class="fas fa-download"></i> Export to CSV
                                        </a>
                                    </div>
                                </div>
                                <div class="card-body">

                                    <table id="subscribers-datatable" class="table table-striped w-100 nowrap">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Email</th>
                                                <th>Name</th>
                                                <th>Status</th>
                                                <th>Source</th>
                                                <th>Subscribed Date</th>
                                                <th>Unsubscribed Date</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            // Filter by status if provided
                                            $statusFilter = $_GET['status'] ?? '';
                                            if ($statusFilter == 'active') {
                                                $sql = "SELECT * FROM email_subscribers WHERE status = 'active' ORDER BY subscribed_at DESC";
                                            } elseif ($statusFilter == 'unsubscribed') {
                                                $sql = "SELECT * FROM email_subscribers WHERE status = 'unsubscribed' ORDER BY unsubscribed_at DESC";
                                            } else {
                                                $sql = "SELECT * FROM email_subscribers ORDER BY subscribed_at DESC";
                                            }
                                            $result = mysqli_query($conn, $sql);

                                            if (mysqli_num_rows($result) > 0) {
                                                while ($row = mysqli_fetch_assoc($result)) {
                                                    echo "<tr>";
                                                    
                                                    echo "<td>" . htmlspecialchars($row['id']) . "</td>";
                                                    
                                                    echo "<td>" . htmlspecialchars($row['email']) . "</td>";
                                                    
                                                    echo "<td>";
                                                    if (!empty($row['name'])) {
                                                        echo htmlspecialchars($row['name']);
                                                    } else {
                                                        echo "<span class='text-muted'>-</span>";
                                                    }
                                                    echo "</td>";
                                                    
                                                    echo "<td>";
                                                    if ($row['status'] == 'active') {
                                                        echo "<span class='badge bg-success'>Active</span>";
                                                    } elseif ($row['status'] == 'unsubscribed') {
                                                        echo "<span class='badge bg-danger'>Unsubscribed</span>";
                                                    } else {
                                                        echo "<span class='badge bg-warning'>" . htmlspecialchars(ucfirst($row['status'])) . "</span>";
                                                    }
                                                    echo "</td>";
                                                    
                                                    echo "<td>";
                                                    echo "<span class='badge bg-info'>" . htmlspecialchars(ucfirst($row['source'])) . "</span>";
                                                    echo "</td>";
                                                    
                                                    echo "<td>";
                                                    if (!empty($row['subscribed_at'])) {
                                                        echo date('M d, Y', strtotime($row['subscribed_at']));
                                                    } else {
                                                        echo "-";
                                                    }
                                                    echo "</td>";
                                                    
                                                    echo "<td>";
                                                    if (!empty($row['unsubscribed_at'])) {
                                                        echo date('M d, Y', strtotime($row['unsubscribed_at']));
                                                    } else {
                                                        echo "<span class='text-muted'>-</span>";
                                                    }
                                                    echo "</td>";
                                                    
                                                    echo "<td>";
                                                    if ($row['status'] == 'active') {
                                                        echo "<button class='btn btn-sm btn-danger unsubscribe-btn' data-id='" . $row['id'] . "' data-email='" . htmlspecialchars($row['email']) . "'>";
                                                        echo "<i class='fas fa-ban'></i> Unsubscribe";
                                                        echo "</button>";
                                                    } else {
                                                        echo "<button class='btn btn-sm btn-success resubscribe-btn' data-id='" . $row['id'] . "' data-email='" . htmlspecialchars($row['email']) . "'>";
                                                        echo "<i class='fas fa-check'></i> Resubscribe";
                                                        echo "</button>";
                                                    }
                                                    echo "</td>";
                                                    
                                                    echo "</tr>";
                                                }
                                            } else {
                                                echo "<tr><td colspan='8' class='text-center'>No subscribers found.</td></tr>";
                                            }
                                            ?>
                                        </tbody>
                                    </table>

                                </div> <!-- end card body-->
                            </div> <!-- end card -->
                        </div> <!-- end col-->
                    </div> <!-- end row-->

                </div> <!-- container -->

            </div> <!-- content -->

            <?php include 'footer.php'; ?>

        </div>

        <!-- ============================================================== -->
        <!-- End Page content -->
        <!-- ============================================================== -->


    </div>
    <!-- END wrapper -->

    <?php include 'footer.php'; ?>

    <!-- Vendor js -->
    <script src="assets/js/vendor.min.js"></script>

    <!-- Datatables js -->
    <script src="assets/js/jquery.dataTables.min.js"></script>
    <script src="assets/js/dataTables.bootstrap5.min.js"></script>

    <!-- Datatable Init js -->
    <script>
        $(document).ready(function() {
            $('#subscribers-datatable').DataTable({
                scrollX: true,
                language: {
                    paginate: {
                        previous: "<i class='mdi mdi-chevron-left'>",
                        next: "<i class='mdi mdi-chevron-right'>"
                    }
                },
                drawCallback: function() {
                    $(".dataTables_paginate > .pagination").addClass("pagination-rounded");
                }
            });

            // Unsubscribe handler
            $('#subscribers-datatable').on('click', '.unsubscribe-btn', function() {
                const id = $(this).data('id');
                const email = $(this).data('email');
                
                if (confirm('Are you sure you want to unsubscribe ' + email + '?')) {
                    $.ajax({
                        url: 'include/unsubscribe_subscriber.php',
                        method: 'POST',
                        data: {
                            id: id,
                            action: 'unsubscribe'
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                location.reload();
                            } else {
                                alert('Error: ' + response.message);
                            }
                        },
                        error: function() {
                            alert('An error occurred. Please try again.');
                        }
                    });
                }
            });

            // Resubscribe handler
            $('#subscribers-datatable').on('click', '.resubscribe-btn', function() {
                const id = $(this).data('id');
                const email = $(this).data('email');
                
                if (confirm('Are you sure you want to resubscribe ' + email + '?')) {
                    $.ajax({
                        url: 'include/unsubscribe_subscriber.php',
                        method: 'POST',
                        data: {
                            id: id,
                            action: 'resubscribe'
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                location.reload();
                            } else {
                                alert('Error: ' + response.message);
                            }
                        },
                        error: function() {
                            alert('An error occurred. Please try again.');
                        }
                    });
                }
            });
        });
    </script>

</body>
</html>

