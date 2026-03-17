# Database Table Structure

**Database Name**: `ethiosocialworks` (localhost) / `ethiosdt_database` (server)

## Tables Overview

1. `audit_logs` - System audit trail
2. `backups` - Backup tracking
3. `changelogs` - System changelog tracking
4. `company_info` - Company/Organization information
5. `email_subscribers` - Email newsletter subscribers
6. `events` - Regular events
7. `id_card_verification` - ID card QR code verification
8. `member_access` - Member login/authentication
9. `news_media` - News/blog posts
10. `password_reset_tokens` - Password reset tokens
11. `registrations` - Member registrations
12. `resources` - Downloadable resources
13. `sent_emails` - Email tracking
14. `settings` - System configuration
15. `telegram_messages` - Telegram message logging (optional)
16. `upcoming` - Upcoming events
17. `user` - Admin users
18. `user_roles` - Role-based access control
19. `about_team_members` - Public “Our Team Members” section

---

## 1. changelogs

| Field | Type | Null | Key | Default | Extra |
|-------|------|------|-----|---------|-------|
| id | int(11) | NO | PRI | NULL | auto_increment |
| version | varchar(50) | NO | | NULL | |
| change_date | date | NO | | NULL | |
| type | varchar(100) | NO | | NULL | |
| title | varchar(255) | NO | | NULL | |
| description | text | NO | | NULL | |

**Purpose**: Track system changes, updates, and version history.

---

## 2. events

| Field | Type | Null | Key | Default | Extra |
|-------|------|------|-----|---------|-------|
| id | int(11) | NO | PRI | NULL | auto_increment |
| event_date | date | NO | | NULL | |
| event_header | varchar(255) | NO | | NULL | |
| event_description | text | NO | | NULL | |
| event_images | text | NO | | NULL | |
| created_at | timestamp | NO | | current_timestamp() | |

**Purpose**: Store regular/past events with images (JSON array).

---

## 3. registrations

| Field | Type | Null | Key | Default | Extra |
|-------|------|------|-----|---------|-------|
| id | int(11) | NO | PRI | NULL | auto_increment |
| fullname | varchar(255) | NO | | NULL | |
| sex | enum('Male','Female') | NO | | NULL | |
| email | varchar(191) | NO | UNI | NULL | |
| phone | varchar(20) | NO | | NULL | |
| address | text | NO | | NULL | |
| qualification | varchar(255) | NO | | NULL | |
| payment_duration | varchar(50) | NO | | NULL | |
| payment_option | varchar(50) | NO | | NULL | |
| id_card | tinyint(1) | NO | | 0 | |
| photo | varchar(255) | YES | | NULL | |
| bank_slip | varchar(255) | YES | | NULL | |
| created_at | timestamp | YES | | current_timestamp() | |

**Purpose**: Member registration data with personal info, photos, and payment details.

**Notes**:
- `email` is unique
- `id_card` is boolean (0/1)
- `photo` and `bank_slip` store file paths

**Planned Fields to Add** (for new features):
- `membership_id` varchar(50) - Auto-generated membership ID (e.g., ESWPA-2025-00001)
- `approval_status` enum('pending','approved','rejected') - Admin approval status
- `approved_by` int(11) - Admin user ID who approved
- `approved_at` timestamp - Approval timestamp
- `expiry_date` date - Membership expiry date
- `status` enum('active','expired','pending') - Membership status
- `qualification_pdf` varchar(255) - Qualification certificate PDF path
- `graduation_date` date - Graduation date
- `id_card_generated` tinyint(1) - Whether ID card has been generated
- `id_card_generated_at` timestamp - ID card generation timestamp

---

## 4. sent_emails

| Field | Type | Null | Key | Default | Extra |
|-------|------|------|-----|---------|-------|
| id | int(11) | NO | PRI | NULL | auto_increment |
| recipients | text | NO | | NULL | |
| subject | varchar(255) | NO | | NULL | |
| body | text | NO | | NULL | |
| attachment | varchar(255) | YES | | NULL | |
| sent_at | timestamp | NO | | current_timestamp() | |

