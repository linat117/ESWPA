<?php
/**
 * Data Synchronization Handler
 * 
 * Handles synchronization between local and remote databases
 * Records all sync operations in sync_logs table
 * 
 * Created: December 23, 2025
 */

require_once __DIR__ . '/conn.php';

/**
 * Perform database synchronization
 * 
 * @param string $direction 'pull' or 'push'
 * @param array $sync_settings Sync configuration
 * @param int $user_id Admin user ID initiating sync
 * @return array Result with success status and message
 */
function performSync($direction, $sync_settings, $user_id) {
    global $conn;
    
    $start_time = microtime(true);
    $sync_log_id = null;
    $tables_synced = [];
    $records_synced = 0;
    $files_synced = 0;
    $status = 'in_progress';
    $error_message = null;
    
    try {
        // Create sync log entry
        $log_query = "INSERT INTO sync_logs (sync_direction, sync_type, status, initiated_by, remote_host, remote_database, started_at) 
                      VALUES (?, 'full', 'in_progress', ?, ?, ?, NOW())";
        $stmt = $conn->prepare($log_query);
        $sync_type = 'full';
        $stmt->bind_param("siss", $direction, $user_id, $sync_settings['remote_host'], $sync_settings['remote_database']);
        $stmt->execute();
        $sync_log_id = $conn->insert_id;
        $stmt->close();
        
        // Connect to remote database
        $remote_conn = new mysqli(
            $sync_settings['remote_host'],
            $sync_settings['remote_user'],
            $sync_settings['remote_password'],
            $sync_settings['remote_database']
        );
        
        if ($remote_conn->connect_error) {
            throw new Exception("Failed to connect to remote database: " . $remote_conn->connect_error);
        }
        
        // Get list of tables to sync
        $tables_to_sync = getTablesToSync($conn, $remote_conn);
        
        if ($direction === 'pull') {
            // Pull: Remote → Local
            foreach ($tables_to_sync as $table) {
                $result = syncTable($remote_conn, $conn, $table, 'pull');
                if ($result['success']) {
                    $tables_synced[] = $table;
                    $records_synced += $result['records'];
                } else {
                    $error_message = "Failed to sync table $table: " . $result['error'];
                    $status = 'partial';
                    break;
                }
            }
        } else {
            // Push: Local → Remote
            foreach ($tables_to_sync as $table) {
                $result = syncTable($conn, $remote_conn, $table, 'push');
                if ($result['success']) {
                    $tables_synced[] = $table;
                    $records_synced += $result['records'];
                } else {
                    $error_message = "Failed to sync table $table: " . $result['error'];
                    $status = 'partial';
                    break;
                }
            }
        }
        
        $remote_conn->close();
        
        // Update sync log
        $end_time = microtime(true);
        $duration = round($end_time - $start_time, 2);
        
        if (empty($error_message) && $status === 'in_progress') {
            $status = 'completed';
        } elseif (!empty($error_message)) {
            $status = 'failed';
        }
        
        updateSyncLog($sync_log_id, $status, $tables_synced, $records_synced, $files_synced, $error_message, $duration);
        
        return [
            'success' => $status === 'completed',
            'message' => $status === 'completed' 
                ? "Sync completed successfully. Synced " . count($tables_synced) . " tables with $records_synced records in {$duration}s."
                : ($error_message ?? "Sync completed with partial success."),
            'log_id' => $sync_log_id,
            'tables_synced' => count($tables_synced),
            'records_synced' => $records_synced,
            'duration' => $duration
        ];
        
    } catch (Exception $e) {
        $end_time = microtime(true);
        $duration = round($end_time - $start_time, 2);
        $error_message = $e->getMessage();
        
        if ($sync_log_id) {
            updateSyncLog($sync_log_id, 'failed', $tables_synced, $records_synced, $files_synced, $error_message, $duration);
        }
        
        return [
            'success' => false,
            'message' => "Sync failed: " . $error_message,
            'log_id' => $sync_log_id
        ];
    }
}

/**
 * Get list of tables to sync (excluding system tables)
 */
function getTablesToSync($local_conn, $remote_conn) {
    $exclude_tables = ['sync_logs', 'audit_logs', 'backups', 'changelogs'];
    $tables = [];
    
    // Get tables from local database
    $result = $local_conn->query("SHOW TABLES");
    while ($row = $result->fetch_array()) {
        $table = $row[0];
        if (!in_array($table, $exclude_tables)) {
            $tables[] = $table;
        }
    }
    
    return $tables;
}

/**
 * Sync a single table
 */
