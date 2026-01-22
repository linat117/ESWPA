# Admin Panel Documentation

## Overview
Admin panel for Ethio Social Works - a PHP-based content management system for managing events, members, and communications.

## Database Configuration
- **Connection File**: `admin/include/conn.php`
- **Auto-detection**: Supports both localhost (`ethiosocialworks`) and server (`ethiosdt_database`)
- **Detection Method**: Uses `$_SERVER['HTTP_HOST']` to determine environment

## Database Tables
- `user` - Admin authentication (username, password)
- `events` - Regular events (event_date, event_header, event_description, event_images)
- `upcoming` - Upcoming events (same structure as events)
- `registrations` - Member registrations (personal info, photos, payment_duration, created_at)
- `sent_emails` - Email tracking (recipient, subject, content, sent_at)
- `changelogs` - System changelog (version, change_date, type, title, description)

## Authentication
- **Login**: `admin/auth-login.php`
- **Register**: `admin/auth-register.php`
- **Handler**: `admin/include/auth.php`
- **Session**: Uses `$_SESSION['user_id']` and `$_SESSION['username']`
- **Protection**: All admin pages check session before rendering

## Core Features

### 1. Dashboard (`admin/index.php`)
- Statistics: Total events, upcoming events, registered members, active/expired subscribers, sent emails
- Charts: Subscription status (pie), monthly registrations (line), monthly events (bar)
- Lists: Latest upcoming events, latest regular events

### 2. Event Management
- **Add Events**: `admin/add_event.php` → `admin/include/send_event.php`
  - Supports regular and upcoming event types
  - Multiple image uploads (stored in `uploads/` directory)
  - Images stored as JSON array in database
- **Regular Events List**: `admin/regular_list.php`
- **Upcoming Events List**: `admin/upcoming_list.php`
- **Delete**: `admin/include/delete_event.php`

### 3. Member Management
- **Members List**: `admin/members_list.php` (DataTable with member details)
- **Delete Member**: `admin/include/delete_member.php`

### 4. Email System
- **Send Email**: `admin/send_email.php` → `admin/include/email_handler.php`
- **Bulk Email**: `admin/include/bulk_email_sender.php`
- **Sent Emails List**: `admin/sent_emails_list.php`
- Uses PHPMailer (vendor/phpmailer)

### 5. Reports
- **Report Page**: `admin/report.php`
- Date range filtering for events, registrations, emails
- Active/expired subscriber calculations

### 6. Changelog
- **List**: `admin/changelog_list.php`
- **Add**: `admin/add_changelog.php` → `admin/include/insert_changelog.php`

### 7. Future Enhancements
- **Page**: `admin/future_enhancement.php` (tracking future features)

### 8. Member ID Card Generation (Planned)
- **Member Panel**: Generate ID card after admin approval
- **Features**: Front/back design, QR code verification, PDF download
- **Admin**: Approve members, manage ID card settings

## File Uploads
- **Directory**: `uploads/` (root level)
- **Naming**: `{timestamp}_{original_filename}`
- **Allowed Types**: PNG, JPEG, JPG, WEBP
- **Storage**: Images stored as JSON array in database

## UI Framework
- **Theme**: Bootstrap-based admin template
- **DataTables**: For sortable/searchable tables
- **ApexCharts**: For dashboard visualizations
- **Icons**: RemixIcon, Bootstrap Icons

## Key Files Structure
```
admin/
├── include/
│   ├── conn.php          # Database connection
│   ├── auth.php          # Authentication handler
│   ├── send_event.php    # Event creation handler
│   ├── delete_event.php  # Event deletion
│   ├── delete_member.php # Member deletion
│   ├── email_handler.php # Email sending logic
│   ├── bulk_email_sender.php
│   └── insert_changelog.php
├── index.php             # Dashboard
├── sidebar.php           # Navigation menu
├── header.php            # HTML head & topbar
├── footer.php            # Footer
└── [feature pages]
```

## Session Management
- All admin pages require session check
- Redirects to `auth-login.php` if not authenticated
- Logout: `admin/logout.php`

## Development Notes
- Uses prepared statements for SQL queries (security)
- Password hashing: `password_hash()` / `password_verify()`
- File paths: Relative paths from admin directory
- Error handling: Check `error_log` files for debugging

## Common Tasks
- **Add new feature**: Create page in `admin/`, add handler in `admin/include/`, update `sidebar.php`
- **Modify database**: Update `conn.php` if needed, check table structure first
- **Add new table**: Update this README with table structure

