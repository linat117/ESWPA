<?php
/**
 * Export Handler - Centralized export functionality
 * Supports: CSV, Excel (XLSX), JSON, and PDF formats
 * 
 * Note: Session check should be done in the calling page before including this file
 */

/**
 * Export data to CSV format
 * 
 * @param array $data - Array of associative arrays (rows)
 * @param array $headers - Column headers
 * @param string $filename - Output filename (without extension)
 */
function exportToCSV($data, $headers, $filename = 'export') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $filename . '_' . date('Y-m-d') . '.csv');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    $output = fopen('php://output', 'w');
    
    // Add BOM for UTF-8 Excel compatibility
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Write headers
    fputcsv($output, $headers);
    
    // Write data rows
    foreach ($data as $row) {
        $csv_row = [];
        foreach ($headers as $header) {
            // Handle nested array access (e.g., 'user.name')
            $value = getNestedValue($row, $header);
            $csv_row[] = $value;
        }
        fputcsv($output, $csv_row);
    }
    
    fclose($output);
    exit();
}

/**
 * Export data to JSON format
 * 
 * @param array $data - Array of data to export
 * @param string $filename - Output filename (without extension)
 */
function exportToJSON($data, $filename = 'export') {
    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $filename . '_' . date('Y-m-d') . '.json');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit();
}

/**
 * Export data to Excel (XLSX) format using proper XML structure
 * 
 * @param array $data - Array of associative arrays (rows)
 * @param array $headers - Column headers
 * @param string $filename - Output filename (without extension)
 */
function exportToExcel($data, $headers, $filename = 'export') {
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename=' . $filename . '_' . date('Y-m-d') . '.xlsx');
    header('Pragma: no-cache');
    header('Expires: 0');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Content-Transfer-Encoding: binary');
    
    // Clean output buffer
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    echo '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet" xmlns:html="http://www.w3.org/TR/REC-html40">' . "\n";
    echo '  <Worksheet ss:Name="Sheet1">' . "\n";
    echo '    <Table>' . "\n";
    
    // Headers row
    echo '      <Row>' . "\n";
    foreach ($headers as $header) {
        $clean_header = htmlspecialchars(strip_tags($header), ENT_QUOTES, 'UTF-8');
        echo '        <Cell><Data ss:Type="String">' . $clean_header . '</Data></Cell>' . "\n";
    }
    echo '      </Row>' . "\n";
    
    // Data rows
    foreach ($data as $row) {
        echo '      <Row>' . "\n";
        foreach ($headers as $header) {
            $value = getNestedValue($row, $header);
            // Clean the value - remove HTML tags and special characters
            $clean_value = htmlspecialchars(strip_tags($value), ENT_QUOTES, 'UTF-8');
            echo '        <Cell><Data ss:Type="String">' . $clean_value . '</Data></Cell>' . "\n";
        }
        echo '      </Row>' . "\n";
    }
    
    echo '    </Table>' . "\n";
    echo '  </Worksheet>' . "\n";
    echo '</Workbook>' . "\n";
    
    exit();
}

/**
 * Export data to PDF format (simplified - requires TCPDF or similar library)
 * For now, we'll create an HTML version that can be printed to PDF
 * 
 * @param array $data - Array of associative arrays (rows)
 * @param array $headers - Column headers
 * @param string $title - Report title
 * @param string $filename - Output filename (without extension)
 */
