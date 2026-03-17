<?php
/**
 * Get active partners from database
 * 
 * @param mysqli $conn Database connection
 * @return array Array of partner data
 */
function getPartners($conn) {
    $partners = [];
    
    $query = "SELECT * FROM partners WHERE status = 'active' ORDER BY sort_order ASC, name ASC";
    $result = mysqli_query($conn, $query);
    
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $partners[] = $row;
        }
    }
    
    return $partners;
}

/**
 * Display partners grid for front-end
 * 
 * @param array $partners Array of partner data
 * @param string $grid_class CSS class for the grid container
 * @param string $item_class CSS class for individual partner items
 */
function displayPartnersGrid($partners, $grid_class = 'partners-grid', $item_class = 'partners-grid__item') {
    if (empty($partners)) {
        // Fallback to default hardcoded partners if database is empty
        $default_partners = [
            ['name' => 'Default Partner 1', 'logo_url' => 'assets/img/partner2.png'],
            ['name' => 'Default Partner 2', 'logo_url' => 'assets/img/partner1.png'],
            ['name' => 'Default Partner 3', 'logo_url' => 'assets/img/ehrc.png'],
            ['name' => 'Default Partner 4', 'logo_url' => 'assets/img/ephi.png'],
            ['name' => 'Default Partner 5', 'logo_url' => 'assets/img/aau.jpeg'],
        ];
        
        foreach ($default_partners as $partner) {
            echo "<div class='{$item_class}'>";
            echo "<img src='" . htmlspecialchars($partner['logo_url']) . "' alt='" . htmlspecialchars($partner['name']) . "' />";
            echo "</div>";
        }
        return;
    }
    
    foreach ($partners as $partner) {
        $logo_url = '';
        $partner_name = htmlspecialchars($partner['name']);
        
        if (!empty($partner['logo_url'])) {
            if (strpos($partner['logo_url'], 'http') === 0) {
                // External URL
                $logo_url = htmlspecialchars($partner['logo_url']);
            } else {
                // Local file
                $logo_url = htmlspecialchars($partner['logo_url']);
            }
        } else {
            // Fallback to default image if no logo
            $logo_url = 'assets/img/partner2.png';
        }
        
        echo "<div class='{$item_class}'>";
        if (!empty($partner['website_url'])) {
            echo "<a href='" . htmlspecialchars($partner['website_url']) . "' target='_blank' title='{$partner_name}'>";
        }
        echo "<img src='{$logo_url}' alt='{$partner_name}' onerror=\"this.src='assets/img/partner2.png'\" />";
        if (!empty($partner['website_url'])) {
            echo "</a>";
        }
        echo "</div>";
    }
}

/**
 * Display partners for home-v2 layout
 * 
 * @param array $partners Array of partner data
 */
function displayHomeV2Partners($partners) {
    if (empty($partners)) {
        // Fallback to default hardcoded partners if database is empty
        $default_partners = [
            ['name' => 'Default Partner 1', 'logo_url' => 'assets/img/partner2.png'],
            ['name' => 'Default Partner 2', 'logo_url' => 'assets/img/partner1.png'],
            ['name' => 'Default Partner 3', 'logo_url' => 'assets/img/ehrc.png'],
            ['name' => 'Default Partner 4', 'logo_url' => 'assets/img/aau.jpeg'],
            ['name' => 'Default Partner 5', 'logo_url' => 'assets/img/ephi.png'],
        ];
        
        foreach ($default_partners as $partner) {
            echo "<div class='home-v2-partners-grid__item'><img src='" . htmlspecialchars($partner['logo_url']) . "' alt='" . htmlspecialchars($partner['name']) . "' /></div>";
        }
        return;
    }
    
    foreach ($partners as $partner) {
        $logo_url = '';
        $partner_name = htmlspecialchars($partner['name']);
        
        if (!empty($partner['logo_url'])) {
            if (strpos($partner['logo_url'], 'http') === 0) {
                // External URL
                $logo_url = htmlspecialchars($partner['logo_url']);
            } else {
                // Local file
                $logo_url = htmlspecialchars($partner['logo_url']);
            }
        } else {
            // Fallback to default image if no logo
            $logo_url = 'assets/img/partner2.png';
        }
        
        echo "<div class='home-v2-partners-grid__item'>";
        if (!empty($partner['website_url'])) {
            echo "<a href='" . htmlspecialchars($partner['website_url']) . "' target='_blank' title='{$partner_name}'>";
        }
        echo "<img src='{$logo_url}' alt='{$partner_name}' onerror=\"this.src='assets/img/partner2.png'\" />";
        if (!empty($partner['website_url'])) {
            echo "</a>";
        }
        echo "</div>";
    }
}
?>
