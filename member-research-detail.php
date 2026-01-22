<?php
session_start();

if (!isset($_SESSION['member_id'])) {
    header("Location: member-login.php");
    exit();
}

include 'include/config.php';
include 'include/access_control.php';

$member_id = $_SESSION['member_id'];
$research_id = intval($_GET['id'] ?? 0);

if ($research_id <= 0) {
    header("Location: member-research.php?error=Invalid research ID");
    exit();
}

// Get research details
$query = "SELECT rp.*, r.fullname as creator_name, r.email as creator_email
          FROM research_projects rp
          LEFT JOIN registrations r ON rp.created_by = r.id
          WHERE rp.id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $research_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: member-research.php?error=Research project not found");
    exit();
}

$research = $result->fetch_assoc();
$stmt->close();

// Check if member has access (creator or collaborator)
$isCreator = ($research['created_by'] == $member_id);
$isCollaborator = false;
$collabQuery = "SELECT role FROM research_collaborators WHERE research_id = ? AND member_id = ?";
$collabStmt = $conn->prepare($collabQuery);
$collabStmt->bind_param("ii", $research_id, $member_id);
$collabStmt->execute();
$collabResult = $collabStmt->get_result();
if ($collabResult->num_rows > 0) {
    $isCollaborator = true;
    $collabData = $collabResult->fetch_assoc();
    $memberRole = $collabData['role'];
}
$collabStmt->close();

// Check if published (anyone can view published research)
$isPublished = ($research['status'] === 'published');

if (!$isCreator && !$isCollaborator && !$isPublished) {
    header("Location: member-research.php?error=You don't have access to this research project");
    exit();
}

// Get collaborators
$collabQuery = "SELECT rc.*, r.fullname, r.email 
                FROM research_collaborators rc
                JOIN registrations r ON rc.member_id = r.id
                WHERE rc.research_id = ?
                ORDER BY rc.role, rc.joined_at";
$collabStmt = $conn->prepare($collabQuery);
$collabStmt->bind_param("i", $research_id);
$collabStmt->execute();
$collabResult = $collabStmt->get_result();

// Get files
$filesQuery = "SELECT rf.*, r.fullname as uploaded_by_name
               FROM research_files rf
               LEFT JOIN registrations r ON rf.uploaded_by = r.id
               WHERE rf.research_id = ?
               ORDER BY rf.uploaded_at DESC";
