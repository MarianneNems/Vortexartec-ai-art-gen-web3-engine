<?php
/**
 * VORTEX AI Engine - Local Environment Fix Script
 * This script addresses the issues found in the diagnostic tests
 */

echo "🔧 VORTEX AI Engine - Local Environment Fix\n";
echo "==========================================\n\n";

// Check current PHP configuration
echo "📋 Current PHP Configuration:\n";
echo "PHP Version: " . PHP_VERSION . "\n";
echo "PHP INI Location: " . php_ini_loaded_file() . "\n";
echo "Memory Limit: " . ini_get('memory_limit') . "\n";
echo "Max Execution Time: " . ini_get('max_execution_time') . "\n\n";

// Check required extensions
echo "🔍 Required Extensions Check:\n";
$required_extensions = [
    'mysqli' => 'Database connectivity (CRITICAL)',
    'json' => 'JSON processing',
    'curl' => 'API communications',
    'openssl' => 'Security features',
    'mbstring' => 'String handling',
    'xml' => 'XML processing'
];

$missing_extensions = [];
foreach ($required_extensions as $ext => $purpose) {
    if (extension_loaded($ext)) {
        echo "✅ $ext: Available ($purpose)\n";
    } else {
        echo "❌ $ext: Missing ($purpose)\n";
        $missing_extensions[] = $ext;
    }
}

echo "\n";

// Provide solutions for missing extensions
if (!empty($missing_extensions)) {
    echo "🚨 CRITICAL: Missing Extensions Found\n";
    echo "=====================================\n\n";
    
    if (in_array('mysqli', $missing_extensions)) {
        echo "🔧 Fix for mysqli (Database Extension):\n";
        echo "1. Open your php.ini file: " . php_ini_loaded_file() . "\n";
        echo "2. Find the line: ;extension=mysqli\n";
        echo "3. Remove the semicolon: extension=mysqli\n";
        echo "4. Save the file and restart your web server\n\n";
        
        echo "Alternative: Enable via command line:\n";
        echo "php -d extension=mysqli your-script.php\n\n";
    }
    
    if (in_array('curl', $missing_extensions)) {
        echo "🔧 Fix for curl:\n";
        echo "1. Open php.ini: " . php_ini_loaded_file() . "\n";
        echo "2. Uncomment: extension=curl\n";
        echo "3. Restart web server\n\n";
    }
    
    if (in_array('openssl', $missing_extensions)) {
        echo "🔧 Fix for openssl:\n";
        echo "1. Open php.ini: " . php_ini_loaded_file() . "\n";
        echo "2. Uncomment: extension=openssl\n";
        echo "3. Restart web server\n\n";
    }
}

// Check if we're in a WordPress environment
echo "🌐 WordPress Environment Check:\n";
$wp_paths = [
    dirname(__FILE__) . '/wp-config.php',
    dirname(dirname(__FILE__)) . '/wp-config.php',
    dirname(dirname(dirname(__FILE__))) . '/wp-config.php',
    dirname(__FILE__) . '/wp-load.php',
    dirname(dirname(__FILE__)) . '/wp-load.php'
];

$wp_found = false;
foreach ($wp_paths as $path) {
    if (file_exists($path)) {
        echo "✅ Found WordPress file: $path\n";
        $wp_found = true;
    }
}

if (!$wp_found) {
    echo "⚠️ WordPress not found in current directory structure\n";
    echo "This is normal for local development. The plugin will work when deployed to a WordPress site.\n\n";
}

// Test database connectivity if mysqli is available
if (extension_loaded('mysqli')) {
    echo "🗄️ Database Connectivity Test:\n";
    
    // Try to load WordPress if available
    $wp_load_path = null;
    foreach ($wp_paths as $path) {
        if (file_exists($path) && basename($path) === 'wp-load.php') {
            $wp_load_path = $path;
            break;
        }
    }
    
    if ($wp_load_path) {
        try {
            require_once($wp_load_path);
            global $wpdb;
            
            if (isset($wpdb) && $wpdb instanceof wpdb) {
                $wpdb->suppress_errors();
                $result = $wpdb->get_var("SELECT 1");
                $wpdb->suppress_errors(false);
                
                if ($result === '1') {
                    echo "✅ WordPress database connection successful\n";
                } else {
                    echo "❌ WordPress database connection failed\n";
                }
            }
        } catch (Exception $e) {
            echo "❌ Error loading WordPress: " . $e->getMessage() . "\n";
        }
    } else {
        echo "⚠️ WordPress not loaded - cannot test database connection\n";
    }
} else {
    echo "❌ Cannot test database connectivity - mysqli extension missing\n";
}

