<?php
session_start();

if (!isset($_SESSION['member_id'])) {
    header("Location: member-login.php");
    exit();
}

include 'include/config.php';
require_once __DIR__ . '/include/bibliography_handler.php';
require_once __DIR__ . '/include/citation_generator.php';

$member_id = $_SESSION['member_id'];

// Handle form submissions (only for non-AJAX fallback)
if ($_SERVER["REQUEST_METHOD"] == "POST" && !isset($_POST['ajax'])) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create_collection') {
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $is_public = isset($_POST['is_public']);
        
        if (!empty($name)) {
            $result = createBibliographyCollection($member_id, $name, $description, $is_public);
            if ($result['success']) {
                $success = "Bibliography collection created successfully!";
            } else {
                $error = "Failed to create collection: " . $result['error'];
            }
        }
    } elseif ($action === 'add_item') {
        $collection_id = intval($_POST['collection_id'] ?? 0);
        $citation_text = trim($_POST['citation_text'] ?? '');
        $resource_id = !empty($_POST['resource_id']) ? intval($_POST['resource_id']) : null;
        $research_id = !empty($_POST['research_id']) ? intval($_POST['research_id']) : null;
        $notes = trim($_POST['notes'] ?? '');
        
        if ($collection_id > 0 && !empty($citation_text)) {
            $result = addBibliographyItem($collection_id, $citation_text, $resource_id, $research_id, $notes);
            if ($result['success']) {
                $success = "Item added to bibliography!";
            } else {
                $error = "Failed to add item: " . $result['error'];
            }
        }
    }
}

// Handle delete (only for non-AJAX fallback)
if (isset($_GET['delete_collection']) && !isset($_GET['ajax'])) {
    $collection_id = intval($_GET['delete_collection']);
    $result = deleteCollection($collection_id, $member_id);
    if ($result['success']) {
        $success = "Collection deleted successfully!";
    }
}

if (isset($_GET['delete_item']) && !isset($_GET['ajax'])) {
    $item_id = intval($_GET['delete_item']);
    $result = deleteBibliographyItem($item_id, $member_id);
    if ($result['success']) {
        $success = "Item deleted successfully!";
    }
}

// Get collections
$collections = getMemberCollections($member_id);

// Get selected collection items
$selected_collection_id = isset($_GET['collection_id']) ? intval($_GET['collection_id']) : null;
$collection_items = null;
if ($selected_collection_id) {
    $collection_items = getCollectionItems($selected_collection_id, $member_id);
}

// Get resources for adding items
$resourcesQuery = "SELECT id, title, author, section, publication_date FROM resources ORDER BY title";
$resourcesResult = mysqli_query($conn, $resourcesQuery);

// Get research projects
$researchQuery = "SELECT rp.*, r.fullname as creator_name 
                  FROM research_projects rp
                  LEFT JOIN registrations r ON rp.created_by = r.id
                  WHERE rp.status = 'published'
                  ORDER BY rp.title";
