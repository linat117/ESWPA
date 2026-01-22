# System Scope - All Panels Overview

**Document Type**: System Architecture & Scope  
**Purpose**: Define all system panels and their responsibilities  
**Date**: December 17, 2025

---

## 🏗️ System Architecture Overview

The ESWPA platform consists of four main panel types, each with specific responsibilities and user access levels:

1. **Public Landing Pages** - Open access to all visitors
2. **Member Panel** - Authenticated member access
3. **Admin Panel** - Administrative access
4. **User Panel** - General user access (if different from member)

---

## 1. 🌐 Public Landing Pages

### Current Status
- Homepage (`index.php`)
- About page (`about.php`)
- Membership registration (`sign-up.php`)
- Contact page (`contact.php`)
- Events page (`events.php`)
- News page (`news.php`)
- Resources page (`resources.php`)
- Membership page (`membership.php`)

### Current Features
- Public information display
- Membership registration form
- Event listings
- News/blog listings
- Resource listings (downloadable)

### Required Updates

#### 1.1 Enhanced Membership Registration
- Add qualification fields (Diploma, BSW, MSW, PhD)
- Qualification certificate upload (PDF)
- Graduation date field
- Payment slip upload with validation
- Submit button disabled until payment slip attached
- Auto-generated membership ID display

#### 1.2 Resources Section
- Resource browsing interface
- Resource download functionality
- Resource filtering by section
- Resource detail view

#### 1.3 News & Media Section
- News/blog listing
- Report listings
- News detail pages
- Media gallery integration

#### 1.4 ID Card Verification (New)
- QR code verification interface
- Public verification system for member IDs

---

## 2. 👤 Member Panel

### Current Status
- Member login (`member-login.php`)
- Member dashboard (`member-dashboard.php`)
- Member header (`member-header.php`, `member-header-v1.2.php`)
- ID card generation (`member-generate-id-card.php`, `member-id-card.php`)
- Password management (`member-forgot-password.php`, `member-reset-password.php`, `member-set-password.php`)
- Member logout (`member-logout.php`)

### Current Features
- Member authentication
- Basic dashboard
- ID card generation (partial)

### Required Updates

#### 2.1 Access Control
- Members-only access enforcement
- Membership expiry checking
- Automatic access blocking after expiry
- Renewal workflow

#### 2.2 Enhanced Dashboard
- Personal information display
- Membership status display
- Membership expiry date warning
- Quick action buttons
- Recent activity summary

#### 2.3 Resources Access
- Resources browsing interface
- Resource download functionality
- Download history
- Favorite resources

#### 2.4 News & Media Access
- News/blog viewing
- Report viewing
- News notifications

#### 2.5 ID Card Management
- Generate ID card (after approval)
- View ID card
- Download ID card (PDF)
- Print ID card
- QR code display

#### 2.6 Profile Management
- Update profile information
- Upload/change photo
- Update qualifications
- Change password

#### 2.7 Notifications
- Registration status notifications
- Approval notifications
- Membership expiry reminders
- News and event notifications

---

## 3. 🔐 Admin Panel

### Current Status
Located in `/admin` directory with comprehensive features:
- Dashboard (`index.php`)
- Member management (`members_list.php`, `regular_list.php`, `upcoming_list.php`)
- Event management (`add_event.php`, `regular_list.php`, `upcoming_list.php`)
- News management (`add_news.php`, `news_list.php`)
- Resource management (`add_resource.php`, `resources_list.php`)
- Email management (`send_email.php`, `sent_emails_list.php`)
- Reports (`report.php`, `reports_dashboard.php`)
- Settings (`settings.php`, `settings_users.php`, `settings_backup.php`, `settings_sync.php`)
- Changelog (`add_changelog.php`, `changelog_list.php`)

### Current Features
- Admin authentication
- Member list management
- Event CRUD operations
- News CRUD operations
- Resource CRUD operations (basic)
- Email sending
- Basic reports
- Basic settings

### Required Updates

#### 3.1 Enhanced Resource Management
- Full CRUD operations for resources
- Section/category management
- Author field management
- Publication date management
- PDF file upload and management
- Resource organization and sorting

