<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: auth-login.php");
    exit();
}

include 'include/conn.php';
include 'header.php';

// Default date range (last 30 days)
$start_date_str = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date_str = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Sanitize and validate dates
$start_date = date('Y-m-d', strtotime($start_date_str));
$end_date = date('Y-m-d', strtotime($end_date_str));

// Validate dates are valid
if (!$start_date || !$end_date || $start_date === '1970-01-01' || $end_date === '1970-01-01') {
    $start_date = date('Y-m-d', strtotime('-30 days'));
    $end_date = date('Y-m-d');
}

// Prepare date parameters for queries
$start_datetime = $start_date . ' 00:00:00';
$end_datetime = $end_date . ' 23:59:59';

// Fetch data based on date range using prepared statements
$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM events WHERE event_date BETWEEN ? AND ?");
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$total_events = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

$total_upcoming = $conn->query("SELECT COUNT(*) AS total FROM upcoming WHERE event_date >= CURDATE()")->fetch_assoc()['total']; // Upcoming is always from today

$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM registrations WHERE created_at BETWEEN ? AND ?");
$stmt->bind_param("ss", $start_datetime, $end_datetime);
$stmt->execute();
$total_registers = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM sent_emails WHERE sent_at BETWEEN ? AND ?");
$stmt->bind_param("ss", $start_datetime, $end_datetime);
$stmt->execute();
$total_sent_emails = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();


// Active/Expired calculation needs to be adjusted based on the full list, not the date range of registration
$active_subscribers = 0;
$expired_subscribers = 0;
$result_all_members = $conn->query("SELECT created_at, payment_duration FROM registrations");
while ($member = $result_all_members->fetch_assoc()) {
    $reg_date = new DateTime($member['created_at']);
    $duration = $member['payment_duration'];
    $expiry_date = clone $reg_date;
    if (strpos($duration, 'Year') !== false) {
        $years = (int) filter_var($duration, FILTER_SANITIZE_NUMBER_INT);
        if ($years > 0) {
            $expiry_date->modify("+$years year");
        }
    }
    if ($expiry_date > new DateTime()) {
        $active_subscribers++;
    } else {
        $expired_subscribers++;
    }
}

// Fetch data for tables using prepared statements
$stmt = $conn->prepare("SELECT fullname, email, phone, created_at FROM registrations WHERE created_at BETWEEN ? AND ? ORDER BY created_at DESC");
$stmt->bind_param("ss", $start_datetime, $end_datetime);
$stmt->execute();
$members_data = $stmt->get_result();
$stmt->close();

$stmt = $conn->prepare("SELECT event_header, event_date FROM events WHERE event_date BETWEEN ? AND ? ORDER BY event_date DESC");
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$events_data = $stmt->get_result();
$stmt->close();

$stmt = $conn->prepare("SELECT recipients, subject, sent_at FROM sent_emails WHERE sent_at BETWEEN ? AND ? ORDER BY sent_at DESC");
$stmt->bind_param("ss", $start_datetime, $end_datetime);
$stmt->execute();
$emails_data = $stmt->get_result();
$stmt->close();

?>

<!DOCTYPE html>
<html lang="en">
<!-- Daterangepicker css -->
<link href="assets/vendor/daterangepicker/daterangepicker.css" rel="stylesheet" type="text/css" />
<!-- Datatables css -->
<link href="assets/vendor/datatables.net-bs5/css/dataTables.bootstrap5.min.css" rel="stylesheet" type="text/css" />
<link href="assets/vendor/datatables.net-responsive-bs5/css/responsive.bootstrap5.min.css" rel="stylesheet" type="text/css" />

