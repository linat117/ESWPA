<?php
session_start();

if (!isset($_SESSION['member_id'])) {
    header("Location: member-login.php");
    exit();
}

include 'include/config.php';
include 'include/access_control.php';

$member_id = $_SESSION['member_id'];

// Get filter parameters
$filter_category = $_GET['category'] ?? '';
$filter_type = $_GET['type'] ?? '';
$search = $_GET['search'] ?? '';

$where = ["rp.status = 'published'"];
$params = [];
$types = '';

if ($filter_category) {
    $where[] = "rp.category = ?";
    $params[] = $filter_category;
    $types .= 's';
}
if ($filter_type) {
    $where[] = "rp.research_type = ?";
    $params[] = $filter_type;
    $types .= 's';
}
if ($search) {
    $where[] = "(rp.title LIKE ? OR rp.description LIKE ? OR rp.abstract LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $types .= 'sss';
}

$whereClause = implode(' AND ', $where);

$query = "SELECT rp.*, r.fullname as creator_name,
          (SELECT COUNT(*) FROM research_collaborators WHERE research_id = rp.id) as collaborator_count,
          (SELECT COUNT(*) FROM research_files WHERE research_id = rp.id) as file_count
          FROM research_projects rp
          LEFT JOIN registrations r ON rp.created_by = r.id
          WHERE $whereClause
          ORDER BY rp.publication_date DESC, rp.created_at DESC";

if (!empty($params)) {
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = mysqli_query($conn, $query);
}

// Get unique categories for filter
$categoriesQuery = "SELECT DISTINCT category FROM research_projects WHERE category IS NOT NULL AND category != '' AND status = 'published' ORDER BY category";
$categoriesResult = mysqli_query($conn, $categoriesQuery);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Research Library - Member Dashboard</title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="assets/css/member-panel.css" rel="stylesheet">
    <style>
        .research-card {
            border: 1px solid #e0e0e0;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 20px;
            transition: all 0.3s;
            background: white;
            height: 100%;
        }
        .research-card:hover {
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            transform: translateY(-3px);
        }
        
        /* Mobile Optimization - Compact Grid with Smaller Fonts */
        @media (max-width: 768px) {
            main {
                margin-top: 65px !important;
                padding: 15px 10px !important;
            }
            
            .container-fluid {
                padding: 0 5px !important;
            }
            
            h2 {
                font-size: 1.2rem !important;
                margin-bottom: 12px !important;
            }
            
            .d-flex.justify-content-between {
                flex-direction: column;
                gap: 10px;
            }
            
            .d-flex.justify-content-between .btn {
                width: 100%;
                font-size: 0.85rem;
                padding: 7px 12px;
            }
            
            .card {
                margin-bottom: 12px !important;
                border-radius: 8px;
            }
            
            .card-header {
                padding: 10px 12px !important;
            }
            
            .card-body {
                padding: 12px !important;
            }
            
            .form-label {
                font-size: 0.85rem;
                margin-bottom: 5px;
            }
            
            .form-control, .form-select {
                font-size: 0.85rem;
                padding: 7px 10px;
            }
            
            .row {
                margin: 0 -5px;
            }
            
            .row > [class*="col-"] {
                padding: 0 5px;
                margin-bottom: 10px;
            }
            
            .research-card {
                padding: 12px !important;
                margin-bottom: 10px !important;
                border-radius: 8px;
            }
            
            .research-card h5 {
                font-size: 0.9rem !important;
                margin-bottom: 8px;
                line-height: 1.3;
            }
            
            .research-card p {
                font-size: 0.8rem !important;
                margin-bottom: 8px;
                line-height: 1.4;
            }
            
            .research-card small {
                font-size: 0.75rem !important;
            }
            
            .research-card .btn-sm {
                font-size: 0.75rem;
                padding: 5px 10px;
            }
            
            .alert {
                padding: 10px 12px;
                font-size: 0.85rem;
            }
        }
        
        @media (max-width: 480px) {
            main {
                padding: 10px 5px !important;
            }
            
            h2 {
                font-size: 1.1rem !important;
            }
            
            .research-card {
                padding: 10px !important;
            }
            
            .research-card h5 {
                font-size: 0.85rem !important;
            }
            
            .research-card p {
                font-size: 0.75rem !important;
            }
        }
    </style>
