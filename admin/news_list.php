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
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-12">
                            <div class="page-title-box d-flex justify-content-between align-items-center">
                                <h4 class="page-title">News & Media</h4>
                                <a href="add_news.php" class="btn btn-primary">
                                    <i class="ri-add-circle-line"></i> Add News/Blog
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">All Posts</h4>
                                    <p class="text-muted mb-0">Manage news, blog posts, and reports</p>
                                </div>
                                <div class="card-body">
                                    <?php
                                    if (isset($_GET['success'])) {
                                        echo '<div class="alert alert-success alert-dismissible fade show" role="alert">';
                                        echo htmlspecialchars($_GET['success']);
                                        echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
                                        echo '</div>';
                                    }
                                    if (isset($_GET['error'])) {
                                        echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">';
                                        echo htmlspecialchars($_GET['error']);
                                        echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
                                        echo '</div>';
                                    }
                                    ?>

                                    <table id="news-datatable" class="table table-striped dt-responsive nowrap w-100">
                                        <thead>
                                            <tr>
                                                <th>Type</th>
                                                <th>Title</th>
                                                <th>Author</th>
                                                <th>Published Date</th>
                                                <th>Status</th>
                                                <th>Created</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $query = "SELECT * FROM news_media ORDER BY created_at DESC";
                                            $result = mysqli_query($conn, $query);

                                            if (mysqli_num_rows($result) > 0) {
                                                while ($row = mysqli_fetch_assoc($result)) {
                                                    echo "<tr>";
                                                    
                                                    // Type badge
                                                    $typeClass = $row['type'] == 'news' ? 'bg-info' : ($row['type'] == 'blog' ? 'bg-success' : 'bg-warning');
                                                    echo "<td><span class='badge {$typeClass}'>" . ucfirst($row['type']) . "</span></td>";
                                                    
                                                    echo "<td><strong>" . htmlspecialchars($row['title']) . "</strong></td>";
                                                    echo "<td>" . htmlspecialchars($row['author'] ?? 'N/A') . "</td>";
                                                    echo "<td>" . (!empty($row['published_date']) ? date('M d, Y', strtotime($row['published_date'])) : 'N/A') . "</td>";
                                                    
                                                    // Status badge
                                                    $statusClass = $row['status'] == 'published' ? 'bg-success' : ($row['status'] == 'draft' ? 'bg-warning' : 'bg-secondary');
                                                    echo "<td><span class='badge {$statusClass}'>" . ucfirst($row['status']) . "</span></td>";
                                                    
                                                    echo "<td>" . date('M d, Y', strtotime($row['created_at'])) . "</td>";
                                                    echo "<td>";
                                                    echo "<a href='edit_news.php?id=" . $row['id'] . "' class='btn btn-sm btn-primary me-1'>";
                                                    echo "<i class='ri-edit-line'></i> Edit";
                                                    echo "</a>";
                                                    echo "<a href='include/delete_news.php?id=" . $row['id'] . "' class='btn btn-sm btn-danger' onclick='return confirm(\"Are you sure you want to delete this post?\");'>";
                                                    echo "<i class='ri-delete-bin-line'></i> Delete";
                                                    echo "</a>";
                                                    echo "</td>";
                                                    echo "</tr>";
                                                }
                                            } else {
                                                echo "<tr><td colspan='7' class='text-center'>No posts found. <a href='add_news.php'>Create your first post</a></td></tr>";
                                            }
                                            mysqli_close($conn);
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?php include 'footer.php'; ?>
        </div>
    </div>

    <script src="assets/js/vendor.min.js"></script>
    <script src="assets/vendor/datatables.net/js/jquery.dataTables.min.js"></script>
    <script src="assets/vendor/datatables.net-bs5/js/dataTables.bootstrap5.min.js"></script>
    <script src="assets/vendor/datatables.net-responsive/js/dataTables.responsive.min.js"></script>
    <script src="assets/vendor/datatables.net-responsive-bs5/js/responsive.bootstrap5.min.js"></script>
    <script src="assets/js/pages/datatable.init.js"></script>
    <script src="assets/js/app.min.js"></script>

    <script>
        $(document).ready(function() {
            $('#news-datatable').DataTable({
                responsive: true,
                order: [[5, 'desc']] // Sort by created date descending
            });
        });
    </script>
</body>
</html>

