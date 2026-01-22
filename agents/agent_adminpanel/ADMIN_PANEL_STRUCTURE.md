# Admin Panel Structure Documentation

**Agent**: agent_adminpanel  
**Created**: December 25, 2025  
**Purpose**: Detailed documentation of admin panel structure

---

## 📁 Directory Structure

```
admin/
├── assets/                      # Admin theme assets
│   ├── css/                     # Stylesheets
│   ├── js/                      # JavaScript files
│   ├── images/                  # Images
│   └── vendor/                  # Third-party libraries
│
├── include/                     # Backend handlers and utilities
│   ├── conn.php                 # Database connection
│   ├── auth.php                 # Authentication
│   ├── audit_log.php            # Audit logging
│   └── [feature]_handler.php    # Feature-specific handlers
│
├── index.php                    # Dashboard
├── sidebar.php                  # Navigation sidebar
├── header.php                   # Header with topbar
├── footer.php                   # Footer
│
├── auth-login.php              # Login page
├── auth-register.php           # Registration page
├── auth-forgotpw.php           # Password reset
├── admin-login-handler.php     # Login handler
├── logout.php                  # Logout handler
│
└── [feature]_*.php             # Feature pages
```

---

## 🔐 Authentication System

### Files:
- `auth-login.php` - Login page
- `auth-register.php` - Registration page
- `auth-forgotpw.php` - Password reset
- `admin-login-handler.php` - Login processing
- `include/auth.php` - Authentication logic
- `logout.php` - Logout handler

### Session Variables:
- `$_SESSION['user_id']` - Admin user ID
- `$_SESSION['username']` - Admin username

### Database Table:
- `user` - Admin users table
  - `id` (INT, PK)
  - `username` (VARCHAR(50), UNIQUE)
  - `password` (VARCHAR(255), hashed)
  - `registration_date` (TIMESTAMP)

---

## 📊 Dashboard (`index.php`)

### Features:
- Statistics cards:
  - Total Events
  - Upcoming Events
  - Registered Members
  - Active Subscribers
  - Expired Subscribers
  - Total Sent Emails
- Charts:
  - Subscription Status (Pie Chart)
  - Monthly New Members (Line Chart)
  - Monthly Events Created (Bar Chart)
- Lists:
  - Latest Upcoming Events (Top 5)
  - Latest Regular Events (Top 5)

### Technologies:
- ApexCharts for visualizations
- PHP/MySQL for data
- Bootstrap for layout

---

## 👥 Member Management

### Pages:
- `members_list.php` - Members listing with DataTables
- `edit_member.php` - Edit member information
- `renew_membership.php` - Renew membership
- `member_analytics.php` - Member analytics
- `member_notes.php` - Member notes

### Handlers:
- `include/approve_member.php` - Approve member
- `include/update_member.php` - Update member
- `include/delete_member.php` - Delete member

### Database Tables:
- `registrations` - Member registration data
- `member_access` - Member authentication (if exists)

### Features:
- Member listing with search and filters
- Member approval workflow
- Membership renewal
- Member editing
- Member analytics
- Member notes

---

## 📅 Events Management

### Pages:
- `add_event.php` - Add new event
- `regular_list.php` - Regular/past events list
- `upcoming_list.php` - Upcoming events list

### Handlers:
- `include/delete_event.php` - Delete event
- `include/send_event.php` - Send event (email)

### Database Tables:
- `events` - Regular/past events
- `upcoming` - Upcoming events

### Common Fields:
- `id` (INT, PK)
- `event_date` (DATE)
- `event_header` (VARCHAR/TEXT)
- `event_description` (TEXT)
- `event_images` (TEXT, JSON array)
- `created_at` (TIMESTAMP)

---

## 📚 Resources Management

### Pages:
- `resources_list.php` - Resources listing with DataTables
- `add_resource.php` - Add new resource
- `edit_resource.php` - Edit resource

### Handlers:
- `include/upload_resource.php` - Upload resource handler
- `include/update_resource.php` - Update resource handler
- `include/delete_resource.php` - Delete resource handler
- `include/ajax_delete_resource.php` - AJAX delete handler
- `include/bulk_resource_operations.php` - Bulk operations handler

### Database Table:
- `resources` - Resources table

### Features:
- Full CRUD operations
- Bulk operations (activate, deactivate, archive, delete)
- Advanced filtering (section, status, access_level, date range)
- Search functionality
- File upload (PDF)
- Access level management
- Status management (active, inactive, archived)
- Tags system
- Featured resources
- Download count tracking
- Access control integration
- Email automation integration

---

## 🔬 Research Management

### Pages:
- `research_list.php` - Research projects listing
- `add_research.php` - Add new research
- `edit_research.php` - Edit research
- `research_details.php` - Research details view
- `research_collaborators.php` - Collaborator management

### Handlers:
- `include/research_handler.php` - Research CRUD operations
- `include/collaborator_handler.php` - Collaborator management

### Database Tables:
- `research_projects` - Research projects
- `research_collaborators` - Research collaborators
- `research_files` - Research files
- `research_versions` - Research version history
- `research_comments` - Research comments

### Features:
- Research CRUD operations
- Collaborator management
- File upload and management
- Version control
- Status workflow (draft → in_progress → completed → published)
- Email automation integration

---

## 📧 Email System

### Pages:
- `send_email.php` - Send email to members/subscribers
- `subscribers_list.php` - Email subscribers list
- `sent_emails_list.php` - Sent emails history
- `email_templates.php` - Email templates management
- `email_automation_settings.php` - Email automation settings
- `email_automation_logs.php` - Automation logs

