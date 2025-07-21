# 🚀 VORTEX AI Engine - Real-Time Logging System

## 📋 **Overview**

The VORTEX AI Engine includes a comprehensive real-time logging system that provides secure, encrypted logging of all WordPress activities with automatic synchronization to GitHub. This system ensures complete privacy and security while maintaining detailed audit trails.

---

## 🔒 **Security Features**

### **Data Encryption**
- **AES-256-CBC encryption** for all sensitive data
- **Automatic pattern detection** for sensitive information
- **Secure key management** with WordPress database storage
- **Encrypted log entries** marked with `[ENCRYPTED:...]` tags

### **Privacy Protection**
- **IP address masking** (last octet hidden)
- **Wallet address masking** (shows only first 6 and last 4 characters)
- **Sensitive pattern filtering** (passwords, tokens, keys, secrets)
- **User data anonymization** for non-admin users

### **Access Control**
- **Admin-only access** to log management
- **Role-based permissions** for log viewing
- **Secure API authentication** with GitHub tokens
- **Audit trail** for all administrative actions

---

## 📊 **System Architecture**

### **Core Components**

1. **VORTEX_Realtime_Logger** - Main logging engine
2. **VORTEX_Log_Database** - Database management
3. **VORTEX_Log_Admin** - Admin interface
4. **VORTEX_GitHub_Settings** - GitHub integration settings
5. **VORTEX_Log_Integration** - System coordination

### **Database Tables**

```sql
-- Main logs table
wp_vortex_logs
- id (bigint, primary key)
- timestamp (varchar)
- level (varchar)
- message (longtext)
- context (longtext)
- user_id (bigint)
- ip_address (varchar)
- request_uri (varchar)
- session_id (varchar)
- encrypted (tinyint)
- created_at (datetime)

-- Statistics table
wp_vortex_log_stats
- id (bigint, primary key)
- date (date)
- level (varchar)
- count (int)
- created_at (datetime)
- updated_at (datetime)

-- Alerts table
wp_vortex_log_alerts
- id (bigint, primary key)
- alert_type (varchar)
- alert_message (text)
- alert_data (longtext)
- triggered_at (datetime)
- resolved_at (datetime)
- status (varchar)

-- GitHub sync history
wp_vortex_github_sync
- id (bigint, primary key)
- sync_date (datetime)
- logs_count (int)
- success (tinyint)
- error_message (text)
- repository (varchar)
- branch (varchar)
```

---

## 🔄 **Real-Time Logging Events**

### **WordPress Core Events**
- ✅ **WordPress initialization**
- ✅ **User login/logout**
- ✅ **User registration/updates**
- ✅ **Plugin activation/deactivation**
- ✅ **Theme switching**
- ✅ **Post creation/updates**
- ✅ **Comment posting**
- ✅ **Media uploads**
- ✅ **Option changes**
- ✅ **Failed login attempts**

### **VORTEX AI Engine Events**
- ✅ **AI Agent activation/deactivation**
- ✅ **Blockchain transactions**
- ✅ **Artwork creation**
- ✅ **NFT minting**
- ✅ **Smart contract interactions**
- ✅ **Marketplace activities**
- ✅ **Security events**

### **Custom Events**
- ✅ **Critical system errors**
- ✅ **Security violations**
- ✅ **Performance issues**
- ✅ **Database operations**
- ✅ **API calls**

---

## 🌐 **GitHub Integration**

### **Secure Synchronization**
- **Encrypted data transmission** to GitHub
- **Personal access token authentication**
- **Repository-specific configuration**
- **Branch-based organization**
- **Automatic conflict resolution**

### **Sync Configuration**
```php
// GitHub settings
vortex_github_logging_enabled = true
vortex_github_repository = "username/vortex-ai-engine"
vortex_github_token = "[ENCRYPTED:...]"
vortex_github_branch = "main"
vortex_github_sync_interval = 300 // 5 minutes
vortex_github_encrypt_sensitive = true
```

### **Sync Process**
1. **Collect recent logs** from database
2. **Filter sensitive data** and encrypt
3. **Format for GitHub** (Markdown)
4. **Push to repository** via GitHub API
5. **Record sync status** in database
6. **Handle errors** and retry logic

---

## 🎛️ **Admin Interface**