function exportToPDF($data, $headers, $title = 'Report', $filename = 'export') {
    // Simple HTML-based PDF (browser print to PDF)
    // For production, consider using TCPDF or DomPDF
    
    header('Content-Type: text/html; charset=utf-8');
    
    $html = '<!DOCTYPE html>';
    $html .= '<html><head>';
    $html .= '<meta charset="UTF-8">';
    $html .= '<title>' . htmlspecialchars($title) . '</title>';
    $html .= '<style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        h1 { color: #333; border-bottom: 2px solid #333; padding-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { background-color: #f0f0f0; border: 1px solid #ddd; padding: 8px; text-align: left; }
        td { border: 1px solid #ddd; padding: 8px; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        .footer { margin-top: 30px; text-align: center; color: #666; font-size: 12px; }
        @media print {
            body { margin: 0; }
            .no-print { display: none; }
        }
    </style>';
    $html .= '</head><body>';
    $html .= '<h1>' . htmlspecialchars($title) . '</h1>';
    $html .= '<p>Generated on: ' . date('F j, Y g:i A') . '</p>';
    $html .= '<table>';
    
    // Headers
    $html .= '<thead><tr>';
    foreach ($headers as $header) {
        $html .= '<th>' . htmlspecialchars($header) . '</th>';
    }
    $html .= '</tr></thead>';
    
    // Data rows
    $html .= '<tbody>';
    foreach ($data as $row) {
        $html .= '<tr>';
        foreach ($headers as $header) {
            $value = getNestedValue($row, $header);
            $html .= '<td>' . htmlspecialchars($value) . '</td>';
        }
        $html .= '</tr>';
    }
    $html .= '</tbody>';
    $html .= '</table>';
    $html .= '<div class="footer">Page 1</div>';
    $html .= '<script>
        window.onload = function() {
            window.print();
        };
    </script>';
    $html .= '</body></html>';
    
    echo $html;
    exit();
}

/**
 * Get nested value from array using dot notation
 * 
 * @param array $array - Source array
 * @param string $key - Key (supports dot notation like 'user.name')
 * @return mixed - Value or empty string
 */
function getNestedValue($array, $key) {
    if (isset($array[$key])) {
        $value = $array[$key];
        return is_array($value) || is_object($value) ? json_encode($value) : $value;
    }
    
    // Handle dot notation
    $keys = explode('.', $key);
    $value = $array;
    foreach ($keys as $k) {
        if (is_array($value) && isset($value[$k])) {
            $value = $value[$k];
        } elseif (is_object($value) && isset($value->$k)) {
            $value = $value->$k;
        } else {
            return '';
        }
    }
    
    return is_array($value) || is_object($value) ? json_encode($value) : ($value ?? '');
}

/**
 * Process export request
 * 
 * @param array $data - Data to export
 * @param array $headers - Column headers
 * @param string $filename - Base filename
 * @param string $format - Export format (csv, json, excel, pdf)
 * @param string $title - Report title (for PDF)
 */
function processExport($data, $headers, $filename = 'export', $format = 'csv', $title = 'Report') {
    switch (strtolower($format)) {
        case 'csv':
            exportToCSV($data, $headers, $filename);
            break;
        case 'json':
            exportToJSON($data, $filename);
            break;
        case 'excel':
        case 'xlsx':
            exportToExcel($data, $headers, $filename);
            break;
        case 'pdf':
            exportToPDF($data, $headers, $title, $filename);
            break;
        default:
            exportToCSV($data, $headers, $filename);
    }
}

/**
 * Generate export buttons HTML
 * 
 * @param string $base_url - Base URL with query parameters (without format parameter)
 * @param string $filename - Base filename for export
 * @param array $formats - Available formats (default: ['csv', 'excel', 'json', 'pdf'])
 * @return string - HTML for export buttons
 */
function getExportButtons($base_url, $filename = 'export', $formats = ['csv', 'excel', 'json', 'pdf']) {
    $buttons = '<div class="btn-group" role="group">';
    $buttons .= '<button type="button" class="btn btn-success dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">';
    $buttons .= '<i class="ri-download-line"></i> Export';
    $buttons .= '</button>';
    $buttons .= '<ul class="dropdown-menu">';
    
    $format_labels = [
        'csv' => '<i class="ri-file-line"></i> CSV',
        'excel' => '<i class="ri-file-excel-line"></i> Excel',
        'json' => '<i class="ri-code-line"></i> JSON',
        'pdf' => '<i class="ri-file-pdf-line"></i> PDF'
    ];
    
    foreach ($formats as $format) {
        $url = $base_url . (strpos($base_url, '?') !== false ? '&' : '?') . 'export=' . $format;
        $label = $format_labels[$format] ?? ucfirst($format);
        $buttons .= '<li><a class="dropdown-item" href="' . htmlspecialchars($url) . '">' . $label . '</a></li>';
    }
    
    $buttons .= '</ul>';
    $buttons .= '</div>';
    
    return $buttons;
}

