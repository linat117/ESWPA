# Common Rules for Agent Dev 2

**Agent**: agent_dev_2  
**Purpose**: Research & Resource Panel Enhancement  
**Last Updated**: December 16, 2025

---

## 🚨 CRITICAL RULES - MUST FOLLOW

### 1. Do NOT Break Existing Functionality
- **ALWAYS** test existing resource features after making changes
- **NEVER** modify core files without understanding their purpose
- **ALWAYS** check if files are used elsewhere before modifying
- **ALWAYS** use version control (git) before making changes
- **NEVER** delete existing code without backup
- **PRESERVE** existing resource upload/download functionality

### 2. Code Quality & Security
- **ALWAYS** use prepared statements for database queries
- **ALWAYS** validate and sanitize user input
- **ALWAYS** use `htmlspecialchars()` for output
- **ALWAYS** check for SQL injection vulnerabilities
- **ALWAYS** implement CSRF protection for forms
- **ALWAYS** validate file uploads (type, size, content)
- **ALWAYS** check permissions before allowing access

### 3. Database Operations
- **ALWAYS** check `database_table_structure.md` before creating tables
- **ALWAYS** create migration SQL files in `Sql/` directory
- **ALWAYS** use proper indexes for performance
- **ALWAYS** use foreign keys where appropriate
- **NEVER** modify existing tables without migration script
- **ALWAYS** test migrations on localhost first
- **ALWAYS** backup existing data before migrations

### 4. File Organization
- **ALWAYS** follow existing file structure
- **ALWAYS** place include files in `include/` directory
- **ALWAYS** place admin files in `admin/` directory
- **ALWAYS** place member files at root or `member/` directory
- **ALWAYS** use descriptive file names (lowercase with underscores)
- **ALWAYS** create SQL migrations in `Sql/` directory

### 5. Access Control Requirements
- **ALWAYS** check member login status
- **ALWAYS** verify package/badge/permissions before granting access
- **ALWAYS** log access attempts
- **ALWAYS** provide clear error messages for access denials
- **NEVER** expose restricted content without permission check

---

## 📋 Development Workflow

### Before Starting:
1. ✅ Read this rules file completely
2. ✅ Read all task files (`TASK_*.md`)
3. ✅ Check current status in `CURRENT_STATUS.md`
4. ✅ Review existing resource code
5. ✅ Check database structure
6. ✅ Understand access control requirements
7. ✅ Review badge/package system (if exists)

### During Development:
1. ✅ Create database migrations first
2. ✅ Test database changes on localhost
3. ✅ Build access control system
4. ✅ Create backend handlers
5. ✅ Test backend functionality
6. ✅ Create frontend components
7. ✅ Test frontend functionality
8. ✅ Integrate with existing code
9. ✅ Test end-to-end flow
10. ✅ Test access control thoroughly

### After Development:
1. ✅ Test all functionality thoroughly
2. ✅ Check for JavaScript errors
3. ✅ Check for PHP errors
4. ✅ Test on mobile devices
5. ✅ Test on different browsers
6. ✅ Verify no conflicts with existing code
7. ✅ Test access control with different packages/badges
8. ✅ Update documentation if needed

---

## 🎨 Design & UI Guidelines

