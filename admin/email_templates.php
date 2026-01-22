<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: auth-login.php");
    exit();
}

include 'include/conn.php';
include 'header.php';

// Get all templates
$templatesQuery = "SELECT * FROM email_templates ORDER BY content_type, name";
$templatesResult = mysqli_query($conn, $templatesQuery);
$templates = [];
while ($row = mysqli_fetch_assoc($templatesResult)) {
    $templates[] = $row;
}
?>

<body>
    <div class="wrapper">
        <?php include 'sidebar.php'; ?>
        <div class="content-page">
            <div class="content">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-12">
                            <div class="page-title-box">
                                <div class="page-title-right">
                                    <ol class="breadcrumb m-0">
                                        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                                        <li class="breadcrumb-item active">Email Templates</li>
                                    </ol>
                                </div>
                                <h4 class="page-title">Email Templates</h4>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-12">
                            <a href="add_email_template.php" class="btn btn-primary">
                                <i class="ri-add-circle-line"></i> Add New Template
                            </a>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped table-bordered" id="templates-datatable">
                                            <thead>
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Name</th>
                                                    <th>Content Type</th>
                                                    <th>Subject</th>
                                                    <th>Status</th>
                                                    <th>Created</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($templates as $template): ?>
                                                <tr>
                                                    <td><?php echo $template['id']; ?></td>
                                                    <td><?php echo htmlspecialchars($template['name']); ?></td>
                                                    <td><span class="badge bg-info"><?php echo ucfirst($template['content_type']); ?></span></td>
                                                    <td><?php echo htmlspecialchars(substr($template['subject'], 0, 50)) . '...'; ?></td>
                                                    <td>
                                                        <?php if ($template['is_active']): ?>
                                                        <span class="badge bg-success">Active</span>
                                                        <?php else: ?>
                                                        <span class="badge bg-secondary">Inactive</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?php echo date('Y-m-d', strtotime($template['created_at'])); ?></td>
                                                    <td>
                                                        <a href="edit_email_template.php?id=<?php echo $template['id']; ?>" class="btn btn-sm btn-primary">
                                                            <i class="ri-edit-line"></i> Edit
                                                        </a>
                                                        <button class="btn btn-sm btn-danger delete-template" data-id="<?php echo $template['id']; ?>">
                                                            <i class="ri-delete-bin-line"></i> Delete
                                                        </button>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php include 'footer.php'; ?>
        </div>
    </div>

    <!-- Vendor js -->
    <script src="assets/js/vendor.min.js"></script>
    
    <!-- Datatables js -->
    <script src="assets/js/jquery.dataTables.min.js"></script>
    <script src="assets/js/dataTables.bootstrap5.min.js"></script>
    
    <!-- App js -->
    <script src="assets/js/app.min.js"></script>

    <script>
        $(document).ready(function() {
            $('#templates-datatable').DataTable({
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

            $('.delete-template').on('click', function() {
                const id = $(this).data('id');
                if (confirm('Are you sure you want to delete this template?')) {
                    window.location.href = 'include/delete_email_template.php?id=' + id;
                }
            });
        });
    </script>
</body>
</html>

