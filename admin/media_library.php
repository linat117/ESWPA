<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: auth-login.php");
    exit();
}

include 'include/conn.php';
include 'header.php';

// Get filter parameters
$category = $_GET['category'] ?? 'all';
$type = $_GET['type'] ?? 'all';
$search = $_GET['search'] ?? '';
$page = intval($_GET['page'] ?? 1);
$per_page = 24;
$offset = ($page - 1) * $per_page;

// Scan uploads directory for media files
function scanMediaFiles($baseDir = '../../uploads/') {
    $mediaFiles = [];
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'doc', 'docx', 'xls', 'xlsx'];
    
    if (!is_dir($baseDir)) {
        return $mediaFiles;
    }
    
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($baseDir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    
    foreach ($iterator as $file) {
        if ($file->isFile()) {
            $extension = strtolower($file->getExtension());
            if (in_array($extension, $allowedExtensions)) {
                $relativePath = str_replace('../../', '', $file->getPathname());
                $relativePath = str_replace('\\', '/', $relativePath);
                
                // Determine category from path
                $category = 'general';
                if (strpos($relativePath, 'news/') !== false) {
                    $category = 'news';
                } elseif (strpos($relativePath, 'members/') !== false) {
                    $category = 'members';
                } elseif (strpos($relativePath, 'bankslip/') !== false) {
                    $category = 'bankslip';
                } elseif (strpos($relativePath, 'resources/') !== false) {
                    $category = 'resources';
                } elseif (strpos($relativePath, 'company/') !== false) {
                    $category = 'company';
                }
                
                // Determine file type
                $fileType = 'image';
                if (in_array($extension, ['pdf', 'doc', 'docx', 'xls', 'xlsx'])) {
                    $fileType = 'document';
                }
                
                $mediaFiles[] = [
                    'path' => $relativePath,
                    'full_path' => $file->getPathname(),
                    'name' => $file->getFilename(),
                    'size' => $file->getSize(),
                    'extension' => $extension,
                    'category' => $category,
                    'type' => $fileType,
                    'modified' => $file->getMTime(),
                    'url' => '/' . $relativePath
                ];
            }
        }
    }
    
    // Sort by modified date (newest first)
    usort($mediaFiles, function($a, $b) {
        return $b['modified'] <=> $a['modified'];
    });
    
    return $mediaFiles;
}

// Get all media files
$allMedia = scanMediaFiles();

// Apply filters
$filteredMedia = $allMedia;

if ($category !== 'all') {
    $filteredMedia = array_filter($filteredMedia, function($file) use ($category) {
        return $file['category'] == $category;
    });
}

if ($type !== 'all') {
    $filteredMedia = array_filter($filteredMedia, function($file) use ($type) {
        return $file['type'] == $type;
    });
}

if (!empty($search)) {
    $filteredMedia = array_filter($filteredMedia, function($file) use ($search) {
        return stripos($file['name'], $search) !== false;
    });
}

// Re-index array after filtering
$filteredMedia = array_values($filteredMedia);

// Pagination
$total_files = count($filteredMedia);
$total_pages = ceil($total_files / $per_page);
$paginatedMedia = array_slice($filteredMedia, $offset, $per_page);

// Get category counts
$categoryCounts = [
    'all' => count($allMedia),
    'news' => 0,
    'members' => 0,
    'bankslip' => 0,
    'resources' => 0,
    'company' => 0,
    'general' => 0
];

foreach ($allMedia as $file) {
    if (isset($categoryCounts[$file['category']])) {
        $categoryCounts[$file['category']]++;
    }
}

// Get type counts
$typeCounts = [
    'all' => count($allMedia),
    'image' => 0,
    'document' => 0
];

foreach ($allMedia as $file) {
    if (isset($typeCounts[$file['type']])) {
        $typeCounts[$file['type']]++;
    }
}

