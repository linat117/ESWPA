-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Mar 10, 2026 at 08:48 AM
-- Server version: 8.3.0
-- PHP Version: 8.2.18

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";
SET FOREIGN_KEY_CHECKS = 0;


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ethiosocialworks`
--

-- --------------------------------------------------------

--
-- Table structure for table `about_team_members`
--

DROP TABLE IF EXISTS `about_team_members`;
CREATE TABLE IF NOT EXISTS `about_team_members` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `role` varchar(255) DEFAULT NULL,
  `bio` text,
  `photo` varchar(255) DEFAULT NULL,
  `sort_order` int DEFAULT '0',
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_sort_order` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `access_logs`
--

DROP TABLE IF EXISTS `access_logs`;
CREATE TABLE IF NOT EXISTS `access_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `member_id` int DEFAULT NULL,
  `resource_id` int DEFAULT NULL,
  `research_id` int DEFAULT NULL,
  `action` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `access_granted` tinyint(1) DEFAULT '1',
  `denial_reason` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `accessed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_member_id` (`member_id`),
  KEY `idx_resource_id` (`resource_id`),
  KEY `idx_research_id` (`research_id`),
  KEY `idx_action` (`action`),
  KEY `idx_accessed_at` (`accessed_at`),
  KEY `idx_access_granted` (`access_granted`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `access_logs`
--

INSERT INTO `access_logs` (`id`, `member_id`, `resource_id`, `research_id`, `action`, `access_granted`, `denial_reason`, `ip_address`, `user_agent`, `accessed_at`) VALUES
(1, 118, 2, NULL, '0', 0, '0', '127.0.0.1', 'Mozilla/5.0 (Linux; Android 11; SAMSUNG SM-G973U) AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/14.2 Chrome/87.0.4280.141 Mobile Safari/537.36', '2025-12-22 23:23:09'),
(2, 118, 1, NULL, '0', 0, '0', '127.0.0.1', 'Mozilla/5.0 (Linux; Android 11; SAMSUNG SM-G973U) AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/14.2 Chrome/87.0.4280.141 Mobile Safari/537.36', '2025-12-22 23:23:09'),
(3, 118, 2, NULL, '0', 0, '0', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '2025-12-22 23:24:11'),
(4, 118, 1, NULL, '0', 0, '0', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '2025-12-22 23:24:11'),
(5, 118, 2, NULL, '0', 0, '0', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '2025-12-22 23:24:20'),
(6, 118, 1, NULL, '0', 0, '0', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '2025-12-22 23:24:20'),
(7, 118, 2, NULL, '0', 0, '0', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '2025-12-23 16:32:26'),
(8, 118, 1, NULL, '0', 0, '0', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '2025-12-23 16:32:26'),
(9, NULL, 2, NULL, '0', 0, '0', '45.55.131.31', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '2026-01-02 22:48:30'),
(10, NULL, 1, NULL, '0', 0, '0', '45.55.131.31', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', '2026-01-02 22:48:30'),
(11, NULL, 2, NULL, '0', 0, '0', '74.7.243.209', 'Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko; compatible; GPTBot/1.3; +https://openai.com/gptbot)', '2026-01-30 04:46:31'),
(12, NULL, 1, NULL, '0', 0, '0', '74.7.243.209', 'Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko; compatible; GPTBot/1.3; +https://openai.com/gptbot)', '2026-01-30 04:46:31'),
(13, NULL, 2, NULL, '0', 0, '0', '74.7.241.48', 'Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko; compatible; GPTBot/1.3; +https://openai.com/gptbot)', '2026-02-19 09:14:34'),
(14, NULL, 1, NULL, '0', 0, '0', '74.7.241.48', 'Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko; compatible; GPTBot/1.3; +https://openai.com/gptbot)', '2026-02-19 09:14:34'),
(15, NULL, 2, NULL, '0', 0, '0', '107.172.195.237', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/116.0.0.0 Safari/537.36', '2026-02-24 01:56:58'),
(16, NULL, 1, NULL, '0', 0, '0', '107.172.195.237', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/116.0.0.0 Safari/537.36', '2026-02-24 01:56:58');

-- --------------------------------------------------------

--
-- Table structure for table `ai_plugins`
--

DROP TABLE IF EXISTS `ai_plugins`;
CREATE TABLE IF NOT EXISTS `ai_plugins` (
  `id` int NOT NULL AUTO_INCREMENT,
  `plugin_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Name of the AI plugin',
  `plugin_class` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'PHP class name for the plugin',
  `plugin_file` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Path to plugin file',
  `plugin_type` enum('text_extraction','summarization','keyword_extraction','similarity','recommendation','other') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'other',
  `is_active` tinyint(1) DEFAULT '1' COMMENT 'Whether plugin is active',
  `is_default` tinyint(1) DEFAULT '0' COMMENT 'Whether this is the default plugin for its type',
  `settings_json` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'Plugin-specific settings in JSON format',
  `api_key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'API key for the plugin (encrypted)',
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'Plugin description',
  `version` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Plugin version',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_plugin_name` (`plugin_name`),
  KEY `idx_plugin_type` (`plugin_type`),
  KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AI Plugin Registry';

-- --------------------------------------------------------

--
-- Table structure for table `ai_processing_queue`
--

