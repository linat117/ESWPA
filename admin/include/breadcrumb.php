<?php
/**
 * Breadcrumb Component
 * Usage: include 'include/breadcrumb.php'; breadcrumb(['Dashboard' => 'index.php', 'Current Page' => '']);
 */

if (!function_exists('breadcrumb')) {
    function breadcrumb($items = []) {
        if (empty($items)) {
            return '';
        }
        
        $breadcrumb_html = '<nav aria-label="breadcrumb"><ol class="breadcrumb mb-3">';
        
        $item_count = count($items);
        $current_index = 0;
        
        foreach ($items as $label => $url) {
            $current_index++;
            
            if ($current_index === $item_count) {
                // Last item (current page) - active state
                $breadcrumb_html .= '<li class="breadcrumb-item active" aria-current="page">' . htmlspecialchars($label) . '</li>';
            } else {
                // Link item
                $breadcrumb_html .= '<li class="breadcrumb-item">';
                if ($url) {
                    $breadcrumb_html .= '<a href="' . htmlspecialchars($url) . '">' . htmlspecialchars($label) . '</a>';
                } else {
                    $breadcrumb_html .= htmlspecialchars($label);
                }
                $breadcrumb_html .= '</li>';
            }
        }
        
        $breadcrumb_html .= '</ol></nav>';
        
        return $breadcrumb_html;
    }
}

// Auto-generate breadcrumb if $breadcrumb_items is set
if (isset($breadcrumb_items) && is_array($breadcrumb_items)) {
    echo breadcrumb($breadcrumb_items);
}