// Handle file deletion
if (isset($_GET['delete']) && isset($_GET['file'])) {
    $fileToDelete = '../../' . urldecode($_GET['file']);
    if (file_exists($fileToDelete) && strpos(realpath($fileToDelete), realpath('../../uploads/')) === 0) {
        if (@unlink($fileToDelete)) {
            header("Location: media_library.php?success=File deleted successfully&category=" . urlencode($category) . "&type=" . urlencode($type) . "&search=" . urlencode($search));
            exit();
        }
    }
    header("Location: media_library.php?error=Failed to delete file&category=" . urlencode($category) . "&type=" . urlencode($type) . "&search=" . urlencode($search));
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<body>
    <div class="wrapper">
        <?php include 'sidebar.php'; ?>

        <div class="content-page">
            <div class="content">
                <div class="container-fluid">
                    <!-- Page Title -->
                    <div class="row">
                        <div class="col-12">
                            <div class="page-title-box">
                                <h4 class="page-title">Media Library</h4>
                                <div class="page-title-right">
                                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#uploadModal">
                                        <i class="ri-upload-cloud-line"></i> Upload Media
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

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

                    <!-- Filters -->
                    <div class="row mb-3">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <form method="GET" class="row g-3">
                                        <div class="col-md-3">
                                            <label class="form-label">Category</label>
                                            <select name="category" class="form-select" onchange="this.form.submit()">
                                                <option value="all" <?php echo $category == 'all' ? 'selected' : ''; ?>>All Categories (<?php echo $categoryCounts['all']; ?>)</option>
                                                <option value="news" <?php echo $category == 'news' ? 'selected' : ''; ?>>News (<?php echo $categoryCounts['news']; ?>)</option>
                                                <option value="members" <?php echo $category == 'members' ? 'selected' : ''; ?>>Members (<?php echo $categoryCounts['members']; ?>)</option>
                                                <option value="resources" <?php echo $category == 'resources' ? 'selected' : ''; ?>>Resources (<?php echo $categoryCounts['resources']; ?>)</option>
                                                <option value="bankslip" <?php echo $category == 'bankslip' ? 'selected' : ''; ?>>Bank Slips (<?php echo $categoryCounts['bankslip']; ?>)</option>
                                                <option value="company" <?php echo $category == 'company' ? 'selected' : ''; ?>>Company (<?php echo $categoryCounts['company']; ?>)</option>
                                                <option value="general" <?php echo $category == 'general' ? 'selected' : ''; ?>>General (<?php echo $categoryCounts['general']; ?>)</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Type</label>
                                            <select name="type" class="form-select" onchange="this.form.submit()">
                                                <option value="all" <?php echo $type == 'all' ? 'selected' : ''; ?>>All Types (<?php echo $typeCounts['all']; ?>)</option>
                                                <option value="image" <?php echo $type == 'image' ? 'selected' : ''; ?>>Images (<?php echo $typeCounts['image']; ?>)</option>
                                                <option value="document" <?php echo $type == 'document' ? 'selected' : ''; ?>>Documents (<?php echo $typeCounts['document']; ?>)</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Search</label>
                                            <input type="text" name="search" class="form-control" placeholder="Search by filename..." value="<?php echo htmlspecialchars($search); ?>">
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label">&nbsp;</label>
                                            <button type="submit" class="btn btn-primary w-100">
                                                <i class="ri-search-line"></i> Search
                                            </button>
                                        </div>
                                        <?php if ($category != 'all' || $type != 'all' || !empty($search)): ?>
                                        <div class="col-12">
                                            <a href="media_library.php" class="btn btn-sm btn-secondary">Clear Filters</a>
                                        </div>
                                        <?php endif; ?>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Media Grid -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">
                                        Media Files 
                                        <span class="badge bg-primary"><?php echo number_format($total_files); ?> files</span>
                                    </h4>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($paginatedMedia)): ?>
                                        <div class="text-center py-5">
                                            <i class="ri-folder-open-line" style="font-size: 64px; color: #ccc;"></i>
                                            <p class="text-muted mt-3">No media files found.</p>
                                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#uploadModal">
                                                <i class="ri-upload-cloud-line"></i> Upload Your First File
                                            </button>
                                        </div>
                                    <?php else: ?>
                                        <div class="row g-3">
                                            <?php foreach ($paginatedMedia as $file): ?>
                                            <div class="col-lg-2 col-md-3 col-sm-4 col-6">
                                                <div class="card border">
                                                    <div class="card-body p-2 text-center">
                                                        <?php if ($file['type'] == 'image'): ?>
                                                            <img src="<?php echo htmlspecialchars($file['url']); ?>" 
                                                                 alt="<?php echo htmlspecialchars($file['name']); ?>"
                                                                 class="img-fluid rounded mb-2" 
                                                                 style="max-height: 120px; width: 100%; object-fit: cover; cursor: pointer;"
                                                                 onclick="openImageModal('<?php echo htmlspecialchars($file['url']); ?>', '<?php echo htmlspecialchars($file['name']); ?>')">
                                                        <?php else: ?>
                                                            <div class="bg-light rounded d-flex align-items-center justify-content-center mb-2" style="height: 120px;">
                                                                <i class="ri-file-line" style="font-size: 48px; color: #999;"></i>
                                                            </div>
                                                        <?php endif; ?>
                                                        
                                                        <div class="text-truncate" title="<?php echo htmlspecialchars($file['name']); ?>">
                                                            <small class="text-muted"><?php echo htmlspecialchars($file['name']); ?></small>
                                                        </div>
                                                        
                                                        <div class="mt-1">
                                                            <small class="text-muted">
                                                                <?php 
                                                                $size = $file['size'];
                                                                if ($size < 1024) {
                                                                    echo $size . ' B';
                                                                } elseif ($size < 1024 * 1024) {
                                                                    echo number_format($size / 1024, 1) . ' KB';
                                                                } else {
                                                                    echo number_format($size / (1024 * 1024), 1) . ' MB';
                                                                }
                                                                ?>
                                                            </small>
                                                        </div>
                                                        
                                                        <div class="mt-2">
                                                            <div class="btn-group btn-group-sm" role="group">
                                                                <button type="button" class="btn btn-outline-primary" 
                                                                        onclick="copyToClipboard('<?php echo htmlspecialchars($file['url']); ?>')"
                                                                        title="Copy URL">
                                                                    <i class="ri-file-copy-line"></i>
                                                                </button>
                                                                <a href="<?php echo htmlspecialchars($file['url']); ?>" 
                                                                   target="_blank" 
                                                                   class="btn btn-outline-info"
                                                                   title="View">
                                                                    <i class="ri-eye-line"></i>
                                                                </a>
                                                                <button type="button" 
                                                                        class="btn btn-outline-danger"
                                                                        onclick="deleteFile('<?php echo urlencode($file['path']); ?>', '<?php echo htmlspecialchars($file['name']); ?>')"
                                                                        title="Delete">
                                                                    <i class="ri-delete-bin-line"></i>
                                                                </button>
                                                            </div>
                                                        </div>
                                                        
                                                        <div class="mt-1">
                                                            <small class="text-muted">
                                                                <?php echo date('M d, Y', $file['modified']); ?>
                                                            </small>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>

                                        <!-- Pagination -->
                                        <?php if ($total_pages > 1): ?>
                                        <div class="row mt-4">
                                            <div class="col-12">
                                                <nav>
                                                    <ul class="pagination justify-content-center">
                                                        <?php
                                                        $queryParams = http_build_query([
                                                            'category' => $category,
                                                            'type' => $type,
                                                            'search' => $search
                                                        ]);
                                                        
                                                        // Previous button
                                                        if ($page > 1): ?>
                                                            <li class="page-item">
                                                                <a class="page-link" href="?<?php echo $queryParams; ?>&page=<?php echo $page - 1; ?>">Previous</a>
                                                            </li>
                                                        <?php endif; ?>
                                                        
                                                        <?php
                                                        // Page numbers
                                                        $start_page = max(1, $page - 2);
                                                        $end_page = min($total_pages, $page + 2);
                                                        
                                                        for ($i = $start_page; $i <= $end_page; $i++): ?>
                                                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                                                <a class="page-link" href="?<?php echo $queryParams; ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                                            </li>
                                                        <?php endfor; ?>
                                                        
                                                        <?php
                                                        // Next button
                                                        if ($page < $total_pages): ?>
                                                            <li class="page-item">
                                                                <a class="page-link" href="?<?php echo $queryParams; ?>&page=<?php echo $page + 1; ?>">Next</a>
                                                            </li>
                                                        <?php endif; ?>
                                                    </ul>
                                                </nav>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?php include 'footer.php'; ?>
        </div>
    </div>

    <!-- Upload Modal -->
    <div class="modal fade" id="uploadModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Upload Media</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="media_upload_handler.php" method="post" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Select Files</label>
                            <input type="file" name="media_files[]" class="form-control" multiple accept="image/*,.pdf,.doc,.docx,.xls,.xlsx">
                            <small class="text-muted">You can select multiple files. Max size: 5MB per file.</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Category</label>
                            <select name="category" class="form-select">
                                <option value="general">General</option>
                                <option value="news">News</option>
                                <option value="members">Members</option>
                                <option value="resources">Resources</option>
                                <option value="company">Company</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Upload</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Image Preview Modal -->
    <div class="modal fade" id="imageModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="imageModalTitle">Image Preview</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <img id="imageModalImg" src="" alt="" class="img-fluid">
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/vendor.min.js"></script>
    <script src="assets/js/app.min.js"></script>

    <script>
        function openImageModal(url, name) {
            document.getElementById('imageModalImg').src = url;
            document.getElementById('imageModalTitle').textContent = name;
            new bootstrap.Modal(document.getElementById('imageModal')).show();
        }

        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(function() {
                alert('URL copied to clipboard!');
            }, function() {
                alert('Failed to copy URL');
            });
        }

        function deleteFile(filePath, fileName) {
            if (confirm('Are you sure you want to delete "' + fileName + '"?\n\nThis action cannot be undone.')) {
                window.location.href = '?delete=1&file=' + encodeURIComponent(filePath) + 
                    '&category=<?php echo urlencode($category); ?>&type=<?php echo urlencode($type); ?>&search=<?php echo urlencode($search); ?>';
            }
        }
    </script>
</body>
</html>

