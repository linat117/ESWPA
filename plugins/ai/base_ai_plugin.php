<?php
/**
 * Base AI Plugin Interface
 * All AI plugins must extend this class
 */

abstract class BaseAIPlugin {
    protected $pluginName;
    protected $pluginId;
    protected $settings;
    protected $isActive;
    
    /**
     * Constructor
     */
    public function __construct($pluginId = null, $settings = []) {
        $this->pluginId = $pluginId;
        $this->settings = $settings;
        $this->isActive = true;
    }
    
    /**
     * Get plugin name
     */
    public function getName() {
        return $this->pluginName ?? get_class($this);
    }
    
    /**
     * Get plugin ID
     */
    public function getId() {
        return $this->pluginId;
    }
    
    /**
     * Check if plugin is available/configured
     */
    abstract public function isAvailable();
    
    /**
     * Process a resource
     * @param int $resourceId Resource ID
     * @param array $options Processing options
     * @return array Processing results
     */
    abstract public function processResource($resourceId, $options = []);
    
    /**
     * Process a research project
     * @param int $researchId Research ID
     * @param array $options Processing options
     * @return array Processing results
     */
    abstract public function processResearch($researchId, $options = []);
    
    /**
     * Generate summary from text
     * @param string $text Text to summarize
     * @param int $maxLength Maximum summary length
     * @return string Summary
     */
    abstract public function generateSummary($text, $maxLength = 200);
    
    /**
     * Extract keywords from text
     * @param string $text Text to analyze
     * @param int $count Number of keywords to extract
     * @return array Keywords
     */
    abstract public function extractKeywords($text, $count = 10);
    
    /**
     * Find similar content
     * @param string $contentType 'resource' or 'research'
     * @param int $contentId Content ID
     * @param int $limit Number of results
     * @return array Similar content
     */
    abstract public function findSimilar($contentType, $contentId, $limit = 10);
    
    /**
     * Extract text from file
     * @param string $filePath Path to file
     * @return string Extracted text
     */
    public function extractTextFromFile($filePath) {
        // Default implementation - can be overridden
        if (!file_exists($filePath)) {
            return '';
        }
        
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        
        switch ($extension) {
            case 'pdf':
                // PDF text extraction would go here
                // For now, return empty (requires PDF parsing library)
                return '';
            
            case 'txt':
                return file_get_contents($filePath);
            
            case 'doc':
            case 'docx':
                // Word document extraction would go here
                return '';
            
            default:
                return '';
        }
    }
    
    /**
     * Get plugin settings
     */
    public function getSettings() {
        return $this->settings;
    }
    
    /**
     * Set plugin setting
     */
    public function setSetting($key, $value) {
        $this->settings[$key] = $value;
    }
    
    /**
     * Get plugin setting
     */
    public function getSetting($key, $default = null) {
        return $this->settings[$key] ?? $default;
    }
    
    /**
     * Validate plugin configuration
     * @return array ['valid' => bool, 'errors' => []]
     */
    public function validateConfiguration() {
        return ['valid' => true, 'errors' => []];
    }
    
    /**
     * Get processing capabilities
     * @return array List of supported process types
     */
    public function getCapabilities() {
        return [
            'extract_text',
            'summarize',
            'analyze',
            'extract_keywords',
            'find_similar'
        ];
    }
}