$researchResult = mysqli_query($conn, $researchQuery);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bibliography Manager - Member Dashboard</title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-9ndCyUaIbzAi2FUVXJi0CjmCapSmO7SnpJef0486qhLnuZ2cdeRhO02iuK6FUUVM" crossorigin="anonymous">
    <link href="assets/css/member-panel.css" rel="stylesheet">
    <style>
        /* Prevent style conflicts */
        .modal { z-index: 1055 !important; }
        .modal-backdrop { z-index: 1050 !important; }
        .dropdown-menu { z-index: 1000 !important; }
        .collection-card {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 15px;
            transition: all 0.3s;
            cursor: pointer;
        }
        .collection-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
        .collection-card.active {
            border-color: #0d6efd;
            background: #f0f7ff;
        }
        
        /* Mobile Optimization */
        @media (max-width: 768px) {
            main {
                margin-top: 65px !important;
                padding: 15px 10px !important;
            }
            
            .container-fluid {
                padding: 0 5px !important;
            }
            
            h2 {
                font-size: 1.3rem;
                margin-bottom: 15px !important;
            }
            
            .d-flex.justify-content-between {
                flex-direction: column;
                gap: 10px;
            }
            
            .d-flex.justify-content-between .btn {
                width: 100%;
                font-size: 0.9rem;
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
            
            .collection-card {
                padding: 12px !important;
                margin-bottom: 10px !important;
            }
            
            .collection-card h5 {
                font-size: 0.9rem !important;
                margin-bottom: 6px;
            }
            
            .collection-card p {
                font-size: 0.8rem !important;
                margin-bottom: 8px;
            }
            
            .row {
                margin: 0 -5px;
            }
            
            .row > [class*="col-"] {
                padding: 0 5px;
            }
            
            .list-group-item {
                padding: 10px 12px !important;
                font-size: 0.9rem;
            }
            
            .mb-4 {
                margin-bottom: 15px !important;
            }
        }
        
        @media (max-width: 480px) {
            main {
                padding: 10px 5px !important;
            }
            
            h2 {
                font-size: 1.1rem;
            }
            
            .collection-card {
                padding: 10px !important;
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
                    <h2 class="mp-page-title"><i class="fas fa-book"></i> Bibliography Manager</h2>
                    <button class="mp-btn mp-btn-primary mp-btn-sm" data-bs-toggle="modal" data-bs-target="#collectionModal">
                    <i class="fas fa-plus"></i> New Collection
                </button>
            </div>

            <?php if (isset($success)): ?>
                    <div class="mp-alert mp-alert-success">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                    <div class="mp-alert mp-alert-danger">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <div class="row">
                <!-- Collections List -->
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-folder"></i> My Collections</h5>
                        </div>
                        <div class="card-body">
                            <?php if (count($collections) > 0): ?>
                                <?php foreach ($collections as $collection): ?>
                                    <div class="collection-card <?php echo $selected_collection_id == $collection['id'] ? 'active' : ''; ?>" 
                                         onclick="window.location.href='?collection_id=<?php echo $collection['id']; ?>'">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="flex-grow-1">
                                                <h6 class="mb-1"><?php echo htmlspecialchars($collection['name']); ?></h6>
                                                <?php if (!empty($collection['description'])): ?>
                                                    <p class="text-muted small mb-1"><?php echo htmlspecialchars(substr($collection['description'], 0, 50)) . '...'; ?></p>
                                                <?php endif; ?>
                                                <small class="text-muted">
                                                    <i class="fas fa-file"></i> <?php echo $collection['item_count']; ?> items
                                                    <?php if ($collection['is_public']): ?>
                                                        <span class="badge bg-info ms-2">Public</span>
                                                    <?php endif; ?>
                                                </small>
                                            </div>
                                            <div class="ms-2">
                                                <button class="btn btn-sm btn-outline-danger" 
                                                        onclick="event.stopPropagation(); deleteCollection(<?php echo $collection['id']; ?>, this)">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="text-muted">No collections yet. Create your first bibliography collection!</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Collection Items -->
                <div class="col-lg-8">
                    <?php if ($selected_collection_id): ?>
                        <?php 
                        $selectedCollection = null;
                        foreach ($collections as $col) {
                            if ($col['id'] == $selected_collection_id) {
                                $selectedCollection = $col;
                                break;
                            }
                        }
                        ?>
                        <div class="card mb-3">
                            <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">
                                    <i class="fas fa-list"></i> <?php echo htmlspecialchars($selectedCollection['name']); ?>
                                </h5>
                                <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#addItemModal">
                                    <i class="fas fa-plus"></i> Add Item
                                </button>
                            </div>
                            <div class="card-body">
                                <?php if (count($collection_items) > 0): ?>
                                    <div class="list-group">
                                        <?php foreach ($collection_items as $item): ?>
                                            <div class="list-group-item">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <div class="flex-grow-1">
                                                        <p class="mb-1"><?php echo htmlspecialchars($item['citation_text']); ?></p>
                                                        <?php if ($item['resource_title']): ?>
                                                            <small class="text-muted">Resource: <?php echo htmlspecialchars($item['resource_title']); ?></small>
                                                        <?php elseif ($item['research_title']): ?>
                                                            <small class="text-muted">Research: <?php echo htmlspecialchars($item['research_title']); ?></small>
                                                        <?php endif; ?>
                                                        <?php if (!empty($item['notes'])): ?>
                                                            <div class="mt-2">
                                                                <small><strong>Notes:</strong> <?php echo htmlspecialchars($item['notes']); ?></small>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="ms-3">
                                                        <button class="btn btn-sm btn-outline-primary" onclick="copyText('<?php echo addslashes($item['citation_text']); ?>')">
                                                            <i class="fas fa-copy"></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-outline-danger"
                                                                onclick="deleteItem(<?php echo $item['id']; ?>, this)">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    
                                    <!-- Export Options -->
                                    <div class="mt-3">
                                        <button class="btn btn-outline-primary" onclick="exportBibliography('bibtex')">
                                            <i class="fas fa-download"></i> Export BibTeX
                                        </button>
                                        <button class="btn btn-outline-primary" onclick="exportBibliography('text')">
                                            <i class="fas fa-download"></i> Export Text
                                        </button>
                                    </div>
                                <?php else: ?>
                                    <p class="text-muted">No items in this collection yet. Add your first citation!</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="card">
                            <div class="card-body text-center">
                                <i class="fas fa-folder-open fa-3x text-muted mb-3"></i>
                                <p class="text-muted">Select a collection to view items or create a new collection</p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Create Collection Modal -->
    <div class="modal fade" id="collectionModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">New Bibliography Collection</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="create_collection">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Collection Name *</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="form-check">
                            <input type="checkbox" name="is_public" class="form-check-input" id="isPublic" value="1">
                            <label class="form-check-label" for="isPublic">Make this collection public</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Create Collection</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Add Item Modal -->
    <div class="modal fade" id="addItemModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Item to Bibliography</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="add_item">
                    <input type="hidden" name="collection_id" value="<?php echo $selected_collection_id; ?>">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Source Type</label>
                            <select class="form-select" id="itemSourceType" onchange="loadItemSources()">
                                <option value="resource">Resource</option>
                                <option value="research">Research Project</option>
                                <option value="manual">Manual Entry</option>
                            </select>
                        </div>
                        <div class="mb-3" id="itemSourceSelectDiv">
                            <label class="form-label">Select Source</label>
                            <select class="form-select" id="itemSourceSelect" onchange="generateItemCitation()">
                                <option value="">-- Select a source --</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Citation Format</label>
                            <select class="form-select" id="itemCitationFormat" onchange="generateItemCitation()">
                                <option value="apa" selected>APA</option>
                                <option value="mla">MLA</option>
                                <option value="chicago">Chicago</option>
                                <option value="harvard">Harvard</option>
                                <option value="ieee">IEEE</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Citation Text *</label>
                            <textarea name="citation_text" id="itemCitationText" class="form-control" rows="3" required></textarea>
                        </div>
                        <input type="hidden" name="resource_id" id="itemResourceId">
                        <input type="hidden" name="research_id" id="itemResearchId">
                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add to Bibliography</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- jQuery (required for some Bootstrap 5 features) -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
    <!-- Bootstrap 5.3.0 Bundle (includes Popper) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" integrity="sha384-geWF76RCwLtnZ8qwWowPQNguL3RmwHVBC9FhGdlKrxdiJJigb/j/68SIy3Te4Bkz" crossorigin="anonymous"></script>
    <!-- AJAX Utilities -->
    <script src="assets/js/ajax-utils.js"></script>
    <script>
        const resources = <?php echo json_encode(mysqli_fetch_all($resourcesResult, MYSQLI_ASSOC)); ?>;
        const research = <?php echo json_encode(mysqli_fetch_all($researchResult, MYSQLI_ASSOC)); ?>;

        function loadItemSources() {
            const sourceType = document.getElementById('itemSourceType').value;
            const sourceSelect = document.getElementById('itemSourceSelect');
            const sourceSelectDiv = document.getElementById('itemSourceSelectDiv');
            
            if (sourceType === 'manual') {
                sourceSelectDiv.style.display = 'none';
                document.getElementById('itemCitationText').value = '';
                document.getElementById('itemResourceId').value = '';
                document.getElementById('itemResearchId').value = '';
                return;
            }
            
            sourceSelectDiv.style.display = 'block';
            sourceSelect.innerHTML = '<option value="">-- Select a source --</option>';
            
            const sources = sourceType === 'resource' ? resources : research;
            sources.forEach((source, index) => {
                const option = document.createElement('option');
                option.value = index;
                option.textContent = source.title || source.name;
                sourceSelect.appendChild(option);
            });
        }

        function generateItemCitation() {
            const sourceType = document.getElementById('itemSourceType').value;
            if (sourceType === 'manual') return;
            
            const sourceIndex = document.getElementById('itemSourceSelect').value;
            const format = document.getElementById('itemCitationFormat').value;
            
            if (sourceIndex === '') return;
            
            const sources = sourceType === 'resource' ? resources : research;
            const source = sources[sourceIndex];
            
            fetch('include/generate_citation_ajax.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `source_type=${sourceType}&format=${format}&source_data=${encodeURIComponent(JSON.stringify(source))}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('itemCitationText').value = data.citation;
                    if (sourceType === 'resource') {
                        document.getElementById('itemResourceId').value = source.id;
                        document.getElementById('itemResearchId').value = '';
                    } else {
                        document.getElementById('itemResearchId').value = source.id;
                        document.getElementById('itemResourceId').value = '';
                    }
                }
            });
        }

        function copyText(text) {
            navigator.clipboard.writeText(text).then(() => {
                alert('Copied to clipboard!');
            });
        }

        function exportBibliography(format) {
            const collectionId = <?php echo $selected_collection_id ?? 'null'; ?>;
            if (!collectionId) return;
            
            window.location.href = `include/export_bibliography.php?collection_id=${collectionId}&format=${format}`;
        }
        
        // AJAX: Create Collection
        document.querySelector('#collectionModal form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'create_collection');
            formData.append('ajax', '1');
            
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating...';
            
            fetch('include/ajax_bibliography_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
                
                if (data.success) {
                    showAlert(data.message, 'success');
                    
                    // Close modal
                    const modal = bootstrap.Modal.getInstance(document.getElementById('collectionModal'));
                    modal.hide();
                    
                    // Add collection to list
                    if (data.collection) {
                        addCollectionToList(data.collection);
                    }
                    
                    // Reset form
                    this.reset();
                } else {
                    showAlert(data.error || 'Failed to create collection', 'danger');
                }
            })
            .catch(error => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
                console.error('Error:', error);
                showAlert('An error occurred while creating the collection', 'danger');
            });
        });
        
        // AJAX: Add Item
        document.querySelector('#addItemModal form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'add_item');
            formData.append('ajax', '1');
            
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';
            
            fetch('include/ajax_bibliography_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
                
                if (data.success) {
                    showAlert(data.message, 'success');
                    
                    // Close modal
                    const modal = bootstrap.Modal.getInstance(document.getElementById('addItemModal'));
                    modal.hide();
                    
                    // Add item to list
                    if (data.item) {
                        addItemToList(data.item);
                    }
                    
                    // Reset form
                    this.reset();
                    loadItemSources();
                } else {
                    showAlert(data.error || 'Failed to add item', 'danger');
                }
            })
            .catch(error => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
                console.error('Error:', error);
                showAlert('An error occurred while adding the item', 'danger');
            });
        });
        
        // AJAX: Delete Collection
        function deleteCollection(collectionId, element) {
            if (!confirm('Are you sure you want to delete this collection? All items will be deleted.')) {
                return;
            }
            
            const originalHTML = element.innerHTML;
            element.disabled = true;
            element.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            
            fetch('include/ajax_bibliography_handler.php?action=delete_collection&collection_id=' + collectionId, {
                method: 'GET'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert(data.message, 'success');
                    // Remove collection from list
                    element.closest('.collection-card').remove();
                    
                    // If no collections left, show message
                    const collectionsContainer = document.querySelector('.card-body');
                    if (collectionsContainer && !collectionsContainer.querySelector('.collection-card')) {
                        collectionsContainer.innerHTML = '<p class="text-muted">No collections yet. Create your first bibliography collection!</p>';
                    }
                    
                    // If deleted collection was selected, redirect
                    const urlParams = new URLSearchParams(window.location.search);
                    if (urlParams.get('collection_id') == collectionId) {
                        window.location.href = 'member-bibliography.php';
                    }
                } else {
                    element.disabled = false;
                    element.innerHTML = originalHTML;
                    showAlert(data.error || 'Failed to delete collection', 'danger');
                }
            })
            .catch(error => {
                element.disabled = false;
                element.innerHTML = originalHTML;
                console.error('Error:', error);
                showAlert('An error occurred while deleting the collection', 'danger');
            });
        }
        
        // AJAX: Delete Item
        function deleteItem(itemId, element) {
            if (!confirm('Are you sure you want to delete this item?')) {
                return;
            }
            
            const originalHTML = element.innerHTML;
            element.disabled = true;
            element.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            
            fetch('include/ajax_bibliography_handler.php?action=delete_item&item_id=' + itemId, {
                method: 'GET'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert(data.message, 'success');
                    // Remove item from list
                    element.closest('.list-group-item').remove();
                    
                    // Check if no items left
                    const itemsList = document.querySelector('.list-group');
                    if (itemsList && itemsList.children.length === 0) {
                        itemsList.parentElement.innerHTML = '<p class="text-muted">No items in this collection yet. Add your first citation!</p>';
                    }
                } else {
                    element.disabled = false;
                    element.innerHTML = originalHTML;
                    showAlert(data.error || 'Failed to delete item', 'danger');
                }
            })
            .catch(error => {
                element.disabled = false;
                element.innerHTML = originalHTML;
                console.error('Error:', error);
                showAlert('An error occurred while deleting the item', 'danger');
            });
        }
        
        function addCollectionToList(collection) {
            const collectionsContainer = document.querySelector('.card-body');
            if (!collectionsContainer) return;
            
            // Remove "no collections" message if exists
            const noCollectionsMsg = collectionsContainer.querySelector('p.text-muted');
            if (noCollectionsMsg) {
                noCollectionsMsg.remove();
            }
            
            // Create collection card
            const collectionCard = document.createElement('div');
            collectionCard.className = 'collection-card';
            collectionCard.onclick = function() {
                window.location.href = '?collection_id=' + collection.id;
            };
            
            collectionCard.innerHTML = `
                <div class="d-flex justify-content-between align-items-start">
                    <div class="flex-grow-1">
                        <h6 class="mb-1">${escapeHtml(collection.name)}</h6>
                        ${collection.description ? `<p class="text-muted small mb-1">${escapeHtml(collection.description.substring(0, 50))}...</p>` : ''}
                        <small class="text-muted">
                            <i class="fas fa-file"></i> ${collection.item_count || 0} items
                            ${collection.is_public == 1 ? '<span class="badge bg-info ms-2">Public</span>' : ''}
                        </small>
                    </div>
                    <div class="ms-2">
                        <button class="btn btn-sm btn-outline-danger" 
                                onclick="event.stopPropagation(); deleteCollection(${collection.id}, this)">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            `;
            
            collectionsContainer.insertBefore(collectionCard, collectionsContainer.firstChild);
        }
        
        function addItemToList(item) {
            const itemsList = document.querySelector('.list-group');
            if (!itemsList) return;
            
            // Remove "no items" message if exists
            const noItemsMsg = itemsList.parentElement.querySelector('p.text-muted');
            if (noItemsMsg) {
                noItemsMsg.remove();
            }
            
            // Create list item
            const listItem = document.createElement('div');
            listItem.className = 'list-group-item';
            
            const citationText = escapeHtml(item.citation_text);
            const copyTextJs = JSON.stringify(item.citation_text);
            
            listItem.innerHTML = `
                <div class="d-flex justify-content-between align-items-start">
                    <div class="flex-grow-1">
                        <p class="mb-1">${citationText}</p>
                        ${item.resource_title ? `<small class="text-muted">Resource: ${escapeHtml(item.resource_title)}</small>` : ''}
                        ${item.research_title ? `<small class="text-muted">Research: ${escapeHtml(item.research_title)}</small>` : ''}
                        ${item.notes ? `<div class="mt-2"><small><strong>Notes:</strong> ${escapeHtml(item.notes)}</small></div>` : ''}
                    </div>
                    <div class="ms-3">
                        <button class="btn btn-sm btn-outline-primary" onclick="copyText(${copyTextJs})">
                            <i class="fas fa-copy"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger" onclick="deleteItem(${item.id}, this)">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            `;
            
            itemsList.insertBefore(listItem, itemsList.firstChild);
        }
        
        function escapeHtml(text) {
            if (text == null) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        loadItemSources();
    </script>
</body>
</html>

<?php
mysqli_close($conn);
?>

