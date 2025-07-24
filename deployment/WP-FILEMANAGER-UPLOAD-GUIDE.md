# 🚀 VORTEX AI ENGINE - WP-FILEMANAGER UPLOAD GUIDE

## 📁 **File Locations and Upload Instructions**

### **🎯 Primary Files to Upload**

#### **1. WordPress Root Directory Files**
**Location:** `/public_html/` (or your WordPress root directory)

| File | Purpose | Upload Location |
|------|---------|----------------|
| `wp-config.php` | **Enhanced WordPress configuration** | `/public_html/wp-config.php` |
| `functions.php` | **Enhanced theme functions with debugging** | `/public_html/wp-content/themes/your-theme/functions.php` |

#### **2. Vortex AI Engine Plugin Directory**
**Location:** `/public_html/wp-content/plugins/vortex-ai-engine/`

| File/Directory | Purpose | Upload Location |
|----------------|---------|----------------|
| `vortex-ai-engine.php` | **Main plugin file** | `/public_html/wp-content/plugins/vortex-ai-engine/vortex-ai-engine.php` |
| `includes/` | **Core plugin classes** | `/public_html/wp-content/plugins/vortex-ai-engine/includes/` |
| `admin/` | **Admin dashboard files** | `/public_html/wp-content/plugins/vortex-ai-engine/admin/` |
| `assets/` | **CSS, JS, and media files** | `/public_html/wp-content/plugins/vortex-ai-engine/assets/` |
| `deployment/` | **Debug and utility scripts** | `/public_html/wp-content/plugins/vortex-ai-engine/deployment/` |
| `public/` | **Public interface files** | `/public_html/wp-content/plugins/vortex-ai-engine/public/` |

### **🔧 Step-by-Step Upload Process**

#### **Step 1: Access WP-FileManager**
1. Log into your hosting control panel
2. Navigate to **File Manager** or **WP-FileManager**
3. Open your website's root directory

#### **Step 2: Upload wp-config.php**
1. Navigate to `/public_html/`
2. **Backup existing wp-config.php** (rename to `wp-config-backup.php`)
3. Upload the enhanced `wp-config.php` file
4. **Verify file permissions** (644 or 640)

#### **Step 3: Upload functions.php**
1. Navigate to `/public_html/wp-content/themes/your-theme/`
2. **Backup existing functions.php** (rename to `functions-backup.php`)
3. Upload the enhanced `functions.php` file
4. **Verify file permissions** (644 or 640)

#### **Step 4: Create Plugin Directory Structure**
1. Navigate to `/public_html/wp-content/plugins/`
2. Create directory: `vortex-ai-engine/`
3. Set directory permissions to **755**

#### **Step 5: Upload Plugin Files**
1. **Main Plugin File:**
   - Upload `vortex-ai-engine.php` to `/public_html/wp-content/plugins/vortex-ai-engine/`

2. **Includes Directory:**
   - Create `/public_html/wp-content/plugins/vortex-ai-engine/includes/`
   - Upload all files from `includes/` directory
   - Create subdirectories as needed:
     - `includes/blockchain/`
     - `includes/ai-agents/`
     - `includes/tola-art/`
     - `includes/secret-sauce/`
     - `includes/artist-journey/`
     - `includes/subscriptions/`
     - `includes/cloud/`
     - `includes/database/`
     - `includes/storage/`

3. **Admin Directory:**
   - Create `/public_html/wp-content/plugins/vortex-ai-engine/admin/`
   - Upload all files from `admin/` directory

4. **Assets Directory:**
   - Create `/public_html/wp-content/plugins/vortex-ai-engine/assets/`
   - Create subdirectories:
     - `assets/css/`
     - `assets/js/`
     - `assets/images/`
   - Upload all asset files

5. **Deployment Directory:**
   - Create `/public_html/wp-content/plugins/vortex-ai-engine/deployment/`
   - Upload all deployment and debug files

6. **Public Directory:**
   - Create `/public_html/wp-content/plugins/vortex-ai-engine/public/`
   - Upload all public interface files

### **📋 Complete Directory Structure**

