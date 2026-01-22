# Task: Automated Email Marketing System

**Agent**: agent_dev_1  
**Priority**: High  
**Status**: 📋 Ready for Development  
**Estimated Time**: 8-10 hours

---

## Objective
Implement an automated email marketing system that sends emails automatically to subscribers, members, and other recipients when news, blogs, events, resources, and other content are created or published.

---

## Current Status

### ✅ Existing Infrastructure:
- **Email Handler**: `admin/include/email_handler.php` - PHPMailer integration
- **Email Functions**: `sendNewsletter()`, `sendBulkEmail()`
- **Event Newsletter**: `admin/include/send_event.php` - Can send newsletter when creating events (checkbox option)
- **Email Logging**: `sent_emails` table exists for tracking

### ❌ What's Missing:
- **Automated System**: No automatic email sending for news/blog/report posts
- **Automated System**: No automatic email sending for resources
- **Email Templates**: No template system for different content types
- **Recipient Management**: No system to choose recipients (subscribers, members, both, custom)
- **Settings**: No admin settings to enable/disable auto-emails
- **Scheduling**: No scheduling options (send immediately, send at specific time)
- **Conditional Logic**: No logic to only send when status = 'published'

---

## Requirements

### 1. Database Setup

**Create Migration File**: `Sql/migration_create_email_automation.sql`

```sql
-- Email Templates Table
CREATE TABLE `email_templates` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `subject` VARCHAR(255) NOT NULL,
  `body` TEXT NOT NULL,
  `content_type` ENUM('news', 'blog', 'report', 'event', 'resource', 'general') NOT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_content_type` (`content_type`),
  INDEX `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Email Automation Settings Table
CREATE TABLE `email_automation_settings` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `content_type` ENUM('news', 'blog', 'report', 'event', 'resource') NOT NULL,
  `enabled` TINYINT(1) NOT NULL DEFAULT 0,
  `send_to_subscribers` TINYINT(1) NOT NULL DEFAULT 1,
  `send_to_members` TINYINT(1) NOT NULL DEFAULT 1,
  `send_to_custom` TINYINT(1) NOT NULL DEFAULT 0,
  `custom_emails` TEXT NULL,
  `template_id` INT(11) NULL,
  `send_immediately` TINYINT(1) NOT NULL DEFAULT 1,
  `send_only_published` TINYINT(1) NOT NULL DEFAULT 1,
  `include_images` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_content_type` (`content_type`),
  FOREIGN KEY (`template_id`) REFERENCES `email_templates`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Email Automation Logs Table
CREATE TABLE `email_automation_logs` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `content_type` ENUM('news', 'blog', 'report', 'event', 'resource') NOT NULL,
  `content_id` INT(11) NOT NULL,
  `content_title` VARCHAR(255) NOT NULL,
  `recipients_count` INT(11) NOT NULL DEFAULT 0,
  `sent_count` INT(11) NOT NULL DEFAULT 0,
  `failed_count` INT(11) NOT NULL DEFAULT 0,
  `sent_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `sent_by` INT(11) NULL,
  `status` ENUM('success', 'failed', 'partial') NOT NULL,
  `error_message` TEXT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_content_type` (`content_type`),
  INDEX `idx_content_id` (`content_id`),
  INDEX `idx_sent_at` (`sent_at`),
  FOREIGN KEY (`sent_by`) REFERENCES `user`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert default settings for each content type
INSERT INTO `email_automation_settings` (`content_type`, `enabled`, `send_to_subscribers`, `send_to_members`, `send_only_published`) VALUES
('news', 0, 1, 1, 1),
('blog', 0, 1, 1, 1),
('report', 0, 1, 1, 1),
('event', 1, 1, 1, 0), -- Event already has manual option
('resource', 0, 1, 1, 0);

