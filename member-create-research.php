<?php
session_start();

if (!isset($_SESSION['member_id'])) {
    header("Location: member-login.php");
    exit();
}

include 'include/config.php';

$member_id = $_SESSION['member_id'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $abstract = trim($_POST['abstract'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $research_type = $_POST['research_type'] ?? null;
    $keywords = trim($_POST['keywords'] ?? '');
    
    if (empty($title) || empty($description)) {
        $error = "Please fill in all required fields";
    } else {
        $query = "INSERT INTO research_projects 
                  (title, description, abstract, status, category, research_type, keywords, created_by) 
                  VALUES (?, ?, ?, 'draft', ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssssssi", $title, $description, $abstract, $category, $research_type, $keywords, $member_id);
        
        if ($stmt->execute()) {
            $research_id = $stmt->insert_id;
            $stmt->close();
            
            // Create initial version
            $versionQuery = "INSERT INTO research_versions 
                            (research_id, version_number, title, description, changed_by) 
                            VALUES (?, '1.0', ?, ?, ?)";
            $versionStmt = $conn->prepare($versionQuery);
            $versionStmt->bind_param("issi", $research_id, $title, $description, $member_id);
            $versionStmt->execute();
            $versionStmt->close();
            
            // Add creator as lead collaborator
            $collabQuery = "INSERT INTO research_collaborators (research_id, member_id, role) VALUES (?, ?, 'lead')";
            $collabStmt = $conn->prepare($collabQuery);
            $collabStmt->bind_param("ii", $research_id, $member_id);
            $collabStmt->execute();
            $collabStmt->close();
            
            header("Location: member-research-detail.php?id=" . $research_id . "&success=Research project created successfully");
            exit();
        } else {
            $error = "Failed to create research project: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Research - Member Dashboard</title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="assets/css/member-panel.css" rel="stylesheet">
</head>
<body>
    <?php include 'member-header-v1.2.php'; ?>
    
    <div class="mp-content-wrapper">
        <div class="mp-container">
            <div class="mp-content" style="max-width: 900px;">
                <div class="mp-flex-between mp-mb-md">
                    <h2 class="mp-page-title"><i class="fas fa-plus-circle"></i> Create Research Project</h2>
                    <a href="member-research.php" class="mp-btn mp-btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                </div>

                <div class="mp-card">
                    <div class="mp-card-body">
                        <?php if (isset($error)): ?>
                            <div class="mp-alert mp-alert-danger"><?php echo htmlspecialchars($error); ?></div>
                        <?php endif; ?>

                        <form method="POST">
                            <div class="mp-form-group">
                                <label class="mp-form-label">Research Title *</label>
                                <input type="text" name="title" class="mp-form-control" required 
                                       value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>"
                                       placeholder="Enter your research project title">
                            </div>

                            <div class="mp-form-group">
                                <label class="mp-form-label">Description *</label>
                                <textarea name="description" class="mp-form-control" rows="5" required 
                                          placeholder="Provide a detailed description of your research project"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                            </div>

                            <div class="mp-form-group">
                                <label class="mp-form-label">Abstract</label>
                                <textarea name="abstract" class="mp-form-control" rows="3" 
                                          placeholder="Brief summary or abstract of your research"><?php echo htmlspecialchars($_POST['abstract'] ?? ''); ?></textarea>
                            </div>

                            <div class="mp-grid-2">
                                <div class="mp-form-group">
                                    <label class="mp-form-label">Category</label>
                                    <input type="text" name="category" class="mp-form-control" 
                                           value="<?php echo htmlspecialchars($_POST['category'] ?? ''); ?>"
                                           placeholder="e.g., Social Work, Psychology">
                                </div>

                                <div class="mp-form-group">
                                    <label class="mp-form-label">Research Type</label>
                                    <select name="research_type" class="mp-form-control">
                                        <option value="">Select Type</option>
                                        <option value="thesis" <?php echo (($_POST['research_type'] ?? '') === 'thesis') ? 'selected' : ''; ?>>Thesis</option>
                                        <option value="journal_article" <?php echo (($_POST['research_type'] ?? '') === 'journal_article') ? 'selected' : ''; ?>>Journal Article</option>
                                        <option value="case_study" <?php echo (($_POST['research_type'] ?? '') === 'case_study') ? 'selected' : ''; ?>>Case Study</option>
                                        <option value="survey" <?php echo (($_POST['research_type'] ?? '') === 'survey') ? 'selected' : ''; ?>>Survey</option>
                                        <option value="experiment" <?php echo (($_POST['research_type'] ?? '') === 'experiment') ? 'selected' : ''; ?>>Experiment</option>
                                        <option value="review" <?php echo (($_POST['research_type'] ?? '') === 'review') ? 'selected' : ''; ?>>Review</option>
                                        <option value="other" <?php echo (($_POST['research_type'] ?? '') === 'other') ? 'selected' : ''; ?>>Other</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mp-form-group">
                                <label class="mp-form-label">Keywords</label>
                                <input type="text" name="keywords" class="mp-form-control" 
                                       value="<?php echo htmlspecialchars($_POST['keywords'] ?? ''); ?>"
                                       placeholder="Comma-separated keywords">
                                <small style="color: var(--mp-gray-500); font-size: 0.75rem;">Separate keywords with commas</small>
                            </div>

                            <div class="mp-alert mp-alert-info">
                                <i class="fas fa-info-circle"></i> Your research will be created as a draft. You can add files, collaborators, and publish it later.
                            </div>

                            <div class="mp-btn-grid-2">
                                <button type="submit" class="mp-btn mp-btn-primary">
                                    <i class="fas fa-save"></i> Create Research Project
                                </button>
                                <a href="member-research.php" class="mp-btn mp-btn-secondary">
                                    <i class="fas fa-times"></i> Cancel
                                </a>
                            </div>
                        </form>
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
mysqli_close($conn);
?>