```
/public_html/
├── wp-config.php (ENHANCED)
├── wp-content/
│   ├── themes/
│   │   └── your-theme/
│   │       └── functions.php (ENHANCED)
│   └── plugins/
│       └── vortex-ai-engine/
│           ├── vortex-ai-engine.php
│           ├── includes/
│           │   ├── blockchain/
│           │   │   ├── class-vortex-solana-integration.php
│           │   │   ├── class-vortex-solana-database.php
│           │   │   ├── class-vortex-tola-token-handler.php
│           │   │   └── class-vortex-smart-contract-manager.php
│           │   ├── ai-agents/
│           │   ├── tola-art/
│           │   ├── secret-sauce/
│           │   ├── artist-journey/
│           │   ├── subscriptions/
│           │   ├── cloud/
│           │   ├── database/
│           │   └── storage/
│           ├── admin/
│           │   ├── class-vortex-solana-dashboard.php
│           │   ├── class-vortex-admin-dashboard.php
│           │   └── class-vortex-activity-monitor.php
│           ├── assets/
│           │   ├── css/
│           │   │   └── solana-dashboard.css
│           │   ├── js/
│           │   │   └── solana-dashboard.js
│           │   └── images/
│           ├── deployment/
│           │   ├── vortex-debug-dashboard.php
│           │   ├── comprehensive-audit.php
│           │   ├── setup-solana-devnet.ps1
│           │   └── verify-solana-integration.php
│           ├── public/
│           │   ├── class-vortex-public-interface.php
│           │   └── class-vortex-marketplace-frontend.php
│           ├── languages/
│           ├── contracts/
│           └── SOLANA-INTEGRATION-GUIDE.md
```

### **🔐 File Permissions**

| File Type | Permission | Description |
|-----------|------------|-------------|
| Directories | **755** | Read, write, execute for owner; read, execute for group and others |
| PHP Files | **644** | Read, write for owner; read for group and others |
| Configuration Files | **640** | Read, write for owner; read for group |
| Debug Files | **600** | Read, write for owner only (for security) |

### **✅ Verification Checklist**

#### **After Upload, Verify:**

- [ ] `wp-config.php` is in `/public_html/`
- [ ] `functions.php` is in `/public_html/wp-content/themes/your-theme/`
- [ ] Plugin directory exists: `/public_html/wp-content/plugins/vortex-ai-engine/`
- [ ] All subdirectories are created with correct permissions
- [ ] All PHP files have correct permissions (644 or 640)
- [ ] Debug files have restricted permissions (600)

#### **Test Access:**

- [ ] WordPress admin loads without errors
- [ ] Debug dashboard accessible: `/wp-content/plugins/vortex-ai-engine/deployment/vortex-debug-dashboard.php`
- [ ] Audit script accessible: `/wp-content/plugins/vortex-ai-engine/deployment/comprehensive-audit.php`
- [ ] Solana dashboard accessible via WordPress admin

### **🚨 Important Security Notes**

1. **Backup First:** Always backup existing files before uploading
2. **Permissions:** Set correct file permissions for security
3. **Debug Files:** Restrict access to debug files in production
4. **SSL:** Ensure SSL is enabled for admin access
5. **Updates:** Keep WordPress and plugins updated

### **🔧 Troubleshooting**

#### **Common Issues:**

1. **500 Internal Server Error:**
   - Check file permissions
   - Verify PHP syntax in uploaded files
   - Check error logs

2. **Plugin Not Activating:**
   - Verify all required files are uploaded
   - Check file permissions
   - Review error logs

3. **Debug Dashboard Not Loading:**
   - Verify file exists in correct location
   - Check file permissions
   - Ensure user has admin capabilities

### **📞 Support**

If you encounter issues during upload:

1. Check the debug dashboard: `/wp-content/plugins/vortex-ai-engine/deployment/vortex-debug-dashboard.php`
2. Run the comprehensive audit: `/wp-content/plugins/vortex-ai-engine/deployment/comprehensive-audit.php`
3. Review error logs in `/wp-content/debug.log`

---

**🎯 Ready for Upload!** All files are properly configured and ready for deployment to your WordPress site via WP-FileManager. 