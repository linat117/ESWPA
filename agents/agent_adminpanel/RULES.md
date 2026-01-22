# Rules for Agent Admin Panel

**Agent**: agent_adminpanel  
**Created**: December 25, 2025  
**Focus**: Admin Panel Upgrade & Enhancement

---

## 🎯 Core Principles

1. **Quality First**: Maintain high code quality and user experience
2. **Security**: Always prioritize security in all changes
3. **Compatibility**: Maintain backward compatibility with existing features
4. **Documentation**: Document all changes and decisions
5. **Consistency**: Follow existing code patterns and conventions
6. **Performance**: Optimize for speed and efficiency

---

## 📋 Development Rules

### Before Making Changes:
1. ✅ Read `doc/database_table_structure.md` completely
2. ✅ Review `agents/agent_ethiosocial/rules.md` for project rules
3. ✅ Understand the existing admin panel structure
4. ✅ Check existing code patterns in admin panel
5. ✅ Test current functionality first
6. ✅ Backup database before any migrations

### Code Quality:
- ✅ Use prepared statements for all database queries
- ✅ Validate all user inputs
- ✅ Sanitize all outputs (XSS prevention)
- ✅ Implement proper error handling
- ✅ Follow existing admin panel code patterns
- ✅ Add comments for complex logic
- ✅ Keep functions focused and small
- ✅ Use meaningful variable names
- ✅ Consistent indentation (4 spaces)

### Security:
- ✅ Never trust user input
- ✅ Always use prepared statements (SQL injection prevention)
- ✅ Validate file uploads (type, size, MIME)
- ✅ Check admin permissions before operations
- ✅ Sanitize output (XSS prevention)
- ✅ Use CSRF tokens for forms
- ✅ Review access control logic
- ✅ Secure file uploads (use `@/upload` and `@/assets` directories)
- ✅ Never expose SQL errors to users
- ✅ Implement rate limiting for sensitive operations

### Database:
- ✅ Always backup before migrations
- ✅ Check `database_table_structure.md` first
- ✅ Use transactions for multi-step operations
- ✅ Add indexes for frequently queried columns
- ✅ Review foreign key constraints
- ✅ Test migrations on localhost first
- ✅ Never assume table structure - verify in documentation

### Admin Panel Specific:
- ✅ Maintain existing navigation structure
- ✅ Follow Bootstrap admin theme patterns
- ✅ Use DataTables for list views
- ✅ Use ApexCharts for visualizations
- ✅ Maintain consistent sidebar navigation
- ✅ Update `admin/sidebar.php` when adding new pages
- ✅ Check session authentication on all admin pages
- ✅ Use admin/include files for handlers

### UI/UX:
- ✅ Maintain responsive design
- ✅ Test on multiple browsers
- ✅ Test on mobile devices
- ✅ Provide clear error messages
- ✅ Show loading indicators
- ✅ Use confirmation dialogs for destructive actions
- ✅ Maintain consistent styling
- ✅ Follow existing design patterns

### Testing:
- ✅ Test all CRUD operations
- ✅ Test access control thoroughly
- ✅ Test AJAX functionality
- ✅ Test mobile responsiveness
- ✅ Test error scenarios
- ✅ Test edge cases
- ✅ Test with different data volumes
- ✅ Test form validation
- ✅ Test file uploads

### Documentation:
- ✅ Update documentation when making changes
- ✅ Document new features
- ✅ Document bug fixes
- ✅ Update status files
- ✅ Add code comments
- ✅ Update `CURRENT_STATUS.md` with progress
- ✅ Update `TASK_FOLLOW_UP.md` with completed tasks

---

## 🚫 What NOT to Do

### Never:
- ❌ Skip testing
- ❌ Ignore security warnings
- ❌ Make assumptions about database structure
- ❌ Break existing functionality
- ❌ Skip documentation
- ❌ Use raw SQL queries (always use prepared statements)
- ❌ Hardcode credentials
- ❌ Ignore error handling
- ❌ Update `config.php` during deployments (contains client credentials)
- ❌ Expose sensitive information in error messages

### Avoid:
- ⚠️ Making changes without understanding the system
- ⚠️ Breaking existing patterns
- ⚠️ Adding features without testing
- ⚠️ Ignoring mobile responsiveness
- ⚠️ Skipping access control checks
- ⚠️ Creating duplicate functionality
- ⚠️ Mixing concerns (separate logic from presentation)