### **Real-Time Dashboard**
- **Live log viewing** with auto-refresh
- **Advanced filtering** by level, user, date
- **Statistics visualization** with charts
- **System health monitoring**
- **Alert management**

### **Log Management**
- **Export logs** to CSV/JSON
- **Clear old logs** with retention policies
- **Search and filter** capabilities
- **Bulk operations** for log management
- **Database optimization** tools

### **GitHub Settings**
- **Connection testing** and validation
- **Repository configuration**
- **Sync history** and status
- **Error monitoring** and alerts
- **Security information** display

---

## 📈 **Log Levels**

### **Standard Levels**
- **DEBUG** - Detailed debugging information
- **INFO** - General information messages
- **WARNING** - Warning conditions
- **ERROR** - Error conditions
- **CRITICAL** - Critical system errors
- **SECURITY** - Security-related events

### **Level Colors**
- 🔵 **DEBUG** - Gray (#6c757d)
- 🔵 **INFO** - Blue (#17a2b8)
- 🟡 **WARNING** - Yellow (#ffc107)
- 🔴 **ERROR** - Red (#dc3545)
- 🔴 **CRITICAL** - Dark Red (#721c24)
- 🟢 **SECURITY** - Green (#28a745)

---

## 🔧 **Configuration Options**

### **System Settings**
```php
// Log retention
vortex_log_retention_days = 30
vortex_log_max_entries = 10000

// Performance
vortex_log_auto_cleanup = true
vortex_log_optimization_interval = 24 // hours

// Security
vortex_log_encryption_key = "[AUTO_GENERATED]"
vortex_log_ip_masking = true
vortex_log_sensitive_patterns = "password,token,key,secret,auth,credential,private"
```

### **GitHub Settings**
```php
// Integration
vortex_github_logging_enabled = true
vortex_github_repository = "username/vortex-ai-engine"
vortex_github_token = "[ENCRYPTED]"
vortex_github_branch = "main"

// Sync settings
vortex_github_sync_interval = 300
vortex_github_encrypt_sensitive = true
vortex_github_exclude_patterns = "password\ntoken\nkey\nsecret\nauth\ncredential\nprivate"
```

---

## 🚀 **Usage Examples**

### **Manual Logging**
```php
// Get logger instance
$logger = VORTEX_Realtime_Logger::get_instance();

// Log information
$logger->log('INFO', 'User action completed', array(
    'user_id' => 123,
    'action' => 'artwork_upload',
    'artwork_id' => 456
));

// Log sensitive data (automatically encrypted)
$logger->log('SECURITY', 'API key accessed', array(
    'api_key' => 'sk-1234567890abcdef',
    'user_id' => 123
), true);
```

### **Custom Event Logging**
```php
// Log AI agent activity
do_action('vortex_ai_agent_activated', 'HURAII');

// Log blockchain transaction
do_action('vortex_blockchain_transaction', array(
    'hash' => '0x1234567890abcdef',
    'blockchain' => 'Solana',
    'amount' => 100,
    'from' => 'wallet_address_1',
    'to' => 'wallet_address_2'
));

// Log artwork creation
do_action('vortex_artwork_created', array(
    'id' => 789,
    'artist_id' => 123,
    'title' => 'Digital Masterpiece',
    'medium' => 'AI Generated',
    'price' => 1000
));
```

---

## 🔍 **Monitoring & Alerts**

### **System Health Checks**
- **Database connectivity** and performance
- **GitHub API status** and authentication
- **Storage space** and permissions
- **Log file integrity** and rotation
- **Encryption key** availability

### **Alert Types**
- **Critical errors** requiring immediate attention
- **Security violations** and suspicious activity
- **Performance degradation** and slow queries
- **Sync failures** and GitHub connectivity issues
- **Storage warnings** and disk space alerts

### **Notification Methods**
- **Admin dashboard** alerts
- **Email notifications** for critical events
- **WordPress admin notices** for configuration issues
- **Real-time WebSocket** updates (if enabled)

---

## 📊 **Performance Optimization**

### **Database Optimization**
- **Automatic cleanup** of old logs
- **Index optimization** for fast queries
- **Table compression** for storage efficiency
- **Query caching** for repeated operations
- **Connection pooling** for high traffic

### **Storage Management**
- **Log rotation** by date
- **Compression** of old log files
- **Archive management** for long-term storage
- **Disk space monitoring** and alerts
- **Backup integration** with WordPress

### **Caching Strategy**
- **Statistics caching** for dashboard performance
- **Filter results caching** for repeated queries
- **GitHub API response caching** to reduce API calls
- **Configuration caching** for faster access

---

## 🔐 **Security Best Practices**

### **Data Protection**
1. **Always encrypt sensitive data** before logging
2. **Use pattern detection** to identify sensitive information
3. **Implement access controls** for log viewing
4. **Regular key rotation** for encryption keys
5. **Secure transmission** to GitHub

### **Access Control**
1. **Admin-only access** to log management
2. **Role-based permissions** for different log levels
3. **Session management** for admin users
4. **IP whitelisting** for sensitive operations
5. **Audit trails** for all administrative actions

### **Compliance**
1. **GDPR compliance** with data anonymization
2. **Data retention policies** for log cleanup
3. **Right to be forgotten** implementation
4. **Data portability** for log exports
5. **Privacy by design** in all features

---

## 🛠️ **Troubleshooting**

### **Common Issues**

#### **GitHub Sync Failures**
```php
// Check GitHub token validity
$token = get_option('vortex_github_token');
$repository = get_option('vortex_github_repository');

// Test connection
$url = "https://api.github.com/repos/{$repository}";
$response = wp_remote_get($url, array(
    'headers' => array('Authorization' => 'token ' . $token)
));
```

#### **Database Performance Issues**
```php
// Check table size
$logs_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}vortex_logs");

// Optimize if needed
if ($logs_count > 50000) {
    VORTEX_Log_Database::clean_old_data(30);
    VORTEX_Log_Database::optimize_tables();
}
```

#### **Encryption Issues**
```php
// Regenerate encryption key
$new_key = wp_generate_password(32, true, true);
update_option('vortex_log_encryption_key', $new_key);

// Re-encrypt existing data if needed
$logger = VORTEX_Realtime_Logger::get_instance();
$logger->re_encrypt_logs($new_key);
```

### **Debug Mode**
```php
// Enable debug logging
define('VORTEX_LOG_DEBUG', true);

// Check debug logs
$debug_logs = $logger->get_logs(array('level' => 'DEBUG'));
```

---

## 📚 **API Reference**

### **VORTEX_Realtime_Logger Methods**
- `get_instance()` - Get singleton instance
- `log($level, $message, $context, $sensitive)` - Log entry
- `get_logs($filters)` - Retrieve logs
- `clear_old_logs($days)` - Clean old entries
- `sync_logs_to_github()` - Sync to GitHub

### **VORTEX_Log_Database Methods**
- `create_tables()` - Create database tables
- `update_statistics()` - Update log statistics
- `get_statistics($days)` - Get statistics
- `create_alert($type, $message, $data)` - Create alert
- `clean_old_data($days)` - Clean old data

### **Hooks and Filters**
- `vortex_log_entry_created` - Fired when log entry is created
- `vortex_github_sync` - Fired for GitHub sync
- `vortex_log_cleanup` - Fired for log cleanup
- `vortex_log_statistics` - Fired for statistics update

---

## 🎯 **Future Enhancements**

### **Planned Features**
- **Real-time WebSocket** notifications
- **Advanced analytics** and reporting
- **Machine learning** for anomaly detection
- **Multi-repository** GitHub sync
- **Cloud storage** integration (AWS S3, Google Cloud)
- **Mobile app** for log monitoring
- **API endpoints** for external integrations
- **Custom dashboards** and widgets

### **Performance Improvements**
- **Redis caching** for high-traffic sites
- **Elasticsearch** integration for log search
- **Distributed logging** for multi-server setups
- **Real-time streaming** to external services
- **Advanced compression** algorithms

---

## 📞 **Support**

For technical support and questions about the real-time logging system:

1. **Check the documentation** in this file
2. **Review the admin interface** for configuration options
3. **Check system health** in the admin dashboard
4. **Enable debug mode** for detailed troubleshooting
5. **Contact support** with specific error messages

---

**🎉 The VORTEX AI Engine Real-Time Logging System provides enterprise-grade logging with complete privacy and security for your WordPress art marketplace!** 