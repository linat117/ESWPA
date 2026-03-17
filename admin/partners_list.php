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
                                <h4 class="page-title">Partners</h4>
                                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPartnerModal">
                                    <i class="ri-add-circle-line"></i> Add Partner
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">All Partners</h4>
                                    <p class="text-muted mb-0">Manage partner organizations and their logos</p>
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

                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>LOGO</th>
                                                    <th>NAME</th>
                                                    <th>STATUS</th>
                                                    <th>SORT ORDER</th>
                                                    <th>ACTION</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $query = "SELECT * FROM partners ORDER BY sort_order ASC, name ASC";
                                                $result = mysqli_query($conn, $query);

                                                if (mysqli_num_rows($result) > 0) {
                                                    while ($row = mysqli_fetch_assoc($result)) {
                                                        echo "<tr>";
                                                        
                                                        // Logo
                                                        echo "<td>";
                                                        if (!empty($row['logo_url'])) {
                                                            if (strpos($row['logo_url'], 'http') === 0) {
                                                                // External URL
                                                                echo "<img src='" . htmlspecialchars($row['logo_url']) . "' alt='" . htmlspecialchars($row['name']) . "' style='max-width: 60px; max-height: 40px; object-fit: contain;' onerror=\"this.src='assets/images/no-image.png'\">";
                                                            } else {
                                                                // Local file
                                                                echo "<img src='../" . htmlspecialchars($row['logo_url']) . "' alt='" . htmlspecialchars($row['name']) . "' style='max-width: 60px; max-height: 40px; object-fit: contain;' onerror=\"this.src='assets/images/no-image.png'\">";
                                                            }
                                                        } else {
                                                            echo "<div class='bg-light text-center p-2' style='width: 60px; height: 40px; font-size: 12px;'>No Logo</div>";
                                                        }
                                                        echo "</td>";
                                                       echo "<td>";
                                                        echo "<strong>" . htmlspecialchars($row['name']) . "</strong></td>";
                                                        
                                                        // Status badge
                                                        $statusClass = $row['status'] == 'active' ? 'bg-success' : 'bg-secondary';
                                                        echo "<td><span class='badge {$statusClass}'>" . ucfirst($row['status']) . "</span></td>";
                                                        
                                                        echo "<td>" . (int)$row['sort_order'] . "</td>";
                                                        
                                                        echo "<td>";
                                                        echo "<div class='btn-group' role='group'>";
                                                        echo "<button type='button' class='btn btn-sm btn-primary me-1' onclick='openEditModal(" . $row['id'] . ", \"" . htmlspecialchars($row['name']) . "\", \"" . htmlspecialchars($row['logo_url']) . "\", \"" . $row['status'] . "\", " . $row['sort_order'] . ")'>";
                                                        echo "<i class='ri-edit-line'></i> Edit";
                                                        echo "</button>";
                                                        echo "<a href='include/delete_partner.php?id=" . $row['id'] . "' class='btn btn-sm btn-danger' onclick='return confirm(\"Are you sure you want to delete this partner?\");'>";
                                                        echo "<i class='ri-delete-bin-line'></i> Delete";
                                                        echo "</a>";
                                                        echo "</div>";
                                                        echo "</td>";
                                                        
                                                        echo "</tr>";
                                                    }
                                                } else {
                                                    echo "<tr><td colspan='4' class='text-center'>No partners found. <button type='button' class='btn btn-primary' data-bs-toggle='modal' data-bs-target='#addPartnerModal'>Add your first partner</button></td></tr>";
                                                }
                                                ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- DataTables JavaScript - DISABLED to prevent conflicts -->
    <script src="assets/js/vendor.min.js"></script>
    <!-- DataTables scripts commented out due to conflicts
    <script src="assets/vendor/datatables.net/js/jquery.dataTables.min.js"></script>
    <script src="assets/vendor/datatables.net-bs5/js/dataTables.bootstrap5.min.js"></script>
    <script src="assets/vendor/datatables.net-responsive/js/dataTables.responsive.min.js"></script>
    <script src="assets/vendor/datatables.net-responsive-bs5/js/responsive.bootstrap5.min.js"></script>
    -->

    <script>
        // DataTables disabled - using simple table instead
        // Fallback: Ensure modal works with Bootstrap 5
        document.addEventListener('DOMContentLoaded', function() {
            var addPartnerBtn = document.querySelector('[data-bs-target="#addPartnerModal"]');
            if (addPartnerBtn) {
                addPartnerBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    var modal = new bootstrap.Modal(document.getElementById('addPartnerModal'));
                    modal.show();
                });
            }
        });
    </script>
