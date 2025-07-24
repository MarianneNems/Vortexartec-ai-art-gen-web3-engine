# 📁 Plesk File Manager Upload Guide

## 🚀 **Uploading Vortex AI Engine via Plesk File Manager**

This guide will walk you through uploading and installing the Vortex AI Engine plugin using Plesk File Manager.

---

## 📋 **Pre-Upload Checklist**

### **✅ Files Ready**
- [ ] `vortex-ai-engine-production.zip` - Plugin package created
- [ ] Plesk access credentials ready
- [ ] WordPress site details confirmed
- [ ] Database backup completed (if updating existing)

### **✅ Plesk Requirements**
- [ ] Plesk File Manager access
- [ ] WordPress installation confirmed
- [ ] Plugin directory writable permissions
- [ ] PHP version 7.4+ confirmed

---

## 🎯 **Step-by-Step Upload Process**

### **Step 1: Access Plesk File Manager**

1. **Login to Plesk**
   - Go to your Plesk control panel
   - Login with your admin credentials

2. **Navigate to File Manager**
   - Click on your domain
   - Go to **Files & Directories** → **File Manager**
   - Or use the **File Manager** shortcut

3. **Navigate to WordPress Plugin Directory**
   ```
   /httpdocs/wp-content/plugins/
   ```
   - Click through the directory structure
   - Ensure you're in the `plugins` folder

### **Step 2: Upload Plugin Package**

1. **Upload the ZIP File**
   - Click **Upload** button in File Manager
   - Select `vortex-ai-engine-production.zip`
   - Click **Upload** to transfer the file

2. **Extract the Plugin**
   - Right-click on `vortex-ai-engine-production.zip`
   - Select **Extract**
   - Choose extraction location: `/httpdocs/wp-content/plugins/`
   - Click **Extract**

3. **Verify Extraction**
   - Confirm `vortex-ai-engine` folder is created
   - Check that all files are present:
     - `vortex-ai-engine.php` (main plugin file)
     - `includes/` directory
     - `assets/` directory
     - `deployment/` directory

### **Step 3: Set File Permissions**

1. **Set Directory Permissions**
   - Right-click on `vortex-ai-engine` folder
   - Select **Change Permissions**
   - Set to **755** (rwxr-xr-x)

2. **Set File Permissions**
   - Select all files in the plugin directory
   - Right-click → **Change Permissions**
   - Set to **644** (rw-r--r--)

3. **Verify Permissions**
   - Ensure directories are **755**
   - Ensure files are **644**
   - Check that web server can read/write

### **Step 4: Clean Up**

1. **Remove ZIP File**
   - Delete `vortex-ai-engine-production.zip`
   - Keep only the extracted plugin folder

2. **Verify Structure**
   ```
   /httpdocs/wp-content/plugins/vortex-ai-engine/
   ├── vortex-ai-engine.php
   ├── includes/
   ├── assets/
   ├── admin/
   ├── public/
   ├── deployment/
   └── README.md
   ```

---

## ⚙️ **Post-Upload Configuration**

### **Step 1: WordPress Plugin Activation**

1. **Access WordPress Admin**
   - Go to `https://yoursite.com/wp-admin`
   - Login with admin credentials

2. **Activate Plugin**
   - Go to **Plugins** → **Installed Plugins**
   - Find **Vortex AI Engine**
   - Click **Activate**

3. **Accept Agreement**
   - Agreement modal should appear
   - Read and accept Terms of Service
   - Click **Accept & Continue**

### **Step 2: Environment Configuration**

1. **Configure wp-config.php**
   ```php
   // Add these lines to wp-config.php
   define('AWS_ACCESS_KEY_ID', 'your_aws_key');
   define('AWS_SECRET_ACCESS_KEY', 'your_aws_secret');
   define('AWS_DEFAULT_REGION', 'us-east-1');
   ```

2. **Update Salts (if needed)**
   - Go to https://api.wordpress.org/secret-key/1.1/salt/
   - Generate new salts
   - Update wp-config.php

### **Step 3: Verify Installation**

1. **Check Admin Menu**
   - Look for **Vortex AI Engine** in admin menu
   - Verify all submenus are present

2. **Test Health Check**
   - Go to **Vortex AI Engine** → **Health Check**
   - Verify all systems are healthy

3. **Test Shortcodes**
   - Create a test page
   - Add shortcode: `[vortex_swap]`
   - Verify it renders correctly

---

## 🔧 **Troubleshooting Common Issues**

### **Upload Issues**

**File Upload Fails**
```
Solution: Check file size limits in Plesk
- Go to Plesk → Tools & Settings → PHP Settings
- Increase upload_max_filesize and post_max_size
```

**Extraction Fails**
```
Solution: Check disk space and permissions
- Verify sufficient disk space
- Check directory write permissions
- Try extracting in smaller chunks
```

### **Activation Issues**

**Plugin Won't Activate**
```
Solution: Check PHP error log
- Go to Plesk → Logs → Error Log
- Look for PHP fatal errors
- Verify all required files are present
```

**Agreement Modal Not Showing**
```
Solution: Check JavaScript and CSS files
- Verify assets/js/agreement.js exists
- Verify assets/css/agreement.css exists
- Check browser console for errors
```

### **Permission Issues**

**Files Not Accessible**
```
Solution: Reset permissions
- Set directories to 755
- Set files to 644
- Ensure web server ownership
```

---

## 📊 **Verification Checklist**

### **✅ Upload Verification**
- [ ] ZIP file uploaded successfully
- [ ] Plugin extracted to correct location
- [ ] All files and directories present
- [ ] Permissions set correctly

### **✅ WordPress Integration**
- [ ] Plugin appears in WordPress admin
- [ ] Plugin activates without errors
- [ ] Agreement modal displays
- [ ] Admin menu items created

### **✅ Functionality Testing**
- [ ] Health check endpoint works
- [ ] Shortcodes render correctly
- [ ] Admin dashboard loads
- [ ] No PHP errors in logs

---

## 🚀 **Next Steps After Upload**

### **Immediate Actions**
1. **Run Health Check**
   - Go to Vortex AI Engine → Health Check
   - Verify all systems are healthy

2. **Configure AWS Credentials**
   - Add AWS keys to wp-config.php
   - Test AWS integration

3. **Test Core Features**
   - Test all shortcodes
   - Verify AI agents respond
   - Check agreement flow

### **Production Readiness**
1. **Monitor for 24 Hours**
   - Check error logs
   - Monitor performance
   - Gather user feedback

2. **Enable Monitoring**
   - Set up health check alerts
   - Configure performance monitoring
   - Enable error tracking

---

## 📞 **Support Resources**

### **Plesk Support**
- **Plesk Documentation** - Official Plesk guides
- **Plesk Support** - Technical support for Plesk issues
- **File Manager Help** - Built-in help system

### **WordPress Support**
- **WordPress Codex** - Plugin development documentation
- **WordPress Support Forums** - Community support
- **WordPress Debug** - Built-in debugging tools

### **Vortex AI Engine Support**
- **Health Dashboard** - Built-in monitoring
- **Error Logs** - Detailed error tracking
- **Admin Interface** - Configuration and management

---

## 🎉 **Upload Complete!**

Once you've completed the upload and verification:

1. **Your Vortex AI Engine is live!**
2. **Monitor the health dashboard**
3. **Test all features thoroughly**
4. **Gather user feedback**
5. **Enjoy your AI-powered marketplace!**

**The agreement policy is active, and your system is ready for production use! 🚀** 