**Purpose**: Track all sent emails with recipients, content, and attachments.

---

## 5. upcoming

| Field | Type | Null | Key | Default | Extra |
|-------|------|------|-----|---------|-------|
| id | int(11) | NO | PRI | NULL | auto_increment |
| event_date | date | NO | | NULL | |
| event_header | text | NO | | NULL | |
| event_description | text | NO | | NULL | |
| event_images | text | NO | | NULL | |
| created_at | timestamp | NO | | current_timestamp() | on update current_timestamp() |

**Purpose**: Store upcoming/future events (similar structure to `events`).

---

## 6. user

| Field | Type | Null | Key | Default | Extra |
|-------|------|------|-----|---------|-------|
| id | int(10) unsigned | NO | PRI | NULL | auto_increment |
| username | varchar(50) | NO | UNI | NULL | |
| password | varchar(255) | NO | | NULL | |
| registration_date | timestamp | NO | | current_timestamp() | on update current_timestamp() |

**Purpose**: Admin user authentication.

**Notes**:
- `username` is unique
- `password` is hashed using `password_hash()`

---

## 7. email_subscribers

| Field | Type | Null | Key | Default | Extra |
|-------|------|------|-----|---------|-------|
| id | int(11) | NO | PRI | NULL | auto_increment |
| email | varchar(191) | NO | UNI | NULL | |
| name | varchar(255) | YES | | NULL | |
| status | enum('active','unsubscribed','bounced') | NO | MUL | active | |
| subscribed_at | timestamp | NO | MUL | current_timestamp() | |
| unsubscribed_at | timestamp | YES | | NULL | |
| source | varchar(50) | YES | | popup | |
| ip_address | varchar(45) | YES | | NULL | |
| user_agent | text | YES | | NULL | |
| unsubscribe_token | varchar(255) | YES | UNI | NULL | |

**Purpose**: Manage email newsletter subscribers with subscription tracking and unsubscribe functionality.

**Notes**:
- `email` is unique
- `unsubscribe_token` is unique for secure unsubscribe links
- `status` tracks subscription state (active, unsubscribed, bounced)
- Created: December 22, 2025

---

## 8. telegram_messages

| Field | Type | Null | Key | Default | Extra |
|-------|------|------|-----|---------|-------|
| id | int(11) | NO | PRI | NULL | auto_increment |
| user_name | varchar(255) | YES | | NULL | |
| user_email | varchar(191) | YES | MUL | NULL | |
| user_phone | varchar(50) | YES | | NULL | |
| message | text | NO | | NULL | |
| telegram_message_id | varchar(100) | YES | | NULL | |
| status | enum('sent','failed','pending') | NO | MUL | pending | |
| ip_address | varchar(45) | YES | | NULL | |
| user_agent | text | YES | | NULL | |
| created_at | timestamp | NO | MUL | current_timestamp() | |

**Purpose**: Optional logging table for Telegram messages sent from website chat widget.

**Notes**:
- Optional table - system works without it
- Used for message history and tracking
- `status` tracks message delivery state
- Created: December 22, 2025

---

## 7. email_subscribers

| Field | Type | Null | Key | Default | Extra |
|-------|------|------|-----|---------|-------|
| id | int(11) | NO | PRI | NULL | auto_increment |
| email | varchar(191) | NO | UNI | NULL | |
| name | varchar(255) | YES | | NULL | |
| status | enum('active','unsubscribed','bounced') | NO | MUL | active | |
| subscribed_at | timestamp | NO | MUL | current_timestamp() | |
| unsubscribed_at | timestamp | YES | | NULL | |
| source | varchar(50) | YES | | popup | |
| ip_address | varchar(45) | YES | | NULL | |
| user_agent | text | YES | | NULL | |
| unsubscribe_token | varchar(255) | YES | UNI | NULL | |

