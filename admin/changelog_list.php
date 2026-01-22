<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: auth-login.php");
    exit();
}
include 'header.php';
include 'include/conn.php';
?>

<body>
    <div class="wrapper">
        <?php include 'sidebar.php'; ?>
        <div class="content-page">
            <div class="content">
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h4 class="header-title">Project Changelog</h4>
                                    <?php if ($_SESSION['user_id'] == 1) : ?>
                                        <a href="add_changelog.php" class="btn btn-primary">Add New Entry</a>
                                    <?php endif; ?>
                                </div>
                                <p class="text-muted mb-0">
                                    A record of all major feature updates, enhancements, and bug fixes.
                                </p>
                            </div>
                            <div class="card-body">
                                <table id="scroll-horizontal-datatable" class="table table-striped w-100 nowrap">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Version</th>
                                            <th>Type</th>
                                            <th>Title</th>
                                            <th>Description</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $sql = "SELECT * FROM changelogs ORDER BY change_date DESC, version DESC";
                                        $result = mysqli_query($conn, $sql);
                                        if (mysqli_num_rows($result) > 0) {
                                            while ($row = mysqli_fetch_assoc($result)) {
                                                $badge_class = 'bg-primary';
                                                if ($row['type'] == 'New Feature') $badge_class = 'bg-success';
                                                if ($row['type'] == 'Enhancement') $badge_class = 'bg-info';
                                                if ($row['type'] == 'Bug Fix') $badge_class = 'bg-danger';

                                                echo "<tr>";
                                                echo "<td>" . date("Y-m-d", strtotime($row['change_date'])) . "</td>";
                                                echo "<td>" . htmlspecialchars($row['version']) . "</td>";
                                                echo "<td><span class='badge " . $badge_class . "'>" . htmlspecialchars($row['type']) . "</span></td>";
                                                echo "<td>" . htmlspecialchars($row['title']) . "</td>";
                                                echo "<td style='white-space: pre-wrap; word-wrap: break-word;'>" . $row['description'] . "</td>";
                                                echo "</tr>";
                                            }
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div> <!-- end card-body -->
                        </div> <!-- end card -->
                    </div><!-- end col -->
                </div> <!-- end row -->
            </div> <!-- content -->
            <?php include 'footer.php'; ?>
        </div>
    </div>
    <script src="assets/js/vendor.min.js"></script>
    <script src="assets/vendor/datatables.net/js/jquery.dataTables.min.js"></script>
    <script src="assets/vendor/datatables.net-bs5/js/dataTables.bootstrap5.min.js"></script>
    <script src="assets/vendor/datatables.net-responsive/js/dataTables.responsive.min.js"></script>
    <script src="assets/js/pages/datatable.init.js"></script>
    <script src="assets/js/app.min.js"></script>
</body>
</html> 