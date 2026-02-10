<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: auth-login.php");
    exit();
}

include 'include/conn.php';
include 'header.php';

$success_message = '';
$error_message = '';

// Handle CSV export
if (isset($_GET['export']) && $_GET['export'] == 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=members_export_' . date('Y-m-d') . '.csv');
    
    $output = fopen('php://output', 'w');
    
    // CSV headers
    fputcsv($output, ['ID', 'Membership ID', 'Full Name', 'Email', 'Phone', 'Sex', 'Qualification', 'Address', 'Approval Status', 'Expiry Date', 'Registered Date']);
    
    // Fetch all members
    $query = "SELECT id, membership_id, fullname, email, phone, sex, qualification, address, approval_status, expiry_date, created_at 
              FROM registrations 
              ORDER BY created_at DESC";
    $result = $conn->query($query);
    
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, [
            $row['id'],
            $row['membership_id'],
            $row['fullname'],
            $row['email'],
            $row['phone'],
            $row['sex'],
            $row['qualification'],
            $row['address'],
            $row['approval_status'],
            $row['expiry_date'] ?? '',
            $row['created_at']
        ]);
    }
    
    fclose($output);
    exit();
}

// Handle CSV import
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] == 0) {
    $file = $_FILES['csv_file'];
    $handle = fopen($file['tmp_name'], 'r');
    
    if ($handle !== false) {
        $imported = 0;
        $skipped = 0;
        $errors = [];
        
        // Skip header row
        fgetcsv($handle);
        
        while (($data = fgetcsv($handle)) !== false) {
            if (count($data) < 4) continue; // Skip invalid rows
            
            $fullname = trim($data[0] ?? '');
            $email = trim($data[1] ?? '');
            $phone = trim($data[2] ?? '');
            $qualification = trim($data[3] ?? '');
            
            if (empty($fullname) || empty($email)) {
                $skipped++;
                continue;
            }
            
            // Check if email already exists
            $checkStmt = $conn->prepare("SELECT id FROM registrations WHERE email = ?");
            $checkStmt->bind_param("s", $email);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();
            $checkStmt->close();
            
            if ($checkResult->num_rows > 0) {
                $skipped++;
                continue;
            }
            
            // Generate membership ID
            $membership_id = 'ESWPA-' . date('Y') . '-' . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);
            
            // Insert member
            $insertStmt = $conn->prepare("INSERT INTO registrations (fullname, email, phone, qualification, membership_id, approval_status, created_at) VALUES (?, ?, ?, ?, ?, 'pending', NOW())");
            $insertStmt->bind_param("sssss", $fullname, $email, $phone, $qualification, $membership_id);
            
            if ($insertStmt->execute()) {
                $imported++;
            } else {
                $skipped++;
            }
            $insertStmt->close();
        }
        
        fclose($handle);
        
        $success_message = "Import completed: {$imported} members imported, {$skipped} skipped";
    } else {
        $error_message = "Failed to read CSV file";
    }
}

// Get import/export statistics
$stats = [];
$stats['total_members'] = $conn->query("SELECT COUNT(*) as total FROM registrations")->fetch_assoc()['total'];
$stats['approved'] = $conn->query("SELECT COUNT(*) as total FROM registrations WHERE approval_status = 'approved'")->fetch_assoc()['total'];
$stats['pending'] = $conn->query("SELECT COUNT(*) as total FROM registrations WHERE approval_status = 'pending'")->fetch_assoc()['total'];
?>

<!DOCTYPE html>
<html lang="en">

