<?php
/**
 * VORTEX AI Engine - Redis Connection Test
 * 
 * Test script to diagnose Redis connection issues
 */

// Load WordPress configuration
if (file_exists('../wp-config.php')) {
    require_once('../wp-config.php');
} elseif (file_exists('../../wp-config.php')) {
    require_once('../../wp-config.php');
} else {
    die('WordPress wp-config.php not found. Please run this from the plugin directory.');
}

echo "<h1>🔍 VORTEX AI Engine - Redis Connection Test</h1>\n";
echo "<div style='font-family: monospace; background: #f5f5f5; padding: 20px; border-radius: 8px;'>\n";

// Test 1: Check if Redis extension is installed
echo "<h2>1. Redis Extension Check</h2>\n";
if (extension_loaded('redis')) {
    echo "✅ Redis extension is installed\n";
    echo "Version: " . phpversion('redis') . "\n";
} else {
    echo "❌ Redis extension is NOT installed\n";
    echo "Please install the Redis extension for PHP\n";
    echo "For Windows: Download from https://pecl.php.net/package/redis\n";
    echo "For Linux: sudo apt-get install php-redis (Ubuntu/Debian)\n";
}

echo "\n";

// Test 2: Check Redis configuration
echo "<h2>2. Redis Configuration</h2>\n";
$config = array(
    'host' => defined('WP_REDIS_HOST') ? WP_REDIS_HOST : '127.0.0.1',
    'port' => defined('WP_REDIS_PORT') ? WP_REDIS_PORT : 6379,
    'database' => defined('WP_REDIS_DATABASE') ? WP_REDIS_DATABASE : 0,
    'timeout' => defined('WP_REDIS_TIMEOUT') ? WP_REDIS_TIMEOUT : 2.5,
    'prefix' => defined('WP_REDIS_PREFIX') ? WP_REDIS_PREFIX : 'wp_',
    'client' => defined('WP_REDIS_CLIENT') ? WP_REDIS_CLIENT : 'phpredis'
);

echo "Host: " . $config['host'] . "\n";
echo "Port: " . $config['port'] . "\n";
echo "Database: " . $config['database'] . "\n";
echo "Timeout: " . $config['timeout'] . "\n";
echo "Prefix: " . $config['prefix'] . "\n";
echo "Client: " . $config['client'] . "\n";

echo "\n";