<body>
    <!-- Begin page -->
    <div class="wrapper">
        <?php include 'sidebar.php'; ?>
        <div class="content-page">
            <div class="content">
                <div class="container-fluid">
                     <div class="d-none d-print-block text-center mb-4">
                        <h1>Ethio Social Work Report</h1>
                    </div>
                    <!-- start page title -->
                    <div class="row d-print-none">
                        <div class="col-12">
                            <div class="page-title-box">
                                <h4 class="page-title">Reports</h4>
                            </div>
                        </div>
                    </div>
                    <!-- end page title -->

                    <!-- Date Range Filter -->
                    <div class="row d-print-none">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <form class="row gy-2 gx-2 align-items-center">
                                        <div class="col-auto">
                                            <label for="date-range" class="visually-hidden">Date Range</label>
                                            <input type="text" class="form-control" id="date-range" name="daterange" value="<?php echo date('m/d/Y', strtotime($start_date)) . ' - ' . date('m/d/Y', strtotime($end_date)); ?>">
                                            <input type="hidden" id="start_date" name="start_date" value="<?php echo $start_date; ?>">
                                            <input type="hidden" id="end_date" name="end_date" value="<?php echo $end_date; ?>">
                                        </div>
                                        <div class="col-auto">
                                            <button type="submit" class="btn btn-primary">Apply</button>
                                        </div>
                                         <div class="col-auto">
                                            <button type="button" class="btn btn-light" onclick="window.print()">Print Report</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Stats -->
                    <div class="row">
                         <div class="col-lg-4 col-sm-6">
                            <div class="card widget-flat text-bg-primary">
                                <div class="card-body">
                                    <h6 class="text-uppercase mt-0" title="Customers">New Memberships In Period</h6>
                                    <h2 class="my-2"><?php echo $total_registers; ?></h2>
                                </div>
                            </div>
                        </div>
                         <div class="col-lg-4 col-sm-6">
                            <div class="card widget-flat text-bg-info">
                                <div class="card-body">
                                    <h6 class="text-uppercase mt-0" title="Customers">Events in Period</h6>
                                    <h2 class="my-2"><?php echo $total_events; ?></h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4 col-sm-6">
                            <div class="card widget-flat text-bg-secondary">
                                <div class="card-body">
                                    <h6 class="text-uppercase mt-0" title="Customers">Emails Sent in Period</h6>
                                    <h2 class="my-2"><?php echo $total_sent_emails; ?></h2>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Datatables -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">New Members Report</h4>
                                </div>
                                <div class="card-body">
                                    <table id="members-datatable" class="table table-striped dt-responsive nowrap w-100">
                                        <thead>
                                            <tr>
                                                <th>Full Name</th>
                                                <th>Email</th>
                                                <th>Phone</th>
                                                <th>Registration Date</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while($row = $members_data->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($row['fullname']); ?></td>
                                                <td><?php echo htmlspecialchars($row['email']); ?></td>
                                                <td><?php echo htmlspecialchars($row['phone']); ?></td>
                                                <td><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                                            </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                     <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Events Report</h4>
                                </div>
                                <div class="card-body">
                                    <table id="events-datatable" class="table table-striped dt-responsive nowrap w-100">
                                        <thead>
                                            <tr>
                                                <th>Event Title</th>
                                                <th>Event Date</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while($row = $events_data->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($row['event_header']); ?></td>
                                                <td><?php echo date('M d, Y', strtotime($row['event_date'])); ?></td>
                                            </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Sent Emails Report</h4>
                                </div>
                                <div class="card-body">
                                    <table id="emails-datatable" class="table table-striped dt-responsive nowrap w-100">
                                        <thead>
                                            <tr>
                                                <th>Recipients</th>
                                                <th>Subject</th>
                                                <th>Date Sent</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while($row = $emails_data->fetch_assoc()): ?>
                                            <tr>
                                                <td style="white-space: normal; word-wrap: break-word;"><?php echo htmlspecialchars($row['recipients']); ?></td>
                                                <td><?php echo htmlspecialchars($row['subject']); ?></td>
                                                <td><?php echo date('M d, Y H:i', strtotime($row['sent_at'])); ?></td>
                                            </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
            <!-- Footer -->
            <?php include 'footer.php'; ?>
        </div>
    </div>
    <!-- END wrapper -->

    <!-- Vendor js -->
    <script src="assets/js/vendor.min.js"></script>
    <script src="assets/vendor/daterangepicker/moment.min.js"></script>
    <script src="assets/vendor/daterangepicker/daterangepicker.js"></script>
    <!-- Datatables js -->
    <script src="assets/vendor/datatables.net/js/jquery.dataTables.min.js"></script>
    <script src="assets/vendor/datatables.net-bs5/js/dataTables.bootstrap5.min.js"></script>
    <script src="assets/vendor/datatables.net-responsive/js/dataTables.responsive.min.js"></script>
    <script src="assets/vendor/datatables.net-responsive-bs5/js/responsive.bootstrap5.min.js"></script>
    <script src="assets/js/app.min.js"></script>

    <script>
    $(document).ready(function() {
        $('#date-range').daterangepicker({
            startDate: moment('<?php echo $start_date; ?>'),
            endDate: moment('<?php echo $end_date; ?>'),
        }, function(start, end, label) {
            $('#start_date').val(start.format('YYYY-MM-DD'));
            $('#end_date').val(end.format('YYYY-MM-DD'));
        });

        $('#members-datatable').DataTable();
        $('#events-datatable').DataTable();
        $('#emails-datatable').DataTable();
    });
    </script>
    <style>
        @media print {
            body * {
                visibility: hidden;
            }
            .content-page, .content-page * {
                visibility: visible;
            }
            .content-page {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
            }
            .leftside-menu, .navbar-custom, footer, .page-title-box, .card form {
                display: none !important;
            }
            .card {
                border: none !important;
                box-shadow: none !important;
            }
            .content-page {
                padding-top: 0 !important;
            }
            .card {
                border: 1px solid #dee2e6 !important;
                box-shadow: none !important;
            }
             .widget-flat {
                 background-color: #f8f9fa !important;
                 color: #000 !important;
            }
             .widget-flat h2, .widget-flat h6 {
                 color: #000 !important;
            }
            .dataTables_length, .dataTables_filter, .dataTables_info, .dataTables_paginate {
                display: none !important;
            }
        }
    </style>
</body>
</html> 