# Technical Requirements - System Upgrade

**Document Type**: Technical Specifications  
**Purpose**: Define technical requirements for proposal  
**Date**: December 17, 2025

---

## 🛠️ Technology Stack

### Current Stack
- **Backend**: PHP (version to be confirmed)
- **Database**: MySQL
- **Frontend**: HTML, CSS, JavaScript, Bootstrap
- **Admin Panel**: Custom Bootstrap-based admin template
- **PDF Generation**: PHP library (to be confirmed, possibly FPDF/TCPDF)
- **Email**: PHPMailer (present in vendor folder)

### Recommended Stack (for new features)
- **Backend**: PHP 7.4+ or PHP 8.x
- **Database**: MySQL 5.7+ or MariaDB 10.3+
- **PDF Generation**: TCPDF or DomPDF (for ID cards)
- **QR Code**: phpqrcode library
- **Image Processing**: GD Library or Imagick
- **Email**: PHPMailer (already present)
- **File Upload**: Custom PHP with validation

---

## 💾 Database Requirements

### New Tables Required

#### 1. Resources Management

```sql
-- Resources table
CREATE TABLE resources (
    id INT PRIMARY KEY AUTO_INCREMENT,
    section_id INT,
    title VARCHAR(255) NOT NULL,
    publication_date DATE NOT NULL,
    author VARCHAR(100) NOT NULL,
    pdf_file VARCHAR(255) NOT NULL,
    description TEXT,
    status ENUM('active', 'inactive') DEFAULT 'active',
    featured TINYINT(1) DEFAULT 0,
    download_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (section_id) REFERENCES resource_sections(id)
);

-- Resource sections table
CREATE TABLE resource_sections (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Resource downloads tracking
CREATE TABLE resource_downloads (
    id INT PRIMARY KEY AUTO_INCREMENT,
    resource_id INT NOT NULL,
    member_id INT,
    ip_address VARCHAR(45),
    downloaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (resource_id) REFERENCES resources(id),
    FOREIGN KEY (member_id) REFERENCES registrations(id)
);
```

#### 2. News & Media System

```sql
-- News table
CREATE TABLE news (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    featured_image VARCHAR(255),
    author_id INT,
    category_id INT,
    publication_date DATETIME,
    status ENUM('draft', 'published') DEFAULT 'draft',
    meta_title VARCHAR(255),
    meta_description TEXT,
    views INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (author_id) REFERENCES user(id)
);

-- Reports table
CREATE TABLE reports (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    report_type VARCHAR(50),
    pdf_file VARCHAR(255),
    report_date DATE,
    author_id INT,
    status ENUM('draft', 'published') DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (author_id) REFERENCES user(id)
);
```

#### 3. Enhanced Membership Registration

```sql
-- Update registrations table (add new columns)
ALTER TABLE registrations 
ADD COLUMN membership_id VARCHAR(50) UNIQUE,
ADD COLUMN qualification_type ENUM('Diploma', 'BSW', 'MSW', 'PhD') NOT NULL,
ADD COLUMN qualification_pdf VARCHAR(255) NOT NULL,
ADD COLUMN graduation_date DATE NOT NULL,
ADD COLUMN approval_status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
ADD COLUMN approved_by INT,
ADD COLUMN approved_at TIMESTAMP NULL,
ADD COLUMN expiry_date DATE,
ADD COLUMN status ENUM('active', 'expired', 'pending') DEFAULT 'pending',
ADD COLUMN id_card_generated TINYINT(1) DEFAULT 0,
ADD COLUMN id_card_generated_at TIMESTAMP NULL,
ADD COLUMN password VARCHAR(255),
ADD COLUMN password_reset_token VARCHAR(255),
ADD COLUMN password_reset_expires TIMESTAMP NULL,
ADD FOREIGN KEY (approved_by) REFERENCES user(id);
```

#### 4. Member Access & Authentication

```sql
-- Member sessions (optional)
CREATE TABLE member_sessions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    member_id INT NOT NULL,
    session_token VARCHAR(255) UNIQUE NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    FOREIGN KEY (member_id) REFERENCES registrations(id)
);
```

#### 5. ID Card System