**Purpose**: Manage email newsletter subscribers with subscription tracking and unsubscribe functionality.

**Notes**:
- `email` is unique
- `unsubscribe_token` is unique for secure unsubscribe links
- `status` tracks subscription state (active, unsubscribed, bounced)
- Created: December 22, 2025

---

## 8. telegram_messages

| Field | Type | Null | Key | Default | Extra |
|-------|------|------|-----|---------|-------|
| id | int(11) | NO | PRI | NULL | auto_increment |
| user_name | varchar(255) | YES | | NULL | |
| user_email | varchar(191) | YES | MUL | NULL | |
| user_phone | varchar(50) | YES | | NULL | |
| message | text | NO | | NULL | |
| telegram_message_id | varchar(100) | YES | | NULL | |
| status | enum('sent','failed','pending') | NO | MUL | pending | |
| ip_address | varchar(45) | YES | | NULL | |
| user_agent | text | YES | | NULL | |
| created_at | timestamp | NO | MUL | current_timestamp() | |

**Purpose**: Optional logging table for Telegram messages sent from website chat widget.

**Notes**:
- Optional table - system works without it
- Used for message history and tracking
- `status` tracks message delivery state
- Created: December 22, 2025

---

## Relationships

- `registrations` → stores member data (no foreign keys currently)
- `events` and `upcoming` → separate tables for event types
- `user` → admin authentication
- `member_access` → member authentication (links to registrations)
- `sent_emails` → tracks email communications
- `email_subscribers` → tracks newsletter subscribers (independent)
- `telegram_messages` → logs Telegram messages (optional, independent)
- `email_templates` → email templates for automation
- `email_automation_settings` → automation settings per content type
- `email_automation_logs` → logs of automated email sends

---

## Email Automation Tables

### `email_templates`
Stores email templates for automated email marketing.

**Fields:**
- `id` (INT, PK, AUTO_INCREMENT)
- `name` (VARCHAR(255)) - Template name
- `subject` (VARCHAR(255)) - Email subject with variables
- `body` (TEXT) - Email body HTML with variables
- `content_type` (ENUM: 'news', 'blog', 'report', 'event', 'resource', 'general')
- `is_active` (TINYINT(1)) - Active status
- `created_at` (TIMESTAMP)
- `updated_at` (TIMESTAMP)

**Indexes:**
- `idx_content_type` on `content_type`
- `idx_is_active` on `is_active`

**Template Variables:**
- `{TITLE}` - Content title
- `{CONTENT}` - Full content
- `{EXCERPT}` - Content excerpt (first 300 chars)
- `{AUTHOR}` - Author name
- `{DATE}` - Published date
- `{LINK}` - Link to content
- `{TYPE}` - Content type
- `{IMAGE}` - Featured image HTML
- `{UNSUBSCRIBE_LINK}` - Unsubscribe link

### `email_automation_settings`
Stores automation settings for each content type.

**Fields:**
- `id` (INT, PK, AUTO_INCREMENT)
- `content_type` (ENUM: 'news', 'blog', 'report', 'event', 'resource') - UNIQUE
- `enabled` (TINYINT(1)) - Enable/disable automation
- `send_to_subscribers` (TINYINT(1)) - Send to email subscribers
- `send_to_members` (TINYINT(1)) - Send to members
- `send_to_custom` (TINYINT(1)) - Send to custom email list
- `custom_emails` (TEXT) - Comma-separated custom emails
- `template_id` (INT, FK → email_templates.id) - Selected template
- `send_immediately` (TINYINT(1)) - Send immediately or schedule
- `send_only_published` (TINYINT(1)) - Only send when status = 'published'
- `include_images` (TINYINT(1)) - Include images in email
- `created_at` (TIMESTAMP)
- `updated_at` (TIMESTAMP)

