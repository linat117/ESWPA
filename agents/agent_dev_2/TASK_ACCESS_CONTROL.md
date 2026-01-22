# Task: Access Control System

**Agent**: agent_dev_2  
**Priority**: High  
**Status**: 📋 Ready for Development  
**Estimated Time**: 8-10 hours

---

## Objective
Implement a comprehensive access control system that restricts resource and research access based on membership packages, badges, and special permissions.

---

## Requirements

### 1. Package-Based Access Control

#### 1.1 Membership Packages

**Create Packages Table** (if not exists):
```sql
CREATE TABLE `membership_packages` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `slug` VARCHAR(100) NOT NULL UNIQUE,
  `description` TEXT NULL,
  `price` DECIMAL(10,2) NULL,
  `duration_months` INT(11) NULL,
  `features` TEXT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default packages
INSERT INTO `membership_packages` (`name`, `slug`, `description`, `price`, `duration_months`) VALUES
('Basic', 'basic', 'Basic membership with limited access', 500.00, 12),
('Premium', 'premium', 'Premium membership with full access', 1000.00, 12),
('Professional', 'professional', 'Professional membership for researchers', 2000.00, 12),
('Lifetime', 'lifetime', 'Lifetime membership with all features', 5000.00, NULL);
```

#### 1.2 Package Permissions

**Package Permissions Table:**
```sql
CREATE TABLE `package_permissions` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `package_id` INT(11) NOT NULL,
  `resource_access` ENUM('none', 'basic', 'premium', 'all') DEFAULT 'basic',
  `research_access` ENUM('none', 'view', 'create', 'collaborate', 'all') DEFAULT 'view',
  `max_research_projects` INT(11) DEFAULT 0,
  `max_resource_downloads` INT(11) DEFAULT 0,
  `can_collaborate` TINYINT(1) DEFAULT 0,
  `can_upload_resources` TINYINT(1) DEFAULT 0,
  `can_create_research` TINYINT(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_package` (`package_id`),
  FOREIGN KEY (`package_id`) REFERENCES `membership_packages`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

#### 1.3 Member Package Assignment

**Update Registrations Table:**
```sql
ALTER TABLE `registrations` 
ADD COLUMN `package_id` INT(11) NULL,
ADD COLUMN `package_start_date` DATE NULL,
ADD COLUMN `package_end_date` DATE NULL,
ADD INDEX `idx_package_id` (`package_id`),
ADD FOREIGN KEY (`package_id`) REFERENCES `membership_packages`(`id`) ON DELETE SET NULL;
```

---

### 2. Badge-Based Permissions

#### 2.1 Badge Permissions Table

