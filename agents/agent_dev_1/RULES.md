# Common Rules for Agent Dev 1

**Agent**: agent_dev_1  
**Purpose**: Email Subscription, Telegram Chatbot & Automated Email Marketing Implementation  
**Last Updated**: December 16, 2025

---

## 🚨 CRITICAL RULES - MUST FOLLOW

### 1. Do NOT Break Existing Functionality
- **ALWAYS** test existing features after making changes
- **NEVER** modify core files without understanding their purpose
- **ALWAYS** check if files are used elsewhere before modifying
- **ALWAYS** use version control (git) before making changes
- **NEVER** delete existing code without backup

### 2. Code Quality & Security
- **ALWAYS** use prepared statements for database queries
- **ALWAYS** validate and sanitize user input
- **ALWAYS** use `htmlspecialchars()` for output
- **ALWAYS** check for SQL injection vulnerabilities
- **ALWAYS** implement CSRF protection for forms
- **ALWAYS** use secure password hashing if needed

### 3. Database Operations
- **ALWAYS** check `database_table_structure.md` before creating tables
- **ALWAYS** create migration SQL files in `Sql/` directory
- **ALWAYS** use proper indexes for performance
- **ALWAYS** use foreign keys where appropriate
- **NEVER** modify existing tables without migration script
- **ALWAYS** test migrations on localhost first

### 4. File Organization
- **ALWAYS** follow existing file structure
- **ALWAYS** place include files in `include/` directory
- **ALWAYS** place admin files in `admin/` directory
- **ALWAYS** use descriptive file names (lowercase with underscores)
- **ALWAYS** create SQL migrations in `Sql/` directory

### 5. Configuration Files
- **NEVER** modify `include/config.php` (contains client credentials)
- **ALWAYS** use existing configuration patterns
- **ALWAYS** check if config is already included before including again

---

## 📋 Development Workflow

### Before Starting:
1. ✅ Read this rules file completely
2. ✅ Read the task files (`TASK_EMAIL_SUBSCRIPTION.md`, `TASK_TELEGRAM_CHATBOT.md`)
3. ✅ Check current status in `README.md`
4. ✅ Review existing code patterns
5. ✅ Check database structure
6. ✅ Understand existing functionality

### During Development:
1. ✅ Create database migrations first
2. ✅ Test database changes on localhost
3. ✅ Create backend handlers
4. ✅ Test backend functionality
5. ✅ Create frontend components
6. ✅ Test frontend functionality
7. ✅ Integrate with existing code
8. ✅ Test end-to-end flow

### After Development:
1. ✅ Test all functionality thoroughly
2. ✅ Check for JavaScript errors
3. ✅ Check for PHP errors
4. ✅ Test on mobile devices
5. ✅ Test on different browsers
6. ✅ Verify no conflicts with existing code
7. ✅ Update documentation if needed

---

## 🎨 Design & UI Guidelines