-- Insert default email templates
INSERT INTO `email_templates` (`name`, `subject`, `body`, `content_type`) VALUES
('News Template', 'New News: {TITLE}', '<h1>{TITLE}</h1><p>{CONTENT}</p><p><a href="{LINK}">Read more</a></p>', 'news'),
('Blog Template', 'New Blog Post: {TITLE}', '<h1>{TITLE}</h1><p>{CONTENT}</p><p><a href="{LINK}">Read more</a></p>', 'blog'),
('Report Template', 'New Report: {TITLE}', '<h1>{TITLE}</h1><p>{CONTENT}</p><p><a href="{LINK}">Read more</a></p>', 'report'),
('Event Template', 'New Event: {TITLE}', '<h1>{TITLE}</h1><p>Date: {DATE}</p><p>{CONTENT}</p><p><a href="{LINK}">View details</a></p>', 'event'),
('Resource Template', 'New Resource Available: {TITLE}', '<h1>{TITLE}</h1><p>{CONTENT}</p><p><a href="{LINK}">Download</a></p>', 'resource');
```

---

### 2. Backend Components

#### A. Email Automation Handler (`admin/include/email_automation.php`)

**Functions**:
- `getAutomationSettings($content_type)` - Get automation settings for content type
- `getRecipients($content_type, $settings)` - Get recipient list based on settings
  - From `email_subscribers` table (status = 'active')
  - From `registrations` table (active members)
  - Custom email list
- `generateEmailContent($content_type, $content_data, $template_id)` - Generate email content from template
- `sendAutomatedEmail($content_type, $content_id, $content_data)` - Main function to send automated email
- `logAutomation($content_type, $content_id, $title, $sent_count, $failed_count, $status, $sent_by)` - Log automation

**Template Variables**:
- `{TITLE}` - Content title
- `{CONTENT}` - Content body/excerpt
- `{AUTHOR}` - Author name
- `{DATE}` - Published/event date
- `{LINK}` - Direct link to content
- `{TYPE}` - Content type (News, Blog, Report, etc.)
- `{IMAGE}` - Featured image (if available)

#### B. Enhanced Email Handler (`admin/include/email_handler.php`)

**Add New Functions**:
- `sendAutomatedBulkEmail($subject, $body, $recipients, $content_type, $content_id)` - Enhanced bulk email with logging
- `formatEmailTemplate($template, $variables)` - Template variable replacement

---

### 3. Integration Points

#### A. News/Blog/Report Creation (`admin/include/manage_news.php`)

**Integration Logic**:
```php
// After successful post creation/update
if ($status === 'published' && $action === 'create') {
    // Check if automation is enabled for this content type
    require_once 'email_automation.php';
    sendAutomatedEmail($type, $new_post_id, [
        'title' => $title,
        'content' => $content,
        'author' => $author,
        'published_date' => $published_date,
        'images' => $uploadedImages,
        'link' => getContentLink($type, $new_post_id)
    ]);
}
```

#### B. Event Creation (`admin/include/send_event.php`)

**Enhancement**:
- Keep existing checkbox option (manual)
- Add automatic sending based on automation settings
- If automation enabled AND checkbox checked → send
- Log both manual and automated sends

#### C. Resource Upload (`admin/include/upload_resource.php`)

**Integration Logic**:
```php
// After successful resource upload
require_once 'email_automation.php';
sendAutomatedEmail('resource', $resource_id, [
    'title' => $resource_title,
    'description' => $resource_description,
    'file_url' => $file_url,
    'link' => getResourceLink($resource_id)
]);
```

---

### 4. Admin Panel Components

#### A. Email Automation Settings (`admin/email_automation_settings.php`)

**Features**:
- Settings for each content type (News, Blog, Report, Event, Resource)
- Enable/disable automation per content type
- Choose recipients:
  - ☑ Send to Email Subscribers
  - ☑ Send to Members (from registrations)
  - ☑ Send to Custom Email List
  - Custom email input (comma-separated)
- Template selection dropdown
- Options:
  - ☑ Send only when status = 'published'
  - ☑ Send immediately or schedule
  - ☑ Include images in email
- Save settings button

#### B. Email Templates Management (`admin/email_templates.php`)

**Features**:
- List all templates (DataTable)
- Create new template
- Edit existing template
- Delete template (with confirmation)
- Preview template
- Template variables help/guide
- Activate/deactivate templates

#### C. Email Automation Logs (`admin/email_automation_logs.php`)

**Features**:
- View all automation logs (DataTable)
- Filter by content type, date range, status
- View details (recipients, success/failure counts)
- Export logs
- Retry failed sends
- View error messages

---

### 5. Email Template System

#### Template Structure:
- HTML email templates
- Variable placeholders: `{VARIABLE_NAME}`
- Responsive design
- Inline CSS (for email client compatibility)
- Header and footer included

#### Default Templates:

**News Template**:
```html
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h1 style="color: #667eea;">{TITLE}</h1>
        <p style="color: #666; font-size: 14px;">Published: {DATE} | By: {AUTHOR}</p>
        <div style="margin: 20px 0;">
            {IMAGE}
            {CONTENT}
        </div>
        <a href="{LINK}" style="display: inline-block; background: #667eea; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">Read More</a>
        <hr style="margin: 30px 0; border: none; border-top: 1px solid #eee;">
        <p style="font-size: 12px; color: #999; text-align: center;">
            You received this email because you subscribed to our newsletter.<br>
            <a href="{UNSUBSCRIBE_LINK}">Unsubscribe</a>
        </p>
    </div>