```sql
-- ID card data
CREATE TABLE id_card_data (
    id INT PRIMARY KEY AUTO_INCREMENT,
    member_id INT NOT NULL UNIQUE,
    qr_code VARCHAR(255) UNIQUE NOT NULL,
    verification_url VARCHAR(255) NOT NULL,
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (member_id) REFERENCES registrations(id)
);

-- ID card verifications
CREATE TABLE id_card_verifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    membership_id VARCHAR(50) NOT NULL,
    verification_code VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    verified_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Company information for ID cards
CREATE TABLE company_info (
    id INT PRIMARY KEY AUTO_INCREMENT,
    company_name VARCHAR(255) NOT NULL,
    company_logo VARCHAR(255),
    company_signature VARCHAR(255),
    address TEXT,
    phone VARCHAR(50),
    email VARCHAR(100),
    website VARCHAR(255),
    terms_conditions TEXT
);
```

#### 6. Reports System

```sql
-- Report templates
CREATE TABLE report_templates (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    type VARCHAR(50) NOT NULL,
    template_config TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Report schedules
CREATE TABLE report_schedules (
    id INT PRIMARY KEY AUTO_INCREMENT,
    template_id INT,
    schedule_type ENUM('daily', 'weekly', 'monthly', 'custom') NOT NULL,
    schedule_config TEXT,
    last_run TIMESTAMP NULL,
    next_run TIMESTAMP,
    FOREIGN KEY (template_id) REFERENCES report_templates(id)
);
```

#### 7. Settings & Configuration

```sql
-- Settings table
CREATE TABLE settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    category VARCHAR(50),
    description TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- User roles
CREATE TABLE user_roles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    role ENUM('super_admin', 'admin', 'member') NOT NULL,
    permissions TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES user(id)
);

-- Permissions
CREATE TABLE permissions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    category VARCHAR(50)
);

-- Role permissions
CREATE TABLE role_permissions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    role_id INT,
    permission_id INT,
    FOREIGN KEY (role_id) REFERENCES user_roles(id),
    FOREIGN KEY (permission_id) REFERENCES permissions(id)
);

-- Audit logs
CREATE TABLE audit_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    table_name VARCHAR(100),
    record_id INT,
    old_value TEXT,
    new_value TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES user(id)
);

-- Backups
CREATE TABLE backups (
    id INT PRIMARY KEY AUTO_INCREMENT,
    backup_type ENUM('database', 'files', 'full') NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    file_size BIGINT,
    status ENUM('success', 'failed', 'in_progress') DEFAULT 'in_progress',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES user(id)
);

-- Sync logs
CREATE TABLE sync_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    sync_type ENUM('database', 'files', 'full') NOT NULL,
    status ENUM('success', 'failed', 'in_progress') DEFAULT 'in_progress',
    sync_details TEXT,
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL
);
```

#### 8. Email System

```sql
-- Email templates
CREATE TABLE email_templates (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    body TEXT NOT NULL,
    type VARCHAR(50),
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Email logs
CREATE TABLE email_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    recipient VARCHAR(255) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    body TEXT,
    status ENUM('sent', 'failed', 'pending') DEFAULT 'pending',
    sent_at TIMESTAMP NULL,
    error_message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

---

## 📁 File Structure Requirements

### New Files to Create

#### Admin Panel Files

```
admin/
  ├── resources_management.php (new)
  ├── resource_sections.php (new)
  ├── news_management.php (enhanced)
  ├── reports_management.php (enhanced)
  ├── member_approval.php (new)
  ├── member_qualifications.php (new)
  ├── id_card_management.php (new)
  ├── settings_sync.php (enhanced)
  ├── settings_roles.php (new)
  ├── settings_permissions.php (new)
  ├── settings_audit.php (new)
  ├── email_templates.php (new)
  └── include/
      ├── resource_handler.php (new)
      ├── news_handler.php (enhanced)
      ├── member_approval_handler.php (new)
      ├── id_card_generator.php (new)
      ├── qr_code_generator.php (new)
      ├── report_generator.php (new)
      ├── sync_handler.php (new)
      ├── audit_logger.php (new)
      ├── backup_handler.php (new)
      └── permission_check.php (new)
