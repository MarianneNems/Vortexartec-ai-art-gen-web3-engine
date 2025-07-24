<?php
/**
 * VORTEX AI Engine - WordPress Config Fix
 * 
 * This script fixes the wp-config.php file by removing Redis configuration
 * that's causing the staging WordPress site to crash.
 */

echo "<h1>🔧 WordPress Config Fix</h1>\n";
echo "<div style='font-family: monospace; background: #f5f5f5; padding: 20px; border-radius: 8px;'>\n";

// Check if we're in the right directory
$wp_config_path = '../wp-config.php';
if (!file_exists($wp_config_path)) {
    $wp_config_path = '../../wp-config.php';
}
if (!file_exists($wp_config_path)) {
    $wp_config_path = '../../../wp-config.php';
}

if (!file_exists($wp_config_path)) {
    echo "❌ wp-config.php not found. Please run this script from the plugin directory.\n";
    exit;
}

echo "✅ Found wp-config.php at: $wp_config_path\n\n";

// Read current wp-config.php
$current_config = file_get_contents($wp_config_path);

// Check if Redis configuration exists
if (strpos($current_config, 'WP_REDIS_HOST') !== false) {
    echo "⚠️ Redis configuration detected in wp-config.php\n";
    
    // Create backup
    $backup_path = dirname($wp_config_path) . '/wp-config-backup-' . date('Y-m-d-H-i-s') . '.php';
    if (copy($wp_config_path, $backup_path)) {
        echo "✅ Backup created: " . basename($backup_path) . "\n";
    } else {
        echo "❌ Failed to create backup\n";
        exit;
    }
    
    // Remove Redis configuration
    $fixed_config = preg_replace('/define\(\s*\'WP_CACHE\',\s*true\s*\);\s*\/\/\s*Redis Configuration.*?define\(\s*\'WP_REDIS_DISABLED\',\s*true\s*\);/s', "// Disable WordPress caching to prevent Redis connection issues\ndefine( 'WP_CACHE', false );", $current_config);
    
    // If the regex didn't work, do a manual replacement
    if ($fixed_config === $current_config) {
        echo "⚠️ Regex replacement failed, using manual replacement\n";
        
        // Remove Redis lines manually
        $lines = explode("\n", $current_config);
        $new_lines = [];
        $skip_redis = false;
        
        foreach ($lines as $line) {
            if (trim($line) === "define( 'WP_CACHE', true );") {
                $new_lines[] = "// Disable WordPress caching to prevent Redis connection issues";
                $new_lines[] = "define( 'WP_CACHE', false );";
                $skip_redis = true;
                continue;
            }
            
            if ($skip_redis && (strpos($line, 'WP_REDIS_') !== false || strpos($line, '// Redis Configuration') !== false)) {
                continue;
            }
            
            if ($skip_redis && trim($line) === '') {
                $skip_redis = false;
            }
            
            $new_lines[] = $line;
        }
        
        $fixed_config = implode("\n", $new_lines);
    }
    
    // Write fixed config
    if (file_put_contents($wp_config_path, $fixed_config)) {
        echo "✅ wp-config.php fixed successfully!\n";
        echo "✅ Redis configuration removed\n";
        echo "✅ WordPress caching disabled\n";
    } else {
        echo "❌ Failed to write fixed wp-config.php\n";
        echo "⚠️ Please manually edit wp-config.php and remove Redis configuration\n";
    }
    
} else {
    echo "✅ No Redis configuration found in wp-config.php\n";
    echo "✅ WordPress should be working normally\n";
}

echo "\n<h2>🔍 Verification</h2>\n";

// Check if the fix worked
$new_config = file_get_contents($wp_config_path);
if (strpos($new_config, 'WP_REDIS_HOST') === false) {
    echo "✅ Redis configuration successfully removed\n";
} else {
    echo "❌ Redis configuration still present - manual fix required\n";
}

if (strpos($new_config, "define( 'WP_CACHE', false );") !== false) {
    echo "✅ WordPress caching disabled\n";
} else {
    echo "❌ WordPress caching still enabled\n";
}

echo "\n<h2>📋 Next Steps</h2>\n";
echo "1. ✅ WordPress staging site should now work without Redis errors\n";
echo "2. ✅ If you want to use Redis later, install Redis server and PHP extension\n";
echo "3. ✅ Then re-enable caching in wp-config.php\n";
echo "4. ✅ Backup file created for safety\n";

echo "\n<h2>🚀 Quick Test</h2>\n";
echo "Try accessing your WordPress staging site now. It should load without Redis errors.\n";

echo "</div>\n";
echo "<p><strong>Fix completed at: " . date('Y-m-d H:i:s') . "</strong></p>\n";
echo "<p><a href='../' style='background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;'>← Back to WordPress</a></p>\n";
?> 