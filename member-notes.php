<?php
session_start();

if (!isset($_SESSION['member_id'])) {
    header("Location: member-login.php");
    exit();
}

include 'include/config.php';
require_once __DIR__ . '/include/notes_handler.php';

$member_id = $_SESSION['member_id'];

// Handle form submissions (only for non-AJAX fallback)
if ($_SERVER["REQUEST_METHOD"] == "POST" && !isset($_POST['ajax'])) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create') {
        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $research_id = !empty($_POST['research_id']) ? intval($_POST['research_id']) : null;
        $resource_id = !empty($_POST['resource_id']) ? intval($_POST['resource_id']) : null;
        $tags = trim($_POST['tags'] ?? '');
        $is_shared = isset($_POST['is_shared']);
        
        if (!empty($title) && !empty($content)) {
            $result = createNote($member_id, $title, $content, $research_id, $resource_id, $tags, $is_shared);
            if ($result['success']) {
                $success = "Note created successfully!";
            } else {
                $error = "Failed to create note: " . $result['error'];
            }
        } else {
            $error = "Please fill in title and content";
        }
    } elseif ($action === 'update') {
        $note_id = intval($_POST['note_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $tags = trim($_POST['tags'] ?? '');
        $is_shared = isset($_POST['is_shared']);
        
        if ($note_id > 0 && !empty($title) && !empty($content)) {
            $result = updateNote($note_id, $member_id, $title, $content, $tags, $is_shared);
            if ($result['success']) {
                $success = "Note updated successfully!";
            } else {
                $error = "Failed to update note: " . $result['error'];
            }
        }
    }
}

// Handle delete (only for non-AJAX fallback)
if (isset($_GET['delete']) && !isset($_GET['ajax'])) {
    $note_id = intval($_GET['delete']);
    $result = deleteNote($note_id, $member_id);
    if ($result['success']) {
        $success = "Note deleted successfully!";
    } else {
        $error = "Failed to delete note";
    }
}

// Get filter parameters
$filter_research = isset($_GET['research_id']) ? intval($_GET['research_id']) : null;
$filter_resource = isset($_GET['resource_id']) ? intval($_GET['resource_id']) : null;
$search = $_GET['search'] ?? '';

// Get notes
$notes = getMemberNotes($member_id, $filter_research, $filter_resource, $search);

// Get research projects for filter
$researchQuery = "SELECT id, title FROM research_projects WHERE created_by = ? OR id IN (SELECT research_id FROM research_collaborators WHERE member_id = ?) ORDER BY title";
$researchStmt = $conn->prepare($researchQuery);
$researchStmt->bind_param("ii", $member_id, $member_id);
$researchStmt->execute();
$researchResult = $researchStmt->get_result();

// Get resources for filter
$resourcesQuery = "SELECT id, title FROM resources ORDER BY title";
$resourcesResult = mysqli_query($conn, $resourcesQuery);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Notes - Member Dashboard</title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-9ndCyUaIbzAi2FUVXJi0CjmCapSmO7SnpJef0486qhLnuZ2cdeRhO02iuK6FUUVM" crossorigin="anonymous">
    <link href="assets/css/member-panel.css" rel="stylesheet">
    <script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
    <style>
        /* Prevent style conflicts */
        .modal { z-index: 1055 !important; }
        .modal-backdrop { z-index: 1050 !important; }
        .dropdown-menu { z-index: 1000 !important; }
        .note-card {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            transition: all 0.3s;
        }
        .note-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .note-tags {
            margin-top: 10px;
        }
        .tag {
            display: inline-block;
            padding: 4px 10px;
            background: #e9ecef;
            border-radius: 12px;
            font-size: 12px;
            margin-right: 5px;
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
            
            .note-card {
                padding: 12px !important;
                margin-bottom: 12px !important;
            }
            
            .note-card h5 {
                font-size: 0.9rem !important;
                margin-bottom: 6px;
            }
            
            .note-card p {
                font-size: 0.85rem !important;
                line-height: 1.5;
            }
            
            .note-tags {
                margin-top: 8px;
            }
            
            .tag {
                font-size: 10px;
                padding: 3px 8px;
            }
            
            .row {
                margin: 0 -5px;
            }
            
            .row > [class*="col-"] {
                padding: 0 5px;
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
            
            .note-card {
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
                    <h2 class="mp-page-title"><i class="fas fa-sticky-note"></i> My Notes</h2>
                    <button class="mp-btn mp-btn-primary mp-btn-sm" data-bs-toggle="modal" data-bs-target="#noteModal" onclick="openNoteModal()">
                    <i class="fas fa-plus"></i> New Note
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

            <!-- Filters -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Search</label>
                            <input type="text" name="search" class="form-control" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search notes...">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Research Project</label>
                            <select name="research_id" class="form-select">
                                <option value="">All Research</option>
                                <?php while ($research = $researchResult->fetch_assoc()): ?>
                                    <option value="<?php echo $research['id']; ?>" <?php echo $filter_research == $research['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($research['title']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Resource</label>
                            <select name="resource_id" class="form-select">
                                <option value="">All Resources</option>
                                <?php while ($resource = mysqli_fetch_assoc($resourcesResult)): ?>
                                    <option value="<?php echo $resource['id']; ?>" <?php echo $filter_resource == $resource['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($resource['title']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">Filter</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Notes List -->
            <?php if (count($notes) > 0): ?>
                <div class="row">
                    <?php foreach ($notes as $note): ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="note-card">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h5 class="mb-0"><?php echo htmlspecialchars($note['title']); ?></h5>
                                    <div class="btn-group">
                                        <button class="btn btn-sm btn-outline-primary" onclick="editNote(<?php echo htmlspecialchars(json_encode($note, JSON_HEX_APOS | JSON_HEX_QUOT)); ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger" onclick="deleteNote(<?php echo $note['id']; ?>, this)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="note-content">
                                    <?php echo substr(strip_tags($note['content']), 0, 150) . '...'; ?>
                                </div>
                                <?php if (!empty($note['tags'])): ?>
                                    <div class="note-tags">
                                        <?php 
                                        $tags = explode(',', $note['tags']);
                                        foreach ($tags as $tag): 
                                        ?>
                                            <span class="tag"><?php echo htmlspecialchars(trim($tag)); ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                                <div class="mt-2">
                                    <small class="text-muted">
                                        <?php echo date('M d, Y', strtotime($note['updated_at'])); ?>
                                        <?php if ($note['is_shared']): ?>
                                            <span class="badge bg-info ms-2">Shared</span>
                                        <?php endif; ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> No notes found. Create your first note!
                </div>
            <?php endif; ?>
        </div>
        </div>
    </div>

    <!-- Note Modal -->
    <div class="modal fade" id="noteModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="noteModalTitle">New Note</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="noteForm">
                    <input type="hidden" name="action" id="noteAction" value="create">
                    <input type="hidden" name="note_id" id="noteId">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Title *</label>
                            <input type="text" name="title" id="noteTitle" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Content *</label>
                            <textarea name="content" id="noteContent" class="form-control" rows="10" required></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Link to Research Project</label>
                                <select name="research_id" class="form-select" id="noteResearchId">
                                    <option value="">None</option>
                                    <?php 
                                    mysqli_data_seek($researchResult, 0);
                                    while ($research = $researchResult->fetch_assoc()): 
                                    ?>
                                        <option value="<?php echo $research['id']; ?>"><?php echo htmlspecialchars($research['title']); ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Link to Resource</label>
                                <select name="resource_id" class="form-select" id="noteResourceId">
                                    <option value="">None</option>
                                    <?php 
                                    mysqli_data_seek($resourcesResult, 0);
                                    while ($resource = mysqli_fetch_assoc($resourcesResult)): 
                                    ?>
                                        <option value="<?php echo $resource['id']; ?>"><?php echo htmlspecialchars($resource['title']); ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tags</label>
                            <input type="text" name="tags" id="noteTags" class="form-control" placeholder="Comma-separated tags">
                        </div>
                        <div class="form-check">
                            <input type="checkbox" name="is_shared" id="noteIsShared" class="form-check-input" value="1">
                            <label class="form-check-label" for="noteIsShared">Share with collaborators</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Note</button>
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
        function openNoteModal() {
            document.getElementById('noteModalTitle').textContent = 'New Note';
            document.getElementById('noteAction').value = 'create';
            document.getElementById('noteId').value = '';
            document.getElementById('noteForm').reset();
        }

        function editNote(note) {
            document.getElementById('noteModalTitle').textContent = 'Edit Note';
            document.getElementById('noteAction').value = 'update';
            document.getElementById('noteId').value = note.id;
            document.getElementById('noteTitle').value = note.title;
            document.getElementById('noteContent').value = note.content;
            document.getElementById('noteResearchId').value = note.research_id || '';
            document.getElementById('noteResourceId').value = note.resource_id || '';
            document.getElementById('noteTags').value = note.tags || '';
            document.getElementById('noteIsShared').checked = note.is_shared == 1;
            
            const modal = new bootstrap.Modal(document.getElementById('noteModal'));
            modal.show();
        }
        
        // AJAX form submission
        document.getElementById('noteForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('ajax', '1');
            
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
            
            fetch('include/ajax_notes_handler.php', {
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
                    const modal = bootstrap.Modal.getInstance(document.getElementById('noteModal'));
                    modal.hide();
                    
                    // Update notes list
                    if (data.note) {
                        if (document.getElementById('noteId').value) {
                            // Update existing note in list
                            updateNoteInList(data.note);
                        } else {
                            // Add new note to list
                            addNoteToList(data.note);
                        }
                    }
                    
                    // Reset form
                    document.getElementById('noteForm').reset();
                    document.getElementById('noteAction').value = 'create';
                    document.getElementById('noteId').value = '';
                } else {
                    showAlert(data.error || 'Failed to save note', 'danger');
                }
            })
            .catch(error => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
                console.error('Error:', error);
                showAlert('An error occurred while saving the note', 'danger');
            });
        });
        
        function deleteNote(noteId, element) {
            if (!confirm('Are you sure you want to delete this note?')) {
                return;
            }
            
            // Show loading state
            const originalHTML = element.innerHTML;
            element.disabled = true;
            element.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            
            // Send AJAX request
            fetch('include/ajax_notes_handler.php?action=delete&note_id=' + noteId, {
                method: 'GET'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert(data.message, 'success');
                    // Remove note from list
                    element.closest('.note-card').parentElement.remove();
                    
                    // Check if no notes left
                    const notesContainer = document.querySelector('.row');
                    if (notesContainer && notesContainer.children.length === 0) {
                        notesContainer.parentElement.innerHTML = `
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> No notes found. Create your first note!
                            </div>
                        `;
                    }
                } else {
                    element.disabled = false;
                    element.innerHTML = originalHTML;
                    showAlert(data.error || 'Failed to delete note', 'danger');
                }
            })
            .catch(error => {
                element.disabled = false;
                element.innerHTML = originalHTML;
                console.error('Error:', error);
                showAlert('An error occurred while deleting the note', 'danger');
            });
        }
        
        function addNoteToList(note) {
            const notesContainer = document.querySelector('.row');
            if (!notesContainer) {
                // Create container if it doesn't exist
                const alertDiv = document.querySelector('.alert-info');
                if (alertDiv) {
                    alertDiv.remove();
                }
                const newContainer = document.createElement('div');
                newContainer.className = 'row';
                newContainer.id = 'notesContainer';
                document.querySelector('.container-fluid').appendChild(newContainer);
                return addNoteToList(note);
            }
            
            // Remove "no notes" message if exists
            const alertDiv = notesContainer.parentElement.querySelector('.alert-info');
            if (alertDiv) {
                alertDiv.remove();
            }
            
            // Create note card
            const noteCard = createNoteCard(note);
            notesContainer.insertBefore(noteCard, notesContainer.firstChild);
        }
        
        function updateNoteInList(note) {
            // Find existing note card and update it
            const noteCards = document.querySelectorAll('.note-card');
            noteCards.forEach(card => {
                const editBtn = card.querySelector('button[onclick*="editNote"]');
                if (editBtn && editBtn.getAttribute('onclick').includes(`"id":${note.id}`)) {
                    // Replace the card with updated version
                    const newCard = createNoteCard(note);
                    card.parentElement.replaceChild(newCard, card);
                }
            });
        }
        
        function createNoteCard(note) {
            const colDiv = document.createElement('div');
            colDiv.className = 'col-md-6 col-lg-4';
            
            const noteCard = document.createElement('div');
            noteCard.className = 'note-card';
            
            // Format tags
            let tagsHtml = '';
            if (note.tags) {
                const tags = note.tags.split(',').map(t => t.trim()).filter(t => t);
                tagsHtml = '<div class="note-tags">' + 
                    tags.map(tag => `<span class="tag">${escapeHtml(tag)}</span>`).join('') + 
                    '</div>';
            }
            
            // Format date
            const date = new Date(note.updated_at);
            const dateStr = date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
            
            // Note content preview (strip HTML and limit length)
            const contentPreview = note.content.replace(/<[^>]*>/g, '').substring(0, 150) + '...';
            
            noteCard.innerHTML = `
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <h5 class="mb-0">${escapeHtml(note.title)}</h5>
                    <div class="btn-group">
                        <button class="btn btn-sm btn-outline-primary" onclick="editNote(${JSON.stringify(note)})">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger" onclick="deleteNote(${note.id}, this)">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
                <div class="note-content">${escapeHtml(contentPreview)}</div>
                ${tagsHtml}
                <div class="mt-2">
                    <small class="text-muted">
                        ${dateStr}
                        ${note.is_shared == 1 ? '<span class="badge bg-info ms-2">Shared</span>' : ''}
                    </small>
                </div>
            `;
            
            colDiv.appendChild(noteCard);
            return colDiv;
        }
        
        function escapeHtml(text) {
            if (text == null) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    </script>
</body>
</html>

<?php
$researchStmt->close();
mysqli_close($conn);
?>

