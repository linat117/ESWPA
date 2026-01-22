# Task: AI Integration Preparation

**Agent**: agent_dev_2  
**Priority**: Medium  
**Status**: 📋 Ready for Development  
**Estimated Time**: 6-8 hours

---

## Objective
Prepare the research and resource system for future AI integration by creating structured data formats, API endpoints, and plugin architecture.

---

## Requirements

### 1. Structured Data Formats

#### 1.1 Resource Metadata Standard

**JSON Schema for Resources:**
```json
{
  "resource_id": "int",
  "title": "string",
  "author": "string",
  "description": "string",
  "category": "string",
  "tags": ["array of strings"],
  "keywords": ["array of strings"],
  "abstract": "string",
  "publication_date": "date",
  "content_type": "enum: pdf, doc, article, etc",
  "file_size": "int",
  "page_count": "int",
  "language": "string",
  "doi": "string",
  "isbn": "string",
  "ai_metadata": {
    "extracted_text": "string",
    "summary": "string",
    "key_concepts": ["array"],
    "sentiment": "string",
    "topics": ["array"],
    "entities": ["array"]
  }
}
```

**Database Update:**
```sql
ALTER TABLE `resources` 
ADD COLUMN `metadata_json` TEXT NULL,
ADD COLUMN `ai_processed` TINYINT(1) DEFAULT 0,
ADD COLUMN `ai_processed_at` TIMESTAMP NULL,
ADD COLUMN `extracted_text` LONGTEXT NULL,
ADD INDEX `idx_ai_processed` (`ai_processed`);
```

#### 1.2 Research Metadata Standard

**JSON Schema for Research:**
```json
{
  "research_id": "int",
  "title": "string",
  "abstract": "text",
  "keywords": ["array"],
  "research_type": "enum",
  "status": "enum",
  "ai_metadata": {
    "methodology": "string",
    "findings_summary": "text",
    "recommendations": "text",
    "related_research": ["array of ids"],
    "similarity_scores": "object"
  }
}
```

---

### 2. API Endpoints Structure

#### 2.1 API Base Structure

**Create API Directory:**
```
api/
  research/
    resources.php
    research.php
    recommendations.php
  ai/
    process.php
    analyze.php
    suggest.php
```

#### 2.2 Resource API Endpoint

**File: `api/research/resources.php`**

**Endpoints:**
- `GET /api/research/resources` - List all resources with metadata
- `GET /api/research/resources/{id}` - Get specific resource with metadata
- `GET /api/research/resources/search?q={query}` - Search resources
- `GET /api/research/resources/recommendations?member_id={id}` - Get recommendations

**Response Format:**
```json
{
  "success": true,
  "data": {
    "resources": [...],
    "total": 100,
    "page": 1
  },
  "metadata": {
    "ai_enabled": true,
    "processing_time": 0.5
  }
}
```

#### 2.3 Research API Endpoint

**File: `api/research/research.php`**

**Endpoints:**
- `GET /api/research/projects` - List research projects
- `GET /api/research/projects/{id}` - Get specific research
- `POST /api/research/projects` - Create research (with AI suggestions)
- `GET /api/research/projects/{id}/similar` - Find similar research

#### 2.4 AI Processing Endpoint

**File: `api/ai/process.php`**

**Endpoints:**
- `POST /api/ai/process/resource` - Process resource for AI
- `POST /api/ai/process/research` - Process research for AI
- `GET /api/ai/process/status/{job_id}` - Check processing status

**Request Format:**
```json
{
  "resource_id": 123,
  "process_type": "extract_text|summarize|analyze",
  "options": {
    "extract_text": true,
    "generate_summary": true,
    "identify_keywords": true
  }
}
```

---

### 3. Plugin Architecture

#### 3.1 Plugin System Structure

**Directory Structure:**
```
plugins/
  ai/
    base_ai_plugin.php
    openai_plugin.php
    custom_ai_plugin.php
  processors/
    text_extractor.php
    summarizer.php
    keyword_extractor.php
```

#### 3.2 Base Plugin Interface

**File: `plugins/ai/base_ai_plugin.php`**

```php
abstract class BaseAIPlugin {
    abstract public function processResource($resource_id);
    abstract public function processResearch($research_id);
    abstract public function generateSummary($text);
    abstract public function extractKeywords($text);
    abstract public function findSimilar($resource_id);
    abstract public function isAvailable();
}
```

#### 3.3 Plugin Registry