### Design Consistency:
- **ALWAYS** match existing design theme (purple/blue gradients)
- **ALWAYS** use existing color scheme (#667eea to #764ba2)
- **ALWAYS** follow responsive design patterns
- **ALWAYS** use existing icon library (Font Awesome, Remix Icon)
- **ALWAYS** match existing button styles

### User Experience Focus:
- **MAKE IT ADDICTIVE** - Research panel should be engaging
- **SIMPLIFY WORK** - Tools should make research easier
- **ENCOURAGE USE** - Features should invite regular use
- **SMOOTH WORKFLOW** - Minimize clicks and steps
- **VISUAL FEEDBACK** - Clear success/error states

### Mobile-First Approach:
- **ALWAYS** design for mobile first
- **ALWAYS** test on mobile devices
- **ALWAYS** ensure touch-friendly buttons (min 44x44px)
- **ALWAYS** test on different screen sizes
- **ALWAYS** optimize file sizes for mobile

### Accessibility:
- **ALWAYS** include ARIA labels
- **ALWAYS** ensure keyboard navigation works
- **ALWAYS** provide alt text for images
- **ALWAYS** ensure sufficient color contrast
- **ALWAYS** support screen readers

---

## 🔒 Security Rules

### Input Validation:
- **ALWAYS** validate file types (whitelist approach)
- **ALWAYS** validate file sizes
- **ALWAYS** scan uploaded files for malware (if possible)
- **ALWAYS** validate all form inputs
- **ALWAYS** sanitize user-generated content

### Access Control:
- **ALWAYS** check authentication before access
- **ALWAYS** verify package/badge permissions
- **ALWAYS** check special permissions
- **ALWAYS** log access attempts
- **NEVER** trust client-side validation alone

### File Security:
- **ALWAYS** store uploads outside web root when possible
- **ALWAYS** use secure file names (no user input in filename)
- **ALWAYS** validate file content, not just extension
- **ALWAYS** set proper file permissions
- **ALWAYS** prevent directory traversal attacks

### Data Protection:
- **ALWAYS** encrypt sensitive research data (if needed)
- **ALWAYS** protect member personal information
- **ALWAYS** use secure file download methods
- **ALWAYS** prevent direct file access (use download handler)

---

## 📁 File Naming Conventions

### PHP Files:
- Resource files: `resource_*.php`, `*_resource.php`
- Research files: `research_*.php`, `*_research.php`
- Handler files: `include/*_handler.php`
- Admin files: `admin/resource_*.php`, `admin/research_*.php`
- Member files: `member-resource*.php`, `member-research*.php`

### SQL Files:
- Migration files: `Sql/migration_{description}.sql`
- Use descriptive names: `migration_create_research_projects.sql`

### CSS/JS Files:
- Use descriptive names: `research-tools.css`
- Place in appropriate directories

---

## 🗄️ Database Rules

### Table Naming:
- Use lowercase, plural nouns: `research_projects`
- Use underscores for multi-word: `resource_access_logs`

### Field Naming:
- Use lowercase with underscores: `created_at`, `updated_at`
- Timestamps: `created_at`, `updated_at`, `deleted_at`
- Foreign keys: `{table}_id` (e.g., `member_id`, `resource_id`)
- Status fields: Use ENUM with descriptive values
- Boolean fields: Use TINYINT(1) or BOOLEAN

### Indexes:
- **ALWAYS** add indexes for frequently queried fields
- **ALWAYS** add unique indexes for unique fields
- **ALWAYS** add indexes for foreign keys
- **ALWAYS** add composite indexes for common query patterns

### Research Tables:
- Version control: Track all changes
- Collaboration: Track contributors
- Access: Log who accessed what
- History: Maintain audit trail

---

## 🔧 Integration Rules

### Existing Code Integration:
- **ALWAYS** check if functionality already exists
- **ALWAYS** reuse existing functions when possible
- **ALWAYS** follow existing code patterns
- **ALWAYS** maintain existing code style
- **NEVER** duplicate existing functionality

### Resource System Integration:
- **PRESERVE** existing upload/download functionality
- **ENHANCE** existing features, don't replace
- **MAINTAIN** backward compatibility
- **TEST** existing features after changes

### Access Control Integration:
- **INTEGRATE** with badge system (if exists)
- **INTEGRATE** with package system (if exists)
- **RESPECT** existing permission structure
- **EXTEND** permission system as needed

---

## 🧪 Testing Rules

### Before Deployment:
- [ ] Test on localhost first
- [ ] Test all CRUD operations
- [ ] Test bulk operations
- [ ] Test access control (all permission levels)
- [ ] Test file uploads/downloads
- [ ] Test search and filtering
- [ ] Test mobile responsiveness
- [ ] Test on different browsers
- [ ] Check for JavaScript errors
- [ ] Check for PHP errors
- [ ] Verify database operations
- [ ] Test collaboration features
- [ ] Test research tools
- [ ] Verify no security vulnerabilities

### Access Control Testing:
- [ ] Test with different packages
- [ ] Test with different badges
- [ ] Test with special permissions
- [ ] Test access denied scenarios
- [ ] Test access logging
- [ ] Test permission changes

### Testing Checklist:
- [ ] All required fields validated
- [ ] Error messages display correctly
- [ ] Success messages display correctly
- [ ] Mobile responsive
- [ ] No console errors
- [ ] No PHP errors
- [ ] Database operations work
- [ ] File operations work
- [ ] Access control works correctly

---

## 🎯 Research Panel Specific Rules

### Make It Addictive:
- **ENCOURAGE** daily use with engaging features
- **PROVIDE** value with useful tools
- **TRACK** progress and achievements
- **ENABLE** collaboration and sharing
- **OFFER** rewards/incentives for use

### Simplify Research Work:
- **REDUCE** manual work with automation
- **PROVIDE** templates and examples
- **ENABLE** quick actions and shortcuts
- **AUTOMATE** repetitive tasks
- **INTEGRATE** useful tools

### User Engagement:
- **SHOW** usage statistics
- **PROVIDE** recommendations
- **ENABLE** favorites/bookmarks
- **TRACK** reading progress
- **OFFER** personalization

---

## 🔮 AI Integration Preparation

### Data Structure:
- **ORGANIZE** data in structured format
- **USE** consistent naming conventions
- **INCLUDE** metadata for AI processing
- **DOCUMENT** data formats
- **PREPARE** API endpoints

### Architecture:
- **DESIGN** plugin architecture
- **CREATE** modular components
- **ENABLE** easy AI integration
- **PREPARE** API structure
- **DOCUMENT** integration points

---

## ⚠️ Common Mistakes to Avoid

1. **DON'T** break existing resource functionality
2. **DON'T** ignore access control requirements
3. **DON'T** skip permission checks
4. **DON'T** expose sensitive data
5. **DON'T** forget file validation
6. **DON'T** skip security measures
7. **DON'T** ignore mobile responsiveness
8. **DON'T** forget error handling
9. **DON'T** skip testing
10. **DON'T** ignore user experience

---

## 🚀 Performance Rules

### Optimization:
- **ALWAYS** minimize database queries
- **ALWAYS** use indexes for queries
- **ALWAYS** optimize images/files
- **ALWAYS** minimize JavaScript/CSS
- **ALWAYS** use lazy loading where appropriate
- **ALWAYS** paginate large lists

### Caching:
- **ALWAYS** consider caching for frequently accessed data
- **ALWAYS** use session storage appropriately
- **ALWAYS** clear cache when needed
- **ALWAYS** cache expensive operations

### File Handling:
- **ALWAYS** optimize file sizes
- **ALWAYS** use CDN for large files (if available)
- **ALWAYS** compress files when possible
- **ALWAYS** lazy load file previews

---

## 📝 Documentation Rules

### Code Documentation:
- **ALWAYS** add comments for complex logic
- **ALWAYS** document function parameters and returns
- **ALWAYS** explain business logic
- **ALWAYS** note any workarounds
- **ALWAYS** document access control logic

### File Documentation:
- **ALWAYS** update task files when completing tasks
- **ALWAYS** document any configuration needed
- **ALWAYS** document API endpoints if created
- **ALWAYS** update README if adding new features
- **ALWAYS** document access control rules

---

## ✅ Completion Checklist

Before marking task as complete:

- [ ] All requirements implemented
- [ ] All tests passed
- [ ] No errors in console/logs
- [ ] Mobile responsive
- [ ] Security measures in place
- [ ] Access control implemented
- [ ] Documentation updated
- [ ] Code follows existing patterns
- [ ] No conflicts with existing code
- [ ] Database migrations created
- [ ] Admin panel integrated
- [ ] Member panel integrated
- [ ] Research tools functional
- [ ] Collaboration features work
- [ ] Performance optimized

---

## 📚 Reference Files

**Must Read Before Starting**:
- `agents/agent_dev_2/README.md` - Overview and current status
- `agents/agent_dev_2/TASK_RESOURCE_RESEARCH_ENHANCEMENT.md` - Main task
- `agents/agent_dev_2/TASK_ACCESS_CONTROL.md` - Access control task
- `agents/agent_dev_2/TASK_RESEARCH_TOOLS.md` - Research tools task
- `agents/agent_dev_2/TASK_AI_PREPARATION.md` - AI preparation task
- `database_table_structure.md` - Database structure
- `agents/agent_ethiosocial/rules.md` - General project rules
- `FUTURE_UPDATES.md` - Future features and badge system

**Reference for Patterns**:
- `admin/resources_list.php` - Existing resource listing
- `admin/add_resource.php` - Existing resource form
- `resources.php` - Existing public resource page
- `admin/include/upload_resource.php` - Upload handler pattern
- `member-dashboard.php` - Member panel structure

---

**Remember**: Focus on making the research panel addictive and useful. Simplify research work, encourage regular use, and prepare for future AI integration. Quality over speed.

---

**Last Updated**: December 16, 2025