function syncTable($source_conn, $target_conn, $table, $direction) {
    try {
        // Check if table exists in target
        $check_query = "SHOW TABLES LIKE '$table'";
        $result = $target_conn->query($check_query);
        
        if ($result->num_rows === 0) {
            // Create table structure in target
            $create_query = "SHOW CREATE TABLE `$table`";
            $create_result = $source_conn->query($create_query);
            if ($create_result && $create_result->num_rows > 0) {
                $create_row = $create_result->fetch_assoc();
                $target_conn->query($create_row['Create Table']);
            }
        }
        
        // Get all data from source
        $source_data = $source_conn->query("SELECT * FROM `$table`");
        
        if (!$source_data) {
            return ['success' => false, 'error' => $source_conn->error, 'records' => 0];
        }
        
        // Clear target table (optional - you might want to merge instead)
        // $target_conn->query("TRUNCATE TABLE `$table`");
        
        // Get column names
        $columns_result = $source_conn->query("SHOW COLUMNS FROM `$table`");
        $columns = [];
        while ($col = $columns_result->fetch_assoc()) {
            $columns[] = $col['Field'];
        }
        
        $records = 0;
        $target_conn->autocommit(FALSE);
        
        while ($row = $source_data->fetch_assoc()) {
            // Build INSERT ... ON DUPLICATE KEY UPDATE query
            $column_list = implode('`, `', $columns);
            $placeholders = implode(', ', array_fill(0, count($columns), '?'));
            $update_list = [];
            foreach ($columns as $col) {
                $update_list[] = "`$col` = VALUES(`$col`)";
            }
            $update_clause = implode(', ', $update_list);
            
            $insert_query = "INSERT INTO `$table` (`$column_list`) VALUES ($placeholders) 
                            ON DUPLICATE KEY UPDATE $update_clause";
            
            $stmt = $target_conn->prepare($insert_query);
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $target_conn->error);
            }
            
            $values = [];
            $types = '';
            foreach ($columns as $col) {
                $value = $row[$col];
                $values[] = $value;
                // Determine type based on value
                if (is_int($value)) {
                    $types .= 'i';
                } elseif (is_float($value)) {
                    $types .= 'd';
                } else {
                    $types .= 's';
                }
            }
            
            if (!empty($values)) {
                // Use call_user_func_array for dynamic parameter binding
                $params = array_merge([$types], $values);
                $refs = [];
                foreach ($params as $key => $value) {
                    $refs[$key] = &$params[$key];
                }
                call_user_func_array([$stmt, 'bind_param'], $refs);
            }
            
            if ($stmt->execute()) {
                $records++;
            }
            $stmt->close();
        }
        
        $target_conn->commit();
        $target_conn->autocommit(TRUE);
        
        return ['success' => true, 'records' => $records, 'error' => null];
        
    } catch (Exception $e) {
        if (isset($target_conn)) {
            $target_conn->rollback();
            $target_conn->autocommit(TRUE);
        }
        return ['success' => false, 'error' => $e->getMessage(), 'records' => 0];
    }
}

/**
 * Update sync log with results
 */
function updateSyncLog($log_id, $status, $tables_synced, $records_synced, $files_synced, $error_message, $duration) {
    global $conn;
    
    $tables_list = implode(', ', $tables_synced);
    
    $update_query = "UPDATE sync_logs SET 
                     status = ?,
                     tables_synced = ?,
                     records_synced = ?,
                     files_synced = ?,
                     error_message = ?,
                     completed_at = NOW(),
                     duration_seconds = ?
                     WHERE id = ?";
    
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("ssiisdi", 
        $status,
        $tables_list,
        $records_synced,
        $files_synced,
        $error_message,
        $duration,
        $log_id
    );
    $stmt->execute();
    $stmt->close();
}

/**
 * Get sync history
 */
function getSyncHistory($limit = 50) {
    global $conn;
    
    $query = "SELECT sl.*, u.username as initiated_by_name 
              FROM sync_logs sl 
              LEFT JOIN user u ON sl.initiated_by = u.id 
              ORDER BY sl.created_at DESC 
              LIMIT ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $history = [];
    while ($row = $result->fetch_assoc()) {
        $history[] = $row;
    }
    $stmt->close();
    
    return $history;
}

/**
 * Get sync statistics
 */
function getSyncStatistics() {
    global $conn;
    
    $stats = [];
    
    // Total syncs
    $result = $conn->query("SELECT COUNT(*) as total FROM sync_logs");
    $stats['total_syncs'] = $result->fetch_assoc()['total'];
    
    // Successful syncs
    $result = $conn->query("SELECT COUNT(*) as total FROM sync_logs WHERE status = 'completed'");
    $stats['successful_syncs'] = $result->fetch_assoc()['total'];
    
    // Failed syncs
    $result = $conn->query("SELECT COUNT(*) as total FROM sync_logs WHERE status = 'failed'");
    $stats['failed_syncs'] = $result->fetch_assoc()['total'];
    
    // Last sync
    $result = $conn->query("SELECT * FROM sync_logs ORDER BY created_at DESC LIMIT 1");
    $stats['last_sync'] = $result->fetch_assoc();
    
    return $stats;
}