**Database Table:**
```sql
CREATE TABLE `ai_plugins` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `plugin_name` VARCHAR(100) NOT NULL,
  `plugin_class` VARCHAR(255) NOT NULL,
  `plugin_file` VARCHAR(500) NOT NULL,
  `is_active` TINYINT(1) DEFAULT 0,
  `config` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_plugin` (`plugin_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

### 4. AI Processing Queue

#### 4.1 Processing Queue Table

```sql
CREATE TABLE `ai_processing_queue` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `resource_id` INT(11) NULL,
  `research_id` INT(11) NULL,
  `process_type` VARCHAR(100) NOT NULL,
  `status` ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
  `priority` INT(11) DEFAULT 5,
  `plugin_used` VARCHAR(100) NULL,
  `result_data` TEXT NULL,
  `error_message` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `processed_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_status` (`status`),
  INDEX `idx_resource_id` (`resource_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

#### 4.2 Queue Processor

**File: `include/ai_queue_processor.php`**

**Functions:**
- `addToQueue($resource_id, $process_type)` - Add to processing queue
- `processQueue()` - Process pending items
- `getQueueStatus()` - Get queue statistics
- `retryFailed()` - Retry failed processes

---

### 5. AI Data Storage

#### 5.1 AI Results Table

```sql
CREATE TABLE `ai_processing_results` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `resource_id` INT(11) NULL,
  `research_id` INT(11) NULL,
  `process_type` VARCHAR(100) NOT NULL,
  `plugin_used` VARCHAR(100) NULL,
  `result_data` LONGTEXT NULL,
  `confidence_score` DECIMAL(5,2) NULL,
  `processing_time` DECIMAL(10,3) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_resource_id` (`resource_id`),
  INDEX `idx_research_id` (`research_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

### 6. AI Features Preparation

#### 6.1 Text Extraction Preparation

**Database Fields:**
- `extracted_text` - Full extracted text from PDFs
- `text_hash` - Hash for duplicate detection
- `extraction_method` - Method used for extraction

#### 6.2 Summary Generation Preparation

**Fields:**
- `ai_summary` - AI-generated summary
- `summary_length` - Word count
- `summary_version` - Version number

#### 6.3 Keyword Extraction Preparation

**Fields:**
- `keywords_json` - Extracted keywords in JSON
- `keywords_count` - Number of keywords

#### 6.4 Similarity Search Preparation

**Fields:**
- `embedding_vector` - Vector for similarity search (if using embeddings)
- `similarity_indexed` - Whether indexed for search

---

### 7. Configuration System

#### 7.1 AI Settings Table

```sql
CREATE TABLE `ai_settings` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `setting_key` VARCHAR(100) NOT NULL UNIQUE,
  `setting_value` TEXT NULL,
  `setting_type` VARCHAR(50) NULL,
  `description` TEXT NULL,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Default settings
INSERT INTO `ai_settings` (`setting_key`, `setting_value`, `setting_type`, `description`) VALUES
('ai_enabled', '0', 'boolean', 'Enable AI features'),
('default_plugin', '', 'string', 'Default AI plugin to use'),
('auto_process_resources', '0', 'boolean', 'Automatically process new resources'),
('auto_process_research', '0', 'boolean', 'Automatically process new research'),
('max_processing_time', '300', 'integer', 'Maximum processing time in seconds');
```

---

### 8. Admin Panel - AI Management

#### 8.1 AI Settings Page (`admin/ai_settings.php`)

**Features:**
- Enable/disable AI features
- Select AI plugin
- Configure processing settings
- View processing statistics
- Manage processing queue

#### 8.2 AI Processing Queue (`admin/ai_queue.php`)

**Features:**
- View processing queue
- Retry failed items
- Clear queue
- Set priorities
- View processing history

---

## Implementation Steps

1. Update database schema for AI metadata
2. Create API endpoint structure
3. Implement plugin architecture
4. Create processing queue system
5. Build admin AI management pages
6. Create API documentation
7. Prepare data structures
8. Test plugin loading system

---

## Files to Create

### API:
1. `api/research/resources.php`
2. `api/research/research.php`
3. `api/ai/process.php`
4. `api/ai/analyze.php`

### Plugins:
1. `plugins/ai/base_ai_plugin.php`
2. `plugins/ai/example_plugin.php`

### Include:
1. `include/ai_queue_processor.php`
2. `include/ai_plugin_loader.php`
3. `include/api_helper.php`

### Admin:
1. `admin/ai_settings.php`
2. `admin/ai_queue.php`

### SQL:
1. `Sql/migration_ai_preparation.sql`

---

## Future AI Features (Placeholders)

1. **Intelligent Recommendations**
   - Based on reading history
   - Based on research interests
   - Collaborative filtering

2. **Auto-Summarization**
   - Summarize resources
   - Summarize research papers
   - Extract key points

3. **Smart Search**
   - Semantic search
   - Natural language queries
   - Context-aware results

4. **Content Analysis**
   - Extract entities
   - Identify topics
   - Sentiment analysis

5. **Research Assistance**
   - Suggest related research
   - Identify gaps
   - Recommend collaborators

---

## Testing Checklist

- [ ] API endpoints work correctly
- [ ] Plugin system loads plugins
- [ ] Queue system processes items
- [ ] Metadata stored correctly
- [ ] Admin settings save correctly
- [ ] API authentication works
- [ ] Error handling works
- [ ] Documentation complete

---

## Notes

- This is preparation work, actual AI integration will come later
- Focus on creating flexible architecture
- Ensure easy plugin integration
- Document all APIs thoroughly
- Make it easy for future developers to add AI features

---

**Last Updated**: December 16, 2025