</body>
</html>
```

---

## Implementation Steps

### Step 1: Database Setup
1. Create migration SQL file
2. Run migration on database
3. Verify tables created
4. Verify default data inserted

### Step 2: Email Automation Handler
1. Create `admin/include/email_automation.php`
2. Implement all required functions
3. Test recipient retrieval
4. Test template generation
5. Test email sending

### Step 3: Template System
1. Create default email templates
2. Implement template variable replacement
3. Create template preview functionality
4. Test template rendering

### Step 4: Integration with Content Creation
1. Integrate with `admin/include/manage_news.php`
2. Integrate with `admin/include/send_event.php` (enhance)
3. Integrate with `admin/include/upload_resource.php`
4. Test automatic sending for each content type

### Step 5: Admin Panel
1. Create `admin/email_automation_settings.php`
2. Create `admin/email_templates.php`
3. Create `admin/email_automation_logs.php`
4. Add menu items to sidebar
5. Test all admin functionality

### Step 6: Testing
1. Test automation for news posts
2. Test automation for blog posts
3. Test automation for reports
4. Test automation for events
5. Test automation for resources
6. Test recipient selection (subscribers, members, custom)
7. Test template rendering
8. Test logging
9. Test error handling

---

## Files to Create

1. `Sql/migration_create_email_automation.sql`
2. `admin/include/email_automation.php`
3. `admin/email_automation_settings.php`
4. `admin/email_templates.php`
5. `admin/add_email_template.php`
6. `admin/edit_email_template.php`
7. `admin/email_automation_logs.php`
8. `admin/include/save_email_template.php`
9. `admin/include/save_automation_settings.php`

## Files to Modify

1. `admin/include/manage_news.php` - Add automation trigger
2. `admin/include/send_event.php` - Enhance with automation
3. `admin/include/upload_resource.php` - Add automation trigger
4. `admin/include/email_handler.php` - Add template functions
5. `admin/sidebar.php` - Add menu items
6. `database_table_structure.md` - Document new tables

---

## Configuration & Settings

### Default Settings:
- **News**: Disabled by default (admin can enable)
- **Blog**: Disabled by default (admin can enable)
- **Report**: Disabled by default (admin can enable)
- **Event**: Enabled (keep existing behavior, add automation option)
- **Resource**: Disabled by default (admin can enable)

### Admin Control:
- Enable/disable per content type
- Choose recipients per content type
- Select template per content type
- Customize sending behavior

---

## Recipient Selection Logic

### Email Subscribers:
```php
SELECT email, name FROM email_subscribers 
WHERE status = 'active'
```

### Members (from registrations):
```php
SELECT DISTINCT email, fullname as name FROM registrations 
WHERE email IS NOT NULL AND email != '' 
AND status = 'active' AND expiry_date >= CURDATE()
```

### Custom Email List:
- Parse comma-separated emails from settings
- Validate each email
- Add to recipient list

### Combined Recipients:
- Merge all selected recipient sources
- Remove duplicates
- Validate all emails
- Return final list

---

## Template Variables Reference

| Variable | Description | Example |
|----------|-------------|---------|
| `{TITLE}` | Content title | "New Blog Post" |
| `{CONTENT}` | Content body/excerpt | "Full content or excerpt..." |
| `{AUTHOR}` | Author name | "John Doe" |
| `{DATE}` | Published/event date | "2025-12-16" |
| `{LINK}` | Direct link to content | "https://site.com/news-detail.php?id=123" |
| `{TYPE}` | Content type | "News", "Blog", "Report" |
| `{IMAGE}` | Featured image HTML | `<img src="...">` |
| `{EXCERPT}` | Content excerpt (first 200 chars) | "Short preview..." |
| `{UNSUBSCRIBE_LINK}` | Unsubscribe link | "https://site.com/unsubscribe.php?token=..." |

---

## Email Content Generation

### Content Excerpt:
- Generate excerpt from full content (first 200-300 characters)
- Strip HTML tags for excerpt
- Add "..." if truncated
- Use excerpt in email, full content on website

### Image Handling:
- Use first image from content if available
- Generate thumbnail (optional)
- Include image in email as HTML `<img>` tag
- Provide alt text

### Link Generation:
- News/Blog/Report: `news-detail.php?id={id}`
- Events: `events.php` or `events.php#event-{id}`
- Resources: `resources.php` or direct download link

