<?php
session_start();

if (!isset($_SESSION['member_id'])) {
    header("Location: member-login.php");
    exit();
}

include 'include/config.php';
require_once __DIR__ . '/include/citation_generator.php';

$member_id = $_SESSION['member_id'];

// Handle save citation (only for non-AJAX fallback)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] === 'save' && !isset($_POST['ajax'])) {
    $resource_id = !empty($_POST['resource_id']) ? intval($_POST['resource_id']) : null;
    $research_id = !empty($_POST['research_id']) ? intval($_POST['research_id']) : null;
    $citation_format = $_POST['citation_format'] ?? 'apa';
    $citation_text = trim($_POST['citation_text'] ?? '');
    
    // Decode HTML entities and URL encoding if present
    $citation_text = html_entity_decode($citation_text, ENT_QUOTES, 'UTF-8');
    $citation_text = urldecode($citation_text);
    
    if (!empty($citation_text)) {
            $query = "INSERT INTO member_citations (member_id, resource_id, research_id, citation_format, citation_text) 
                      VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("iiiss", $member_id, $resource_id, $research_id, $citation_format, $citation_text);
        
        if ($stmt->execute()) {
            $success = "Citation saved successfully!";
        } else {
            $error = "Failed to save citation: " . $conn->error;
        }
        $stmt->close();
    }
}

// Handle delete citation (only for non-AJAX fallback)
if (isset($_GET['delete']) && !isset($_GET['ajax'])) {
    $citation_id = intval($_GET['delete']);
    $query = "DELETE FROM member_citations WHERE id = ? AND member_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $citation_id, $member_id);
    
    if ($stmt->execute()) {
        $success = "Citation deleted successfully!";
    }
    $stmt->close();
}

// Get saved citations
$citationsQuery = "SELECT mc.*, r.title as resource_title, rp.title as research_title
                   FROM member_citations mc
                   LEFT JOIN resources r ON mc.resource_id = r.id
                   LEFT JOIN research_projects rp ON mc.research_id = rp.id
                   WHERE mc.member_id = ?
                   ORDER BY mc.created_at DESC";
$citationsStmt = $conn->prepare($citationsQuery);
$citationsStmt->bind_param("i", $member_id);
$citationsStmt->execute();
$citationsResult = $citationsStmt->get_result();

// Get resources for citation generation (using prepared statement)
$resourcesQuery = "SELECT id, title, author, section, publication_date, pdf_file FROM resources ORDER BY title";
$resourcesStmt = $conn->prepare($resourcesQuery);
$resourcesStmt->execute();
$resourcesResult = $resourcesStmt->get_result();
$resourcesStmt->close();

// Get research projects (using prepared statement)
$researchQuery = "SELECT rp.*, r.fullname as creator_name 
                  FROM research_projects rp
                  LEFT JOIN registrations r ON rp.created_by = r.id
                  WHERE rp.status = 'published'
                  ORDER BY rp.title";