$filesStmt = $conn->prepare($filesQuery);
$filesStmt->bind_param("i", $research_id);
$filesStmt->execute();
$filesResult = $filesStmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($research['title']); ?> - Research</title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="assets/css/member-panel.css" rel="stylesheet">
</head>
<body>
    <?php include 'member-header-v1.2.php'; ?>
    
    <div class="mp-content-wrapper">
        <div class="mp-container">
            <div class="mp-content">
                <?php if (isset($_GET['success'])): ?>
                    <div class="mp-alert mp-alert-success">
                        <?php echo htmlspecialchars($_GET['success']); ?>
                    </div>
                <?php endif; ?>

                <!-- Research Header -->
                <div class="mp-research-header">
                    <div class="mp-flex-between">
                        <div>
                            <h1><?php echo htmlspecialchars($research['title']); ?></h1>
                            <p>
                                <i class="fas fa-user"></i> <?php echo htmlspecialchars($research['creator_name'] ?? 'Unknown'); ?>
                                <?php if ($research['publication_date']): ?>
                                    | <i class="fas fa-calendar"></i> Published: <?php echo date('M d, Y', strtotime($research['publication_date'])); ?>
                                <?php endif; ?>
                            </p>
                            <span class="mp-status-badge mp-status-<?php echo $research['status']; ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $research['status'])); ?>
                            </span>
                        </div>
                        <?php if ($isCreator || ($isCollaborator && in_array($memberRole, ['lead', 'co_author']))): ?>
                            <a href="../admin/edit_research.php?id=<?php echo $research_id; ?>" class="mp-btn mp-btn-light">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="mp-grid-2" style="grid-template-columns: 2fr 1fr; gap: var(--mp-space-lg);">
                    <!-- Main Content -->
                    <div>
                        <!-- Description -->
                        <div class="mp-card mp-mb-md">
                            <div class="mp-card-body">
                                <h5 class="mp-card-title">Description</h5>
                                <p class="mp-card-text"><?php echo nl2br(htmlspecialchars($research['description'])); ?></p>
                            </div>
                        </div>

                        <!-- Abstract -->
                        <?php if (!empty($research['abstract'])): ?>
                        <div class="mp-card mp-mb-md">
                            <div class="mp-card-body">
                                <h5 class="mp-card-title">Abstract</h5>
                                <p class="mp-card-text" style="color: var(--mp-gray-600);"><?php echo nl2br(htmlspecialchars($research['abstract'])); ?></p>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Files -->
                        <div class="mp-card mp-mb-md">
                            <div class="mp-card-header">
                                <h5><i class="fas fa-file"></i> Research Files</h5>
                            </div>
                            <div class="mp-card-body">
                                <?php if ($filesResult->num_rows > 0): ?>
                                    <div class="mp-activity-list">
                                        <?php while ($file = $filesResult->fetch_assoc()): ?>
                                            <div class="mp-activity-item">
                                                <div class="mp-activity-content">
                                                    <div class="mp-activity-text">
                                                        <strong><?php echo htmlspecialchars($file['file_name']); ?></strong>
                                                    </div>
                                                    <div class="mp-activity-time">
                                                        <?php echo number_format($file['file_size'] / 1024, 2); ?> KB | 
                                                        Uploaded by <?php echo htmlspecialchars($file['uploaded_by_name'] ?? 'Unknown'); ?> | 
                                                        <?php echo date('M d, Y', strtotime($file['uploaded_at'])); ?>
                                                    </div>
                                                </div>
                                                <a href="../<?php echo htmlspecialchars($file['file_path']); ?>" target="_blank" class="mp-btn mp-btn-sm mp-btn-primary">
                                                    <i class="fas fa-download"></i> Download
                                                </a>
                                            </div>
                                        <?php endwhile; ?>
                                    </div>
                                <?php else: ?>
                                    <p style="color: var(--mp-gray-500); margin: 0;">No files uploaded yet.</p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Research Info -->
                        <div class="mp-card">
                            <div class="mp-card-body">
                                <h5 class="mp-card-title">Research Information</h5>
                                <div class="mp-grid-2">
                                    <?php if ($research['category']): ?>
                                    <div class="mp-info-row">
                                        <span class="mp-info-label">Category:</span>
                                        <span class="mp-info-value"><?php echo htmlspecialchars($research['category']); ?></span>
                                    </div>
                                    <?php endif; ?>
                                    <?php if ($research['research_type']): ?>
                                    <div class="mp-info-row">
                                        <span class="mp-info-label">Type:</span>
                                        <span class="mp-info-value"><?php echo ucfirst(str_replace('_', ' ', $research['research_type'])); ?></span>
                                    </div>
                                    <?php endif; ?>
                                    <?php if ($research['start_date']): ?>
                                    <div class="mp-info-row">
                                        <span class="mp-info-label">Start Date:</span>
                                        <span class="mp-info-value"><?php echo date('M d, Y', strtotime($research['start_date'])); ?></span>
                                    </div>
                                    <?php endif; ?>
                                    <?php if ($research['end_date']): ?>
                                    <div class="mp-info-row">
                                        <span class="mp-info-label">End Date:</span>
                                        <span class="mp-info-value"><?php echo date('M d, Y', strtotime($research['end_date'])); ?></span>
                                    </div>
                                    <?php endif; ?>
                                    <?php if ($research['doi']): ?>
                                    <div class="mp-info-row">
                                        <span class="mp-info-label">DOI:</span>
                                        <span class="mp-info-value"><?php echo htmlspecialchars($research['doi']); ?></span>
                                    </div>
                                    <?php endif; ?>
                                    <?php if ($research['keywords']): ?>
                                    <div class="mp-info-row" style="grid-column: 1 / -1;">
                                        <span class="mp-info-label">Keywords:</span>
                                        <span class="mp-info-value"><?php echo htmlspecialchars($research['keywords']); ?></span>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sidebar -->
                    <div>
                        <!-- Collaborators -->
                        <div class="mp-card mp-mb-md">
                            <div class="mp-card-header">
                                <h5><i class="fas fa-users"></i> Collaborators</h5>
                            </div>
                            <div class="mp-card-body">
                                <?php if ($collabResult->num_rows > 0): ?>
                                    <div class="mp-activity-list">
                                        <?php 
                                        mysqli_data_seek($collabResult, 0);
                                        while ($collab = $collabResult->fetch_assoc()): 
                                            $roleColors = [
                                                'lead' => 'mp-badge-primary',
                                                'co_author' => 'mp-badge-success',
                                                'contributor' => 'mp-badge-info',
                                                'advisor' => 'mp-badge-warning',
                                                'reviewer' => 'mp-badge'
                                            ];
                                            $roleColor = $roleColors[$collab['role']] ?? 'mp-badge';
                                        ?>
                                            <div class="mp-activity-item">
                                                <div class="mp-activity-content">
                                                    <div class="mp-activity-text">
                                                        <strong><?php echo htmlspecialchars($collab['fullname']); ?></strong>
                                                    </div>
                                                    <div class="mp-activity-time"><?php echo htmlspecialchars($collab['email']); ?></div>
                                                </div>
                                                <span class="mp-badge <?php echo $roleColor; ?>">
                                                    <?php echo ucfirst(str_replace('_', ' ', $collab['role'])); ?>
                                                </span>
                                            </div>
                                        <?php endwhile; ?>
                                    </div>
                                <?php else: ?>
                                    <p style="color: var(--mp-gray-500); margin: 0;">No collaborators yet.</p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Actions -->
                        <?php if ($isCreator): ?>
                        <div class="mp-card">
                            <div class="mp-card-body">
                                <h6 class="mp-card-title">Actions</h6>
                                <div class="mp-btn-grid-2">
                                    <a href="../admin/research_collaborators.php?id=<?php echo $research_id; ?>" class="mp-btn mp-btn-outline mp-btn-primary">
                                        <i class="fas fa-user-plus"></i> Manage Collaborators
                                    </a>
                                    <a href="../admin/edit_research.php?id=<?php echo $research_id; ?>" class="mp-btn mp-btn-outline mp-btn-success">
                                        <i class="fas fa-edit"></i> Edit Research
                                    </a>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="mp-mt-lg">
                    <a href="member-research.php" class="mp-btn mp-btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Research
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- jQuery (required for some Bootstrap 5 features) -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
    <!-- Bootstrap 5.3.0 Bundle (includes Popper) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" integrity="sha384-geWF76RCwLtnZ8qwWowPQNguL3RmwHVBC9FhGdlKrxdiJJigb/j/68SIy3Te4Bkz" crossorigin="anonymous"></script>
</body>
</html>

<?php
$collabStmt->close();
$filesStmt->close();
mysqli_close($conn);
?>

