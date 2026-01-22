# Task: Email Subscription Popup System

**Agent**: agent_dev_1  
**Priority**: High  
**Status**: 📋 Ready for Development  
**Estimated Time**: 4-6 hours

---

## Objective
Implement a public-facing email subscription popup system that allows website visitors to subscribe to newsletters and updates.

---

## Current Status

### ✅ Existing Infrastructure:
- PHPMailer installed and configured (`vendor/phpmailer`)
- Email handler exists (`admin/include/email_handler.php`)
- SMTP configured (mail.ethiosocialworker.org)
- Newsletter sending functionality exists (admin can send newsletters)

### ❌ Missing Components:
- Public subscription form/popup
- Email subscribers database table
- Subscription management in admin panel
- Unsubscribe functionality

---

## Requirements

### 1. Database Setup

**Create Migration File**: `Sql/migration_create_email_subscribers.sql`

```sql
CREATE TABLE `email_subscribers` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `email` VARCHAR(191) NOT NULL UNIQUE,
  `name` VARCHAR(255) NULL,
  `status` ENUM('active', 'unsubscribed', 'bounced') NOT NULL DEFAULT 'active',
  `subscribed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `unsubscribed_at` TIMESTAMP NULL,
  `source` VARCHAR(50) DEFAULT 'popup',
  `ip_address` VARCHAR(45) NULL,
  `user_agent` TEXT NULL,
  `unsubscribe_token` VARCHAR(255) NULL UNIQUE,
  PRIMARY KEY (`id`),
  INDEX `idx_email` (`email`),
  INDEX `idx_status` (`status`),
  INDEX `idx_unsubscribe_token` (`unsubscribe_token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

---

### 2. Frontend Components

#### A. Subscription Popup (`include/subscription_popup.php`)

**Features**:
- Modal popup with email subscription form
- Cookie-based display control (show once per session/day)
- Delay option (show after X seconds)
- Responsive design
- Close button and overlay click
- Success/error message display
- Loading state

**Form Fields**:
- Email (required, validated)
- Name (optional)
- Privacy policy checkbox (required)
- Honeypot field (spam protection)

**Styling**:
- Modern, attractive design
- Matches site theme (purple/blue gradients)
- Mobile-responsive
- Smooth animations

#### B. Footer Subscription Form (Optional)
- Alternative subscription form in footer
- Same validation as popup
- Less intrusive option

---

### 3. Backend Components

#### A. Subscription Handler (`include/subscribe_handler.php`)

**Functions**:
- `processSubscription($email, $name, $source, $ip, $user_agent)`
  - Validate email format
  - Check for duplicates
  - Generate unsubscribe token
  - Insert into database
  - Send confirmation email
  - Return success/error response

- `sendSubscriptionConfirmation($email, $name, $unsubscribe_token)`
  - Send welcome email
  - Include unsubscribe link

- `unsubscribe($token)`
  - Validate token
  - Update subscriber status
  - Return success/error

**Security**:
- Email validation (filter_var)
- Honeypot spam protection
- Rate limiting (prevent spam)
- Prepared statements (SQL injection prevention)
- CSRF protection (optional)

#### B. AJAX Endpoint (`include/subscribe_ajax.php`)

**Purpose**: Handle AJAX subscription requests

**Response Format**:
```json
{
  "success": true/false,
  "message": "Success/Error message",
  "data": {}
}
```

---

### 4. Admin Panel Components

#### A. Subscribers List (`admin/subscribers_list.php`)

**Features**:
- DataTable with search/filter
- Columns: Email, Name, Status, Subscribed Date, Source, Actions
- Export to CSV
- Bulk actions (unsubscribe, delete)
- Statistics (total, active, unsubscribed)

#### B. Unsubscribe Handler (`admin/unsubscribe.php`)
- Manual unsubscribe by admin
- Bulk unsubscribe
- Unsubscribe reason tracking

---

### 5. Public Unsubscribe Page (`unsubscribe.php`)

**Features**:
- Unsubscribe form (email or token)
- Confirmation message
- Resubscribe option

---

## Implementation Steps

### Step 1: Database Setup
1. Create migration SQL file
2. Run migration on database
3. Verify table creation

### Step 2: Backend Handler
1. Create `include/subscribe_handler.php`
2. Implement subscription processing
3. Implement unsubscribe functionality
4. Test with sample data

### Step 3: Frontend Popup
1. Create `include/subscription_popup.php`
2. Add HTML/CSS/JavaScript
3. Implement cookie-based display control
4. Add AJAX form submission
5. Test popup display and submission

### Step 4: Integration
1. Include popup in `index.php`
2. Optionally add to other public pages
3. Add footer form (optional)
4. Test end-to-end flow

### Step 5: Admin Panel
1. Create `admin/subscribers_list.php`
2. Add menu item to sidebar
3. Implement export functionality
4. Test admin features

### Step 6: Testing
1. Test subscription flow
2. Test duplicate email handling
3. Test unsubscribe flow
4. Test email delivery
5. Test mobile responsiveness
6. Test spam protection

---

## Files to Create

1. `Sql/migration_create_email_subscribers.sql`
2. `include/subscribe_handler.php`
3. `include/subscribe_ajax.php`
4. `include/subscription_popup.php`
5. `admin/subscribers_list.php`
6. `admin/unsubscribe.php`
7. `unsubscribe.php`

## Files to Modify

1. `index.php` - Include subscription popup
2. `footer.php` - Add footer subscription form (optional)
3. `admin/sidebar.php` - Add subscribers menu item
4. `admin/include/email_handler.php` - Use subscribers list for newsletters

---

## Design Guidelines

### Popup Design:
- Modern, clean design
- Purple/blue gradient theme (#667eea to #764ba2)
- Smooth animations
- Mobile-first responsive
- Accessible (ARIA labels, keyboard navigation)

### Form Design:
- Clear labels
- Error messages below fields
- Success message with icon
- Loading spinner on submit

---

## Testing Checklist

- [ ] Popup displays on first visit
- [ ] Popup doesn't show again (cookie works)
- [ ] Email validation works
- [ ] Duplicate email handling
- [ ] Subscription success message
- [ ] Confirmation email sent
- [ ] Unsubscribe link works
- [ ] Admin can view subscribers
- [ ] Admin can export subscribers
- [ ] Mobile responsive
- [ ] Spam protection works
- [ ] No JavaScript errors

---

## Notes

- Use existing PHPMailer configuration
- Follow existing code style and patterns
- Don't break existing functionality
- Test thoroughly before deployment
- Document any configuration needed

---

**Last Updated**: December 16, 2025