echo "\n";

// Create a test WordPress environment for local development
echo "🏗️ Local Development Setup:\n";
echo "==========================\n\n";

echo "To test the plugin locally, you can:\n\n";

echo "1. **Install WordPress locally:**\n";
echo "   - Use XAMPP, WAMP, or similar\n";
echo "   - Install WordPress in a subdirectory\n";
echo "   - Place this plugin in wp-content/plugins/\n\n";

echo "2. **Use Docker (Recommended):**\n";
echo "   Create a docker-compose.yml file:\n";
echo "   ```yaml\n";
echo "   version: '3.8'\n";
echo "   services:\n";
echo "     wordpress:\n";
echo "       image: wordpress:latest\n";
echo "       ports:\n";
echo "         - '8080:80'\n";
echo "       environment:\n";
echo "         WORDPRESS_DB_HOST: db\n";
echo "         WORDPRESS_DB_USER: wordpress\n";
echo "         WORDPRESS_DB_PASSWORD: wordpress\n";
echo "         WORDPRESS_DB_NAME: wordpress\n";
echo "       volumes:\n";
echo "         - ./wp-content:/var/www/html/wp-content\n";
echo "     db:\n";
echo "       image: mysql:5.7\n";
echo "       environment:\n";
echo "         MYSQL_DATABASE: wordpress\n";
echo "         MYSQL_USER: wordpress\n";
echo "         MYSQL_PASSWORD: wordpress\n";
echo "         MYSQL_ROOT_PASSWORD: somewordpress\n";
echo "   ```\n\n";

echo "3. **Quick PHP Test:**\n";
echo "   Create a simple test file to verify the plugin loads:\n";
echo "   ```php\n";
echo "   <?php\n";
echo "   // Test if plugin classes can be loaded\n";
echo "   require_once 'vortex-ai-engine.php';\n";
echo "   echo 'Plugin loaded successfully!';\n";
echo "   ```\n\n";

// Check plugin file integrity
echo "📁 Plugin File Integrity Check:\n";
echo "===============================\n\n";

$critical_files = [
    'vortex-ai-engine.php' => 'Main plugin file',
    'includes/class-vortex-db-setup.php' => 'Database setup',
    'includes/class-vortex-logger.php' => 'Logging system',
    'admin/class-vortex-admin.php' => 'Admin interface',
    'vendor/autoload.php' => 'Composer autoloader'
];

foreach ($critical_files as $file => $description) {
    $file_path = dirname(__FILE__) . '/' . $file;
    if (file_exists($file_path)) {
        $size = filesize($file_path);
        echo "✅ $description: $file ($size bytes)\n";
    } else {
        echo "❌ $description: $file (MISSING)\n";
    }
}

echo "\n";

// Summary and next steps
echo "📊 Summary:\n";
echo "===========\n\n";

if (empty($missing_extensions)) {
    echo "🎉 All required extensions are available!\n";
    echo "Your local environment is ready for development.\n\n";
} else {
    echo "⚠️ Missing extensions detected:\n";
    foreach ($missing_extensions as $ext) {
        echo "   - $ext\n";
    }
    echo "\nPlease fix these before proceeding.\n\n";
}

echo "🚀 Next Steps:\n";
echo "==============\n";
echo "1. Fix missing extensions (if any)\n";
echo "2. Set up a local WordPress environment\n";
echo "3. Test the plugin in WordPress\n";
echo "4. Run the health check again\n\n";

echo "📞 Need Help?\n";
echo "=============\n";
echo "- Check the DEPLOYMENT-GUIDE.md for detailed instructions\n";
echo "- Run vortex-health-check.php after fixing extensions\n";
echo "- Test with test-database-connection.php in WordPress environment\n\n";

?> 