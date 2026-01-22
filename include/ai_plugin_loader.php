<?php
/**
 * AI Plugin Loader
 * Loads and manages AI plugins
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/../plugins/ai/base_ai_plugin.php';

/**
 * Load all active plugins
 */
function loadAIPlugins() {
    global $conn;
    
    $query = "SELECT * FROM ai_plugins WHERE is_active = 1 ORDER BY is_default DESC, plugin_type, plugin_name";
    $result = mysqli_query($conn, $query);
    
    $plugins = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $plugin = loadPlugin($row);
        if ($plugin) {
            $plugins[$row['plugin_type']][] = $plugin;
        }
    }
    
    return $plugins;
}

/**
 * Load a specific plugin
 */
function loadPlugin($pluginData) {
    $pluginFile = __DIR__ . '/../' . $pluginData['plugin_file'];
    
    if (!file_exists($pluginFile)) {
        error_log("Plugin file not found: $pluginFile");
        return null;
    }
    
    require_once $pluginFile;
    
    $className = $pluginData['plugin_class'];
    
    if (!class_exists($className)) {
        error_log("Plugin class not found: $className");
        return null;
    }
    
    // Parse settings JSON
    $settings = [];
    if (!empty($pluginData['settings_json'])) {
        $settings = json_decode($pluginData['settings_json'], true) ?? [];
    }
    
    try {
        $plugin = new $className($pluginData['id'], $settings);
        return $plugin;
    } catch (Exception $e) {
        error_log("Error loading plugin {$pluginData['plugin_name']}: " . $e->getMessage());
        return null;
    }
}

/**
 * Get default plugin for a type
 */
function getDefaultPlugin($pluginType) {
    global $conn;
    
    $query = "SELECT * FROM ai_plugins 
              WHERE plugin_type = ? AND is_active = 1 
              ORDER BY is_default DESC, id ASC 
              LIMIT 1";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $pluginType);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $pluginData = $result->fetch_assoc();
        return loadPlugin($pluginData);
    }
    
    return null;
}

/**
 * Get plugin by ID
 */
function getPluginById($pluginId) {
    global $conn;
    
    $query = "SELECT * FROM ai_plugins WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $pluginId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $pluginData = $result->fetch_assoc();
        return loadPlugin($pluginData);
    }
    
    return null;
}

/**
 * Register a new plugin
 */
function registerPlugin($pluginName, $pluginClass, $pluginFile, $pluginType, $description = null) {
    global $conn;
    
    $query = "INSERT INTO ai_plugins 
              (plugin_name, plugin_class, plugin_file, plugin_type, description, is_active) 
              VALUES (?, ?, ?, ?, ?, 1)
              ON DUPLICATE KEY UPDATE 
              plugin_class = VALUES(plugin_class),
              plugin_file = VALUES(plugin_file),
              plugin_type = VALUES(plugin_type),
              description = VALUES(description)";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sssss", $pluginName, $pluginClass, $pluginFile, $pluginType, $description);
    
    if ($stmt->execute()) {
        return $stmt->insert_id;
    }
    
    return false;
}

