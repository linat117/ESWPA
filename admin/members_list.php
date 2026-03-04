<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: auth-login.php");
    exit();
}?>

<!DOCTYPE html>
<html lang="en">

<?php
 

include 'header.php';
include 'include/conn.php';
?>

<body>
    <!-- Begin page -->
    <div class="wrapper">

        <?php include 'sidebar.php'; ?>

        <!-- ============================================================== -->
        <!-- Start Page Content here -->
        <!-- ============================================================== -->

        <div class="content-page">
            <div class="content">


                <div class="row">
                    <div class="col-12">
                        <div class="page-title-box d-flex justify-content-between align-items-center mb-3">
                            <h4 class="page-title">Members Management</h4>
                            <div>
                                <a href="add_member.php" class="btn btn-primary me-2">
                                    <i class="ri-add-circle-line"></i> Add Member
                                </a>
                                <a href="members_dashboard.php" class="btn btn-secondary me-2">
                                    <i class="ri-dashboard-line"></i> Dashboard
                                </a>
                                <a href="member_reports.php" class="btn btn-info me-2">
                                    <i class="ri-bar-chart-line"></i> Reports
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if (!empty($_GET['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($_GET['success']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                <?php if (!empty($_GET['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($_GET['error']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Quick Stats -->
                <div class="row g-2 mb-3">
                    <?php
                    $totalQuery = "SELECT COUNT(*) as total FROM registrations";
                    $approvedQuery = "SELECT COUNT(*) as total FROM registrations WHERE approval_status = 'approved'";
                    $pendingQuery = "SELECT COUNT(*) as total FROM registrations WHERE approval_status = 'pending'";
                    $expiredQuery = "SELECT COUNT(*) as total FROM registrations WHERE expiry_date IS NOT NULL AND expiry_date < CURDATE() AND approval_status = 'approved'";
                    
                    $total = mysqli_fetch_assoc(mysqli_query($conn, $totalQuery))['total'] ?? 0;
                    $approved = mysqli_fetch_assoc(mysqli_query($conn, $approvedQuery))['total'] ?? 0;
                    $pending = mysqli_fetch_assoc(mysqli_query($conn, $pendingQuery))['total'] ?? 0;
                    $expired = mysqli_fetch_assoc(mysqli_query($conn, $expiredQuery))['total'] ?? 0;
                    ?>
                    <div class="col-md-3">
                        <div class="card bg-primary bg-opacity-10 border-primary">
                            <div class="card-body p-2">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-0 text-muted small">Total Members</h6>
                                        <h4 class="mb-0"><?php echo number_format($total); ?></h4>
                                    </div>
                                    <i class="ri-team-line fs-1 text-primary opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success bg-opacity-10 border-success">
                            <div class="card-body p-2">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-0 text-muted small">Approved</h6>
                                        <h4 class="mb-0"><?php echo number_format($approved); ?></h4>
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
                                        <h6 class="mb-0 text-muted small">Pending</h6>
                                        <h4 class="mb-0"><?php echo number_format($pending); ?></h4>
                                    </div>
                                    <i class="ri-time-line fs-1 text-warning opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-danger bg-opacity-10 border-danger">
                            <div class="card-body p-2">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-0 text-muted small">Expired</h6>
                                        <h4 class="mb-0"><?php echo number_format($expired); ?></h4>
                                    </div>
                                    <i class="ri-close-circle-line fs-1 text-danger opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Advanced Filters -->
                <div class="row mb-3">
                    <div class="col-12">
                        <div class="card border">
                            <div class="card-body">
                                <h6 class="card-title mb-3 d-flex justify-content-between align-items-center">
                                    <span><i class="ri-filter-line"></i> Advanced Filters & Search</span>
                                    <button class="btn btn-sm btn-link" type="button" data-bs-toggle="collapse" data-bs-target="#filterCollapse">
                                        <i class="ri-arrow-down-s-line"></i> Toggle
                                    </button>
                                </h6>
                                <div class="collapse show" id="filterCollapse">
                                    <form method="GET" class="row g-3">
                                        <div class="col-md-3">
                                            <label class="form-label small">Search (Name/Email/ID)</label>
                                            <input type="text" name="search" class="form-control form-control-sm" 
                                                   value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>" 
                                                   placeholder="Search members...">
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label small">Approval Status</label>
                                            <select name="status" class="form-select form-select-sm">
                                                <option value="">All Status</option>
                                                <option value="pending" <?php echo ($_GET['status'] ?? '') == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                <option value="approved" <?php echo ($_GET['status'] ?? '') == 'approved' ? 'selected' : ''; ?>>Approved</option>
                                                <option value="rejected" <?php echo ($_GET['status'] ?? '') == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label small">Qualification</label>
                                            <select name="qualification" class="form-select form-select-sm">
                                                <option value="">All Qualifications</option>
                                                <?php
                                                $qualQuery = "SELECT DISTINCT qualification FROM registrations WHERE qualification IS NOT NULL AND qualification != '' ORDER BY qualification";
                                                $qualResult = mysqli_query($conn, $qualQuery);
                                                while ($qualRow = mysqli_fetch_assoc($qualResult)) {
                                                    $selected = ($_GET['qualification'] ?? '') == $qualRow['qualification'] ? 'selected' : '';
                                                    echo '<option value="' . htmlspecialchars($qualRow['qualification']) . '" ' . $selected . '>' . htmlspecialchars($qualRow['qualification']) . '</option>';
                                                }
                                                ?>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label small">Date From</label>
                                            <input type="date" name="date_from" class="form-control form-control-sm" 
                                                   value="<?php echo htmlspecialchars($_GET['date_from'] ?? ''); ?>">
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label small">Date To</label>
                                            <input type="date" name="date_to" class="form-control form-control-sm" 
                                                   value="<?php echo htmlspecialchars($_GET['date_to'] ?? ''); ?>">
                                        </div>
                                        <div class="col-md-1 d-flex align-items-end gap-2">
                                            <button type="submit" class="btn btn-sm btn-primary w-100">
                                                <i class="ri-filter-line"></i> Filter
                                            </button>
                                        </div>
                                    </form>
                                    <div class="row mt-2">
                                        <div class="col-md-12">
                                            <a href="members_list.php" class="btn btn-sm btn-secondary">
                                                <i class="ri-refresh-line"></i> Reset Filters
                                            </a>
                                            <a href="?export=csv" class="btn btn-sm btn-success">
                                                <i class="ri-download-line"></i> Export CSV
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <?php
                // Build query with filters
                $where = [];
                $params = [];
                $types = '';
                
                $statusFilter = $_GET['status'] ?? '';
                $search = $_GET['search'] ?? '';
                $qualification = $_GET['qualification'] ?? '';
                $date_from = $_GET['date_from'] ?? '';
                $date_to = $_GET['date_to'] ?? '';
                
                if ($statusFilter) {
                    if ($statusFilter == 'expired') {
                        $where[] = "expiry_date IS NOT NULL AND expiry_date < CURDATE() AND approval_status = 'approved'";
                    } else {
                        $where[] = "approval_status = ?";
                        $params[] = $statusFilter;
                        $types .= 's';
                    }
                }
                
                if ($search) {
                    $where[] = "(fullname LIKE ? OR email LIKE ? OR membership_id LIKE ?)";
                    $searchTerm = "%{$search}%";
                    $params[] = $searchTerm;
                    $params[] = $searchTerm;
                    $params[] = $searchTerm;
                    $types .= 'sss';
                }
                
                if ($qualification) {
                    $where[] = "qualification = ?";
                    $params[] = $qualification;
                    $types .= 's';
                }
                
                if ($date_from) {
                    $where[] = "created_at >= ?";
                    $params[] = $date_from . ' 00:00:00';
                    $types .= 's';
                }
                
                if ($date_to) {
                    $where[] = "created_at <= ?";
                    $params[] = $date_to . ' 23:59:59';
                    $types .= 's';
                }
                
                $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
                
                // Execute query with prepared statement if we have parameters
                if (!empty($params)) {
                    $sql = "SELECT * FROM registrations {$whereClause} ORDER BY created_at DESC";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param($types, ...$params);
                    $stmt->execute();
                    $result = $stmt->get_result();
                } else {
                    $sql = "SELECT * FROM registrations {$whereClause} ORDER BY created_at DESC";
                    $result = mysqli_query($conn, $sql);
                }
                ?>

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="header-title">All Members</h4>
                                <p class="text-muted mb-0">Manage registered members</p>
                            </div>
                            <div class="card-body">

                                <table id="scroll-horizontal-datatable" class="table table-striped w-100 nowrap">
                                    <thead>
                                        <tr>
                                            <th>Membership ID</th>
                                            <th>Personal Info</th>
                                            <th>Photo</th>
                                            <th>Address</th>
                                            <th>Qualification</th>
                                            <th>Payment Info</th>
                                            <th>Bank Slip</th>
                                            <th>Approval Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        // Check if result is valid and fetch data
                                        $has_data = false;
                                        if ($result) {
                                            if ($result instanceof mysqli_result) {
                                                $num_rows = mysqli_num_rows($result);
                                                $has_data = $num_rows > 0;
                                            } else {
                                                $num_rows = $result->num_rows ?? 0;
                                                $has_data = $num_rows > 0;
                                            }
                                            
                                            if ($has_data) {
                                                // Fetch all rows into array first
                                                $rows = [];
                                                if ($result instanceof mysqli_result) {
                                                    while ($row = mysqli_fetch_assoc($result)) {
                                                        $rows[] = $row;
                                                    }
                                                } else {
                                                    while ($row = $result->fetch_assoc()) {
                                                        $rows[] = $row;
                                                    }
                                                }
                                                
                                                // Now output rows
                                                foreach ($rows as $row) {
                                                echo "<tr>";

                                                // Membership ID
                                                echo "<td>";
                                                if (!empty($row['membership_id'])) {
                                                    echo "<strong>" . htmlspecialchars($row['membership_id'] ?? '') . "</strong>";
                                                } else {
                                                    echo "<span class='text-muted'>Pending</span>";
                                                }
                                                echo "</td>";

                                                // Personal Info
                                                echo "<td>";
                                                echo "<strong>Name:</strong> " . htmlspecialchars($row['fullname'] ?? '') . "<br>";
                                                echo "<strong>Sex:</strong> " . htmlspecialchars($row['sex'] ?? '') . "<br>";
                                                echo "<strong>Phone:</strong> " . htmlspecialchars($row['phone'] ?? '') . "<br>";
                                                echo "<strong>Email:</strong> " . htmlspecialchars($row['email'] ?? '');
                                                echo "</td>";

                                                echo "<td>";
if (!empty($row['photo'])) {
    echo "<a href='../uploads/members/" . htmlspecialchars($row['photo']) . "' download>";
    echo "<img src='../uploads/members/" . htmlspecialchars($row['photo']) . "' alt='Photo' width='50'>";
    echo "</a>";
} else {
    echo "No Photo";
}
echo "</td>";


                                                // Address
                                                echo "<td>" . htmlspecialchars($row['address'] ?? '') . "</td>";

                                                echo "<td style='white-space: normal; word-wrap: break-word; max-width: 300px;'>"
                                                    . nl2br(htmlspecialchars($row['qualification'] ?? '')) . "</td>";


                                                // Payment Info
                                                echo "<td>";
                                                echo "<strong>Duration:</strong> " . htmlspecialchars($row['payment_duration'] ?? '') . "<br>";
                                                echo "<strong>Option:</strong> " . htmlspecialchars($row['payment_option'] ?? '') . "<br>";
                                                echo "<strong>ID Card:</strong> " . (!empty($row['id_card']) ? 'Yes' : 'No');
                                                echo "</td>";

                                                // Bank Slip
                                                echo "<td>";
                                                if (!empty($row['bank_slip'])) {
                                                    echo "<img src='../uploads/bankslip/" . htmlspecialchars($row['bank_slip']) . "' alt='Bank Slip' width='50'>";
                                                } else {
                                                    echo "No Slip";
                                                }
                                                echo "</td>";

                                                // Approval Status
                                                echo "<td>";
                                                $approvalStatus = $row['approval_status'] ?? 'pending';
                                                $statusClass = '';
                                                $statusText = '';
                                                
                                                switch($approvalStatus) {
                                                    case 'approved':
                                                        $statusClass = 'badge bg-success';
                                                        $statusText = 'Approved';
                                                        break;
                                                    case 'rejected':
                                                        $statusClass = 'badge bg-danger';
                                                        $statusText = 'Rejected';
                                                        break;
                                                    default:
                                                        $statusClass = 'badge bg-warning';
                                                        $statusText = 'Pending';
                                                }
                                                echo "<span class='{$statusClass}'>{$statusText}</span>";
                                                
                                                if (!empty($row['approved_at'])) {
                                                    echo "<br><small class='text-muted'>" . date('M d, Y', strtotime($row['approved_at'])) . "</small>";
                                                }
                                                echo "</td>";

                                                // Action Button (responsive: wrap + min touch target)
                                                echo "<td class='text-nowrap'>";
                                                echo "<div class='d-flex flex-wrap gap-1 align-items-center action-buttons'>";
                                                
                                                echo "<a href='edit_member.php?id=" . htmlspecialchars($row['id']) . "' class='btn btn-info btn-sm' title='Edit Member'>";
                                                echo "<i class='ri-edit-line'></i> Edit";
                                                echo "</a>";
                                                echo "<a href='member_notes.php?member_id=" . htmlspecialchars($row['id']) . "' class='btn btn-secondary btn-sm' title='View Notes'>";
                                                echo "<i class='ri-file-list-line'></i> Notes";
                                                echo "</a>";
                                                
                                                echo "<button type='button' class='btn btn-primary btn-sm view-member' 
                                                        data-bs-toggle='modal' 
                                                        data-bs-target='#member-modal'
                                                        data-fullname='" . htmlspecialchars($row['fullname'] ?? '') . "'
                                                        data-sex='" . htmlspecialchars($row['sex'] ?? '') . "'
                                                        data-phone='" . htmlspecialchars($row['phone'] ?? '') . "'
                                                        data-email='" . htmlspecialchars($row['email'] ?? '') . "'
                                                        data-photo='" . htmlspecialchars($row['photo'] ?? '') . "'
                                                        data-address='" . htmlspecialchars($row['address'] ?? '') . "'
                                                        data-qualification=\"" . htmlspecialchars($row['qualification'] ?? '') . "\"
                                                        data-payment_duration='" . htmlspecialchars($row['payment_duration'] ?? '') . "'
                                                        data-payment_option='" . htmlspecialchars($row['payment_option'] ?? '') . "'
                                                        data-id_card='" . (!empty($row['id_card']) ? 'Yes' : 'No') . "'
                                                        data-created_at='" . htmlspecialchars($row['created_at'] ?? '') . "'
                                                        data-membership_id='" . htmlspecialchars($row['membership_id'] ?? '') . "'
                                                        data-approval_status='" . htmlspecialchars($approvalStatus ?? '') . "'
                                                        title='View Details'>";
                                                echo "<i class='ri-eye-line'></i> View";
                                                echo "</button>";
                                                
                                                // Approve/Reject buttons (only show for pending)
                                                if ($approvalStatus == 'pending') {
                                                    echo "<button type='button' class='btn btn-success btn-sm approve-member' data-id='" . htmlspecialchars($row['id']) . "' title='Approve Member'>";
                                                    echo "<i class='ri-check-line'></i> Approve";
                                                    echo "</button>";
                                                    echo "<button type='button' class='btn btn-warning btn-sm reject-member' data-id='" . htmlspecialchars($row['id']) . "' title='Reject Member'>";
                                                    echo "<i class='ri-close-line'></i> Reject";
                                                    echo "</button>";
                                                }
                                                
                                                // Renewal button (for approved/active members)
                                                if ($approvalStatus == 'approved' && (!empty($row['expiry_date']) || $row['status'] == 'active')) {
                                                    echo "<a href='renew_membership.php?id=" . htmlspecialchars($row['id']) . "' class='btn btn-success btn-sm' title='Renew Membership'>";
                                                    echo "<i class='ri-refresh-line'></i> Renew";
                                                    echo "</a>";
                                                }
                                                
                                                echo "<button type='button' class='btn btn-danger btn-sm delete-member' data-id='" . htmlspecialchars($row['id']) . "' title='Delete Member'>";
                                                echo "<i class='ri-delete-bin-line'></i> Delete";
                                                echo "</button>";
                                                
                                                echo "</div>";
                                                echo "</td>";

                                                echo "</tr>";
                                                } // end foreach
                                            } // end if has_data
                                        } // end if result
                                        
                                        if (!$has_data) {
                                            echo "<tr><td colspan='9' class='text-center'>No members found matching your criteria.</td></tr>";
                                        }

                                        // Close statement if used
                                        if (!empty($params) && isset($stmt)) {
                                            $stmt->close();
                                        }
                                        ?>
                                    </tbody>
                                </table>


                            </div> <!-- end card body-->
                        </div> <!-- end card -->
                    </div><!-- end col-->
                </div> <!-- end row-->

            </div> <!-- container -->
        </div>

        <!-- Footer Start -->
        <?php include 'footer.php'; ?>
        <!-- end Footer -->

    </div>

    <!-- Member Details Modal -->
    <div class="modal fade" id="member-modal" tabindex="-1" aria-labelledby="memberModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header d-print-none">
                    <h5 class="modal-title" id="memberModalLabel">Member CV</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="member-cv">
                    <!-- CV content will be injected here -->
                </div>
                <div class="modal-footer d-print-none">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="printCv()">Print</button>
                </div>
            </div>
        </div>
    </div>


    <!-- ============================================================== -->
    <!-- End Page content -->
    <!-- ============================================================== -->

    </div>
    <!-- END wrapper -->

    <script src="assets/js/vendor.min.js"></script>

    <!-- Datatables js -->
    <script src="assets/vendor/datatables.net/js/jquery.dataTables.min.js"></script>
    <script src="assets/vendor/datatables.net-bs5/js/dataTables.bootstrap5.min.js"></script>
    <script src="assets/vendor/datatables.net-responsive/js/dataTables.responsive.min.js"></script>
    <script src="assets/vendor/datatables.net-responsive-bs5/js/responsive.bootstrap5.min.js"></script>
    <script src="assets/vendor/datatables.net-fixedcolumns-bs5/js/fixedColumns.bootstrap5.min.js"></script>
    <script src="assets/vendor/datatables.net-fixedheader/js/dataTables.fixedHeader.min.js"></script>
    <script src="assets/vendor/datatables.net-buttons/js/dataTables.buttons.min.js"></script>
    <script src="assets/vendor/datatables.net-buttons-bs5/js/buttons.bootstrap5.min.js"></script>
    <script src="assets/vendor/datatables.net-buttons/js/buttons.html5.min.js"></script>
    <script src="assets/vendor/datatables.net-buttons/js/buttons.flash.min.js"></script>
    <script src="assets/vendor/datatables.net-buttons/js/buttons.print.min.js"></script>
    <script src="assets/vendor/datatables.net-keytable/js/dataTables.keyTable.min.js"></script>
    <script src="assets/vendor/datatables.net-select/js/dataTables.select.min.js"></script>

    <!-- Datatable Demo App js -->
    <script src="assets/js/pages/datatable.init.js"></script>
    
    <!-- jQuery toast plugin -->
    <script src="assets/vendor/jquery-toast-plugin/jquery.toast.min.js"></script>

    <!-- App js -->
    <script src="assets/js/app.min.js"></script>

    <script>
        function formatPaymentDuration(duration) {
            if (!duration) return 'N/A';
            const parts = duration.split('_');
            if (parts.length !== 2) return duration;
            const value = parts[0];
            const unit = parts[1].charAt(0).toUpperCase() + parts[1].slice(1);
            return `${value} ${unit}${value > 1 ? 's' : ''}`;
        }

        function formatPaymentOption(option) {
            if (!option) return 'N/A';
            return option.charAt(0).toUpperCase() + option.slice(1);
        }

        $(document).ready(function() {
            $('#scroll-horizontal-datatable').on('click', '.view-member', function() {
                const button = $(this);
                const createdAt = new Date(button.data('created_at'));
                const paymentDuration = button.data('payment_duration'); // e.g., "1_year"
                
                let expiryDate = new Date(createdAt);
                if (paymentDuration) {
                    const parts = paymentDuration.split('_');
                    if (parts.length === 2) {
                        const value = parseInt(parts[0]);
                        const unit = parts[1];

                        if (unit.includes('year')) {
                            expiryDate.setFullYear(expiryDate.getFullYear() + value);
                        } else if (unit.includes('month')) {
                            expiryDate.setMonth(expiryDate.getMonth() + value);
                        }
                    }
                }

                const now = new Date();
                let statusHtml;
                if (now > expiryDate) {
                    statusHtml = `<span class="badge bg-danger">Expired</span>`;
                } else {
                    statusHtml = `<span class="badge bg-success">Active</span>`;
                }
                
                const formattedPaymentDuration = formatPaymentDuration(button.data('payment_duration'));
                const formattedPaymentOption = formatPaymentOption(button.data('payment_option'));


                const cvHtml = `
                    <div class="container-fluid">
                        <div class="row">
                            <div class="col-md-4 text-center">
                                <img src="../uploads/members/${button.data('photo')}" alt="Photo" class="img-fluid rounded-circle mb-3" style="width: 150px; height: 150px; object-fit: cover;" onerror="this.onerror=null;this.src='assets/images/users/avatar-1.jpg';">
                                <h4>${button.data('fullname')}</h4>
                            </div>
                            <div class="col-md-8">
                                <h3>${button.data('fullname')}</h3>
                                <hr>
                                <h5>Personal Information</h5>
                                <p><strong>Email:</strong> ${button.data('email')}</p>
                                <p><strong>Phone:</strong> ${button.data('phone')}</p>
                                <p><strong>Sex:</strong> ${button.data('sex')}</p>
                                <p><strong>Address:</strong> ${button.data('address')}</p>
                                <hr>
                                <h5>Professional Qualification</h5>
                                <p>${button.data('qualification').replace(/\n/g, '<br>')}</p>
                                <hr>
                                <h5>Membership Details</h5>
                                <p><strong>Subscription Status:</strong> ${statusHtml}</p>
                                <p><strong>Payment Duration:</strong> ${formattedPaymentDuration}</p>
                                <p><strong>Payment Option:</strong> ${formattedPaymentOption}</p>
                                <p><strong>ID Card Requested:</strong> ${button.data('id_card')}</p>
                            </div>
                        </div>
                    </div>
                `;
                $('#member-cv').html(cvHtml);
            });

            // Use document delegation so delete works after DataTable redraws and on touch devices
            $(document).on('click', '.delete-member', function(e) {
                e.preventDefault();
                e.stopPropagation();
                var button = $(this);
                var memberId = button.attr('data-id') || button.data('id');
                var row = button.closest('tr');
                if (!memberId) return;

                if (confirm('Are you sure you want to delete this member? This action cannot be undone.')) {
                    $.ajax({
                        url: 'include/delete_member.php',
                        type: 'POST',
                        data: { id: memberId },
                        dataType: 'json',
                        success: function(response) {
                            if (response && response.success) {
                                try {
                                    var dt = $('#scroll-horizontal-datatable').DataTable();
                                    if (dt && dt.row && row.length) {
                                        dt.row(row).remove().draw(false);
                                    } else {
                                        row.remove();
                                    }
                                } catch (err) {
                                    row.remove();
                                }
                                $.toast({
                                    heading: 'Success',
                                    text: 'Member deleted successfully.',
                                    icon: 'success',
                                    loader: true,
                                    loaderBg: '#f96a6a',
                                    position: 'top-right',
                                    hideAfter: 3000
                                });
                            } else {
                                $.toast({
                                    heading: 'Error',
                                    text: (response && response.message) ? response.message : 'Could not delete member.',
                                    icon: 'error',
                                    loader: true,
                                    loaderBg: '#f96a6a',
                                    position: 'top-right',
                                    hideAfter: 5000
                                });
                            }
                        },
                        error: function(xhr) {
                            var msg = 'An error occurred. Please try again.';
                            if (xhr.responseText) {
                                try {
                                    var r = JSON.parse(xhr.responseText);
                                    if (r && r.message) { msg = r.message; }
                                } catch (e) {
                                    if (xhr.responseText.length < 300) msg = xhr.responseText;
                                    else if (xhr.status) msg = 'Server error (HTTP ' + xhr.status + '). Check if you are logged in.';
                                }
                            } else if (xhr.status) {
                                msg = 'Request failed (HTTP ' + xhr.status + ').';
                            }
                            $.toast({
                                heading: 'Error',
                                text: msg,
                                icon: 'error',
                                loader: true,
                                loaderBg: '#f96a6a',
                                position: 'top-right',
                                hideAfter: 5000
                            });
                        }
                    });
                }
            });
            
            // Approve member handler
            $('#scroll-horizontal-datatable').on('click', '.approve-member', function() {
                const memberId = $(this).data('id');
                if (confirm('Are you sure you want to approve this member? They will be able to access the member panel.')) {
                    $.ajax({
                        url: 'include/approve_member.php',
                        method: 'POST',
                        data: {
                            member_id: memberId,
                            action: 'approve'
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                $.toast({
                                    heading: 'Success',
                                    text: response.message || 'Member approved successfully.',
                                    icon: 'success',
                                    loader: true,
                                    loaderBg: '#47ad77',
                                    position: 'top-right',
                                    hideAfter: 3000
                                });
                                // Reload page after 1 second
                                setTimeout(function() {
                                    location.reload();
                                }, 1000);
                            } else {
                                $.toast({
                                    heading: 'Error',
                                    text: response.message || 'Could not approve member.',
                                    icon: 'error',
                                    loader: true,
                                    loaderBg: '#f96a6a',
                                    position: 'top-right',
                                    hideAfter: 5000
                                });
                            }
                        },
                        error: function() {
                            $.toast({
                                heading: 'Error',
                                text: 'An error occurred. Please try again.',
                                icon: 'error',
                                loader: true,
                                loaderBg: '#f96a6a',
                                position: 'top-right',
                                hideAfter: 5000
                            });
                        }
                    });
                }
            });
            
            // Reject member handler
            $('#scroll-horizontal-datatable').on('click', '.reject-member', function() {
                const memberId = $(this).data('id');
                const reason = prompt('Please provide a reason for rejection (optional):');
                if (confirm('Are you sure you want to reject this member?')) {
                    $.ajax({
                        url: 'include/approve_member.php',
                        method: 'POST',
                        data: {
                            member_id: memberId,
                            action: 'reject',
                            reason: reason || ''
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                $.toast({
                                    heading: 'Success',
                                    text: response.message || 'Member rejected.',
                                    icon: 'success',
                                    loader: true,
                                    loaderBg: '#ffbc00',
                                    position: 'top-right',
                                    hideAfter: 3000
                                });
                                // Reload page after 1 second
                                setTimeout(function() {
                                    location.reload();
                                }, 1000);
                            } else {
                                $.toast({
                                    heading: 'Error',
                                    text: response.message || 'Could not reject member.',
                                    icon: 'error',
                                    loader: true,
                                    loaderBg: '#f96a6a',
                                    position: 'top-right',
                                    hideAfter: 5000
                                });
                            }
                        },
                        error: function() {
                            $.toast({
                                heading: 'Error',
                                text: 'An error occurred. Please try again.',
                                icon: 'error',
                                loader: true,
                                loaderBg: '#f96a6a',
                                position: 'top-right',
                                hideAfter: 5000
                            });
                        }
                    });
                }
            });
        });

        function printCv() {
            window.print();
        }
    </script>
    <style>
        /* Action column: responsive and touch-friendly */
        .action-buttons .btn {
            min-height: 38px;
            min-width: 36px;
        }
        @media (max-width: 768px) {
            .action-buttons .btn {
                min-height: 44px;
                padding: 0.4rem 0.5rem;
            }
        }
        .action-buttons .delete-member {
            cursor: pointer;
        }
        @media print {
            body * {
                visibility: hidden;
            }
            #member-cv, #member-cv * {
                visibility: visible;
            }
            #member-cv {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                margin: 20px;
            }
            .modal-dialog {
                max-width: 100% !important;
            }
        }
    </style>

</body>

</html>