<?php
/**
 * Access Control Functions
 * 
 * Handles package-based, badge-based, and special permissions for resources and research
 * Agent: agent_dev_2
 * Phase: Phase 2 - Access Control System
 */

require_once __DIR__ . '/config.php';

/**
 * Check if member can access a resource
 * 
 * @param int $member_id Member ID
 * @param int $resource_id Resource ID
 * @return array ['granted' => bool, 'reason' => string]
 */
function canAccessResource($member_id, $resource_id) {
    global $conn;
    
    // Get resource details
    $resourceQuery = "SELECT access_level, status FROM resources WHERE id = ?";
    $stmt = $conn->prepare($resourceQuery);
    $stmt->bind_param("i", $resource_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $stmt->close();
        return ['granted' => false, 'reason' => 'Resource not found'];
    }
    
    $resource = $result->fetch_assoc();
    $stmt->close();
    
    // Check if resource is active
    if (isset($resource['status']) && $resource['status'] !== 'active') {
        return ['granted' => false, 'reason' => 'Resource is not active'];
    }
    
    $access_level = $resource['access_level'] ?? 'member';
    
    // Public resources are accessible to everyone
    if ($access_level === 'public') {
        logAccess($member_id, $resource_id, null, 'view', true);
        return ['granted' => true, 'reason' => 'Public resource'];
    }
    
    // If not logged in, deny access (except public)
    if (!$member_id) {
        logAccess(null, $resource_id, null, 'view', false, 'Member not logged in');
        return ['granted' => false, 'reason' => 'Please login to access this resource'];
    }
    
    // Check special permissions first (highest priority)
    if (hasSpecialPermission($member_id, 'resource_access', $resource_id)) {
        logAccess($member_id, $resource_id, null, 'view', true);
        return ['granted' => true, 'reason' => 'Special permission granted'];
    }
    
    // Check package permissions
    $packageAccess = checkPackageResourceAccess($member_id, $access_level);
    if ($packageAccess['granted']) {
        logAccess($member_id, $resource_id, null, 'view', true);
        return ['granted' => true, 'reason' => $packageAccess['reason']];
    }
    
    // Check badge permissions
    $badgeAccess = checkBadgeResourceAccess($member_id, $access_level);
    if ($badgeAccess['granted']) {
        logAccess($member_id, $resource_id, null, 'view', true);
        return ['granted' => true, 'reason' => $badgeAccess['reason']];
    }
    
    // Default: deny access
    $reason = 'Access denied. Required: ' . ucfirst($access_level) . ' package or badge';
    logAccess($member_id, $resource_id, null, 'view', false, $reason);
    return ['granted' => false, 'reason' => $reason];
}

/**
 * Check if member can access research
 * 
 * @param int $member_id Member ID
 * @param int $research_id Research ID
 * @return array ['granted' => bool, 'reason' => string]
 */
function canAccessResearch($member_id, $research_id) {
    global $conn;
    
    // If not logged in, deny access
    if (!$member_id) {
        logAccess($member_id, null, $research_id, 'view', false, 'Member not logged in');
        return ['granted' => false, 'reason' => 'Please login to access research'];
    }
    
    // Check special permissions first
    if (hasSpecialPermission($member_id, 'research_access', null, $research_id)) {
        logAccess($member_id, null, $research_id, 'view', true);
        return ['granted' => true, 'reason' => 'Special permission granted'];
    }
    
    // Check package permissions
    $packageAccess = checkPackageResearchAccess($member_id);
    if ($packageAccess['granted']) {
        logAccess($member_id, null, $research_id, 'view', true);
        return ['granted' => true, 'reason' => $packageAccess['reason']];
    }
    
    // Check badge permissions
    $badgeAccess = checkBadgeResearchAccess($member_id);
    if ($badgeAccess['granted']) {
        logAccess($member_id, null, $research_id, 'view', true);
        return ['granted' => true, 'reason' => $badgeAccess['reason']];
    }
    
    // Default: deny access
    $reason = 'Access denied. Research access requires appropriate package or badge';
    logAccess($member_id, null, $research_id, 'view', false, $reason);
    return ['granted' => false, 'reason' => $reason];
}

/**
 * Check package-based resource access
 * 
 * @param int $member_id Member ID
 * @param string $required_level Required access level (public, member, premium, restricted)
 * @return array ['granted' => bool, 'reason' => string]
 */
