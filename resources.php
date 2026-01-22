<!DOCTYPE html>
<html lang="en">
<?php
session_start();
include 'head.php';
include 'include/config.php';
require_once __DIR__ . '/include/access_control.php';

$member_id = $_SESSION['member_id'] ?? null;
?>

<body>
    <!-- Header -->
    <?php 
    if (isset($_SESSION['member_id'])) {
        include 'member-header-v1.2.php';
    } else {
        include 'header-v1.2.php';
    }
    ?>
    <!-- End Header -->

    <!-- Start Breadcrumb (Only for non-members) -->
    <?php if (!isset($_SESSION['member_id'])): ?>
    <div class="breadcrumb-area text-center shadow dark bg-fixed padding-xl text-light" style="background-image: url(assets/img/bgregister.png);">
        <div class="container">
            <div class="breadcrumb-items">
                <div class="row">
                    <div class="col-lg-12">
                        <h2>Resources</h2>
                    </div>
                </div>
                <ul class="breadcrumb">
                    <li><a href="index.php"><i class="fas fa-home"></i> Home</a></li>
                    <li class="active">Resources</li>
                </ul>
            </div>
        </div>
    </div>
    <?php else: ?>
    <div class="mp-content-wrapper">
        <div class="mp-container">
            <div class="mp-content">
                <h2 class="mp-page-title mp-mb-md"><i class="fas fa-download"></i> Available Resources</h2>
                <p class="mp-mb-lg" style="color: var(--mp-gray-600);">Download resources, guidelines, reports, and manuals</p>
    <?php endif; ?>
    <!-- End Breadcrumb -->

    <?php if (!isset($_SESSION['member_id'])): ?>
    <div class="blog-area single full-blog full-blog default-padding">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="blog-items">
                        <h3 class="mb-4">Available Resources</h3>
                        <p class="mb-4">Download resources, guidelines, reports, and manuals</p>
    <?php endif; ?>

                        <?php
                        // Check if status column exists
                        $checkColumn = mysqli_query($conn, "SHOW COLUMNS FROM resources LIKE 'status'");
                        $hasStatusColumn = (mysqli_num_rows($checkColumn) > 0);
                        
                        // Get all active resources
                        if ($hasStatusColumn) {
                            $query = "SELECT * FROM resources WHERE status = 'active' OR status IS NULL ORDER BY publication_date DESC, created_at DESC";
                        } else {
                            // Fallback if status column doesn't exist yet
                            $query = "SELECT * FROM resources ORDER BY publication_date DESC, created_at DESC";
                        }
                        $result = mysqli_query($conn, $query);

                        if (mysqli_num_rows($result) > 0) {
                            // Group by section and filter by access
                            $resourcesBySection = [];
                            $hasAccessibleResources = false;
                            
                            while ($row = mysqli_fetch_assoc($result)) {
                                // Check access for this resource
                                $accessCheck = canAccessResource($member_id, $row['id']);
                                
                                if ($accessCheck['granted']) {
                                    $hasAccessibleResources = true;
                                    $section = $row['section'];
                                    if (!isset($resourcesBySection[$section])) {
                                        $resourcesBySection[$section] = [];
                                    }
                                    $resourcesBySection[$section][] = $row;
                                }
                            }
                            
                            if (!$hasAccessibleResources) {
                                $alertClass = isset($_SESSION['member_id']) ? 'mp-alert mp-alert-info' : 'alert alert-info';
                                echo '<div class="' . $alertClass . '">';
                                echo '<strong>No Accessible Resources:</strong> ';
                                if (!$member_id) {
                                    echo 'Please <a href="member-login.php" style="color: inherit; text-decoration: underline;">login</a> to access resources.';
                                } else {
                                    echo 'You do not have access to any resources. Please upgrade your membership package or contact admin.';
                                }
                                echo '</div>';
                            }

                            if ($hasAccessibleResources) {
                                foreach ($resourcesBySection as $section => $resources) {
                                    $cardClass = isset($_SESSION['member_id']) ? 'mp-card mp-mb-lg' : 'card mb-4 shadow-sm';
                                    $headerClass = isset($_SESSION['member_id']) ? 'mp-card-header' : 'card-header bg-primary text-white';
                                    $bodyClass = isset($_SESSION['member_id']) ? 'mp-card-body' : 'card-body';
                                    
                                    echo '<div class="' . $cardClass . '">';
                                    echo '<div class="' . $headerClass . '">';
                                    echo '<h5' . (isset($_SESSION['member_id']) ? '' : ' class="mb-0"') . '><i class="fas fa-folder"></i> ' . htmlspecialchars($section) . '</h5>';
                                    echo '</div>';
                                    echo '<div class="' . $bodyClass . '">';
                                    
                                    if (isset($_SESSION['member_id'])) {
                                        // Use compact list layout for members
                                        echo '<div class="mp-activity-list">';
                                        foreach ($resources as $resource) {
                                            $accessCheck = canAccessResource($member_id, $resource['id']);
                                            if (!$accessCheck['granted']) continue;
                                            
                                            $access_level = $resource['access_level'] ?? 'member';
                                            $badgeClass = '';
                                            if ($access_level === 'premium') {
                                                $badgeClass = 'mp-badge mp-badge-warning';
                                            } elseif ($access_level === 'restricted') {
                                                $badgeClass = 'mp-badge mp-badge-danger';
                                            }
                                            
                                            echo '<div class="mp-activity-item">';
                                            echo '<div class="mp-activity-content" style="flex: 1;">';
                                            echo '<div class="mp-activity-text">';
                                            echo '<strong>' . htmlspecialchars($resource['title']) . '</strong>';
                                            if ($badgeClass) {
                                                echo ' <span class="' . $badgeClass . '">' . ucfirst($access_level) . '</span>';
                                            }
                                            echo '</div>';
                                            echo '<div class="mp-activity-time">';
                                            echo '<i class="fas fa-user"></i> ' . htmlspecialchars($resource['author']);
                                            echo ' | <i class="fas fa-calendar"></i> ' . date('M d, Y', strtotime($resource['publication_date']));
                                            if (!empty($resource['description'])) {
                                                echo '<br>' . htmlspecialchars(substr($resource['description'], 0, 100)) . (strlen($resource['description']) > 100 ? '...' : '');
                                            }
                                            echo '</div>';
                                            echo '</div>';
                                            echo '<a href="' . htmlspecialchars($resource['pdf_file']) . '" class="mp-btn mp-btn-sm mp-btn-primary" download>';
                                            echo '<i class="fas fa-download"></i> Download';
                                            echo '</a>';
                                            echo '</div>';
                                        }
                                        echo '</div>';
                                    } else {
                                        // Use table for non-members
                                        echo '<div class="table-responsive">';
                                        echo '<table class="table table-hover">';
                                        echo '<thead>';
                                        echo '<tr>';
                                        echo '<th>Title</th>';
                                        echo '<th>Author</th>';
                                        echo '<th>Publication Date</th>';
                                        echo '<th>Action</th>';
                                        echo '</tr>';
                                        echo '</thead>';
                                        echo '<tbody>';
                                        
                                        foreach ($resources as $resource) {
                                            $accessCheck = canAccessResource($member_id, $resource['id']);
                                            if (!$accessCheck['granted']) continue;
                                            
                                            $access_level = $resource['access_level'] ?? 'member';
                                            $accessBadge = '';
                                            if ($access_level === 'premium') {
                                                $accessBadge = '<span class="badge bg-warning ms-2">Premium</span>';
                                            } elseif ($access_level === 'restricted') {
                                                $accessBadge = '<span class="badge bg-danger ms-2">Restricted</span>';
                                            }
                                            
                                            echo '<tr>';
                                            echo '<td><strong>' . htmlspecialchars($resource['title']) . '</strong>' . $accessBadge;
                                            if (!empty($resource['description'])) {
                                                echo '<br><small class="text-muted">' . htmlspecialchars($resource['description']) . '</small>';
                                            }
                                            echo '</td>';
                                            echo '<td>' . htmlspecialchars($resource['author']) . '</td>';
                                            echo '<td>' . date('M d, Y', strtotime($resource['publication_date'])) . '</td>';
                                            echo '<td>';
                                            if ($access_level === 'public') {
                                                echo '<a href="member-login.php" class="btn btn-sm btn-primary">';
                                                echo '<i class="fas fa-download"></i> Login to Download';
                                                echo '</a>';
                                            } else {
                                                echo '<button class="btn btn-sm btn-secondary" disabled>';
                                                echo '<i class="fas fa-lock"></i> Login Required';
                                                echo '</button>';
                                            }
                                            echo '</td>';
                                            echo '</tr>';
                                        }
                                        
                                        echo '</tbody>';
                                        echo '</table>';
                                        echo '</div>';
                                    }
                                    
                                    echo '</div>';
                                    echo '</div>';
                                }
                            }
                        } else {
                            $alertClass = isset($_SESSION['member_id']) ? 'mp-alert mp-alert-info' : 'alert alert-info';
                            echo '<div class="' . $alertClass . '">No resources available at the moment.</div>';
                        }
                        ?>
                    <?php if (!isset($_SESSION['member_id'])): ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php else: ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Footer (Only for non-members) -->
    <?php if (!isset($_SESSION['member_id'])): ?>
        <?php include 'footer.php'; ?>
    <?php endif; ?>

    <!-- Optimized Scripts for Member Pages -->
    <script src="assets/js/jquery-1.12.4.min.js"></script>
    <script src="assets/js/bootstrap.min.js" defer></script>
    <script src="assets/js/bootsnav.js" defer></script>
    
    <?php if (isset($_SESSION['member_id'])): ?>
    <!-- Member Panel CSS -->
    <link href="assets/css/member-panel.css" rel="stylesheet">
    <?php else: ?>
    <!-- Member Optimized CSS -->
    <link href="assets/css/member-optimized.css" rel="stylesheet">
    <?php endif; ?>
    
    <?php
    // Close database connection if it exists and is still open
    if (isset($conn) && $conn instanceof mysqli) {
        // Check if connection is still open by trying to ping
        try {
            if (@mysqli_ping($conn)) {
                mysqli_close($conn);
            }
        } catch (Exception $e) {
            // Connection already closed, ignore
        }
    }
    ?>

</body>

</html>