<body>
    <div class="wrapper">
        <?php include 'sidebar.php'; ?>

        <div class="content-page">
            <div class="content">
                <div class="container-fluid">
                    <!-- Page Title -->
                    <div class="row">
                        <div class="col-12">
                            <div class="page-title-box mb-3">
                                <h4 class="page-title mb-2">Member Import/Export</h4>
                                <div class="d-inline-flex flex-row flex-nowrap gap-2">
                                    <a href="members_dashboard.php" class="btn btn-secondary btn-sm">
                                        <i class="ri-dashboard-line"></i> Dashboard
                                    </a>
                                    <a href="members_list.php" class="btn btn-secondary btn-sm">
                                        <i class="ri-list-check"></i> All Members
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php
                    if ($success_message) {
                        echo '<div class="alert alert-success alert-dismissible fade show" role="alert">';
                        echo htmlspecialchars($success_message);
                        echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
                        echo '</div>';
                    }
                    if ($error_message) {
                        echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">';
                        echo htmlspecialchars($error_message);
                        echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
                        echo '</div>';
                    }
                    ?>

                    <!-- Statistics -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <div class="card bg-primary bg-opacity-10 border-primary">
                                <div class="card-body">
                                    <h6 class="text-muted small">Total Members</h6>
                                    <h3><?php echo number_format($stats['total_members']); ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-success bg-opacity-10 border-success">
                                <div class="card-body">
                                    <h6 class="text-muted small">Approved</h6>
                                    <h3><?php echo number_format($stats['approved']); ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-warning bg-opacity-10 border-warning">
                                <div class="card-body">
                                    <h6 class="text-muted small">Pending</h6>
                                    <h3><?php echo number_format($stats['pending']); ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Export Section -->
                        <div class="col-lg-6">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">
                                        <i class="ri-download-line"></i> Export Members
                                    </h4>
                                </div>
                                <div class="card-body">
                                    <p class="text-muted">Export all members to CSV file for backup or external processing.</p>
                                    
                                    <div class="alert alert-info">
                                        <h6>Export Format:</h6>
                                        <ul class="mb-0 small">
                                            <li>CSV format (comma-separated values)</li>
                                            <li>Includes: ID, Membership ID, Name, Email, Phone, Qualification, Address, Status, Dates</li>
                                            <li>Compatible with Excel, Google Sheets, and other spreadsheet applications</li>
                                        </ul>
                                    </div>
                                    
                                    <a href="?export=csv" class="btn btn-success w-100">
                                        <i class="ri-download-line"></i> Export All Members to CSV
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Import Section -->
                        <div class="col-lg-6">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">
                                        <i class="ri-upload-line"></i> Import Members
                                    </h4>
                                </div>
                                <div class="card-body">
                                    <p class="text-muted">Import members from a CSV file.</p>
                                    
                                    <form method="POST" enctype="multipart/form-data">
                                        <div class="mb-3">
                                            <label class="form-label">CSV File</label>
                                            <input type="file" 
                                                   name="csv_file" 
                                                   class="form-control" 
                                                   accept=".csv" 
                                                   required>
                                            <small class="text-muted">Select a CSV file to import</small>
                                        </div>
                                        
                                        <div class="alert alert-warning">
                                            <h6>CSV Format Requirements:</h6>
                                            <ul class="mb-0 small">
                                                <li>First row should be headers (will be skipped)</li>
                                                <li>Required columns: Full Name, Email, Phone, Qualification</li>
                                                <li>Email addresses must be unique</li>
                                                <li>Imported members will have "pending" approval status</li>
                                                <li>Membership IDs will be auto-generated</li>
                                            </ul>
                                        </div>
                                        
                                        <button type="submit" class="btn btn-primary w-100">
                                            <i class="ri-upload-line"></i> Import Members from CSV
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sample CSV Format -->
                    <div class="row mt-3">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title">Sample CSV Format</h4>
                                </div>
                                <div class="card-body">
                                    <p class="text-muted">Use this format for importing members:</p>
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-sm mb-0">
                                            <thead>
                                                <tr>
                                                    <th>Full Name</th>
                                                    <th>Email</th>
                                                    <th>Phone</th>
                                                    <th>Qualification</th>
                                                    <th>Sex (optional)</th>
                                                    <th>Address (optional)</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td>John Doe</td>
                                                    <td>john@example.com</td>
                                                    <td>+251-911-234-567</td>
                                                    <td>BSW</td>
                                                    <td>Male</td>
                                                    <td>Addis Ababa, Ethiopia</td>
                                                </tr>
                                                <tr>
                                                    <td>Jane Smith</td>
                                                    <td>jane@example.com</td>
                                                    <td>+251-922-345-678</td>
                                                    <td>MSW</td>
                                                    <td>Female</td>
                                                    <td>Addis Ababa, Ethiopia</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                    <p class="text-muted small mb-0">
                                        <i class="ri-information-line"></i> The first row (headers) will be automatically skipped during import.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <?php include 'footer.php'; ?>
        </div>
    </div>

    <!-- Vendor js -->
    <script src="assets/js/vendor.min.js"></script>
    <script src="assets/js/app.min.js"></script>

</body>
</html>

