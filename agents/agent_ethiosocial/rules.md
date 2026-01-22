# Development Rules & Consistency Guidelines

## General Rules

### 1. Database Operations
- **ALWAYS** check `database_table_structure.md` before creating/modifying tables
- **ALWAYS** use prepared statements for SQL queries (security)
- **NEVER** use direct string concatenation in SQL queries
- **ALWAYS** verify table structure using terminal before assuming schema
- Use transactions for multi-step database operations

### 2. File Uploads
- **Upload Directory**: Use `uploads/` at root level (not inside swap folder)
- **Naming Convention**: `{timestamp}_{original_filename}`
- **Allowed Types**: PNG, JPEG, JPG, WEBP, PDF (for documents)
- **Storage**: Store relative paths in database (e.g., `uploads/members/photo.jpg`)
- **Validation**: Always validate file type and size before upload

### 3. Configuration Files
- **DO NOT** update `config.php` during deployments (contains client credentials)
- **Localhost Config**: `ethiosocialworks` database
- **Server Config**: `ethiosdt_database` database
- Auto-detection via `$_SERVER['HTTP_HOST']`

### 4. Authentication & Sessions
- **Admin Sessions**: `$_SESSION['user_id']` and `$_SESSION['username']`
- **Member Sessions**: Use separate session keys (e.g., `$_SESSION['member_id']`, `$_SESSION['membership_id']`)
- **Session Check**: All protected pages must check session before rendering
- **Password Hashing**: Always use `password_hash()` and `password_verify()`

### 5. Code Structure
- **Admin Pages**: Place in `admin/` directory
- **Public Pages**: Place in root directory
- **Include Files**: Place handlers in `admin/include/` or `include/`
- **Naming**: Use lowercase with underscores (e.g., `add_resource.php`, `email_handler.php`)

---

## File Organization

### Admin Panel Files
```
admin/
├── [feature].php          # Main page
├── [feature]_list.php     # List/view page
├── add_[feature].php       # Add/create form
├── include/
│   ├── [feature]_handler.php  # Processing logic
│   ├── delete_[feature].php   # Delete operations
│   └── [feature]_auth.php     # Authentication checks
```

### Public Pages
```
root/
├── [feature].php          # Public listing
├── [feature]-detail.php   # Single item view
├── include/
│   └── [feature]_handler.php
```

---

## Database Naming Conventions

### Tables
- Use lowercase, plural nouns (e.g., `registrations`, `sent_emails`)
- Use underscores for multi-word names (e.g., `user_roles`, `audit_logs`)

### Fields
- Use lowercase with underscores (e.g., `created_at`, `payment_duration`)
- Primary keys: `id` (auto_increment)
- Foreign keys: `{table}_id` (e.g., `user_id`, `member_id`)
- Timestamps: `created_at`, `updated_at`, `expiry_date`
- Boolean fields: Use `tinyint(1)` with 0/1 values

---

## Security Rules

### 1. Input Validation
- **ALWAYS** sanitize user input
- **ALWAYS** validate file uploads (type, size)
- **ALWAYS** use `htmlspecialchars()` for output
- **ALWAYS** validate email format
- **ALWAYS** check required fields

### 2. SQL Injection Prevention
- **NEVER** use `mysqli_query()` with string concatenation
- **ALWAYS** use prepared statements: `mysqli_prepare()`, `mysqli_stmt_bind_param()`
- **ALWAYS** validate data types before binding

### 3. File Upload Security
- **ALWAYS** validate file type (MIME type, not just extension)
- **ALWAYS** limit file size
- **ALWAYS** rename files (prevent overwrite attacks)
- **ALWAYS** store files outside web root when possible (or use proper permissions)

### 4. Access Control
- **ALWAYS** check user permissions before sensitive operations
- **ALWAYS** verify ownership before allowing edits/deletes
- **ALWAYS** log admin actions (audit trail)

---

## Email Rules

### Email Sending
- Use PHPMailer (already in `vendor/phpmailer`)
- Store email templates in `admin/include/email_templates/`
- Log all sent emails in `sent_emails` table
- Use no-reply email for automated messages

### Email Content
- Include membership ID in registration confirmations
- Include expiry dates in renewal reminders
- Use professional formatting

---

## UI/UX Rules