### Handlers:
- `include/email_handler.php` - Email sending
- `include/email_automation.php` - Email automation logic
- `include/save_email_template.php` - Save email template
- `include/save_automation_settings.php` - Save automation settings
- `include/delete_email_template.php` - Delete email template
- `include/bulk_email_sender.php` - Bulk email sending
- `include/export_subscribers.php` - Export subscribers
- `include/unsubscribe_subscriber.php` - Unsubscribe handler
- `include/test_automation_email.php` - Test automation email
- `include/insert_default_templates.php` - Insert default templates

### Database Tables:
- `email_subscribers` - Email subscribers
- `sent_emails` - Sent emails log
- `email_templates` - Email templates
- `email_automation_settings` - Automation settings
- `email_automation_logs` - Automation logs

### Features:
- Send email to members/subscribers
- Email templates (CRUD)
- Email automation (news, events, resources, research)
- Subscriber management
- Unsubscribe functionality
- Bulk email sending
- Email logging
- Export subscribers

---

## 🔐 Access Control

### Pages:
- `membership_packages.php` - Membership packages management
- `badge_permissions.php` - Badge permissions management
- `special_permissions.php` - Special permissions management
- `access_logs.php` - Access logs viewer

### Database Tables:
- `membership_packages` - Membership packages
- `badges` - Badges
- `badge_permissions` - Badge permissions mapping
- `special_permissions` - Special permissions
- `access_logs` - Access attempt logs

### Features:
- Package-based access control
- Badge-based permissions
- Special permission grants
- Access logging and statistics
- Grant/deny tracking

### Functions:
- `include/access_control.php` - Access control functions (in root include/)

---

## 📈 Reports & Analytics

### Pages:
- `reports_dashboard.php` - Reports dashboard
- `report.php` - Report viewer

### Handlers:
- `include/export_report.php` - Export report
- `include/report_content_members.php` - Members report content
- `include/report_content_daily.php` - Daily report content
- `include/report_content_monthly.php` - Monthly report content
- `include/report_content_finance.php` - Finance report content
- `include/report_content_payment.php` - Payment report content
- `include/report_content_audit.php` - Audit report content

### Features:
- Member reports
- Daily reports
- Monthly reports
- Finance reports
- Payment reports
- Audit reports
- Export to PDF/Excel

---

## ⚙️ Settings

### Pages:
- `settings.php` - System settings
- `settings_users.php` - Admin user management
- `settings_backup.php` - Backup & restore
- `settings_sync.php` - Data sync

### Database Table:
- `settings` - System settings (key-value pairs)
- `user` - Admin users

### Features:
- System configuration
- Admin user management
- Database backup and restore
- Data synchronization

---

## 🤖 AI Management

### Pages:
- `ai_settings.php` - AI settings
- `ai_queue.php` - Processing queue

### Features:
- AI configuration
- Processing queue management
- AI preparation infrastructure

---

## 📦 Media Library

### Pages:
- `media_library.php` - Media library
- `media_upload_handler.php` - Media upload handler

### Features:
- Media upload
- Media library browsing
- Media management

---

## 📰 News/Blog Management

### Pages:
- `news_list.php` - News listing
- `add_news.php` - Add news
- `edit_news.php` - Edit news

### Handlers:
- `include/manage_news.php` - News CRUD operations
- `include/delete_news.php` - Delete news

### Database Table:
- `news_media` - News/blog posts

### Features:
- News CRUD operations
- Email automation integration

---

## 📝 Changelog Management

### Pages:
- `changelog_list.php` - Changelog listing
- `add_changelog.php` - Add changelog entry

### Handlers:
- `include/insert_changelog.php` - Insert changelog

### Database Table:
- `changelogs` - System changelog

### Fields:
- `id` (INT, PK)
- `version` (VARCHAR(50))
- `change_date` (DATE)
- `type` (VARCHAR(100))
- `title` (VARCHAR(255))
- `description` (TEXT)

---

## 🔧 Common Include Files

### Core Files:
- `include/conn.php` - Database connection (auto-detects environment)
- `include/auth.php` - Authentication logic
- `include/audit_log.php` - Audit logging

### Utility Files:
- Various handler files for specific features

---

## 🎨 Frontend Structure

### CSS:
- `assets/css/app.min.css` - Main stylesheet
- `assets/css/icons.min.css` - Icons
- Bootstrap-based admin theme

### JavaScript:
- `assets/js/app.min.js` - Main JavaScript
- `assets/js/config.js` - Configuration
- `assets/js/vendor.min.js` - Vendor libraries
- `assets/js/pages/` - Page-specific scripts

### Libraries:
- Bootstrap (via admin theme)
- DataTables (for tables)
- ApexCharts (for charts)
- jQuery (dependency)

---

## 🔒 Security Features

### Current:
- Session-based authentication
- Prepared statements (mostly)
- Input validation (partial)
- File upload validation

### Needs Enhancement:
- CSRF protection
- Rate limiting
- Enhanced input validation
- Better error handling
- Security audit

---

## 📱 Responsive Design

### Current State:
- Partial responsive design
- Bootstrap-based layout
- Some mobile support

### Needs Improvement:
- Better mobile navigation
- Improved tablet support
- Mobile-first approach
- Touch-friendly interfaces

---

## 🚀 Performance Considerations

### Current:
- Standard PHP/MySQL
- No caching layer
- Direct database queries

### Needs Optimization:
- Query optimization
- Caching implementation
- Lazy loading
- Asset optimization
- Database indexing review

---

## 📋 Navigation Structure

See `admin/sidebar.php` for complete navigation structure.

### Main Sections:
1. Dashboard
2. Events Management
3. Member Management
4. Resource & Research
5. AI Management
6. Content Management
7. Communications
8. Reports & Analytics
9. Access Control
10. System

---

**Last Updated**: December 25, 2025  
**Next Review**: After initial upgrades