---

## 📁 File Organization

### Follow Existing Structure:
- Admin pages: `admin/`
- Include files: `admin/include/`
- Assets: `admin/assets/`
- SQL migrations: `Sql/`

### Naming Conventions:
- Admin pages: `admin/[feature]_[action].php` (e.g., `add_resource.php`, `members_list.php`)
- Include files: `admin/include/[feature]_handler.php` (e.g., `email_handler.php`)
- AJAX handlers: `admin/include/ajax_[feature]_handler.php` (e.g., `ajax_delete_resource.php`)
- Delete handlers: `admin/include/delete_[feature].php` (e.g., `delete_member.php`)

---

## 🔧 Testing Requirements

### Before Marking Complete:
1. ✅ Test all functionality
2. ✅ Test on multiple browsers (Chrome, Firefox, Safari, Edge)
3. ✅ Test on mobile devices
4. ✅ Test access control
5. ✅ Test error scenarios
6. ✅ Review code quality
7. ✅ Check security
8. ✅ Update documentation
9. ✅ Test performance
10. ✅ Verify no console errors

### Testing Checklist:
- [ ] Functional testing
- [ ] Security testing
- [ ] Performance testing
- [ ] Cross-browser testing
- [ ] Mobile testing
- [ ] Access control testing
- [ ] AJAX testing
- [ ] Form validation testing
- [ ] File upload testing
- [ ] Error handling testing

---

## 📝 Documentation Requirements

### Must Document:
- ✅ New features
- ✅ Bug fixes
- ✅ Configuration changes
- ✅ Database changes
- ✅ API changes
- ✅ Security fixes
- ✅ Performance improvements
- ✅ UI/UX changes

### Update These Files:
- `CURRENT_STATUS.md` - Current status and progress
- `TASK_FOLLOW_UP.md` - Task progress checklist
- `doc/database_table_structure.md` - Database changes (if any)
- Code comments - Complex logic

---

## 🎯 Priority Guidelines

### High Priority:
1. Security enhancements
2. Bug fixes
3. Performance optimization
4. Critical feature improvements

### Medium Priority:
5. UI/UX enhancements
6. Feature additions
7. Code refactoring
8. Documentation

### Low Priority:
9. Nice-to-have features
10. Advanced analytics
11. Additional integrations

---

## ⚠️ Important Warnings

### Database:
- ⚠️ Always backup before changes
- ⚠️ Test migrations locally first
- ⚠️ Check table structure in `database_table_structure.md` before modifying
- ⚠️ Use transactions for complex operations
- ⚠️ Never assume structure - verify first

### File Uploads:
- ⚠️ Validate file types and sizes
- ⚠️ Use `@/upload` and `@/assets` directories (not inside swap folder)
- ⚠️ Never trust file extensions
- ⚠️ Check MIME types
- ⚠️ Scan for malicious content

### Access Control:
- ⚠️ Test thoroughly
- ⚠️ Review permission logic
- ⚠️ Check all access points
- ⚠️ Log all admin actions (audit trail)
- ⚠️ Verify session authentication on all pages

### Security:
- ⚠️ Never skip security checks
- ⚠️ Review all user inputs
- ⚠️ Test for SQL injection
- ⚠️ Test for XSS vulnerabilities
- ⚠️ Implement CSRF protection
- ⚠️ Review file upload security

### Configuration:
- ⚠️ **DO NOT** update `config.php` during deployments (contains client credentials)
- ⚠️ Use environment-based configuration
- ⚠️ Never commit sensitive data

---

## ✅ Success Criteria

### Task is Complete When:
- [ ] All tests passed
- [ ] No bugs found
- [ ] Security reviewed
- [ ] Code reviewed
- [ ] Documentation updated
- [ ] Mobile responsive
- [ ] Performance acceptable
- [ ] No console errors
- [ ] Backward compatible
- [ ] Follows existing patterns

---

## 📞 Reference

### Key Documents:
- `doc/database_table_structure.md` - Database schema
- `agents/agent_ethiosocial/rules.md` - Project-wide rules
- `doc/SYSTEM_SUMMARY.md` - System overview
- `README.md` - Agent overview

### Code Examples:
- Check existing admin panel files for patterns
- Review `admin/include/` files for handler examples
- Review existing pages for implementation patterns
- Check other agent README files for consistency

---

**Last Updated**: December 25, 2025  
**Status**: Active