```

#### Member Panel Files

```
member-dashboard.php (enhanced)
member-resources.php (new)
member-news.php (new)
member-profile.php (enhanced)
member-id-card.php (enhanced)
member-notifications.php (new)
```

#### Public Pages

```
resources.php (enhanced)
news-detail.php (enhanced)
verify-id.php (new)
```

#### Include Files

```
include/
  ├── member-auth.php (enhanced)
  ├── id_card_generator.php (new)
  ├── qr_code_generator.php (new)
  ├── permission_check.php (new)
  ├── audit_logger.php (new)
  └── email_handler.php (enhanced)
```

---

## 🔧 Technical Components Required

### 1. PDF Generation Library
- **Library**: TCPDF or DomPDF
- **Purpose**: ID card generation, report PDFs
- **Installation**: Composer or manual

### 2. QR Code Library
- **Library**: phpqrcode (PHP QR Code)
- **Purpose**: Generate QR codes for ID cards
- **Installation**: Manual download or Composer

### 3. Image Processing
- **Library**: GD Library (PHP built-in) or Imagick
- **Purpose**: Image manipulation, ID card design
- **Installation**: PHP extension

### 4. Email System
- **Library**: PHPMailer (already present)
- **Purpose**: Send emails
- **Configuration**: SMTP settings

### 5. File Upload Handler
- **Custom PHP**: File validation and storage
- **Purpose**: Handle PDF, image uploads
- **Security**: File type validation, size limits, virus scanning (optional)

### 6. Session Management
- **Custom PHP**: Member session handling
- **Purpose**: Authentication and authorization
- **Security**: Secure session handling

### 7. Backup System
- **Custom PHP**: Database and file backup
- **Purpose**: Automated backups
- **Tools**: mysqldump for database, tar/zip for files

### 8. Sync System
- **Custom PHP**: Remote server sync
- **Purpose**: Database and file synchronization
- **Methods**: FTP, SSH, or API-based

---

## 🔐 Security Requirements

### 1. Authentication & Authorization
- Password hashing (bcrypt/argon2)
- Session security (httponly, secure flags)
- CSRF protection
- Role-based access control (RBAC)

### 2. File Upload Security
- File type validation (whitelist)
- File size limits
- Filename sanitization
- Virus scanning (optional but recommended)
- Storage outside web root (if possible)

### 3. SQL Injection Prevention
- Prepared statements
- Parameterized queries
- Input sanitization

### 4. XSS Prevention
- Output escaping
- Content Security Policy (CSP)
- Input validation

### 5. Data Encryption
- Sensitive data encryption at rest
- HTTPS for data transmission
- Password encryption

---

## 📊 Performance Requirements

### 1. Database Optimization
- Proper indexing
- Query optimization
- Connection pooling (if needed)

### 2. File Storage
- Organized file structure
- File caching
- CDN integration (optional)

### 3. Caching
- Session caching
- Database query caching
- Static content caching

### 4. Scalability
- Code optimization
- Database optimization
- Server resource planning

---

## 🔄 Integration Requirements

### 1. Email Integration
- SMTP configuration
- Email template system
- Email queue system (optional)

### 2. Telegram Bot Integration
- Bot API integration
- Notification sending
- Command handling

### 3. Remote Server Sync
- FTP/SSH connection
- Database sync mechanism
- File sync mechanism

---

## 📱 Responsive Design Requirements

- Mobile-responsive admin panel
- Mobile-responsive member panel
- Mobile-responsive public pages
- Print-friendly ID cards
- Mobile-friendly forms

---

## 🧪 Testing Requirements

### 1. Unit Testing
- Core functionality testing
- Library integration testing

### 2. Integration Testing
- Database integration
- File upload testing
- Email sending testing

### 3. Security Testing
- Penetration testing
- SQL injection testing
- XSS testing
- Authentication testing

### 4. User Acceptance Testing
- Admin panel testing
- Member panel testing
- Public pages testing

---

## 📚 Documentation Requirements

### 1. Technical Documentation
- Database schema documentation
- API documentation (if applicable)
- Code documentation

### 2. User Documentation
- Admin user manual
- Member user guide
- System administrator guide

### 3. Installation Guide
- Setup instructions
- Configuration guide
- Deployment guide

---

**End of Technical Requirements Document**

