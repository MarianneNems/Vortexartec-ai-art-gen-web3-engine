<?php
/**
 * Simple Redis Connection Test
 */

echo "=== VORTEX AI Engine - Simple Redis Test ===\n\n";

// Test 1: Check Redis extension
echo "1. Checking Redis extension...\n";
if (extension_loaded('redis')) {
    echo "   ✅ Redis extension is installed\n";
    echo "   Version: " . phpversion('redis') . "\n";
} else {
    echo "   ❌ Redis extension is NOT installed\n";
    echo "   Please install Redis extension for PHP\n";
}
echo "\n";

// Test 2: Check Redis configuration from wp-config.php
echo "2. Checking Redis configuration...\n";
if (file_exists('../wp-config.php')) {
    // Load wp-config.php to get Redis settings
    $wp_config_content = file_get_contents('../wp-config.php');
    
    // Extract Redis settings
    preg_match("/define\('WP_REDIS_HOST',\s*'([^']+)'\);/", $wp_config_content, $host_match);
    preg_match("/define\('WP_REDIS_PORT',\s*(\d+)\);/", $wp_config_content, $port_match);
    preg_match("/define\('WP_REDIS_DISABLED',\s*(true|false)\);/", $wp_config_content, $disabled_match);
    
    $host = $host_match[1] ?? '127.0.0.1';
    $port = $port_match[1] ?? 6379;
    $disabled = $disabled_match[1] ?? 'false';
    
    echo "   Host: $host\n";
    echo "   Port: $port\n";
    echo "   Disabled: $disabled\n";
    
    if ($disabled === 'true') {
        echo "   ⚠️ Redis is disabled in wp-config.php\n";
    }
} else {
    echo "   ❌ wp-config.php not found\n";
    $host = '127.0.0.1';
    $port = 6379;
}
echo "\n";

// Test 3: Test Redis connection
echo "3. Testing Redis connection...\n";
if (extension_loaded('redis')) {
    try {
        $redis = new Redis();
        $connected = $redis->connect($host, $port, 2.0);
        
        if ($connected) {
            echo "   ✅ Successfully connected to Redis\n";
            
            // Test basic operations
            $test_key = 'vortex_test_' . time();
            $test_value = 'test_value_' . time();
            
            $redis->set($test_key, $test_value);
            $retrieved = $redis->get($test_key);
            
            if ($retrieved === $test_value) {
                echo "   ✅ Basic operations working\n";
            } else {
                echo "   ❌ Basic operations failed\n";
            }
            
            $redis->del($test_key);
            $redis->close();
        } else {
            echo "   ❌ Failed to connect to Redis\n";
        }
    } catch (Exception $e) {
        echo "   ❌ Redis connection error: " . $e->getMessage() . "\n";
    }
} else {
    echo "   ⚠️ Cannot test connection - Redis extension not installed\n";
}
echo "\n";

// Test 4: Check if Redis server is running
echo "4. Checking Redis server status...\n";
$connection = @fsockopen($host, $port, $errno, $errstr, 3);
if ($connection) {
    echo "   ✅ Redis server is running and accessible\n";
    fclose($connection);
} else {
    echo "   ❌ Redis server is not accessible\n";
    echo "   Error: $errstr ($errno)\n";
}
echo "\n";

// Test 5: Recommendations
echo "5. Recommendations:\n";
if (!extension_loaded('redis')) {
    echo "   🔧 Install Redis PHP extension:\n";
    echo "      Windows: Download from https://pecl.php.net/package/redis\n";
    echo "      Linux: sudo apt-get install php-redis\n";
    echo "      macOS: brew install php-redis\n";
    echo "\n";
}

if (!isset($connection) || !$connection) {
    echo "   🔧 Install/Start Redis server:\n";
    echo "      Windows: Download from https://redis.io/download\n";
    echo "      Linux: sudo apt-get install redis-server\n";
    echo "      macOS: brew install redis && brew services start redis\n";
    echo "      Docker: docker run -d -p 6379:6379 redis:alpine\n";
    echo "\n";
}

echo "   🔧 Quick fix - Disable Redis temporarily:\n";
echo "      Add this line to wp-config.php:\n";
echo "      define('WP_REDIS_DISABLED', true);\n";
echo "\n";

echo "=== Test completed at " . date('Y-m-d H:i:s') . " ===\n";
?> 