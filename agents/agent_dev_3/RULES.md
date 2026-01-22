# Rules for Agent Dev_3

**Agent**: agent_dev_3  
**Created**: December 23, 2025  
**Based On**: agent_dev_2 rules + handover requirements

---

## 🎯 Core Principles

1. **Quality First**: Test thoroughly before marking complete
2. **Security**: Always prioritize security in all changes
3. **Documentation**: Document all changes and decisions
4. **Consistency**: Follow existing code patterns and conventions
5. **User Experience**: Prioritize user experience in all changes

---

## 📋 Development Rules

### Before Making Changes:
1. ✅ Read `HANDOVER_FROM_AGENT_DEV_2.md` completely
2. ✅ Review `CURRENT_STATUS.md` from agent_dev_2
3. ✅ Understand the existing code structure
4. ✅ Check `database_table_structure.md` before database changes
5. ✅ Test current functionality first

### Code Quality:
- ✅ Use prepared statements for all database queries
- ✅ Validate all user inputs
- ✅ Sanitize all outputs
- ✅ Implement proper error handling
- ✅ Follow existing code patterns
- ✅ Add comments for complex logic
- ✅ Keep functions focused and small

### Security:
- ✅ Never trust user input
- ✅ Always use prepared statements
- ✅ Validate file uploads (type, size)
- ✅ Check access permissions
- ✅ Sanitize output (XSS prevention)
- ✅ Use CSRF tokens for forms
- ✅ Review access control logic

### Database:
- ✅ Always backup before migrations
- ✅ Check `database_table_structure.md` first
- ✅ Use transactions for multi-step operations
- ✅ Add indexes for frequently queried columns
- ✅ Review foreign key constraints
- ✅ Test migrations on localhost first

### Testing:
- ✅ Test all CRUD operations
- ✅ Test access control thoroughly
- ✅ Test AJAX functionality
- ✅ Test mobile responsiveness
- ✅ Test error scenarios
- ✅ Test edge cases
- ✅ Test with different user roles

### Documentation:
- ✅ Update documentation when making changes
- ✅ Document new features
- ✅ Document bug fixes
- ✅ Update status files
- ✅ Add code comments
- ✅ Update user guides

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

### Avoid:
- ⚠️ Making changes without understanding the system
- ⚠️ Breaking existing patterns
- ⚠️ Adding features without testing
- ⚠️ Ignoring mobile responsiveness
- ⚠️ Skipping access control checks

---

## 📁 File Organization

### Follow Existing Structure:
- Admin pages: `admin/`
- Member pages: `member-*.php`
- Include files: `include/`
- API endpoints: `api/`
- Assets: `assets/`
- SQL migrations: `Sql/`

### Naming Conventions:
- Admin pages: `admin/[feature]_[action].php`
- Member pages: `member-[feature].php`
- Include files: `include/[feature]_handler.php`
- AJAX handlers: `include/ajax_[feature]_handler.php`

---

## 🔧 Testing Requirements

### Before Marking Complete:
1. ✅ Test all functionality
2. ✅ Test on multiple browsers
3. ✅ Test on mobile devices
4. ✅ Test access control
5. ✅ Test error scenarios
6. ✅ Review code quality
7. ✅ Check security
8. ✅ Update documentation

### Testing Checklist:
- [ ] Functional testing
- [ ] Security testing
- [ ] Performance testing
- [ ] Cross-browser testing
- [ ] Mobile testing
- [ ] Access control testing
- [ ] AJAX testing

---

## 📝 Documentation Requirements

### Must Document:
- ✅ New features
- ✅ Bug fixes
- ✅ Configuration changes
- ✅ Database changes
- ✅ API changes
- ✅ Security fixes

### Update These Files:
- `RECENT_UPDATES.md` - Recent changes
- `agents/agent_dev_3/TASK_FOLLOW_UP.md` - Task progress
- `database_table_structure.md` - Database changes
- User guides (if created)

---

## 🎯 Priority Guidelines

### High Priority:
1. Testing & Quality Assurance
2. Bug Fixes
3. Security Issues

### Medium Priority:
4. Documentation
5. Performance Optimization
6. Code Refactoring

### Low Priority:
7. Optional Features
8. Nice-to-have Enhancements
9. Advanced Analytics

---

## ⚠️ Important Warnings

### Database:
- ⚠️ Always backup before changes
- ⚠️ Test migrations locally first
- ⚠️ Check table structure before modifying
- ⚠️ Use transactions for complex operations

### File Uploads:
- ⚠️ Validate file types and sizes
- ⚠️ Use `@/upload` and `@/assets` directories
- ⚠️ Never trust file extensions
- ⚠️ Scan for malicious content

### Access Control:
- ⚠️ Test thoroughly
- ⚠️ Review permission logic
- ⚠️ Check all access points
- ⚠️ Log all access attempts

### Security:
- ⚠️ Never skip security checks
- ⚠️ Review all user inputs
- ⚠️ Test for SQL injection
- ⚠️ Test for XSS vulnerabilities

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

---

## 📞 Reference

### Key Documents:
- `HANDOVER_FROM_AGENT_DEV_2.md` - Complete handover
- `TASK_FOLLOW_UP.md` - Task checklist
- `agents/agent_dev_2/CURRENT_STATUS.md` - System status
- `database_table_structure.md` - Database schema

### Code Examples:
- Check existing files for patterns
- Review `include/access_control.php` for access control
- Review `include/research_handler.php` for CRUD operations
- Review AJAX handlers for AJAX patterns

---

**Last Updated**: December 23, 2025  
**Status**: Active

