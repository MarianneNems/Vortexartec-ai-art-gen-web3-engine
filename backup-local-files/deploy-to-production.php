<?php
/**
 * VORTEX AI Engine - Production Deployment Script
 * This script addresses the Cloudways hosting issues found in the debug log
 */

echo "🚀 VORTEX AI Engine - Production Deployment\n";
echo "==========================================\n\n";

// Production environment checklist
echo "📋 Production Environment Checklist:\n";
echo "====================================\n\n";

$checks = [
    'Database Connection' => false,
    'File Permissions' => false,
    'PHP Extensions' => false,
    'WordPress Integration' => false,
    'Plugin Activation' => false
];

// Check if we're in a WordPress environment
if (defined('ABSPATH')) {
    echo "✅ WordPress environment detected\n";
    $checks['WordPress Integration'] = true;
    
    // Test database connection
    global $wpdb;
    if (isset($wpdb) && $wpdb instanceof wpdb) {
        $wpdb->suppress_errors();
        $result = $wpdb->get_var("SELECT 1");
        $wpdb->suppress_errors(false);
        
        if ($result === '1') {
            echo "✅ Database connection successful\n";
            $checks['Database Connection'] = true;
        } else {
            echo "❌ Database connection failed\n";
        }
    }
    
    // Check file permissions
    $plugin_dir = plugin_dir_path(__FILE__);
    if (is_readable($plugin_dir . 'vortex-ai-engine.php')) {
        echo "✅ Plugin files readable\n";
        $checks['File Permissions'] = true;
    } else {
        echo "❌ Plugin files not readable\n";
    }
    
} else {
    echo "⚠️ Not in WordPress environment - this script should be run from WordPress\n\n";
    
    echo "🔧 To deploy to production:\n";
    echo "1. Upload all plugin files to wp-content/plugins/vortex-ai-engine/\n";
    echo "2. Ensure file permissions are correct (755 for directories, 644 for files)\n";
    echo "3. Run this script from within WordPress\n\n";
}

// Check PHP extensions
echo "\n🔍 PHP Extensions Check:\n";
$required_extensions = ['mysqli', 'json', 'curl', 'openssl', 'mbstring'];
$missing_extensions = [];

foreach ($required_extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "✅ $ext: Available\n";
    } else {
        echo "❌ $ext: Missing\n";
        $missing_extensions[] = $ext;
    }
}

if (empty($missing_extensions)) {
    echo "✅ All required extensions available\n";
    $checks['PHP Extensions'] = true;
} else {
    echo "❌ Missing extensions: " . implode(', ', $missing_extensions) . "\n";
}

// Production deployment instructions
echo "\n🚀 Production Deployment Instructions:\n";
echo "=====================================\n\n";

echo "1. **Upload Plugin Files**\n";
echo "   - Upload entire vortex-ai-engine folder to:\n";
echo "     /home/1205138.cloudwaysapps.com/ydhrrartdn/public_html/wp-content/plugins/\n";
echo "   - Ensure all files are uploaded completely\n\n";

echo "2. **Set File Permissions**\n";
echo "   ```bash\n";
echo "   chmod -R 755 wp-content/plugins/vortex-ai-engine/\n";
echo "   chmod 644 wp-content/plugins/vortex-ai-engine/*.php\n";
echo "   chown -R www-data:www-data wp-content/plugins/vortex-ai-engine/\n";
echo "   ```\n\n";

echo "3. **Fix Database Issues**\n";
echo "   The debug log showed: 'Access denied for user vortexartec2@localhost'\n";
echo "   Solutions:\n";
echo "   - Check wp-config.php database credentials\n";
echo "   - Verify database user exists and has permissions\n";
echo "   - Contact Cloudways support if needed\n\n";

echo "4. **Install Dependencies**\n";
echo "   ```bash\n";
echo "   cd wp-content/plugins/vortex-ai-engine/\n";
echo "   composer install --no-dev --optimize-autoloader\n";
echo "   ```\n\n";

echo "5. **Activate Plugin**\n";
echo "   - Go to WordPress Admin → Plugins\n";
echo "   - Find 'VORTEX AI Engine For the ARTS'\n";
echo "   - Click 'Activate'\n\n";

echo "6. **Verify Installation**\n";
echo "   - Check for 'VORTEX AI Engine' in admin menu\n";
echo "   - Run health check: /wp-content/plugins/vortex-ai-engine/vortex-health-check.php\n";
echo "   - Monitor error logs\n\n";

// Cloudways-specific instructions
echo "🌐 Cloudways-Specific Instructions:\n";
echo "==================================\n\n";

echo "1. **Access Cloudways Panel**\n";
echo "   - Log into Cloudways dashboard\n";
echo "   - Select your application\n";
echo "   - Go to 'Application Settings' → 'Advanced'\n\n";

echo "2. **Check PHP Configuration**\n";
echo "   - Verify PHP version is 7.4 or higher\n";
echo "   - Check that mysqli extension is enabled\n";
echo "   - Increase memory limit if needed\n\n";

echo "3. **Database Management**\n";
echo "   - Go to 'Database Manager'\n";
echo "   - Verify database credentials\n";
echo "   - Check database user permissions\n\n";

echo "4. **File Manager**\n";
echo "   - Use Cloudways File Manager to upload plugin\n";
echo "   - Set correct file permissions\n";
echo "   - Verify all files uploaded completely\n\n";

// Troubleshooting section
echo "🔧 Troubleshooting Common Issues:\n";
echo "=================================\n\n";

echo "**Database Connection Error (1045):**\n";
echo "- Verify database credentials in wp-config.php\n";
echo "- Check if database user exists\n";
echo "- Ensure user has proper permissions\n";
echo "- Try connecting with MySQL client\n\n";

echo "**Missing Files Error:**\n";
echo "- Re-upload missing files\n";
echo "- Check file permissions\n";
echo "- Verify no file corruption during upload\n";
echo "- Use FTP/SFTP for large files\n\n";

echo "**PHP Deprecation Warnings:**\n";
echo "- Update PHP to latest version\n";
echo "- Update plugin dependencies\n";
echo "- These are warnings, not critical errors\n\n";

echo "**Memory/Timeout Issues:**\n";
echo "- Increase PHP memory limit\n";
echo "- Increase max execution time\n";
echo "- Optimize plugin performance\n\n";

// Success criteria
echo "✅ Success Criteria:\n";
echo "===================\n\n";

$success_count = 0;
foreach ($checks as $check => $status) {
    if ($status) {
        echo "✅ $check: PASSED\n";
        $success_count++;
    } else {
        echo "❌ $check: FAILED\n";
    }
}

echo "\nOverall Status: $success_count/" . count($checks) . " checks passed\n\n";

if ($success_count == count($checks)) {
    echo "🎉 All checks passed! Plugin is ready for production.\n";
} else {
    echo "⚠️ Some checks failed. Please address the issues above.\n";
}

echo "\n📞 Need Help?\n";
echo "=============\n";
echo "- Check Cloudways documentation\n";
echo "- Contact Cloudways support\n";
echo "- Review error logs in wp-content/debug.log\n";
echo "- Run diagnostic scripts after deployment\n\n";

?> 