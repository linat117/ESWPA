<?php
/**
 * AI Queue Processor
 * Processes items in the AI processing queue
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/ai_plugin_loader.php';

/**
 * Process next item in queue
 */
function processNextQueueItem($limit = 1) {
    global $conn;
    
    // Get pending items ordered by priority and creation date
    $query = "SELECT * FROM ai_processing_queue 
              WHERE status = 'pending' 
              ORDER BY priority ASC, created_at ASC 
              LIMIT ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $processed = 0;
    
    while ($row = $result->fetch_assoc()) {
        if (processQueueItem($row)) {
            $processed++;
        }
    }
    
    $stmt->close();
    return $processed;
}

/**
 * Process a single queue item
 */
function processQueueItem($queueItem) {
    global $conn;
    
    $queueId = $queueItem['id'];
    $contentType = $queueItem['content_type'];
    $contentId = $queueItem['content_id'];
    $processType = $queueItem['process_type'];
    $pluginId = $queueItem['plugin_id'];
    
    // Update status to processing
    updateQueueStatus($queueId, 'processing');
    
    try {
        // Get plugin
        if ($pluginId) {
            $plugin = getPluginById($pluginId);
        } else {
            // Use default plugin based on process type
            $pluginType = getPluginTypeForProcessType($processType);
            $plugin = getDefaultPlugin($pluginType);
        }
        
        if (!$plugin || !$plugin->isAvailable()) {
            throw new Exception('No available plugin for processing');
        }
        
        $startTime = microtime(true);
        
        // Process based on content type
        $options = [
            'process_type' => $processType
        ];
        
        if ($contentType === 'resource') {
            $result = $plugin->processResource($contentId, $options);
        } else {
            $result = $plugin->processResearch($contentId, $options);
        }
        
        $processingTime = microtime(true) - $startTime;
        
        // Save results
        saveProcessingResult($queueId, $contentType, $contentId, $processType, $pluginId, $result, $processingTime);
        
        // Update content metadata
        updateContentMetadata($contentType, $contentId, $result);
        
        // Update queue status
        updateQueueStatus($queueId, 'completed', null, $result);
        
        return true;
        
    } catch (Exception $e) {
        $attempts = $queueItem['attempts'] + 1;
        $maxAttempts = $queueItem['max_attempts'];
        
        if ($attempts >= $maxAttempts) {
            updateQueueStatus($queueId, 'failed', $e->getMessage());
        } else {
            updateQueueStatus($queueId, 'pending', $e->getMessage(), null, $attempts);
        }
        
        return false;
    }
}

/**
 * Update queue item status
 */
function updateQueueStatus($queueId, $status, $errorMessage = null, $result = null, $attempts = null) {
    global $conn;
    
    if ($status === 'processing') {
        $query = "UPDATE ai_processing_queue 
                  SET status = ?, started_at = NOW(), attempts = attempts + 1 
                  WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("si", $status, $queueId);
    } elseif ($status === 'completed') {
        $resultJson = $result ? json_encode($result) : null;
        $query = "UPDATE ai_processing_queue 
                  SET status = ?, completed_at = NOW(), result_json = ? 
                  WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssi", $status, $resultJson, $queueId);
    } elseif ($status === 'failed') {
        $query = "UPDATE ai_processing_queue 
                  SET status = ?, error_message = ?, completed_at = NOW() 
                  WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssi", $status, $errorMessage, $queueId);
    } else {
        $attemptsParam = $attempts ?? 'attempts';
        $query = "UPDATE ai_processing_queue 
                  SET status = ?, error_message = ?, attempts = ? 
                  WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssii", $status, $errorMessage, $attempts, $queueId);
    }
    
    $stmt->execute();
    $stmt->close();
}

/**
 * Save processing result
 */
function saveProcessingResult($queueId, $contentType, $contentId, $processType, $pluginId, $result, $processingTime) {
    global $conn;
    
    $resultJson = json_encode($result);
    $tokensUsed = $result['tokens_used'] ?? null;
    $cost = $result['cost'] ?? null;
    $metadata = isset($result['metadata']) ? json_encode($result['metadata']) : null;
    
    $query = "INSERT INTO ai_processing_results 
              (queue_id, content_type, content_id, process_type, plugin_id, 
               result_data, processing_time, tokens_used, cost, metadata) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iisissdidd", 
        $queueId, $contentType, $contentId, $processType, $pluginId,
        $resultJson, $processingTime, $tokensUsed, $cost, $metadata
    );
    
    $stmt->execute();
    $stmt->close();
}

/**
 * Update content metadata after processing
 */
function updateContentMetadata($contentType, $contentId, $result) {
    global $conn;
    
    $metadata = [
        'ai_processed' => true,
        'ai_processed_at' => date('Y-m-d H:i:s'),
        'processing_results' => $result
    ];
    
    // Extract specific fields
    if (isset($result['summary'])) {
        $metadata['summary'] = $result['summary'];
    }
    if (isset($result['keywords'])) {
        $metadata['keywords'] = $result['keywords'];
    }
    if (isset($result['extracted_text'])) {
        $metadata['extracted_text'] = $result['extracted_text'];
    }
    
    $metadataJson = json_encode($metadata);
    
    if ($contentType === 'resource') {
        $updateFields = [];
        $params = [];
        $types = '';
        
        $updateFields[] = "metadata_json = ?";
        $params[] = $metadataJson;
        $types .= 's';
        
        $updateFields[] = "ai_processed = 1";
        $updateFields[] = "ai_processed_at = NOW()";
        
        if (isset($result['extracted_text'])) {
            $updateFields[] = "extracted_text = ?";
            $params[] = $result['extracted_text'];
            $types .= 's';
        }
        
        $params[] = $contentId;
        $types .= 'i';
        
        $query = "UPDATE resources SET " . implode(', ', $updateFields) . " WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $stmt->close();
    } else {
        $updateFields = [];
        $params = [];
        $types = '';
        
        $updateFields[] = "metadata_json = ?";
        $params[] = $metadataJson;
        $types .= 's';
        
        $updateFields[] = "ai_processed = 1";
        $updateFields[] = "ai_processed_at = NOW()";
        
        if (isset($result['summary'])) {
            $updateFields[] = "ai_summary = ?";
            $params[] = $result['summary'];
            $types .= 's';
        }
        
        if (isset($result['keywords']) && is_array($result['keywords'])) {
            $updateFields[] = "ai_keywords_extracted = ?";
            $params[] = implode(', ', $result['keywords']);
            $types .= 's';
        }
        
        $params[] = $contentId;
        $types .= 'i';
        
        $query = "UPDATE research_projects SET " . implode(', ', $updateFields) . " WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $stmt->close();
    }
}

/**
 * Get plugin type for process type
 */
function getPluginTypeForProcessType($processType) {
    $mapping = [
        'extract_text' => 'text_extraction',
        'summarize' => 'summarization',
        'extract_keywords' => 'keyword_extraction',
        'find_similar' => 'similarity',
        'generate_recommendations' => 'recommendation'
    ];
    
    return $mapping[$processType] ?? 'other';
}

/**
 * Get queue statistics
 */
function getQueueStatistics() {
    global $conn;
    
    $query = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) as processing,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed
              FROM ai_processing_queue";
    
    $result = mysqli_query($conn, $query);
    return mysqli_fetch_assoc($result);
}

