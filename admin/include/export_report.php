<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    die('Unauthorized access');
}

include 'conn.php';

$report_type = $_GET['type'] ?? 'daily';
$export_format = $_GET['export'] ?? 'pdf';
$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$end_date = $_GET['end_date'] ?? date('Y-m-d');

// For now, we'll use a simple HTML to PDF approach
// In production, you would use TCPDF or similar library

if ($export_format == 'excel') {
    // Excel export
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="report_' . $report_type . '_' . date('Y-m-d') . '.xls"');
    
    // Generate Excel content (simple HTML table)
    echo '<table border="1">';
    echo '<tr><th colspan="5">' . ucfirst($report_type) . ' Report - ' . date('F d, Y') . '</th></tr>';
    echo '<tr><th>Date Range:</th><td colspan="4">' . $start_date . ' to ' . $end_date . '</td></tr>';
    // Add report-specific data here
    echo '</table>';
    
} else {
    // PDF export (using browser print for now)
    header('Content-Type: text/html; charset=utf-8');
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Report - <?php echo ucfirst($report_type); ?></title>
        <style>
            body { font-family: Arial, sans-serif; padding: 20px; }
            table { width: 100%; border-collapse: collapse; margin: 20px 0; }
            th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
            th { background-color: #f2f2f2; }
            .header { text-align: center; margin-bottom: 30px; }
        </style>
    </head>
    <body>
        <div class="header">
            <h1>Ethiopian Social Workers Professional Association</h1>
            <h2><?php echo ucfirst($report_type); ?> Report</h2>
            <p>Date Range: <?php echo date('M d, Y', strtotime($start_date)); ?> to <?php echo date('M d, Y', strtotime($end_date)); ?></p>
            <p>Generated: <?php echo date('F d, Y H:i:s'); ?></p>
        </div>
        
        <script>
            window.onload = function() {
                window.print();
            };
        </script>
    </body>
    </html>
    <?php
}
?>