$researchStmt = $conn->prepare($researchQuery);
$researchStmt->execute();
$researchResult = $researchStmt->get_result();
$researchStmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Citation Generator - Member Dashboard</title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-9ndCyUaIbzAi2FUVXJi0CjmCapSmO7SnpJef0486qhLnuZ2cdeRhO02iuK6FUUVM" crossorigin="anonymous">
    <link href="assets/css/member-panel.css" rel="stylesheet">
    <style>
        /* Prevent style conflicts */
        .modal { z-index: 1055 !important; }
        .modal-backdrop { z-index: 1050 !important; }
        .dropdown-menu { z-index: 1000 !important; }
        .citation-preview {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            margin: 10px 0;
            font-family: 'Times New Roman', serif;
            min-height: 60px;
        }
        .format-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        /* Mobile Optimization - Compact Grid Layout */
        @media (max-width: 768px) {
            main {
                padding: 15px 10px !important;
                margin-top: 65px !important;
            }
            
            .container-fluid {
                padding: 0 5px !important;
            }
            
            h2 {
                font-size: 1.2rem !important;
                margin-bottom: 12px !important;
            }
            
            .row {
                margin: 0 -5px;
            }
            
            .col-lg-6 {
                padding: 0 5px;
                margin-bottom: 12px;
            }
            
            .card {
                margin-bottom: 12px !important;
                border-radius: 8px;
            }
            
            .card-header {
                padding: 10px 12px !important;
            }
            
            .card-header h5 {
                font-size: 0.95rem;
                margin: 0;
            }
            
            .card-body {
                padding: 12px !important;
            }
            
            .form-label {
                font-size: 0.9rem;
                margin-bottom: 5px;
            }
            
            .form-select, .form-control {
                font-size: 0.9rem;
                padding: 8px 12px;
            }
            
            .mb-3 {
                margin-bottom: 12px !important;
            }
            
            .citation-preview {
                font-size: 0.8rem !important;
                padding: 10px;
                min-height: 50px;
                word-wrap: break-word;
                overflow-wrap: break-word;
            }
            
            .d-flex.gap-2 {
                flex-direction: column;
                gap: 8px !important;
            }
            
            .d-flex.gap-2 .btn {
                width: 100%;
                font-size: 0.9rem;
                padding: 8px 15px;
            }
            
            /* Saved Citations - Grid/Compact View */
            .list-group {
                max-height: none;
            }
            
            .list-group-item {
                padding: 10px !important;
                margin-bottom: 8px;
                border-radius: 6px;
            }
            
            .list-group-item .d-flex {
                flex-direction: column;
                gap: 8px;
            }
            
            .list-group-item .flex-grow-1 {
                width: 100%;
            }
            
            .format-badge {
                font-size: 9px;
                padding: 3px 8px;
                margin-bottom: 6px;
            }
            
            .list-group-item p {
                font-size: 0.8rem !important;
                margin-bottom: 6px;
                line-height: 1.4;
            }
            
            .list-group-item small {
                font-size: 0.7rem !important;
            }
            
            .list-group-item .ms-3 {
                margin-left: 0 !important;
                margin-top: 8px;
                display: flex;
                gap: 6px;
                width: 100%;
            }
            
            .list-group-item .btn-sm {
                flex: 1;
                font-size: 0.75rem !important;
                padding: 5px 10px;
            }
        }
        
        @media (max-width: 480px) {
            main {
                padding: 10px 5px !important;
            }
            
            h2 {
                font-size: 1.05rem !important;
            }
            
            .card-header {
                padding: 8px 10px !important;
            }
            
            .card-body {
                padding: 10px !important;
            }
            
            .citation-preview {
                font-size: 0.75rem !important;
                padding: 8px;
            }
            
            .list-group-item {
                padding: 8px !important;
            }
            
            .list-group-item p {
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
                    <h2 class="mp-page-title"><i class="fas fa-quote-left"></i> Citation Generator</h2>
                    <a href="member-research.php" class="mp-btn mp-btn-outline-primary mp-btn-sm">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
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

            <div class="row g-2">
                <!-- Citation Generator -->
                <div class="col-12 col-lg-6">
                    <div class="card mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-magic"></i> Generate Citation</h5>
                        </div>
                        <div class="card-body">
                            <form id="citationForm">
                                <div class="mb-3">
                                    <label class="form-label">Source Type</label>
                                    <select class="form-select" id="sourceType" onchange="loadSources()">
                                        <option value="resource">Resource</option>
                                        <option value="research">Research Project</option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Select Source</label>
                                    <select class="form-select" id="sourceSelect" onchange="generateCitation()">
                                        <option value="">-- Select a source --</option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Citation Format</label>
                                    <select class="form-select" id="citationFormat" onchange="generateCitation()">
                                        <option value="apa" selected>APA (American Psychological Association)</option>
                                        <option value="mla">MLA (Modern Language Association)</option>
                                        <option value="chicago">Chicago</option>
                                        <option value="harvard">Harvard</option>
                                        <option value="ieee">IEEE</option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Generated Citation</label>
                                    <div class="citation-preview" id="citationPreview">
                                        <em class="text-muted">Select a source to generate citation...</em>
                                    </div>
                                </div>

                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-primary" onclick="copyCitation()">
                                        <i class="fas fa-copy"></i> Copy to Clipboard
                                    </button>
                                    <button type="button" class="btn btn-success" onclick="saveCitation()">
                                        <i class="fas fa-save"></i> Save Citation
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Saved Citations -->
                <div class="col-12 col-lg-6">
                    <div class="card">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0"><i class="fas fa-bookmark"></i> My Saved Citations</h5>
                        </div>
                        <div class="card-body">
                            <?php if ($citationsResult->num_rows > 0): ?>
                                <div class="list-group">
                                    <?php while ($citation = $citationsResult->fetch_assoc()): ?>
                                        <div class="list-group-item">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div class="flex-grow-1">
                                                    <span class="format-badge bg-primary"><?php echo strtoupper($citation['citation_format']); ?></span>
                                                    <?php 
                                                    // Properly decode citation text for display
                                                    $display_text = $citation['citation_text'];
                                                    $display_text = urldecode($display_text);
                                                    $display_text = html_entity_decode($display_text, ENT_QUOTES, 'UTF-8');
                                                    ?>
                                                    <p class="mb-1 mt-2"><?php echo htmlspecialchars($display_text, ENT_QUOTES, 'UTF-8'); ?></p>
                                                    <small class="text-muted">
                                                        <?php if ($citation['resource_title']): ?>
                                                            Resource: <?php echo htmlspecialchars($citation['resource_title']); ?>
                                                        <?php elseif ($citation['research_title']): ?>
                                                            Research: <?php echo htmlspecialchars($citation['research_title']); ?>
                                                        <?php endif; ?>
                                                        | <?php echo date('M d, Y', strtotime($citation['created_at'])); ?>
                                                    </small>
                                                </div>
                                                <div class="ms-3">
                                                    <?php 
                                                    // Properly decode citation text for JavaScript copy function
                                                    $copy_text = $citation['citation_text'];
                                                    $copy_text = urldecode($copy_text);
                                                    $copy_text = html_entity_decode($copy_text, ENT_QUOTES, 'UTF-8');
                                                    $copy_text_js = json_encode($copy_text, JSON_HEX_APOS | JSON_HEX_QUOT);
                                                    ?>
                                                    <button class="btn btn-sm btn-outline-primary" onclick="copyText(<?php echo $copy_text_js; ?>)">
                                                        <i class="fas fa-copy"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-danger" onclick="deleteCitation(<?php echo $citation['id']; ?>, this)">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endwhile; ?>
                                </div>
                            <?php else: ?>
                                <p class="text-muted">No saved citations yet. Generate and save your first citation!</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
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
        // Store sources data
        const resources = <?php echo json_encode(mysqli_fetch_all($resourcesResult, MYSQLI_ASSOC)); ?>;
        const research = <?php echo json_encode(mysqli_fetch_all($researchResult, MYSQLI_ASSOC)); ?>;

        function loadSources() {
            const sourceType = document.getElementById('sourceType').value;
            const sourceSelect = document.getElementById('sourceSelect');
            sourceSelect.innerHTML = '<option value="">-- Select a source --</option>';
            
            const sources = sourceType === 'resource' ? resources : research;
            
            sources.forEach((source, index) => {
                const option = document.createElement('option');
                option.value = index;
                option.textContent = source.title || source.name;
                sourceSelect.appendChild(option);
            });
        }

        function generateCitation() {
            const sourceType = document.getElementById('sourceType').value;
            const sourceIndex = document.getElementById('sourceSelect').value;
            const format = document.getElementById('citationFormat').value;
            
            if (sourceIndex === '') {
                document.getElementById('citationPreview').innerHTML = '<em class="text-muted">Select a source to generate citation...</em>';
                return;
            }
            
            const sources = sourceType === 'resource' ? resources : research;
            const source = sources[sourceIndex];
            
            // Call PHP function via AJAX
            fetch('include/generate_citation_ajax.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `source_type=${sourceType}&format=${format}&source_data=${encodeURIComponent(JSON.stringify(source))}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('citationPreview').textContent = data.citation;
                    window.currentCitation = {
                        text: data.citation,
                        format: format,
                        resource_id: sourceType === 'resource' ? source.id : null,
                        research_id: sourceType === 'research' ? source.id : null
                    };
                } else {
                    document.getElementById('citationPreview').innerHTML = '<em class="text-danger">Error generating citation</em>';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('citationPreview').innerHTML = '<em class="text-danger">Error generating citation</em>';
            });
        }

        function copyCitation() {
            const citationText = document.getElementById('citationPreview').textContent;
            if (citationText && !citationText.includes('Select a source')) {
                navigator.clipboard.writeText(citationText).then(() => {
                    alert('Citation copied to clipboard!');
                });
            }
        }

        function copyText(text) {
            // Ensure text is properly decoded
            if (typeof text === 'string') {
                try {
                    text = decodeURIComponent(text);
                } catch(e) {
                    // If decoding fails, use text as is
                }
            }
            navigator.clipboard.writeText(text).then(() => {
                alert('Copied to clipboard!');
            }).catch(err => {
                console.error('Failed to copy:', err);
                alert('Failed to copy to clipboard');
            });
        }

        function saveCitation() {
            if (!window.currentCitation) {
                showAlert('Please generate a citation first', 'warning');
                return;
            }
            
            // Show loading state
            const saveBtn = event.target;
            const originalText = saveBtn.innerHTML;
            saveBtn.disabled = true;
            saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
            
            // Prepare form data
            const formData = new FormData();
            formData.append('action', 'save');
            formData.append('citation_format', window.currentCitation.format);
            formData.append('citation_text', window.currentCitation.text);
            formData.append('resource_id', window.currentCitation.resource_id || '');
            formData.append('research_id', window.currentCitation.research_id || '');
            
            // Send AJAX request
            fetch('include/ajax_citation_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                saveBtn.disabled = false;
                saveBtn.innerHTML = originalText;
                
                if (data.success) {
                    showAlert(data.message, 'success');
                    // Add the new citation to the list without page reload
                    if (data.citation) {
                        addCitationToList(data.citation);
                    }
                    // Clear the form
                    document.getElementById('citationPreview').innerHTML = '<em class="text-muted">Select a source to generate citation...</em>';
                    window.currentCitation = null;
                } else {
                    showAlert(data.error || 'Failed to save citation', 'danger');
                }
            })
            .catch(error => {
                saveBtn.disabled = false;
                saveBtn.innerHTML = originalText;
                console.error('Error:', error);
                showAlert('An error occurred while saving the citation', 'danger');
            });
        }
        
        function deleteCitation(citationId, element) {
            if (!confirm('Are you sure you want to delete this citation?')) {
                return;
            }
            
            // Show loading state
            const originalHTML = element.innerHTML;
            element.disabled = true;
            element.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            
            // Send AJAX request
            fetch('include/ajax_citation_handler.php?action=delete&citation_id=' + citationId, {
                method: 'GET'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert(data.message, 'success');
                    // Remove the citation from the list without page reload
                    element.closest('.list-group-item').remove();
                    // If no citations left, show message
                    const citationsList = document.querySelector('.list-group');
                    if (citationsList && citationsList.children.length === 0) {
                        citationsList.parentElement.innerHTML = '<p class="text-muted">No saved citations yet. Generate and save your first citation!</p>';
                    }
                } else {
                    element.disabled = false;
                    element.innerHTML = originalHTML;
                    showAlert(data.error || 'Failed to delete citation', 'danger');
                }
            })
            .catch(error => {
                element.disabled = false;
                element.innerHTML = originalHTML;
                console.error('Error:', error);
                showAlert('An error occurred while deleting the citation', 'danger');
            });
        }
        
        function addCitationToList(citation) {
            const citationsList = document.querySelector('.list-group');
            if (!citationsList) return;
            
            // Remove "no citations" message if exists
            const noCitationsMsg = citationsList.parentElement.querySelector('p.text-muted');
            if (noCitationsMsg) {
                noCitationsMsg.remove();
            }
            
            // Create new list item
            const listItem = document.createElement('div');
            listItem.className = 'list-group-item';
            
            // Decode citation text
            let citationText = citation.citation_text;
            try {
                citationText = decodeURIComponent(citationText);
            } catch(e) {}
            
            // Escape HTML for display
            const displayText = escapeHtml(citationText);
            
            // Store original text for copying (properly escaped for JavaScript)
            const copyTextJs = JSON.stringify(citationText);
            
            listItem.innerHTML = `
                <div class="d-flex justify-content-between align-items-start">
                    <div class="flex-grow-1">
                        <span class="format-badge bg-primary">${citation.citation_format.toUpperCase()}</span>
                        <p class="mb-1 mt-2">${displayText}</p>
                        <small class="text-muted">
                            ${citation.resource_title ? 'Resource: ' + escapeHtml(citation.resource_title) : ''}
                            ${citation.research_title ? 'Research: ' + escapeHtml(citation.research_title) : ''}
                            | ${new Date(citation.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}
                        </small>
                    </div>
                    <div class="ms-3">
                        <button class="btn btn-sm btn-outline-primary" onclick="copyText(${copyTextJs})">
                            <i class="fas fa-copy"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger" onclick="deleteCitation(${citation.id}, this)">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            `;
            
            // Insert at the beginning of the list
            citationsList.insertBefore(listItem, citationsList.firstChild);
        }
        
        function showAlert(message, type) {
            // Remove existing alerts
            const existingAlerts = document.querySelectorAll('.alert-auto-dismiss');
            existingAlerts.forEach(alert => alert.remove());
            
            // Create new alert
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type} alert-dismissible fade show alert-auto-dismiss`;
            alertDiv.innerHTML = `
                ${escapeHtml(message)}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            // Insert at the top of main content
            const main = document.querySelector('main .container-fluid');
            if (main) {
                main.insertBefore(alertDiv, main.firstChild);
                
                // Auto-dismiss after 3 seconds
                setTimeout(() => {
                    alertDiv.remove();
                }, 3000);
            }
        }
        
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Load sources on page load
        loadSources();
    </script>
</body>
</html>

<?php
$citationsStmt->close();
mysqli_close($conn);
?>