DROP TABLE IF EXISTS `ai_processing_queue`;
CREATE TABLE IF NOT EXISTS `ai_processing_queue` (
  `id` int NOT NULL AUTO_INCREMENT,
  `content_type` enum('resource','research') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Type of content to process',
  `content_id` int NOT NULL COMMENT 'ID of the resource or research project',
  `process_type` enum('extract_text','summarize','analyze','extract_keywords','find_similar','generate_recommendations') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Type of processing to perform',
  `plugin_id` int DEFAULT NULL COMMENT 'Specific plugin to use (NULL = use default)',
  `priority` int DEFAULT '5' COMMENT 'Processing priority (1=highest, 10=lowest)',
  `status` enum('pending','processing','completed','failed','cancelled') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'pending' COMMENT 'Processing status',
  `attempts` int DEFAULT '0' COMMENT 'Number of processing attempts',
  `max_attempts` int DEFAULT '3' COMMENT 'Maximum processing attempts',
  `error_message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'Error message if processing failed',
  `result_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'Processing results in JSON format',
  `started_at` timestamp NULL DEFAULT NULL COMMENT 'When processing started',
  `completed_at` timestamp NULL DEFAULT NULL COMMENT 'When processing completed',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_content` (`content_type`,`content_id`),
  KEY `idx_status` (`status`),
  KEY `idx_priority` (`priority`),
  KEY `idx_created_at` (`created_at`),
  KEY `plugin_id` (`plugin_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AI Processing Queue';

-- --------------------------------------------------------

--
-- Table structure for table `ai_processing_results`
--

DROP TABLE IF EXISTS `ai_processing_results`;
CREATE TABLE IF NOT EXISTS `ai_processing_results` (
  `id` int NOT NULL AUTO_INCREMENT,
  `queue_id` int NOT NULL COMMENT 'Reference to processing queue item',
  `content_type` enum('resource','research') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `content_id` int NOT NULL,
  `process_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Type of processing performed',
  `plugin_id` int DEFAULT NULL COMMENT 'Plugin used for processing',
  `result_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Processing results in JSON format',
  `processing_time` decimal(10,3) DEFAULT NULL COMMENT 'Processing time in seconds',
  `tokens_used` int DEFAULT NULL COMMENT 'Number of tokens used (for API-based plugins)',
  `cost` decimal(10,4) DEFAULT NULL COMMENT 'Processing cost (if applicable)',
  `metadata` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'Additional metadata in JSON format',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_queue_id` (`queue_id`),
  KEY `idx_content` (`content_type`,`content_id`),
  KEY `idx_process_type` (`process_type`),
  KEY `idx_created_at` (`created_at`),
  KEY `plugin_id` (`plugin_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AI Processing Results Storage';

-- --------------------------------------------------------

--
-- Table structure for table `ai_settings`
--

DROP TABLE IF EXISTS `ai_settings`;
CREATE TABLE IF NOT EXISTS `ai_settings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Setting key/name',
  `setting_value` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'Setting value (can be JSON)',
  `setting_type` enum('string','integer','boolean','json','array') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'string' COMMENT 'Type of setting value',
  `category` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'general' COMMENT 'Setting category',
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'Setting description',
  `is_public` tinyint(1) DEFAULT '0' COMMENT 'Whether setting is publicly accessible via API',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_setting_key` (`setting_key`),
  KEY `idx_category` (`category`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AI System Settings';

--
-- Dumping data for table `ai_settings`
--

INSERT INTO `ai_settings` (`id`, `setting_key`, `setting_value`, `setting_type`, `category`, `description`, `is_public`, `created_at`, `updated_at`) VALUES
(1, 'ai_enabled', '0', 'boolean', 'general', 'Enable/disable AI features globally', 1, '2025-12-22 23:15:50', '2025-12-22 23:15:50'),
(2, 'ai_auto_process', '0', 'boolean', 'processing', 'Automatically process new resources/research', 0, '2025-12-22 23:15:50', '2025-12-22 23:15:50'),
(3, 'ai_processing_limit', '100', 'integer', 'processing', 'Maximum number of items to process per batch', 0, '2025-12-22 23:15:50', '2025-12-22 23:15:50'),
(4, 'ai_default_text_extractor', '', 'string', 'plugins', 'Default plugin for text extraction', 0, '2025-12-22 23:15:50', '2025-12-22 23:15:50'),
(5, 'ai_default_summarizer', '', 'string', 'plugins', 'Default plugin for summarization', 0, '2025-12-22 23:15:50', '2025-12-22 23:15:50'),
(6, 'ai_default_keyword_extractor', '', 'string', 'plugins', 'Default plugin for keyword extraction', 0, '2025-12-22 23:15:50', '2025-12-22 23:15:50'),
(7, 'ai_api_rate_limit', '60', 'integer', 'api', 'API requests per minute limit', 0, '2025-12-22 23:15:50', '2025-12-22 23:15:50'),
(8, 'ai_cache_enabled', '1', 'boolean', 'performance', 'Enable caching of AI results', 0, '2025-12-22 23:15:50', '2025-12-22 23:15:50'),
(9, 'ai_cache_duration', '86400', 'integer', 'performance', 'Cache duration in seconds (default: 24 hours)', 0, '2025-12-22 23:15:50', '2025-12-22 23:15:50');

-- --------------------------------------------------------

--
-- Table structure for table `ai_similarity_index`
--

DROP TABLE IF EXISTS `ai_similarity_index`;
CREATE TABLE IF NOT EXISTS `ai_similarity_index` (
  `id` int NOT NULL AUTO_INCREMENT,
  `content_type` enum('resource','research') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `content_id` int NOT NULL,
  `similar_content_type` enum('resource','research') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `similar_content_id` int NOT NULL,
  `similarity_score` decimal(5,4) NOT NULL COMMENT 'Similarity score (0.0000 to 1.0000)',
  `similarity_method` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Method used to calculate similarity',
  `metadata` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'Additional metadata in JSON format',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_similarity` (`content_type`,`content_id`,`similar_content_type`,`similar_content_id`),
  KEY `idx_content` (`content_type`,`content_id`),
  KEY `idx_similar_content` (`similar_content_type`,`similar_content_id`),
  KEY `idx_similarity_score` (`similarity_score`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AI Similarity Index for Content Recommendations';

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

DROP TABLE IF EXISTS `audit_logs`;
CREATE TABLE IF NOT EXISTS `audit_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `user_type` enum('admin','member') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'admin',
  `action` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `table_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `record_id` int DEFAULT NULL,
  `old_value` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `new_value` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_user_type` (`user_type`),
  KEY `idx_action` (`action`),
  KEY `idx_table_name` (`table_name`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `backups`
--

DROP TABLE IF EXISTS `backups`;
CREATE TABLE IF NOT EXISTS `backups` (
  `id` int NOT NULL AUTO_INCREMENT,
  `backup_type` enum('database','files','full') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'database',
  `file_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_size` bigint DEFAULT NULL,
  `status` enum('pending','completed','failed') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_backup_type` (`backup_type`),
  KEY `idx_status` (`status`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `badge_permissions`
--

DROP TABLE IF EXISTS `badge_permissions`;
CREATE TABLE IF NOT EXISTS `badge_permissions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `badge_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `resource_access` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `research_access` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `special_features` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `idx_badge_name` (`badge_name`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `badge_permissions`
--

INSERT INTO `badge_permissions` (`id`, `badge_name`, `resource_access`, `research_access`, `special_features`, `description`) VALUES
(1, 'Research Leader', 'all', 'all', NULL, 'Full access to all resources and research features'),
(2, 'Resource Expert', 'all', 'view', NULL, 'Access to all resources'),
(3, 'Research Publisher', 'premium', 'all', NULL, 'Full research access and premium resources'),
(4, 'Community Champion', 'basic', 'collaborate', NULL, 'Basic resources and research collaboration'),
(5, 'Research Leader', 'all', 'all', NULL, 'Full access to all resources and research features'),
(6, 'Resource Expert', 'all', 'view', NULL, 'Access to all resources'),
(7, 'Research Publisher', 'premium', 'all', NULL, 'Full research access and premium resources'),
(8, 'Community Champion', 'basic', 'collaborate', NULL, 'Basic resources and research collaboration'),
(9, 'Research Leader', 'all', 'all', NULL, 'Full access to all resources and research features'),
(10, 'Resource Expert', 'all', 'view', NULL, 'Access to all resources'),
(11, 'Research Publisher', 'premium', 'all', NULL, 'Full research access and premium resources'),
(12, 'Community Champion', 'basic', 'collaborate', NULL, 'Basic resources and research collaboration');

-- --------------------------------------------------------

--
-- Table structure for table `bibliography_collections`
--

DROP TABLE IF EXISTS `bibliography_collections`;
CREATE TABLE IF NOT EXISTS `bibliography_collections` (
  `id` int NOT NULL AUTO_INCREMENT,
  `member_id` int NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `is_public` tinyint(1) DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_member_id` (`member_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `bibliography_collections`
--

INSERT INTO `bibliography_collections` (`id`, `member_id`, `name`, `description`, `is_public`, `created_at`) VALUES
(1, 118, 'Check', 'kk', 1, '2025-12-22 22:46:34');

-- --------------------------------------------------------

--
-- Table structure for table `bibliography_items`
--

DROP TABLE IF EXISTS `bibliography_items`;
CREATE TABLE IF NOT EXISTS `bibliography_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `collection_id` int NOT NULL,
  `resource_id` int DEFAULT NULL,
  `research_id` int DEFAULT NULL,
  `citation_text` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `added_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_collection_id` (`collection_id`),
  KEY `idx_resource_id` (`resource_id`),
  KEY `idx_research_id` (`research_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `bibliography_items`
--

INSERT INTO `bibliography_items` (`id`, `collection_id`, `resource_id`, `research_id`, `citation_text`, `notes`, `added_at`) VALUES
(1, 1, 1, NULL, 'Daglas, D. 2025. Ethio Social Works. Lebawi Net Trading.', '', '2025-12-22 22:46:55'),
(2, 1, 1, NULL, 'Bibliographic Entry Summary\r\n\r\nAuthor / Name: Daglas\r\n\r\nAffiliation / Work: Ethio Social Works\r\n\r\nPublisher / Organization: Lebawi Net Trading\r\n\r\nYear: 2025\r\n\r\nOverall Meaning:\r\nA citation indicating a work by Daglas associated with Ethio Social Works, published or issued by Lebawi Net Trading in 2025.', '', '2025-12-22 22:47:43');

-- --------------------------------------------------------

--
-- Table structure for table `changelogs`
--

DROP TABLE IF EXISTS `changelogs`;
CREATE TABLE IF NOT EXISTS `changelogs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `version` varchar(50) NOT NULL,
  `change_date` date NOT NULL,
  `type` varchar(100) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `company_info`
--

DROP TABLE IF EXISTS `company_info`;
CREATE TABLE IF NOT EXISTS `company_info` (
  `id` int NOT NULL AUTO_INCREMENT,
  `company_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `company_logo` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `company_signature` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `phone` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `website` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `terms_conditions` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `company_info`
--

INSERT INTO `company_info` (`id`, `company_name`, `company_logo`, `company_signature`, `address`, `phone`, `email`, `website`, `terms_conditions`, `created_at`, `updated_at`) VALUES
(1, 'Ethiopian Social Workers Professional Association', NULL, NULL, 'Addis Ababa, Ethiopia', '+251-XXX-XXX-XXXX', 'info@eswpa.org', 'www.eswpa.org', NULL, '2025-12-16 18:51:35', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `email_automation_logs`
--

DROP TABLE IF EXISTS `email_automation_logs`;
CREATE TABLE IF NOT EXISTS `email_automation_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `content_type` enum('news','blog','report','event','resource') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `content_id` int NOT NULL,
  `content_title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `recipients_count` int NOT NULL DEFAULT '0',
  `sent_count` int NOT NULL DEFAULT '0',
  `failed_count` int NOT NULL DEFAULT '0',
  `sent_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `sent_by` int DEFAULT NULL,
  `status` enum('success','failed','partial') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `error_message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `idx_content_type` (`content_type`),
  KEY `idx_content_id` (`content_id`),
  KEY `idx_sent_at` (`sent_at`),
  KEY `idx_status` (`status`),
  KEY `idx_sent_by` (`sent_by`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `email_automation_logs`
--

INSERT INTO `email_automation_logs` (`id`, `content_type`, `content_id`, `content_title`, `recipients_count`, `sent_count`, `failed_count`, `sent_at`, `sent_by`, `status`, `error_message`) VALUES
(1, 'news', 0, 'Test News - 2025-12-22 22:58:18', 1, 1, 0, '2025-12-22 21:58:21', 2, 'success', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `email_automation_settings`
--

DROP TABLE IF EXISTS `email_automation_settings`;
CREATE TABLE IF NOT EXISTS `email_automation_settings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `content_type` enum('news','blog','report','event','resource') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT '0',
  `send_to_subscribers` tinyint(1) NOT NULL DEFAULT '1',
  `send_to_members` tinyint(1) NOT NULL DEFAULT '1',
  `send_to_custom` tinyint(1) NOT NULL DEFAULT '0',
  `custom_emails` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `template_id` int DEFAULT NULL,
  `send_immediately` tinyint(1) NOT NULL DEFAULT '1',
  `send_only_published` tinyint(1) NOT NULL DEFAULT '1',
  `include_images` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_content_type` (`content_type`),
  KEY `idx_enabled` (`enabled`),
  KEY `fk_template_id` (`template_id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `email_automation_settings`
--

INSERT INTO `email_automation_settings` (`id`, `content_type`, `enabled`, `send_to_subscribers`, `send_to_members`, `send_to_custom`, `custom_emails`, `template_id`, `send_immediately`, `send_only_published`, `include_images`, `created_at`, `updated_at`) VALUES
(1, 'news', 1, 1, 1, 0, '0', 1, 1, 1, 1, '2025-12-22 21:22:15', '2025-12-22 21:36:25'),
(2, 'blog', 1, 1, 1, 0, '0', 2, 1, 1, 1, '2025-12-22 21:22:15', '2025-12-22 21:37:44'),
(3, 'report', 1, 1, 1, 0, '0', NULL, 1, 1, 1, '2025-12-22 21:22:15', '2025-12-22 21:37:52'),
(4, 'event', 1, 1, 1, 0, '0', NULL, 1, 0, 1, '2025-12-22 21:22:15', '2025-12-22 21:38:00'),
(5, 'resource', 1, 1, 1, 0, '0', 5, 1, 1, 1, '2025-12-22 21:22:15', '2025-12-22 21:38:15');

-- --------------------------------------------------------

--
-- Table structure for table `email_subscribers`
--

DROP TABLE IF EXISTS `email_subscribers`;
CREATE TABLE IF NOT EXISTS `email_subscribers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `email` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('active','unsubscribed','bounced') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `subscribed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `unsubscribed_at` timestamp NULL DEFAULT NULL,
  `source` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'popup',
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `unsubscribe_token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `unsubscribe_token` (`unsubscribe_token`),
  KEY `idx_email` (`email`),
  KEY `idx_status` (`status`),
  KEY `idx_unsubscribe_token` (`unsubscribe_token`),
  KEY `idx_subscribed_at` (`subscribed_at`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `email_subscribers`
--

INSERT INTO `email_subscribers` (`id`, `email`, `name`, `status`, `subscribed_at`, `unsubscribed_at`, `source`, `ip_address`, `user_agent`, `unsubscribe_token`) VALUES
(1, 'salem@lebawi.net', NULL, 'active', '2025-12-22 20:29:13', NULL, 'popup', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '4220cc3b8116a7affa85ef00f28031c900e9e0bb28582ed212bb352e568958da_cb61eee2825d4afb9105884d7138c5d8'),
(2, 'info@lebawi.net', 'DAGLAS', 'active', '2025-12-22 20:38:44', NULL, 'popup', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '5679d089f312c99ece3f8bc61fe3f2e3ec45a2bb6f61b0b3e62383b6bc9904a0_46975c0dd55c49bb1c3fa4f7f8f4788c'),
(3, 'daglasmohamed@gmail.com', NULL, 'active', '2025-12-22 20:43:13', NULL, 'popup', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '72138bbc1ef59c165630ce05fdbc35dba0b156bae365184e07b26c8846850105_ef5b79c42d50b2bc01095ddcf355dec7');

-- --------------------------------------------------------

--
-- Table structure for table `email_templates`
--

DROP TABLE IF EXISTS `email_templates`;
CREATE TABLE IF NOT EXISTS `email_templates` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `subject` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `body` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `content_type` enum('news','blog','report','event','resource','general') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_content_type` (`content_type`),
  KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `email_templates`
--

INSERT INTO `email_templates` (`id`, `name`, `subject`, `body`, `content_type`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'News Template', 'New News: {TITLE}', '<!DOCTYPE html><html><head><meta charset=\"UTF-8\"><meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\"></head><body style=\"font-family: Arial, sans-serif; line-height: 1.6; color: #333;\"><div style=\"max-width: 600px; margin: 0 auto; padding: 20px;\"><h1 style=\"color: #667eea;\">{TITLE}</h1><p style=\"color: #666; font-size: 14px;\">Published: {DATE} | By: {AUTHOR}</p><div style=\"margin: 20px 0;\">{IMAGE}{CONTENT}</div><a href=\"{LINK}\" style=\"display: inline-block; background: #667eea; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;\">Read More</a><hr style=\"margin: 30px 0; border: none; border-top: 1px solid #eee;\"><p style=\"font-size: 12px; color: #999; text-align: center;\">You received this email because you subscribed to our newsletter.<br><a href=\"{UNSUBSCRIBE_LINK}\">Unsubscribe</a></p></div></body></html>', 'news', 1, '2025-12-22 21:34:24', NULL),
(2, 'Blog Template', 'New Blog Post: {TITLE}', '<!DOCTYPE html><html><head><meta charset=\"UTF-8\"><meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\"></head><body style=\"font-family: Arial, sans-serif; line-height: 1.6; color: #333;\"><div style=\"max-width: 600px; margin: 0 auto; padding: 20px;\"><h1 style=\"color: #667eea;\">{TITLE}</h1><p style=\"color: #666; font-size: 14px;\">Published: {DATE} | By: {AUTHOR}</p><div style=\"margin: 20px 0;\">{IMAGE}{CONTENT}</div><a href=\"{LINK}\" style=\"display: inline-block; background: #667eea; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;\">Read More</a><hr style=\"margin: 30px 0; border: none; border-top: 1px solid #eee;\"><p style=\"font-size: 12px; color: #999; text-align: center;\">You received this email because you subscribed to our newsletter.<br><a href=\"{UNSUBSCRIBE_LINK}\">Unsubscribe</a></p></div></body></html>', 'blog', 1, '2025-12-22 21:34:24', NULL),
(3, 'Report Template', 'New Report: {TITLE}', '<!DOCTYPE html><html><head><meta charset=\"UTF-8\"><meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\"></head><body style=\"font-family: Arial, sans-serif; line-height: 1.6; color: #333;\"><div style=\"max-width: 600px; margin: 0 auto; padding: 20px;\"><h1 style=\"color: #667eea;\">{TITLE}</h1><p style=\"color: #666; font-size: 14px;\">Published: {DATE} | By: {AUTHOR}</p><div style=\"margin: 20px 0;\">{IMAGE}{CONTENT}</div><a href=\"{LINK}\" style=\"display: inline-block; background: #667eea; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;\">Read More</a><hr style=\"margin: 30px 0; border: none; border-top: 1px solid #eee;\"><p style=\"font-size: 12px; color: #999; text-align: center;\">You received this email because you subscribed to our newsletter.<br><a href=\"{UNSUBSCRIBE_LINK}\">Unsubscribe</a></p></div></body></html>', 'report', 1, '2025-12-22 21:34:24', NULL),
(4, 'Event Template', 'New Event: {TITLE}', '<!DOCTYPE html><html><head><meta charset=\"UTF-8\"><meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\"></head><body style=\"font-family: Arial, sans-serif; line-height: 1.6; color: #333;\"><div style=\"max-width: 600px; margin: 0 auto; padding: 20px;\"><h1 style=\"color: #667eea;\">{TITLE}</h1><p style=\"color: #666; font-size: 14px;\">Event Date: {DATE}</p><div style=\"margin: 20px 0;\">{IMAGE}{CONTENT}</div><a href=\"{LINK}\" style=\"display: inline-block; background: #667eea; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;\">View Details</a><hr style=\"margin: 30px 0; border: none; border-top: 1px solid #eee;\"><p style=\"font-size: 12px; color: #999; text-align: center;\">You received this email because you subscribed to our newsletter.<br><a href=\"{UNSUBSCRIBE_LINK}\">Unsubscribe</a></p></div></body></html>', 'event', 1, '2025-12-22 21:34:24', NULL),
(5, 'Resource Template', 'New Resource Available: {TITLE}', '<!DOCTYPE html><html><head><meta charset=\"UTF-8\"><meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\"></head><body style=\"font-family: Arial, sans-serif; line-height: 1.6; color: #333;\"><div style=\"max-width: 600px; margin: 0 auto; padding: 20px;\"><h1 style=\"color: #667eea;\">{TITLE}</h1><p style=\"color: #666; font-size: 14px;\">Published: {DATE} | By: {AUTHOR}</p><div style=\"margin: 20px 0;\">{CONTENT}</div><a href=\"{LINK}\" style=\"display: inline-block; background: #667eea; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;\">Download</a><hr style=\"margin: 30px 0; border: none; border-top: 1px solid #eee;\"><p style=\"font-size: 12px; color: #999; text-align: center;\">You received this email because you subscribed to our newsletter.<br><a href=\"{UNSUBSCRIBE_LINK}\">Unsubscribe</a></p></div></body></html>', 'resource', 1, '2025-12-22 21:34:24', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

DROP TABLE IF EXISTS `events`;
CREATE TABLE IF NOT EXISTS `events` (
  `id` int NOT NULL AUTO_INCREMENT,
  `event_date` date NOT NULL,
  `event_header` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `event_description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `event_images` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`id`, `event_date`, `event_header`, `event_description`, `event_images`, `created_at`) VALUES
(12, '2023-04-04', 'Members visited the Selihom Rehabilitation Center.', 'Members visited the Selihom Rehabilitation Center for psychiatry patients and provided donations of sanitary materials.', '[\"..\\/..\\/uploads\\/1739366799_IMG_20250212_162455_839.jpg\",\"..\\/..\\/uploads\\/1739366799_IMG_20250212_162455_922.jpg\"]', '2025-02-12 13:26:39'),
(13, '2024-02-24', 'The third general Assembly.', 'The third general Assembly held at Amhara Bank Building on February 24/ 2024.', '[\"..\\/..\\/uploads\\/1739366950_IMG_20250212_162818_572.jpg\"]', '2025-02-12 13:29:10'),
(14, '2024-11-06', 'Training of trainers organized by PHE', 'Training of trainers organized by PHE consuntrum on the nexus between climate change and gender to enhance the resilience of vulnerable communities', '[\"..\\/..\\/uploads\\/1739368354_IMG_20250212_164943_841.jpg\",\"..\\/..\\/uploads\\/1739368354_IMG_20250212_164943_922.jpg\"]', '2025-02-12 13:52:34'),
(15, '2022-03-25', 'The 1st general assembly', 'the 1st general assembly after reorganization of the association', '[\"..\\/..\\/uploads\\/1739368665_IMG_20250212_165555_177.jpg\",\"..\\/..\\/uploads\\/1739368665_IMG_20250212_165556_052.jpg\"]', '2025-02-12 13:57:45'),
(16, '2024-03-13', 'Social work act development opening program', 'Social work act development opening program', '[\"..\\/..\\/uploads\\/1739368910_IMG_20250212_170109_634.jpg\"]', '2025-02-12 14:01:50'),
(17, '2024-12-24', 'Mental health and psychosocial manual development workshops.', 'Mental health and psychosocial manual development workshops.', '[\"..\\/..\\/uploads\\/1739369206_IMG_20250212_170407_279.jpg\",\"..\\/..\\/uploads\\/1739369206_IMG_20250212_170407_521.jpg\"]', '2025-02-12 14:06:46'),
(18, '2024-08-08', '\"Psychosocial service as a response\"', 'As a member of the Ethiopian medical team ( EMT),  we give psychosocial service as a response for land slide at gofa zone.', '[\"..\\/..\\/uploads\\/1739369316_IMG_20250212_170719_538.jpg\",\"..\\/..\\/uploads\\/1739369316_IMG_20250212_170718_306.jpg\"]', '2025-02-12 14:08:36'),
(19, '2018-12-28', 'Training and experience sharing.', 'Training and experience sharing with Hospital social workers from government hospitals.', '[\"..\\/..\\/uploads\\/1739369413_IMG_20250212_170906_860.jpg\",\"..\\/..\\/uploads\\/1739369413_IMG_20250212_170906_181.jpg\",\"..\\/..\\/uploads\\/1739369413_IMG_20250212_170905_297.jpg\"]', '2025-02-12 14:10:13'),
(20, '2023-03-26', 'The second general Assembly ', 'The second general Assembly after the reorganization of the association at Adore Addis Hotel.', '[\"..\\/..\\/uploads\\/1739369517_IMG_20250212_171041_676.jpg\",\"..\\/..\\/uploads\\/1739369517_IMG_20250212_171041_111.jpg\",\"..\\/..\\/uploads\\/1739369517_IMG_20250212_171039_411.jpg\"]', '2025-02-12 14:11:57'),
(21, '2021-02-13', 'The discussion at Zewditu Memorial Hospital ', 'The discussion at Zewditu Memorial Hospital centered on the future direction of the association after receiving our certificate. We deliberated on strategies to enhance our services and strengthen our community impact.', '[\"..\\/..\\/uploads\\/1739540657_IMG_20250214_164208_175.jpg\",\"..\\/..\\/uploads\\/1739540657_IMG_20250214_164206_870.jpg\"]', '2025-02-14 13:44:17'),
(22, '2018-02-24', 'The first meeting', 'The first meeting held at zewditu hospital after the establishment of ESWPA', '[\"..\\/..\\/uploads\\/1739540730_IMG_20250214_164438_200.jpg\",\"..\\/..\\/uploads\\/1739540730_IMG_20250214_164438_044.jpg\"]', '2025-02-14 13:45:30'),
(23, '2021-08-10', 'Received recognition from the Ministry of Health', 'We received recognition from the Ministry of Health for our active participation in the COVID-19 response efforts. This acknowledgment highlights our commitment to public health and the essential role we played in supporting the community during the pandemic.', '[\"..\\/..\\/uploads\\/1739540987_IMG_20250214_164543_295.jpg\",\"..\\/..\\/uploads\\/1739540987_IMG_20250214_164543_975.jpg\"]', '2025-02-14 13:49:47'),
(24, '2020-02-18', 'World Mental Health Day', 'On World Mental Health Day the Ethiopian Social Work Professional Association** united to raise awareness about mental health.', '[\"..\\/..\\/uploads\\/1739541157_IMG_20250214_165019_517.jpg\",\"..\\/..\\/uploads\\/1739541157_IMG_20250214_165018_065.jpg\",\"..\\/..\\/uploads\\/1739541157_IMG_20250214_165016_512.jpg\"]', '2025-02-14 13:52:37'),
(26, '2025-03-29', 'ESWPA 4th general Assembly', 'The general assembly was held at bole Noah plaza 7th floor, Potential members were participated on the meeting. Performance report  and  next year plan was presented. Fruitful discussions was made on how to strengthen the association and way forward  given by the participants.', '[\"..\\/..\\/uploads\\/1743269443_photo_2025-03-29_20-19-51.jpg\",\"..\\/..\\/uploads\\/1743269443_photo_2025-03-29_20-19-09.jpg\",\"..\\/..\\/uploads\\/1743269443_photo_2025-03-29_20-19-42.jpg\"]', '2025-03-29 17:30:43');

-- --------------------------------------------------------

--
-- Table structure for table `id_card_verification`
--

DROP TABLE IF EXISTS `id_card_verification`;
CREATE TABLE IF NOT EXISTS `id_card_verification` (
  `id` int NOT NULL AUTO_INCREMENT,
  `membership_id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `verification_code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `scanned_at` timestamp NULL DEFAULT NULL,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `verification_code` (`verification_code`),
  KEY `idx_membership_id` (`membership_id`),
  KEY `idx_verification_code` (`verification_code`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `id_card_verification`
--

INSERT INTO `id_card_verification` (`id`, `membership_id`, `verification_code`, `scanned_at`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 'ESWPA-2025-00001', 'f001c115012231526654202e4e0ed0a0', NULL, NULL, NULL, '2025-12-16 19:20:27');

-- --------------------------------------------------------

--
-- Table structure for table `membership_packages`
--

DROP TABLE IF EXISTS `membership_packages`;
CREATE TABLE IF NOT EXISTS `membership_packages` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `price` decimal(10,2) DEFAULT NULL,
  `duration_months` int DEFAULT NULL,
  `features` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `idx_slug` (`slug`),
  KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `membership_packages`
--

INSERT INTO `membership_packages` (`id`, `name`, `slug`, `description`, `price`, `duration_months`, `features`, `is_active`, `created_at`) VALUES
(1, 'Basic', 'basic', 'Basic membership with limited access to resources', 500.00, 12, 'Access to basic resources, View research projects', 1, '2025-12-22 21:45:23'),
(2, 'Premium', 'premium', 'Premium membership with full resource access', 1000.00, 12, 'Access to all resources, View and create research, Collaboration features', 1, '2025-12-22 21:45:23'),
(3, 'Professional', 'professional', 'Professional membership for researchers', 2000.00, 12, 'Full access to all resources, Unlimited research projects, Advanced collaboration, Priority support', 1, '2025-12-22 21:45:23'),
(4, 'Lifetime', 'lifetime', 'Lifetime membership with all features', 5000.00, NULL, 'All features, Lifetime access, Unlimited everything, VIP support', 1, '2025-12-22 21:45:23');

-- --------------------------------------------------------

--
-- Table structure for table `member_access`
--

DROP TABLE IF EXISTS `member_access`;
CREATE TABLE IF NOT EXISTS `member_access` (
  `id` int NOT NULL AUTO_INCREMENT,
  `member_id` int NOT NULL,
  `email` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `membership_id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('active','expired','suspended') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `membership_id` (`membership_id`),
  KEY `idx_member_id` (`member_id`),
  KEY `idx_email` (`email`),
  KEY `idx_membership_id` (`membership_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `member_access`
--

INSERT INTO `member_access` (`id`, `member_id`, `email`, `password`, `membership_id`, `status`, `last_login`, `created_at`, `updated_at`) VALUES
(1, 118, 'daglasmohamed@gmail.com', '$2y$10$i1Iae7y6HOQmSl2htIfdSOAm0IqKcZUvzNGnc8ztrX5vH2vb14y/S', 'ESWPA-2025-00001', 'active', '2025-12-22 22:05:15', '2025-12-16 18:59:31', '2025-12-22 22:05:15');

-- --------------------------------------------------------

--
-- Table structure for table `member_activities`
--

DROP TABLE IF EXISTS `member_activities`;
CREATE TABLE IF NOT EXISTS `member_activities` (
  `id` int NOT NULL AUTO_INCREMENT,
  `member_id` int NOT NULL,
  `activity_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `activity_description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `related_id` int DEFAULT NULL,
  `related_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `user_agent` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_member_id` (`member_id`),
  KEY `idx_activity_type` (`activity_type`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `member_activities`
--

INSERT INTO `member_activities` (`id`, `member_id`, `activity_type`, `activity_description`, `related_id`, `related_type`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 118, 'badge_earned', 'Earned badge: Research Leader - Led 1 research project(s)', NULL, NULL, NULL, NULL, '2025-12-24 21:09:38'),
(2, 118, 'badge_earned', 'Earned badge: Research Participant - Contributed to 2 research project(s)', NULL, NULL, NULL, NULL, '2025-12-24 21:09:38'),
(3, 118, 'badge_earned', 'Earned badge: Research Collaborator - Contributed to 4 research projects', NULL, NULL, NULL, NULL, '2025-12-25 02:06:43');

-- --------------------------------------------------------

--
-- Table structure for table `member_admin_notes`
--

DROP TABLE IF EXISTS `member_admin_notes`;
CREATE TABLE IF NOT EXISTS `member_admin_notes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `member_id` int NOT NULL,
  `admin_id` int NOT NULL,
  `note` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `is_important` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_member_id` (`member_id`),
  KEY `idx_admin_id` (`admin_id`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `member_admin_notes`
--

INSERT INTO `member_admin_notes` (`id`, `member_id`, `admin_id`, `note`, `is_important`, `created_at`, `updated_at`) VALUES
(1, 118, 1, 'Hey', 1, '2025-12-24 19:27:53', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `member_badges`
--

DROP TABLE IF EXISTS `member_badges`;
CREATE TABLE IF NOT EXISTS `member_badges` (
  `id` int NOT NULL AUTO_INCREMENT,
  `member_id` int NOT NULL,
  `badge_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `badge_description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `assigned_by` int DEFAULT NULL,
  `assigned_at` timestamp NULL DEFAULT NULL,
  `earned_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `is_active` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `idx_member_id` (`member_id`),
  KEY `idx_badge_name` (`badge_name`),
  KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `member_citations`
--

DROP TABLE IF EXISTS `member_citations`;
CREATE TABLE IF NOT EXISTS `member_citations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `member_id` int NOT NULL,
  `resource_id` int DEFAULT NULL,
  `research_id` int DEFAULT NULL,
  `citation_format` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `citation_text` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_member_id` (`member_id`),
  KEY `idx_resource_id` (`resource_id`),
  KEY `idx_research_id` (`research_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `member_citations`
--

INSERT INTO `member_citations` (`id`, `member_id`, `resource_id`, `research_id`, `citation_format`, `citation_text`, `created_at`) VALUES
(5, 118, 1, NULL, 'mla', 'Daglas. \"Ethio Social Works.\" Lebawi Net Trading, 2025. uploads/resources/1765913424_6941b35072d14_CI for 25111702-2.pdf', '2025-12-22 22:49:24'),
(6, 118, 1, NULL, 'apa', 'Daglas, D. 2025. Ethio Social Works. Lebawi Net Trading. uploads/resources/1765913424_6941b35072d14_CI for 25111702-2.pdf', '2025-12-22 22:52:09');

-- --------------------------------------------------------

--
-- Table structure for table `member_permissions`
--

DROP TABLE IF EXISTS `member_permissions`;
CREATE TABLE IF NOT EXISTS `member_permissions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `member_id` int NOT NULL,
  `permission_id` int NOT NULL,
  `granted_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `granted_by` int DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `notes` text,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_member_permission` (`member_id`,`permission_id`),
  KEY `idx_member_id` (`member_id`),
  KEY `idx_permission_id` (`permission_id`),
  KEY `idx_granted_by` (`granted_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `news_media`
--

DROP TABLE IF EXISTS `news_media`;
CREATE TABLE IF NOT EXISTS `news_media` (
  `id` int NOT NULL AUTO_INCREMENT,
  `type` enum('report','news','blog') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'news',
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `images` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `author` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `published_date` date DEFAULT NULL,
  `status` enum('draft','published','archived') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_type` (`type`),
  KEY `idx_status` (`status`),
  KEY `idx_published_date` (`published_date`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `news_media`
--

INSERT INTO `news_media` (`id`, `type`, `title`, `content`, `images`, `author`, `published_date`, `status`, `created_at`, `updated_at`) VALUES
(1, 'news', 'Ethio Social Works', 'BYDEntrepreneurship thrives on simplicity. A vision sparks, fueled by passion. Here, we honor the genesis—the moment your idea takes root, setting the stage for a transformative journey into the world ...', '[\"uploads\\/news\\/1765915939_6941bd23d8098_service_section_generator.png\"]', 'Daglas', '2025-12-16', 'published', '2025-12-16 20:12:19', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `member_id` int NOT NULL,
  `type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `related_id` int DEFAULT NULL,
  `related_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `read_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_member_id` (`member_id`),
  KEY `idx_is_read` (`is_read`),
  KEY `idx_type` (`type`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `member_id`, `type`, `title`, `message`, `is_read`, `related_id`, `related_type`, `created_at`, `read_at`) VALUES
(1, 118, 'badge_earned', 'New Badge Earned!', 'Congratulations! You have earned the \"Research Leader\" badge. Led 1 research project(s)', 1, NULL, NULL, '2025-12-24 21:09:38', '2025-12-24 21:29:10'),
(2, 118, 'badge_earned', 'New Badge Earned!', 'Congratulations! You have earned the \"Research Participant\" badge. Contributed to 2 research project(s)', 1, NULL, NULL, '2025-12-24 21:09:38', '2025-12-24 21:29:10'),
(3, 118, 'badge_earned', 'New Badge Earned!', 'Congratulations! You have earned the \"Research Collaborator\" badge. Contributed to 4 research projects', 0, NULL, NULL, '2025-12-25 02:06:43', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `notification_templates`
--

DROP TABLE IF EXISTS `notification_templates`;
CREATE TABLE IF NOT EXISTS `notification_templates` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `subject` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `body` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_type` (`type`),
  KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `package_permissions`
--

DROP TABLE IF EXISTS `package_permissions`;
CREATE TABLE IF NOT EXISTS `package_permissions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `package_id` int NOT NULL,
  `resource_access` enum('none','basic','premium','all') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'basic',
  `research_access` enum('none','view','create','collaborate','all') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'view',
  `max_research_projects` int DEFAULT '0',
  `max_resource_downloads` int DEFAULT '0',
  `can_collaborate` tinyint(1) DEFAULT '0',
  `can_upload_resources` tinyint(1) DEFAULT '0',
  `can_create_research` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_package` (`package_id`),
  KEY `idx_package_id` (`package_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `package_permissions`
--

INSERT INTO `package_permissions` (`id`, `package_id`, `resource_access`, `research_access`, `max_research_projects`, `max_resource_downloads`, `can_collaborate`, `can_upload_resources`, `can_create_research`) VALUES
(1, 1, 'basic', 'view', 0, 10, 0, 0, 0),
(2, 4, 'all', 'all', 999, 999, 1, 0, 1),
(3, 2, 'premium', 'create', 5, 50, 1, 0, 1),
(4, 3, 'all', 'all', 999, 999, 1, 0, 1);

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
CREATE TABLE IF NOT EXISTS `password_reset_tokens` (
  `id` int NOT NULL AUTO_INCREMENT,
  `member_id` int NOT NULL,
  `email` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `expires_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `used` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`),
  KEY `idx_token` (`token`),
  KEY `idx_email` (`email`),
  KEY `idx_member_id` (`member_id`),
  KEY `idx_expires_at` (`expires_at`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `password_reset_tokens`
--

INSERT INTO `password_reset_tokens` (`id`, `member_id`, `email`, `token`, `expires_at`, `used`, `created_at`) VALUES
(1, 118, 'daglasmohamed@gmail.com', '54ba7ff4e66ed5350030f987b788a5b980147f7d200459b72d691bca83f47910', '2025-12-16 18:11:51', 0, '2025-12-16 19:11:51');

-- --------------------------------------------------------

--
-- Table structure for table `pdf_annotations`
--

DROP TABLE IF EXISTS `pdf_annotations`;
CREATE TABLE IF NOT EXISTS `pdf_annotations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `member_id` int NOT NULL,
  `resource_id` int DEFAULT NULL,
  `research_file_id` int DEFAULT NULL,
  `page_number` int NOT NULL,
  `annotation_type` enum('highlight','note','bookmark','drawing') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `coordinates` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `color` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rect` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_member_id` (`member_id`),
  KEY `idx_resource_id` (`resource_id`),
  KEY `idx_research_file_id` (`research_file_id`),
  KEY `idx_page_number` (`page_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

DROP TABLE IF EXISTS `permissions`;
CREATE TABLE IF NOT EXISTS `permissions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `permission_key` varchar(100) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text,
  `category` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permission_key` (`permission_key`),
  KEY `idx_permission_key` (`permission_key`),
  KEY `idx_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reading_goals`
--

DROP TABLE IF EXISTS `reading_goals`;
CREATE TABLE IF NOT EXISTS `reading_goals` (
  `id` int NOT NULL AUTO_INCREMENT,
  `member_id` int NOT NULL,
  `goal_type` enum('daily','weekly','monthly') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `target_minutes` int NOT NULL,
  `current_minutes` int DEFAULT '0',
  `goal_period_start` date NOT NULL,
  `goal_period_end` date NOT NULL,
  `completed` tinyint(1) DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_member_id` (`member_id`),
  KEY `idx_goal_type` (`goal_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reading_progress`
--

DROP TABLE IF EXISTS `reading_progress`;
CREATE TABLE IF NOT EXISTS `reading_progress` (
  `id` int NOT NULL AUTO_INCREMENT,
  `member_id` int NOT NULL,
  `resource_id` int DEFAULT NULL,
  `research_id` int DEFAULT NULL,
  `page_number` int DEFAULT '1',
  `total_pages` int DEFAULT NULL,
  `time_spent_minutes` int DEFAULT '0',
  `last_read_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `completed` tinyint(1) DEFAULT '0',
  `completed_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_progress` (`member_id`,`resource_id`,`research_id`),
  KEY `idx_member_id` (`member_id`),
  KEY `idx_resource_id` (`resource_id`),
  KEY `idx_research_id` (`research_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `registrations`
--

DROP TABLE IF EXISTS `registrations`;
CREATE TABLE IF NOT EXISTS `registrations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `membership_id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `fullname` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `sex` enum('Male','Female') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `phone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `address` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `qualification` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `qualification_pdf` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `graduation_date` date DEFAULT NULL,
  `payment_duration` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `payment_option` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `id_card` tinyint(1) NOT NULL DEFAULT '0',
  `photo` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `bank_slip` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `approval_status` enum('pending','approved','rejected') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'pending',
  `approved_by` int DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `status` enum('active','expired','pending') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'pending',
  `id_card_generated` tinyint(1) NOT NULL DEFAULT '0',
  `id_card_generated_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `package_id` int DEFAULT NULL,
  `package_start_date` date DEFAULT NULL,
  `package_end_date` date DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `membership_id` (`membership_id`),
  KEY `idx_membership_id` (`membership_id`),
  KEY `idx_approval_status` (`approval_status`),
  KEY `idx_status` (`status`),
  KEY `fk_approved_by` (`approved_by`),
  KEY `idx_package_id` (`package_id`)
) ENGINE=MyISAM AUTO_INCREMENT=123 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `registrations`
--

INSERT INTO `registrations` (`id`, `membership_id`, `fullname`, `sex`, `email`, `phone`, `address`, `qualification`, `qualification_pdf`, `graduation_date`, `payment_duration`, `payment_option`, `id_card`, `photo`, `bank_slip`, `approval_status`, `approved_by`, `approved_at`, `expiry_date`, `status`, `id_card_generated`, `id_card_generated_at`, `created_at`, `package_id`, `package_start_date`, `package_end_date`, `updated_at`) VALUES
(118, 'ESWPA-2025-00001', 'Daglas man', 'Male', 'daglasmohamed@gmail.com', '+251-911-234-567', 'Addis Ababa, Ethiopia', 'BSW - Bachelor of Social Work', NULL, NULL, '1_year', 'bank', 1, NULL, NULL, 'approved', NULL, NULL, '2026-12-16', 'active', 1, '2025-12-16 19:20:27', '2025-12-16 18:59:31', NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `research_categories`
--

DROP TABLE IF EXISTS `research_categories`;
CREATE TABLE IF NOT EXISTS `research_categories` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `display_order` int NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `research_categories`
--

INSERT INTO `research_categories` (`id`, `name`, `display_order`, `created_at`) VALUES
(1, 'social works', 0, '2026-01-26 09:58:40');

-- --------------------------------------------------------

--
-- Table structure for table `research_collaborators`
--

DROP TABLE IF EXISTS `research_collaborators`;
CREATE TABLE IF NOT EXISTS `research_collaborators` (
  `id` int NOT NULL AUTO_INCREMENT,
  `research_id` int NOT NULL,
  `member_id` int NOT NULL,
  `role` enum('lead','co_author','contributor','advisor','reviewer') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'contributor',
  `contribution_percentage` decimal(5,2) DEFAULT NULL,
  `joined_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_collaboration` (`research_id`,`member_id`),
  KEY `idx_research_id` (`research_id`),
  KEY `idx_member_id` (`member_id`),
  KEY `idx_role` (`role`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `research_collaborators`
--

INSERT INTO `research_collaborators` (`id`, `research_id`, `member_id`, `role`, `contribution_percentage`, `joined_at`) VALUES
(1, 1, 118, 'lead', NULL, '2025-12-22 22:05:48');

-- --------------------------------------------------------

--
-- Table structure for table `research_comments`
--

DROP TABLE IF EXISTS `research_comments`;
CREATE TABLE IF NOT EXISTS `research_comments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `research_id` int NOT NULL,
  `member_id` int NOT NULL,
  `comment` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `parent_comment_id` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_research_id` (`research_id`),
  KEY `idx_member_id` (`member_id`),
  KEY `idx_parent` (`parent_comment_id`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `research_files`
--

DROP TABLE IF EXISTS `research_files`;
CREATE TABLE IF NOT EXISTS `research_files` (
  `id` int NOT NULL AUTO_INCREMENT,
  `research_id` int NOT NULL,
  `file_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_path` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `file_size` bigint DEFAULT NULL,
  `version` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '1.0',
  `uploaded_by` int NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_research_id` (`research_id`),
  KEY `idx_uploaded_by` (`uploaded_by`),
  KEY `idx_file_type` (`file_type`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `research_notes`
--

DROP TABLE IF EXISTS `research_notes`;
CREATE TABLE IF NOT EXISTS `research_notes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `member_id` int NOT NULL,
  `research_id` int DEFAULT NULL,
  `resource_id` int DEFAULT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tags` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `is_shared` tinyint(1) DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_member_id` (`member_id`),
  KEY `idx_research_id` (`research_id`),
  KEY `idx_resource_id` (`resource_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `research_notes`
--

INSERT INTO `research_notes` (`id`, `member_id`, `research_id`, `resource_id`, `title`, `content`, `tags`, `is_shared`, `created_at`, `updated_at`) VALUES
(1, 118, 1, 1, 'Ethio Social Works', 'Bibliographic Entry Summary\r\n\r\nAuthor / Name: Daglas\r\n\r\nAffiliation / Work: Ethio Social Works\r\n\r\nPublisher / Organization: Lebawi Net Trading\r\n\r\nYear: 2025\r\n\r\nOverall Meaning:\r\nA citation indicating a work by Daglas associated with Ethio Social Works, published or issued by Lebawi Net Trading in 2025.', '', 1, '2025-12-22 22:48:17', '2025-12-22 22:48:17');

-- --------------------------------------------------------

--
-- Table structure for table `research_projects`
--

DROP TABLE IF EXISTS `research_projects`;
CREATE TABLE IF NOT EXISTS `research_projects` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `abstract` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `status` enum('draft','in_progress','completed','published','archived') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'draft',
  `category` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `research_type` enum('thesis','journal_article','case_study','survey','experiment','review','other') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `publication_date` date DEFAULT NULL,
  `doi` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `keywords` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_by` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `metadata_json` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'Structured metadata in JSON format',
  `ai_processed` tinyint(1) DEFAULT '0' COMMENT 'Whether this research has been processed by AI',
  `ai_processed_at` timestamp NULL DEFAULT NULL COMMENT 'Timestamp when AI processing was completed',
  `ai_summary` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'AI-generated summary of the research',
  `ai_keywords_extracted` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'AI-extracted keywords (comma-separated)',
  PRIMARY KEY (`id`),
  KEY `idx_created_by` (`created_by`),
  KEY `idx_status` (`status`),
  KEY `idx_category` (`category`),
  KEY `idx_research_type` (`research_type`),
  KEY `idx_publication_date` (`publication_date`),
  KEY `idx_ai_processed` (`ai_processed`),
  KEY `idx_ai_processed_at` (`ai_processed_at`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `research_projects`
--

INSERT INTO `research_projects` (`id`, `title`, `description`, `abstract`, `status`, `category`, `research_type`, `start_date`, `end_date`, `publication_date`, `doi`, `keywords`, `created_by`, `created_at`, `updated_at`, `metadata_json`, `ai_processed`, `ai_processed_at`, `ai_summary`, `ai_keywords_extracted`) VALUES
(1, 'Powering Your Future With Innovation Daglass', 'l5', 'ccc', 'draft', 'ccc', 'thesis', NULL, NULL, NULL, NULL, '', 118, '2025-12-22 22:05:48', '2025-12-22 22:05:48', NULL, 0, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `research_versions`
--

DROP TABLE IF EXISTS `research_versions`;
CREATE TABLE IF NOT EXISTS `research_versions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `research_id` int NOT NULL,
  `version_number` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `changes_summary` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `changed_by` int NOT NULL,
  `changed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_research_id` (`research_id`),
  KEY `idx_version` (`research_id`,`version_number`),
  KEY `idx_changed_by` (`changed_by`),
  KEY `idx_changed_at` (`changed_at`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `research_versions`
--

INSERT INTO `research_versions` (`id`, `research_id`, `version_number`, `title`, `description`, `changes_summary`, `changed_by`, `changed_at`) VALUES
(1, 1, '1.0', 'Powering Your Future With Innovation Daglass', 'l5', NULL, 118, '2025-12-22 22:05:48');

-- --------------------------------------------------------

--
-- Table structure for table `resources`
--

DROP TABLE IF EXISTS `resources`;
CREATE TABLE IF NOT EXISTS `resources` (
  `id` int NOT NULL AUTO_INCREMENT,
  `section` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `publication_date` date NOT NULL,
  `author` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `pdf_file` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `status` enum('active','inactive','archived') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'active' COMMENT 'Resource status: active, inactive, or archived',
  `tags` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'Comma-separated tags for categorization',
  `featured` tinyint(1) DEFAULT '0' COMMENT 'Whether resource is featured (1) or not (0)',
  `download_count` int DEFAULT '0' COMMENT 'Total number of downloads',
  `access_level` enum('public','member','premium','restricted') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'member' COMMENT 'Access level: public (everyone), member (logged in), premium (premium package), restricted (special permission)',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `metadata_json` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'Structured metadata in JSON format',
  `ai_processed` tinyint(1) DEFAULT '0' COMMENT 'Whether this resource has been processed by AI',
  `ai_processed_at` timestamp NULL DEFAULT NULL COMMENT 'Timestamp when AI processing was completed',
  `extracted_text` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'Extracted text content from PDF/document',
  PRIMARY KEY (`id`),
  KEY `idx_section` (`section`),
  KEY `idx_publication_date` (`publication_date`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_status` (`status`),
  KEY `idx_featured` (`featured`),
  KEY `idx_access_level` (`access_level`),
  KEY `idx_download_count` (`download_count`),
  KEY `idx_ai_processed` (`ai_processed`),
  KEY `idx_ai_processed_at` (`ai_processed_at`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `resources`
--

INSERT INTO `resources` (`id`, `section`, `title`, `publication_date`, `author`, `pdf_file`, `description`, `status`, `tags`, `featured`, `download_count`, `access_level`, `created_at`, `updated_at`, `metadata_json`, `ai_processed`, `ai_processed_at`, `extracted_text`) VALUES
(1, 'Lebawi Net Trading', 'Ethio Social Works', '2025-12-16', 'Daglas', 'uploads/resources/1765913424_6941b35072d14_CI for 25111702-2.pdf', 'Check', 'active', NULL, 0, 0, 'member', '2025-12-16 19:30:24', NULL, NULL, 0, NULL, NULL),
(2, 'Lebawi Net Trading check', 'Square', '2025-12-23', 'Daglas', 'uploads/resources/1766439693_6949bb0d62a18_1765913424_6941b35072d14_CI for 25111702-2.pdf', 'Check', 'active', NULL, 0, 0, 'member', '2025-12-22 21:41:33', '2025-12-22 22:03:51', NULL, 0, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `resource_sections`
--

DROP TABLE IF EXISTS `resource_sections`;
CREATE TABLE IF NOT EXISTS `resource_sections` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `display_order` int NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sent_emails`
--

DROP TABLE IF EXISTS `sent_emails`;
CREATE TABLE IF NOT EXISTS `sent_emails` (
  `id` int NOT NULL AUTO_INCREMENT,
  `recipients` text NOT NULL,
  `subject` varchar(255) NOT NULL,
  `body` text NOT NULL,
  `attachment` varchar(255) DEFAULT NULL,
  `sent_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=latin1;

--
-- Dumping data for table `sent_emails`
--

INSERT INTO `sent_emails` (`id`, `recipients`, `subject`, `body`, `attachment`, `sent_at`) VALUES
(1, 'daglasmohamed@gmail.com', 'Check', '<p>asd</p>', '', '2025-06-21 18:56:46'),
(2, 'daglasmohamed@gmail.com', 'Test Ticketsss', '<p>asd</p>', '', '2025-12-22 20:37:11');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
CREATE TABLE IF NOT EXISTS `settings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `setting_value` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `category` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'general',
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`),
  KEY `idx_category` (`category`),
  KEY `idx_setting_key` (`setting_key`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `setting_key`, `setting_value`, `category`, `description`, `updated_at`, `created_at`) VALUES
(1, 'site_name', 'Ethiopian Social Workers Professional Association', 'general', 'Website name', NULL, '2025-12-16 19:43:19'),
(2, 'site_email', 'info@ethiosocialworker.org', 'general', 'Default email address', NULL, '2025-12-16 19:43:19'),
(3, 'telegram_bot_token', '7276849358:AAFnk_wSy_E0RbW3U1at8tk70OBL4uAaYE8', 'telegram', 'Telegram bot token', '2025-12-22 20:50:38', '2025-12-16 19:43:19'),
(4, 'telegram_chat_id', '317393086', 'telegram', 'Telegram chat ID for notifications', '2025-12-22 20:50:38', '2025-12-16 19:43:19'),
(5, 'smtp_host', '', 'email', 'SMTP server host', NULL, '2025-12-16 19:43:19'),
(6, 'smtp_port', '587', 'email', 'SMTP server port', NULL, '2025-12-16 19:43:19'),
(7, 'smtp_username', '', 'email', 'SMTP username', NULL, '2025-12-16 19:43:19'),
(8, 'smtp_password', '', 'email', 'SMTP password', NULL, '2025-12-16 19:43:19'),
(9, 'smtp_encryption', 'tls', 'email', 'SMTP encryption type', NULL, '2025-12-16 19:43:19'),
(10, 'backup_auto_enabled', '0', 'backup', 'Enable automatic backups', NULL, '2025-12-16 19:43:19'),
(11, 'backup_auto_frequency', 'daily', 'backup', 'Automatic backup frequency', NULL, '2025-12-16 19:43:19'),
(12, 'sync_enabled', '0', 'sync', 'Enable data synchronization', NULL, '2025-12-16 19:43:19'),
(13, 'sync_remote_host', '', 'sync', 'Remote server host', NULL, '2025-12-16 19:43:19'),
(14, 'sync_remote_db', '', 'sync', 'Remote database name', NULL, '2025-12-16 19:43:19');

-- --------------------------------------------------------

--
-- Table structure for table `special_permissions`
--

DROP TABLE IF EXISTS `special_permissions`;
CREATE TABLE IF NOT EXISTS `special_permissions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `member_id` int NOT NULL,
  `permission_type` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `resource_id` int DEFAULT NULL,
  `research_id` int DEFAULT NULL,
  `granted_by` int UNSIGNED DEFAULT NULL,
  `granted_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `idx_member_id` (`member_id`),
  KEY `idx_permission_type` (`permission_type`),
  KEY `idx_resource_id` (`resource_id`),
  KEY `idx_research_id` (`research_id`),
  KEY `idx_is_active` (`is_active`),
  KEY `idx_expires_at` (`expires_at`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `special_permissions`
--

INSERT INTO `special_permissions` (`id`, `member_id`, `permission_type`, `resource_id`, `research_id`, `granted_by`, `granted_at`, `expires_at`, `is_active`, `notes`) VALUES
(1, 118, 'unlimited_downloads', NULL, NULL, 2, '2025-12-22 23:32:31', '0000-00-00 00:00:00', 1, '');

-- --------------------------------------------------------

--
-- Table structure for table `support_knowledge_base`
--

DROP TABLE IF EXISTS `support_knowledge_base`;
CREATE TABLE IF NOT EXISTS `support_knowledge_base` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `category` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` enum('draft','published','archived') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'draft',
  `views` int NOT NULL DEFAULT '0',
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_category` (`category`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `support_tickets`
--

DROP TABLE IF EXISTS `support_tickets`;
CREATE TABLE IF NOT EXISTS `support_tickets` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ticket_number` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `member_id` int DEFAULT NULL,
  `subject` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `category` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `priority` enum('low','medium','high','urgent') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'medium',
  `status` enum('open','pending','in_progress','resolved','closed') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'open',
  `assigned_to` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `resolved_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ticket_number` (`ticket_number`),
  KEY `idx_status` (`status`),
  KEY `idx_priority` (`priority`),
  KEY `idx_member_id` (`member_id`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sync_logs`
--

DROP TABLE IF EXISTS `sync_logs`;
CREATE TABLE IF NOT EXISTS `sync_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `sync_direction` enum('pull','push') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'pull = remote to local, push = local to remote',
  `sync_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Type of sync: full, tables_only, files_only, etc.',
  `tables_synced` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'Comma-separated list of tables synced',
  `records_synced` int DEFAULT '0' COMMENT 'Number of records synced',
  `files_synced` int DEFAULT '0' COMMENT 'Number of files synced',
  `status` enum('pending','in_progress','completed','failed','partial') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `error_message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'Error message if sync failed',
  `started_at` timestamp NULL DEFAULT NULL COMMENT 'When sync started',
  `completed_at` timestamp NULL DEFAULT NULL COMMENT 'When sync completed',
  `duration_seconds` decimal(10,2) DEFAULT NULL COMMENT 'Sync duration in seconds',
  `initiated_by` int UNSIGNED DEFAULT NULL COMMENT 'Admin user ID who initiated sync (references user.id)',
  `remote_host` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Remote server host',
  `remote_database` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Remote database name',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_direction` (`sync_direction`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_initiated_by` (`initiated_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Logs all data synchronization operations';

-- --------------------------------------------------------

--
-- Table structure for table `telegram_messages`
--

DROP TABLE IF EXISTS `telegram_messages`;
CREATE TABLE IF NOT EXISTS `telegram_messages` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_email` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_phone` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `telegram_message_id` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('sent','failed','pending') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_user_email` (`user_email`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `telegram_messages`
--

INSERT INTO `telegram_messages` (`id`, `user_name`, `user_email`, `user_phone`, `message`, `telegram_message_id`, `status`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 'DAGLAS', 'daglasmohamed@gmail.com', '924067895', 'hi', '522', 'sent', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '2025-12-22 20:51:33'),
(2, 'Robel Friend', 'salem@lebawi.net', '0919399535', 'DSA', '523', 'sent', '127.0.0.1', 'Mozilla/5.0 (Linux; Android 11; SAMSUNG SM-G973U) AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/14.2 Chrome/87.0.4280.141 Mobile Safari/537.36', '2025-12-22 21:05:20');

-- --------------------------------------------------------

--
-- Table structure for table `upcoming`
--

DROP TABLE IF EXISTS `upcoming`;
CREATE TABLE IF NOT EXISTS `upcoming` (
  `id` int NOT NULL AUTO_INCREMENT,
  `event_date` date NOT NULL,
  `event_header` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `event_description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `event_images` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `upcoming`
--

INSERT INTO `upcoming` (`id`, `event_date`, `event_header`, `event_description`, `event_images`, `created_at`) VALUES
(8, '2025-03-29', '4th general assembly ', 'Ethiopian Social Work Professionals Association (ESWPA)  4th General Assembly ', '[\"..\\/..\\/uploads\\/1742569003_photo_2025-03-21_14-53-24.jpg\"]', '2025-03-21 14:56:43');

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

DROP TABLE IF EXISTS `user`;
CREATE TABLE IF NOT EXISTS `user` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `registration_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=MyISAM AUTO_INCREMENT=13 DEFAULT CHARSET=utf32;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`id`, `username`, `password`, `registration_date`) VALUES
(11, 'ethiosocialworker.org', '$2y$10$i1Iae7y6HOQmSl2htIfdSOAm0IqKcZUvzNGnc8ztrX5vH2vb14y/S', '2025-12-16 19:23:03'),
(7, 'admin', '$2y$10$i1Iae7y6HOQmSl2htIfdSOAm0IqKcZUvzNGnc8ztrX5vH2vb14y/S', '2025-12-16 19:23:00'),
(9, 'Afomiya', '$2y$10$i1Iae7y6HOQmSl2htIfdSOAm0IqKcZUvzNGnc8ztrX5vH2vb14y/S', '2025-12-16 19:22:57'),
(1, 'superadmin', '$2y$10$WNDjHgXAAllQfXufWDpXSeCzcTK7qGbZIHpOwkUTemAbDqa8T9jx2', '2025-12-24 18:21:25'),
(12, 'newadmin', '$2y$10$C2jTvwEB0.8TkglrcuDaMuPXrmDEtmLMTtWnLGD.xzJDvbv0QzAdq', '2026-03-09 12:26:17');

-- --------------------------------------------------------

--
-- Table structure for table `user_roles`
--

DROP TABLE IF EXISTS `user_roles`;
CREATE TABLE IF NOT EXISTS `user_roles` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `role` enum('super_admin','admin','editor','viewer') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'viewer',
  `permissions` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_role` (`user_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_role` (`role`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_roles`
--

INSERT INTO `user_roles` (`id`, `user_id`, `role`, `permissions`, `created_at`, `updated_at`) VALUES
(1, 0, 'super_admin', '[]', '2026-03-09 12:26:36', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `research_notes`
--
ALTER TABLE `research_notes` ADD FULLTEXT KEY `idx_content` (`title`,`content`);

--
-- Constraints for dumped tables
--

--
-- Constraints for table `ai_processing_queue`
--
ALTER TABLE `ai_processing_queue`
  ADD CONSTRAINT `ai_processing_queue_ibfk_1` FOREIGN KEY (`plugin_id`) REFERENCES `ai_plugins` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `ai_processing_results`
--
ALTER TABLE `ai_processing_results`
  ADD CONSTRAINT `ai_processing_results_ibfk_1` FOREIGN KEY (`queue_id`) REFERENCES `ai_processing_queue` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ai_processing_results_ibfk_2` FOREIGN KEY (`plugin_id`) REFERENCES `ai_plugins` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `email_automation_settings`
--
ALTER TABLE `email_automation_settings`
  ADD CONSTRAINT `email_automation_settings_ibfk_1` FOREIGN KEY (`template_id`) REFERENCES `email_templates` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_template_id` FOREIGN KEY (`template_id`) REFERENCES `email_templates` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `package_permissions`
--
ALTER TABLE `package_permissions`
  ADD CONSTRAINT `package_permissions_ibfk_1` FOREIGN KEY (`package_id`) REFERENCES `membership_packages` (`id`) ON DELETE CASCADE;
SET FOREIGN_KEY_CHECKS = 1;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

