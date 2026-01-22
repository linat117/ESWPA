<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: auth-login.php");
    exit();
}

include 'include/conn.php';
include 'header.php';

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    header("Location: news_list.php?error=Invalid post ID");
    exit();
}

// Fetch post data
$query = "SELECT * FROM news_media WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: news_list.php?error=Post not found");
    exit();
}

$post = $result->fetch_assoc();
$stmt->close();
?>

<!-- Quill css -->
<link href="assets/vendor/quill/quill.core.css" rel="stylesheet" type="text/css" />
<link href="assets/vendor/quill/quill.snow.css" rel="stylesheet" type="text/css" />

<body>
    <div class="wrapper">
        <?php include 'sidebar.php'; ?>

        <div class="content-page">
            <div class="content">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-12">
                            <div class="page-title-box">
                                <h4 class="page-title">Edit News/Blog/Report</h4>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Edit Post</h4>
                                </div>
                                <div class="card-body">
                                    <?php
                                    if (isset($_GET['success'])) {
                                        echo '<div class="alert alert-success alert-dismissible fade show" role="alert">';
                                        echo 'Post updated successfully!';
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
                                        <input type="hidden" name="action" value="update">
                                        <input type="hidden" name="id" value="<?php echo $post['id']; ?>">
                                        
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="type" class="form-label">Type *</label>
                                                <select class="form-control" id="type" name="type" required>
                                                    <option value="news" <?php echo $post['type'] == 'news' ? 'selected' : ''; ?>>News</option>
                                                    <option value="blog" <?php echo $post['type'] == 'blog' ? 'selected' : ''; ?>>Blog</option>
                                                    <option value="report" <?php echo $post['type'] == 'report' ? 'selected' : ''; ?>>Report</option>
                                                </select>
                                            </div>

                                            <div class="col-md-6 mb-3">
                                                <label for="status" class="form-label">Status *</label>
                                                <select class="form-control" id="status" name="status" required>
                                                    <option value="draft" <?php echo $post['status'] == 'draft' ? 'selected' : ''; ?>>Draft</option>
                                                    <option value="published" <?php echo $post['status'] == 'published' ? 'selected' : ''; ?>>Published</option>
                                                    <option value="archived" <?php echo $post['status'] == 'archived' ? 'selected' : ''; ?>>Archived</option>
                                                </select>
                                            </div>

                                            <div class="col-12 mb-3">
                                                <label for="title" class="form-label">Title *</label>
                                                <input type="text" class="form-control" id="title" name="title" required 
                                                       value="<?php echo htmlspecialchars($post['title']); ?>"
                                                       placeholder="Post title">
                                            </div>

                                            <div class="col-12 mb-3">
                                                <label for="content" class="form-label">Content *</label>
                                                <div id="content-editor" style="height: 300px;"></div>
                                                <textarea id="content" name="content" style="display: none;" required><?php echo htmlspecialchars($post['content']); ?></textarea>
                                                <small class="text-muted">Use the editor above to format your content. HTML formatting is supported.</small>
                                            </div>

                                            <div class="col-md-6 mb-3">
                                                <label for="author" class="form-label">Author</label>
                                                <input type="text" class="form-control" id="author" name="author" 
                                                       placeholder="Author name" value="<?php echo htmlspecialchars($post['author'] ?? $_SESSION['username']); ?>">
                                            </div>

                                            <div class="col-md-6 mb-3">
                                                <label for="published_date" class="form-label">Published Date</label>
                                                <input type="date" class="form-control" id="published_date" name="published_date" 
                                                       value="<?php echo !empty($post['published_date']) ? date('Y-m-d', strtotime($post['published_date'])) : date('Y-m-d'); ?>">
                                            </div>

                                            <div class="col-12 mb-3">
                                                <label for="images" class="form-label">Add More Images</label>
                                                <input type="file" class="form-control" id="images" name="images[]" 
                                                       accept="image/*" multiple>
                                                <small class="text-muted">You can select multiple images (Max size: 2MB each). Existing images will be preserved.</small>
                                                <?php if (!empty($post['images'])): 
                                                    $existingImages = json_decode($post['images'], true);
                                                    if ($existingImages && count($existingImages) > 0): ?>
                                                        <div class="mt-2">
                                                            <strong>Existing Images:</strong>
                                                            <div class="d-flex flex-wrap gap-2 mt-2">
                                                                <?php foreach ($existingImages as $img): ?>
                                                                    <div class="position-relative">
                                                                        <img src="../<?php echo htmlspecialchars($img); ?>" alt="Post image" style="max-width: 100px; max-height: 100px; object-fit: cover;" class="border rounded">
                                                                    </div>
                                                                <?php endforeach; ?>
                                                            </div>
                                                        </div>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </div>

                                            <div class="col-12">
                                                <button type="submit" class="btn btn-primary">
                                                    <i class="ri-save-line"></i> Update Post
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
            // Initialize Quill editor with existing content
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

            // Set existing content
            var existingContent = $('#content').val();
            if (existingContent) {
                quill.root.innerHTML = existingContent;
            }

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