**Indexes:**
- `unique_content_type` on `content_type` (UNIQUE)
- `idx_enabled` on `enabled`
- `idx_template_id` on `template_id`

**Default Settings:**
- News: Disabled
- Blog: Disabled
- Report: Disabled
- Event: Enabled
- Resource: Disabled

### `email_automation_logs`
Logs all automated email sends.

**Fields:**
- `id` (INT, PK, AUTO_INCREMENT)
- `content_type` (ENUM: 'news', 'blog', 'report', 'event', 'resource')
- `content_id` (INT) - ID of the content item
- `content_title` (VARCHAR(255)) - Title of content
- `recipients_count` (INT) - Total recipients
- `sent_count` (INT) - Successfully sent
- `failed_count` (INT) - Failed sends
- `sent_at` (TIMESTAMP) - When sent
- `sent_by` (INT, FK → user.id) - Admin user who triggered
- `status` (ENUM: 'success', 'failed', 'partial')
- `error_message` (TEXT) - Error details if failed

**Indexes:**
- `idx_content_type` on `content_type`
- `idx_content_id` on `content_id`
- `idx_sent_at` on `sent_at`
- `idx_status` on `status`
- `idx_sent_by` on `sent_by`

---

## 12. resources

| Field | Type | Null | Key | Default | Extra |
|-------|------|------|-----|---------|-------|
| id | int(11) | NO | PRI | NULL | auto_increment |
| section | varchar(100) | NO | MUL | NULL | |
| title | varchar(255) | NO | | NULL | |
| publication_date | date | NO | MUL | NULL | |
| author | varchar(255) | NO | | NULL | |
| pdf_file | varchar(255) | NO | | NULL | |
| description | text | YES | | NULL | |
| status | enum('active','inactive','archived') | YES | MUL | active | |
| tags | text | YES | | NULL | |
| featured | tinyint(1) | YES | MUL | 0 | |
| download_count | int(11) | YES | MUL | 0 | |
| access_level | enum('public','member','premium','restricted') | YES | MUL | member | |
| created_at | timestamp | NO | MUL | current_timestamp() | |
| updated_at | timestamp | YES | | NULL | on update current_timestamp() |

**Purpose**: Store downloadable resources for members (PDFs, documents, etc.).

**Notes**:
- `section` - Category/section of the resource
- `status` - Resource status: active, inactive, or archived
- `tags` - Comma-separated tags for categorization
- `featured` - Whether resource is featured (1) or not (0)
- `download_count` - Total number of downloads
- `access_level` - Access control: public (everyone), member (logged in), premium (premium package), restricted (special permission)
- Created: Initial structure created in migration_create_resources_news.sql
- Enhanced: December 16, 2025 (agent_dev_2) - Added status, tags, featured, download_count, access_level fields

**Indexes**:
- `idx_section` on `section`
- `idx_publication_date` on `publication_date`
- `idx_created_at` on `created_at`
- `idx_status` on `status`
- `idx_featured` on `featured`
- `idx_access_level` on `access_level`
- `idx_download_count` on `download_count`

---

## 13. research_projects

| Field | Type | Null | Key | Default | Extra |
|-------|------|------|-----|---------|-------|
| id | int(11) | NO | PRI | NULL | auto_increment |
| title | varchar(255) | NO | | NULL | |
| description | text | NO | | NULL | |
| abstract | text | YES | | NULL | |
| status | enum('draft','in_progress','completed','published','archived') | YES | MUL | draft | |
| category | varchar(100) | YES | MUL | NULL | |
| research_type | enum('thesis','journal_article','case_study','survey','experiment','review','other') | YES | MUL | NULL | |
| start_date | date | YES | | NULL | |
| end_date | date | YES | | NULL | |
| publication_date | date | YES | MUL | NULL | |
| doi | varchar(255) | YES | | NULL | |
| keywords | text | YES | | NULL | |
| created_by | int(11) | NO | MUL | NULL | |
| created_at | timestamp | NO | | current_timestamp() | |
| updated_at | timestamp | NO | | current_timestamp() | on update current_timestamp() |

