<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: auth-login.php");
    exit();
}

include 'include/conn.php';
include 'header.php';
?>
<!-- Quill css -->
<link href="assets/vendor/quill/quill.core.css" rel="stylesheet" type="text/css" />
<link href="assets/vendor/quill/quill.snow.css" rel="stylesheet" type="text/css" />
<style>
    /* Ensure Status dropdown stays next to its label (theme uses #status for loader) */
    #post_status.form-select {
        display: block !important;
        width: 100% !important;
        min-width: 120px !important;
        min-height: 38px !important;
    }
</style>

<body>
    <div class="wrapper">
        <?php include 'sidebar.php'; ?>

        <div class="content-page">
            <div class="content">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-12">
                            <div class="page-title-box">
                                <h4 class="page-title">Add News/Blog/Report</h4>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Create New Post</h4>
                                </div>
                                <div class="card-body">
                                    <?php
                                    if (isset($_GET['success'])) {
                                        echo '<div class="alert alert-success alert-dismissible fade show" role="alert">';
                                        echo 'Post created successfully!';
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

                                    <form action="include/manage_news.php" method="post" enctype="multipart/form-data">
                                        <div class="row">
                                            <div class="col-12 col-sm-6 mb-3">
                                                <label for="type" class="form-label">Type *</label>
                                                <select class="form-select w-100" id="type" name="type" required>
                                                    <option value="news">News</option>
                                                    <option value="blog">Blog</option>
                                                    <option value="report">Report</option>
                                                </select>
                                            </div>

                                            <div class="col-12 col-sm-6 mb-3" style="min-width: 0;">
                                                <label for="post_status" class="form-label">Status *</label>
                                                <select class="form-select w-100" id="post_status" name="status" required style="min-height: 38px; display: block !important; width: 100% !important;">
                                                    <option value="draft">Draft</option>
                                                    <option value="published" selected>Published</option>
                                                    <option value="archived">Archived</option>
                                                </select>
                                            </div>

                                            <div class="col-12 mb-3">
                                                <label for="title" class="form-label">Title *</label>
                                                <input type="text" class="form-control" id="title" name="title" required 
                                                       placeholder="Post title">
                                            </div>

                                            <div class="col-12 mb-3">
                                                <label for="content" class="form-label">Content *</label>
                                                <div id="content-editor" style="height: 300px;"></div>
                                                <textarea id="content" name="content" style="display: none;" required></textarea>
                                                <small class="text-muted">Use the editor above to format your content. HTML formatting is supported.</small>
                                            </div>

                                            <div class="col-md-6 mb-3">
                                                <label for="author" class="form-label">Author</label>
                                                <input type="text" class="form-control" id="author" name="author" 
                                                       placeholder="Author name" value="<?php echo htmlspecialchars($_SESSION['username']); ?>">
                                            </div>

                                            <div class="col-md-6 mb-3">
                                                <label for="published_date" class="form-label">Published Date</label>
                                                <input type="date" class="form-control" id="published_date" name="published_date" 
                                                       value="<?php echo date('Y-m-d'); ?>">
                                            </div>

                                            <div class="col-12 mb-3">
                                                <label for="images" class="form-label">Images</label>
                                                <input type="file" class="form-control" id="images" name="images[]" 
                                                       accept="image/*" multiple>
                                                <small class="text-muted">You can select multiple images (Max size: 2MB each)</small>
                                            </div>

                                            <div class="col-12">
                                                <button type="submit" name="action" value="create" class="btn btn-primary">
                                                    <i class="ri-save-line"></i> Create Post
                                                </button>
                                                <a href="news_list.php" class="btn btn-secondary">
                                                    <i class="ri-arrow-left-line"></i> Back to List
                                                </a>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?php include 'footer.php'; ?>
        </div>
    </div>

    <!-- Quill js -->
    <script src="assets/vendor/quill/quill.min.js"></script>
    <script src="assets/js/vendor.min.js"></script>
    <script src="assets/js/app.min.js"></script>

    <script>
        $(document).ready(function() {
            // Initialize Quill editor
            var quill = new Quill('#content-editor', {
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

            // Update hidden textarea on text change
            quill.on('text-change', function() {
                $('#content').val(quill.root.innerHTML);
            });
            
            // Handle form submission
            $('form').on('submit', function(e) {
                // Update textarea with editor content before submit
                $('#content').val(quill.root.innerHTML);
                
                // Validate content is not empty
                if (quill.getText().trim().length === 0) {
                    e.preventDefault();
                    alert('Please enter some content.');
                    return false;
                }
            });
        });
    </script>
</body>
</html>