function checkPackageResourceAccess($member_id, $required_level) {
    global $conn;
    
    // Get member's package
    $memberQuery = "SELECT package_id, package_end_date FROM registrations WHERE id = ?";
    $stmt = $conn->prepare($memberQuery);
    $stmt->bind_param("i", $member_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $stmt->close();
        return ['granted' => false, 'reason' => 'Member not found'];
    }
    
    $member = $result->fetch_assoc();
    $stmt->close();
    
    // Check if package is expired
    if (!empty($member['package_end_date'])) {
        $endDate = new DateTime($member['package_end_date']);
        $today = new DateTime();
        if ($endDate < $today) {
            return ['granted' => false, 'reason' => 'Package expired'];
        }
    }
    
    if (empty($member['package_id'])) {
        return ['granted' => false, 'reason' => 'No package assigned'];
    }
    
    // Get package permissions
    $packageQuery = "SELECT pp.resource_access, mp.slug 
                     FROM package_permissions pp 
                     JOIN membership_packages mp ON pp.package_id = mp.id 
                     WHERE pp.package_id = ?";
    $stmt = $conn->prepare($packageQuery);
    $stmt->bind_param("i", $member['package_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $stmt->close();
        return ['granted' => false, 'reason' => 'Package permissions not found'];
    }
    
    $package = $result->fetch_assoc();
    $stmt->close();
    
    $resource_access = $package['resource_access'];
    
    // Check access based on required level
    switch ($required_level) {
        case 'public':
        case 'member':
            // All packages can access public and member resources
            return ['granted' => true, 'reason' => 'Package: ' . $package['slug']];
            
        case 'premium':
            // Premium, professional, and lifetime can access
            if (in_array($resource_access, ['premium', 'all'])) {
                return ['granted' => true, 'reason' => 'Package: ' . $package['slug']];
            }
            break;
            
        case 'restricted':
            // Only all access can get restricted
            if ($resource_access === 'all') {
                return ['granted' => true, 'reason' => 'Package: ' . $package['slug']];
            }
            break;
    }
    
    return ['granted' => false, 'reason' => 'Package does not have required access level'];
}

/**
 * Check package-based research access
 * 
 * @param int $member_id Member ID
 * @return array ['granted' => bool, 'reason' => string]
 */
function checkPackageResearchAccess($member_id) {
    global $conn;
    
    // Get member's package
    $memberQuery = "SELECT package_id, package_end_date FROM registrations WHERE id = ?";
    $stmt = $conn->prepare($memberQuery);
    $stmt->bind_param("i", $member_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $stmt->close();
        return ['granted' => false, 'reason' => 'Member not found'];
    }
    
    $member = $result->fetch_assoc();
    $stmt->close();
    
    if (empty($member['package_id'])) {
        return ['granted' => false, 'reason' => 'No package assigned'];
    }
    
    // Get package permissions
    $packageQuery = "SELECT pp.research_access, mp.slug 
                     FROM package_permissions pp 
                     JOIN membership_packages mp ON pp.package_id = mp.id 
                     WHERE pp.package_id = ?";
    $stmt = $conn->prepare($packageQuery);
    $stmt->bind_param("i", $member['package_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $stmt->close();
        return ['granted' => false, 'reason' => 'Package permissions not found'];
    }
    
    $package = $result->fetch_assoc();
    $stmt->close();
    
    $research_access = $package['research_access'];
    
    if (in_array($research_access, ['view', 'create', 'collaborate', 'all'])) {
        return ['granted' => true, 'reason' => 'Package: ' . $package['slug']];
    }
    
    return ['granted' => false, 'reason' => 'Package does not have research access'];
}

/**
 * Check badge-based resource access
 * 
 * @param int $member_id Member ID
 * @param string $required_level Required access level
 * @return array ['granted' => bool, 'reason' => string]
 */
function checkBadgeResourceAccess($member_id, $required_level) {
    global $conn;
    
    // Get member's active badges
    $badgeQuery = "SELECT mb.badge_name, bp.resource_access 
                   FROM member_badges mb
                   JOIN badge_permissions bp ON mb.badge_name = bp.badge_name
                   WHERE mb.member_id = ? AND mb.is_active = 1";
    $stmt = $conn->prepare($badgeQuery);
    $stmt->bind_param("i", $member_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($badge = $result->fetch_assoc()) {
        $resource_access = $badge['resource_access'];
        
        // Check if badge grants required access
        if ($resource_access === 'all') {
            $stmt->close();
            return ['granted' => true, 'reason' => 'Badge: ' . $badge['badge_name']];
        }
        
        if ($required_level === 'premium' && $resource_access === 'premium') {
            $stmt->close();
            return ['granted' => true, 'reason' => 'Badge: ' . $badge['badge_name']];
        }
    }
    
    $stmt->close();
    return ['granted' => false, 'reason' => 'No qualifying badge'];
}

/**
 * Check badge-based research access
 * 
 * @param int $member_id Member ID
 * @return array ['granted' => bool, 'reason' => string]
 */
function checkBadgeResearchAccess($member_id) {
    global $conn;
    
    // Get member's active badges
    $badgeQuery = "SELECT mb.badge_name, bp.research_access 
                   FROM member_badges mb
                   JOIN badge_permissions bp ON mb.badge_name = bp.badge_name
                   WHERE mb.member_id = ? AND mb.is_active = 1";
    $stmt = $conn->prepare($badgeQuery);
    $stmt->bind_param("i", $member_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($badge = $result->fetch_assoc()) {
        $research_access = $badge['research_access'];
        
        if (in_array($research_access, ['view', 'create', 'collaborate', 'all'])) {
            $stmt->close();
            return ['granted' => true, 'reason' => 'Badge: ' . $badge['badge_name']];
        }
    }
    
    $stmt->close();
    return ['granted' => false, 'reason' => 'No qualifying badge'];
}

/**
 * Check if member has special permission
 * 
 * @param int $member_id Member ID
 * @param string $permission_type Permission type
 * @param int|null $resource_id Resource ID (optional)
 * @param int|null $research_id Research ID (optional)
 * @return bool
 */
function hasSpecialPermission($member_id, $permission_type, $resource_id = null, $research_id = null) {
    global $conn;
    
    $query = "SELECT id FROM special_permissions 
              WHERE member_id = ? 
              AND permission_type = ? 
              AND is_active = 1
              AND (expires_at IS NULL OR expires_at > NOW())";
    
    $params = [$member_id, $permission_type];
    $types = "is";
    
    if ($resource_id !== null) {
        $query .= " AND (resource_id IS NULL OR resource_id = ?)";
        $params[] = $resource_id;
        $types .= "i";
    }
    
    if ($research_id !== null) {
        $query .= " AND (research_id IS NULL OR research_id = ?)";
        $params[] = $research_id;
        $types .= "i";
    }
    
    $stmt = $conn->prepare($query);
    if ($params) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $hasPermission = $result->num_rows > 0;
    $stmt->close();
    
    return $hasPermission;
}

/**
 * Get member's effective permissions
 * 
 * @param int $member_id Member ID
 * @return array
 */
function getMemberPermissions($member_id) {
    global $conn;
    
    $permissions = [
        'package' => null,
        'badges' => [],
        'special_permissions' => [],
        'resource_access' => 'none',
        'research_access' => 'none'
    ];
    
    // Get package
    $memberQuery = "SELECT package_id, package_start_date, package_end_date 
                   FROM registrations WHERE id = ?";
    $stmt = $conn->prepare($memberQuery);
    $stmt->bind_param("i", $member_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $member = $result->fetch_assoc();
        if ($member['package_id']) {
            $packageQuery = "SELECT mp.*, pp.* 
                            FROM membership_packages mp
                            LEFT JOIN package_permissions pp ON mp.id = pp.package_id
                            WHERE mp.id = ?";
            $pstmt = $conn->prepare($packageQuery);
            $pstmt->bind_param("i", $member['package_id']);
            $pstmt->execute();
            $presult = $pstmt->get_result();
            if ($presult->num_rows > 0) {
                $permissions['package'] = $presult->fetch_assoc();
                $permissions['package']['start_date'] = $member['package_start_date'];
                $permissions['package']['end_date'] = $member['package_end_date'];
            }
            $pstmt->close();
        }
    }
    $stmt->close();
    
    // Get badges
    $badgeQuery = "SELECT mb.badge_name, mb.earned_at, bp.* 
                   FROM member_badges mb
                   JOIN badge_permissions bp ON mb.badge_name = bp.badge_name
                   WHERE mb.member_id = ? AND mb.is_active = 1";
    $stmt = $conn->prepare($badgeQuery);
    $stmt->bind_param("i", $member_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($badge = $result->fetch_assoc()) {
        $permissions['badges'][] = $badge;
    }
    $stmt->close();
    
    // Get special permissions
    $specialQuery = "SELECT * FROM special_permissions 
                     WHERE member_id = ? 
                     AND is_active = 1
                     AND (expires_at IS NULL OR expires_at > NOW())";
    $stmt = $conn->prepare($specialQuery);
    $stmt->bind_param("i", $member_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($perm = $result->fetch_assoc()) {
        $permissions['special_permissions'][] = $perm;
    }
    $stmt->close();
    
    return $permissions;
}

/**
 * Log access attempt
 * 
 * @param int|null $member_id Member ID
 * @param int|null $resource_id Resource ID
 * @param int|null $research_id Research ID
 * @param string $action Action performed
 * @param bool $granted Whether access was granted
 * @param string|null $denial_reason Reason for denial
 * @return void
 */
function logAccess($member_id, $resource_id = null, $research_id = null, $action = 'view', $granted = true, $denial_reason = null) {
    global $conn;
    
    // Check if access_logs table exists
    $checkTable = $conn->query("SHOW TABLES LIKE 'access_logs'");
    if ($checkTable->num_rows === 0) {
        return; // Table doesn't exist yet, skip logging
    }
    
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
    
    $query = "INSERT INTO access_logs 
              (member_id, resource_id, research_id, action, access_granted, denial_reason, ip_address, user_agent) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iiiisiss", $member_id, $resource_id, $research_id, $action, $granted, $denial_reason, $ip_address, $user_agent);
    $stmt->execute();
    $stmt->close();
}

?>