---

## Error Handling & Logging

### Error Handling:
- Try-catch blocks around email sending
- Continue sending even if one email fails
- Log individual failures
- Return success/failure counts

### Logging:
- Log every automation attempt
- Log success/failure counts
- Log error messages
- Log recipient count
- Track who triggered (admin user)

### Retry Logic (Optional):
- Retry failed sends (up to 3 times)
- Queue failed emails
- Admin can manually retry from logs

---

## Testing Checklist

- [ ] Database tables created successfully
- [ ] Default templates inserted
- [ ] Default settings created
- [ ] News automation works (when enabled)
- [ ] Blog automation works (when enabled)
- [ ] Report automation works (when enabled)
- [ ] Event automation works (enhanced)
- [ ] Resource automation works (when enabled)
- [ ] Recipient selection works (subscribers)
- [ ] Recipient selection works (members)
- [ ] Recipient selection works (custom)
- [ ] Template variables replaced correctly
- [ ] Email content formatted correctly
- [ ] Images included in emails (if enabled)
- [ ] Links work correctly
- [ ] Automation logs created
- [ ] Admin settings save correctly
- [ ] Templates can be created/edited/deleted
- [ ] Automation can be enabled/disabled
- [ ] Only published content triggers (if enabled)
- [ ] Error handling works
- [ ] No duplicate emails sent

---

## Security Considerations

- Validate all email addresses
- Sanitize template content
- Prevent email injection
- Rate limiting (prevent spam)
- CSRF protection on settings forms
- Admin authentication required
- Input validation and sanitization

---

## Performance Considerations

- Batch email sending (send in chunks)
- Use BCC for multiple recipients
- Queue system for large lists (optional)
- Asynchronous sending (optional, for future)
- Limit email size (optimize images)

---

## Future Enhancements (Optional)

1. **Scheduled Sending**: Schedule emails for specific date/time
2. **A/B Testing**: Test different templates
3. **Email Queue**: Queue system for reliable delivery
4. **Personalization**: Personalized emails per recipient
5. **Analytics**: Open rates, click rates tracking
6. **Segmentation**: Send to specific subscriber segments
7. **Conditional Logic**: Send based on member criteria

---

## Notes

- Start with simple automation (send immediately when published)
- Can enhance with scheduling later
- Use existing email infrastructure
- Follow existing code patterns
- Test thoroughly with actual emails
- Document any configuration needed

---

**Last Updated**: December 16, 2025

