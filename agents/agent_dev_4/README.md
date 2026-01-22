# Agent Dev 4

**Agent**: agent_dev_4  
**Created**: December 24, 2025  
**Status**: 📋 Ready for Assignment

---

## Overview

This agent is ready to take on development tasks for the Ethio Social Works platform. The agent follows established development patterns, security practices, and code quality standards.

---

## Current Status

**Status**: 📋 **Ready for Assignment**

This agent is currently available and ready to receive tasks. Once assigned, this section will be updated with:
- Current task assignments
- Implementation progress
- Completion status
- Next steps

---

## Development Approach

### Core Principles:
1. **Quality First**: Test thoroughly before marking complete
2. **Security**: Always prioritize security in all changes
3. **Documentation**: Document all changes and decisions
4. **Consistency**: Follow existing code patterns and conventions
5. **User Experience**: Prioritize user experience in all changes

### Workflow:
1. ✅ Read and understand task requirements
2. ✅ Review existing code patterns
3. ✅ Check database structure (`database_table_structure.md`)
4. ✅ Implement following established patterns
5. ✅ Test thoroughly
6. ✅ Document changes
7. ✅ Update status files

---

## Key Rules & Guidelines

### Before Starting Any Task:
1. ✅ Read `agents/agent_ethiosocial/rules.md` - General project rules
2. ✅ Check `database_table_structure.md` - Database schema
3. ✅ Review similar existing features for patterns
4. ✅ Understand the task requirements completely
5. ✅ Plan the implementation approach

### Code Quality:
- ✅ Use prepared statements for all database queries
- ✅ Validate all user inputs
- ✅ Sanitize all outputs
- ✅ Implement proper error handling
- ✅ Follow existing code patterns
- ✅ Add comments for complex logic

### Security:
- ✅ Never trust user input
- ✅ Always use prepared statements
- ✅ Validate file uploads (type, size)
- ✅ Check access permissions
- ✅ Sanitize output (XSS prevention)
- ✅ Use CSRF tokens for forms

### Database:
- ✅ Always backup before migrations
- ✅ Check `database_table_structure.md` first
- ✅ Use transactions for multi-step operations
- ✅ Add indexes for frequently queried columns
- ✅ Test migrations on localhost first

### File Organization:
- Admin pages: `admin/`
- Member pages: `member-*.php`
- Include files: `include/`
- API endpoints: `api/`
- Assets: `assets/` or `uploads/` (root level)
- SQL migrations: `Sql/`

### Configuration:
- ⚠️ **NEVER** update `include/config.php` during deployments (contains client credentials)
- Use existing configuration patterns
- Check environment detection (localhost vs server)

---

## Server & Environment Information

### Hosting Details:
- **Primary Domain**: `ethiosocialworker.org`
- **Subdomain**: `new.ethiosocialworks.org`
- **Hosting Type**: Shared Hosting (cPanel)
- **Server IP**: 192.250.239.84
- **Home Directory**: `/home/ethiosdt`
- **Document Root**: `/home/ethiosdt/public_html`
- **cPanel Username**: `ethiosdt`

### Database Configuration:
- **Localhost Database**: `ethiosocialworks`
  - User: `root`
  - Password: (empty)
  - Host: `localhost`
  
- **Production Database**: `ethiosdt_new_db`
  - User: `ethiosdt_new_user`
  - Password: `Ol9xN*dS7B=jX%}o`
  - Host: `localhost` (on remote server)
  
- **Legacy Production Database** (if exists): `ethiosdt_database`
  - User: `ethiosdt_admin`
  - Password: `atKBEC4Yzb@@Uxv`
  
- **Auto-detection**: Via `$_SERVER['HTTP_HOST']` in connection files
- **Remote Access**: Database remote access configured for IP 192.250.239.84

### Email Configuration:
- **Email Domain**: `@ethiosocialworker.org`
- **Default Email**: `info@ethiosocialworker.org`
- **Total Email Accounts**: 2

### Security & Access:
- **HTTPS**: Force HTTPS redirect enabled for `ethiosocialworker.org`
- **SSL Certificate**: Verify status in cPanel
- **cPanel Access**: https://ethiosocialworker.org:2083
- **phpMyAdmin**: Available through cPanel
- **File Manager**: Available through cPanel

