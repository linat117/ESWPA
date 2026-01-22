# Server Information & Configuration

**Last Updated**: December 23, 2025  
**Purpose**: Centralized server and hosting information for future reference

---

## 🌐 Hosting Server Information

### Primary Domain
- **Domain**: `ethiosocialworker.org`
- **Subdomain**: `new.ethiosocialworks.org` (Note: user mentioned `new.ethiosocialworks.org` but domain is `ethiosocialworker.org` - please verify)

### Server Details
- **Hosting Type**: Shared Hosting (cPanel)
- **Control Panel**: cPanel
- **Server IP**: 192.250.239.84 (from cPanel interface)
- **Home Directory**: `/home/ethiosdt`
- **Document Root**: `/home/ethiosdt/public_html`

---

## 🗄️ Database Information

### Production Database (Remote Server)
- **Database Name**: `ethiosdt_new_db`
- **Database User**: `ethiosdt_new_user`
- **Database Password**: `Ol9xN*dS7B=jX%}o`
- **Host**: `localhost` (on remote server)
- **Connection**: MySQL/MariaDB

### Local Development Database
- **Database Name**: `ethiosocialworks`
- **Database User**: `root`
- **Database Password**: `` (empty)
- **Host**: `localhost`
- **Connection**: MySQL/MariaDB (XAMPP)

### Legacy Production Database (if exists)
- **Database Name**: `ethiosdt_database`
- **Database User**: `ethiosdt_admin`
- **Database Password**: `atKBEC4Yzb@@Uxv`
- **Host**: `localhost` (on remote server)

---

## 📧 Email Configuration

### Email Accounts
- **Total Email Accounts**: 2 (from cPanel stats)
- **Email Domain**: `@ethiosocialworker.org`
- **Default Email**: `info@ethiosocialworker.org` (from settings)

---

## 🔐 Access Information

### cPanel Access
- **Username**: `ethiosdt`
- **Last Login IP**: 196.188.252.131 (from cPanel interface)
- **User Analytics ID**: `9d1d48da-4e4c-4690...` (partial)

### FTP Access
- **FTP Accounts**: 0 (from cPanel stats)
- **FTP Host**: `ethiosocialworker.org` or server IP

### SSH Access
- **SSH Status**: Not specified (may require enabling in cPanel)
- **SSH Port**: Typically 22 (if enabled)

---

## 📊 Server Statistics

### Disk Usage
- **Current Usage**: 515.66 MB
- **Limit**: Unlimited (∞)

### File Usage
- **Current Files**: 4,287
- **Limit**: Unlimited (∞)

### Database Disk Usage
- **Current Usage**: 248 KB
- **Limit**: Unlimited (∞)

### Bandwidth
- **Current Usage**: 1.23 GB
- **Limit**: Unlimited (∞)

### Resource Limits
- **Addon Domains**: 0 / ∞
- **Subdomains**: 0 / ∞
- **Alias Domains**: 0 / ∞
- **Email Accounts**: 2 / ∞
- **Mailing Lists**: 0 / ∞
- **Autoresponders**: 0 / ∞
- **Forwarders**: 0 / ∞
- **Email Filters**: 0 / ∞
- **FTP Accounts**: 0 / ∞
- **Databases**: 1 / ∞
- **CPU Usage**: 0 / 100 (0%)
- **Entry Processes**: 0 / 30 (0%)

---

## 🔄 Data Synchronization

### Sync Configuration
- **Remote Host**: To be configured in admin settings
- **Remote Database**: `ethiosdt_new_db`
- **Sync Direction**: 
  - Pull: Remote → Local
  - Push: Local → Remote

### Sync Status
- **Enabled**: Configurable in admin panel
- **Last Sync**: Tracked in sync_logs table
- **Sync History**: Available in admin panel

---

## 🛠️ Server Tools Available

### Database Management
- ✅ phpMyAdmin (available)
- ✅ Remote Database Access (configured)
- ✅ Database Wizard

### File Management
- ✅ File Manager
- ✅ FTP Accounts
- ✅ Web Disk
- ✅ Git Version Control
- ✅ JetBackup 5

### Email Management
- ✅ Email Accounts
- ✅ Forwarders
- ✅ Email Routing
- ✅ Autoresponders
- ✅ Spam Filters

### Domain Management
- ✅ WordPress Management
- ✅ Site Publisher
- ✅ Sitejet Builder
- ✅ Social Media Management

---

## 🔒 Security Settings

### HTTPS
- **Force HTTPS Redirect**: Enabled for `ethiosocialworker.org`
- **SSL Certificate**: Status unknown (check in cPanel)

### Database Access
- **Remote Access Host**: 192.250.239.84 (configured)
- **Local Access**: localhost only

---

## 📝 Notes

1. **Subdomain Verification**:  `new.ethiosocialworker.org`  ; this is correct
2. **Database Migration**: New database `ethiosdt_new_db` appears to be set up for production
3. **Remote Access**: Database remote access is configured for IP 192.250.239.84
4. **Backup**: JetBackup 5 is available for automated backups
5. **SSL**: Verify SSL certificate status in cPanel

---

## 🔗 Useful Links

- **cPanel**: https://ethiosocialworker.org:2083 (or standard cPanel port)   ; this is correct
- **phpMyAdmin**: Available through cPanel  
- **File Manager**: Available through cPanel  
- **Email Webmail**: Available through cPanel 

---

## ⚠️ Important Reminders

1. **Password Security**: Database password is stored in code - ensure proper security
2. **Backup**: Always backup before sync operations
3. **Remote Access**: Database remote access should be restricted to necessary IPs only
4. **SSL**: Ensure HTTPS is properly configured for production
5. **Documentation**: Update this file when server configuration changes

---

**Maintained By**: Development Team  
**Confidentiality**: This file contains sensitive information - keep secure

