<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] != 1) {
    header("Location: index.php");
    exit();
}
include 'header.php';
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
                                <h4 class="header-title">Add New Changelog Entry</h4>
                                <p class="text-muted mb-0">
                                    Use this form to add a new update to the public changelog.
                                </p>
                            </div>
                            <div class="card-body">
                                <?php if(isset($_GET['status'])): ?>
                                    <div class="alert alert-<?php echo $_GET['status'] == 'success' ? 'success' : 'danger'; ?>">
                                        <?php echo htmlspecialchars($_GET['msg']); ?>
                                    </div>
                                <?php endif; ?>
                                <form action="include/insert_changelog.php" method="POST">
                                    <div class="mb-3">
                                        <label for="version" class="form-label">Version</label>
                                        <input type="text" class="form-control" id="version" name="version" required placeholder="e.g., 1.2.0">
                                    </div>
                                    <div class="mb-3">
                                        <label for="change_date" class="form-label">Date</label>
                                        <input type="date" class="form-control" id="change_date" name="change_date" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="type" class="form-label">Change Type</label>
                                        <select class="form-select" id="type" name="type" required>
                                            <option value="New Feature">New Feature</option>
                                            <option value="Enhancement">Enhancement</option>
                                            <option value="Bug Fix">Bug Fix</option>
                                            <option value="Update">Update</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="title" class="form-label">Title</label>
                                        <input type="text" class="form-control" id="title" name="title" required placeholder="A brief summary of the change">
                                    </div>
                                    <div class="mb-3">
                                        <label for="description" class="form-label">Description</label>
                                        <textarea class="form-control" id="description" name="description" rows="5" required placeholder="Detailed description of the changes made. You can use HTML for formatting."></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-primary">Submit</button>
                                </form>
                            </div> <!-- end card-body -->
                        </div> <!-- end card-->
                    </div> <!-- end col -->
                </div> <!-- end row-->
            </div> <!-- content -->
            <?php include 'footer.php'; ?>
        </div>
    </div>
    <script src="assets/js/vendor.min.js"></script>
    <script src="assets/js/app.min.js"></script>
</body>
</html> 