#### 3.2 Enhanced News & Media Management
- News/blog CRUD operations
- Report CRUD operations
- Content publishing workflow
- Media/image management
- Content categories

#### 3.3 Member Approval System
- Member registration approval interface
- Approval/rejection workflow
- Bulk approval operations
- Approval history tracking
- Member status management

#### 3.4 Enhanced Member Management
- View member details with qualifications
- Qualification certificate management
- Membership ID management
- Membership expiry management
- Renewal processing
- Member access control

#### 3.5 Comprehensive Reports System
- Daily reports generation
- Monthly reports generation
- Payment reports
- Audit log reports
- Finance reports
- Members reports
- Custom report builder
- Report export (PDF, Excel, CSV)
- Scheduled reports

#### 3.6 Advanced Settings System
- **Data Sync Settings**:
  - Local to remote sync configuration
  - Database sync settings
  - File sync settings
  - Sync scheduling

- **User Management**:
  - Create/edit/delete admin users
  - User activity tracking
  - User session management

- **Role & Permissions**:
  - Super Admin role management
  - Admin Users role management
  - Permission assignment
  - Role-based access control

- **Backup & Restore**:
  - Database backup configuration
  - File backup configuration
  - Automated backup scheduling
  - Restore operations

- **Audit Logging**:
  - System-wide audit log viewing
  - Audit log filtering and search
  - Audit log export
  - Audit log retention policies

- **Integration Settings**:
  - Telegram bot configuration
  - Email/SMTP settings
  - Other API integrations

#### 3.7 ID Card Management
- View generated ID cards
- Regenerate ID cards
- ID card template management
- Company information for ID cards
- Signature management

#### 3.8 Qualification Management
- View member qualifications
- Verify qualification certificates
- Qualification type management

---

## 4. 👥 User Panel (General)

### Current Status
- May overlap with Member Panel
- May have limited access features

### Clarification Needed
- Determine if User Panel is separate from Member Panel
- Define user vs member distinction
- Determine access levels

### Potential Features (if separate)
- Limited resource access
- News/blog viewing
- Event viewing
- Registration submission

---

## 🔄 Panel Interactions

### Registration Flow
```
Public Landing Page (Registration Form)
    ↓
Submit with Payment Slip
    ↓
Admin Panel (Approval Required)
    ↓
Approved → Member Panel Access Granted
    ↓
ID Card Generation Available
```

### Resource Access Flow
```
Admin Panel (Upload Resource)
    ↓
Public/Member Panel (View & Download)
```

### News Publishing Flow
```
Admin Panel (Create News/Report)
    ↓
Public/Member Panel (View Content)
```

---

## 📊 Panel Access Matrix

| Feature | Public | User | Member | Admin |
|---------|--------|------|--------|-------|
| View Homepage | ✓ | ✓ | ✓ | ✓ |
| View Events | ✓ | ✓ | ✓ | ✓ |
| View News | ✓ | ✓ | ✓ | ✓ |
| View Resources | ✓ | ✓ | ✓ | ✓ |
| Download Resources | ✗ | ? | ✓ | ✓ |
| Register Membership | ✓ | ✗ | ✗ | ✓ |
| Member Dashboard | ✗ | ✗ | ✓ | ✓ |
| Generate ID Card | ✗ | ✗ | ✓ | ✓ |
| Manage Members | ✗ | ✗ | ✗ | ✓ |
| Manage Content | ✗ | ✗ | ✗ | ✓ |
| Reports | ✗ | ✗ | ✗ | ✓ |
| Settings | ✗ | ✗ | ✗ | ✓ |

---

## 🎯 Update Scope Summary

### Public Landing Pages
- Enhanced registration form
- Resources interface
- News & media interface
- ID card verification

### Member Panel
- Access control enforcement
- Enhanced dashboard
- Resources access
- News & media access
- ID card management
- Profile management
- Notifications

### Admin Panel
- Enhanced resource management
- Enhanced news & media management
- Member approval system
- Enhanced member management
- Comprehensive reports
- Advanced settings
- ID card management
- Qualification management

---

**End of System Scope Document**