```sql
CREATE TABLE `badge_permissions` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `badge_name` VARCHAR(100) NOT NULL,
  `resource_access` TEXT NULL,
  `research_access` TEXT NULL,
  `special_features` TEXT NULL,
  `description` TEXT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_badge_name` (`badge_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default badge permissions
INSERT INTO `badge_permissions` (`badge_name`, `resource_access`, `research_access`, `description`) VALUES
('Research Leader', 'all', 'all', 'Full access to all resources and research features'),
('Resource Expert', 'all', 'view', 'Access to all resources'),
('Research Publisher', 'premium', 'all', 'Full research access and premium resources'),
('Community Champion', 'basic', 'collaborate', 'Basic resources and research collaboration');
```

#### 2.2 Member Badges Table (if not exists)

```sql
CREATE TABLE `member_badges` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `member_id` INT(11) NOT NULL,
  `badge_name` VARCHAR(100) NOT NULL,
  `earned_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `is_active` TINYINT(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  INDEX `idx_member_id` (`member_id`),
  INDEX `idx_badge_name` (`badge_name`),
  FOREIGN KEY (`member_id`) REFERENCES `registrations`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

### 3. Special Permissions

#### 3.1 Special Permissions Table

```sql
CREATE TABLE `special_permissions` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `member_id` INT(11) NOT NULL,
  `permission_type` VARCHAR(100) NOT NULL,
  `resource_id` INT(11) NULL,
  `research_id` INT(11) NULL,
  `granted_by` INT(11) NULL,
  `granted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `expires_at` TIMESTAMP NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  `notes` TEXT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_member_id` (`member_id`),
  INDEX `idx_permission_type` (`permission_type`),
  INDEX `idx_resource_id` (`resource_id`),
  INDEX `idx_research_id` (`research_id`),
  FOREIGN KEY (`member_id`) REFERENCES `registrations`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`granted_by`) REFERENCES `user`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Permission Types:**
- `resource_access` - Access to specific resource
- `research_access` - Access to specific research
- `unlimited_downloads` - Unlimited resource downloads
- `research_creation` - Ability to create research projects
- `collaboration` - Ability to collaborate on research
- `admin_resources` - Access to admin-level resources

---

### 4. Resource Access Levels

#### 4.1 Update Resources Table

```sql
ALTER TABLE `resources` 
ADD COLUMN `access_level` ENUM('public', 'member', 'premium', 'restricted') DEFAULT 'member',
ADD COLUMN `required_packages` TEXT NULL,
ADD COLUMN `required_badges` TEXT NULL,
ADD INDEX `idx_access_level` (`access_level`);
```

**Access Levels:**
- `public` - Accessible to everyone
- `member` - Accessible to all logged-in members
- `premium` - Requires premium package or badge
- `restricted` - Requires specific package/badge/permission

---

### 5. Access Control Functions

#### 5.1 Access Check Handler (`include/access_control.php`)

**Functions:**

```php
// Check if member can access a resource
function canAccessResource($member_id, $resource_id) {
    // 1. Check if resource is public
    // 2. Check member login status
    // 3. Check package permissions
    // 4. Check badge permissions
    // 5. Check special permissions
    // 6. Check resource-specific requirements
    return true/false;
}

// Check if member can access research
function canAccessResearch($member_id, $research_id) {
    // Similar logic for research
}

// Check package permission
function hasPackagePermission($member_id, $permission_type) {
    // Check member's package permissions
}

// Check badge permission
function hasBadgePermission($member_id, $badge_name) {
    // Check if member has badge
}

// Check special permission
function hasSpecialPermission($member_id, $permission_type, $resource_id = null) {
    // Check special permissions
}

// Get member's effective permissions
function getMemberPermissions($member_id) {
    // Combine package, badge, and special permissions
    return array of permissions;
}
```

---

### 6. Access Logging

#### 6.1 Access Logs Table

```sql
CREATE TABLE `access_logs` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `member_id` INT(11) NULL,
  `resource_id` INT(11) NULL,
  `research_id` INT(11) NULL,
  `action` VARCHAR(50) NOT NULL,
  `access_granted` TINYINT(1) DEFAULT 1,
  `denial_reason` VARCHAR(255) NULL,
  `ip_address` VARCHAR(45) NULL,
  `user_agent` TEXT NULL,
  `accessed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_member_id` (`member_id`),
  INDEX `idx_resource_id` (`resource_id`),
  INDEX `idx_research_id` (`research_id`),
  INDEX `idx_accessed_at` (`accessed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Actions:**
- `view` - Viewed resource/research
- `download` - Downloaded resource
- `create` - Created research
- `edit` - Edited research
- `delete` - Deleted research
- `access_denied` - Access was denied

---

### 7. Admin Panel - Access Management

#### 7.1 Package Management (`admin/membership_packages.php`)

**Features:**
- List all packages
- Create/edit/delete packages
- Set package permissions
- Assign packages to members
- View package statistics

#### 7.2 Badge Permissions (`admin/badge_permissions.php`)

**Features:**
- Manage badge permissions
- Assign badges to members
- View badge statistics
- Edit permission rules

#### 7.3 Special Permissions (`admin/special_permissions.php`)

**Features:**
- Grant special permissions to members
- Set expiration dates
- View all special permissions
- Revoke permissions
- Permission history

#### 7.4 Access Logs (`admin/access_logs.php`)

**Features:**
- View access logs
- Filter by member, resource, date
- Export logs
- Access statistics
- Denied access reports

---

### 8. Member Panel - Access Display

#### 8.1 Display Access Status

- Show current package
- Show active badges
- Show access level for resources
- Show access restrictions
- Upgrade prompts (if access denied)

#### 8.2 Access Denied Page

- Clear message explaining why access is denied
- Show required package/badge
- Upgrade/purchase options
- Contact admin option

---

## Implementation Steps

1. Create database tables
2. Create access control functions
3. Integrate access checks into resource pages
4. Integrate access checks into research pages
5. Create admin management pages
6. Create access logging
7. Update member panel to show access info
8. Test all access scenarios

---

## Files to Create

1. `include/access_control.php` - Access control functions
2. `admin/membership_packages.php` - Package management
3. `admin/badge_permissions.php` - Badge permissions
4. `admin/special_permissions.php` - Special permissions
5. `admin/access_logs.php` - Access logs viewer
6. `Sql/migration_access_control.sql` - Database migration

## Files to Modify

1. `resources.php` - Add access checks
2. `member-research.php` - Add access checks
3. `admin/resources_list.php` - Add access level column
4. `admin/add_resource.php` - Add access level selection
5. `admin/edit_resource.php` - Add access level editing

---

## Testing Checklist

- [ ] Package permissions work correctly
- [ ] Badge permissions work correctly
- [ ] Special permissions work correctly
- [ ] Access denied shows correct message
- [ ] Access logging works
- [ ] Admin can manage packages
- [ ] Admin can grant special permissions
- [ ] Member sees correct access status
- [ ] Public resources accessible without login
- [ ] Restricted resources require permissions

---

**Last Updated**: December 16, 2025

