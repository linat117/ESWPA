<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: auth-login.php");
    exit();
}

include 'include/conn.php';
include 'header.php';

// Get resource ID
$resource_id = intval($_GET['id'] ?? 0);

if ($resource_id <= 0) {
    header("Location: resources_list.php?error=Invalid resource ID");
    exit();
}

// Fetch resource data
$query = "SELECT * FROM resources WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $resource_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: resources_list.php?error=Resource not found");
    exit();
}

$resource = $result->fetch_assoc();
$stmt->close();

// Format file size if needed
function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}

// Get file size if file exists
$file_path = '../uploads/resources/' . $resource['pdf_file'];
$file_size = 0;
if (file_exists($file_path)) {
    $file_size = filesize($file_path);
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
                            <div class="page-title-box d-flex justify-content-between align-items-center">
                                <h4 class="page-title">Resource Details</h4>
                                <div>
                                    <a href="edit_resource.php?id=<?php echo $resource_id; ?>" class="btn btn-primary me-2">
                                        <i class="ri-edit-line"></i> Edit
                                    </a>
                                    <a href="resources_list.php" class="btn btn-secondary">
                                        <i class="ri-arrow-left-line"></i> Back to List
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Main Information -->
                        <div class="col-lg-8">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title"><?php echo htmlspecialchars($resource['title']); ?></h4>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <?php
                                        $statusColors = [
                                            'active' => 'success',
                                            'inactive' => 'warning',
                                            'archived' => 'secondary'
                                        ];
                                        $statusColor = $statusColors[$resource['status']] ?? 'secondary';
                                        $statusLabel = ucfirst($resource['status']);
                                        ?>
                                        <span class="badge bg-<?php echo $statusColor; ?> me-2"><?php echo $statusLabel; ?></span>
                                        
                                        <?php
                                        $accessColors = [
                                            'public' => 'info',
                                            'member' => 'primary',
                                            'premium' => 'warning',
                                            'restricted' => 'danger'
                                        ];
                                        $accessColor = $accessColors[$resource['access_level']] ?? 'secondary';
                                        $accessLabel = ucfirst($resource['access_level']);
                                        ?>
                                        <span class="badge bg-<?php echo $accessColor; ?> me-2"><?php echo $accessLabel; ?> Access</span>
                                        
                                        <?php if ($resource['featured'] == 1): ?>
                                            <span class="badge bg-warning">
                                                <i class="ri-star-fill"></i> Featured
                                            </span>
                                        <?php endif; ?>
                                    </div>

                                    <div class="mb-3">
                                        <h6>Description</h6>
                                        <p><?php echo nl2br(htmlspecialchars($resource['description'] ?? 'No description provided.')); ?></p>
                                    </div>

                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <strong>Section:</strong> 
                                            <span class="badge bg-light text-dark"><?php echo htmlspecialchars($resource['section']); ?></span>
                                        </div>
                                        <div class="col-md-6">
                                            <strong>Author:</strong> <?php echo htmlspecialchars($resource['author']); ?>
                                        </div>
                                    </div>

                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <strong>Publication Date:</strong> 
                                            <?php echo date('F d, Y', strtotime($resource['publication_date'])); ?>
                                        </div>
                                        <div class="col-md-6">
                                            <strong>Created:</strong> 
                                            <?php echo date('F d, Y H:i', strtotime($resource['created_at'])); ?>
                                        </div>
                                    </div>

                                    <?php if (!empty($resource['tags'])): ?>
                                    <div class="mb-3">
                                        <strong>Tags:</strong>
                                        <?php
                                        $tags = explode(',', $resource['tags']);
                                        foreach ($tags as $tag) {
                                            $tag = trim($tag);
                                            if (!empty($tag)) {
                                                echo '<span class="badge bg-secondary me-1">' . htmlspecialchars($tag) . '</span>';
                                            }
                                        }
                                        ?>
                                    </div>
                                    <?php endif; ?>

                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <strong>Download Count:</strong> 
                                            <span class="badge bg-primary"><?php echo number_format($resource['download_count']); ?></span>
                                        </div>
                                        <div class="col-md-6">
                                            <strong>File Size:</strong> 
                                            <?php echo $file_size > 0 ? formatFileSize($file_size) : 'Unknown'; ?>
                                        </div>
                                    </div>

                                    <?php if (!empty($resource['pdf_file'])): ?>
                                    <div class="mb-3">
                                        <strong>PDF File:</strong>
                                        <div class="mt-2">
                                            <a href="../uploads/resources/<?php echo htmlspecialchars($resource['pdf_file']); ?>" 
                                               target="_blank" 
                                               class="btn btn-primary">
                                                <i class="ri-file-pdf-line"></i> View/Download PDF
                                            </a>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Sidebar Information -->
                        <div class="col-lg-4">
                            <!-- Quick Actions -->
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Quick Actions</h4>
                                </div>
                                <div class="card-body">
                                    <div class="d-grid gap-2">
                                        <a href="edit_resource.php?id=<?php echo $resource_id; ?>" class="btn btn-primary">
                                            <i class="ri-edit-line"></i> Edit Resource
                                        </a>
                                        <a href="resources_list.php" class="btn btn-secondary">
                                            <i class="ri-list-check"></i> Back to List
                                        </a>
                                        <a href="resources_dashboard.php" class="btn btn-info">
                                            <i class="ri-dashboard-line"></i> Resources Dashboard
                                        </a>
                                        <a href="resources_analytics.php" class="btn btn-warning">
                                            <i class="ri-bar-chart-line"></i> View Analytics
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Resource Statistics -->
                            <div class="card mt-3">
                                <div class="card-header">
                                    <h4 class="header-title">Statistics</h4>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between">
                                            <span>Status:</span>
                                            <strong>
                                                <span class="badge bg-<?php echo $statusColor; ?>">
                                                    <?php echo $statusLabel; ?>
                                                </span>
                                            </strong>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between">
                                            <span>Access Level:</span>
                                            <strong>
                                                <span class="badge bg-<?php echo $accessColor; ?>">
                                                    <?php echo $accessLabel; ?>
                                                </span>
                                            </strong>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between">
                                            <span>Downloads:</span>
                                            <strong><?php echo number_format($resource['download_count']); ?></strong>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between">
                                            <span>Featured:</span>
                                            <strong>
                                                <?php if ($resource['featured'] == 1): ?>
                                                    <i class="ri-star-fill text-warning"></i> Yes
                                                <?php else: ?>
                                                    <i class="ri-star-line text-muted"></i> No
                                                <?php endif; ?>
                                            </strong>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between">
                                            <span>Created:</span>
                                            <strong><?php echo date('M d, Y', strtotime($resource['created_at'])); ?></strong>
                                        </div>
                                    </div>
                                    <?php if ($resource['updated_at']): ?>
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between">
                                            <span>Last Updated:</span>
                                            <strong><?php echo date('M d, Y', strtotime($resource['updated_at'])); ?></strong>
                                        </div>
                                    </div>
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

    <!-- Vendor js -->
    <script src="assets/js/vendor.min.js"></script>
    <!-- App js -->
    <script src="assets/js/app.min.js"></script>

</body>
</html>

