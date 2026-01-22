<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: auth-login.php");
    exit();
}

include 'header.php';
include 'include/conn.php';
?>

<body>
    <div class="wrapper">
        <?php include 'sidebar.php'; ?>

        <div class="content-page">
            <div class="content">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-12">
                            <div class="page-title-box d-flex justify-content-between align-items-center">
                                <h4 class="page-title">Resources Management</h4>
                                <div class="d-flex gap-2">
                                    <a href="resources_dashboard.php" class="btn btn-secondary">
                                        <i class="ri-dashboard-line"></i> Dashboard
                                    </a>
                                    <a href="resources_reports.php" class="btn btn-info">
                                        <i class="ri-bar-chart-line"></i> Reports
                                    </a>
                                    <a href="add_resource.php" class="btn btn-primary">
                                        <i class="ri-add-circle-line"></i> Add Resource
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">All Resources</h4>
                                    <p class="text-muted mb-0">Manage downloadable resources for members</p>
                                </div>
                                <div class="card-body">
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

                                    <!-- Quick Stats -->
                                    <div class="row g-2 mb-3">
                                        <?php
                                        // Quick stats
                                        $totalQuery = "SELECT COUNT(*) as total FROM resources";
                                        $activeQuery = "SELECT COUNT(*) as total FROM resources WHERE status = 'active'";
                                        $featuredQuery = "SELECT COUNT(*) as total FROM resources WHERE featured = 1";
                                        $downloadsQuery = "SELECT SUM(download_count) as total FROM resources";
                                        
                                        $total = mysqli_fetch_assoc(mysqli_query($conn, $totalQuery))['total'] ?? 0;
                                        $active = mysqli_fetch_assoc(mysqli_query($conn, $activeQuery))['total'] ?? 0;
                                        $featured = mysqli_fetch_assoc(mysqli_query($conn, $featuredQuery))['total'] ?? 0;
                                        $downloads = mysqli_fetch_assoc(mysqli_query($conn, $downloadsQuery))['total'] ?? 0;
                                        ?>
                                        <div class="col-md-3">
                                            <div class="card bg-primary bg-opacity-10 border-primary">
                                                <div class="card-body p-2">
                                                    <div class="d-flex align-items-center">
                                                        <div class="flex-grow-1">
                                                            <h6 class="mb-0 text-muted small">Total Resources</h6>
                                                            <h4 class="mb-0"><?php echo number_format($total); ?></h4>
                                                        </div>
                                                        <i class="ri-file-paper-line fs-1 text-primary opacity-50"></i>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="card bg-success bg-opacity-10 border-success">
                                                <div class="card-body p-2">
                                                    <div class="d-flex align-items-center">
                                                        <div class="flex-grow-1">
                                                            <h6 class="mb-0 text-muted small">Active</h6>
                                                            <h4 class="mb-0"><?php echo number_format($active); ?></h4>
                                                        </div>
                                                        <i class="ri-checkbox-circle-line fs-1 text-success opacity-50"></i>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="card bg-warning bg-opacity-10 border-warning">
                                                <div class="card-body p-2">
                                                    <div class="d-flex align-items-center">
                                                        <div class="flex-grow-1">
                                                            <h6 class="mb-0 text-muted small">Featured</h6>
                                                            <h4 class="mb-0"><?php echo number_format($featured); ?></h4>
                                                        </div>
                                                        <i class="ri-star-line fs-1 text-warning opacity-50"></i>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="card bg-info bg-opacity-10 border-info">
                                                <div class="card-body p-2">
                                                    <div class="d-flex align-items-center">
                                                        <div class="flex-grow-1">
                                                            <h6 class="mb-0 text-muted small">Total Downloads</h6>
                                                            <h4 class="mb-0"><?php echo number_format($downloads); ?></h4>
                                                        </div>
                                                        <i class="ri-download-line fs-1 text-info opacity-50"></i>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Advanced Filters -->
                                    <div class="row mb-3">
                                        <div class="col-md-12">
                                            <div class="card border">
                                                <div class="card-body">
                                                    <h6 class="card-title mb-3 d-flex justify-content-between align-items-center">
                                                        <span><i class="ri-filter-line"></i> Advanced Filters & Search</span>
                                                        <button class="btn btn-sm btn-link" type="button" data-bs-toggle="collapse" data-bs-target="#filterCollapse">
                                                            <i class="ri-arrow-down-s-line"></i> Toggle
                                                        </button>
                                                    </h6>
                                                    <div class="collapse show" id="filterCollapse">
                                                        <div class="row g-3">
                                                            <div class="col-md-3">
                                                                <label for="filter-search" class="form-label small">Search (Title/Author)</label>
                                                                <input type="text" id="filter-search" class="form-control form-control-sm" placeholder="Search resources...">
                                                            </div>
                                                            <div class="col-md-2">
                                                                <label for="filter-section" class="form-label small">Section</label>
                                                                <select id="filter-section" class="form-select form-select-sm">
                                                                    <option value="">All Sections</option>
                                                                    <?php
                                                                    $sectionQuery = "SELECT DISTINCT section FROM resources WHERE section IS NOT NULL AND section != '' ORDER BY section";
                                                                    $sectionResult = mysqli_query($conn, $sectionQuery);
                                                                    while ($sectionRow = mysqli_fetch_assoc($sectionResult)) {
                                                                        echo '<option value="' . htmlspecialchars($sectionRow['section']) . '">' . htmlspecialchars($sectionRow['section']) . '</option>';
                                                                    }
                                                                    ?>
                                                                </select>
                                                            </div>
                                                            <div class="col-md-2">
                                                                <label for="filter-status" class="form-label small">Status</label>
                                                                <select id="filter-status" class="form-select form-select-sm">
                                                                    <option value="">All Status</option>
                                                                    <option value="active">Active</option>
                                                                    <option value="inactive">Inactive</option>
                                                                    <option value="archived">Archived</option>
                                                                </select>
                                                            </div>
                                                            <div class="col-md-2">
                                                                <label for="filter-access" class="form-label small">Access Level</label>
                                                                <select id="filter-access" class="form-select form-select-sm">
                                                                    <option value="">All Levels</option>
                                                                    <option value="public">Public</option>
                                                                    <option value="member">Member</option>
                                                                    <option value="premium">Premium</option>
                                                                    <option value="restricted">Restricted</option>
                                                                </select>
                                                            </div>
                                                            <div class="col-md-1">
                                                                <label for="filter-featured" class="form-label small">Featured</label>
                                                                <select id="filter-featured" class="form-select form-select-sm">
                                                                    <option value="">All</option>
                                                                    <option value="1">Yes</option>
                                                                    <option value="0">No</option>
                                                                </select>
                                                            </div>
                                                            <div class="col-md-2">
                                                                <label for="filter-date-from" class="form-label small">Date From</label>
                                                                <input type="date" id="filter-date-from" class="form-control form-control-sm">
                                                            </div>
                                                            <div class="col-md-2">
                                                                <label for="filter-date-to" class="form-label small">Date To</label>
                                                                <input type="date" id="filter-date-to" class="form-control form-control-sm">
                                                            </div>
                                                            <div class="col-md-2 d-flex align-items-end gap-2">
                                                                <button type="button" class="btn btn-sm btn-secondary w-100" id="clear-filters">
                                                                    <i class="ri-refresh-line"></i> Reset
                                                                </button>
                                                                <button type="button" class="btn btn-sm btn-success w-100" id="export-csv">
                                                                    <i class="ri-download-line"></i> Export
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Bulk Actions Toolbar -->
                                    <div class="row mb-3" id="bulk-actions-toolbar" style="display: none;">
                                        <div class="col-md-12">
                                            <div class="d-flex align-items-center gap-2">
                                                <span id="selected-count" class="badge bg-primary">0 selected</span>
                                                <select id="bulk-action" class="form-select form-select-sm" style="width: auto;">
                                                    <option value="">Choose action...</option>
                                                    <option value="activate">Activate</option>
                                                    <option value="deactivate">Deactivate</option>
                                                    <option value="archive">Archive</option>
                                                    <option value="delete">Delete</option>
                                                </select>
                                                <button type="button" class="btn btn-sm btn-primary" id="apply-bulk-action">
                                                    <i class="ri-check-line"></i> Apply
                                                </button>
                                                <button type="button" class="btn btn-sm btn-secondary" id="clear-selection">
                                                    <i class="ri-close-line"></i> Clear
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <table id="resources-datatable" class="table table-striped table-hover dt-responsive nowrap w-100">
                                        <thead>
                                            <tr>
                                                <th width="30">
                                                    <input type="checkbox" id="select-all" title="Select All">
                                                </th>
                                                <th>Section</th>
                                                <th>Title</th>
                                                <th>Author</th>
                                                <th>Status</th>
                                                <th>Access</th>
                                                <th>Downloads</th>
                                                <th>Featured</th>
                                                <th>Publication Date</th>
                                                <th>Created</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            // Check if status column exists, if not use basic query
                                            $checkStatus = mysqli_query($conn, "SHOW COLUMNS FROM resources LIKE 'status'");
                                            $hasStatus = mysqli_num_rows($checkStatus) > 0;
                                            
                                            if ($hasStatus) {
                                                $query = "SELECT * FROM resources ORDER BY created_at DESC";
                                            } else {
                                                // Fallback if migration hasn't been run yet
                                                $query = "SELECT *, 'active' as status, 'member' as access_level, 0 as download_count, 0 as featured, NULL as tags FROM resources ORDER BY created_at DESC";
                                            }
                                            $result = mysqli_query($conn, $query);

                                            if (mysqli_num_rows($result) > 0) {
                                                while ($row = mysqli_fetch_assoc($result)) {
                                                    $status = $row['status'] ?? 'active';
                                                    $statusClass = $status === 'active' ? 'success' : ($status === 'inactive' ? 'warning' : 'secondary');
                                                    $statusLabel = ucfirst($status);
                                                    $accessLevel = $row['access_level'] ?? 'member';
                                                    $downloadCount = $row['download_count'] ?? 0;
                                                    $featured = $row['featured'] ?? 0;
                                                    
                                                    echo "<tr>";
                                                    echo "<td>";
                                                    echo "<input type='checkbox' class='resource-checkbox' name='resource_ids[]' value='" . $row['id'] . "'>";
                                                    echo "</td>";
                                                    echo "<td><span class='badge bg-light text-dark'>" . htmlspecialchars($row['section']) . "</span></td>";
                                                    echo "<td>";
                                                    echo "<strong>" . htmlspecialchars($row['title']) . "</strong>";
                                                    if (!empty($row['pdf_file'])) {
                                                        echo "<br><small class='text-muted'><i class='ri-file-pdf-line'></i> PDF Available</small>";
                                                    }
                                                    echo "</td>";
                                                    echo "<td>" . htmlspecialchars($row['author']) . "</td>";
                                                    echo "<td>";
                                                    echo "<span class='badge bg-" . $statusClass . "'>" . $statusLabel . "</span>";
                                                    echo "</td>";
                                                    echo "<td>";
                                                    $accessClass = $accessLevel == 'public' ? 'primary' : ($accessLevel == 'member' ? 'info' : ($accessLevel == 'premium' ? 'warning' : 'danger'));
                                                    echo "<span class='badge bg-" . $accessClass . "'>" . ucfirst($accessLevel) . "</span>";
                                                    echo "</td>";
                                                    echo "<td>";
                                                    echo "<span class='badge bg-secondary'><i class='ri-download-line'></i> " . number_format($downloadCount) . "</span>";
                                                    echo "</td>";
                                                    echo "<td>";
                                                    if ($featured == 1) {
                                                        echo "<span class='badge bg-warning'><i class='ri-star-fill'></i> Featured</span>";
                                                    } else {
                                                        echo "<span class='text-muted'>-</span>";
                                                    }
                                                    echo "</td>";
                                                    echo "<td>" . date('M d, Y', strtotime($row['publication_date'])) . "</td>";
                                                    echo "<td>" . date('M d, Y', strtotime($row['created_at'])) . "</td>";
                                                    echo "<td>";
                                                    echo "<div class='btn-group' role='group'>";
                                                    echo "<a href='resource_details.php?id=" . $row['id'] . "' class='btn btn-sm btn-info' title='View Details'>";
                                                    echo "<i class='ri-eye-line'></i>";
                                                    echo "</a>";
                                                    echo "<a href='edit_resource.php?id=" . $row['id'] . "' class='btn btn-sm btn-primary' title='Edit Resource'>";
                                                    echo "<i class='ri-edit-line'></i>";
                                                    echo "</a>";
                                                    if (!empty($row['pdf_file'])) {
                                                        echo "<a href='../" . htmlspecialchars($row['pdf_file']) . "' target='_blank' class='btn btn-sm btn-success' title='View PDF'>";
                                                        echo "<i class='ri-file-pdf-line'></i>";
                                                        echo "</a>";
                                                    }
                                                    echo "<button class='btn btn-sm btn-danger' onclick='deleteResource(" . $row['id'] . ", this)' title='Delete Resource'>";
                                                    echo "<i class='ri-delete-bin-line'></i>";
                                                    echo "</button>";
                                                    echo "</div>";
                                                    echo "</td>";
                                                    echo "</tr>";
                                                }
                                            } else {
                                                echo "<tr><td colspan='11' class='text-center py-4'>";
                                                echo "<div class='text-muted'>";
                                                echo "<i class='ri-inbox-line fs-1 d-block mb-2'></i>";
                                                echo "No resources found. <a href='add_resource.php' class='text-primary'>Add your first resource</a>";
                                                echo "</div>";
                                                echo "</td></tr>";
                                            }
                                            mysqli_close($conn);
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?php include 'footer.php'; ?>
        </div>
    </div>

    <script src="assets/js/vendor.min.js"></script>
    <script src="assets/vendor/datatables.net/js/jquery.dataTables.min.js"></script>
    <script src="assets/vendor/datatables.net-bs5/js/dataTables.bootstrap5.min.js"></script>
    <script src="assets/vendor/datatables.net-responsive/js/dataTables.responsive.min.js"></script>
    <script src="assets/vendor/datatables.net-responsive-bs5/js/responsive.bootstrap5.min.js"></script>
    <script src="assets/js/app.min.js"></script>

    <script>
        $(document).ready(function() {
            var table = $('#resources-datatable').DataTable({
                responsive: true,
                order: [[9, 'desc']], // Sort by created date descending
                columnDefs: [
                    { orderable: false, targets: [0, 10] }, // Disable sorting on checkbox and action columns
                    { searchable: true, targets: [1, 2, 3, 4, 5, 6, 7, 8, 9] } // Make these columns searchable
                ],
                pageLength: 25,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
                dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rtip',
                language: {
                    search: "Search:",
                    searchPlaceholder: "Search resources..."
                },
                buttons: [
                    {
                        extend: 'csv',
                        text: '<i class="ri-download-line"></i> Export CSV',
                        className: 'btn btn-sm btn-success',
                        exportOptions: {
                            columns: [1, 2, 3, 4, 5, 6, 7, 8, 9] // Exclude checkbox and actions
                        }
                    }
                ]
            });

            // Advanced Filters
            var customSearch = null;
            
            function applyFilters() {
                // Remove previous custom search
                if (customSearch !== null) {
                    $.fn.dataTable.ext.search.pop();
                }
                
                var search = $('#filter-search').val().toLowerCase();
                var section = $('#filter-section').val();
                var status = $('#filter-status').val();
                var access = $('#filter-access').val();
                var featured = $('#filter-featured').val();
                var dateFrom = $('#filter-date-from').val();
                var dateTo = $('#filter-date-to').val();

                // Build custom filter
                customSearch = function(settings, data, dataIndex) {
                    var rowTitle = (data[2] || '').toLowerCase();
                    var rowAuthor = (data[3] || '').toLowerCase();
                    var rowSection = (data[1] || '').toLowerCase();
                    var rowStatus = (data[4] || '').toLowerCase();
                    var rowAccess = (data[5] || '').toLowerCase();
                    var rowFeatured = (data[7] || '').toLowerCase();
                    var rowDate = data[8] || '';
                    
                    // Search filter (title/author)
                    if (search && rowTitle.indexOf(search) === -1 && rowAuthor.indexOf(search) === -1) {
                        return false;
                    }
                    
                    // Section filter
                    if (section && rowSection.indexOf(section.toLowerCase()) === -1) {
                        return false;
                    }
                    
                    // Status filter
                    if (status && rowStatus.indexOf(status.toLowerCase()) === -1) {
                        return false;
                    }
                    
                    // Access level filter
                    if (access && rowAccess.indexOf(access.toLowerCase()) === -1) {
                        return false;
                    }
                    
                    // Featured filter
                    if (featured !== '') {
                        var isFeatured = rowFeatured.indexOf('featured') !== -1;
                        if ((featured === '1' && !isFeatured) || (featured === '0' && isFeatured)) {
                            return false;
                        }
                    }
                    
                    // Date range filter
                    if (dateFrom || dateTo) {
                        var rowDateObj = new Date(rowDate);
                        if (dateFrom) {
                            var fromDate = new Date(dateFrom);
                            if (rowDateObj < fromDate) {
                                return false;
                            }
                        }
                        if (dateTo) {
                            var toDate = new Date(dateTo);
                            toDate.setHours(23, 59, 59, 999);
                            if (rowDateObj > toDate) {
                                return false;
                            }
                        }
                    }
                    
                    return true;
                };
                
                $.fn.dataTable.ext.search.push(customSearch);
                table.draw();
            }

            // Filter change events
            $('#filter-search').on('keyup', function() {
                applyFilters();
            });
            
            $('#filter-section, #filter-status, #filter-access, #filter-featured, #filter-date-from, #filter-date-to').on('change', function() {
                applyFilters();
            });

            // Clear filters
            $('#clear-filters').on('click', function() {
                $('#filter-search, #filter-section, #filter-status, #filter-access, #filter-featured, #filter-date-from, #filter-date-to').val('');
                if (customSearch !== null) {
                    $.fn.dataTable.ext.search.pop();
                    customSearch = null;
                }
                table.search('').draw();
            });
            
            // Export CSV
            $('#export-csv').on('click', function() {
                // Get current filtered data
                var filteredData = table.rows({search: 'applied'}).data();
                var csvContent = "data:text/csv;charset=utf-8,";
                
                // Headers
                csvContent += "Section,Title,Author,Status,Access Level,Downloads,Featured,Publication Date,Created Date\n";
                
                // Data rows
                filteredData.each(function(row) {
                    var rowData = [
                        row[1] || '', // Section
                        (row[2] || '').replace(/<[^>]*>/g, '').replace(/,/g, ';'), // Title (strip HTML)
                        row[3] || '', // Author
                        (row[4] || '').replace(/<[^>]*>/g, ''), // Status (strip HTML)
                        (row[5] || '').replace(/<[^>]*>/g, ''), // Access (strip HTML)
                        (row[6] || '').replace(/<[^>]*>/g, '').replace(/\D/g, ''), // Downloads (extract number)
                        row[7].indexOf('Featured') !== -1 ? 'Yes' : 'No', // Featured
                        row[8] || '', // Publication Date
                        row[9] || ''  // Created Date
                    ];
                    csvContent += rowData.join(',') + "\n";
                });
                
                // Create download link
                var encodedUri = encodeURI(csvContent);
                var link = document.createElement("a");
                link.setAttribute("href", encodedUri);
                link.setAttribute("download", "resources_export_" + new Date().toISOString().split('T')[0] + ".csv");
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            });

            // Select All checkbox
            $('#select-all').on('click', function() {
                var isChecked = $(this).prop('checked');
                $('.resource-checkbox').prop('checked', isChecked);
                updateBulkActionsToolbar();
            });

            // Individual checkbox change
            $(document).on('change', '.resource-checkbox', function() {
                updateBulkActionsToolbar();
                // Update select all checkbox state
                var totalCheckboxes = $('.resource-checkbox').length;
                var checkedCheckboxes = $('.resource-checkbox:checked').length;
                $('#select-all').prop('checked', totalCheckboxes === checkedCheckboxes && totalCheckboxes > 0);
            });

            // Update bulk actions toolbar visibility
            function updateBulkActionsToolbar() {
                var selectedCount = $('.resource-checkbox:checked').length;
                if (selectedCount > 0) {
                    $('#bulk-actions-toolbar').show();
                    $('#selected-count').text(selectedCount + ' selected');
                } else {
                    $('#bulk-actions-toolbar').hide();
                }
            }

            // Clear selection
            $('#clear-selection').on('click', function() {
                $('.resource-checkbox').prop('checked', false);
                $('#select-all').prop('checked', false);
                updateBulkActionsToolbar();
            });

            // Apply bulk action
            $('#apply-bulk-action').on('click', function() {
                var action = $('#bulk-action').val();
                var selectedIds = [];

                $('.resource-checkbox:checked').each(function() {
                    selectedIds.push($(this).val());
                });

                if (!action) {
                    alert('Please select an action');
                    return;
                }

                if (selectedIds.length === 0) {
                    alert('Please select at least one resource');
                    return;
                }

                var confirmMessage = 'Are you sure you want to ' + action + ' ' + selectedIds.length + ' resource(s)?';
                if (action === 'delete') {
                    confirmMessage = 'WARNING: This will permanently delete ' + selectedIds.length + ' resource(s). This action cannot be undone. Are you sure?';
                }

                if (!confirm(confirmMessage)) {
                    return;
                }

                // Send AJAX request
                $.ajax({
                    url: 'include/bulk_resource_operations.php',
                    method: 'POST',
                    data: {
                        action: action,
                        resource_ids: selectedIds
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            // Show success message
                            showAdminAlert(response.message, 'success');
                            
                            // Remove deleted rows from table
                            if (action === 'delete') {
                                selectedIds.forEach(function(id) {
                                    table.row($('.resource-checkbox[value="' + id + '"]').closest('tr')).remove().draw(false);
                                });
                            } else {
                                // For status changes, reload the table to show updated status
                                table.ajax.reload(null, false);
                            }
                            
                            // Clear selections
                            $('.resource-checkbox:checked').prop('checked', false);
                            $('#select-all').prop('checked', false);
                            updateBulkActionsToolbar();
                        } else {
                            showAdminAlert('Error: ' + response.message, 'danger');
                        }
                    },
                    error: function() {
                        showAdminAlert('An error occurred while processing the request', 'danger');
                    }
                });
            });
            
            // AJAX Delete Resource
            window.deleteResource = function(resourceId, element) {
                if (!confirm('Are you sure you want to delete this resource? This action cannot be undone.')) {
                    return;
                }
                
                // Show loading state
                const originalHTML = element.innerHTML;
                element.disabled = true;
                element.innerHTML = '<i class="ri-loader-4-line ri-spin"></i>';
                
                $.ajax({
                    url: 'include/ajax_delete_resource.php',
                    method: 'GET',
                    data: { id: resourceId },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            showAdminAlert(response.message, 'success');
                            // Remove row from table
                            table.row($(element).closest('tr')).remove().draw(false);
                        } else {
                            element.disabled = false;
                            element.innerHTML = originalHTML;
                            showAdminAlert('Error: ' + response.message, 'danger');
                        }
                    },
                    error: function() {
                        element.disabled = false;
                        element.innerHTML = originalHTML;
                        showAdminAlert('An error occurred while deleting the resource', 'danger');
                    }
                });
            };
            
            // Show alert function for admin panel
            function showAdminAlert(message, type) {
                // Remove existing alerts
                $('.alert-auto-dismiss').remove();
                
                // Create alert
                const alertHtml = '<div class="alert alert-' + type + ' alert-dismissible fade show alert-auto-dismiss" role="alert">' +
                    message +
                    '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' +
                    '</div>';
                
                // Insert at top of card body
                $('.card-body').prepend(alertHtml);
                
                // Auto-dismiss after 3 seconds
                setTimeout(function() {
                    $('.alert-auto-dismiss').fadeOut(function() {
                        $(this).remove();
                    });
                }, 3000);
            }
        });
    </script>
</body>
</html>