</body>

<?php include 'footer.php'; ?>

<!-- Add Partner Modal -->
<div class="modal fade" id="addPartnerModal" tabindex="-1" aria-labelledby="addPartnerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addPartnerModalLabel">Add New Partner</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addPartnerForm" method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="name" class="form-label">Partner Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="logo_file" class="form-label">Logo Image</label>
                        <input type="file" class="form-control" id="logo_file" name="logo_file" accept="image/*">
                        <div class="form-text">Upload partner logo. Recommended size: 200x100px</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="sort_order" class="form-label fw-bold">Sort Order</label>
                        <input type="number" class="form-control" id="sort_order" name="sort_order" value="0" min="0">
                        <div class="form-text">Lower numbers appear first</div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Partner</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Edit Partner Modal -->
<div class="modal fade" id="editPartnerModal" tabindex="-1" aria-labelledby="editPartnerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editPartnerModalLabel">Edit Partner</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editPartnerForm" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="id" id="editPartnerId">
                    
                    <div class="mb-3">
                        <label for="editName" class="form-label">Partner Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="editName" name="name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="editLogoFile" class="form-label">Logo Image</label>
                        <input type="file" class="form-control" id="editLogoFile" name="logo_file" accept="image/*">
                        <div class="form-text">Upload new partner logo. Recommended size: 200x100px</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="editSortOrder" class="form-label fw-bold">Sort Order</label>
                        <input type="number" class="form-control" id="editSortOrder" name="sort_order" value="<?php echo (int)($partner['sort_order'] ?? 0); ?>" min="0">
                        <div class="form-text">Lower numbers appear first</div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" form="editPartnerForm" class="btn btn-primary">Update Partner</button>
            </div>
        </div>
    </div>
</div>

<script>
// Function to open edit modal with partner data
function openEditModal(id, name, logoUrl, sortOrder) {
    // Set form values
    document.getElementById('editPartnerId').value = id;
    document.getElementById('editName').value = name;
    document.getElementById('editSortOrder').value = sortOrder;
    
    // Show current logo if exists
    const currentLogoDiv = document.querySelector('#editPartnerModal .current-logo');
    if (currentLogoDiv && logoUrl) {
        currentLogoDiv.innerHTML = logoUrl.startsWith('http') 
            ? `<img src="${logoUrl}" alt="${name}" style="max-width: 100px; max-height: 60px; object-fit: contain;">`
            : `<img src="../${logoUrl}" alt="${name}" style="max-width: 100px; max-height: 60px; object-fit: contain;">`;
    }
    
    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('editPartnerModal'));
    modal.show();
}

document.addEventListener('DOMContentLoaded', function() {
    // Direct form attachment - most reliable method
    const form = document.getElementById('addPartnerForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const submitBtn = this.querySelector('button[type="submit"]');
            if (!submitBtn) {
                console.error('Submit button not found');
                return;
            }
            
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Adding...';
            submitBtn.disabled = true;
            
            const formData = new FormData(this);
            
            fetch('add_partner.php', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Close modal and refresh page
                    const modalElement = document.getElementById('addPartnerModal');
                    if (modalElement) {
                        const modal = bootstrap.Modal.getInstance(modalElement);
                        if (modal) {
                            modal.hide();
                        }
                    }
                    
                    setTimeout(() => {
                        window.location.href = 'partners_list.php?success=' + encodeURIComponent(data.message);
                    }, 500);
                } else {
                    // Show error message
                    const modalBody = document.querySelector('#addPartnerModal .modal-body');
                    if (modalBody) {
                        let alertHtml = '<div class="alert alert-danger alert-dismissible fade show" role="alert">';
                        alertHtml += data.error;
                        alertHtml += '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
                        alertHtml += '</div>';
                        
                        modalBody.insertAdjacentHTML('afterbegin', alertHtml);
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            })
            .finally(() => {
                if (submitBtn) {
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                }
            });
        });
    }
    
    // Fallback: Ensure modal works with Bootstrap 5
    var addPartnerBtn = document.querySelector('[data-bs-target="#addPartnerModal"]');
    if (addPartnerBtn) {
        addPartnerBtn.addEventListener('click', function(e) {
            e.preventDefault();
            var modal = new bootstrap.Modal(document.getElementById('addPartnerModal'));
            modal.show();
        });
    }
});
</script>