</head>
<body>
    <?php include 'member-header-v1.2.php'; ?>
    
    <div class="mp-content-wrapper">
        <div class="mp-container">
            <div class="mp-content">
                <div class="mp-flex-between mp-mb-md">
                    <h2 class="mp-page-title"><i class="fas fa-book-open"></i> Research Library</h2>
                    <a href="member-research.php" class="mp-btn mp-btn-outline-primary mp-btn-sm">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
            </div>

            <!-- Filters -->
                <div class="mp-card mp-mb-lg">
                    <div class="mp-card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <label class="mp-form-label">Search</label>
                            <input type="text" name="search" class="mp-form-control" 
                                   value="<?php echo htmlspecialchars($search); ?>"
                                   placeholder="Search by title, description...">
                        </div>
                        <div class="col-md-3">
                            <label class="mp-form-label">Category</label>
                            <select name="category" class="mp-form-control">
                                <option value="">All Categories</option>
                                <?php
                                while ($cat = mysqli_fetch_assoc($categoriesResult)) {
                                    echo '<option value="' . htmlspecialchars($cat['category']) . '" ' . 
                                         ($filter_category === $cat['category'] ? 'selected' : '') . '>' . 
                                         htmlspecialchars($cat['category']) . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="mp-form-label">Research Type</label>
                            <select name="type" class="mp-form-control">
                                <option value="">All Types</option>
                                <option value="thesis" <?php echo $filter_type === 'thesis' ? 'selected' : ''; ?>>Thesis</option>
                                <option value="journal_article" <?php echo $filter_type === 'journal_article' ? 'selected' : ''; ?>>Journal Article</option>
                                <option value="case_study" <?php echo $filter_type === 'case_study' ? 'selected' : ''; ?>>Case Study</option>
                                <option value="survey" <?php echo $filter_type === 'survey' ? 'selected' : ''; ?>>Survey</option>
                                <option value="experiment" <?php echo $filter_type === 'experiment' ? 'selected' : ''; ?>>Experiment</option>
                                <option value="review" <?php echo $filter_type === 'review' ? 'selected' : ''; ?>>Review</option>
                                <option value="other" <?php echo $filter_type === 'other' ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="mp-btn mp-btn-primary mp-btn-full">
                                <i class="fas fa-filter"></i> Filter
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Research List -->
            <?php if ($result->num_rows > 0): ?>
                    <div class="mp-grid-auto">
                    <?php while ($research = $result->fetch_assoc()): ?>
                            <div class="mp-card">
                                <div class="mp-card-header mp-card-header-primary">
                                    <h5><?php echo htmlspecialchars($research['title']); ?></h5>
                                </div>
                                <div class="mp-card-body">
                                <?php if (!empty($research['abstract'])): ?>
                                        <p style="font-size: 0.8125rem; color: var(--mp-gray-600); margin-bottom: 12px;"><?php echo htmlspecialchars(substr($research['abstract'], 0, 150)) . '...'; ?></p>
                                <?php endif; ?>
                                
                                    <div style="margin-bottom: 12px; font-size: 0.75rem; color: var(--mp-gray-500);">
                                        <div><i class="fas fa-user"></i> <?php echo htmlspecialchars($research['creator_name'] ?? 'Unknown'); ?></div>
                                        <?php if ($research['publication_date']): ?>
                                            <div><i class="fas fa-calendar"></i> <?php echo date('M Y', strtotime($research['publication_date'])); ?></div>
                                        <?php endif; ?>
                                        <?php if ($research['category']): ?>
                                            <div><i class="fas fa-tag"></i> <?php echo htmlspecialchars($research['category']); ?></div>
                                        <?php endif; ?>
                                </div>
                                
                                    <div class="mp-flex-between" style="margin-top: 12px;">
                                        <div style="font-size: 0.75rem; color: var(--mp-gray-500);">
                                        <i class="fas fa-users"></i> <?php echo $research['collaborator_count']; ?> 
                                            <i class="fas fa-file" style="margin-left: 8px;"></i> <?php echo $research['file_count']; ?>
                                        </div>
                                        <a href="member-research-detail.php?id=<?php echo $research['id']; ?>" class="mp-btn mp-btn-primary mp-btn-sm">
                                        View <i class="fas fa-arrow-right"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                    <div class="mp-alert mp-alert-info">
                    <i class="fas fa-info-circle"></i> No published research found matching your criteria.
                </div>
            <?php endif; ?>
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
if (isset($stmt)) {
    $stmt->close();
}
mysqli_close($conn);
?>