**Purpose**: Store research projects with full metadata and tracking.

**Notes**:
- `created_by` - References registrations.id (member who created)
- `status` - Tracks research lifecycle
- `research_type` - Type of research work
- Created: December 16, 2025 (agent_dev_2)

## 14. research_collaborators

| Field | Type | Null | Key | Default | Extra |
|-------|------|------|-----|---------|-------|
| id | int(11) | NO | PRI | NULL | auto_increment |
| research_id | int(11) | NO | MUL | NULL | |
| member_id | int(11) | NO | MUL | NULL | |
| role | enum('lead','co_author','contributor','advisor','reviewer') | YES | MUL | contributor | |
| contribution_percentage | decimal(5,2) | YES | | NULL | |
| joined_at | timestamp | NO | | current_timestamp() | |

**Purpose**: Track collaborators on research projects.

**Notes**:
- Unique constraint on (research_id, member_id)
- Created: December 16, 2025 (agent_dev_2)

## 15. research_files

| Field | Type | Null | Key | Default | Extra |
|-------|------|------|-----|---------|-------|
| id | int(11) | NO | PRI | NULL | auto_increment |
| research_id | int(11) | NO | MUL | NULL | |
| file_name | varchar(255) | NO | | NULL | |
| file_path | varchar(500) | NO | | NULL | |
| file_type | varchar(50) | YES | MUL | NULL | |
| file_size | bigint(20) | YES | | NULL | |
| version | varchar(50) | YES | | 1.0 | |
| uploaded_by | int(11) | NO | MUL | NULL | |
| uploaded_at | timestamp | NO | | current_timestamp() | |

**Purpose**: Store files associated with research projects.

**Notes**:
- Supports versioning
- Created: December 16, 2025 (agent_dev_2)

## 16. research_versions

| Field | Type | Null | Key | Default | Extra |
|-------|------|------|-----|---------|-------|
| id | int(11) | NO | PRI | NULL | auto_increment |
| research_id | int(11) | NO | MUL | NULL | |
| version_number | varchar(50) | NO | | NULL | |
| title | varchar(255) | NO | | NULL | |
| description | text | NO | | NULL | |
| changes_summary | text | YES | | NULL | |
| changed_by | int(11) | NO | MUL | NULL | |
| changed_at | timestamp | NO | MUL | current_timestamp() | |

**Purpose**: Track version history and changes for research projects.

**Notes**:
- Full change history tracking
- Created: December 16, 2025 (agent_dev_2)

## 17. research_comments

| Field | Type | Null | Key | Default | Extra |
|-------|------|------|-----|---------|-------|
| id | int(11) | NO | PRI | NULL | auto_increment |
| research_id | int(11) | NO | MUL | NULL | |
| member_id | int(11) | NO | MUL | NULL | |
| comment | text | NO | | NULL | |
| parent_comment_id | int(11) | YES | MUL | NULL | |
| created_at | timestamp | NO | MUL | current_timestamp() | |

**Purpose**: Comments and discussions on research projects.

**Notes**:
- Supports threaded comments (parent_comment_id)
- Created: December 16, 2025 (agent_dev_2)

---

## Future Tables (Planned)

Based on new requirements, the following tables may need to be created:

1. **member_qualifications** - Enhanced qualification tracking
   - Fields: member_id, qualification_type (Diploma/BSW/MSW/PhD), pdf_file, graduation_date

---

**Last Updated**: December 16, 2025  
**Database**: ethiosocialworks  
**Verified**: ✅ All tables verified via MySQL terminal  
**Email Automation**: ✅ Implemented (agent_dev_1)  
**Resources Enhancement**: ✅ Phase 1 completed (agent_dev_2)  
**Access Control**: ✅ Phase 2 completed (agent_dev_2)  
**Research System**: ✅ Phase 3 database created (agent_dev_2)

