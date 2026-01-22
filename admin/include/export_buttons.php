<?php
/**
 * Export Buttons Component
 * Usage: include 'include/export_buttons.php'; 
 *        echo getExportButtons('current_page.php?param=value', 'filename', ['csv', 'excel', 'json']);
 */

if (!function_exists('getExportButtons')) {
    function getExportButtons($base_url, $filename = 'export', $formats = ['csv', 'excel', 'json', 'pdf']) {
        // Ensure base_url has query string handling
        $separator = strpos($base_url, '?') !== false ? '&' : '?';
        
        $buttons = '<div class="btn-group" role="group">';
        $buttons .= '<button type="button" class="btn btn-success dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">';
        $buttons .= '<i class="ri-download-line"></i> Export';
        $buttons .= '</button>';
        $buttons .= '<ul class="dropdown-menu">';
        
        $format_labels = [
            'csv' => '<i class="ri-file-line"></i> Export as CSV',
            'excel' => '<i class="ri-file-excel-line"></i> Export as Excel',
            'json' => '<i class="ri-code-line"></i> Export as JSON',
            'pdf' => '<i class="ri-file-pdf-line"></i> Export as PDF'
        ];
        
        foreach ($formats as $format) {
            $url = $base_url . $separator . 'export=' . $format;
            $label = $format_labels[$format] ?? '<i class="ri-file-line"></i> Export as ' . ucfirst($format);
            $buttons .= '<li><a class="dropdown-item" href="' . htmlspecialchars($url) . '">' . $label . '</a></li>';
        }
        
        $buttons .= '</ul>';
        $buttons .= '</div>';
        
        return $buttons;
    }
}

