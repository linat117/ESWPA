<?php
session_start();

if (!isset($_SESSION['member_id'])) {
    header("Location: member-login.php");
    exit();
}

include 'include/config.php';
include 'include/access_control.php';

$member_id = $_SESSION['member_id'];

// Get member's research projects (created by or collaborated on)
$query = "SELECT DISTINCT rp.*, 
          (SELECT COUNT(*) FROM research_collaborators WHERE research_id = rp.id) as collaborator_count,
          (SELECT COUNT(*) FROM research_files WHERE research_id = rp.id) as file_count
          FROM research_projects rp
          LEFT JOIN research_collaborators rc ON rp.id = rc.research_id
          WHERE rp.created_by = ? OR rc.member_id = ?
          ORDER BY rp.updated_at DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $member_id, $member_id);
$stmt->execute();
$myResearch = $stmt->get_result();

// Get all published research (with access control)
$publishedQuery = "SELECT rp.*, r.fullname as creator_name,
                   (SELECT COUNT(*) FROM research_collaborators WHERE research_id = rp.id) as collaborator_count,
                   (SELECT COUNT(*) FROM research_files WHERE research_id = rp.id) as file_count
                   FROM research_projects rp
                   LEFT JOIN registrations r ON rp.created_by = r.id
                   WHERE rp.status = 'published'
                   ORDER BY rp.publication_date DESC, rp.created_at DESC
                   LIMIT 12";
$publishedResult = mysqli_query($conn, $publishedQuery);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Research - Member Dashboard</title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="assets/css/member-panel.css" rel="stylesheet">
</head>
<body>
    <?php include 'member-header-v1.2.php'; ?>
    
    <div class="mp-content-wrapper">
        <div class="mp-container">
            <div class="mp-content">
                <div class="mp-flex-between mp-mb-md">
                    <h2 class="mp-page-title"><i class="fas fa-search"></i> Research Projects</h2>
                    <a href="member-create-research.php" class="mp-btn mp-btn-primary">
                        <i class="fas fa-plus"></i> Create Research
                    </a>
                </div>

                <!-- My Research Section -->
                <div class="mp-card mp-mb-lg">
                    <div class="mp-card-header">
                        <h5><i class="fas fa-folder-open"></i> My Research Projects</h5>
                    </div>
                    <div class="mp-card-body">
                        <?php if ($myResearch->num_rows > 0): ?>
                            <div class="mp-grid-auto">
                                <?php while ($research = $myResearch->fetch_assoc()): 
                                    $statusClass = 'mp-status-' . str_replace('_', '_', $research['status']);
                                ?>
                                    <div class="mp-research-card">
                                        <div class="mp-flex-between mp-mb-sm">
                                            <h6 class="mp-research-title"><?php echo htmlspecialchars($research['title']); ?></h6>
                                            <span class="mp-status-badge <?php echo $statusClass; ?>">
                                                <?php echo ucfirst(str_replace('_', ' ', $research['status'])); ?>
                                            </span>
                                        </div>
                                        <?php if (!empty($research['abstract'])): ?>
                                            <p class="mp-research-abstract"><?php echo htmlspecialchars(substr($research['abstract'], 0, 100)) . '...'; ?></p>
                                        <?php endif; ?>
                                        <div class="mp-research-actions">
                                            <div class="mp-research-meta">
                                                <span><i class="fas fa-users"></i> <?php echo $research['collaborator_count']; ?></span>
                                                <span><i class="fas fa-file"></i> <?php echo $research['file_count']; ?></span>
                                            </div>
                                            <a href="member-research-detail.php?id=<?php echo $research['id']; ?>" class="mp-btn mp-btn-sm mp-btn-primary">
                                                View <i class="fas fa-arrow-right"></i>
                                            </a>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <div class="mp-alert mp-alert-info">
                                <i class="fas fa-info-circle"></i> You haven't created or collaborated on any research projects yet.
                                <a href="member-create-research.php" style="color: inherit; text-decoration: underline;">Create your first research project</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Published Research Library -->
                <div class="mp-card">
                    <div class="mp-card-header">
                        <h5><i class="fas fa-book"></i> Published Research Library</h5>
                    </div>
                    <div class="mp-card-body">
                        <?php if ($publishedResult->num_rows > 0): ?>
                            <div class="mp-grid-auto">
                                <?php while ($research = mysqli_fetch_assoc($publishedResult)): ?>
                                    <div class="mp-research-card">
                                        <h6 class="mp-research-title"><?php echo htmlspecialchars($research['title']); ?></h6>
                                        <?php if (!empty($research['abstract'])): ?>
                                            <p class="mp-research-abstract"><?php echo htmlspecialchars(substr($research['abstract'], 0, 120)) . '...'; ?></p>
                                        <?php endif; ?>
                                        <div class="mp-research-meta mp-mb-sm">
                                            <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($research['creator_name'] ?? 'Unknown'); ?></span>
                                            <?php if ($research['publication_date']): ?>
                                                <span><i class="fas fa-calendar"></i> <?php echo date('M Y', strtotime($research['publication_date'])); ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="mp-research-actions">
                                            <div class="mp-research-meta">
                                                <span><i class="fas fa-users"></i> <?php echo $research['collaborator_count']; ?></span>
                                                <span><i class="fas fa-file"></i> <?php echo $research['file_count']; ?></span>
                                            </div>
                                            <a href="member-research-detail.php?id=<?php echo $research['id']; ?>" class="mp-btn mp-btn-sm mp-btn-success">
                                                View <i class="fas fa-arrow-right"></i>
                                            </a>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                            <div class="mp-text-center mp-mt-md">
                                <a href="member-research-library.php" class="mp-btn mp-btn-outline mp-btn-success">
                                    <i class="fas fa-book-open"></i> Browse All Research
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="mp-alert mp-alert-info">
                                <i class="fas fa-info-circle"></i> No published research available yet.
                            </div>
                        <?php endif; ?>
                    </div>
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
$stmt->close();
mysqli_close($conn);
?>