### Important Notes:
- ⚠️ **NEVER** update `include/config.php` during deployments (contains client credentials)
- Always backup before database migrations
- Test on localhost first before deploying to production
- Always verify table structure using terminal before assuming schema
- For detailed server information, see `SERVER_INFORMATION.md`

---

## Important Notes

### Database:
- Always verify table structure using terminal before assuming schema
- Use prepared statements for all queries
- Test migrations on localhost first
- Backup before any database changes

### File Uploads:
- Use `@/upload` and `@/assets` directories in main project path
- Not inside swap folder
- Naming: `{timestamp}_{original_filename}`
- Validate file types and sizes

### Design Consistency:
- Match existing design theme (purple/blue gradients)
- Use existing color scheme (#667eea to #764ba2)
- Follow responsive design patterns
- Mobile-first approach
- Use existing icon libraries (Font Awesome, Remix Icon)

---

## Reference Files

### Must Read:
- `agents/agent_ethiosocial/rules.md` - General project rules
- `database_table_structure.md` - Database schema
- `agents/agent_ethiosocial/README.md` - Admin panel documentation
- `SERVER_INFORMATION.md` - Server configuration and hosting details

### Other Agents (for reference):
- `agents/agent_dev_1/README.md` - Email Subscription & Telegram Chatbot
- `agents/agent_dev_2/README.md` - Research & Resource Panel Enhancement
- `agents/agent_dev_3/README.md` - Testing & Quality Assurance

### Code Patterns:
- Check existing files for similar functionality
- Review `admin/include/` for handler patterns
- Review `include/` for public-facing handlers
- Check `admin/sidebar.php` for menu integration

---

## Task Management

### When Assigned a Task:
1. Create a task file (e.g., `TASK_[FEATURE_NAME].md`)
2. Document requirements and approach
3. Update this README with task status
4. Create RULES.md if needed for specific guidelines
5. Track progress in status files

### Task Completion:
1. Test all functionality thoroughly
2. Check for errors (PHP, JavaScript, console)
3. Test on mobile devices
4. Test on different browsers
5. Update documentation
6. Update status files
7. Mark task as complete

---

## Testing Checklist

Before marking any task as complete:
- [ ] Test on localhost first
- [ ] Test all CRUD operations
- [ ] Test form validations
- [ ] Test error handling
- [ ] Test mobile responsiveness
- [ ] Test on different browsers
- [ ] Check for JavaScript errors
- [ ] Check for PHP errors
- [ ] Verify database operations
- [ ] Test security measures
- [ ] Verify no conflicts with existing code

---

## Success Criteria

A task is complete when:
- [ ] All requirements implemented
- [ ] All tests passed
- [ ] No errors in console/logs
- [ ] Mobile responsive
- [ ] Security measures in place
- [ ] Documentation updated
- [ ] Code follows existing patterns
- [ ] No conflicts with existing code

---

## Communication

### Status Updates:
- Update this README when task status changes
- Create status files for complex tasks
- Document decisions and approaches

### Documentation:
- Update relevant documentation files
- Add code comments for complex logic
- Document API endpoints if created
- Update database structure documentation

---

**Status**: Ready for Assignment  
**Last Updated**: December 24, 2025

---

## Server Tools & Resources

### Available Tools:
- ✅ phpMyAdmin - Database management
- ✅ File Manager - File management
- ✅ Git Version Control - Version control
- ✅ JetBackup 5 - Automated backups
- ✅ Email Accounts - Email management
- ✅ WordPress Management - CMS tools
- ✅ Sitejet Builder - Site building tools

### Useful Links:
- **cPanel**: https://ethiosocialworker.org:2083
- **phpMyAdmin**: Available through cPanel
- **File Manager**: Available through cPanel
- **Email Webmail**: Available through cPanel

### Backup & Sync:
- **Backup Tool**: JetBackup 5 available
- **Sync Configuration**: Configurable in admin settings
- **Sync Direction**: Pull (Remote → Local) or Push (Local → Remote)
- **Sync History**: Tracked in `sync_logs` table

---

## Next Steps

Once assigned a task:
1. Read task requirements carefully
2. Review existing code patterns
3. Check database structure
4. Plan implementation approach
5. Create task file and start development
6. Update this README with progress