### Design Consistency:
- **ALWAYS** match existing design theme (purple/blue gradients)
- **ALWAYS** use existing color scheme (#667eea to #764ba2)
- **ALWAYS** follow responsive design patterns
- **ALWAYS** use existing icon library (Font Awesome)
- **ALWAYS** match existing button styles

### Mobile-First Approach:
- **ALWAYS** design for mobile first
- **ALWAYS** test on mobile devices
- **ALWAYS** ensure touch-friendly buttons (min 44x44px)
- **ALWAYS** test on different screen sizes

### Accessibility:
- **ALWAYS** include ARIA labels
- **ALWAYS** ensure keyboard navigation works
- **ALWAYS** provide alt text for images
- **ALWAYS** ensure sufficient color contrast

---

## 🔒 Security Rules

### Input Validation:
- **ALWAYS** validate email format using `filter_var(FILTER_VALIDATE_EMAIL)`
- **ALWAYS** validate required fields
- **ALWAYS** check for empty values
- **ALWAYS** sanitize user input
- **ALWAYS** use prepared statements

### Spam Protection:
- **ALWAYS** implement honeypot fields
- **ALWAYS** implement rate limiting
- **ALWAYS** validate on both client and server side
- **ALWAYS** log suspicious activity

### Data Protection:
- **ALWAYS** hash sensitive data if needed
- **ALWAYS** use secure tokens for unsubscribe links
- **ALWAYS** never expose API keys in frontend code
- **ALWAYS** use HTTPS for sensitive operations

---

## 📁 File Naming Conventions

### PHP Files:
- Use lowercase with underscores: `subscribe_handler.php`
- Include files: `include/{feature}_handler.php`
- Admin files: `admin/{feature}_list.php` or `admin/{feature}.php`
- API files: `api/{feature}_api.php` or `include/{feature}_ajax.php`

### SQL Files:
- Migration files: `Sql/migration_{description}.sql`
- Use descriptive names: `migration_create_email_subscribers.sql`

### CSS/JS Files:
- Use descriptive names: `subscription-popup.css`
- Place in appropriate directories

---

## 🗄️ Database Rules

### Table Naming:
- Use lowercase, plural nouns: `email_subscribers`
- Use underscores for multi-word: `telegram_messages`

### Field Naming:
- Use lowercase with underscores: `subscribed_at`
- Timestamps: `created_at`, `updated_at`, `deleted_at`
- Foreign keys: `{table}_id` (e.g., `user_id`)
- Status fields: Use ENUM with descriptive values

### Indexes:
- **ALWAYS** add indexes for frequently queried fields
- **ALWAYS** add unique indexes for unique fields
- **ALWAYS** add indexes for foreign keys

---

## 🔧 Integration Rules

### Existing Code Integration:
- **ALWAYS** check if functionality already exists
- **ALWAYS** reuse existing functions when possible
- **ALWAYS** follow existing code patterns
- **ALWAYS** maintain existing code style
- **NEVER** duplicate existing functionality

### Header/Footer Integration:
- **ALWAYS** check which header version is used (`header-v1.2.php`)
- **ALWAYS** ensure compatibility with existing headers
- **ALWAYS** test on pages that use different headers
- **ALWAYS** ensure no CSS conflicts

### Admin Panel Integration:
- **ALWAYS** follow existing admin panel structure
- **ALWAYS** use existing admin theme
- **ALWAYS** add menu items to `admin/sidebar.php`
- **ALWAYS** follow existing admin page patterns

---

## 🧪 Testing Rules

### Before Deployment:
- [ ] Test on localhost first
- [ ] Test all form validations
- [ ] Test error handling
- [ ] Test success scenarios
- [ ] Test edge cases
- [ ] Test mobile responsiveness
- [ ] Test on different browsers
- [ ] Check for JavaScript errors
- [ ] Check for PHP errors
- [ ] Verify database operations
- [ ] Test email delivery
- [ ] Test Telegram integration

### Testing Checklist:
- [ ] All required fields validated
- [ ] Duplicate entries handled
- [ ] Error messages display correctly
- [ ] Success messages display correctly
- [ ] Mobile responsive
- [ ] No console errors
- [ ] No PHP errors
- [ ] Database operations work
- [ ] Email sending works
- [ ] Telegram messages work

---

## 📝 Documentation Rules

### Code Documentation:
- **ALWAYS** add comments for complex logic
- **ALWAYS** document function parameters and returns
- **ALWAYS** explain business logic
- **ALWAYS** note any workarounds

### File Documentation:
- **ALWAYS** update task files when completing tasks
- **ALWAYS** document any configuration needed
- **ALWAYS** document API endpoints if created
- **ALWAYS** update README if adding new features

---

## ⚠️ Common Mistakes to Avoid

1. **DON'T** modify `include/config.php`
2. **DON'T** break existing functionality
3. **DON'T** use string concatenation in SQL queries
4. **DON'T** expose sensitive data in frontend
5. **DON'T** skip input validation
6. **DON'T** forget to test on mobile
7. **DON'T** create duplicate functionality
8. **DON'T** ignore existing code patterns
9. **DON'T** forget error handling
10. **DON'T** skip security measures

---

## 🚀 Performance Rules

### Optimization:
- **ALWAYS** minimize database queries
- **ALWAYS** use indexes for queries
- **ALWAYS** optimize images
- **ALWAYS** minimize JavaScript/CSS
- **ALWAYS** use lazy loading where appropriate

### Caching:
- **ALWAYS** consider caching for frequently accessed data
- **ALWAYS** use session storage appropriately
- **ALWAYS** clear cache when needed

---

## 🔄 Version Control

### Git Workflow:
- **ALWAYS** commit related changes together
- **ALWAYS** write clear commit messages
- **ALWAYS** test before committing
- **ALWAYS** don't commit sensitive data
- **ALWAYS** don't commit error_log files

---

## 📞 When Stuck

1. ✅ Re-read the task files
2. ✅ Check existing code for patterns
3. ✅ Review database structure
4. ✅ Check error logs
5. ✅ Test step by step
6. ✅ Document what you've tried

---

## ✅ Completion Checklist

Before marking task as complete:

- [ ] All requirements implemented
- [ ] All tests passed
- [ ] No errors in console/logs
- [ ] Mobile responsive
- [ ] Security measures in place
- [ ] Documentation updated
- [ ] Code follows existing patterns
- [ ] No conflicts with existing code
- [ ] Database migrations created
- [ ] Admin panel integrated (if needed)

---

## 📚 Reference Files

**Must Read Before Starting**:
- `agents/agent_dev_1/README.md` - Overview and current status
- `agents/agent_dev_1/TASK_EMAIL_SUBSCRIPTION.md` - Email subscription task
- `agents/agent_dev_1/TASK_TELEGRAM_CHATBOT.md` - Telegram chatbot task
- `agents/agent_dev_1/TASK_AUTOMATED_EMAIL_MARKETING.md` - Automated email marketing task
- `database_table_structure.md` - Database structure
- `agents/agent_ethiosocial/rules.md` - General project rules

**Reference for Patterns**:
- `admin/include/email_handler.php` - Email handling pattern
- `admin/settings.php` - Settings page pattern
- `include/config.php` - Configuration pattern (read only)
- `header-v1.2.php` - Header integration pattern

---

**Remember**: Quality over speed. Take time to understand existing code, follow patterns, and test thoroughly. It's better to ask questions than to break existing functionality.

---

**Last Updated**: December 16, 2025

