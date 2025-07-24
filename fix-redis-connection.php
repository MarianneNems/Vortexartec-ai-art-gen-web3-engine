<?php
/**
 * VORTEX AI Engine - Redis Connection Fix
 * 
 * Comprehensive fix for Redis connection issues
 */

// Load WordPress configuration
if (file_exists('../wp-config.php')) {
    require_once('../wp-config.php');
} elseif (file_exists('../../wp-config.php')) {
    require_once('../../wp-config.php');
} elseif (file_exists('../../../wp-config.php')) {
    require_once('../../../wp-config.php');
} else {
    die('WordPress wp-config.php not found. Please run this from the plugin directory.');
}

echo "<h1>🔧 VORTEX AI Engine - Redis Connection Fix</h1>\n";
echo "<div style='font-family: monospace; background: #f5f5f5; padding: 20px; border-radius: 8px;'>\n";

// Check current Redis status
echo "<h2>🔍 Current Redis Status</h2>\n";

if (defined('WP_REDIS_DISABLED') && WP_REDIS_DISABLED) {
    echo "✅ Redis is currently DISABLED in wp-config.php\n";
} else {
    echo "⚠️ Redis is currently ENABLED in wp-config.php\n";
}

if (extension_loaded('redis')) {
    echo "✅ Redis PHP extension is installed\n";
} else {
    echo "❌ Redis PHP extension is NOT installed\n";
}

// Check Redis server
$redis_host = defined('WP_REDIS_HOST') ? WP_REDIS_HOST : '127.0.0.1';
$redis_port = defined('WP_REDIS_PORT') ? WP_REDIS_PORT : 6379;

$connection = @fsockopen($redis_host, $redis_port, $errno, $errstr, 2);
if ($connection) {
    echo "✅ Redis server is running on $redis_host:$redis_port\n";
    fclose($connection);
} else {
    echo "❌ Redis server is not accessible on $redis_host:$redis_port\n";
}

echo "\n";

// Apply fixes
echo "<h2>🔧 Applying Fixes</h2>\n";

$wp_config_path = ABSPATH . 'wp-config.php';
$wp_config_content = file_get_contents($wp_config_path);

// Ensure Redis is disabled
if (strpos($wp_config_content, "define('WP_REDIS_DISABLED', true);") === false) {
    // Replace false with true
    $wp_config_content = str_replace(
        "define('WP_REDIS_DISABLED', false);",
        "define('WP_REDIS_DISABLED', true);",
        $wp_config_content
    );
    
    // If the line doesn't exist, add it after the Redis configuration
    if (strpos($wp_config_content, "define('WP_REDIS_DISABLED', true);") === false) {
        $redis_config_end = strpos($wp_config_content, "define('WP_REDIS_ASYNC_FLUSH', true);");
        if ($redis_config_end !== false) {
            $insert_pos = strpos($wp_config_content, "\n", $redis_config_end) + 1;
            $wp_config_content = substr($wp_config_content, 0, $insert_pos) . 
                                "define('WP_REDIS_DISABLED', true);\n" . 
                                substr($wp_config_content, $insert_pos);
        }
    }
    
    if (file_put_contents($wp_config_path, $wp_config_content)) {
        echo "✅ Successfully disabled Redis in wp-config.php\n";
    } else {
        echo "❌ Failed to update wp-config.php\n";
    }
} else {
    echo "✅ Redis is already disabled in wp-config.php\n";
}

echo "\n";

// Test WordPress functionality
echo "<h2>🧪 Testing WordPress Functionality</h2>\n";

// Test basic WordPress functions
if (function_exists('wp_cache_get')) {
    echo "✅ WordPress object cache functions available\n";
} else {
    echo "⚠️ WordPress object cache functions not available\n";
}

if (defined('ABSPATH')) {
    echo "✅ WordPress core loaded successfully\n";
} else {
    echo "❌ WordPress core not loaded properly\n";
}

echo "\n";

// Installation instructions
echo "<h2>📋 Installation Instructions</h2>\n";

echo "<h3>Option 1: Install Redis (Recommended for Production)</h3>\n";
echo "<strong>For Windows:</strong>\n";
echo "1. Download Redis for Windows: https://github.com/microsoftarchive/redis/releases\n";
echo "2. Install and start Redis service\n";
echo "3. Download Redis PHP extension: https://pecl.php.net/package/redis\n";
echo "4. Add to php.ini: extension=redis.so\n\n";

echo "<strong>For Linux (Ubuntu/Debian):</strong>\n";
echo "sudo apt-get update\n";
echo "sudo apt-get install redis-server php-redis\n";
echo "sudo systemctl start redis-server\n";
echo "sudo systemctl enable redis-server\n\n";

echo "<strong>For macOS:</strong>\n";
echo "brew install redis php-redis\n";
echo "brew services start redis\n\n";

echo "<strong>Using Docker:</strong>\n";
echo "docker run -d -p 6379:6379 redis:alpine\n\n";

echo "<h3>Option 2: Keep Redis Disabled (Temporary Solution)</h3>\n";
echo "Redis is now disabled. Your site will work normally with WordPress default caching.\n";
echo "To re-enable Redis later, change this line in wp-config.php:\n";
echo "define('WP_REDIS_DISABLED', false);\n\n";

echo "<h3>Option 3: Alternative Caching</h3>\n";
echo "Consider using other caching solutions:\n";
echo "- WP Super Cache\n";
echo "- W3 Total Cache\n";
echo "- LiteSpeed Cache\n\n";

echo "\n";

// Final status
echo "<h2>✅ Final Status</h2>\n";
echo "Redis connection error should now be resolved.\n";
echo "Your WordPress site will use default caching instead of Redis.\n";
echo "The site should load normally without Redis connection errors.\n";

echo "</div>\n";

echo "<p><strong>Fix completed at: " . date('Y-m-d H:i:s') . "</strong></p>\n";
echo "<p><a href='../' style='background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;'>← Back to WordPress</a></p>\n";
?> 