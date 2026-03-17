-- =============================================================================
-- Migration: Add DEFAULT NULL to columns for safe OLD data import
-- =============================================================================
-- Purpose: When importing OLD client data into the NEW structure, columns that
--          exist in NEW but not in OLD need DEFAULT NULL (or 0) so inserts work.
-- Run this on your NEW database BEFORE importing OLD data.
-- =============================================================================

USE ethiosocialworks;

-- -----------------------------------------------------------------------------
-- 1. member_badges
-- OLD has: id, member_id, badge_name, earned_at, is_active
-- NEW adds: badge_description, assigned_by, assigned_at
-- -----------------------------------------------------------------------------
ALTER TABLE `member_badges`
  MODIFY COLUMN `badge_description` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  MODIFY COLUMN `assigned_by` int DEFAULT NULL,
  MODIFY COLUMN `assigned_at` timestamp NULL DEFAULT NULL;

-- -----------------------------------------------------------------------------
-- 2. registrations
-- OLD has no updated_at. NEW has it. Ensure it accepts NULL on insert.
-- (Usually already NULL DEFAULT NULL - this just ensures it)
-- -----------------------------------------------------------------------------
ALTER TABLE `registrations`
  MODIFY COLUMN `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP;

-- -----------------------------------------------------------------------------
-- 3. notification_templates (NEW-only table)
-- created_by is NOT NULL - if you ever need to seed without a user, allow NULL
-- -----------------------------------------------------------------------------
ALTER TABLE `notification_templates`
  MODIFY COLUMN `created_by` int DEFAULT NULL;

-- -----------------------------------------------------------------------------
-- 4. support_knowledge_base (NEW-only table)
-- created_by is NOT NULL - allow NULL for empty/seed rows
-- -----------------------------------------------------------------------------
ALTER TABLE `support_knowledge_base`
  MODIFY COLUMN `created_by` int DEFAULT NULL;

-- -----------------------------------------------------------------------------
-- 5. member_permissions (NEW-only table)
-- granted_by already DEFAULT NULL. notes - ensure DEFAULT NULL
-- -----------------------------------------------------------------------------
ALTER TABLE `member_permissions`
  MODIFY COLUMN `granted_by` int DEFAULT NULL,
  MODIFY COLUMN `notes` text DEFAULT NULL;

-- -----------------------------------------------------------------------------
-- 6. permissions (NEW-only table)
-- -----------------------------------------------------------------------------
ALTER TABLE `permissions`
  MODIFY COLUMN `description` text DEFAULT NULL,
  MODIFY COLUMN `category` varchar(50) DEFAULT NULL;

-- -----------------------------------------------------------------------------
-- 7. reading_progress - ensure nullable columns have DEFAULT NULL
-- resource_id, research_id can be NULL (same resource/research)
-- -----------------------------------------------------------------------------
ALTER TABLE `reading_progress`
  MODIFY COLUMN `resource_id` int DEFAULT NULL,
  MODIFY COLUMN `research_id` int DEFAULT NULL,
  MODIFY COLUMN `total_pages` int DEFAULT NULL,
  MODIFY COLUMN `completed_at` timestamp NULL DEFAULT NULL;

-- -----------------------------------------------------------------------------
-- 8. reading_goals - completed flag
-- -----------------------------------------------------------------------------
ALTER TABLE `reading_goals`
  MODIFY COLUMN `completed` tinyint(1) DEFAULT 0;

-- -----------------------------------------------------------------------------
-- 9. pdf_annotations - nullable FKs
-- -----------------------------------------------------------------------------
ALTER TABLE `pdf_annotations`
  MODIFY COLUMN `resource_id` int DEFAULT NULL,
  MODIFY COLUMN `research_file_id` int DEFAULT NULL,
  MODIFY COLUMN `content` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  MODIFY COLUMN `coordinates` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  MODIFY COLUMN `color` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  MODIFY COLUMN `rect` text COLLATE utf8mb4_unicode_ci DEFAULT NULL;

-- -----------------------------------------------------------------------------
-- 10. member_activities (NEW-only) - related_id, related_type
-- -----------------------------------------------------------------------------
ALTER TABLE `member_activities`
  MODIFY COLUMN `related_id` int DEFAULT NULL,
  MODIFY COLUMN `related_type` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  MODIFY COLUMN `ip_address` varchar(45) COLLATE utf8mb4_general_ci DEFAULT NULL,
  MODIFY COLUMN `user_agent` text COLLATE utf8mb4_general_ci DEFAULT NULL;

-- -----------------------------------------------------------------------------
-- 11. notifications (NEW-only)
-- -----------------------------------------------------------------------------
ALTER TABLE `notifications`
  MODIFY COLUMN `related_id` int DEFAULT NULL,
  MODIFY COLUMN `related_type` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  MODIFY COLUMN `read_at` timestamp NULL DEFAULT NULL;

-- -----------------------------------------------------------------------------
-- 12. support_tickets (NEW-only)
-- -----------------------------------------------------------------------------
ALTER TABLE `support_tickets`
  MODIFY COLUMN `member_id` int DEFAULT NULL,
  MODIFY COLUMN `category` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  MODIFY COLUMN `assigned_to` int DEFAULT NULL,
  MODIFY COLUMN `resolved_at` timestamp NULL DEFAULT NULL;

-- -----------------------------------------------------------------------------
-- 13. support_knowledge_base - category, views (if not done in section 4)
-- -----------------------------------------------------------------------------
ALTER TABLE `support_knowledge_base`
  MODIFY COLUMN `category` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  MODIFY COLUMN `views` int NOT NULL DEFAULT 0;

-- =============================================================================
-- DONE. Run this script on your NEW database before importing OLD data.
-- =============================================================================