### Header Versions
- **Version 1.2 Headers**: Use `header-v1.2.php` for public pages and `member-header-v1.2.php` for member pages
- **Design Style**: Modern futuristic with glassmorphism effects
- **Color Scheme**: Purple/blue gradients (#667eea to #764ba2)
- **Responsive**: Mobile-first approach with breakpoints at 768px and 1024px
- **CSS Scoping**: All Version 1.2 styles use `-v1-2` suffix to avoid conflicts
- **Navigation**: Side-slide hamburger menu on mobile, horizontal menu on desktop

### Admin Panel
- Use existing Bootstrap admin theme
- Use DataTables for list views
- Use ApexCharts for visualizations
- Maintain consistent sidebar navigation
- Add new menu items to `admin/sidebar.php`

### Public Pages
- Use `header-v1.2.php` for all public-facing pages
- Maintain modern futuristic design theme
- Responsive design (mobile-friendly)
- Clear navigation structure
- Accessible forms and buttons
- Member dashboard link when logged in

### Member Pages
- Use `member-header-v1.2.php` for all member panel pages
- Mobile bottom navigation for quick access
- Side-slide menu for full navigation
- Profile dropdown on desktop
- Active page indicators

---

## Error Handling

### Database Errors
- Log errors to `error_log` file
- Display user-friendly error messages
- Never expose SQL errors to users
- Use try-catch blocks where appropriate

### File Upload Errors
- Validate before processing
- Provide clear error messages
- Handle disk space issues gracefully

---

## Testing Checklist

Before deploying any feature:
- [ ] Test on localhost first
- [ ] Verify database changes don't break existing functionality
- [ ] Test file uploads with various file types
- [ ] Test authentication/authorization
- [ ] Test form validation
- [ ] Test error handling
- [ ] Check mobile responsiveness
- [ ] Verify email sending works
- [ ] Test session management

---

## Code Quality

### PHP Best Practices
- Use meaningful variable names
- Add comments for complex logic
- Keep functions focused (single responsibility)
- Avoid deep nesting (max 3-4 levels)
- Use consistent indentation (4 spaces)

### Code Review Points
- Security vulnerabilities
- SQL injection risks
- XSS vulnerabilities
- File upload security
- Session management
- Error handling
- Code duplication

---

## Documentation

### Required Documentation
- Update `database_table_structure.md` when adding/modifying tables
- Update `agents/agent_ethiosocial/README.md` when adding features
- Update `task_follow_up.md` when completing tasks
- Document API endpoints if creating APIs
- Document configuration changes

### Code Comments
- Document complex algorithms
- Explain business logic
- Note any workarounds or temporary solutions
- Include TODO comments for future improvements

---

## Version Control

### Commit Messages
- Use clear, descriptive messages
- Reference task numbers if applicable
- Group related changes in single commit

### File Changes
- Test thoroughly before committing
- Don't commit sensitive data (passwords, API keys)
- Don't commit `error_log` files
- Don't commit temporary files

---

## Deployment Rules

### Pre-Deployment
- [ ] Test all features on localhost
- [ ] Verify database migrations
- [ ] Check file permissions
- [ ] Backup existing database
- [ ] Review error logs

### Deployment
- [ ] **DO NOT** update `config.php` (contains client credentials)
- [ ] Update database structure if needed
- [ ] Upload files maintaining directory structure
- [ ] Set proper file permissions (644 for files, 755 for directories)
- [ ] Test critical functionality after deployment

### Post-Deployment
- [ ] Verify all features work
- [ ] Check error logs
- [ ] Monitor for issues
- [ ] Update documentation

---

## Special Considerations

### Membership System
- First registration must be one year payment (enforce in validation)
- Membership ID format: `ESWPA-YYYY-XXXXX`
- Expiry calculation based on `payment_duration` and `created_at`
- Block access after expiry (check on login)

### Member Access
- Separate authentication from admin
- Limited permissions (view-only for most content)
- Can download resources
- Can view member-only content

### Reports
- Export to PDF/Excel
- Date range filtering
- Real-time data (not cached)
- Audit trail for all actions

### ID Card Generation
- **Prerequisites**: Member must be approved by admin
- **QR Code**: Generate unique verification code per member
- **QR URL Format**: `{website_url}/verify_id.php?code={verification_code}`
- **Dimensions**: Standard credit card size (85.6mm x 53.98mm)
- **Format**: PDF for download, high-resolution image for display
- **Company Signature**: Store in `uploads/company/signature.png`
- **Security**: QR code should contain encrypted/hashed verification code
- **Verification Page**: Public page that shows member info when QR code is scanned
- **Libraries**: Use TCPDF/FPDF for PDF, phpqrcode for QR codes
- **Template**: Create reusable HTML/CSS template for ID card design

---

**Last Updated**: December 16, 2025 - Added Version 1.2 header rules and guidelines