// Test 3: Test basic connection
echo "<h2>3. Basic Connection Test</h2>\n";
try {
    $redis = new Redis();
    $connected = $redis->connect($config['host'], $config['port'], $config['timeout']);
    
    if ($connected) {
        echo "✅ Successfully connected to Redis\n";
        echo "Redis Server Info: " . $redis->info('server')['redis_version'] . "\n";
        echo "Connected Clients: " . $redis->info('clients')['connected_clients'] . "\n";
        echo "Used Memory: " . round($redis->info('memory')['used_memory_human']) . "\n";
    } else {
        echo "❌ Failed to connect to Redis\n";
    }
} catch (Exception $e) {
    echo "❌ Redis connection error: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 4: Test basic operations
echo "<h2>4. Basic Operations Test</h2>\n";
if (isset($redis) && $connected) {
    try {
        // Test set/get
        $test_key = $config['prefix'] . 'test_connection';
        $test_value = 'VORTEX_AI_TEST_' . time();
        
        $redis->set($test_key, $test_value);
        $retrieved = $redis->get($test_key);
        
        if ($retrieved === $test_value) {
            echo "✅ Set/Get operations working\n";
        } else {
            echo "❌ Set/Get operations failed\n";
        }
        
        // Clean up
        $redis->del($test_key);
        
        // Test ping
        $ping = $redis->ping();
        if ($ping === '+PONG' || $ping === true) {
            echo "✅ Ping operation working\n";
        } else {
            echo "❌ Ping operation failed\n";
        }
        
    } catch (Exception $e) {
        echo "❌ Redis operations error: " . $e->getMessage() . "\n";
    }
}

echo "\n";

// Test 5: Check if Redis server is running
echo "<h2>5. Redis Server Status</h2>\n";
$connection = @fsockopen($config['host'], $config['port'], $errno, $errstr, 5);
if ($connection) {
    echo "✅ Redis server is running and accessible\n";
    fclose($connection);
} else {
    echo "❌ Redis server is not accessible\n";
    echo "Error: $errstr ($errno)\n";
    echo "\nPossible solutions:\n";
    echo "1. Start Redis server: redis-server\n";
    echo "2. Check if Redis is installed: redis-cli ping\n";
    echo "3. Check firewall settings\n";
    echo "4. Verify Redis is running on the correct port\n";
}

echo "\n";

// Test 6: Alternative connection methods
echo "<h2>6. Alternative Connection Test</h2>\n";
$alternative_hosts = array('localhost', '127.0.0.1', 'redis', 'redis-server');
$alternative_ports = array(6379, 6380);

foreach ($alternative_hosts as $host) {
    foreach ($alternative_ports as $port) {
        if ($host === $config['host'] && $port === $config['port']) {
            continue; // Skip current config
        }
        
        $test_connection = @fsockopen($host, $port, $errno, $errstr, 2);
        if ($test_connection) {
            echo "✅ Alternative connection found: $host:$port\n";
            fclose($test_connection);
        }
    }
}

echo "\n";

// Test 7: WordPress Object Cache Test
echo "<h2>7. WordPress Object Cache Test</h2>\n";
if (function_exists('wp_cache_get')) {
    $test_key = 'vortex_test_' . time();
    $test_value = 'test_value_' . time();
    
    wp_cache_set($test_key, $test_value, '', 60);
    $retrieved = wp_cache_get($test_key);
    
    if ($retrieved === $test_value) {
        echo "✅ WordPress object cache working\n";
    } else {
        echo "❌ WordPress object cache not working\n";
    }
    
    wp_cache_delete($test_key);
} else {
    echo "⚠️ WordPress object cache functions not available\n";
}

echo "\n";

// Test 8: Recommendations
echo "<h2>8. Recommendations</h2>\n";
if (!extension_loaded('redis')) {
    echo "🔧 Install Redis extension for PHP\n";
    echo "   - Windows: Download from https://pecl.php.net/package/redis\n";
    echo "   - Linux: sudo apt-get install php-redis\n";
    echo "   - macOS: brew install php-redis\n";
}

if (!isset($redis) || !$connected) {
    echo "🔧 Start Redis server\n";
    echo "   - Linux/macOS: redis-server\n";
    echo "   - Windows: Download Redis for Windows\n";
    echo "   - Docker: docker run -d -p 6379:6379 redis:alpine\n";
}

echo "🔧 Check Redis configuration in wp-config.php\n";
echo "🔧 Ensure Redis server is running on the correct host and port\n";
echo "🔧 Check firewall and network connectivity\n";

echo "\n";

// Test 9: Quick Fix Options
echo "<h2>9. Quick Fix Options</h2>\n";
echo "Option 1: Disable Redis temporarily\n";
echo "Add this to wp-config.php:\n";
echo "define('WP_REDIS_DISABLED', true);\n\n";

echo "Option 2: Use different Redis host/port\n";
echo "Update in wp-config.php:\n";
echo "define('WP_REDIS_HOST', 'localhost');\n";
echo "define('WP_REDIS_PORT', 6379);\n\n";

echo "Option 3: Install Redis server\n";
echo "Linux: sudo apt-get install redis-server\n";
echo "macOS: brew install redis\n";
echo "Windows: Download from https://redis.io/download\n";

echo "</div>\n";

// Close Redis connection if open
if (isset($redis) && $connected) {
    $redis->close();
}

echo "<p><strong>Test completed at: " . date('Y-m-d H:i:s') . "</strong></p>\n";
?> 